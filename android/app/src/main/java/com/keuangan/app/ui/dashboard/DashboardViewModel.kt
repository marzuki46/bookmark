package com.keuangan.app.ui.dashboard

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.keuangan.app.data.ApiResult
import com.keuangan.app.data.DashboardResponse
import com.keuangan.app.data.KeuanganRepository
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch

val PERIODS = listOf(
    "this_month" to "Bulan Ini",
    "last_month" to "Bulan Lalu",
    "last_3_months" to "3 Bulan",
    "this_year" to "Tahun Ini",
    "all_time" to "Semua",
)

data class DashboardUiState(
    val loading: Boolean = true,
    val refreshing: Boolean = false,
    val period: String = "this_month",
    val data: DashboardResponse? = null,
    val error: String? = null,
    val advice: String? = null,
    val adviceLoading: Boolean = false,
    val adviceError: String? = null,
)

class DashboardViewModel(private val repository: KeuanganRepository) : ViewModel() {

    private val _state = MutableStateFlow(DashboardUiState())
    val state: StateFlow<DashboardUiState> = _state.asStateFlow()

    init {
        load()
    }

    fun setPeriod(period: String) {
        if (_state.value.period == period) return
        _state.update { it.copy(period = period, loading = true, error = null) }
        load()
    }

    fun refresh() {
        _state.update { it.copy(refreshing = true, error = null) }
        load()
    }

    private fun load() {
        val period = _state.value.period
        viewModelScope.launch {
            when (val result = repository.dashboard(period)) {
                is ApiResult.Ok -> _state.update {
                    it.copy(loading = false, refreshing = false, data = result.value, error = null)
                }
                is ApiResult.Err -> _state.update {
                    it.copy(loading = false, refreshing = false, error = result.message)
                }
            }
        }
    }

    fun loadAdvice() {
        if (_state.value.adviceLoading) return
        _state.update { it.copy(adviceLoading = true, adviceError = null) }
        viewModelScope.launch {
            when (val result = repository.advice(_state.value.period)) {
                is ApiResult.Ok -> _state.update {
                    it.copy(
                        adviceLoading = false,
                        advice = result.value.advice ?: "Belum ada saran untuk periode ini.",
                    )
                }
                is ApiResult.Err -> _state.update {
                    it.copy(adviceLoading = false, adviceError = result.message)
                }
            }
        }
    }

    fun dismissAdvice() = _state.update { it.copy(advice = null, adviceError = null) }
}
