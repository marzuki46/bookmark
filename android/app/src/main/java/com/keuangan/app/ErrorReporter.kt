package com.keuangan.app

import com.keuangan.app.data.KeuanganRepository
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.SupervisorJob
import kotlinx.coroutines.launch
import java.io.PrintWriter
import java.io.StringWriter

/**
 * Global crash reporter. Wraps the default uncaught-exception handler and
 * pushes one fire-and-forget report to the server's app-error channel before
 * delegating to the previous handler, so behaviour (dialogs, crash uploaders)
 * is never replaced.
 */
object ErrorReporter {

    private val scope = CoroutineScope(SupervisorJob() + Dispatchers.IO)

    fun install(app: KeuanganApp) {
        if (BuildConfig.DEBUG) return
        val previous = Thread.getDefaultUncaughtExceptionHandler()
        if (previous is ErrorReporterHandler) return
        Thread.setDefaultUncaughtExceptionHandler(ErrorReporterHandler(previous, app.repository))
    }

    private class ErrorReporterHandler(
        private val previous: Thread.UncaughtExceptionHandler?,
        private val repository: KeuanganRepository,
    ) : Thread.UncaughtExceptionHandler {
        override fun uncaughtException(thread: Thread, throwable: Throwable) {
            val classError = throwable.javaClass.name
            val message = throwable.message?.takeIf { it.isNotBlank() } ?: classError
            val stack = StringWriter().also { throwable.printStackTrace(PrintWriter(it)) }.toString()

            scope.launch {
                runCatching { repository.reportCrash(classError, message, stack) }
            }

            previous?.uncaughtException(thread, throwable)
        }
    }
}