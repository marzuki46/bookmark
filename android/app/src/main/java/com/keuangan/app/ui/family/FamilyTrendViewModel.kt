package com.keuangan.app.ui.family

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.keuangan.app.data.ApiResult
import com.keuangan.app.data.KeuanganRepository
import com.keuangan.app.data.TrendPointDto
import com.keuangan.app.data.getOrNull
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch

data class FamilyTrendUiState(
    val loading: Boolean = true,
    val points: List<TrendPointDto> = emptyList(),
    val error: String? = null,
    val months: Int = 6,
)

class FamilyTrendViewModel(private val repository: KeuanganRepository) : ViewModel() {

    private val _state = MutableStateFlow(FamilyTrendUiState())
    val state: StateFlow<FamilyTrendUiState> = _state.asStateFlow()

    fun load(familyId: Int, months: Int = 6) {
        _state.update { it.copy(loading = true, error = null, months = months) }
        viewModelScope.launch {
            repository.familyTrend(familyId, months).let { result ->
                _state.update {
                    when (result) {
                        is ApiResult.Ok -> it.copy(loading = false, points = result.value, error = null)
                        is ApiResult.Err -> it.copy(loading = false, error = result.message)
                    }
                }
            }
        }
    }

    fun retry(familyId: Int) {
        _state.update { it.copy(loading = true, error = null) }
        load(familyId, _state.value.months)
    }

    fun setPeriod(familyId: Int, months: Int) {
        load(familyId, months)
    }
}
