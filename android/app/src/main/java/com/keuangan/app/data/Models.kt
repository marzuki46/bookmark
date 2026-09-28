package com.keuangan.app.data

import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable

// --- Auth ---

@Serializable
data class LoginRequest(
    val email: String,
    val password: String,
    @SerialName("device_name") val deviceName: String = "android",
)

@Serializable
data class LoginResponse(val token: String, val user: UserDto? = null)

@Serializable
data class UserDto(
    val id: Int,
    val name: String? = null,
    val email: String? = null,
)

// --- Categories ---

@Serializable
data class CategoryDto(
    val id: Int,
    val name: String,
    // Defaulted: a category nested inside a transaction may omit it, and a
    // missing required field would fail the whole response decode.
    val type: String = "expense",
    val icon: String? = null,
    val color: String? = null,
)

@Serializable
data class CategoryRequest(
    val name: String,
    val type: String,
    val icon: String? = null,
    val color: String? = null,
)

@Serializable
data class CategoryListResponse(val data: List<CategoryDto> = emptyList())

@Serializable
data class CategoryResponse(val data: CategoryDto)

// --- Transactions ---

@Serializable
data class TransactionDto(
    val id: Int,
    @SerialName("category_id") val categoryId: Int? = null,
    val type: String,
    val amount: Double,
    val description: String? = null,
    val date: String,
    val source: String? = null,
    val category: CategoryDto? = null,
)

@Serializable
data class TransactionRequest(
    val type: String,
    val amount: Double,
    val description: String? = null,
    val date: String,
    @SerialName("category_id") val categoryId: Int? = null,
)

@Serializable
data class TransactionListResponse(
    val data: List<TransactionDto> = emptyList(),
    val meta: MetaDto? = null,
)

@Serializable
data class MetaDto(
    val current_page: Int = 1,
    @SerialName("last_page") val lastPage: Int = 1,
    val total: Int = 0,
)

@Serializable
data class TransactionResponse(val data: TransactionDto)

// --- Dashboard ---

@Serializable
data class PeriodDto(val from: String, val to: String, val label: String)

@Serializable
data class StatsDto(
    @SerialName("total_income") val totalIncome: Double = 0.0,
    @SerialName("total_expense") val totalExpense: Double = 0.0,
    val balance: Double = 0.0,
    val count: Int = 0,
    @SerialName("savings_rate") val savingsRate: Double = 0.0,
    @SerialName("avg_daily_expense") val avgDailyExpense: Double = 0.0,
)

@Serializable
data class MonthlyPointDto(
    val month: String,
    val label: String,
    @SerialName("long_label") val longLabel: String? = null,
    val income: Double = 0.0,
    val expense: Double = 0.0,
    val balance: Double = 0.0,
)

@Serializable
data class DailyPointDto(
    val date: String,
    val income: Double = 0.0,
    val expense: Double = 0.0,
)

@Serializable
data class CategoryBreakdownDto(
    val id: Int? = null,
    val name: String,
    val icon: String? = null,
    val color: String? = null,
    val total: Double = 0.0,
    val count: Int = 0,
    val share: Double = 0.0,
)

@Serializable
data class BiggestExpenseDto(
    val description: String? = null,
    val amount: Double = 0.0,
    val date: String? = null,
)

@Serializable
data class InsightsDto(
    @SerialName("top_expense_category") val topExpenseCategory: CategoryBreakdownDto? = null,
    @SerialName("top_income_category") val topIncomeCategory: CategoryBreakdownDto? = null,
    @SerialName("biggest_expense") val biggestExpense: BiggestExpenseDto? = null,
    @SerialName("expense_change_pct") val expenseChangePct: Double? = null,
    @SerialName("income_change_pct") val incomeChangePct: Double? = null,
    val health: String? = null,
)

@Serializable
data class DashboardResponse(
    val period: PeriodDto? = null,
    val stats: StatsDto = StatsDto(),
    val monthly: List<MonthlyPointDto> = emptyList(),
    val daily: List<DailyPointDto> = emptyList(),
    @SerialName("expense_by_category") val expenseByCategory: List<CategoryBreakdownDto> = emptyList(),
    @SerialName("income_by_category") val incomeByCategory: List<CategoryBreakdownDto> = emptyList(),
    val insights: InsightsDto? = null,
    val recent: List<TransactionDto> = emptyList(),
)

// --- AI ---

@Serializable
data class AdviceRequest(val period: String = "this_month")

@Serializable
data class AdviceResponse(
    val advice: String? = null,
    val model: String? = null,
    val health: String? = null,
)

@Serializable
data class AskRequest(val question: String)

@Serializable
data class AskResponse(val answer: String? = null, val model: String? = null)

// --- Personal profile (the "about" menu) ---

@Serializable
data class SubscriptionDto(
    val active: Boolean = false,
    @SerialName("plan_name") val planName: String? = null,
    @SerialName("plan_slug") val planSlug: String? = null,
    @SerialName("starts_at") val startsAt: String? = null,
    @SerialName("expires_at") val expiresAt: String? = null,
    val provider: String? = null,
)

@Serializable
data class SubscriptionResponse(val data: SubscriptionDto)

@Serializable
data class MeDto(
    val id: Int,
    val name: String? = null,
    val email: String? = null,
    val about: String? = null,
    @SerialName("is_admin") val isAdmin: Boolean = false,
    @SerialName("setup_completed") val setupCompleted: Boolean = false,
    val subscription: SubscriptionDto? = null,
)

@Serializable
data class MeResponse(val data: MeDto)

@Serializable
data class UpdateMeRequest(
    val name: String? = null,
    val about: String? = null,
)

@Serializable
data class MessageResponse(val message: String? = null)

// --- Sellable plans & checkout (Midtrans Snap via external browser) ---

@Serializable
data class PlanDto(
    val id: Int,
    val slug: String,
    val name: String,
    val description: String? = null,
    @SerialName("duration_type") val durationType: String,
    @SerialName("duration_label") val durationLabel: String? = null,
    val price: Double = 0.0,
)

@Serializable
data class PlansResponse(val data: List<PlanDto> = emptyList())

@Serializable
data class ChargeRequest(@SerialName("plan_id") val planId: Int)

@Serializable
data class ChargeResponse(
    @SerialName("order_id") val orderId: String,
    val token: String,
    @SerialName("redirect_url") val redirectUrl: String,
    val price: Double = 0.0,
)
