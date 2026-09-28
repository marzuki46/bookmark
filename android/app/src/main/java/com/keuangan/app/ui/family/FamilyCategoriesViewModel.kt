package com.keuangan.app.ui.family

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.keuangan.app.data.ApiResult
import com.keuangan.app.data.FamilyCategoryDto
import com.keuangan.app.data.FamilyCategoryRequest
import com.keuangan.app.data.KeuanganRepository
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch

data class CategoryForm(
    val id: Int? = null,
    val name: String = "",
    val type: String = "expense",
)

data class FamilyCategoriesUiState(
    val loading: Boolean = true,
    val saving: Boolean = false,
    val items: List<FamilyCategoryDto> = emptyList(),
    val error: String? = null,
    val formError: String? = null,
    val form: CategoryForm? = null,
)

class FamilyCategoriesViewModel(private val repository: KeuanganRepository) : ViewModel() {

    private val _state = MutableStateFlow(FamilyCategoriesUiState())
    val state: StateFlow<FamilyCategoriesUiState> = _state.asStateFlow()

    fun load(familyId: Int) {
        _state.update { it.copy(loading = it.items.isEmpty(), error = null) }
        viewModelScope.launch {
            when (val result = repository.familyCategories(familyId)) {
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
        _state.update { it.copy(form = CategoryForm(), formError = null) }
    }

    fun openEdit(category: FamilyCategoryDto) {
        _state.update {
            it.copy(
                form = CategoryForm(id = category.id, name = category.name, type = category.type),
                formError = null,
            )
        }
    }

    fun updateForm(transform: (CategoryForm) -> CategoryForm) {
        _state.update { it.copy(form = _state.value.form?.let(transform), formError = null) }
    }

    fun saveForm(familyId: Int) {
        val form = _state.value.form ?: return
        if (form.name.isBlank()) {
            _state.update { it.copy(formError = "Nama kategori harus diisi.") }
            return
        }
        if (_state.value.saving) return

        val body = FamilyCategoryRequest(name = form.name.trim().take(60), type = form.type)

        _state.update { it.copy(saving = true, formError = null) }
        viewModelScope.launch {
            val result = if (form.id == null) {
                repository.createFamilyCategory(familyId, body)
            } else {
                repository.updateFamilyCategory(familyId, form.id!!, body)
            }
            when (result) {
                is ApiResult.Ok -> _state.update {
                    it.copy(saving = false, form = null)
                }
                is ApiResult.Err -> _state.update {
                    it.copy(saving = false, formError = result.message)
                }
            }
            load(familyId)
        }
    }

    fun closeForm() = _state.update { it.copy(form = null, formError = null) }
}