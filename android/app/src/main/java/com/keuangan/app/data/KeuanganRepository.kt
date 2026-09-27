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
        _authenticated.value = false
    }

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
}
