package com.keuangan.app.reminder

import android.Manifest
import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.PendingIntent
import android.content.BroadcastReceiver
import android.content.Context
import android.content.Intent
import android.content.pm.PackageManager
import android.os.Build
import androidx.core.app.NotificationCompat
import androidx.core.app.NotificationManagerCompat
import androidx.core.content.ContextCompat
import com.keuangan.app.KeuanganApp
import com.keuangan.app.MainActivity
import com.keuangan.app.data.AffirmationDto
import com.keuangan.app.data.ApiResult
import com.keuangan.app.data.KangCuanMessage
import com.keuangan.app.data.KangCuanState
import com.keuangan.app.data.KangCuanStore
import com.keuangan.app.data.getOrNull
import com.keuangan.app.ui.formatRupiah
import com.keuangan.app.ui.todayIso
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.SupervisorJob
import kotlinx.coroutines.launch
import java.time.Instant
import java.time.ZonedDateTime
import kotlin.random.Random

/**
 * Rings the Kang Cuan alarm after the user-set time is reached (like a kitchen
 * timer on the phone — no push). Composes one friendly message from the server
 * templates, persists it to the local "Pesan dari Kang Cuan" JSON, and posts a
 * notification that re-opens the app onto the floating message page.
 */
class KangCuanAlarmReceiver : BroadcastReceiver() {

    override fun onReceive(context: Context, intent: Intent) {
        when (intent.action) {
            Intent.ACTION_BOOT_COMPLETED,
            Intent.ACTION_MY_PACKAGE_REPLACED,
            -> {
                val app = context.applicationContext as? KeuanganApp ?: return
                val store = KangCuanStore(context.applicationContext)
                CoroutineScope(SupervisorJob() + Dispatchers.IO).launch {
                    if (app.tokenStore.token != null) {
                        KangCuanScheduler.scheduleAll(context.applicationContext, store)
                    }
                }
            }

            KangCuanSlot.ACTION_FIRE -> {
                val pending = goAsync()
                val slot = intent.getStringExtra("slot") ?: return
                val app = context.applicationContext as? KeuanganApp ?: return
                val appContext = context.applicationContext
                CoroutineScope(SupervisorJob() + Dispatchers.IO).launch {
                    try {
                        fire(app, appContext, slot)
                    } catch (_: Exception) {
                        // Never let a composing failure crash the alarm loop.
                    } finally {
                        pending.finish()
                    }
                }
            }
        }
    }

    private suspend fun fire(app: KeuanganApp, context: Context, slot: String) {
        val store = KangCuanStore(context)
        val state = store.read()
        if (!isEnabled(state.schedule, slot)) return

        // Inside the Slack-style "fail soft": compose goes ahead even offline;
        // templates and the user name come straight from the persisted copy.
        val templates = app.repository.affirmations().getOrNull().orEmpty()
        if (templates.isEmpty()) {
            reschedule(context, store, slot)
            return
        }

        val composed = composeMessage(app, state, templates, slot) ?: run {
            reschedule(context, store, slot)
            return
        }

        val templateKey = "${composed.template.slot}:${composed.template.id}"
        if (templateKey in state.deliveredKeys) {
            // Same text already shown recently — pick a different one next time.
            reschedule(context, store, slot)
            return
        }

        val message = KangCuanMessage(
            id = System.currentTimeMillis(),
            slot = slot,
            title = titleFor(slot),
            body = composed.text,
            sentAt = Instant.now().toString(),
        )
        store.appendMessage(message)
        store.recordDelivered(listOf(templateKey))
        showNotification(context, message)
        reschedule(context, store, slot)
    }

    private suspend fun composeMessage(
        app: KeuanganApp,
        state: KangCuanState,
        templates: List<AffirmationDto>,
        slot: String,
    ): Composed? {
        val pool: List<AffirmationDto> = when (slot) {
            KangCuanSlot.MALAM -> {
                // Malam: pilih varian sesuai rekap hari ini dari cache lokal
                // (server adalah sumbernya, tapi alarm harus tetap jalan offline).
                val recap = todayRecap(app)
                val variant = when {
                    recap.expense == 0.0 && recap.income == 0.0 -> "kosong"
                    recap.expense > recap.income -> "pengeluaran-luas"
                    else -> "pemasukan-luas"
                }
                templates.filter { it.slot == slot && it.variant == variant }.ifEmpty {
                    templates.filter { it.slot == slot }
                }
            }

            else -> templates.filter { it.slot == slot }
        }

        if (pool.isEmpty()) return null

        val unused = pool.filterNot { "${it.slot}:${it.id}" in state.deliveredKeys }
        val template = (if (unused.isNotEmpty()) unused else pool).random(Random)
        val firstName = state.userName ?: "Keluarga"

        val recap = if (slot == KangCuanSlot.MALAM) todayRecap(app) else TodayRecap()
        val text = template.content
            .replace("{nama}", firstName)
            .replace("{pengeluaran}", formatRupiah(recap.expense))
            .replace("{pemasukan}", formatRupiah(recap.income))

        return Composed(template, text)
    }

    /** Sums today's household transactions from the offline cache when possible. */
    private suspend fun todayRecap(app: KeuanganApp): TodayRecap {
        val familyId = app.repository.cachedFamilyId
        if (familyId == null) return TodayRecap()

        val today = todayIso()
        val result = app.repository.familyTransactions(
            familyId = familyId,
            from = today,
            to = today,
        ).result
        val transactions = (result as? ApiResult.Ok)?.value?.data.orEmpty()

        var expense = 0.0
        var income = 0.0
        for (tx in transactions) {
            when (tx.type) {
                "expense" -> expense += tx.amount
                "income" -> income += tx.amount
            }
        }
        return TodayRecap(expense = expense, income = income)
    }

    private fun reschedule(context: Context, store: KangCuanStore, slot: String) {
        // Re-arm is slot-agnostic and idempotent: it resets every enabled slot.
        CoroutineScope(SupervisorJob() + Dispatchers.IO).launch {
            KangCuanScheduler.scheduleAll(context, store)
        }
    }

    private fun titleFor(slot: String): String = when (slot) {
        KangCuanSlot.PAGI -> "Penyemangat Pagi ☀️"
        KangCuanSlot.MALAM -> "Rekap Malam 📊"
        else -> "Refleksi Bulanan 🌙"
    }

    private fun isEnabled(schedule: com.keuangan.app.data.KangCuanSchedule, slot: String): Boolean =
        KangCuanScheduler.isEnabled(schedule, slot)

    private fun showNotification(context: Context, message: KangCuanMessage) {
        if (Build.VERSION.SDK_INT >= 33 &&
            ContextCompat.checkSelfPermission(context, Manifest.permission.POST_NOTIFICATIONS) !=
            PackageManager.PERMISSION_GRANTED
        ) {
            return
        }

        val manager = context.getSystemService(NotificationManager::class.java)
        if (Build.VERSION.SDK_INT >= 26) {
            manager.createNotificationChannel(
                NotificationChannel(CHANNEL_ID, "Pesan dari Kang Cuan", NotificationManager.IMPORTANCE_DEFAULT),
            )
        }

        val intent = Intent(context, MainActivity::class.java).apply {
            flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TOP
            putExtra(EXTRA_OPEN_KANG_CUAN, true)
        }
        val pending = PendingIntent.getActivity(
            context,
            NOTIFICATION_ID,
            intent,
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE,
        )

        val notification = NotificationCompat.Builder(context, CHANNEL_ID)
            .setSmallIcon(context.applicationInfo.icon)
            .setContentTitle(message.title)
            .setContentText(message.body)
            .setStyle(NotificationCompat.BigTextStyle().bigText(message.body))
            .setPriority(NotificationCompat.PRIORITY_DEFAULT)
            .setAutoCancel(true)
            .setContentIntent(pending)
            .build()

        NotificationManagerCompat.from(context).notify(NOTIFICATION_ID, notification)
    }

    private data class Composed(val template: AffirmationDto, val text: String)

    private data class TodayRecap(val expense: Double = 0.0, val income: Double = 0.0)

    companion object {
        private const val CHANNEL_ID = "kang_cuan"
        private const val NOTIFICATION_ID = 5100
        const val EXTRA_OPEN_KANG_CUAN = "open_kang_cuan"
    }
}