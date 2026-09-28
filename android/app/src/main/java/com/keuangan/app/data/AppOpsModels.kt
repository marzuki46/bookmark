package com.keuangan.app.data

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable

/**
 * Contracts for app-level operations: in-app updates, crash reporting, and
 * the family "share a login code" flow. Kept apart from the finance and
 * household DTOs because these are used outside their screens.
 */

// --- In-app update check ---

@Serializable
data class AppUpdateDto(
    @SerialName("latest_version_code") val latestVersionCode: Int = 1,
    @SerialName("latest_version_name") val latestVersionName: String = "",
    @SerialName("download_url") val downloadUrl: String = "",
    val notes: String = "",
    @SerialName("update_available") val updateAvailable: Boolean = false,
)

@Serializable
data class AppUpdateResponse(val data: AppUpdateDto = AppUpdateDto())

// --- Crash / error reporting ---

@Serializable
data class AppErrorRequest(
    @SerialName("error_class") val errorClass: String? = null,
    val message: String? = null,
    val route: String? = null,
    @SerialName("stack_trace") val stackTrace: String? = null,
    @SerialName("app_version") val appVersion: String? = null,
    val platform: String = "android",
    @SerialName("occurred_at") val occurredAt: String? = null,
)

@Serializable
data class AppErrorResponse(val ok: Boolean = false)

// --- Family: current login code ---

@Serializable
data class LoginCodeData(val code: String = "")

@Serializable
data class LoginCodeResponse(val data: LoginCodeData = LoginCodeData())

// --- Family: add spouse member (owner-only) ---

@Serializable
data class FamilyMemberRequest(
    val name: String,
    @SerialName("payer_role") val payerRole: String? = null,
    val email: String? = null,
)

@Serializable
data class NewFamilyMemberDto(
    @SerialName("user_id") val userId: Int = 0,
    val name: String? = null,
    @SerialName("payer_role") val payerRole: String? = null,
    @SerialName("payer_label") val payerLabel: String? = null,
    @SerialName("login_code") val loginCode: String = "",
)

@Serializable
data class FamilyMemberResponse(val data: NewFamilyMemberDto = NewFamilyMemberDto())