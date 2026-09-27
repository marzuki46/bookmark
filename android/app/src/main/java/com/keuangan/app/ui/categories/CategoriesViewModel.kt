package com.keuangan.app.ui.categories

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.keuangan.app.data.ApiResult
import com.keuangan.app.data.CategoryDto
import com.keuangan.app.data.CategoryRequest
import com.keuangan.app.data.KeuanganRepository
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch

data class CategoriesUiState(
    val loading: Boolean = true,
    val items: List<CategoryDto> = emptyList(),
    val error: String? = null,
    val formOpen: Boolean = false,
    val editingId: Int? = null,
    val name: String = "",
    val type: String = "expense",
    val color: String = "#0F766E",
    val saving: Boolean = false,
    val formError: String? = null,
)

class CategoriesViewModel(private val repository: KeuanganRepository) : ViewModel() {

    private val _state = MutableStateFlow(CategoriesUiState())
    val state: StateFlow<CategoriesUiState> = _state.asStateFlow()

    init {
        load()
    }

    fun load() {
        _state.update { it.copy(loading = true, error = null) }
        viewModelScope.launch {
            when (val result = repository.categories()) {
                is ApiResult.Ok -> _state.update {
                    it.copy(loading = false, items = result.value, error = null)
                }
                is ApiResult.Err -> _state.update {
                    it.copy(loading = false, error = result.message)
                }
            }
        }
    }

    fun openCreate() = _state.update {
        it.copy(formOpen = true, editingId = null, name = "", type = "expense", formError = null)
    }

    fun openEdit(category: CategoryDto) = _state.update {
        it.copy(
            formOpen = true,
            editingId = category.id,
            name = category.name,
            type = category.type,
            color = category.color ?: "#0F766E",
            formError = null,
        )
    }

    fun closeForm() = _state.update { it.copy(formOpen = false, formError = null) }

    fun onNameChange(value: String) = _state.update { it.copy(name = value, formError = null) }

    fun onTypeChange(value: String) = _state.update { it.copy(type = value) }

    fun onColorChange(value: String) = _state.update { it.copy(color = value) }

    fun save() {
        val current = _state.value
        if (current.name.isBlank()) {
            _state.update { it.copy(formError = "Nama kategori wajib diisi") }
            return
        }
        if (current.saving) return

        _state.update { it.copy(saving = true, formError = null) }
        val body = CategoryRequest(
            name = current.name.trim(),
            type = current.type,
            color = current.color,
        )

        viewModelScope.launch {
            val result = if (current.editingId == null) {
                repository.createCategory(body)
            } else {
                repository.updateCategory(current.editingId, body)
            }
            when (result) {
                is ApiResult.Ok -> {
                    _state.update { it.copy(saving = false, formOpen = false) }
                    load()
                }
                is ApiResult.Err -> _state.update {
                    it.copy(saving = false, formError = result.message)
                }
            }
        }
    }

    fun delete(category: CategoryDto) {
        viewModelScope.launch {
            when (val result = repository.deleteCategory(category.id)) {
                is ApiResult.Ok -> _state.update { current ->
                    current.copy(items = current.items.filterNot { it.id == category.id })
                }
                is ApiResult.Err -> _state.update { it.copy(error = result.message) }
            }
        }
    }
}
