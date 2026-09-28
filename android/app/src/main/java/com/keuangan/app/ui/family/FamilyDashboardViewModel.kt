package com.keuangan.app.ui.family

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.keuangan.app.data.ApiResult
import com.keuangan.app.data.FamilyHealthDto
import com.keuangan.app.data.InsightDto
import com.keuangan.app.data.IncomeBySourceDto
import com.keuangan.app.data.KeuanganRepository
import com.keuangan.app.data.NudgeDto
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.async
import kotlinx.coroutines.launch
import java.time.LocalDate

data class FamilyDashboardUiState(
    val loading: Boolean = true,
    val refreshing: Boolean = false,
    val error: String? = null,
    val health: FamilyHealthDto? = null,
    val insights: List<InsightDto> = emptyList(),
    val nudge: NudgeDto? = null,
    val incomeBySource: List<IncomeBySourceDto> = emptyList(),
)

/**
 * The one-screen-everything overview: health score, this-week's family +
 * personal insights, the current nudge, and this month's income by source.
 *
 * Every source is fetched independently so one failing endpoint (say the AI
 * summary coming back on a slow model) never blanks the rest of the screen.
 */
class FamilyDashboardViewModel(private val repository: KeuanganRepository) : ViewModel() {

    private val _state = MutableStateFlow(FamilyDashboardUiState())
    val state: StateFlow<FamilyDashboardUiState> = _state.asStateFlow()

    fun load(familyId: Int, refreshing: Boolean = false) {
        _state.update { it.copy(error = null, refreshing = refreshing) }
        viewModelScope.launch {
            // These endpoints are independent. Awaiting them together removes
            // two network round trips from the dashboard's critical path.
            val snapshotDeferred = async {
                repository.insights(familyId).let { result ->
                    when (result) { is ApiResult.Ok -> result.value; is ApiResult.Err -> null }
                }
            }
            val healthDeferred = async {
                repository.familyHealth(familyId).let { result ->
                    when (result) { is ApiResult.Ok -> result.value; is ApiResult.Err -> null }
                }
            }
            val nudgeDeferred = async {
                repository.nudge(familyId).let { result ->
                    when (result) { is ApiResult.Ok -> result.value; is ApiResult.Err -> null }
                }
            }
            val incomeDeferred = async { incomeBySourceThisMonth(familyId) }
            val snapshot = snapshotDeferred.await()
            val health = healthDeferred.await()
            val nudge = nudgeDeferred.await()
            val incomeBySource = incomeDeferred.await()

            _state.update {
                it.copy(
                    loading = false,
                    refreshing = false,
                    insights = listOfNotNull(snapshot?.family, snapshot?.personal),
                    health = health,
                    nudge = nudge,
                    incomeBySource = incomeBySource,
                )
            }
        }
    }

    /**
     * Aggregates income into per-source rows for the current month. Mirrors the
     * server's [com.keuangan.app.data.IncomeBySourceDto] contract, including the
     * synthetic "Belum dikategorikan" bucket for income without a source.
     */
    private suspend fun incomeBySourceThisMonth(familyId: Int): List<IncomeBySourceDto> = runCatching {
        val today = LocalDate.now()
        val first = today.withDayOfMonth(1).toString()
        val todayStr = today.toString()
        val loaded = repository.familyTransactions(familyId, type = "income", from = first, to = todayStr)
        if (loaded.result !is ApiResult.Ok) return@runCatching emptyList()

        val rows = mutableMapOf<Int?, Pair<String, Double>>()
        loaded.result.value.data.forEach { tx ->
            val source = tx.incomeSource
            val key = source?.id
            val name = source?.name ?: "Belum dikategorikan"
            rows[key] = name to (rows[key]?.second ?: 0.0) + tx.amount
        }
        rows.map { (id, entry) -> IncomeBySourceDto(id, entry.first, entry.second) }
            .sortedBy { it.name }
    }.getOrElse { emptyList() }

    fun refresh(familyId: Int) = load(familyId, refreshing = true)

    fun dismissNudge() = _state.update { it.copy(nudge = null) }

    /**
     * Marks an insight read. The server re-derives the whole list afterwards,
     * so we refetch to keep the unread badges accurate.
     */
    fun markRead(familyId: Int, insight: InsightDto) {
        viewModelScope.launch {
            repository.markInsightRead(familyId)
            load(familyId)
        }
    }
}
