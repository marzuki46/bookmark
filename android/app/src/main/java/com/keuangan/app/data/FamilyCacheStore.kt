package com.keuangan.app.data

import android.content.Context
import kotlinx.coroutines.sync.Mutex
import kotlinx.coroutines.sync.withLock
import kotlinx.serialization.Serializable
import java.io.File

/**
 * Everything the household screens need to stay usable offline, mirrored to a
 * plain JSON file per family under filesDir/family_cache.
 *
 * Each section is overwritten with the freshest server response (collections are
 * union-merged so later filter combinations never lose earlier rows), and every
 * read falls back to the mirror when the network is unreachable. Writes are
 * serialized with a mutex plus atomic (tmp + rename) replaces, matching
 * [OfflineTxStore] so a crash mid-write cannot corrupt the mirror.
 */
@Serializable
data class FamilyCacheData(
    val detail: FamilyDto? = null,
    val health: FamilyHealthDto? = null,
    val reminders: List<ReminderDto> = emptyList(),
    val trend: List<TrendPointDto> = emptyList(),
    val categories: List<FamilyCategoryDto> = emptyList(),
    val debts: List<FamilyDebtDto> = emptyList(),
    val goals: List<FamilyGoalDto> = emptyList(),
    val syncedAt: String? = null,
)

class FamilyCacheStore(private val context: Context) {

    private val dir: File get() = File(context.filesDir, "family_cache")
    private val json = ApiClient.json
    private val mutex = Mutex()

    private fun fileFor(familyId: Int) = File(dir, "family-$familyId.json")

    suspend fun read(familyId: Int): FamilyCacheData = mutex.withLock {
        readUnsafe(familyId)
    }

    /** Read-modify-write under one lock; [transform] sees the current mirror. */
    suspend fun write(familyId: Int, transform: (FamilyCacheData) -> FamilyCacheData): FamilyCacheData =
        mutex.withLock {
            val updated = transform(readUnsafe(familyId)).copy(
                syncedAt = java.time.LocalDateTime.now().toString(),
            )
            writeAtomic(fileFor(familyId), json.encodeToString(FamilyCacheData.serializer(), updated))
            updated
        }

    /** Drops every family mirror, used on login/logout so a new account never sees old data. */
    suspend fun clearAll() = mutex.withLock {
        dir.mkdirs()
        dir.listFiles()?.forEach { it.delete() }
    }

    private fun readUnsafe(familyId: Int): FamilyCacheData {
        val file = fileFor(familyId)
        if (!file.exists()) return FamilyCacheData()
        return runCatching { json.decodeFromString(FamilyCacheData.serializer(), file.readText()) }
            .getOrDefault(FamilyCacheData())
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