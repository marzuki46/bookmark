package com.keuangan.app.ui.family

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.keuangan.app.data.ApiResult
import com.keuangan.app.data.FamilyDto
import com.keuangan.app.data.KeuanganRepository
import com.keuangan.app.data.MeDto
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch

data class FamilyProfileUiState(
    val loading: Boolean = true,
    val error: String? = null,
    val family: FamilyDto? = null,
    val me: MeDto? = null,
    /** Not found / not a member yet: user has no family. */
    val noFamily: Boolean = false,
    val saving: Boolean = false,
    val rotating: Boolean = false,
    val newCode: String? = null,
    val actionMessage: String? = null,
    /** The caller's own login code, shown on the family screen. */
    val loginCode: String? = null,
    val loadingCode: Boolean = false,
    /** Owner only: the freshly created spouse's code, shown once. */
    val newMemberCode: String? = null,
    val addingMember: Boolean = false,
)

class FamilyProfileViewModel(private val repository: KeuanganRepository) : ViewModel() {

    private val _state = MutableStateFlow(FamilyProfileUiState())
    val state: StateFlow<FamilyProfileUiState> = _state.asStateFlow()

    val currentUserId: Int? get() = repository.currentUserId

    fun load(familyId: Int) {
        _state.update { it.copy(loading = true, error = null) }
        viewModelScope.launch {
            // Profile and entitlement are independent of the family detail call;
            // a failure to load one must not blank out the other.
            val meResult = repository.currentUser()
            val familyResult = repository.familyDetail(familyId)

            _state.update {
                it.copy(
                    loading = false,
                    me = (meResult as? ApiResult.Ok)?.value,
                    family = (familyResult as? ApiResult.Ok)?.value,
                    noFamily = familyResult is ApiResult.Err,
                    error = firstError(meResult, familyResult),
                )
            }

            loadLoginCode(familyId)
        }
    }

    fun loadLoginCode(familyId: Int) {
        if (_state.value.loadingCode || _state.value.loginCode != null) return
        _state.update { it.copy(loadingCode = true) }
        viewModelScope.launch {
            when (val result = repository.familyLoginCode(familyId)) {
                is ApiResult.Ok -> _state.update { it.copy(loadingCode = false, loginCode = result.value.code) }
                is ApiResult.Err -> _state.update { it.copy(loadingCode = false) }
            }
        }
    }

    fun addMember(familyId: Int, name: String, payerRole: String?) {
        val trimmed = name.trim()
        if (trimmed.isEmpty()) {
            _state.update { it.copy(actionMessage = "Nama anggota tidak boleh kosong.") }
            return
        }
        if (_state.value.addingMember) return
        _state.update { it.copy(addingMember = true, actionMessage = null) }
        viewModelScope.launch {
            when (val result = repository.addFamilyMember(familyId, trimmed, payerRole)) {
                is ApiResult.Ok -> {
                    _state.update {
                        it.copy(
                            addingMember = false,
                            newMemberCode = result.value.loginCode,
                            actionMessage = "Anggota \"${result.value.name ?: trimmed}\" berhasil ditambahkan.",
                        )
                    }
                    load(familyId)
                }
                is ApiResult.Err -> _state.update { it.copy(addingMember = false, actionMessage = result.message) }
            }
        }
    }

    fun dismissNewMemberCode() = _state.update { it.copy(newMemberCode = null) }

    fun saveProfile(name: String, about: String) {
        val existing = _state.value.me ?: return
        if (_state.value.saving) return
        val nameInput = name.trim()
        val aboutInput = about.trim()
        if (nameInput.isEmpty()) {
            _state.update { it.copy(actionMessage = "Nama tidak boleh kosong.") }
            return
        }
        if (nameInput == (existing.name ?: "") && aboutInput == (existing.about ?: "")) {
            _state.update { it.copy(actionMessage = "Tidak ada perubahan.") }
            return
        }

        _state.update { it.copy(saving = true, actionMessage = null) }
        viewModelScope.launch {
            when (val result = repository.updateProfile(nameInput.ifEmpty { null }, aboutInput.ifEmpty { null })) {
                is ApiResult.Ok -> {
                    // Re-fetch only the profile; the family panel is unaffected.
                    val refreshed = repository.currentUser()
                    _state.update {
                        it.copy(
                            saving = false,
                            me = (refreshed as? ApiResult.Ok)?.value ?: it.me?.copy(name = nameInput, about = aboutInput),
                            actionMessage = "Profil diperbarui.",
                        )
                    }
                }
                is ApiResult.Err -> _state.update {
                    it.copy(saving = false, actionMessage = result.message)
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

    private fun firstError(vararg results: ApiResult<*>): String? = results
        .mapNotNull { (it as? ApiResult.Err)?.message }
        .firstOrNull()
}