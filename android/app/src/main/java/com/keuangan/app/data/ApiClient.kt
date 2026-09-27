package com.keuangan.app.data

import com.jakewharton.retrofit2.converter.kotlinx.serialization.asConverterFactory
import com.keuangan.app.BuildConfig
import kotlinx.serialization.json.Json
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.Retrofit
import java.util.concurrent.TimeUnit

object ApiClient {

    val json: Json = Json {
        ignoreUnknownKeys = true
        explicitNulls = false
        coerceInputValues = true
    }

    @Volatile
    private var retrofit: Retrofit? = null

    fun create(tokenStore: TokenStore): KeuanganApi = retrofit(tokenStore)

    private fun retrofit(tokenStore: TokenStore): KeuanganApi {
        return retrofit?.create(KeuanganApi::class.java) ?: synchronized(this) {
            retrofit?.create(KeuanganApi::class.java) ?: build(tokenStore).also { retrofit = it }
                .create(KeuanganApi::class.java)
        }
    }

    private fun build(tokenStore: TokenStore): Retrofit {
        val logging = HttpLoggingInterceptor().apply {
            level = if (BuildConfig.DEBUG) {
                HttpLoggingInterceptor.Level.BASIC
            } else {
                HttpLoggingInterceptor.Level.NONE
            }
        }

        val client = OkHttpClient.Builder()
            .connectTimeout(20, TimeUnit.SECONDS)
            .readTimeout(30, TimeUnit.SECONDS)
            .addInterceptor { chain ->
                val request = chain.request().newBuilder().apply {
                    tokenStore.tokenBlocking()?.let { header("Authorization", "Bearer $it") }
                    header("Accept", "application/json")
                }.build()
                chain.proceed(request)
            }
            .addInterceptor(logging)
            .build()

        return Retrofit.Builder()
            .baseUrl(BuildConfig.API_BASE_URL.trimEnd('/') + "/")
            .client(client)
            .addConverterFactory(json.asConverterFactory("application/json".toMediaType()))
            .build()
    }
}
