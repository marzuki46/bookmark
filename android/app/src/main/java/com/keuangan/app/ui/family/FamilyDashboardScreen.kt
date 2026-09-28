package com.keuangan.app.ui.family

import androidx.compose.animation.core.animateFloatAsState
import androidx.compose.animation.core.animateIntAsState
import androidx.compose.animation.core.tween
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
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.Refresh
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.LinearProgressIndicator
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.keuangan.app.data.FamilyDto
import com.keuangan.app.data.FamilyHealthDto
import com.keuangan.app.data.InsightDto
import com.keuangan.app.data.IncomeBySourceDto
import com.keuangan.app.data.NudgeDto
import com.keuangan.app.ui.components.GradientHeader
import com.keuangan.app.ui.formatCompact
import com.keuangan.app.ui.formatRupiah
import com.keuangan.app.ui.theme.Amber100
import com.keuangan.app.ui.theme.Amber600
import com.keuangan.app.ui.theme.Red100
import com.keuangan.app.ui.theme.Red600
import com.keuangan.app.ui.theme.Teal100
import com.keuangan.app.ui.theme.Teal700

@Composable
fun FamilyDashboardScreen(
    familyId: Int,
    family: FamilyDto?,
    viewModel: FamilyDashboardViewModel,
) {
    val state by viewModel.state.collectAsState()

    // If this tab is the start destination, load happens once via the shell;
    // never depend on init alone for correctness when familyId arrives later.
    LaunchedEffect(familyId) {
        viewModel.load(familyId)
    }

    val initialLoading = state.loading && state.health == null && state.insights.isEmpty()

    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(16.dp, 16.dp, 16.dp, 96.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp),
    ) {
        item {
            GradientHeader(
                title = family?.name ?: "Keluarga",
                subtitle = family?.members?.let { members ->
                    listOfNotNull(
                        members.count { it.payerRole == "husband" }.takeIf { it > 0 }?.let { "Suami ✓" },
                        members.count { it.payerRole == "wife" }.takeIf { it > 0 }?.let { "Istri ✓" },
                    ).joinToString("  ·  ").ifBlank { "Satu sentuhan untuk keuangan bersama" }
                } ?: "Satu sentuhan untuk keuangan bersama",
                trailing = {
                    IconButton(onClick = { viewModel.refresh(familyId) }, enabled = !state.refreshing) {
                        if (state.refreshing) {
                            CircularProgressIndicator(
                                modifier = Modifier.size(18.dp),
                                strokeWidth = 2.dp,
                                color = Color.White,
                            )
                        } else {
                            Icon(
                                Icons.Filled.Refresh,
                                contentDescription = "Muat ulang",
                                tint = Color.White,
                            )
                        }
                    }
                },
            )
        }

        if (initialLoading) {
            item { LoadingRow() }
        } else {
            if (state.error != null) {
                item {
                    Text(
                        state.error!!,
                        color = MaterialTheme.colorScheme.error,
                        style = MaterialTheme.typography.bodyMedium,
                    )
                }
            }

            state.health?.let { health ->
                item { HealthCard(health) }
                item {
                    StatRow(
                        income = health.income,
                        expense = health.expense,
                        savings = health.savings,
                    )
                }
                item {
                    EmergencyCard(health)
                }
            }

            state.nudge?.let { nudge ->
                item {
                    NudgeCard(
                        nudge = nudge,
                        onDismiss = viewModel::dismissNudge,
                    )
                }
            }

            item { SectionTitle("Insight minggu ini") }
            if (state.insights.isEmpty()) {
                item {
                    Text(
                        "Belum ada insight. Insight baru dibuat tiap Senin pagi atau saat mencatat transaksi.",
                        style = MaterialTheme.typography.bodyMedium,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                    )
                }
            } else {
                items(state.insights, key = { it.id }) { insight ->
                    InsightRow(
                        insight = insight,
                        onMarkRead = { viewModel.markRead(familyId, insight) },
                    )
                }
            }

            if (state.incomeBySource.isNotEmpty()) {
                item { SectionTitle("Pemasukan bulan ini") }
                items(state.incomeBySource, key = { it.incomeSourceId ?: -1 }) { row ->
                    IncomeSourceRow(row)
                }
            }
        }
    }
}

@Composable
private fun LoadingRow() {
    Box(Modifier.fillMaxWidth().padding(vertical = 48.dp), contentAlignment = Alignment.Center) {
        CircularProgressIndicator()
    }
}

@Composable
private fun SectionTitle(text: String) {
    Text(
        text,
        style = MaterialTheme.typography.titleMedium,
        fontWeight = FontWeight.SemiBold,
        modifier = Modifier.padding(top = 4.dp),
    )
}

@Composable
private fun HealthCard(health: FamilyHealthDto) {
    val (bg, fg) = gradeTint(health.grade)
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = bg),
    ) {
        Row(
            modifier = Modifier.padding(18.dp),
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Column(Modifier.weight(1f)) {
                Text(
                    "Kesehatan keuangan keluarga",
                    style = MaterialTheme.typography.labelLarge,
                    color = fg,
                )
                Spacer(Modifier.height(6.dp))
                Text(
                    when {
                        health.income >= health.expense && health.savings >= 0 ->
                            "Kondisi sehat — pemasukan menutupi pengeluaran dan tabungan berjalan."
                        health.savings < 0 -> "Tabungan negatif — bicarakan anggaran bulan ini."
                        else -> "Berjalan cukup — dorong tabungan darurat agar lebih aman."
                    },
                    style = MaterialTheme.typography.bodyMedium,
                    color = fg,
                )
            }
            Spacer(Modifier.width(12.dp))
            Column(horizontalAlignment = Alignment.CenterHorizontally) {
                val animatedScore by animateIntAsState(
                    targetValue = health.score.toInt(),
                    animationSpec = tween(700),
                    label = "skor-kesehatan",
                )
                Text("$animatedScore", fontSize = 40.sp, fontWeight = FontWeight.Bold, color = fg)
                Text(
                    "Nilai ${health.grade}",
                    style = MaterialTheme.typography.labelMedium,
                    color = fg,
                )
            }
        }
    }
}

@Composable
private fun StatRow(income: Double, expense: Double, savings: Double) {
    Row(horizontalArrangement = Arrangement.spacedBy(10.dp)) {
        StatCard("Pemasukan", income, ::formatShortRupiah, Teal100, Teal700, Modifier.weight(1f))
        StatCard("Pengeluaran", expense, ::formatShortRupiah, Red100, Red600, Modifier.weight(1f))
        StatCard("Selisih", savings, ::formatSignedShort, Amber100, Amber600, Modifier.weight(1f))
    }
}

@Composable
private fun StatCard(
    label: String,
    value: Double,
    format: (Double) -> String,
    bg: Color,
    fg: Color,
    modifier: Modifier = Modifier,
) {
    val animated by animateFloatAsState(
        targetValue = value.toFloat(),
        animationSpec = tween(700),
        label = "kartu-$label",
    )
    Card(
        modifier = modifier,
        colors = CardDefaults.cardColors(containerColor = bg),
    ) {
        Column(Modifier.padding(vertical = 12.dp, horizontal = 10.dp)) {
            Text(label, style = MaterialTheme.typography.labelMedium, color = fg)
            Spacer(Modifier.height(2.dp))
            Text(
                format(animated.toDouble()),
                style = MaterialTheme.typography.titleLarge,
                fontWeight = FontWeight.Bold,
                color = fg,
            )
        }
    }
}

@Composable
private fun EmergencyCard(health: FamilyHealthDto) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
    ) {
        Column(Modifier.padding(16.dp)) {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Column(Modifier.weight(1f)) {
                    Text("Dana darurat", fontWeight = FontWeight.SemiBold)
                    Spacer(Modifier.height(2.dp))
                    Text(
                        "${formatRupiah(health.emergencyCurrent)} dari target ${formatRupiah(health.emergencyTarget)}",
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                    )
                }
                if (health.totalDebt > 0) {
                    Text(
                        "Hutang ${formatCompact(health.totalDebt)}",
                        style = MaterialTheme.typography.labelMedium,
                        color = Red600,
                    )
                }
            }
            Spacer(Modifier.height(8.dp))
            val fraction = if (health.emergencyTarget > 0) {
                (health.emergencyCurrent / health.emergencyTarget).coerceIn(0.0, 1.0)
            } else {
                0.0
            }
            LinearProgressIndicator(
                progress = { fraction.toFloat() },
                modifier = Modifier.fillMaxWidth().height(8.dp),
                color = if (fraction >= 1f) Teal700 else Amber600,
                trackColor = MaterialTheme.colorScheme.surfaceVariant,
            )
        }
    }
}

@Composable
private fun NudgeCard(nudge: NudgeDto, onDismiss: () -> Unit) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = Amber100),
        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
    ) {
        Row(Modifier.padding(14.dp), verticalAlignment = Alignment.Top) {
            Text(
                "👉  " + (nudge.message.ifBlank { "Saran keuangan untuk keluarga." }),
                style = MaterialTheme.typography.bodyMedium,
                modifier = Modifier.weight(1f),
            )
            IconButton(onClick = onDismiss, modifier = Modifier.size(28.dp)) {
                Icon(
                    Icons.Filled.Close,
                    contentDescription = "Tutup saran",
                    modifier = Modifier.size(16.dp),
                    tint = Amber600,
                )
            }
        }
    }
}

@Composable
private fun InsightRow(insight: InsightDto, onMarkRead: () -> Unit) {
    val emphasized = insight.readAt == null
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(
            containerColor = if (emphasized) MaterialTheme.colorScheme.primaryContainer else MaterialTheme.colorScheme.surface,
        ),
        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
    ) {
        Column(Modifier.padding(14.dp)) {
            if (emphasized) {
                Text(
                    "Belum dibaca",
                    style = MaterialTheme.typography.labelSmall,
                    color = MaterialTheme.colorScheme.primary,
                )
                Spacer(Modifier.height(2.dp))
            }
            Text(insight.message, style = MaterialTheme.typography.bodyMedium)
            Spacer(Modifier.height(6.dp))
            Row(verticalAlignment = Alignment.CenterVertically) {
                Text(
                    if (insight.scope == "personal") "Ringkasan pribadi" else "Keluarga",
                    style = MaterialTheme.typography.labelSmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                    modifier = Modifier.weight(1f),
                )
                if (emphasized) {
                    TextButton(onClick = onMarkRead) {
                        Text("Tandai dibaca")
                    }
                }
            }
        }
    }
}

@Composable
private fun IncomeSourceRow(row: IncomeBySourceDto) {
    Row(
        modifier = Modifier.fillMaxWidth().padding(horizontal = 2.dp),
        verticalAlignment = Alignment.CenterVertically,
    ) {
        Text(
            row.name,
            style = MaterialTheme.typography.bodyMedium,
            modifier = Modifier.weight(1f),
        )
        Text(
            formatRupiah(row.total),
            style = MaterialTheme.typography.bodyMedium,
            fontWeight = FontWeight.SemiBold,
            color = Teal700,
        )
    }
}

private fun formatShortRupiah(value: Double): String = if (kotlin.math.abs(value) >= 1_000_000) {
    formatCompact(value)
} else {
    formatRupiah(value)
}

private fun formatSignedShort(value: Double): String = if (value >= 0) {
    "+${formatShortRupiah(value)}"
} else {
    "-${formatShortRupiah(kotlin.math.abs(value))}"
}