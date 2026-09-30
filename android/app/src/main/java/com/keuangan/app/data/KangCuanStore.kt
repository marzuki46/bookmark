package com.keuangan.app.data

import android.content.Context
import kotlinx.coroutines.sync.Mutex
import kotlinx.coroutines.sync.withLock
import kotlinx.serialization.Serializable
import java.io.File

/** Schedule the user configures on the phone (like setting an alarm). */
@Serializable
data class KangCuanSchedule(
    /** Penyemangat pagi. */
    val pagiEnabled: Boolean = true,
    val pagiHour: Int = 5,
    val pagiMinute: Int = 0,
    /** Rekap harian. */
    val malamEnabled: Boolean = true,
    val malamHour: Int = 21,
    val malamMinute: Int = 30,
    /** Refleksi bulanan: tgl 1 jam 22:00 (bahan renungan malam). */
    val bulananEnabled: Boolean = true,
    val bulananDay: Int = 1,
    val bulananHour: Int = 22,
    val bulananMinute: Int = 0,
)

/** One delivered Kang Cuan message, persisted to the user's local JSON. */
@Serializable
data class KangCuanMessage(
    val id: Long,
    val slot: String, // "pagi" | "malam" | "bulanan"
    val title: String,
    val body: String,
    val sentAt: String, // ISO-8601 instant
)

/** Full local state for Kang Cuan; nothing here is sent back to the server. */
@Serializable
data class KangCuanState(
    val schedule: KangCuanSchedule = KangCuanSchedule(),
    val messages: List<KangCuanMessage> = emptyList(),
    /** Templates last fetched from the server, so the alarm works offline too. */
    val affirmations: List<AffirmationDto> = emptyList(),
    /** Keys of already-delivered notifications to avoid repeats of the same text. */
    val deliveredKeys: List<String> = emptyList(),
    /** The user's first name, cached so "{nama}" keeps working offline. */
    val userName: String? = null,
)

/**
 * Durable local JSON mirror for Kang Cuan ("Pesan dari Kang Cuan").
 *
 * The server only provides templates; everything user-specific — the alarm
 * schedule, the delivered messages and their deletion — lives in this single
 * file under filesDir/kang_cuan, mirroring the OfflineTxStore pattern (mutex +
 * atomic replace, no schema/ORM).
 */
class KangCuanStore(private val context: Context) {

    private val file: File get() = File(File(context.filesDir, "kang_cuan"), "state.json")
    private val json = ApiClient.json
    private val mutex = Mutex()

    companion object {
        private const val MAX_MESSAGES = 100
        private const val MAX_DELIVERED_KEYS = 64
    }

    suspend fun read(): KangCuanState = mutex.withLock {
        if (!file.exists()) return@withLock KangCuanState()
        runCatching { json.decodeFromString(KangCuanState.serializer(), file.readText()) }
            .getOrDefault(KangCuanState())
    }

    suspend fun write(state: KangCuanState) = mutex.withLock {
        runCatching {
            file.parentFile?.mkdirs()
            val tmp = File(file.parentFile, file.name + ".tmp")
            tmp.writeText(json.encodeToString(KangCuanState.serializer(), state))
            if (file.exists()) file.delete()
            if (!tmp.renameTo(file)) {
                file.writeText(json.encodeToString(KangCuanState.serializer(), state))
            }
        }
    }

    suspend fun appendMessage(message: KangCuanMessage) {
        val state = read()
        write(state.copy(messages = (state.messages + message).takeLast(MAX_MESSAGES)))
    }

    suspend fun deleteMessage(id: Long) {
        val state = read()
        write(state.copy(messages = state.messages.filterNot { it.id == id }))
    }

    suspend fun deleteAllMessages() {
        val state = read()
        write(state.copy(messages = emptyList()))
    }

    suspend fun saveSchedule(schedule: KangCuanSchedule) {
        val state = read()
        write(state.copy(schedule = schedule))
    }

    suspend fun saveUserName(name: String?) {
        val state = read()
        if (state.userName == name) return
        write(state.copy(userName = name))
    }

    suspend fun storeTemplatesIfNewer(templates: List<AffirmationDto>) {
        if (templates.isEmpty()) return
        val state = read()
        if (templates.map { it.id }.toSet().isEmpty()) return
        write(state.copy(affirmations = templates))
    }

    suspend fun readTemplates(): List<AffirmationDto> = read().affirmations

    suspend fun recordDelivered(keys: List<String>) {
        val state = read()
        write(state.copy(deliveredKeys = (state.deliveredKeys + keys).takeLast(MAX_DELIVERED_KEYS)))
    }
}