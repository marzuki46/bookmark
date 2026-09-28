package com.keuangan.app.ui.family

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.keuangan.app.data.ApiResult
import com.keuangan.app.data.FamilyCategoryDto
import com.keuangan.app.data.FamilyTransactionDto
import com.keuangan.app.data.FamilyTransactionRequest
import com.keuangan.app.data.IncomeSourceDto
import com.keuangan.app.data.KeuanganRepository
import com.keuangan.app.data.getOrNull
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.async
import kotlinx.coroutines.launch

data class TxForm(
    val id: Int? = null,
    val type: String = "expense",
    val amount: String = "",
    val description: String = "",
    val date: String = "",
    val payer: String = "shared",
    val categoryId: Int? = null,
    val incomeSourceId: Int? = null,
)

data class FamilyTransactionsUiState(
    val loading: Boolean = true,
    val saving: Boolean = false,
    val syncing: Boolean = false,
    val search: String = "",
    val typeFilter: String? = null,
    val payerFilter: String? = null,
    val items: List<FamilyTransactionDto> = emptyList(),
    val categories: List<FamilyCategoryDto> = emptyList(),
    val incomeSources: List<IncomeSourceDto> = emptyList(),
    val error: String? = null,
    val formError: String? = null,
    /** True when the current data came from the local offline cache. */
    val offline: Boolean = false,
    /** Writes still waiting to reach the server. */
    val pendingCount: Int = 0,
    /** Problem blocking a queued write from syncing, inviting a retry. */
    val pendingError: String? = null,
    /** Instant rule nudge returned by a create/update, shown above the list. */
    val nudge: String? = null,
    val form: TxForm? = null,
)

class FamilyTransactionsViewModel(private val repository: KeuanganRepository) : ViewModel() {

    private var activeFamilyId: Int? = null

    private val _state = MutableStateFlow(FamilyTransactionsUiState())
    val state: StateFlow<FamilyTransactionsUiState> = _state.asStateFlow()

    fun load(familyId: Int) {
        activeFamilyId = familyId
        _state.update { it.copy(loading = it.items.isEmpty(), error = null) }
        viewModelScope.launch {
            // Filters are independent of the transaction list; fetch all three
            // in parallel so opening this menu does not feel serialised.
            val catsDeferred = async { repository.familyCategories(familyId).getOrNull().orEmpty() }
            val sourcesDeferred = async { repository.incomeSources(familyId).getOrNull().orEmpty() }
            val filters = _state.value
            val transactionsDeferred = async { repository.familyTransactions(
                familyId = familyId,
                type = filters.typeFilter,
                payer = filters.payerFilter,
                query = filters.search.ifBlank { null },
            ) }
            val cats = catsDeferred.await()
            val sources = sourcesDeferred.await()
            transactionsDeferred.await().let { loaded ->
                when (val result = loaded.result) {
                    is ApiResult.Ok -> _state.update {
                        it.copy(
                            loading = false,
                            items = result.value.data,
                            categories = cats,
                            incomeSources = sources,
                            offline = loaded.offline,
                            error = null,
                            pendingCount = repository.pendingCount(familyId),
                            pendingError = repository.failedPendingMessage(familyId),
                        )
                    }
                    is ApiResult.Err -> _state.update {
                        it.copy(loading = false, offline = loaded.offline, error = result.message)
                    }
                }
            }
        }
    }

    fun onSearchChange(value: String) {
        _state.update { it.copy(search = value.filter { c -> c != '\n' }.take(50)) }
    }

    fun submitSearch(familyId: Int) = load(familyId)

    fun setTypeFilter(type: String?) {
        _state.update { it.copy(typeFilter = type) }
        activeFamilyId?.let(::load)
    }

    fun setPayerFilter(payer: String?) {
        _state.update { it.copy(payerFilter = payer) }
        activeFamilyId?.let(::load)
    }

    fun openCreate() {
        _state.update {
            it.copy(
                form = TxForm(
                    type = it.typeFilter?.takeIf { f -> f == "income" || f == "expense" } ?: "expense",
                    date = todayIso(),
                ),
            )
        }
    }

    fun openEdit(tx: FamilyTransactionDto) {
        _state.update {
            it.copy(
                form = TxForm(
                    id = tx.id,
                    type = tx.type,
                    amount = formatAmount(tx.amount),
                    description = tx.description.orEmpty(),
                    date = tx.date.takeIf(String::isNotBlank) ?: todayIso(),
                    payer = tx.payer,
                    categoryId = tx.category?.id,
                    incomeSourceId = tx.incomeSource?.id,
                ),
            )
        }
    }

    fun updateForm(transform: (TxForm) -> TxForm) {
        _state.update { it.copy(form = _state.value.form?.let(transform), formError = null) }
    }

    fun saveForm(familyId: Int) {
        val form = _state.value.form ?: return
        val amount = form.amount.replace(",", ".").toDoubleOrNull()
        if (amount == null || amount <= 0) {
            _state.update { it.copy(formError = "Jumlah harus angka lebih dari 0.") }
            return
        }
        if (form.description.isBlank()) {
            _state.update { it.copy(formError = "Keterangan harus diisi.") }
            return
        }
        if (_state.value.saving) return

        _state.update { it.copy(saving = true, formError = null) }
        viewModelScope.launch {
            val body = FamilyTransactionRequest(
                type = form.type,
                amount = amount,
                description = form.description.trim().take(255),
                date = form.date,
                payer = form.payer,
                categoryId = form.categoryId,
                incomeSourceId = if (form.type == "income") form.incomeSourceId else null,
            )
            when (val result = repository.saveFamilyTransaction(familyId, body, form.id)) {
                is ApiResult.Ok -> {
                    _state.update {
                        it.copy(
                            saving = false,
                            form = null,
                            nudge = result.value.nudge?.message,
                            pendingCount = repository.pendingCount(familyId),
                        )
                    }
                    load(familyId)
                }
                is ApiResult.Err -> _state.update {
                    it.copy(saving = false, formError = result.message)
                }
            }
        }
    }

    fun dismissNudge() = _state.update { it.copy(nudge = null) }

    /**
     * Replays queued offline writes now (invoked from the offline banner's
     * retry button) and refreshes the list to their server state.
     */
    fun retrySync(familyId: Int) {
        viewModelScope.launch {
            _state.update { it.copy(syncing = true) }
            repository.syncPendingTransactions()
            _state.update { it.copy(syncing = false) }
            load(familyId)
        }
    }

    fun closeForm() = _state.update { it.copy(form = null, formError = null) }

    fun delete(familyId: Int, tx: FamilyTransactionDto) {
        viewModelScope.launch {
            repository.deleteFamilyTransaction(familyId, tx.id)
            _state.update { it.copy(pendingCount = repository.pendingCount(familyId)) }
            load(familyId)
        }
    }

    private fun formatAmount(value: Double): String =
        if (value == value.toLong().toDouble()) value.toLong().toString() else value.toString()

    private fun todayIso(): String = java.time.LocalDate.now().toString()
}
