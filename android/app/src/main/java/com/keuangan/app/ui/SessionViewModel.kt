package com.keuangan.app.ui

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.keuangan.app.data.KeuanganRepository
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch

/** Holds whether a stored token exists, so the app can pick its start screen. */
class SessionViewModel(private val repository: KeuanganRepository) : ViewModel() {

    private val _authenticated = MutableStateFlow<Boolean?>(null)
    val authenticated: StateFlow<Boolean?> = _authenticated.asStateFlow()

    init {
        viewModelScope.launch {
            _authenticated.value = repository.restoreSession()
        }
    }

    fun onLoggedIn() {
        _authenticated.value = true
    }

    fun logout() {
        viewModelScope.launch { repository.logout() }
    }
}
