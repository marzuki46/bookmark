package com.keuangan.app.data

import android.content.Context
import kotlinx.coroutines.sync.Mutex
import kotlinx.coroutines.sync.withLock
import kotlinx.serialization.Serializable
import java.io.File

/** Outbox operation kinds, kept as plain strings so the queue stays readable. */
const val OP_CREATE = "create"
const val OP_UPDATE = "update"
const val OP_DELETE = "delete"

/**
 * A queued household-transaction write that could not reach the server yet.
 *
 * While offline the app keeps the household usable: creates/updates/deletes are
 * parked here in order, applied to the local read-cache immediately, and replayed
 * once connectivity returns. A failed replay is kept (not dropped) so the user's
 * entry survives; [failedMessage] explains why the retry did not go through.
 */
@Serializable
data class PendingTxOp(
    val opId: Long,
    val familyId: Int,
    val op: String, // "create" | "update" | "delete"
    /** Server id for update/delete; null for create. */
    val id: Int? = null,
    /** Negative placeholder id the cache uses until the create is confirmed. */
    val localId: Int? = null,
    val body: FamilyTransactionRequest? = null,
    val failedMessage: String? = null,
)

@Serializable
data class PendingOutbox(
    val ops: List<PendingTxOp> = emptyList(),
    var nextLocalId: Int = 1,
)

@Serializable
data class TxCache(
    val items: List<FamilyTransactionDto> = emptyList(),
    val syncedAt: String? = null,
)

/**
 * Durable local mirror for the transaction screen.
 *
 * Two kinds of persistence, both plain JSON files under filesDir/offline_tx so
 * no schema/ORM is involved:
 *  - one read-cache file per family, overwritten with the freshest server list;
 *  - one shared outbox replaying every write that has not reached the server.
 *
 * Writes are serialized with a mutex plus atomic (tmp + rename) replaces so a
 * crash mid-write cannot corrupt the queue.
 */
class OfflineTxStore(private val context: Context) {

    private val dir: File get() = File(context.filesDir, "offline_tx")
    private val json = ApiClient.json
    private val mutex = Mutex()

    private fun cacheFile(familyId: Int) = File(dir, "tx-$familyId.json")

    private fun outboxFile() = File(dir, "outbox.json")

    suspend fun readCache(familyId: Int): TxCache = mutex.withLock {
        readCacheUnsafe(familyId)
    }

    suspend fun writeCache(familyId: Int, items: List<FamilyTransactionDto>): TxCache =
        mutex.withLock {
            val cache = TxCache(items = items, syncedAt = java.time.LocalDateTime.now().toString())
            writeAtomic(cacheFile(familyId), json.encodeToString(TxCache.serializer(), cache))
            cache
        }

    suspend fun readOutbox(): PendingOutbox = mutex.withLock {
        val file = outboxFile()
        if (!file.exists()) return@withLock PendingOutbox()
        runCatching { json.decodeFromString(PendingOutbox.serializer(), file.readText()) }
            .getOrDefault(PendingOutbox())
    }

    suspend fun saveOutbox(outbox: PendingOutbox) = mutex.withLock {
        writeAtomic(outboxFile(), json.encodeToString(PendingOutbox.serializer(), outbox))
    }

    private fun readCacheUnsafe(familyId: Int): TxCache {
        val file = cacheFile(familyId)
        if (!file.exists()) return TxCache()
        return runCatching { json.decodeFromString(TxCache.serializer(), file.readText()) }
            .getOrDefault(TxCache())
    }

    private fun writeAtomic(file: File, content: String) {
        dir.mkdirs()
        val tmp = File(file.parentFile, file.name + ".tmp")
        tmp.writeText(content)
        if (file.exists()) file.delete()
        if (!tmp.renameTo(file)) {
            file.writeText(content)
        }
    }
}