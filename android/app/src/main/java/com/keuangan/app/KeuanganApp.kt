package com.keuangan.app

import android.app.Application
import androidx.work.Constraints
import androidx.work.ExistingPeriodicWorkPolicy
import androidx.work.NetworkType
import androidx.work.PeriodicWorkRequestBuilder
import androidx.work.WorkManager
import com.keuangan.app.data.ApiClient
import com.keuangan.app.data.Connectivity
import com.keuangan.app.data.FamilyCacheStore
import com.keuangan.app.data.KeuanganRepository
import com.keuangan.app.data.KangCuanStore
import com.keuangan.app.data.OfflineTxStore
import com.keuangan.app.data.TokenStore
import com.keuangan.app.reminder.KangCuanAlarmReceiver
import com.keuangan.app.reminder.KangCuanScheduler
import com.keuangan.app.reminder.ReminderWorker
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.SupervisorJob
import kotlinx.coroutines.delay
import kotlinx.coroutines.launch
import java.util.concurrent.TimeUnit

class KeuanganApp : Application() {

    lateinit var tokenStore: TokenStore
        private set

    lateinit var repository: KeuanganRepository
        private set

    lateinit var connectivity: Connectivity
        private set

    private val appScope = CoroutineScope(SupervisorJob() + Dispatchers.Default)

    override fun onCreate() {
        super.onCreate()
        tokenStore = TokenStore(this)
        connectivity = Connectivity(this)
        repository = KeuanganRepository(
            ApiClient.create(tokenStore),
            tokenStore,
            OfflineTxStore(this),
            KangCuanStore(this),
            FamilyCacheStore(this),
        )
        observeRecovery()
        scheduleReminders()
        armKangCuanAlarms()
    }

    /** Re-arms the Kang Cuan alarms whenever the app is opened with a session. */
    private fun armKangCuanAlarms() {
        appScope.launch {
            if (tokenStore.token == null) return@launch
            KangCuanScheduler.scheduleAll(this@KeuanganApp, KangCuanStore(this@KeuanganApp))
        }
    }

    /**
     * Replays offline writes as soon as connectivity comes back, without the
     * user doing anything. A short delay lets the network settle before syncing.
     */
    private fun observeRecovery() {
        appScope.launch {
            connectivity.recovered.collect {
                delay(1000)
                repository.syncPendingTransactions()
            }
        }
    }

    /** Gentle, infrequent nudge at most every 6h while the device is online. */
    private fun scheduleReminders() {
        val request = PeriodicWorkRequestBuilder<ReminderWorker>(6, TimeUnit.HOURS)
            .setConstraints(Constraints.Builder().setRequiredNetworkType(NetworkType.CONNECTED).build())
            .build()
        WorkManager.getInstance(this)
            .enqueueUniquePeriodicWork("family-reminders", ExistingPeriodicWorkPolicy.KEEP, request)
    }
}
