package com.keuangan.app.ui.family

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.keuangan.app.data.ApiResult
import com.keuangan.app.data.FamilyDebtDto
import com.keuangan.app.data.FamilyDebtRequest
import com.keuangan.app.data.KeuanganRepository
import com.keuangan.app.ui.formatRupiah
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch

data class DebtForm(
    val id: Int? = null,
    val name: String = "",
    val type: String = "payable",
    val amount: String = "",
    val interestRate: String = "",
    val installment: String = "",
    val dueDate: String = "",
    val notes: String = "",
)

data class FamilyDebtsUiState(
    val loading: Boolean = true,
    val saving: Boolean = false,
    val typeFilter: String? = null,
    val statusFilter: String? = null,
    val items: List<FamilyDebtDto> = emptyList(),
    val error: String? = null,
    val formError: String? = null,
    val actionMessage: String? = null,
    val form: DebtForm? = null,
    val paymentDebt: FamilyDebtDto? = null,
    val paymentAmount: String = "",
)

class FamilyDebtsViewModel(private val repository: KeuanganRepository) : ViewModel() {

    private val _state = MutableStateFlow(FamilyDebtsUiState())
    val state: StateFlow<FamilyDebtsUiState> = _state.asStateFlow()

    fun load(familyId: Int) {
        _state.update { it.copy(loading = it.items.isEmpty(), error = null) }
        viewModelScope.launch {
            val filters = _state.value
            val result = repository.debts(familyId, filters.statusFilter)
            val items = when (result) {
                is ApiResult.Ok -> result.value.sortedWith(
                    compareByDescending<FamilyDebtDto> { it.status != "settled" }
                        .thenBy { it.dueDate ?: "" },
                )
                is ApiResult.Err -> {
                    _state.update { it.copy(loading = false, error = result.message) }
                    emptyList()
                }
            }
            _state.update {
                it.copy(loading = false, items = items, error = null)
            }
        }
    }

    fun setTypeFilter(type: String?) {
        _state.update { it.copy(typeFilter = type) }
    }

    fun setStatusFilter(status: String?) {
        _state.update { it.copy(statusFilter = status) }
    }

    fun openCreate() {
        _state.update {
            it.copy(
                form = DebtForm(type = it.typeFilter ?: "payable"),
                formError = null,
            )
        }
    }

    fun openEdit(debt: FamilyDebtDto) {
        _state.update {
            it.copy(
                form = DebtForm(
                    id = debt.id,
                    name = debt.name,
                    type = debt.type,
                    amount = formatAmount(debt.amount),
                    interestRate = debt.interestRate?.let(::formatAmount).orEmpty(),
                    installment = debt.installment?.let(::formatAmount).orEmpty(),
                    dueDate = debt.dueDate.orEmpty(),
                    notes = debt.notes.orEmpty(),
                ),
                formError = null,
            )
        }
    }

    fun updateForm(transform: (DebtForm) -> DebtForm) {
        _state.update { it.copy(form = _state.value.form?.let(transform), formError = null) }
    }

    fun saveForm(familyId: Int) {
        val form = _state.value.form ?: return
        val amount = form.amount.replace(",", ".").toDoubleOrNull()
        if (amount == null || amount <= 0) {
            _state.update { it.copy(formError = "Jumlah harus angka lebih dari 0.") }
            return
        }
        if (form.name.isBlank()) {
            _state.update { it.copy(formError = "Nama harus diisi.") }
            return
        }
        if (_state.value.saving) return

        val interestRate = form.interestRate.replace(",", ".").toDoubleOrNull()
        val installment = form.installment.replace(",", ".").toDoubleOrNull()
        val body = FamilyDebtRequest(
            name = form.name.trim().take(120),
            type = form.type,
            amount = amount,
            interestRate = interestRate,
            installment = installment,
            dueDate = form.dueDate.ifBlank { null },
            notes = form.notes.ifBlank { null },
        )

        _state.update { it.copy(saving = true, formError = null) }
        viewModelScope.launch {
            when (val result = repository.saveDebt(familyId, body, form.id)) {
                is ApiResult.Ok -> {
                    _state.update { it.copy(saving = false, form = null) }
                    load(familyId)
                }
                is ApiResult.Err -> _state.update {
                    it.copy(saving = false, formError = result.message)
                }
            }
        }
    }

    fun closeForm() = _state.update { it.copy(form = null, formError = null) }

    fun openPayment(debt: FamilyDebtDto) {
        _state.update { it.copy(paymentDebt = debt, paymentAmount = "") }
    }

    fun onPaymentAmountChange(value: String) {
        _state.update { it.copy(paymentAmount = value) }
    }

    fun closePayment() = _state.update { it.copy(paymentDebt = null, paymentAmount = "") }

    fun confirmPayment(familyId: Int) {
        val debt = _state.value.paymentDebt ?: return
        val amount = _state.value.paymentAmount.replace(",", ".").toDoubleOrNull()
        if (amount == null || amount <= 0) {
            _state.update { it.copy(formError = "Jumlah harus angka lebih dari 0.") }
            return
        }
        if (_state.value.saving) return

        _state.update { it.copy(saving = true, formError = null) }
        viewModelScope.launch {
            when (val result = repository.payDebt(familyId, debt.id, amount)) {
                is ApiResult.Ok -> {
                    _state.update {
                        it.copy(
                            saving = false,
                            paymentDebt = null,
                            paymentAmount = "",
                            actionMessage = "Pembayaran ${formatRupiah(amount)} dicatat.",
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

    fun dismissMessage() = _state.update { it.copy(actionMessage = null) }

    fun delete(familyId: Int, debt: FamilyDebtDto) {
        viewModelScope.launch {
            repository.deleteDebt(familyId, debt.id)
            load(familyId)
        }
    }

    /** Overpaying/rounding keeps display clean, not necessarily Math precision. */
    private fun formatAmount(value: Double): String =
        if (value == value.toLong().toDouble()) value.toLong().toString() else value.toString()
}