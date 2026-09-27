package com.keuangan.app.data

import kotlinx.serialization.json.JsonArray
import kotlinx.serialization.json.jsonObject
import kotlinx.serialization.json.jsonPrimitive
import retrofit2.HttpException
import java.io.IOException

sealed interface ApiResult<out T> {
    data class Ok<T>(val value: T) : ApiResult<T>
    data class Err(val message: String, val code: Int? = null) : ApiResult<Nothing>
}

class ApiException(message: String, val code: Int? = null) : Exception(message)

/**
 * Turns the Laravel error envelope into one human readable line.
 *
 * Laravel returns `{"message": "...", "errors": {"amount": ["The amount ..."]}}`
 * for validation failures, so prefer the first field error when present.
 */
fun Throwable.toApiError(): ApiResult.Err = when (this) {
    is ApiException -> ApiResult.Err(message ?: fallbackMessage, code)

    is HttpException -> {
        val body = runCatching { response()?.errorBody()?.string() }.getOrNull()
        val parsed = body?.let { parseErrorBody(it) }
        parsed ?: ApiResult.Err("Server error (${code()})", code())
    }

    is IOException -> ApiResult.Err("Tidak bisa terhubung ke server. Periksa koneksi.")

    else -> ApiResult.Err(message ?: fallbackMessage)
}

private const val fallbackMessage = "Terjadi kesalahan"

private fun parseErrorBody(body: String): ApiResult.Err? = runCatching {
    val root = ApiClient.json.parseToJsonElement(body).jsonObject
    val fieldError = root["errors"]?.jsonObject?.values
        ?.firstNotNullOfOrNull { (it as? JsonArray)?.firstOrNull()?.jsonPrimitive?.content }
    val message = fieldError ?: root["message"]?.jsonPrimitive?.content
    message?.let { ApiResult.Err(it) }
}.getOrNull()

/** Throws [ApiException] on failure so repository bodies stay linear. */
suspend fun <T> apiCall(block: suspend () -> T): T = try {
    block()
} catch (e: Throwable) {
    val err = e.toApiError()
    throw ApiException(err.message, err.code)
}
