package com.keuangan.app.data

import android.content.Context
import androidx.datastore.preferences.core.edit
import androidx.datastore.preferences.core.intPreferencesKey
import androidx.datastore.preferences.core.stringPreferencesKey
import androidx.datastore.preferences.preferencesDataStore
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.runBlocking

private val Context.dataStore by preferencesDataStore(name = "keuangan_session")

/**
 * Holds the Sanctum bearer token and the signed-in user id.
 *
 * The OkHttp interceptor needs the token synchronously on a background thread,
 * so the current value is cached in memory and DataStore is only the durable
 * copy. On cold start the value is loaded once by [restore] before the UI runs.
 */
class TokenStore(private val context: Context) {

    @Volatile
    private var cachedToken: String? = null

    @Volatile
    private var cachedUserId: Int? = null

    @Volatile
    private var cachedFamilyId: Int? = null

    val token: String?
        get() = cachedToken

    val userId: Int?
        get() = cachedUserId

    val familyId: Int?
        get() = cachedFamilyId

    suspend fun restore(): String? {
        val data = context.dataStore.data.first()
        cachedToken = data[KEY_TOKEN]
        cachedUserId = data[KEY_USER_ID]
        cachedFamilyId = data[KEY_FAMILY_ID]
        return cachedToken
    }

    suspend fun save(value: String) {
        saveSession(value, cachedUserId)
    }

    suspend fun saveSession(token: String, userId: Int?) {
        cachedToken = token
        cachedUserId = userId
        context.dataStore.edit {
            it[KEY_TOKEN] = token
            if (userId == null) {
                it.remove(KEY_USER_ID)
            } else {
                it[KEY_USER_ID] = userId
            }
        }
    }

    suspend fun saveFamilyId(value: Int?) {
        cachedFamilyId = value
        context.dataStore.edit {
            if (value == null) it.remove(KEY_FAMILY_ID) else it[KEY_FAMILY_ID] = value
        }
    }

    suspend fun clear() {
        cachedToken = null
        cachedUserId = null
        cachedFamilyId = null
        context.dataStore.edit {
            it.remove(KEY_TOKEN)
            it.remove(KEY_USER_ID)
            it.remove(KEY_FAMILY_ID)
        }
    }

    companion object {
        private val KEY_TOKEN = stringPreferencesKey("token")
        private val KEY_USER_ID = intPreferencesKey("user_id")
        private val KEY_FAMILY_ID = intPreferencesKey("family_id")
    }
}

/** Read blocking fallback for use inside OkHttp interceptors only. */
internal fun TokenStore.tokenBlocking(): String? =
    token ?: runBlocking { restore() }
