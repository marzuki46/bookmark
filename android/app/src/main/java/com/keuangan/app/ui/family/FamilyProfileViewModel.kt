package com.keuangan.app.ui.family

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.keuangan.app.data.ApiResult
import com.keuangan.app.data.FamilyDto
import com.keuangan.app.data.KeuanganRepository
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch

data class FamilyProfileUiState(
    val loading: Boolean = true,
    val error: String? = null,
    val family: FamilyDto? = null,
    /** Not found / not a member yet: user has no family. */
    val noFamily: Boolean = false,
    val rotating: Boolean = false,
    val newCode: String? = null,
    val actionMessage: String? = null,
)

class FamilyProfileViewModel(private val repository: KeuanganRepository) : ViewModel() {

    private val _state = MutableStateFlow(FamilyProfileUiState())
    val state: StateFlow<FamilyProfileUiState> = _state.asStateFlow()

    val currentUserId: Int? get() = repository.currentUserId

    fun load(familyId: Int) {
        _state.update { it.copy(loading = true, error = null) }
        viewModelScope.launch {
            when (val result = repository.familyDetail(familyId)) {
                is ApiResult.Ok -> _state.update {
                    it.copy(loading = false, family = result.value, noFamily = false, error = null)
                }
                is ApiResult.Err -> _state.update {
                    it.copy(loading = false, error = result.message)
                }
            }
        }
    }

    fun setPayerRole(familyId: Int, role: String) {
        if (_state.value.loading) return
        viewModelScope.launch {
            when (val result = repository.setPayerRole(familyId, role)) {
                is ApiResult.Ok -> {
                    _state.update { it.copy(actionMessage = "Peran diperbarui.") }
                    load(familyId)
                }
                is ApiResult.Err -> _state.update {
                    it.copy(actionMessage = result.message)
                }
            }
        }
    }

    fun rotate(familyId: Int) {
        if (_state.value.rotating) return
        _state.update { it.copy(rotating = true, error = null) }
        viewModelScope.launch {
            when (val result = repository.rotateLoginCode()) {
                is ApiResult.Ok -> _state.update {
                    it.copy(
                        rotating = false,
                        newCode = result.value.code,
                        actionMessage = "Kode baru dibuat. Anggota lain yang sudah masuk akan di-logout dan perlu kode ini untuk masuk kembali.",
                    )
                }
                is ApiResult.Err -> _state.update {
                    it.copy(rotating = false, error = result.message)
                }
            }
        }
    }

    fun dismissNewCode() = _state.update { it.copy(newCode = null) }

    fun dismissMessage() = _state.update { it.copy(actionMessage = null) }
}