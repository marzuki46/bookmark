package com.keuangan.app.data

import retrofit2.http.Body
import retrofit2.http.DELETE
import retrofit2.http.GET
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
}
