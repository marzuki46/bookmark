package com.keuangan.app.data

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable

/**
 * Contracts for the household ("family") API.
 *
 * Kept apart from Models.kt so the personal-finance DTOs and the household
 * DTOs can move independently: they were built for different screens and
 * change for different reasons.
 *
 * Every field the server may omit carries a default. A missing required field
 * would fail the decode of the whole response, and one null field must not
 * blank the dashboard.
 */

// --- Login by code ---

@Serializable
data class AppLoginRequest(
    val code: String,
    @SerialName("device_name") val deviceName: String = "android",
)

@Serializable
data class AppLoginResponse(
    val token: String,
    val user: UserDto? = null,
)

@Serializable
data class RotateCodeResponse(
    val code: String = "",
    val token: String = "",
    @SerialName("rotated_at") val rotatedAt: String? = null,
    val message: String = "",
)

// --- Device registration ---

@Serializable
data class DeviceRequest(
    @SerialName("fcm_token") val fcmToken: String,
    val platform: String = "android",
    @SerialName("app_version") val appVersion: String? = null,
)

@Serializable
data class DeviceDto(
    val id: Int,
    val platform: String = "android",
    @SerialName("app_version") val appVersion: String? = null,
    @SerialName("last_seen_at") val lastSeenAt: String? = null,
)

@Serializable
data class DeviceListResponse(val data: List<DeviceDto> = emptyList())

@Serializable
data class DeviceResponse(
    val data: DeviceDto = DeviceDto(id = 0),
)

// --- Family ---

@Serializable
data class HousingComplexDto(
    val id: Int,
    val name: String,
    val code: String? = null,
)

@Serializable
data class FamilyMemberDto(
    // Defaulted: a member row missing a role/payer label must never blank the
    // whole family detail (e.g. an older server response without payer fields).
    @SerialName("user_id") val userId: Int = 0,
    val role: String = "member",
    val name: String? = null,
    @SerialName("payer_role") val payerRole: String? = null,
    @SerialName("payer_label") val payerLabel: String? = null,
    val relationship: String = "adult",
    val visibility: Map<String, Boolean> = emptyMap(),
)

@Serializable
data class FamilyDto(
    val id: Int,
    val name: String,
    val role: String? = null,
    @SerialName("payer_role") val payerRole: String? = null,
    @SerialName("payer_label") val payerLabel: String? = null,
    @SerialName("members_count") val membersCount: Int = 0,
    @SerialName("housing_complex") val housingComplex: HousingComplexDto? = null,
    val members: List<FamilyMemberDto> = emptyList(),
)

@Serializable
data class FamilyListResponse(val data: List<FamilyDto> = emptyList())

@Serializable
data class FamilyResponse(val data: FamilyDto)

@Serializable
data class PayerRoleRequest(@SerialName("payer_role") val payerRole: String)

/**
 * Defaults everywhere: this is written from a "best effort" PATCH whose exact
 * payload shape may vary by server version, so a missing field must degrade to
 * a no-op instead of failing the whole response.
 */
@Serializable
data class PayerRoleResponse(
    @SerialName("user_id") val userId: Int = 0,
    @SerialName("payer_role") val payerRole: String? = null,
    @SerialName("payer_label") val payerLabel: String? = null,
)

/** Family health, shaped by FamilyAIService::healthScore. */
@Serializable
data class FamilyHealthDto(
    val score: Int = 0,
    val grade: String = "",
    val income: Double = 0.0,
    val expense: Double = 0.0,
    val savings: Double = 0.0,
    @SerialName("emergency_current") val emergencyCurrent: Double = 0.0,
    @SerialName("emergency_target") val emergencyTarget: Double = 0.0,
    @SerialName("total_debt") val totalDebt: Double = 0.0,
    @SerialName("insufficient_data") val insufficientData: Boolean = false,
    @SerialName("planned_debt") val plannedDebt: Double = 0.0,
    @SerialName("realized_debt_this_month") val realizedDebtThisMonth: Double = 0.0,
    @SerialName("uncovered_debt") val uncoveredDebt: Double = 0.0,
    val recommendations: List<String> = emptyList(),
)

@Serializable
data class FamilySummaryResponse(val data: FamilyHealthDto = FamilyHealthDto())

/**
 * One cash-flow window: the current period, the period before it, and the
 * rupiah / percent movement between the two. income_pct & expense_pct are null
 * when the previous window had no movement to compare against.
 */
@Serializable
data class ForecastWindowDto(
    val current: ForecastAmountDto = ForecastAmountDto(),
    val previous: ForecastAmountDto = ForecastAmountDto(),
    val delta: ForecastDeltaDto = ForecastDeltaDto(),
)

@Serializable
data class ForecastAmountDto(
    val income: Double = 0.0,
    val expense: Double = 0.0,
)

@Serializable
data class ForecastDeltaDto(
    @SerialName("income_delta") val incomeDelta: Double = 0.0,
    @SerialName("income_pct") val incomePct: Double? = null,
    @SerialName("expense_delta") val expenseDelta: Double = 0.0,
    @SerialName("expense_pct") val expensePct: Double? = null,
)

/**
 * Today / this week / this month, each vs. its previous window.
 *
 * Visibility is deliberately NOT on this object: the server returns it as a
 * sibling `license` key at the top level of the payload, not inside `data`.
 */
@Serializable
data class FamilyForecastDto(
    val today: ForecastWindowDto = ForecastWindowDto(),
    val week: ForecastWindowDto = ForecastWindowDto(),
    val month: ForecastWindowDto = ForecastWindowDto(),
)

/**
 * Whether the viewer is allowed to see income / expense figures. When a stream
 * is hidden the server zeroes its amounts, so the client also needs the flag to
 * explain the zeros rather than show a misleading "Rp 0".
 */
@Serializable
data class ForecastLicenseDto(
    @SerialName("income_visible") val incomeVisible: Boolean = true,
    @SerialName("expense_visible") val expenseVisible: Boolean = true,
)

@Serializable
data class FamilyForecastResponse(
    val data: FamilyForecastDto = FamilyForecastDto(),
    val license: ForecastLicenseDto = ForecastLicenseDto(),
)

// --- Kang Cuan: family financial advisor ("Pendamping Keuangan") ---

@Serializable
data class AdvisorContextDto(
    val income: Double = 0.0,
    val expense: Double = 0.0,
    val savings: Double = 0.0,
    @SerialName("average_monthly_expense") val averageMonthlyExpense: Double = 0.0,
    @SerialName("emergency_current") val emergencyCurrent: Double = 0.0,
    @SerialName("emergency_target") val emergencyTarget: Double = 0.0,
    @SerialName("emergency_month_coverage") val emergencyMonthCoverage: Double? = null,
    @SerialName("mandatory_debt") val mandatoryDebt: Double = 0.0,
    @SerialName("planned_debt") val plannedDebt: Double = 0.0,
    @SerialName("realized_debt_this_month") val realizedDebtThisMonth: Double = 0.0,
    @SerialName("uncovered_debt") val uncoveredDebt: Double = 0.0,
    @SerialName("total_debt") val totalDebt: Double = 0.0,
)

@Serializable
data class AdvisorPostDto(
    val label: String = "",
    val amount: Double = 0.0,
    val source: String? = null,
    val planned: Double? = null,
    val realized: Double? = null,
)

@Serializable
data class AdvisorPlanDto(
    val income: Double = 0.0,
    val deficit: Double = 0.0,
    @SerialName("is_deficit") val isDeficit: Boolean = false,
    val posts: Map<String, AdvisorPostDto> = emptyMap(),
    val total: Double = 0.0,
    @SerialName("emergency_target") val emergencyTarget: Double = 0.0,
    @SerialName("emergency_current") val emergencyCurrent: Double = 0.0,
    @SerialName("emergency_month_coverage") val emergencyMonthCoverage: Double? = null,
    @SerialName("budget_suggestion") val budgetSuggestion: List<AdvisorBudgetSuggestionDto> = emptyList(),
)

@Serializable
data class AdvisorBudgetSuggestionDto(
    @SerialName("category_id") val categoryId: Int? = null,
    val name: String = "",
    val amount: Double = 0.0,
)

@Serializable
data class AdvisorProfileDto(
    @SerialName("monthly_income") val monthlyIncome: Double? = null,
    @SerialName("income_type") val incomeType: String? = null,
    @SerialName("members_count") val membersCount: Int? = null,
    @SerialName("dependents_count") val dependentsCount: Int? = null,
    @SerialName("housing_type") val housingType: String? = null,
    @SerialName("has_protection") val hasProtection: Boolean? = null,
    @SerialName("uncovered_members") val uncoveredMembers: Int? = null,
    val priorities: List<String> = emptyList(),
    @SerialName("monthly_essential_override") val monthlyEssentialOverride: Double? = null,
    val notes: String? = null,
)

@Serializable
data class AdvisorStatusDto(
    val enabled: Boolean = false,
    val accessible: Boolean? = null,
    val message: String? = null,
    val context: AdvisorContextDto? = null,
    val plan: AdvisorPlanDto? = null,
    val profile: AdvisorProfileDto = AdvisorProfileDto(),
)

@Serializable
data class AdvisorToggleRequest(val enabled: Boolean)

@Serializable
data class AdvisorStatusResponse(val data: AdvisorStatusDto = AdvisorStatusDto())

@Serializable
data class AdvisorToggleResponse(val data: AdvisorStatusDto = AdvisorStatusDto())

@Serializable
data class AdvisorProfileRequest(
    @SerialName("monthly_income") val monthlyIncome: Double? = null,
    @SerialName("income_type") val incomeType: String? = null,
    @SerialName("members_count") val membersCount: Int? = null,
    @SerialName("dependents_count") val dependentsCount: Int? = null,
    @SerialName("housing_type") val housingType: String? = null,
    @SerialName("has_protection") val hasProtection: Boolean? = null,
    @SerialName("uncovered_members") val uncoveredMembers: Int? = null,
    val priorities: List<String>? = null,
    @SerialName("monthly_essential_override") val monthlyEssentialOverride: Double? = null,
    val notes: String? = null,
)

// --- Categories ---

@Serializable
data class FamilyCategoryDto(
    val id: Int,
    val name: String,
    val type: String = "expense",
    val icon: String? = null,
    val color: String? = null,
    @SerialName("is_system") val isSystem: Boolean = false,
)

@Serializable
data class FamilyCategoryListResponse(val data: List<FamilyCategoryDto> = emptyList())

@Serializable
data class FamilyCategoryRequest(
    val name: String,
    val type: String,
    val icon: String? = null,
    val color: String? = null,
)

@Serializable
data class FamilyCategoryResponse(val data: FamilyCategoryDto)

// --- Income sources ---

@Serializable
data class IncomeSourceDto(
    val id: Int,
    val name: String,
    val type: String = "other",
    @SerialName("is_default") val isDefault: Boolean? = null,
    @SerialName("is_active") val isActive: Boolean? = null,
)

@Serializable
data class IncomeSourceListResponse(val data: List<IncomeSourceDto> = emptyList())

@Serializable
data class IncomeSourceRequest(
    val name: String,
    val type: String = "other",
    @SerialName("is_default") val isDefault: Boolean? = null,
)

@Serializable
data class IncomeSourceResponse(val data: IncomeSourceDto)

// --- Household transactions ---

@Serializable
data class FamilyRecordedByDto(val id: Int, val name: String? = null)

@Serializable
data class FamilyTransactionDto(
    val id: Int,
    @SerialName("family_id") val familyId: Int = 0,
    val type: String = "expense",
    val amount: Double = 0.0,
    val description: String? = null,
    val date: String,
    val payer: String = "shared",
    @SerialName("payment_method") val paymentMethod: String? = null,
    val notes: String? = null,
    @SerialName("recorded_by") val recordedBy: FamilyRecordedByDto? = null,
    val category: FamilyCategoryDto? = null,
    @SerialName("income_source") val incomeSource: IncomeSourceDto? = null,
    @SerialName("created_at") val createdAt: String? = null,
)

@Serializable
data class FamilyTransactionRequest(
    val type: String,
    val amount: Double,
    val description: String,
    val date: String,
    val payer: String = "shared",
    @SerialName("category_id") val categoryId: Int? = null,
    @SerialName("income_source_id") val incomeSourceId: Int? = null,
    @SerialName("payment_method") val paymentMethod: String? = null,
    val notes: String? = null,
)

/**
 * Transaction payload plus the instant nudge.
 *
 * The server evaluates the free rules on save, so a newly recorded expense can
 * come back with advice attached and the app can show it without a second
 * round trip.
 */
@Serializable
data class FamilyTransactionSaveResponse(
    val data: FamilyTransactionDto? = null,
    val nudge: NudgeDto? = null,
)

@Serializable
data class FamilyTransactionListResponse(
    val data: List<FamilyTransactionDto> = emptyList(),
    val meta: MetaDto? = null,
)

// --- Debts ---

@Serializable
data class FamilyDebtDto(
    val id: Int,
    val name: String,
    val type: String = "payable",
    val amount: Double = 0.0,
    @SerialName("paid_amount") val paidAmount: Double = 0.0,
    @SerialName("remaining_amount") val remainingAmount: Double = 0.0,
    @SerialName("interest_rate") val interestRate: Double? = null,
    val installment: Double? = null,
    @SerialName("due_date") val dueDate: String? = null,
val notes: String? = null,
    val status: String = "open",
    val priority: Int? = null,
    @SerialName("is_overdue") val isOverdue: Boolean = false,
)

@Serializable
data class FamilyDebtListResponse(val data: List<FamilyDebtDto> = emptyList())

@Serializable
data class FamilyDebtResponse(val data: FamilyDebtDto)

@Serializable
data class FamilyDebtRequest(
    val name: String,
    val type: String = "payable",
    val amount: Double,
    @SerialName("interest_rate") val interestRate: Double? = null,
    val installment: Double? = null,
@SerialName("due_date") val dueDate: String? = null,
    val priority: Int? = null,
    val notes: String? = null,
)

@Serializable
data class DebtPaymentRequest(val amount: Double)

/** Paying a debt returns the refetched debt row. */
@Serializable
data class DebtPaymentResponse(
    val data: FamilyDebtDto? = null,
)

// --- Budgets ---

@Serializable
data class FamilyBudgetDto(
    val id: Int,
    @SerialName("category_id") val categoryId: Int? = null,
    @SerialName("category_name") val categoryName: String? = null,
    val amount: Double = 0.0,
    val spent: Double = 0.0,
)

@Serializable
data class FamilyBudgetMonthDto(
    val month: Int = 1,
    val year: Int = 2024,
    val budgets: List<FamilyBudgetDto> = emptyList(),
)

@Serializable
data class FamilyBudgetResponse(val data: FamilyBudgetMonthDto = FamilyBudgetMonthDto())

@Serializable
data class FamilyBudgetRequest(
    @SerialName("category_id") val categoryId: Int?,
    val month: Int,
    val year: Int,
    val amount: Double,
)

@Serializable
data class FamilyBudgetUpdateRequest(val amount: Double)

// --- Goals ---

@Serializable
data class FamilyGoalDto(
    val id: Int,
    val name: String,
    val type: String = "custom",
    @SerialName("target_amount") val targetAmount: Double = 0.0,
    @SerialName("current_amount") val currentAmount: Double = 0.0,
    @SerialName("remaining_amount") val remainingAmount: Double = 0.0,
    @SerialName("progress_percent") val progressPercent: Double = 0.0,
    @SerialName("monthly_allocation") val monthlyAllocation: Double = 0.0,
    val deadline: String? = null,
    val icon: String? = null,
    val color: String? = null,
    val priority: Int? = null,
    val status: String = "active",
)

@Serializable
data class FamilyGoalListResponse(val data: List<FamilyGoalDto> = emptyList())

@Serializable
data class FamilyGoalResponse(val data: FamilyGoalDto)

@Serializable
data class FamilyGoalRequest(
    val name: String,
    val type: String = "custom",
    @SerialName("target_amount") val targetAmount: Double,
    @SerialName("monthly_allocation") val monthlyAllocation: Double? = null,
    val deadline: String? = null,
    val priority: Int? = null,
    val icon: String? = null,
    val color: String? = null,
)

@Serializable
data class GoalContributionRequest(val amount: Double)

// --- Insights & nudges ---

@Serializable
data class InsightDto(
    val id: Int,
    val scope: String = "family",
    val message: String = "",
    val tone: String = "neutral",
    @SerialName("week_key") val weekKey: String = "",
    @SerialName("is_fallback") val isFallback: Boolean = false,
    @SerialName("generated_at") val generatedAt: String? = null,
    @SerialName("read_at") val readAt: String? = null,
)

@Serializable
data class NudgeDto(
    val tone: String = "neutral",
    val message: String = "",
    val code: String = "",
)

@Serializable
data class IncomeBySourceDto(
    // Nullable: income recorded without a source is bucketed under a synthetic
    // "Belum dikategorikan" row rather than dropped.
    @SerialName("income_source_id") val incomeSourceId: Int? = null,
    val name: String = "",
    val total: Double = 0.0,
)

/** Everything the family dashboard needs, in one request. */
@Serializable
data class FamilyInsightsResponse(
    val family: InsightDto? = null,
    val personal: InsightDto? = null,
    val nudge: NudgeDto? = null,
    @SerialName("income_by_source") val incomeBySource: List<IncomeBySourceDto> = emptyList(),
    @SerialName("week_key") val weekKey: String = "",
    @SerialName("days_left_this_month") val daysLeftThisMonth: Int = 0,
)

@Serializable
data class NudgeResponse(val data: NudgeDto? = null)

@Serializable
data class InsightResponse(val data: InsightDto? = null)

