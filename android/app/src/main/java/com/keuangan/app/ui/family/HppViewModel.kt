package com.keuangan.app.ui.family

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.keuangan.app.data.ApiResult
import com.keuangan.app.data.KeuanganRepository
import com.keuangan.app.data.PricingRequest
import com.keuangan.app.data.PricingResponse
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch

/**
 * Backs the HPP calculator's pricing advisor.
 *
 * Kept separate from the calculator's own arithmetic so the form stays purely
 * local: nothing here is needed to compute HPP, only to ask the server for a
 * selling-price opinion.
 */
class HppViewModel(private val repository: KeuanganRepository) : ViewModel() {

    private val _pricing = MutableStateFlow<PricingResponse?>(null)
    val pricing: StateFlow<PricingResponse?> = _pricing.asStateFlow()

    private val _loading = MutableStateFlow(false)
    val loading: StateFlow<Boolean> = _loading.asStateFlow()

    private val _error = MutableStateFlow<String?>(null)
    val error: StateFlow<String?> = _error.asStateFlow()

    /**
     * Ask for a selling-price recommendation. Rejects a zero HPP locally so the
     * user gets an instant message instead of a round trip the server would
     * answer with zeros anyway.
     */
    fun recommend(hpp: Double, quantity: Double, wastePercent: Double, product: String?, competition: Double?) {
        if (hpp <= 0) {
            _error.value = "Isi modal dan jumlah produk dulu."
            return
        }
        if (_loading.value) return

        viewModelScope.launch {
            _loading.value = true
            _error.value = null
            when (val result = repository.pricingAdvice(
                PricingRequest(
                    hpp = hpp,
                    quantity = quantity.takeIf { it > 0 },
                    wastePercent = wastePercent.takeIf { it > 0 },
                    product = product?.takeIf { it.isNotBlank() },
                    competitionPrice = competition?.takeIf { it > 0 },
                ),
            )) {
                is ApiResult.Ok -> _pricing.value = result.value
                is ApiResult.Err -> _error.value = result.message ?: "Gagal mengambil saran harga."
            }
            _loading.value = false
        }
    }

    fun clear() {
        _pricing.value = null
        _error.value = null
    }
}