package com.keuangan.app.data

import com.keuangan.app.BuildConfig
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow

class KeuanganRepository(
    private val api: KeuanganApi,
    private val tokenStore: TokenStore,
    private val offline: OfflineTxStore,
    private val kangCuan: KangCuanStore,
    private val familyCache: FamilyCacheStore,
) {

    /** Kang Cuan local state (messages, schedule, template cache) for the UI. */
    val kangCuanStore: KangCuanStore get() = kangCuan

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
            tokenStore.saveSession(response.token, response.user?.id, response.user?.name)
            clearFamily()
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
            tokenStore.saveSession(response.token, response.user?.id, response.user?.name)
            clearFamily()
            _authenticated.value = true
            ApiResult.Ok(Unit)
        },
        onFailure = { e -> e.toApiError() },
    )

    // --- profile & paid plans ---

    /** The caller's profile plus entitlement, for the personal menu. */
    suspend fun currentUser(): ApiResult<MeDto> = runCatching {
        apiCall { api.me() }.data
    }.fold(
        onSuccess = { me ->
            val shortName = me.name?.substringBefore(' ')?.takeIf { it.isNotBlank() }
            kangCuan.saveUserName(shortName)
            // Durable copy: the dashboard greets the member from this, and it has
            // to survive a cold start that never opens the profile screen.
            if (shortName != null && tokenStore.userName != shortName) {
                tokenStore.saveSession(tokenStore.token.orEmpty(), currentUserId, shortName)
            }
            ApiResult.Ok(me)
        },
        onFailure = { e -> e.toApiError() },
    )

    suspend fun updateProfile(name: String?, about: String?, religion: String? = null): ApiResult<Unit> = runCatching {
        apiCall { api.updateMe(UpdateMeRequest(name, about, religion)) }
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

    /** The signed-in user's display name, cached from the login response. */
    val currentUserName: String?
        get() = tokenStore.userName

    /**
     * Kang Cuan message templates. The server is the source of truth; the cached
     * copy keeps the daily alarm working offline. Returns whatever is usable:
     * fresh templates when online, the last synced copy otherwise.
     */
    suspend fun affirmations(): ApiResult<List<AffirmationDto>> = runCatching {
        apiCall { api.affirmations() }.data
    }.fold(
        onSuccess = { list ->
            kangCuan.storeTemplatesIfNewer(list)
            ApiResult.Ok(list)
        },
        onFailure = { e ->
            val cached = kangCuan.readTemplates()
            if (cached.isNotEmpty()) ApiResult.Ok(cached) else e.toApiError()
        },
    )

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

    suspend fun pricingAdvice(request: PricingRequest): ApiResult<PricingResponse> = runCatching {
        apiCall { api.pricing(request) }
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

    val cachedFamilyId: Int? get() = tokenStore.familyId

    /** Resolves the caller's household. Safe to call repeatedly. */
    suspend fun loadFamily(force: Boolean = false): ApiResult<FamilyDto?> {
        if (!force && _family.value != null) {
            return ApiResult.Ok(_family.value)
        }

        return runCatching {
            apiCall { api.families() }.data.firstOrNull().also {
                _family.value = it
                tokenStore.saveFamilyId(it?.id)
            }
        }.fold(onSuccess = { ApiResult.Ok(it) }, onFailure = { e -> e.toApiError() })
    }

    private suspend fun clearFamily() {
        _family.value = null
        tokenStore.saveFamilyId(null)
        runCatching { familyCache.clearAll() }
    }

    suspend fun familyDetail(familyId: Int): ApiResult<FamilyDto> {
        val remote = runCatching {
            val dto = apiCall { api.family(familyId) }.data
            _family.value = dto
            tokenStore.saveFamilyId(dto.id)
            dto
        }
        if (remote.isSuccess) {
            val dto = remote.getOrThrow()
            runCatching { familyCache.write(familyId) { it.copy(detail = dto) } }
            return ApiResult.Ok(dto)
        }
        val cached = runCatching { familyCache.read(familyId).detail }.getOrDefault(null)
        return if (cached != null) ApiResult.Ok(cached) else remote.exceptionOrNull()!!.toApiError()
    }

    suspend fun setPayerRole(familyId: Int, payerRole: String): ApiResult<PayerRoleResponse> = runCatching {
        apiCall { api.updatePayerRole(familyId, PayerRoleRequest(payerRole)) }
    }.fold(onSuccess = { ApiResult.Ok(it) }, onFailure = { e -> e.toApiError() })

    suspend fun familyHealth(familyId: Int): ApiResult<FamilyHealthDto> {
        val remote = runCatching { apiCall { api.familySummary(familyId) }.data }
        if (remote.isSuccess) {
            val dto = remote.getOrThrow()
            runCatching { familyCache.write(familyId) { it.copy(health = dto) } }
            return ApiResult.Ok(dto)
        }
        val cached = runCatching { familyCache.read(familyId).health }.getOrDefault(null)
        return if (cached != null) ApiResult.Ok(cached) else remote.exceptionOrNull()!!.toApiError()
    }

    /**
     * Today / this week / this month vs. the matching previous windows. The
     * server owns the arithmetic and visibility masking, so this stays a thin
     * pass-through — it is cheap enough that caching would only risk showing
     * yesterday's "hari ini".
     */
    suspend fun familyForecast(familyId: Int): ApiResult<FamilyForecastResponse> =
        runCatching { apiCall { api.familyForecast(familyId) } }
            .fold(onSuccess = { ApiResult.Ok(it) }, onFailure = { e -> e.toApiError() })

    // --- reminders & trend ---

    suspend fun familyReminders(familyId: Int): ApiResult<List<ReminderDto>> {
        val remote = runCatching { apiCall { api.familyReminders(familyId) }.data }
        if (remote.isSuccess) {
            val list = remote.getOrThrow()
            runCatching { familyCache.write(familyId) { it.copy(reminders = list) } }
            return ApiResult.Ok(list)
        }
        val cached = runCatching { familyCache.read(familyId).reminders }.getOrDefault(emptyList())
        return if (cached.isNotEmpty()) ApiResult.Ok(cached) else remote.exceptionOrNull()!!.toApiError()
    }

    suspend fun familyTrend(familyId: Int, months: Int = 6): ApiResult<List<TrendPointDto>> {
        val remote = runCatching { apiCall { api.familyTrend(familyId, months) }.data }
        if (remote.isSuccess) {
            val list = remote.getOrThrow()
            runCatching { familyCache.write(familyId) { it.copy(trend = list) } }
            return ApiResult.Ok(list)
        }
        val cached = runCatching { familyCache.read(familyId).trend }.getOrDefault(emptyList())
        return if (cached.isNotEmpty()) ApiResult.Ok(cached) else remote.exceptionOrNull()!!.toApiError()
    }

    // --- insights ---

    suspend fun insights(familyId: Int): ApiResult<FamilyInsightsResponse> = runCatching {
        apiCall { api.insights(familyId) }.data
    }.fold(onSuccess = { ApiResult.Ok(it) }, onFailure = { e -> e.toApiError() })

    suspend fun nudge(familyId: Int): ApiResult<NudgeDto?> = runCatching {
        apiCall { api.nudge(familyId) }.data
    }.fold(onSuccess = { ApiResult.Ok(it) }, onFailure = { e -> e.toApiError() })

    suspend fun markInsightRead(familyId: Int): ApiResult<InsightDto?> = runCatching {
        apiCall { api.markInsightRead(familyId) }.data
    }.fold(onSuccess = { ApiResult.Ok(it) }, onFailure = { e -> e.toApiError() })

    // --- categories ---

    suspend fun familyCategories(familyId: Int, type: String? = null): ApiResult<List<FamilyCategoryDto>> {
        val remote = runCatching { apiCall { api.familyCategories(familyId, type) }.data }
        if (remote.isSuccess) {
            val list = remote.getOrThrow()
            runCatching {
                familyCache.write(familyId) { cache ->
                    val merged = (cache.categories + list)
                        .distinctBy { it.id }
                        .sortedWith(compareBy<FamilyCategoryDto> { it.name }.thenBy { it.id })
                    cache.copy(categories = merged)
                }
            }
            return ApiResult.Ok(list)
        }
        val cached = runCatching { familyCache.read(familyId).categories }.getOrDefault(emptyList())
        if (cached.isEmpty()) return remote.exceptionOrNull()!!.toApiError()
        val filtered = cached.filter { type == null || it.type == type }
        return if (filtered.isNotEmpty() || type == null) {
            ApiResult.Ok(filtered)
        } else {
            remote.exceptionOrNull()!!.toApiError()
        }
    }

    suspend fun createFamilyCategory(familyId: Int, body: FamilyCategoryRequest): ApiResult<FamilyCategoryDto> = runCatching {
        apiCall { api.createFamilyCategory(familyId, body) }.data
    }.fold(onSuccess = { ApiResult.Ok(it) }, onFailure = { e -> e.toApiError() })

    suspend fun updateFamilyCategory(familyId: Int, categoryId: Int, body: FamilyCategoryRequest): ApiResult<FamilyCategoryDto> = runCatching {
        apiCall { api.updateFamilyCategory(familyId, categoryId, body) }.data
    }.fold(onSuccess = { ApiResult.Ok(it) }, onFailure = { e -> e.toApiError() })

    suspend fun deleteFamilyCategory(familyId: Int, categoryId: Int): ApiResult<Unit> = runCatching {
        apiCall { api.deleteFamilyCategory(familyId, categoryId) }
    }.fold(onSuccess = { ApiResult.Ok(Unit) }, onFailure = { e -> e.toApiError() })

    // --- transactions ---

    /**
     * Loads the household transactions.
     *
     * When the server cannot be reached the last synced list is served from the
     * local cache instead, locally filtered the way the server would have done,
     * so the screen always has something honest to show. [OfflineLoad.offline]
     * tells the UI it is looking at cached data.
     */
    suspend fun familyTransactions(
        familyId: Int,
        type: String? = null,
        payer: String? = null,
        from: String? = null,
        to: String? = null,
        query: String? = null,
    ): OfflineLoad {
        val remote = runCatching {
            api.familyTransactions(familyId, type, payer, null, null, from, to, query, 50)
        }.fold(
            onSuccess = { response ->
                offline.writeCache(familyId, response.data)
                OfflineLoad(ApiResult.Ok(response), offline = false)
            },
            onFailure = { e -> OfflineLoad(e.toApiError(), offline = true) },
        )
        if (!remote.result.isOffline()) return remote

        val cached = offline.readCache(familyId).items
        if (cached.isEmpty()) return remote

        val filtered = cached.filter { tx ->
            (type == null || tx.type == type) &&
                (payer == null || tx.payer == payer) &&
                (from == null || tx.date >= from) &&
                (to == null || tx.date <= to) &&
                (query.isNullOrBlank() || (tx.description?.contains(query, ignoreCase = true) == true))
        }
        return OfflineLoad(ApiResult.Ok(FamilyTransactionListResponse(data = filtered)), offline = true)
    }

    /**
     * Saves a transaction and hands back the instant nudge the server
     * evaluated, so the caller can show advice without another request.
     *
     * While offline the write is queued to the outbox and applied to the local
     * cache straight away, returning a pending placeholder transaction; the same
     * shape is used so the caller needs no special-casing.
     */
    suspend fun saveFamilyTransaction(
        familyId: Int,
        body: FamilyTransactionRequest,
        id: Int? = null,
    ): ApiResult<FamilyTransactionSaveResponse> {
        if (id != null && id < 0) return editPendingCreate(familyId, id, body)

        val remote = runCatching {
            if (id == null) {
                api.createFamilyTransaction(familyId, body)
            } else {
                api.updateFamilyTransaction(familyId, id, body)
            }
        }.fold(
            onSuccess = { ApiResult.Ok(it) },
            onFailure = { e -> e.toApiError() },
        )

        return when {
            remote is ApiResult.Ok -> {
                remote.value.data?.let { upsertCache(familyId, it) }
                remote
            }
            remote.isOffline() -> queueOfflineWrite(familyId, body, id)
            else -> remote
        }
    }

    private suspend fun queueOfflineWrite(
        familyId: Int,
        body: FamilyTransactionRequest,
        id: Int?,
    ): ApiResult<FamilyTransactionSaveResponse> {
        val localId = if (id == null) -offline.readOutbox().nextLocalId else id
        updateOutbox { box ->
            box.copy(
                nextLocalId = if (id == null) box.nextLocalId + 1 else box.nextLocalId,
                ops = box.ops + PendingTxOp(
                    opId = System.currentTimeMillis() + if (id == null) 0 else localId,
                    familyId = familyId,
                    op = if (id == null) OP_CREATE else OP_UPDATE,
                    id = id,
                    localId = localId,
                    body = body,
                ),
            )
        }

        val pendingItem = pendingDto(localId, familyId, body)
        val cache = offline.readCache(familyId)
        val items = if (id == null) {
            listOf(pendingItem) + cache.items
        } else {
            cache.items.map { if (it.id == id) pendingItem else it }
        }
        offline.writeCache(familyId, items)
        return ApiResult.Ok(FamilyTransactionSaveResponse(data = pendingItem))
    }

    private suspend fun editPendingCreate(
        familyId: Int,
        localId: Int,
        body: FamilyTransactionRequest,
    ): ApiResult<FamilyTransactionSaveResponse> {
        updateOutbox { box ->
            box.copy(
                ops = box.ops.map { op ->
                    if (op.op == OP_CREATE && op.localId == localId) op.copy(body = body) else op
                },
            )
        }

        val pendingItem = pendingDto(localId, familyId, body)
        val cache = offline.readCache(familyId)
        offline.writeCache(familyId, cache.items.map { if (it.id == localId) pendingItem else it })
        return ApiResult.Ok(FamilyTransactionSaveResponse(data = pendingItem))
    }

    private suspend fun updateOutbox(transform: (PendingOutbox) -> PendingOutbox) {
        offline.saveOutbox(transform(offline.readOutbox()))
    }

    private fun pendingDto(id: Int, familyId: Int, body: FamilyTransactionRequest): FamilyTransactionDto =
        FamilyTransactionDto(
            id = id,
            familyId = familyId,
            type = body.type,
            amount = body.amount,
            description = body.description,
            date = body.date,
            payer = body.payer,
            incomeSource = body.incomeSourceId?.let { IncomeSourceDto(id = it, name = "") },
        )

    private suspend fun upsertCache(familyId: Int, tx: FamilyTransactionDto) {
        val cache = offline.readCache(familyId)
        val items = if (cache.items.any { it.id == tx.id }) {
            cache.items.map { if (it.id == tx.id) tx else it }
        } else {
            listOf(tx) + cache.items
        }
        offline.writeCache(familyId, items)
    }

    suspend fun deleteFamilyTransaction(familyId: Int, id: Int): ApiResult<Unit> {
        if (id < 0) {
            updateOutbox { box ->
                box.copy(ops = box.ops.filterNot { it.op == OP_CREATE && it.localId == id })
            }
            val cache = offline.readCache(familyId)
            offline.writeCache(familyId, cache.items.filterNot { it.id == id })
            return ApiResult.Ok(Unit)
        }

        val remote = runCatching { api.deleteFamilyTransaction(familyId, id) }.fold(
            onSuccess = { ApiResult.Ok(Unit) },
            onFailure = { e -> e.toApiError() },
        )
        when {
            remote is ApiResult.Ok -> {
                val cache = offline.readCache(familyId)
                offline.writeCache(familyId, cache.items.filterNot { it.id == id })
                return remote
            }
            remote.isOffline() -> {
                updateOutbox { box ->
                    box.copy(
                        ops = box.ops + PendingTxOp(
                            opId = System.currentTimeMillis(),
                            familyId = familyId,
                            op = OP_DELETE,
                            id = id,
                        ),
                    )
                }
                val cache = offline.readCache(familyId)
                offline.writeCache(familyId, cache.items.filterNot { it.id == id })
                return ApiResult.Ok(Unit)
            }
            else -> return remote
        }
    }

    /** Count of offline writes still waiting for this family. */
    suspend fun pendingCount(familyId: Int): Int = offline.readOutbox().ops.count { it.familyId == familyId }

    /** Message of the oldest failed replay, if any, so the UI can explain why. */
    suspend fun failedPendingMessage(familyId: Int): String? =
        offline.readOutbox().ops.firstOrNull { it.familyId == familyId && it.failedMessage != null }?.failedMessage

    /**
     * Replays every queued offline write in the order the user made them.
     * Keeps a conflicting write (e.g. a server-side validation) in the outbox
     * with its error message rather than silently dropping the user's data.
     */
    suspend fun syncPendingTransactions(): Int {
        val outbox = offline.readOutbox()
        if (outbox.ops.isEmpty()) return 0

        var remaining = outbox.ops.size
        var result = outbox.copy()
        for (op in outbox.ops) {
            val outcome = when (op.op) {
                OP_CREATE -> op.body?.let { body ->
                    runCatching { api.createFamilyTransaction(op.familyId, body) }.fold(
                        onSuccess = {
                            it.data?.let { serverTx -> pendingCreated(op, serverTx) }
                            ReplayDone(null)
                        },
                        onFailure = { e -> ReplayDone(e.toApiError().message) },
                    )
                } ?: ReplayDone(null)

                OP_UPDATE -> op.body?.let { body -> op.id?.let { id ->
                    runCatching { api.updateFamilyTransaction(op.familyId, id, body) }.fold(
                        onSuccess = {
                            it.data?.let { serverTx -> upsertCache(op.familyId, serverTx) }
                            ReplayDone(null)
                        },
                        onFailure = { e -> ReplayDone(e.toApiError().message) },
                    )
                } } ?: ReplayDone(null)

                OP_DELETE -> op.id?.let { id ->
                    runCatching { api.deleteFamilyTransaction(op.familyId, id) }.fold(
                        onSuccess = {
                            val cache = offline.readCache(op.familyId)
                            offline.writeCache(op.familyId, cache.items.filterNot { it.id == id })
                            ReplayDone(null)
                        },
                        onFailure = { e -> ReplayDone(e.toApiError().message) },
                    )
                } ?: ReplayDone(null)

                else -> ReplayDone(null)
            }

            if (outcome.failedMessage == null) {
                remaining -= 1
                result = result.copy(ops = result.ops.filterNot { it.opId == op.opId })
            } else {
                result = result.copy(ops = result.ops.map { if (it.opId == op.opId) it.copy(failedMessage = outcome.failedMessage) else it })
            }
        }
        offline.saveOutbox(result)
        return remaining
    }

    /** Confirms a queued create: replaces the placeholder with the real entry. */
    private suspend fun pendingCreated(op: PendingTxOp, serverTx: FamilyTransactionDto?) {
        if (serverTx == null) return
        val cache = offline.readCache(op.familyId)
        val items = if (op.localId != null && cache.items.any { it.id == op.localId }) {
            cache.items.map { if (it.id == op.localId) serverTx else it }
        } else {
            listOf(serverTx) + cache.items
        }
        offline.writeCache(op.familyId, items)
    }

    private data class ReplayDone(val failedMessage: String?)

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

    suspend fun debts(familyId: Int, status: String? = null, type: String? = null): ApiResult<List<FamilyDebtDto>> {
        val remote = runCatching { apiCall { api.debts(familyId, status, type) }.data }
        if (remote.isSuccess) {
            val list = remote.getOrThrow()
            runCatching {
                familyCache.write(familyId) { it.copy(debts = list) }
            }
            return ApiResult.Ok(list)
        }
        val cached = runCatching { familyCache.read(familyId).debts }.getOrDefault(emptyList())
        if (cached.isEmpty()) return remote.exceptionOrNull()!!.toApiError()
        val filtered = cached.filter {
            (status == null || it.status == status) && (type == null || it.type == type)
        }
        return if (filtered.isNotEmpty() || (status == null && type == null)) {
            ApiResult.Ok(filtered)
        } else {
            remote.exceptionOrNull()!!.toApiError()
        }
    }

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

    suspend fun goals(familyId: Int, status: String? = null): ApiResult<List<FamilyGoalDto>> {
        val remote = runCatching { apiCall { api.goals(familyId, status) }.data }
        if (remote.isSuccess) {
            val list = remote.getOrThrow()
            runCatching { familyCache.write(familyId) { it.copy(goals = list) } }
            return ApiResult.Ok(list)
        }
        val cached = runCatching { familyCache.read(familyId).goals }.getOrDefault(emptyList())
        if (cached.isEmpty()) return remote.exceptionOrNull()!!.toApiError()
        val filtered = cached.filter { status == null || it.status == status }
        return if (filtered.isNotEmpty() || status == null) {
            ApiResult.Ok(filtered)
        } else {
            remote.exceptionOrNull()!!.toApiError()
        }
    }

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

    // --- app ops: updates & crash reporting ---

    suspend fun appUpdates(): ApiResult<AppUpdateDto> = runCatching {
        apiCall { api.appUpdates(BuildConfig.VERSION_CODE) }.data
    }.fold(onSuccess = { ApiResult.Ok(it) }, onFailure = { e -> e.toApiError() })

    /**
     * Best-effort crash reporter. Never throws: a failed report must not mask
     * the original crash or block the caller.
     */
    suspend fun reportCrash(errorClass: String?, message: String?, stackTrace: String?): ApiResult<Unit> = runCatching {
        apiCall {
            api.reportError(
                AppErrorRequest(
                    errorClass = errorClass,
                    message = message?.take(2000),
                    stackTrace = stackTrace?.take(20_000),
                    appVersion = BuildConfig.VERSION_NAME,
                ),
            )
        }
    }.fold(onSuccess = { ApiResult.Ok(Unit) }, onFailure = { e -> e.toApiError() })

    // --- family: login code & spouse members ---

    suspend fun familyLoginCode(familyId: Int): ApiResult<LoginCodeData> = runCatching {
        apiCall { api.familyLoginCode(familyId) }.data
    }.fold(onSuccess = { ApiResult.Ok(it) }, onFailure = { e -> e.toApiError() })

    suspend fun addFamilyMember(familyId: Int, name: String, payerRole: String?): ApiResult<NewFamilyMemberDto> = runCatching {
        apiCall { api.createFamilyMember(familyId, FamilyMemberRequest(name.trim(), payerRole)) }.data
    }.fold(onSuccess = { ApiResult.Ok(it) }, onFailure = { e -> e.toApiError() })

    suspend fun updateFamilyMember(
        familyId: Int,
        userId: Int,
        name: String,
        payerRole: String?,
        relationship: String,
        visibility: Map<String, Boolean>,
    ): ApiResult<NewFamilyMemberDto> = runCatching {
        apiCall {
            api.updateFamilyMember(
                familyId,
                userId,
                FamilyMemberRequest(
                    name = name.trim(),
                    payerRole = payerRole,
                    relationship = relationship,
                    visibility = visibility,
                ),
            )
        }.data
    }.fold(onSuccess = { ApiResult.Ok(it) }, onFailure = { e -> e.toApiError() })

    suspend fun deleteFamilyMember(familyId: Int, userId: Int): ApiResult<Unit> = runCatching {
        apiCall { api.deleteFamilyMember(familyId, userId) }
    }.fold(onSuccess = { ApiResult.Ok(Unit) }, onFailure = { e -> e.toApiError() })

    // --- Kang Cuan: family financial advisor ("Pendamping Keuangan") ---

    suspend fun advisorStatus(familyId: Int): ApiResult<AdvisorStatusDto> = runCatching {
        apiCall { api.advisorStatus(familyId).data }
    }.fold(onSuccess = { ApiResult.Ok(it) }, onFailure = { e -> e.toApiError() })

    suspend fun setAdvisorEnabled(familyId: Int, enabled: Boolean): ApiResult<AdvisorStatusDto> = runCatching {
        apiCall { api.setAdvisorEnabled(familyId, AdvisorToggleRequest(enabled)).data }
    }.fold(onSuccess = { ApiResult.Ok(it) }, onFailure = { e -> e.toApiError() })

    suspend fun saveAdvisorProfile(familyId: Int, body: AdvisorProfileRequest): ApiResult<AdvisorStatusDto> = runCatching {
        apiCall { api.saveAdvisorProfile(familyId, body).data }
    }.fold(onSuccess = { ApiResult.Ok(it) }, onFailure = { e -> e.toApiError() })
}

/** Result of a transaction load plus whether it came from the offline cache. */
data class OfflineLoad(
    val result: ApiResult<FamilyTransactionListResponse>,
    val offline: Boolean,
)
