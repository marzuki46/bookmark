package com.keuangan.app.ui.transactions

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.keuangan.app.data.ApiResult
import com.keuangan.app.data.CategoryDto
import com.keuangan.app.data.KeuanganRepository
import com.keuangan.app.data.TransactionDto
import com.keuangan.app.data.TransactionRequest
import com.keuangan.app.ui.todayIso
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch

data class TransactionForm(
    val id: Int? = null,
    val type: String = "expense",
    val amount: String = "",
    val description: String = "",
    val date: String = todayIso(),
    val categoryId: Int? = null,
)

data class TransactionsUiState(
    val loading: Boolean = true,
    val items: List<TransactionDto> = emptyList(),
    val categories: List<CategoryDto> = emptyList(),
    val search: String = "",
    val typeFilter: String? = null,
    val categoryFilter: Int? = null,
    val error: String? = null,
    val form: TransactionForm? = null,
    val saving: Boolean = false,
    val formError: String? = null,
)

class TransactionsViewModel(private val repository: KeuanganRepository) : ViewModel() {

    private val _state = MutableStateFlow(TransactionsUiState())
    val state: StateFlow<TransactionsUiState> = _state.asStateFlow()

    init {
        loadCategories()
        load()
    }

    fun load() {
        _state.update { it.copy(loading = true, error = null) }
        viewModelScope.launch {
            when (val result = repository.transactions(
                period = "all_time",
                type = _state.value.typeFilter,
                categoryId = _state.value.categoryFilter,
                search = _state.value.search.takeIf { it.isNotBlank() },
            )) {
                is ApiResult.Ok -> _state.update {
                    it.copy(loading = false, items = result.value.data, error = null)
                }
                is ApiResult.Err -> _state.update {
                    it.copy(loading = false, error = result.message)
                }
            }
        }
    }

    private fun loadCategories() {
        viewModelScope.launch {
            when (val result = repository.categories()) {
                is ApiResult.Ok -> _state.update { it.copy(categories = result.value) }
                is ApiResult.Err -> Unit
            }
        }
    }

    fun onSearchChange(value: String) {
        _state.update { it.copy(search = value) }
    }

    fun submitSearch() = load()

    fun setTypeFilter(type: String?) {
        _state.update { it.copy(typeFilter = type) }
        load()
    }

    fun setCategoryFilter(categoryId: Int?) {
        _state.update { it.copy(categoryFilter = categoryId) }
        load()
    }

    fun openCreate() {
        _state.update {
            it.copy(form = TransactionForm(), formError = null)
        }
    }

    fun openEdit(transaction: TransactionDto) {
        _state.update {
            it.copy(
                form = TransactionForm(
                    id = transaction.id,
                    type = transaction.type,
                    amount = transaction.amount.toLong().toString(),
                    description = transaction.description.orEmpty(),
                    date = transaction.date.take(10),
                    categoryId = transaction.categoryId,
                ),
                formError = null,
            )
        }
    }

    fun closeForm() = _state.update { it.copy(form = null, formError = null) }

    fun updateForm(transform: (TransactionForm) -> TransactionForm) {
        _state.update { it.copy(form = it.form?.let(transform), formError = null) }
    }

    fun saveForm() {
        val form = _state.value.form ?: return
        if (_state.value.saving) return

        val amount = form.amount.toDoubleOrNull()
        if (amount == null || amount <= 0) {
            _state.update { it.copy(formError = "Jumlah harus lebih besar dari 0") }
            return
        }

        _state.update { it.copy(saving = true, formError = null) }
        val body = TransactionRequest(
            type = form.type,
            amount = amount,
            description = form.description.ifBlank { null },
            date = form.date.take(10),
            categoryId = form.categoryId,
        )

        viewModelScope.launch {
            val result = if (form.id == null) {
                repository.createTransaction(body)
            } else {
                repository.updateTransaction(form.id, body)
            }

            when (result) {
                is ApiResult.Ok -> {
                    _state.update { it.copy(saving = false, form = null) }
                    load()
                }
                is ApiResult.Err -> _state.update {
                    it.copy(saving = false, formError = result.message)
                }
            }
        }
    }

    fun delete(transaction: TransactionDto) {
        viewModelScope.launch {
            when (val result = repository.deleteTransaction(transaction.id)) {
                is ApiResult.Ok -> {
                    _state.update { current ->
                        current.copy(items = current.items.filterNot { it.id == transaction.id })
                    }
                }
                is ApiResult.Err -> _state.update { it.copy(error = result.message) }
            }
        }
    }
}
