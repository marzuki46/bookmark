package com.keuangan.app.ui.family

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.keuangan.app.data.ApiResult
import com.keuangan.app.data.FamilyGoalDto
import com.keuangan.app.data.FamilyGoalRequest
import com.keuangan.app.data.KeuanganRepository
import com.keuangan.app.ui.formatRupiah
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch

data class GoalForm(
    val id: Int? = null,
    val name: String = "",
    val type: String = "custom",
    val targetAmount: String = "",
    val monthlyAllocation: String = "",
    val deadline: String = "",
)

data class FamilyGoalsUiState(
    val loading: Boolean = true,
    val saving: Boolean = false,
    val allItems: List<FamilyGoalDto> = emptyList(),
    val search: String = "",
    val typeFilter: String? = null,
    val statusFilter: String? = null,
    val error: String? = null,
    val formError: String? = null,
    val actionMessage: String? = null,
    val form: GoalForm? = null,
    val contributeGoal: FamilyGoalDto? = null,
    val contributeAmount: String = "",
) {
    /** Visible rows after the type / status / search chips (client-side). */
    val items: List<FamilyGoalDto>
        get() = allItems
            .filter { goal ->
                (typeFilter == null || goal.type == typeFilter) &&
                    (statusFilter == null || goal.status == statusFilter) &&
                    (search.isBlank() || goal.name.contains(search.trim(), ignoreCase = true))
            }
            .sortedWith(
                compareBy<FamilyGoalDto> { it.status == "completed" }
                    .thenBy { it.deadline ?: "" },
            )
}

class FamilyGoalsViewModel(private val repository: KeuanganRepository) : ViewModel() {

    private val _state = MutableStateFlow(FamilyGoalsUiState())
    val state: StateFlow<FamilyGoalsUiState> = _state.asStateFlow()

    fun load(familyId: Int) {
        _state.update { it.copy(loading = it.allItems.isEmpty(), error = null) }
        viewModelScope.launch {
            when (val result = repository.goals(familyId)) {
                is ApiResult.Ok -> _state.update {
                    it.copy(loading = false, allItems = result.value, error = null)
                }
                is ApiResult.Err -> _state.update {
                    it.copy(loading = false, error = result.message)
                }
            }
        }
    }

    fun setTypeFilter(type: String?) {
        _state.update { it.copy(typeFilter = if (it.typeFilter == type) null else type) }
    }

    fun setStatusFilter(status: String?) {
        _state.update { it.copy(statusFilter = if (it.statusFilter == status) null else status) }
    }

    fun onSearchChange(value: String) {
        _state.update { it.copy(search = value) }
    }

    fun clearFilters() {
        _state.update { it.copy(typeFilter = null, statusFilter = null, search = "") }
    }

    /** Drops the filters but keeps the search box content — see Debts' twin. */
    fun clearFiltersKeepSearch() {
        _state.update { it.copy(typeFilter = null, statusFilter = null) }
    }

    /** Sets both filters outright for the filter sheet; no toggle behaviour. */
    fun applyFilters(type: String?, status: String?) {
        _state.update { it.copy(typeFilter = type, statusFilter = status) }
    }

    fun openCreate() {
        _state.update { it.copy(form = GoalForm(), formError = null) }
    }

    fun openEdit(goal: FamilyGoalDto) {
        _state.update {
            it.copy(
                form = GoalForm(
                    id = goal.id,
                    name = goal.name,
                    type = goal.type,
                    targetAmount = formatAmount(goal.targetAmount),
                    monthlyAllocation = goal.monthlyAllocation.takeIf { a -> a > 0 }?.let(::formatAmount).orEmpty(),
                    deadline = goal.deadline.orEmpty(),
                ),
                formError = null,
            )
        }
    }

    fun updateForm(transform: (GoalForm) -> GoalForm) {
        _state.update { it.copy(form = _state.value.form?.let(transform), formError = null) }
    }

    fun saveForm(familyId: Int) {
        val form = _state.value.form ?: return
        val target = form.targetAmount.replace(",", ".").toDoubleOrNull()
        if (target == null || target <= 0) {
            _state.update { it.copy(formError = "Target harus angka lebih dari 0.") }
            return
        }
        if (form.name.isBlank()) {
            _state.update { it.copy(formError = "Nama target harus diisi.") }
            return
        }
        if (_state.value.saving) return

        val monthly = form.monthlyAllocation.replace(",", ".").toDoubleOrNull()
        val body = FamilyGoalRequest(
            name = form.name.trim().take(120),
            type = form.type,
            targetAmount = target,
            monthlyAllocation = monthly?.takeIf { it > 0 },
            deadline = form.deadline.ifBlank { null },
        )

        _state.update { it.copy(saving = true, formError = null) }
        viewModelScope.launch {
            when (val result = repository.saveGoal(familyId, body, form.id)) {
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

    fun openContribute(goal: FamilyGoalDto) {
        _state.update { it.copy(contributeGoal = goal, contributeAmount = "", formError = null) }
    }

    fun onContributeAmountChange(value: String) {
        _state.update { it.copy(contributeAmount = value) }
    }

    fun closeContribute() = _state.update { it.copy(contributeGoal = null, contributeAmount = "") }

    fun confirmContribute(familyId: Int) {
        val goal = _state.value.contributeGoal ?: return
        val amount = _state.value.contributeAmount.replace(",", ".").toDoubleOrNull()
        if (amount == null || amount <= 0) {
            _state.update { it.copy(formError = "Jumlah harus angka lebih dari 0.") }
            return
        }
        if (_state.value.saving) return

        _state.update { it.copy(saving = true, formError = null) }
        viewModelScope.launch {
            when (val result = repository.contributeToGoal(familyId, goal.id, amount)) {
                is ApiResult.Ok -> {
                    _state.update {
                        it.copy(
                            saving = false,
                            contributeGoal = null,
                            contributeAmount = "",
                            actionMessage = "Tabungan ${result.value.name} bertambah ${formatRupiah(amount)}.",
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

    fun delete(familyId: Int, goal: FamilyGoalDto) {
        viewModelScope.launch {
            repository.deleteGoal(familyId, goal.id)
            load(familyId)
        }
    }

    private fun formatAmount(value: Double): String =
        if (value == value.toLong().toDouble()) value.toLong().toString() else value.toString()
}