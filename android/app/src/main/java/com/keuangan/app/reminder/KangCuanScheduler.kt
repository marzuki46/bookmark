package com.keuangan.app.reminder

import android.app.AlarmManager
import android.app.PendingIntent
import android.content.Context
import android.content.Intent
import android.os.Build
import com.keuangan.app.data.KangCuanSchedule
import com.keuangan.app.data.KangCuanStore
import java.time.LocalDate
import java.time.ZonedDateTime
import java.time.temporal.ChronoUnit

/**
 * Kang Cuan alarm slots. The phone triggers them like a kitchen timer — no
 * push service: the app arms AlarmManager at the times the user configured and
 * wakes on boot to re-arm them.
 */
object KangCuanSlot {
    const val PAGI = "pagi"
    const val MALAM = "malam"
    const val BULANAN = "bulanan"

    val ALL = listOf(PAGI, MALAM, BULANAN)

    const val ACTION_FIRE = "com.keuangan.app.ACTION_KANG_CUAN_FIRE"
    val REQUEST_CODE = mapOf(PAGI to 1001, MALAM to 1002, BULANAN to 1003)
}

/**
 * Arms one exact (or near-exact) alarm per enabled Kang Cuan slot.
 *
 * The alarm is one-shot: after it rings the receiver re-arms the next
 * occurrence, so schedule edits made anywhere take effect on the next round
 * without cancelling anything in flight.
 */
object KangCuanScheduler {

    fun isEnabled(schedule: KangCuanSchedule, slot: String): Boolean = when (slot) {
        KangCuanSlot.PAGI -> schedule.pagiEnabled
        KangCuanSlot.MALAM -> schedule.malamEnabled
        else -> schedule.bulananEnabled
    }

    fun hourOf(schedule: KangCuanSchedule, slot: String): Int = when (slot) {
        KangCuanSlot.PAGI -> schedule.pagiHour
        KangCuanSlot.MALAM -> schedule.malamHour
        else -> schedule.bulananHour
    }

    fun minuteOf(schedule: KangCuanSchedule, slot: String): Int = when (slot) {
        KangCuanSlot.PAGI -> schedule.pagiMinute
        KangCuanSlot.MALAM -> schedule.malamMinute
        else -> schedule.bulananMinute
    }

    fun dayOf(schedule: KangCuanSchedule): Int = schedule.bulananDay

    /** Next moment strictly after [now] where [slot] should ring. */
    fun nextTrigger(template: KangCuanSchedule, slot: String, now: ZonedDateTime): ZonedDateTime {
        val hour = hourOf(template, slot)
        val minute = minuteOf(template, slot)
        return when (slot) {
            KangCuanSlot.PAGI, KangCuanSlot.MALAM -> {
                var candidate = now.toLocalDate().atTime(hour, minute).atZone(now.zone)
                if (!candidate.isAfter(now)) candidate = candidate.plus(1, ChronoUnit.DAYS)
                candidate
            }
            else -> {
                // Bulanan: ring pada tanggal pertama bulan depan (default) atau
                // tanggal yang dipilih user. Tanggal lenyap dari bulan pendek
                // dipotong ke hari terakhir bulan itu.
                val monthStart = LocalDate.of(now.year, now.month, 1)
                val targetMonth = monthStart.plusMonths(1)
                val lastDay = targetMonth.lengthOfMonth()
                val day = dayOf(template).coerceIn(1, lastDay)
                var candidate = targetMonth.withDayOfMonth(day).atTime(hour, minute).atZone(now.zone)
                if (!candidate.isAfter(now)) candidate = candidate.plus(1, ChronoUnit.MONTHS)
                candidate
            }
        }
    }

    /**
     * Re-arms every enabled slot. Reads the persisted schedule so a change
     * saved anywhere in the app is picked up on the next call.
     */
    suspend fun scheduleAll(context: Context, store: KangCuanStore) {
        val schedule = store.read().schedule
        val alarm = context.getSystemService(AlarmManager::class.java)
        val now = ZonedDateTime.now()

        for (slot in KangCuanSlot.ALL) {
            cancel(alarm, context, slot)
            if (!isEnabled(schedule, slot)) continue

            val trigger = nextTrigger(schedule, slot, now)
            val millis = trigger.toInstant().toEpochMilli()
            val pending = pendingIntent(context, slot)

            if (Build.VERSION.SDK_INT >= 31 && alarm.canScheduleExactAlarms()) {
                alarm.setExactAndAllowWhileIdle(AlarmManager.RTC_WAKEUP, millis, pending)
            } else {
                // Fallback window (±10 minutes) still dings near the chosen time
                // without the SCHEDULE_EXACT_ALARM runtime permission.
                alarm.setWindow(AlarmManager.RTC_WAKEUP, millis, 10 * 60_000L, pending)
            }
        }
    }

    fun cancelAll(alarm: AlarmManager, context: Context) {
        for (slot in KangCuanSlot.ALL) cancel(alarm, context, slot)
    }

    private fun cancel(alarm: AlarmManager, context: Context, slot: String) {
        alarm.cancel(pendingIntent(context, slot))
    }

    private fun pendingIntent(context: Context, slot: String): PendingIntent {
        val intent = Intent(context, KangCuanAlarmReceiver::class.java).apply {
            action = KangCuanSlot.ACTION_FIRE
            putExtra("slot", slot)
        }
        return PendingIntent.getBroadcast(
            context,
            KangCuanSlot.REQUEST_CODE[slot]!!,
            intent,
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE,
        )
    }
}