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
import androidx.compose.foundation.lazy.rememberLazyListState
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.WarningAmber
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
import androidx.compose.material3.OutlinedTextField
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
import com.keuangan.app.data.ManualPaymentDto
import com.keuangan.app.ui.components.GradientHeader
import com.keuangan.app.ui.components.collapsingHeader
import com.keuangan.app.ui.components.rememberHeaderCollapse
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
    val listState = rememberLazyListState()
    val headerFraction by rememberHeaderCollapse(listState)
    val redirect by viewModel.pendingRedirect.collectAsState()

    LaunchedEffect(Unit) {
        viewModel.load()
    }

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
                modifier = Modifier.collapsingHeader { headerFraction },
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
                state = listState,
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
                        startsAt = state.subscription?.startsAt,
                        expiresAt = state.subscription?.expiresAt,
                        provider = state.subscription?.provider,
                        onRefresh = viewModel::refresh,
                    )
                }

                item {
                    PromoCodeCard(
                        code = state.promoCode,
                        redeeming = state.redeemingPromo,
                        onCodeChange = viewModel::updatePromoCode,
                        onRedeem = viewModel::redeemPromo,
                    )
                }

                val sub = state.subscription
                val lifetimeOwned = sub?.active == true && sub.expiresAt == null
                val daysLeft = sub?.expiresAt?.let { daysUntil(it) }
                val expiringSoon = sub?.active == true && daysLeft != null && daysLeft < 30

                if (lifetimeOwned) {
                    item {
                        MembershipCard(
                            plan = state.plans.firstOrNull { it.slug == sub.planSlug },
                            subscription = sub,
                        )
                    }
                }

                if (expiringSoon) {
                    item { RenewalNotice(daysLeft!!) }
                }

                // Lifetime holders have nothing left to buy; an expiring licence
                // should not push a monthly plan that would only renew for a
                // month, so those two cases get a narrowed plan list.
                val visiblePlans = when {
                    lifetimeOwned -> emptyList()
                    expiringSoon -> state.plans.filter { it.durationType == "yearly" || it.durationType == "lifetime" }
                    else -> state.plans
                }

                if (lifetimeOwned) {
                    item {
                        Text(
                            "Paket seumur hidup aktif. Semua fitur terbuka tanpa batas waktu — tidak ada yang perlu dibeli lagi.",
                            style = MaterialTheme.typography.bodyMedium,
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                        )
                    }
                } else if (visiblePlans.isEmpty()) {
                    item {
                        Text(
                            "Belum ada paket yang bisa dibeli.",
                            style = MaterialTheme.typography.bodyMedium,
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                        )
                    }
                } else {
                    if (sub?.active == true) {
                        item {
                            MembershipCard(
                                plan = state.plans.firstOrNull { it.slug == sub.planSlug },
                                subscription = sub,
                            )
                        }
                    }
                    if (expiringSoon) {
                        item {
                            Text(
                                "Masa aktif tinggal ${daysLeft} hari. Pilih paket tahunan atau seumur hidup agar tidak perlu perpanjangan tiap bulan.",
                                style = MaterialTheme.typography.bodyMedium,
                                color = Amber600,
                            )
                        }
                    }
                    item {
                        Text(
                            "Pilih paket",
                            style = MaterialTheme.typography.titleSmall,
                            fontWeight = FontWeight.SemiBold,
                        )
                    }
                    items(visiblePlans, key = { it.id }) { plan ->
                        PlanRow(
                            plan = plan,
                            charging = state.charging == plan.id,
                            enabled = state.charging == null && state.manualPayment?.paymentMode != "manual",
                            manualMode = state.manualPayment?.paymentMode == "manual",
                            onCharge = { viewModel.charge(plan.id) },
                        )
                    }
                    item {
                        Text(
                            if (state.manualPayment?.paymentMode == "manual") "Pembayaran manual aktif. Nominal sudah memiliki penanda unik untuk akun ini. Gunakan detail transfer bank/QRIS di bawah, lalu kirim bukti kepada admin untuk diverifikasi." else "Pembayaran dibuka di browser melalui gateway yang aktif. Setelah selesai, tekan muat ulang untuk mengambil status dan masa aktif terbaru dari server.",
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                        )
                    }
                }

                 state.manualPayment?.takeIf {
                     it.paymentMode == "manual" &&
                    it.bankName.isNotBlank() || it.qrisImageUrl.isNotBlank() || it.instructions.isNotBlank()
                }?.let { manual ->
                    item { ManualPaymentCard(manual) }
                }
            }
        }
    }
}

@Composable
private fun ManualPaymentCard(payment: ManualPaymentDto) {
    val context = LocalContext.current
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.secondaryContainer),
    ) {
        Column(Modifier.padding(14.dp), verticalArrangement = Arrangement.spacedBy(6.dp)) {
            Text("Pembayaran manual", style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.Bold)
            Text("Transfer bank atau QRIS. Setelah membayar, kirim bukti ke admin untuk diverifikasi.", style = MaterialTheme.typography.bodySmall)
            if (payment.paymentId.isNotBlank()) Text("ID pembayaran: ${payment.paymentId}", fontWeight = FontWeight.Bold)
            if (payment.bankName.isNotBlank()) Text("Bank: ${payment.bankName}", fontWeight = FontWeight.SemiBold)
            if (payment.bankAccount.isNotBlank()) Text("Rekening: ${payment.bankAccount}")
            if (payment.accountHolder.isNotBlank()) Text("Atas nama: ${payment.accountHolder}")
            if (payment.qrisImageUrl.isNotBlank()) {
                OutlinedButton(onClick = {
                    context.startActivity(Intent(Intent.ACTION_VIEW, Uri.parse(payment.qrisImageUrl)))
                }) { Text("Buka QRIS") }
            }
            if (payment.instructions.isNotBlank()) Text(payment.instructions, style = MaterialTheme.typography.bodySmall)
            if (payment.whatsapp.isNotBlank()) {
                OutlinedButton(onClick = {
                    context.startActivity(Intent(Intent.ACTION_VIEW, Uri.parse("https://wa.me/${payment.whatsapp}")))
                }) { Text("Kirim bukti ke WhatsApp admin") }
            }
        }
    }
}

@Composable
private fun PromoCodeCard(
    code: String,
    redeeming: Boolean,
    onCodeChange: (String) -> Unit,
    onRedeem: () -> Unit,
) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.tertiaryContainer),
    ) {
        Column(Modifier.padding(14.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
            Text("Punya kode promo?", style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.Bold)
            Text("Masukkan kode promo dari admin atau chat WhatsApp. Satu akun hanya dapat memakai kode yang sama satu kali.", style = MaterialTheme.typography.bodySmall)
            OutlinedTextField(
                value = code,
                onValueChange = onCodeChange,
                label = { Text("Kode promo") },
                singleLine = true,
                modifier = Modifier.fillMaxWidth(),
            )
            Button(onClick = onRedeem, enabled = code.isNotBlank() && !redeeming) {
                Text(if (redeeming) "Memeriksa..." else "Gunakan kode promo")
            }
        }
    }
}

/** Whole days until [value]; negative once it has passed, null if unparseable. */
private fun daysUntil(value: String): Long? = runCatching {
    Duration.between(Instant.now(), Instant.parse(value)).toDays()
}.getOrNull()

@Composable
private fun RenewalNotice(daysLeft: Long) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = Amber100),
    ) {
        Column(Modifier.padding(14.dp)) {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Icon(
                    Icons.Filled.WarningAmber,
                    contentDescription = null,
                    tint = Amber600,
                    modifier = Modifier.size(20.dp),
                )
                Spacer(Modifier.size(8.dp))
                Text(
                    "Masa aktif hampir habis",
                    style = MaterialTheme.typography.titleSmall,
                    fontWeight = FontWeight.SemiBold,
                    color = Amber600,
                )
            }
            Spacer(Modifier.height(6.dp))
            Text(
                if (daysLeft < 0) {
                    "Lisensi ini sudah lewat tanggal. Perpanjang sekarang agar data keluarga dan fitur premium tidak terputus."
                } else if (daysLeft == 0L) {
                    "Lisensi berakhir hari ini. Perpanjang sekarang agar data keluarga dan fitur premium tidak terputus."
                } else {
                    "Sisa ${daysLeft} hari lagi. Perpanjang sekarang agar data keluarga dan fitur premium tidak terputus."
                },
                style = MaterialTheme.typography.bodyMedium,
            )
        }
    }
}

@Composable
private fun StatusBanner(
    active: Boolean,
    planName: String?,
    startsAt: String?,
    expiresAt: String?,
    provider: String?,
    onRefresh: () -> Unit,
) {
    val (bg, fg) = if (active) Teal100 to Teal700 else Amber100 to Amber600
    val daysRemaining = expiresAt?.let(::daysUntil)
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

/** Full membership details for an existing active plan, with the plan text. */
@Composable
private fun MembershipCard(
    plan: PlanDto?,
    subscription: com.keuangan.app.data.SubscriptionDto,
) {
    Card(
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
        modifier = Modifier.fillMaxWidth(),
    ) {
        Column(Modifier.padding(16.dp)) {
            Text(
                "Keanggotaan Kamu",
                style = MaterialTheme.typography.titleSmall,
                fontWeight = FontWeight.SemiBold,
            )
            Spacer(Modifier.height(8.dp))
            Text(
                (subscription.planName ?: plan?.name) ?: "Paket aktif",
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.SemiBold,
                color = Teal700,
            )
            plan?.description?.takeIf { it.isNotBlank() }?.let {
                Spacer(Modifier.height(4.dp))
                Text(it, style = MaterialTheme.typography.bodySmall)
            }
            Spacer(Modifier.height(10.dp))
            val rows = buildList {
                subscription.startsAt?.let { add("Mulai" to formatFullDate(it)) }
                subscription.expiresAt?.let { add("Berlaku sampai" to formatFullDate(it)) }
                add("Status" to "Aktif")
                subscription.provider?.takeIf { it.isNotBlank() }?.let {
                    add("Metode pembayaran" to it)
                }
            }
            rows.forEach { (label, value) ->
                if (label != rows.first().first) Spacer(Modifier.height(4.dp))
                Text(
                    "$label: $value",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
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
    manualMode: Boolean,
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
                    formatRupiah(if (manualMode && plan.manualPaymentAmount > 0) plan.manualPaymentAmount else plan.price),
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
                    Text(if (manualMode) "Lihat instruksi manual" else "Bayar sekarang")
                }
            }
        }
    }
}
