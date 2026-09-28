package com.keuangan.app.ui.family

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.keuangan.app.data.ApiResult
import com.keuangan.app.data.IncomeSourceDto
import com.keuangan.app.data.IncomeSourceRequest
import com.keuangan.app.data.KeuanganRepository
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch

data class IncomeSourceForm(
    val id: Int? = null,
    val name: String = "",
    val type: String = "other",
)

data class FamilyIncomeSourcesUiState(
    val loading: Boolean = true,
    val saving: Boolean = false,
    val items: List<IncomeSourceDto> = emptyList(),
    val error: String? = null,
    val formError: String? = null,
    val form: IncomeSourceForm? = null,
)

class FamilyIncomeSourcesViewModel(private val repository: KeuanganRepository) : ViewModel() {

    private val _state = MutableStateFlow(FamilyIncomeSourcesUiState())
    val state: StateFlow<FamilyIncomeSourcesUiState> = _state.asStateFlow()

    fun load(familyId: Int) {
        _state.update { it.copy(loading = it.items.isEmpty(), error = null) }
        viewModelScope.launch {
            when (val result = repository.incomeSources(familyId)) {
                is ApiResult.Ok -> _state.update {
                    it.copy(loading = false, items = result.value, error = null)
                }
                is ApiResult.Err -> _state.update {
                    it.copy(loading = false, error = result.message)
                }
            }
        }
    }

    fun openCreate() {
        _state.update { it.copy(form = IncomeSourceForm(), formError = null) }
    }

    fun openEdit(source: IncomeSourceDto) {
        _state.update {
            it.copy(
                form = IncomeSourceForm(id = source.id, name = source.name, type = source.type),
                formError = null,
            )
        }
    }

    fun updateForm(transform: (IncomeSourceForm) -> IncomeSourceForm) {
        _state.update { it.copy(form = _state.value.form?.let(transform), formError = null) }
    }

    fun saveForm(familyId: Int) {
        val form = _state.value.form ?: return
        if (form.name.isBlank()) {
            _state.update { it.copy(formError = "Nama harus diisi.") }
            return
        }
        if (_state.value.saving) return

        val body = IncomeSourceRequest(
            name = form.name.trim().take(120),
            type = form.type,
        )

        _state.update { it.copy(saving = true, formError = null) }
        viewModelScope.launch {
            when (val result = repository.saveIncomeSource(familyId, body, form.id)) {
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

    fun delete(familyId: Int, source: IncomeSourceDto) {
        viewModelScope.launch {
            repository.deleteIncomeSource(familyId, source.id)
            load(familyId)
        }
    }
}