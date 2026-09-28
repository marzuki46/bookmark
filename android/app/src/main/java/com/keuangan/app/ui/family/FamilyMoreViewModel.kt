package com.keuangan.app.ui.family

import android.content.Context
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.keuangan.app.data.ApiResult
import com.keuangan.app.data.AppUpdateDto
import com.keuangan.app.data.KeuanganRepository
import com.keuangan.app.update.AppUpdater
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
    val downloading: Boolean = false,
    val installProgress: Float = 0f,
    val installError: String? = null,
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

    /** Store-style install: stream the APK, hand it to PackageInstaller. */
    fun applyUpdate(context: Context, url: String) {
        if (_state.value.downloading) return
        _state.update { it.copy(downloading = true, installProgress = 0f, installError = null) }
        viewModelScope.launch {
            try {
                val file = AppUpdater.download(context, url) { progress ->
                    _state.update { it.copy(installProgress = progress) }
                }
                AppUpdater.install(context, file)
                _state.update { it.copy(downloading = false, installProgress = 0f) }
                dismissUpdates()
            } catch (e: Exception) {
                _state.update {
                    it.copy(
                        downloading = false,
                        installProgress = 0f,
                        installError = e.message ?: "Gagal memperbarui. Coba lagi ya.",
                    )
                }
            }
        }
    }

    fun dismissUpdates() = _state.update {
        it.copy(
            update = null,
            checkedUpdate = false,
            updateError = null,
            installError = null,
        )
    }
}