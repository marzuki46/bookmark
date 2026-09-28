package com.keuangan.app

import android.app.Application
import com.keuangan.app.data.ApiClient
import com.keuangan.app.data.Connectivity
import com.keuangan.app.data.KeuanganRepository
import com.keuangan.app.data.OfflineTxStore
import com.keuangan.app.data.TokenStore
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.SupervisorJob
import kotlinx.coroutines.delay
import kotlinx.coroutines.launch

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
        )
        observeRecovery()
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
}
