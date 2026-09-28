package com.keuangan.app.ui.family

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.keuangan.app.data.ApiResult
import com.keuangan.app.data.FamilyBudgetDto
import com.keuangan.app.data.FamilyBudgetRequest
import com.keuangan.app.data.FamilyCategoryDto
import com.keuangan.app.data.KeuanganRepository
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import java.time.LocalDate

data class BudgetForm(
    val categoryId: Int? = null,
    val amount: String = "",
)

data class FamilyBudgetsUiState(
    val loading: Boolean = true,
    val saving: Boolean = false,
    val month: Int = LocalDate.now().monthValue,
    val year: Int = LocalDate.now().year,
    val items: List<FamilyBudgetDto> = emptyList(),
    val categories: List<FamilyCategoryDto> = emptyList(),
    val error: String? = null,
    val formError: String? = null,
    val form: BudgetForm? = null,
)

class FamilyBudgetsViewModel(private val repository: KeuanganRepository) : ViewModel() {

    private val _state = MutableStateFlow(FamilyBudgetsUiState())
    val state: StateFlow<FamilyBudgetsUiState> = _state.asStateFlow()

    fun load(familyId: Int) {
        _state.update { it.copy(loading = true, error = null) }
        viewModelScope.launch {
            val s = _state.value
            val withCategories = repository.familyCategories(familyId, type = "expense")
                .let { when (it) { is ApiResult.Ok -> it.value; is ApiResult.Err -> emptyList() } }

            when (val result = repository.budgets(familyId, s.month, s.year)) {
                is ApiResult.Ok -> _state.update {
                    it.copy(
                        loading = false,
                        items = result.value.budgets,
                        categories = withCategories,
                        error = null,
                    )
                }
                is ApiResult.Err -> _state.update {
                    it.copy(loading = false, error = result.message)
                }
            }
        }
    }

    /** Moves by [delta] months and refetches. */
    fun shiftMonth(familyId: Int, delta: Int) {
        val base = LocalDate.of(_state.value.year, _state.value.month, 1).plusMonths(delta.toLong())
        _state.update {
            it.copy(
                month = base.monthValue,
                year = base.year,
                form = null,
                formError = null,
            )
        }
        load(familyId)
    }

    fun openCreate() {
        _state.update { it.copy(form = BudgetForm(amount = ""), formError = null) }
    }

    fun openEdit(budget: FamilyBudgetDto) {
        _state.update {
            it.copy(
                form = BudgetForm(categoryId = budget.categoryId, amount = formatAmount(budget.amount)),
                formError = null,
            )
        }
    }

    fun updateForm(transform: (BudgetForm) -> BudgetForm) {
        _state.update { it.copy(form = _state.value.form?.let(transform), formError = null) }
    }

    fun saveForm(familyId: Int) {
        val form = _state.value.form ?: return
        val amount = form.amount.replace(",", ".").toDoubleOrNull()
        if (amount == null || amount < 0) {
            _state.update { it.copy(formError = "Jumlah harus angka tidak negatif.") }
            return
        }
        if (_state.value.saving) return

        val s = _state.value
        // Editing an already-budgeted category only updates the amount (the
        // server's update verb accepts amount alone); a new category is sent
        // with the full create payload. Both routes are covered by reusing the
        // existing row's id when a budget for that category already exists.
        val existingId = s.items.firstOrNull { it.categoryId == form.categoryId }?.id
        val body = FamilyBudgetRequest(
            categoryId = form.categoryId,
            month = s.month,
            year = s.year,
            amount = amount,
        )

        _state.update { it.copy(saving = true, formError = null) }
        viewModelScope.launch {
            when (val result = repository.saveBudget(familyId, body, existingId)) {
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

    fun delete(familyId: Int, budget: FamilyBudgetDto) {
        viewModelScope.launch {
            repository.deleteBudget(familyId, budget.id)
            load(familyId)
        }
    }

    private fun formatAmount(value: Double): String =
        if (value == value.toLong().toDouble()) value.toLong().toString() else value.toString()
}