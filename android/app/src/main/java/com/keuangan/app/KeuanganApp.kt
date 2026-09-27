package com.keuangan.app

import android.app.Application
import com.keuangan.app.data.ApiClient
import com.keuangan.app.data.KeuanganRepository
import com.keuangan.app.data.TokenStore

class KeuanganApp : Application() {

    lateinit var tokenStore: TokenStore
        private set

    lateinit var repository: KeuanganRepository
        private set

    override fun onCreate() {
        super.onCreate()
        tokenStore = TokenStore(this)
        repository = KeuanganRepository(ApiClient.create(tokenStore), tokenStore)
    }
}
