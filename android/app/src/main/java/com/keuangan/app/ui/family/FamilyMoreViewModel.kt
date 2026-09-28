package com.keuangan.app.ui.family

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.keuangan.app.data.ApiResult
import com.keuangan.app.data.AppUpdateDto
import com.keuangan.app.data.KeuanganRepository
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch

data class FamilyMoreUiState(
    val checkingUpdate: Boolean = false,
    val checkedUpdate: Boolean = false,
    val update: AppUpdateDto? = null,
    val updateError: String? = null,
)

class FamilyMoreViewModel(private val repository: KeuanganRepository) : ViewModel() {

    private val _state = MutableStateFlow(FamilyMoreUiState())
    val state: StateFlow<FamilyMoreUiState> = _state.asStateFlow()

    fun checkUpdates() {
        if (_state.value.checkingUpdate) return
        _state.update { it.copy(checkingUpdate = true, checkedUpdate = true, updateError = null) }
        viewModelScope.launch {
            when (val result = repository.appUpdates()) {
                is ApiResult.Ok -> _state.update { it.copy(checkingUpdate = false, update = result.value) }
                is ApiResult.Err -> _state.update { it.copy(checkingUpdate = false, updateError = result.message) }
            }
        }
    }

    fun dismissUpdates() = _state.update {
        it.copy(update = null, checkedUpdate = false, updateError = null)
    }
}