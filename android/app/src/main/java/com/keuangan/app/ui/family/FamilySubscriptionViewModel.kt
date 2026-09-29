package com.keuangan.app.ui.family

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.keuangan.app.data.ApiResult
import com.keuangan.app.data.KeuanganRepository
import com.keuangan.app.data.PlanDto
import com.keuangan.app.data.SubscriptionDto
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch

data class FamilySubscriptionUiState(
    val loading: Boolean = true,
    val error: String? = null,
    val subscription: SubscriptionDto? = null,
    val plans: List<PlanDto> = emptyList(),
    /** The plan id currently starting a checkout; null when idle. */
    val charging: Int? = null,
    val message: String? = null,
)

class FamilySubscriptionViewModel(private val repository: KeuanganRepository) : ViewModel() {

    private val _state = MutableStateFlow(FamilySubscriptionUiState())
    val state: StateFlow<FamilySubscriptionUiState> = _state.asStateFlow()

    /** Consumed by the screen: opens the Snap checkout in the external browser. */
    private val _pendingRedirect = MutableStateFlow<String?>(null)
    val pendingRedirect: StateFlow<String?> = _pendingRedirect.asStateFlow()

    fun load() {
        refresh()
    }

    fun refresh() {
        _state.update { it.copy(loading = true, error = null, message = null) }
        viewModelScope.launch {
            val sub = repository.currentSubscription()
            val plans = repository.subscriptionPlans()

            _state.update {
                it.copy(
                    loading = false,
                    subscription = (sub as? ApiResult.Ok)?.value,
                    plans = (plans as? ApiResult.Ok)?.value.orEmpty(),
                    error = listOfNotNull(
                        (sub as? ApiResult.Err)?.message,
                        (plans as? ApiResult.Err)?.message,
                    ).firstOrNull(),
                )
            }
        }
    }

    fun charge(planId: Int) {
        if (_state.value.charging != null) return
        _state.update { it.copy(charging = planId, error = null, message = null) }
        viewModelScope.launch {
            when (val result = repository.chargePlan(planId)) {
                is ApiResult.Ok -> {
                    _pendingRedirect.value = result.value.redirectUrl
                    _state.update {
                        it.copy(
                            charging = null,
                            message = "Pembayaran dibuka di browser. Setelah selesai, status langganan akan diperbarui otomatis.",
                        )
                    }
                }
                is ApiResult.Err -> _state.update {
                    it.copy(charging = null, error = result.message)
                }
            }
        }
    }

    fun consumeRedirect(): String? = _pendingRedirect.value.also { _pendingRedirect.value = null }

    fun dismissMessage() = _state.update { it.copy(message = null) }
}
