package com.keuangan.app.data

import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow

class KeuanganRepository(
    private val api: KeuanganApi,
    private val tokenStore: TokenStore,
) {

    private val _authenticated = MutableStateFlow<Boolean?>(null)
    val authenticated: StateFlow<Boolean?> = _authenticated.asStateFlow()

    suspend fun restoreSession(): Boolean {
        val token = tokenStore.restore()
        val ok = !token.isNullOrBlank()
        _authenticated.value = ok
        return ok
    }

    suspend fun login(email: String, password: String): ApiResult<Unit> = runCatching {
        apiCall { api.login(LoginRequest(email.trim(), password)) }
    }.fold(
        onSuccess = { response ->
            tokenStore.save(response.token)
            _authenticated.value = true
            ApiResult.Ok(Unit)
        },
        onFailure = { e -> e.toApiError() },
    )

    suspend fun logout() {
        runCatching { apiCall { api.logout() } }
        tokenStore.clear()
        clearFamily()
        _authenticated.value = false
    }

    /**
     * Exchanges the permanent login code for a Sanctum token.
     *
     * This is the app's only sign-in path. The code is 16 characters in four
     * groups, so the UI can offer a paste field while still accepting manual
     * entry; the server normalises the dashes away either way.
     */
    suspend fun loginWithCode(code: String, deviceName: String = "android"): ApiResult<Unit> = runCatching {
        apiCall { api.appLogin(AppLoginRequest(code.trim().uppercase(), deviceName)) }
    }.fold(
        onSuccess = { response ->
            tokenStore.saveSession(response.token, response.user?.id)
            _authenticated.value = true
            ApiResult.Ok(Unit)
        },
        onFailure = { e -> e.toApiError() },
    )

    // --- profile & paid plans ---

    /** The caller's profile plus entitlement, for the personal menu. */
    suspend fun currentUser(): ApiResult<MeDto> = runCatching {
        apiCall { api.me() }.data
    }.fold(onSuccess = { ApiResult.Ok(it) }, onFailure = { e -> e.toApiError() })

    suspend fun updateProfile(name: String?, about: String?): ApiResult<Unit> = runCatching {
        apiCall { api.updateMe(UpdateMeRequest(name, about)) }
    }.fold(onSuccess = { ApiResult.Ok(Unit) }, onFailure = { e -> e.toApiError() })

    suspend fun currentSubscription(): ApiResult<SubscriptionDto> = runCatching {
        apiCall { api.currentSubscription() }.data
    }.fold(onSuccess = { ApiResult.Ok(it) }, onFailure = { e -> e.toApiError() })

    suspend fun subscriptionPlans(): ApiResult<List<PlanDto>> = runCatching {
        apiCall { api.plans() }.data
    }.fold(onSuccess = { ApiResult.Ok(it) }, onFailure = { e -> e.toApiError() })

    /**
     * Starts a Midtrans purchase. The returned redirect URL opens the Snap
     * payment page in the external browser; the webhook activates the plan.
     */
    suspend fun chargePlan(planId: Int): ApiResult<ChargeResponse> = runCatching {
        apiCall { api.charge(ChargeRequest(planId)) }
    }.fold(onSuccess = { ApiResult.Ok(it) }, onFailure = { e -> e.toApiError() })

    /** The signed-in user's id, restored with the token on cold start. */
    val currentUserId: Int?
        get() = tokenStore.userId

    /**
     * Rotates the login code, which also revokes every existing token.
     *
     * The response carries a fresh token so the caller stays signed in; it is
     * saved here rather than making the screen re-authenticate.
     */
    suspend fun rotateLoginCode(): ApiResult<RotateCodeResponse> = runCatching {
        val response = apiCall { api.rotateCode() }
        if (response.token.isNotBlank()) {
            tokenStore.save(response.token)
        }
        response
    }.fold(onSuccess = { ApiResult.Ok(it) }, onFailure = { e -> e.toApiError() })

    suspend fun dashboard(period: String): ApiResult<DashboardResponse> = runCatching {
        apiCall { api.dashboard(period) }
    }.fold(onSuccess = { ApiResult.Ok(it) }, onFailure = { e -> e.toApiError() })

    suspend fun transactions(
        period: String? = null,
        type: String? = null,
        categoryId: Int? = null,
        search: String? = null,
    ): ApiResult<TransactionListResponse> = runCatching {
        apiCall { api.transactions(period, type, categoryId, search) }
    }.fold(onSuccess = { ApiResult.Ok(it) }, onFailure = { e -> e.toApiError() })

    suspend fun createTransaction(body: TransactionRequest): ApiResult<TransactionDto> = runCatching {
        apiCall { api.createTransaction(body).data }
    }.fold(onSuccess = { ApiResult.Ok(it) }, onFailure = { e -> e.toApiError() })

    suspend fun updateTransaction(id: Int, body: TransactionRequest): ApiResult<TransactionDto> = runCatching {
        apiCall { api.updateTransaction(id, body).data }
    }.fold(onSuccess = { ApiResult.Ok(it) }, onFailure = { e -> e.toApiError() })

    suspend fun deleteTransaction(id: Int): ApiResult<Unit> = runCatching {
        apiCall { api.deleteTransaction(id) }
    }.fold(onSuccess = { ApiResult.Ok(Unit) }, onFailure = { e -> e.toApiError() })

    suspend fun categories(type: String? = null): ApiResult<List<CategoryDto>> = runCatching {
        apiCall { api.categories(type).data }
    }.fold(onSuccess = { ApiResult.Ok(it) }, onFailure = { e -> e.toApiError() })

    suspend fun createCategory(body: CategoryRequest): ApiResult<CategoryDto> = runCatching {
        apiCall { api.createCategory(body).data }
    }.fold(onSuccess = { ApiResult.Ok(it) }, onFailure = { e -> e.toApiError() })

    suspend fun updateCategory(id: Int, body: CategoryRequest): ApiResult<CategoryDto> = runCatching {
        apiCall { api.updateCategory(id, body).data }
    }.fold(onSuccess = { ApiResult.Ok(it) }, onFailure = { e -> e.toApiError() })

    suspend fun deleteCategory(id: Int): ApiResult<Unit> = runCatching {
        apiCall { api.deleteCategory(id) }
    }.fold(onSuccess = { ApiResult.Ok(Unit) }, onFailure = { e -> e.toApiError() })

    suspend fun advice(period: String): ApiResult<AdviceResponse> = runCatching {
        apiCall { api.advice(AdviceRequest(period)) }
    }.fold(onSuccess = { ApiResult.Ok(it) }, onFailure = { e -> e.toApiError() })

    suspend fun ask(question: String): ApiResult<AskResponse> = runCatching {
        apiCall { api.ask(AskRequest(question)) }
    }.fold(onSuccess = { ApiResult.Ok(it) }, onFailure = { e -> e.toApiError() })

    // ======================================================================
    // Household ("family")
    //
    // A user belongs to exactly one family, so the resolved family is cached
    // here instead of being passed around by every screen. Screens read
    // [familyId] and stay disabled until it resolves, which avoids every one
    // of them re-fetching the same list.
    // ======================================================================

    private val _family = MutableStateFlow<FamilyDto?>(null)
    val family: StateFlow<FamilyDto?> = _family.asStateFlow()

    val familyId: Int? get() = _family.value?.id

    /** Resolves the caller's household. Safe to call repeatedly. */
    suspend fun loadFamily(force: Boolean = false): ApiResult<FamilyDto?> {
        if (!force && _family.value != null) {
            return ApiResult.Ok(_family.value)
        }

        return runCatching {
            apiCall { api.families() }.data.firstOrNull().also { _family.value = it }
        }.fold(onSuccess = { ApiResult.Ok(it) }, onFailure = { e -> e.toApiError() })
    }

    fun clearFamily() {
        _family.value = null
    }

    suspend fun familyDetail(familyId: Int): ApiResult<FamilyDto> = runCatching {
        val dto = apiCall { api.family(familyId) }.data
        _family.value = dto
        dto
    }.fold(onSuccess = { ApiResult.Ok(it) }, onFailure = { e -> e.toApiError() })

    suspend fun setPayerRole(familyId: Int, payerRole: String): ApiResult<PayerRoleResponse> = runCatching {
        apiCall { api.updatePayerRole(familyId, PayerRoleRequest(payerRole)) }
    }.fold(onSuccess = { ApiResult.Ok(it) }, onFailure = { e -> e.toApiError() })

    suspend fun familyHealth(familyId: Int): ApiResult<FamilyHealthDto> = runCatching {
        apiCall { api.familySummary(familyId) }.data
    }.fold(onSuccess = { ApiResult.Ok(it) }, onFailure = { e -> e.toApiError() })

    // --- insights ---

    suspend fun insights(familyId: Int): ApiResult<FamilyInsightsResponse> = runCatching {
        apiCall { api.insights(familyId) }
    }.fold(onSuccess = { ApiResult.Ok(it) }, onFailure = { e -> e.toApiError() })

    suspend fun nudge(familyId: Int): ApiResult<NudgeDto?> = runCatching {
        apiCall { api.nudge(familyId) }.data
    }.fold(onSuccess = { ApiResult.Ok(it) }, onFailure = { e -> e.toApiError() })

    suspend fun markInsightRead(familyId: Int): ApiResult<InsightDto?> = runCatching {
        apiCall { api.markInsightRead(familyId) }.data
    }.fold(onSuccess = { ApiResult.Ok(it) }, onFailure = { e -> e.toApiError() })

    // --- categories ---

    suspend fun familyCategories(familyId: Int, type: String? = null): ApiResult<List<FamilyCategoryDto>> = runCatching {
        apiCall { api.familyCategories(familyId, type) }.data
    }.fold(onSuccess = { ApiResult.Ok(it) }, onFailure = { e -> e.toApiError() })

    suspend fun createFamilyCategory(familyId: Int, body: FamilyCategoryRequest): ApiResult<FamilyCategoryDto> = runCatching {
        apiCall { api.createFamilyCategory(familyId, body) }.data
    }.fold(onSuccess = { ApiResult.Ok(it) }, onFailure = { e -> e.toApiError() })

    suspend fun updateFamilyCategory(familyId: Int, categoryId: Int, body: FamilyCategoryRequest): ApiResult<FamilyCategoryDto> = runCatching {
        apiCall { api.updateFamilyCategory(familyId, categoryId, body) }.data
    }.fold(onSuccess = { ApiResult.Ok(it) }, onFailure = { e -> e.toApiError() })

    // --- transactions ---

    suspend fun familyTransactions(
        familyId: Int,
        type: String? = null,
        payer: String? = null,
        from: String? = null,
        to: String? = null,
        query: String? = null,
    ): ApiResult<FamilyTransactionListResponse> = runCatching {
        apiCall { api.familyTransactions(familyId, type, payer, null, null, from, to, query, 50) }
    }.fold(onSuccess = { ApiResult.Ok(it) }, onFailure = { e -> e.toApiError() })

    /**
     * Saves a transaction and hands back the instant nudge the server
     * evaluated, so the caller can show advice without another request.
     */
    suspend fun saveFamilyTransaction(
        familyId: Int,
        body: FamilyTransactionRequest,
        id: Int? = null,
    ): ApiResult<FamilyTransactionSaveResponse> = runCatching {
        if (id == null) {
            apiCall { api.createFamilyTransaction(familyId, body) }
        } else {
            apiCall { api.updateFamilyTransaction(familyId, id, body) }
        }
    }.fold(onSuccess = { ApiResult.Ok(it) }, onFailure = { e -> e.toApiError() })

    suspend fun deleteFamilyTransaction(familyId: Int, id: Int): ApiResult<Unit> = runCatching {
        apiCall { api.deleteFamilyTransaction(familyId, id) }
    }.fold(onSuccess = { ApiResult.Ok(Unit) }, onFailure = { e -> e.toApiError() })

    // --- income sources ---

    suspend fun incomeSources(familyId: Int): ApiResult<List<IncomeSourceDto>> = runCatching {
        apiCall { api.incomeSources(familyId) }.data
    }.fold(onSuccess = { ApiResult.Ok(it) }, onFailure = { e -> e.toApiError() })

    suspend fun saveIncomeSource(
        familyId: Int,
        body: IncomeSourceRequest,
        id: Int? = null,
    ): ApiResult<IncomeSourceDto> = runCatching {
        if (id == null) {
            apiCall { api.createIncomeSource(familyId, body) }
        } else {
            apiCall { api.updateIncomeSource(familyId, id, body) }
        }
    }.fold(onSuccess = { ApiResult.Ok(it.data) }, onFailure = { e -> e.toApiError() })

    suspend fun deleteIncomeSource(familyId: Int, id: Int): ApiResult<Unit> = runCatching {
        apiCall { api.deleteIncomeSource(familyId, id) }
    }.fold(onSuccess = { ApiResult.Ok(Unit) }, onFailure = { e -> e.toApiError() })

    // --- debts ---

    suspend fun debts(familyId: Int, status: String? = null): ApiResult<List<FamilyDebtDto>> = runCatching {
        apiCall { api.debts(familyId, status) }.data
    }.fold(onSuccess = { ApiResult.Ok(it) }, onFailure = { e -> e.toApiError() })

    suspend fun saveDebt(
        familyId: Int,
        body: FamilyDebtRequest,
        id: Int? = null,
    ): ApiResult<FamilyDebtDto> = runCatching {
        if (id == null) {
            apiCall { api.createDebt(familyId, body) }
        } else {
            apiCall { api.updateDebt(familyId, id, body) }
        }
    }.fold(onSuccess = { ApiResult.Ok(it.data) }, onFailure = { e -> e.toApiError() })

    suspend fun deleteDebt(familyId: Int, id: Int): ApiResult<Unit> = runCatching {
        apiCall { api.deleteDebt(familyId, id) }
    }.fold(onSuccess = { ApiResult.Ok(Unit) }, onFailure = { e -> e.toApiError() })

    suspend fun payDebt(familyId: Int, id: Int, amount: Double): ApiResult<DebtPaymentResponse> = runCatching {
        apiCall { api.payDebt(familyId, id, DebtPaymentRequest(amount)) }
    }.fold(onSuccess = { ApiResult.Ok(it) }, onFailure = { e -> e.toApiError() })

    // --- budgets ---

    suspend fun budgets(familyId: Int, month: Int, year: Int): ApiResult<FamilyBudgetMonthDto> = runCatching {
        apiCall { api.budgets(familyId, month, year) }.data
    }.fold(onSuccess = { ApiResult.Ok(it) }, onFailure = { e -> e.toApiError() })

    suspend fun saveBudget(familyId: Int, body: FamilyBudgetRequest, id: Int? = null): ApiResult<Unit> = runCatching {
        if (id == null) {
            apiCall { api.createBudget(familyId, body) }
        } else {
            apiCall { api.updateBudget(familyId, id, FamilyBudgetUpdateRequest(body.amount)) }
        }
    }.fold(onSuccess = { ApiResult.Ok(Unit) }, onFailure = { e -> e.toApiError() })

    suspend fun deleteBudget(familyId: Int, id: Int): ApiResult<Unit> = runCatching {
        apiCall { api.deleteBudget(familyId, id) }
    }.fold(onSuccess = { ApiResult.Ok(Unit) }, onFailure = { e -> e.toApiError() })

    // --- goals ---

    suspend fun goals(familyId: Int, status: String? = null): ApiResult<List<FamilyGoalDto>> = runCatching {
        apiCall { api.goals(familyId, status) }.data
    }.fold(onSuccess = { ApiResult.Ok(it) }, onFailure = { e -> e.toApiError() })

    suspend fun saveGoal(familyId: Int, body: FamilyGoalRequest, id: Int? = null): ApiResult<FamilyGoalDto> = runCatching {
        if (id == null) {
            apiCall { api.createGoal(familyId, body) }
        } else {
            apiCall { api.updateGoal(familyId, id, body) }
        }
    }.fold(onSuccess = { ApiResult.Ok(it.data) }, onFailure = { e -> e.toApiError() })

    suspend fun deleteGoal(familyId: Int, id: Int): ApiResult<Unit> = runCatching {
        apiCall { api.deleteGoal(familyId, id) }
    }.fold(onSuccess = { ApiResult.Ok(Unit) }, onFailure = { e -> e.toApiError() })

    suspend fun contributeToGoal(familyId: Int, id: Int, amount: Double): ApiResult<FamilyGoalDto> = runCatching {
        apiCall { api.contributeToGoal(familyId, id, GoalContributionRequest(amount)) }.data
    }.fold(onSuccess = { ApiResult.Ok(it) }, onFailure = { e -> e.toApiError() })

    // --- devices ---

    /**
     * Registers this device's push token.
     *
     * Called on login. A failure is reported rather than thrown because a
     * missing push token must never block someone from opening the app: the
     * dashboard falls back to the persisted insight it already fetched.
     */
    suspend fun registerDevice(fcmToken: String, appVersion: String?): ApiResult<DeviceResponse> = runCatching {
        apiCall { api.registerDevice(DeviceRequest(fcmToken, "android", appVersion)) }
    }.fold(onSuccess = { ApiResult.Ok(it) }, onFailure = { e -> e.toApiError() })
}
