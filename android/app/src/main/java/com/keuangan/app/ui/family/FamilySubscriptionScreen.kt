package com.keuangan.app.ui.family

import android.content.Intent
import android.net.Uri
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.WorkspacePremium
import androidx.compose.material.icons.filled.Refresh
import androidx.compose.material3.Button
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.keuangan.app.data.PlanDto
import com.keuangan.app.ui.components.GradientHeader
import com.keuangan.app.ui.formatFullDate
import com.keuangan.app.ui.formatRupiah
import com.keuangan.app.ui.theme.Amber100
import com.keuangan.app.ui.theme.Amber600
import com.keuangan.app.ui.theme.Teal100
import com.keuangan.app.ui.theme.Teal700
import java.time.Duration
import java.time.Instant

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun FamilySubscriptionScreen(
    viewModel: FamilySubscriptionViewModel,
    onBack: () -> Unit,
) {
    val state by viewModel.state.collectAsState()
    val redirect by viewModel.pendingRedirect.collectAsState()

    // Hand a paid redirect (or a generic checkout link) over to a browser.
    val context = LocalContext.current
    LaunchedEffect(redirect) {
        val url = redirect
        if (url != null) {
            viewModel.consumeRedirect()
            context.startActivity(Intent(Intent.ACTION_VIEW, Uri.parse(url)))
        }
    }

Scaffold(
        topBar = {
            GradientHeader(
                title = "Langganan",
                subtitle = "Status paket & pembayaran",
                trailing = {
                    IconButton(onClick = onBack) {
                        Icon(
                            Icons.AutoMirrored.Filled.ArrowBack,
                            contentDescription = "Kembali",
                            tint = Color.White,
                        )
                    }
                },
            )
        },
    ) { padding ->
        when {
            state.loading -> Box(Modifier.fillMaxSize().padding(padding), contentAlignment = Alignment.Center) {
                CircularProgressIndicator()
            }

            state.error != null && state.subscription == null && state.plans.isEmpty() -> Box(
                Modifier.fillMaxSize().padding(padding),
                contentAlignment = Alignment.Center,
            ) {
                Column(horizontalAlignment = Alignment.CenterHorizontally) {
                    Text(
                        state.error ?: "Terjadi kesalahan",
                        color = MaterialTheme.colorScheme.error,
                        style = MaterialTheme.typography.bodyMedium,
                    )
                    Spacer(Modifier.height(12.dp))
                    OutlinedButton(onClick = viewModel::refresh) { Text("Coba lagi") }
                }
            }

            else -> LazyColumn(
                modifier = Modifier.fillMaxSize().padding(padding),
                contentPadding = PaddingValues(16.dp, 16.dp, 16.dp, 32.dp),
                verticalArrangement = Arrangement.spacedBy(12.dp),
            ) {
                state.message?.let { message ->
                    item {
                        Row(verticalAlignment = Alignment.Top) {
                            Card(
                                modifier = Modifier.weight(1f),
                                colors = CardDefaults.cardColors(containerColor = Amber100),
                            ) {
                                Text(
                                    message,
                                    style = MaterialTheme.typography.bodyMedium,
                                    modifier = Modifier.padding(12.dp),
                                )
                            }
                            IconButton(onClick = viewModel::dismissMessage) {
                                Icon(
                                    Icons.Filled.Close,
                                    contentDescription = "Tutup",
                                    tint = Amber600,
                                    modifier = Modifier.size(18.dp),
                                )
                            }
                        }
                    }
                }

                state.error?.let { error ->
                    item {
                        Text(
                            error,
                            color = MaterialTheme.colorScheme.error,
                            style = MaterialTheme.typography.bodyMedium,
                        )
                    }
                }

                item {
                    StatusBanner(
                        active = state.subscription?.active == true,
                        planName = state.subscription?.planName,
                        expiresAt = state.subscription?.expiresAt,
                        onRefresh = viewModel::refresh,
                    )
                }

                if (state.plans.isEmpty()) {
                    item {
                        Text(
                            "Belum ada paket yang bisa dibeli.",
                            style = MaterialTheme.typography.bodyMedium,
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                        )
                    }
                } else {
                    item {
                        Text(
                            "Pilih paket",
                            style = MaterialTheme.typography.titleSmall,
                            fontWeight = FontWeight.SemiBold,
                        )
                    }
                    items(state.plans, key = { it.id }) { plan ->
                        PlanRow(
                            plan = plan,
                            charging = state.charging == plan.id,
                            enabled = state.charging == null,
                            onCharge = { viewModel.charge(plan.id) },
                        )
                    }
                    item {
                        Text(
                            "Pembayaran dibuka di browser melalui gateway yang aktif. Setelah selesai, tekan muat ulang untuk mengambil status dan masa aktif terbaru dari server.",
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                        )
                    }
                }
            }
        }
    }
}

@Composable
private fun StatusBanner(
    active: Boolean,
    planName: String?,
    expiresAt: String?,
    onRefresh: () -> Unit,
) {
    val (bg, fg) = if (active) Teal100 to Teal700 else Amber100 to Amber600
    val daysRemaining = expiresAt?.let { value ->
        runCatching {
            Duration.between(Instant.now(), Instant.parse(value)).toDays()
        }.getOrNull()
    }
    val expiringSoon = active && daysRemaining != null && daysRemaining in 0..3
    Card(colors = CardDefaults.cardColors(containerColor = bg), modifier = Modifier.fillMaxWidth()) {
        Row(Modifier.padding(14.dp), verticalAlignment = Alignment.CenterVertically) {
            Icon(
                Icons.Filled.WorkspacePremium,
                contentDescription = null,
                tint = fg,
                modifier = Modifier.size(22.dp),
            )
            Spacer(Modifier.size(10.dp))
            Column(Modifier.weight(1f)) {
                Text(
                    if (active) "Berlangganan aktif" else "Belum berlangganan",
                    style = MaterialTheme.typography.titleSmall,
                    fontWeight = FontWeight.SemiBold,
                    color = fg,
                )
                if (active) {
                    Text(
                        "${planName ?: "Paket aktif"}${expiresAt?.let { " · berlaku sampai ${formatFullDate(it)}" } ?: ""}",
                        style = MaterialTheme.typography.bodySmall,
                        color = fg,
                    )
                    daysRemaining?.let { days ->
                        Text(
                            when {
                                days < 0 -> "Lisensi sudah berakhir. Segera lakukan pembayaran."
                                days == 0L -> "Lisensi berakhir hari ini. Segera lakukan pembayaran."
                                else -> "Sisa masa lisensi: $days hari${if (expiringSoon) ". Segera lakukan pembayaran." else ""}"
                            },
                            style = MaterialTheme.typography.bodySmall,
                            fontWeight = if (expiringSoon || days < 0) FontWeight.Bold else FontWeight.Normal,
                            color = if (expiringSoon || days < 0) Amber600 else fg,
                        )
                    }
                }
            }
            IconButton(onClick = onRefresh) {
                Icon(
                    Icons.Filled.Refresh,
                    contentDescription = "Muat ulang",
                    tint = fg,
                    modifier = Modifier.size(20.dp),
                )
            }
        }
    }
}

@Composable
private fun PlanRow(
    plan: PlanDto,
    charging: Boolean,
    enabled: Boolean,
    onCharge: () -> Unit,
) {
    Card(
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
        modifier = Modifier.fillMaxWidth(),
    ) {
        Column(Modifier.padding(16.dp)) {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Column(Modifier.weight(1f)) {
                    Text(plan.name, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
                    plan.durationLabel?.let {
                        Text(it, style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                    }
                }
                Text(
                    formatRupiah(plan.price),
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.Bold,
                    color = Teal700,
                )
            }
            plan.description?.takeIf { it.isNotBlank() }?.let {
                Spacer(Modifier.height(6.dp))
                Text(it, style = MaterialTheme.typography.bodySmall)
            }
            Spacer(Modifier.height(12.dp))
            Button(
                onClick = onCharge,
                enabled = enabled && !charging,
                modifier = Modifier.fillMaxWidth(),
            ) {
                if (charging) {
                    CircularProgressIndicator(modifier = Modifier.size(16.dp), strokeWidth = 2.dp)
                } else {
                    Text("Bayar sekarang")
                }
            }
        }
    }
}
