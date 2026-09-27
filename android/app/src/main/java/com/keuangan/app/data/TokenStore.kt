package com.keuangan.app.data

import android.content.Context
import androidx.datastore.preferences.core.edit
import androidx.datastore.preferences.core.stringPreferencesKey
import androidx.datastore.preferences.preferencesDataStore
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.runBlocking

private val Context.dataStore by preferencesDataStore(name = "keuangan_session")

/**
 * Holds the Sanctum bearer token.
 *
 * The OkHttp interceptor needs the token synchronously on a background thread,
 * so the current value is cached in memory and DataStore is only the durable
 * copy. On cold start the value is loaded once by [restore] before the UI runs.
 */
class TokenStore(private val context: Context) {

    @Volatile
    private var cached: String? = null

    val token: String?
        get() = cached

    suspend fun restore(): String? {
        cached = context.dataStore.data.first()[KEY_TOKEN]
        return cached
    }

    suspend fun save(value: String) {
        cached = value
        context.dataStore.edit { it[KEY_TOKEN] = value }
    }

    suspend fun clear() {
        cached = null
        context.dataStore.edit { it.remove(KEY_TOKEN) }
    }

    companion object {
        private val KEY_TOKEN = stringPreferencesKey("token")
    }
}

/** Read blocking fallback for use inside OkHttp interceptors only. */
internal fun TokenStore.tokenBlocking(): String? =
    token ?: runBlocking { restore() }
