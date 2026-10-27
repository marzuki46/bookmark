package com.keuangan.app.ui.login

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.keuangan.app.data.ApiResult
import com.keuangan.app.data.KeuanganRepository
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch

data class LoginUiState(
    val registering: Boolean = false,
    val name: String = "",
    val email: String = "",
    val password: String = "",
    val passwordConfirmation: String = "",
    val loading: Boolean = false,
    val error: String? = null,
)

class LoginViewModel(private val repository: KeuanganRepository) : ViewModel() {

    private val _state = MutableStateFlow(LoginUiState())
    val state: StateFlow<LoginUiState> = _state.asStateFlow()

    fun onEmailChange(value: String) = _state.update { it.copy(email = value, error = null) }

    fun onPasswordChange(value: String) = _state.update { it.copy(password = value, error = null) }

    fun onPasswordConfirmationChange(value: String) = _state.update { it.copy(passwordConfirmation = value, error = null) }

    fun submit(onSuccess: () -> Unit) {
        val current = _state.value
        if (current.registering && current.name.isBlank()) {
            _state.update { it.copy(error = "Nama wajib diisi") }
            return
        }
        if (current.email.isBlank() || current.password.isBlank()) {
            _state.update { it.copy(error = "Email dan password wajib diisi") }
            return
        }
        if (current.registering && current.password.length < 8) {
            _state.update { it.copy(error = "Password minimal 8 karakter") }
            return
        }
        if (current.registering && current.password != current.passwordConfirmation) {
            _state.update { it.copy(error = "Konfirmasi password tidak sama") }
            return
        }
        if (_state.value.loading) return

        _state.update { it.copy(loading = true, error = null) }
        viewModelScope.launch {
            val result = if (current.registering) {
                repository.register(current.name, current.email, current.password, current.passwordConfirmation)
            } else {
                repository.login(current.email, current.password)
            }
            when (result) {
                is ApiResult.Ok -> {
                    _state.update { it.copy(loading = false, password = "") }
                    onSuccess()
                }
                is ApiResult.Err -> _state.update { it.copy(loading = false, error = result.message) }
            }
        }
    }
    fun toggleMode() = _state.update {
        it.copy(
            registering = !it.registering,
            error = null,
            password = "",
            passwordConfirmation = "",
        )
    }

    fun onNameChange(value: String) = _state.update { it.copy(name = value, error = null) }

}
