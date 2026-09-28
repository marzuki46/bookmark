package com.keuangan.app.data

import retrofit2.http.Body
import retrofit2.http.DELETE
import retrofit2.http.GET
import retrofit2.http.PATCH
import retrofit2.http.POST
import retrofit2.http.PUT
import retrofit2.http.Path
import retrofit2.http.Query

interface KeuanganApi {

    @POST("api/login")
    suspend fun login(@Body body: LoginRequest): LoginResponse

    @POST("api/logout")
    suspend fun logout()

    @GET("api/finance/dashboard")
    suspend fun dashboard(@Query("period") period: String? = null): DashboardResponse

    @GET("api/finance/transactions")
    suspend fun transactions(
        @Query("period") period: String? = null,
        @Query("type") type: String? = null,
        @Query("category_id") categoryId: Int? = null,
        @Query("search") search: String? = null,
    ): TransactionListResponse

    @POST("api/finance/transactions")
    suspend fun createTransaction(@Body body: TransactionRequest): TransactionResponse

    @PUT("api/finance/transactions/{id}")
    suspend fun updateTransaction(
        @Path("id") id: Int,
        @Body body: TransactionRequest,
    ): TransactionResponse

    @DELETE("api/finance/transactions/{id}")
    suspend fun deleteTransaction(@Path("id") id: Int)

    @GET("api/finance/categories")
    suspend fun categories(@Query("type") type: String? = null): CategoryListResponse

    @POST("api/finance/categories")
    suspend fun createCategory(@Body body: CategoryRequest): CategoryResponse

    @PUT("api/finance/categories/{id}")
    suspend fun updateCategory(@Path("id") id: Int, @Body body: CategoryRequest): CategoryResponse

    @DELETE("api/finance/categories/{id}")
    suspend fun deleteCategory(@Path("id") id: Int)

    @POST("api/finance/ai/advice")
    suspend fun advice(@Body body: AdviceRequest): AdviceResponse

    @POST("api/finance/ai/ask")
    suspend fun ask(@Body body: AskRequest): AskResponse

    // --- App login by permanent code ---

    @POST("api/app/login")
    suspend fun appLogin(@Body body: AppLoginRequest): AppLoginResponse

    @POST("api/app/login-code/rotate")
    suspend fun rotateCode(): RotateCodeResponse

    @POST("api/app/devices")
    suspend fun registerDevice(@Body body: DeviceRequest): DeviceResponse

    @GET("api/app/devices")
    suspend fun devices(): DeviceListResponse

    // --- Family ---

    @GET("api/families")
    suspend fun families(): FamilyListResponse

    @GET("api/families/{family}")
    suspend fun family(@Path("family") familyId: Int): FamilyResponse

    @PATCH("api/families/{family}/me")
    suspend fun updatePayerRole(
        @Path("family") familyId: Int,
        @Body body: PayerRoleRequest,
    ): PayerRoleResponse

    @GET("api/families/{family}/summary")
    suspend fun familySummary(@Path("family") familyId: Int): FamilySummaryResponse

    // --- Household: categories ---

    @GET("api/families/{family}/categories")
    suspend fun familyCategories(
        @Path("family") familyId: Int,
        @Query("type") type: String? = null,
    ): FamilyCategoryListResponse

    @POST("api/families/{family}/categories")
    suspend fun createFamilyCategory(
        @Path("family") familyId: Int,
        @Body body: FamilyCategoryRequest,
    ): FamilyCategoryResponse

    @PUT("api/families/{family}/categories/{category}")
    suspend fun updateFamilyCategory(
        @Path("family") familyId: Int,
        @Path("category") categoryId: Int,
        @Body body: FamilyCategoryRequest,
    ): FamilyCategoryResponse

    // --- Household: transactions ---

    @GET("api/families/{family}/transactions")
    suspend fun familyTransactions(
        @Path("family") familyId: Int,
        @Query("type") type: String? = null,
        @Query("payer") payer: String? = null,
        @Query("category_id") categoryId: Int? = null,
        @Query("income_source_id") incomeSourceId: Int? = null,
        @Query("from") from: String? = null,
        @Query("to") to: String? = null,
        @Query("q") query: String? = null,
        @Query("per_page") perPage: Int? = null,
    ): FamilyTransactionListResponse

    @POST("api/families/{family}/transactions")
    suspend fun createFamilyTransaction(
        @Path("family") familyId: Int,
        @Body body: FamilyTransactionRequest,
    ): FamilyTransactionSaveResponse

    @PUT("api/families/{family}/transactions/{transaction}")
    suspend fun updateFamilyTransaction(
        @Path("family") familyId: Int,
        @Path("transaction") transactionId: Int,
        @Body body: FamilyTransactionRequest,
    ): FamilyTransactionSaveResponse

    @DELETE("api/families/{family}/transactions/{transaction}")
    suspend fun deleteFamilyTransaction(
        @Path("family") familyId: Int,
        @Path("transaction") transactionId: Int,
    )

    // --- Household: income sources ---

    @GET("api/families/{family}/income-sources")
    suspend fun incomeSources(@Path("family") familyId: Int): IncomeSourceListResponse

    @POST("api/families/{family}/income-sources")
    suspend fun createIncomeSource(
        @Path("family") familyId: Int,
        @Body body: IncomeSourceRequest,
    ): IncomeSourceResponse

    @PUT("api/families/{family}/income-sources/{incomeSource}")
    suspend fun updateIncomeSource(
        @Path("family") familyId: Int,
        @Path("incomeSource") sourceId: Int,
        @Body body: IncomeSourceRequest,
    ): IncomeSourceResponse

    @DELETE("api/families/{family}/income-sources/{incomeSource}")
    suspend fun deleteIncomeSource(
        @Path("family") familyId: Int,
        @Path("incomeSource") sourceId: Int,
    )

    // --- Household: debts ---

    @GET("api/families/{family}/debts")
    suspend fun debts(
        @Path("family") familyId: Int,
        @Query("status") status: String? = null,
        @Query("type") type: String? = null,
    ): FamilyDebtListResponse

    @POST("api/families/{family}/debts")
    suspend fun createDebt(
        @Path("family") familyId: Int,
        @Body body: FamilyDebtRequest,
    ): FamilyDebtResponse

    @PUT("api/families/{family}/debts/{debt}")
    suspend fun updateDebt(
        @Path("family") familyId: Int,
        @Path("debt") debtId: Int,
        @Body body: FamilyDebtRequest,
    ): FamilyDebtResponse

    @DELETE("api/families/{family}/debts/{debt}")
    suspend fun deleteDebt(
        @Path("family") familyId: Int,
        @Path("debt") debtId: Int,
    )

    @POST("api/families/{family}/debts/{debt}/pay")
    suspend fun payDebt(
        @Path("family") familyId: Int,
        @Path("debt") debtId: Int,
        @Body body: DebtPaymentRequest,
    ): DebtPaymentResponse

    // --- Household: budgets ---

    @GET("api/families/{family}/budgets")
    suspend fun budgets(
        @Path("family") familyId: Int,
        @Query("month") month: Int? = null,
        @Query("year") year: Int? = null,
    ): FamilyBudgetResponse

    @POST("api/families/{family}/budgets")
    suspend fun createBudget(
        @Path("family") familyId: Int,
        @Body body: FamilyBudgetRequest,
    ): FamilyBudgetResponse

    @PUT("api/families/{family}/budgets/{budget}")
    suspend fun updateBudget(
        @Path("family") familyId: Int,
        @Path("budget") budgetId: Int,
        @Body body: FamilyBudgetUpdateRequest,
    ): FamilyBudgetResponse

    @DELETE("api/families/{family}/budgets/{budget}")
    suspend fun deleteBudget(
        @Path("family") familyId: Int,
        @Path("budget") budgetId: Int,
    )

    // --- Household: goals ---

    @GET("api/families/{family}/goals")
    suspend fun goals(
        @Path("family") familyId: Int,
        @Query("status") status: String? = null,
    ): FamilyGoalListResponse

    @POST("api/families/{family}/goals")
    suspend fun createGoal(
        @Path("family") familyId: Int,
        @Body body: FamilyGoalRequest,
    ): FamilyGoalResponse

    @PUT("api/families/{family}/goals/{goal}")
    suspend fun updateGoal(
        @Path("family") familyId: Int,
        @Path("goal") goalId: Int,
        @Body body: FamilyGoalRequest,
    ): FamilyGoalResponse

    @DELETE("api/families/{family}/goals/{goal}")
    suspend fun deleteGoal(
        @Path("family") familyId: Int,
        @Path("goal") goalId: Int,
    )

    @POST("api/families/{family}/goals/{goal}/contribute")
    suspend fun contributeToGoal(
        @Path("family") familyId: Int,
        @Path("goal") goalId: Int,
        @Body body: GoalContributionRequest,
    ): FamilyGoalResponse

    // --- Insights ---

    @GET("api/families/{family}/insights")
    suspend fun insights(@Path("family") familyId: Int): FamilyInsightsResponse

    @GET("api/families/{family}/nudge")
    suspend fun nudge(@Path("family") familyId: Int): NudgeResponse

    @POST("api/families/{family}/insights/read")
    suspend fun markInsightRead(@Path("family") familyId: Int): InsightResponse
}
