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

    @Volatile
    private var cachedUserName: String? = null

    val token: String?
        get() = cachedToken

    val userId: Int?
        get() = cachedUserId

    /**
     * The signed-in user's display name, cached from the login response.
     *
     * The dashboard greets the member by name. Resolving it purely by matching
     * `userId` against `family.members` fails whenever the id was never cached,
     * so the name is kept as its own fallback rather than re-fetched.
     */
    val userName: String?
        get() = cachedUserName

    val familyId: Int?
        get() = cachedFamilyId

    suspend fun restore(): String? {
        val data = context.dataStore.data.first()
        cachedToken = data[KEY_TOKEN]
        cachedUserId = data[KEY_USER_ID]
        cachedUserName = data[KEY_USER_NAME]
        cachedFamilyId = data[KEY_FAMILY_ID]
        return cachedToken
    }

    suspend fun save(value: String) {
        saveSession(value, cachedUserId)
    }

    suspend fun saveSession(token: String, userId: Int?, userName: String? = cachedUserName) {
        cachedToken = token
        cachedUserId = userId
        cachedUserName = userName
        context.dataStore.edit {
            it[KEY_TOKEN] = token
            if (userId == null) {
                it.remove(KEY_USER_ID)
            } else {
                it[KEY_USER_ID] = userId
            }
            if (userName == null) {
                it.remove(KEY_USER_NAME)
            } else {
                it[KEY_USER_NAME] = userName
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
        cachedUserName = null
        cachedFamilyId = null
        context.dataStore.edit {
            it.remove(KEY_TOKEN)
            it.remove(KEY_USER_ID)
            it.remove(KEY_USER_NAME)
            it.remove(KEY_FAMILY_ID)
        }
    }

    companion object {
        private val KEY_TOKEN = stringPreferencesKey("token")
        private val KEY_USER_ID = intPreferencesKey("user_id")
        private val KEY_USER_NAME = stringPreferencesKey("user_name")
        private val KEY_FAMILY_ID = intPreferencesKey("family_id")
    }
}

/** Read blocking fallback for use inside OkHttp interceptors only. */
internal fun TokenStore.tokenBlocking(): String? =
    token ?: runBlocking { restore() }
