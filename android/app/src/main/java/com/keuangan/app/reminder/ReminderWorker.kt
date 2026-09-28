package com.keuangan.app.reminder

import android.Manifest
import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.PendingIntent
import android.content.Context
import android.content.Intent
import android.content.pm.PackageManager
import android.os.Build
import androidx.core.app.NotificationCompat
import androidx.core.app.NotificationManagerCompat
import androidx.core.content.ContextCompat
import androidx.work.CoroutineWorker
import androidx.work.WorkerParameters
import com.keuangan.app.KeuanganApp
import com.keuangan.app.MainActivity
import com.keuangan.app.data.getOrNull

/**
 * Periodically asks the server for household reminders (budgets nearly spent,
 * debts coming due, goals nearing their deadline) and shows one friendly local
 * notification — no FCM or push service required. Skips duplicate work, so the
 * family only gets pinged when something actually changed.
 */
class ReminderWorker(
    context: Context,
    params: WorkerParameters,
) : CoroutineWorker(context, params) {

    override suspend fun doWork(): Result {
        val app = applicationContext as? KeuanganApp ?: return Result.failure()
        if (app.tokenStore.token == null) return Result.success()

        val familyId = app.repository.loadFamily(force = true).getOrNull()?.id ?: return Result.success()
        val reminders = app.repository.familyReminders(familyId).getOrNull().orEmpty()
        if (reminders.isEmpty()) return Result.success()

        val prefs = applicationContext.getSharedPreferences(PREFS, Context.MODE_PRIVATE)
        val key = reminders.joinToString("|") { it.message }
        if (prefs.getString(KEY_LAST, null) == key) return Result.success()
        prefs.edit().putString(KEY_LAST, key).apply()

        showNotification(reminders.map { it.message })
        return Result.success()
    }

    private fun showNotification(lines: List<String>) {
        if (Build.VERSION.SDK_INT >= 33 &&
            ContextCompat.checkSelfPermission(applicationContext, Manifest.permission.POST_NOTIFICATIONS) !=
            PackageManager.PERMISSION_GRANTED
        ) {
            return
        }

        val manager = applicationContext.getSystemService(NotificationManager::class.java)
        if (Build.VERSION.SDK_INT >= 26) {
            manager.createNotificationChannel(
                NotificationChannel(CHANNEL_ID, "Pengingat keluarga", NotificationManager.IMPORTANCE_DEFAULT),
            )
        }

        val content = when {
            lines.size <= 3 -> lines.joinToString("\n")
            else -> lines.take(3).joinToString("\n") + "\n+" + (lines.size - 3) + " pengingat lainnya"
        }

        val intent = Intent(applicationContext, MainActivity::class.java).apply {
            flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TOP
        }
        val pending = PendingIntent.getActivity(
            applicationContext,
            0,
            intent,
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE,
        )

        val notification = NotificationCompat.Builder(applicationContext, CHANNEL_ID)
            .setSmallIcon(applicationContext.applicationInfo.icon)
            .setContentTitle("Pengingat keluarga kamu")
            .setContentText(lines.first())
            .setStyle(NotificationCompat.BigTextStyle().bigText(content))
            .setPriority(NotificationCompat.PRIORITY_DEFAULT)
            .setAutoCancel(true)
            .setContentIntent(pending)
            .build()

        NotificationManagerCompat.from(applicationContext).notify(NOTIFICATION_ID, notification)
    }

    companion object {
        private const val CHANNEL_ID = "family_reminders"
        private const val NOTIFICATION_ID = 4102
        private const val PREFS = "reminder_prefs"
        private const val KEY_LAST = "last_key"
    }
}