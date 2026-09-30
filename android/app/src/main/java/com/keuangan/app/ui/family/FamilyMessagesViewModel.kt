package com.keuangan.app.ui.family

import android.app.Application
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.keuangan.app.data.KangCuanMessage
import com.keuangan.app.data.KangCuanSchedule
import com.keuangan.app.data.KeuanganRepository
import com.keuangan.app.reminder.KangCuanScheduler
import com.keuangan.app.reminder.KangCuanSlot
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import java.time.Instant
import java.time.ZoneId
import java.time.format.DateTimeFormatter
import java.util.Locale

data class KangCuanUiState(
    val loading: Boolean = true,
    val schedule: KangCuanSchedule = KangCuanSchedule(),
    val messages: List<KangCuanMessage> = emptyList(),
    val saving: Boolean = false,
    val actionMessage: String? = null,
)

/**
 * "Pesan dari Kang Cuan" — the local message history plus the alarm schedule
 * (pagi / malam / refleksi bulanan). Everything is device-local JSON; nothing
 * here is uploaded. Rescheduling after any edit re-arms the exact alarm.
 */
class FamilyMessagesViewModel(
    private val app: Application,
    private val repository: KeuanganRepository,
) : ViewModel() {

    private val _state = MutableStateFlow(KangCuanUiState())
    val state: StateFlow<KangCuanUiState> = _state.asStateFlow()

    fun load() {
        if (!_state.value.loading) return
        viewModelScope.launch {
            val kangCuan = repository.kangCuanStore.read()
            _state.update {
                it.copy(
                    loading = false,
                    schedule = kangCuan.schedule,
                    messages = kangCuan.messages.sortedByDescending { m -> m.id },
                )
            }
        }
    }

    fun refresh() {
        viewModelScope.launch {
            val kangCuan = repository.kangCuanStore.read()
            _state.update {
                it.copy(schedule = kangCuan.schedule, messages = kangCuan.messages.sortedByDescending { m -> m.id })
            }
        }
    }

    fun setPagiEnabled(enabled: Boolean) = updateSchedule { it.copy(pagiEnabled = enabled) }
    fun setMalamEnabled(enabled: Boolean) = updateSchedule { it.copy(malamEnabled = enabled) }
    fun setBulananEnabled(enabled: Boolean) = updateSchedule { it.copy(bulananEnabled = enabled) }

    fun setPagiTime(hour: Int, minute: Int) = updateSchedule {
        it.copy(pagiHour = hour, pagiMinute = minute)
    }

    fun setMalamTime(hour: Int, minute: Int) = updateSchedule {
        it.copy(malamHour = hour, malamMinute = minute)
    }

    fun setBulananTime(hour: Int, minute: Int) = updateSchedule {
        it.copy(bulananHour = hour, bulananMinute = minute)
    }

    fun setBulananDay(day: Int) = updateSchedule { it.copy(bulananDay = day.coerceIn(1, 28)) }

    fun deleteMessage(id: Long) {
        viewModelScope.launch {
            repository.kangCuanStore.deleteMessage(id)
            refresh()
        }
    }

    fun clearMessages() {
        viewModelScope.launch {
            repository.kangCuanStore.deleteAllMessages()
            refresh()
        }
    }

    fun dismissMessage() = _state.update { it.copy(actionMessage = null) }

    private fun updateSchedule(transform: (KangCuanSchedule) -> KangCuanSchedule) {
        if (_state.value.saving) return
        _state.update { it.copy(saving = true, actionMessage = null) }
        viewModelScope.launch {
            val next = transform(_state.value.schedule)
            repository.kangCuanStore.saveSchedule(next)
            KangCuanScheduler.scheduleAll(app, repository.kangCuanStore)
            _state.update {
                it.copy(
                    schedule = next,
                    saving = false,
                    actionMessage = "Jadwal diubah. Alarm Kang Cuan sudah diperbarui.",
                )
            }
        }
    }

}

fun formatMessageTime(sentAt: String): String = runCatching {
    val zone = ZoneId.systemDefault()
    val formatter = DateTimeFormatter.ofPattern("d MMM yyyy · HH:mm", Locale("in", "ID"))
    Instant.parse(sentAt).atZone(zone).format(formatter)
}.getOrDefault(sentAt)

fun slotLabel(slot: String): String = when (slot) {
    KangCuanSlot.PAGI -> "Penyemangat Pagi"
    KangCuanSlot.MALAM -> "Rekap Malam"
    KangCuanSlot.BULANAN -> "Refleksi Bulanan"
    else -> slot
}