package com.keuangan.app.update

import android.app.PendingIntent
import android.content.BroadcastReceiver
import android.content.Context
import android.content.Intent
import android.content.pm.PackageInstaller
import android.os.Build
import java.io.File
import java.io.IOException
import java.util.concurrent.TimeUnit
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import okhttp3.OkHttpClient
import okhttp3.Request

/**
 * Store-style update flow: the APK is streamed straight into a package
 * installer session. When the session is committed Android shows its own
 * confirmation dialog (like Play Store), then installs and updates the app.
 */
class InstallReceiver : BroadcastReceiver() {
    override fun onReceive(context: Context, intent: Intent) {
        when (intent.getIntExtra(PackageInstaller.EXTRA_STATUS, PackageInstaller.STATUS_FAILURE)) {
            PackageInstaller.STATUS_PENDING_USER_ACTION -> {
                val confirmation: Intent? = if (Build.VERSION.SDK_INT >= 33) {
                    intent.getParcelableExtra(Intent.EXTRA_INTENT, Intent::class.java)
                } else {
                    @Suppress("DEPRECATION")
                    intent.getParcelableExtra(Intent.EXTRA_INTENT)
                }
                if (confirmation != null) {
                    confirmation.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
                    context.startActivity(confirmation)
                }
            }
            PackageInstaller.STATUS_FAILURE,
            PackageInstaller.STATUS_FAILURE_ABORTED,
            -> {
                // The install dialog explains itself; the update dialog is
                // still open so the user can simply try again.
            }
            else -> {
                // STATUS_SUCCESS is normally covered by the system dialog.
            }
        }
    }
}

object AppUpdater {
    const val ACTION_INSTALL = "com.keuangan.app.ACTION_INSTALL"

    private val client = OkHttpClient.Builder()
        .connectTimeout(30, TimeUnit.SECONDS)
        .readTimeout(120, TimeUnit.SECONDS)
        .build()

    /** Streams the APK into the app cache, reporting 0f→1f progress. */
    suspend fun download(context: Context, url: String, onProgress: (Float) -> Unit): File =
        withContext(Dispatchers.IO) {
            val request = Request.Builder().url(url).build()
            client.newCall(request).execute().use { response ->
                if (!response.isSuccessful) {
                    throw IOException("Server merespons HTTP ${response.code}")
                }
                val body = response.body
                    ?: throw IOException("Respon tanpa isi dari server")
                val length = body.contentLength()
                val target = File(context.cacheDir, "update-${System.currentTimeMillis()}.apk")
                body.byteStream().use { input ->
                    target.outputStream().buffered().use { output ->
                        val buffer = ByteArray(64 * 1024)
                        var total = 0L
                        var read: Int
                        while (input.read(buffer).also { read = it } != -1) {
                            output.write(buffer, 0, read)
                            total += read
                            if (length > 0L) {
                                onProgress((total.toFloat() / length.toFloat()).coerceIn(0f, 1f))
                            }
                        }
                        output.flush()
                    }
                }
                target
            }
        }

    /** Uses [PackageInstaller.SessionParams.MODE_FULL_INSTALL] on our own package. */
    fun install(context: Context, apk: File) {
        val installer = context.packageManager.packageInstaller
        val params = PackageInstaller.SessionParams(PackageInstaller.SessionParams.MODE_FULL_INSTALL)
        params.setAppPackageName(context.packageName)
        val sessionId = installer.createSession(params)
        try {
            val session = installer.openSession(sessionId)
            try {
                val size = apk.length()
                val out = session.openWrite("package.apk", 0, size)
                apk.inputStream().use { input ->
                    out.use { output ->
                        input.copyTo(output)
                    }
                }
                session.commit(pendingSender(context))
            } finally {
                session.close()
            }
        } catch (e: Exception) {
            try {
                installer.abandonSession(sessionId)
            } catch (_: Exception) {
                // Best effort cleanup only.
            }
            throw e
        }
    }

    private fun pendingSender(context: Context): android.content.IntentSender {
        val intent = Intent(ACTION_INSTALL)
        val pending = PendingIntent.getBroadcast(
            context,
            0,
            intent,
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_MUTABLE,
        )
        return pending.intentSender
    }
}