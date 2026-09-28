package com.keuangan.app.ui.family

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.keuangan.app.data.ApiResult
import com.keuangan.app.data.KeuanganRepository
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch

data class CodeLoginUiState(
    val code: String = "",
    val loading: Boolean = false,
    val error: String? = null,
)

/**
 * Grouped text entry that normalises the login code on the way in: the stored
 * form is `XXXX-XXXX-XXXX-XXXX`, but the HttpCodeController strips dashes and
 * uppercases, so the request body simply gets the bare 16 letters/digits.
 */
object CodeFormat {
    fun group(raw: String): String {
        val clean = raw.filter { it.isLetterOrDigit() }.uppercase().take(16)
        return clean.chunked(4).joinToString("-")
    }
}

class CodeLoginViewModel(private val repository: KeuanganRepository) : ViewModel() {

    private val _state = MutableStateFlow(CodeLoginUiState())
    val state: StateFlow<CodeLoginUiState> = _state.asStateFlow()

    fun onCodeChange(raw: String) {
        _state.update {
            it.copy(code = CodeFormat.group(raw), error = null)
        }
    }

    fun submit(onLoggedIn: () -> Unit) {
        val code = _state.value.code
        if (code.replace("-", "").length < 16) {
            _state.update { it.copy(error = "Masukkan 16 karakter kode keluarga (XXXX-XXXX-XXXX-XXXX).") }
            return
        }
        if (_state.value.loading) return

        _state.update { it.copy(loading = true, error = null) }
        viewModelScope.launch {
            when (val result = repository.loginWithCode(code)) {
                is ApiResult.Ok -> {
                    _state.update { it.copy(loading = false) }
                    onLoggedIn()
                }
                is ApiResult.Err -> _state.update {
                    it.copy(loading = false, error = result.message)
                }
            }
        }
    }
}