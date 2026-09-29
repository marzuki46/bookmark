package com.keuangan.app.ui.family

import androidx.compose.animation.core.animateFloatAsState
import androidx.compose.animation.core.animateIntAsState
import androidx.compose.animation.core.tween
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.offset
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.KeyboardArrowRight
import androidx.compose.material.icons.automirrored.filled.TrendingUp
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.Balance
import androidx.compose.material.icons.filled.ChildCare
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.Flag
import androidx.compose.material.icons.filled.Group
import androidx.compose.material.icons.filled.GridView
import androidx.compose.material.icons.filled.Lightbulb
import androidx.compose.material.icons.filled.Refresh
import androidx.compose.material.icons.filled.Wallet
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
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.keuangan.app.data.FamilyDto
import com.keuangan.app.data.FamilyGoalDto
import com.keuangan.app.data.FamilyHealthDto
import com.keuangan.app.data.InsightDto
import com.keuangan.app.data.IncomeBySourceDto
import com.keuangan.app.data.NudgeDto
import com.keuangan.app.ui.formatCompact
import com.keuangan.app.ui.formatRupiah
import com.keuangan.app.ui.theme.Amber100
import com.keuangan.app.ui.theme.Amber600
import com.keuangan.app.ui.theme.AppThemes
import com.keuangan.app.ui.theme.Red600
import com.keuangan.app.ui.theme.Teal700
import com.keuangan.app.ui.theme.ThemeController
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale

/**
 * Home — Gojek-style dashboard. A glassy gradient hero that owns the "Selisih
 * bulan ini" number, a grid of shortcuts, a compact family summary (hutang,
 * dana darurat, target tabungan), Kang Cuan's nudge and the weekly insights.
 * The financial health score lives in Pendamping Keuangan.
 */
@Composable
fun FamilyDashboardScreen(
    familyId: Int,
    family: FamilyDto?,
    viewModel: FamilyDashboardViewModel,
    onOpenTransactions: () -> Unit = {},
    onOpenBudgets: () -> Unit = {},
    onOpenDebts: () -> Unit = {},
    onOpenGoals: () -> Unit = {},
    onOpenTrend: () -> Unit = {},
    onOpenProfile: () -> Unit = {},
    onOpenMore: () -> Unit = {},
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
        contentPadding = PaddingValues(16.dp, 8.dp, 16.dp, 96.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp),
    ) {
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
                item {
                    HeroHeader(
                        familyName = family?.name ?: "Keluarga",
                        health = health,
                        refreshing = state.refreshing,
                        onRefresh = { viewModel.refresh(familyId) },
                    )
                }
                item {
                    FeatureGrid(
                        onTransactions = onOpenTransactions,
                        onBudgets = onOpenBudgets,
                        onDebts = onOpenDebts,
                        onGoals = onOpenGoals,
                        onTrend = onOpenTrend,
                        onProfile = onOpenProfile,
                        onMore = onOpenMore,
                    )
                }
                item {
                    FamilySummaryCard(
                        health = health,
                        goals = state.goals,
                        onOpenDebts = onOpenDebts,
                        onOpenGoals = onOpenGoals,
                    )
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

/**
 * Glassy gradient hero: greeting on top, the month's balance front and centre,
 * and both inputs underneath — so the header space does real work.
 */
@Composable
private fun HeroHeader(
    familyName: String,
    health: FamilyHealthDto,
    refreshing: Boolean,
    onRefresh: () -> Unit,
) {
    val savings = health.income - health.expense
    val status = when {
        health.income == 0.0 && health.expense == 0.0 -> "Belum ada catatan bulan ini."
        savings < 0 -> "Defisit — pengeluaran melebihi pemasukan."
        savings == 0.0 -> "Seimbang — pemasukan sama dengan pengeluaran."
        else -> "Surplus — pemasukan melebihi pengeluaran."
    }
    val appTheme = AppThemes.firstOrNull { it.id == ThemeController.themeId.value }
        ?: AppThemes.first()
    val contentOn = Color.White
    val mutedOn = Color.White.copy(alpha = 0.85f)
    val savingsColor = if (savings < 0) Color(0xFFFFF3CD) else Color.White

    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(22.dp),
        colors = CardDefaults.cardColors(containerColor = Color.Transparent),
        elevation = CardDefaults.cardElevation(defaultElevation = 0.dp),
    ) {
        Box(
            Modifier
                .fillMaxWidth()
                .background(appTheme.gradient)
        ) {
            // Glassmorphism decorations.
            Box(
                Modifier
                    .align(Alignment.TopEnd)
                    .offset(x = 26.dp, y = (-22).dp)
                    .size(96.dp)
                    .background(Color.White.copy(alpha = 0.14f), CircleShape),
            )
            Box(
                Modifier
                    .align(Alignment.BottomStart)
                    .offset(x = (-20).dp, y = 26.dp)
                    .size(72.dp)
                    .background(Color.White.copy(alpha = 0.10f), CircleShape),
            )

            Column(Modifier.padding(18.dp)) {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Column(Modifier.weight(1f)) {
                        Text(
                            "Soal cuan, urusan Kang Cuan",
                            style = MaterialTheme.typography.labelMedium,
                            color = mutedOn,
                        )
                        Text(
                            familyName,
                            style = MaterialTheme.typography.titleLarge,
                            fontWeight = FontWeight.Bold,
                            color = contentOn,
                            maxLines = 1,
                        )
                    }
                    IconButton(onClick = onRefresh, enabled = !refreshing) {
                        if (refreshing) {
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
                }
                Spacer(Modifier.height(12.dp))
                Text(
                    "Selisih bulan ini · ${SimpleDateFormat("MMMM yyyy", Locale("id", "ID")).format(Date())}",
                    style = MaterialTheme.typography.labelLarge,
                    color = mutedOn,
                )
                Spacer(Modifier.height(2.dp))
                val animated by animateIntAsState(
                    targetValue = savings.toInt(),
                    animationSpec = tween(700),
                    label = "selisih-bulan",
                )
                Text(
                    formatSignedFull(animated.toDouble()),
                    fontSize = 24.sp,
                    fontWeight = FontWeight.ExtraBold,
                    color = savingsColor,
                )
                Spacer(Modifier.height(2.dp))
                Text(
                    status,
                    style = MaterialTheme.typography.bodyMedium,
                    color = mutedOn,
                )
                Spacer(Modifier.height(14.dp))
                Row(horizontalArrangement = Arrangement.spacedBy(28.dp)) {
                    MiniAmount("Pemasukan", health.income, contentOn, mutedOn)
                    MiniAmount("Pengeluaran", health.expense, contentOn, mutedOn)
                }
            }
        }
    }
}

@Composable
private fun MiniAmount(label: String, value: Double, fg: Color, muted: Color) {
    Column {
        Text(label, style = MaterialTheme.typography.labelMedium, color = muted)
        Spacer(Modifier.height(2.dp))
        Text(
            formatRupiah(value),
            style = MaterialTheme.typography.bodyLarge,
            fontWeight = FontWeight.Bold,
            color = fg,
        )
    }
}

/** Gojek-style shortcut grid: four tiles a row, two rows. */
@Composable
private fun FeatureGrid(
    onTransactions: () -> Unit,
    onBudgets: () -> Unit,
    onDebts: () -> Unit,
    onGoals: () -> Unit,
    onTrend: () -> Unit,
    onProfile: () -> Unit,
    onMore: () -> Unit,
) {
    val tiles = listOf(
        FeatureTile("Catat", "Catat transaksi", Icons.Filled.Add, onTransactions),
        FeatureTile("Anggaran", "Atur batas pengeluaran", Icons.Filled.Wallet, onBudgets),
        FeatureTile("Hutang", "Hutang & cicilan", Icons.Filled.Balance, onDebts),
        FeatureTile("Tabungan", "Target tabungan", Icons.Filled.Flag, onGoals),
        FeatureTile("Tren", "Pemasukan vs pengeluaran", Icons.AutoMirrored.Filled.TrendingUp, onTrend),
        FeatureTile("Saran", "Saran dari Kang Cuan", Icons.Filled.ChildCare, onMore),
        FeatureTile("Keluarga", "Anggota & kode login", Icons.Filled.Group, onProfile),
        FeatureTile("Semua", "Kategori, tema & lainnya", Icons.Filled.GridView, onMore),
    )
    Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
        tiles.chunked(4).forEach { rowTiles ->
            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                rowTiles.forEach { tile ->
                    Tile(tile, Modifier.weight(1f))
                }
            }
        }
    }
}

private data class FeatureTile(
    val title: String,
    val subtitle: String,
    val icon: ImageVector,
    val onClick: () -> Unit,
)

@Composable
private fun Tile(tile: FeatureTile, modifier: Modifier = Modifier) {
    Card(
        modifier = modifier.clickable(onClick = tile.onClick),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
        shape = RoundedCornerShape(16.dp),
    ) {
        // A fixed-ish height keeps both rows even, and the label is allowed two
        // lines on narrow phones so "Anggaran" is never cut in half.
        Column(
            Modifier
                .height(96.dp)
                .padding(horizontal = 8.dp, vertical = 10.dp),
            horizontalAlignment = Alignment.CenterHorizontally,
        ) {
            Box(
                Modifier
                    .size(34.dp)
                    .background(MaterialTheme.colorScheme.primaryContainer, CircleShape),
                contentAlignment = Alignment.Center,
            ) {
                Icon(
                    tile.icon,
                    contentDescription = null,
                    tint = MaterialTheme.colorScheme.primary,
                    modifier = Modifier.size(20.dp),
                )
            }
            Spacer(Modifier.height(8.dp))
            Text(
                tile.title,
                style = MaterialTheme.typography.labelMedium,
                fontWeight = FontWeight.SemiBold,
                color = MaterialTheme.colorScheme.onSurface,
                textAlign = TextAlign.Center,
                maxLines = 2,
                lineHeight = 14.sp,
            )
        }
    }
}

/** One card that summarises where the family stands: hutang, darurat, target. */
@Composable
private fun FamilySummaryCard(
    health: FamilyHealthDto,
    goals: List<FamilyGoalDto>,
    onOpenDebts: () -> Unit,
    onOpenGoals: () -> Unit,
) {
    val activeGoals = goals.filter { it.status == "active" }
    val goalTarget = activeGoals.sumOf { it.targetAmount }
    val goalCurrent = activeGoals.sumOf { it.currentAmount }
    val goalFraction = if (goalTarget > 0) (goalCurrent / goalTarget).coerceIn(0.0, 1.0) else 0.0
    val emergencyFraction = if (health.emergencyTarget > 0) {
        (health.emergencyCurrent / health.emergencyTarget).coerceIn(0.0, 1.0)
    } else {
        0.0
    }

    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
        shape = RoundedCornerShape(18.dp),
    ) {
        Column(Modifier.padding(16.dp)) {
            Text(
                "Ringkasan keluarga",
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.SemiBold,
            )
            Spacer(Modifier.height(8.dp))

            ClickableSummaryRow(
                label = "Total hutang",
                value = if (health.totalDebt > 0) {
                    formatRupiah(health.totalDebt)
                } else {
                    "Bersih dari hutang"
                },
                valueColor = if (health.totalDebt > 0) Red600 else Teal700,
                onClick = onOpenDebts,
            )

            Spacer(Modifier.height(12.dp))
            Text(
                "Dana darurat — ${formatCompact(health.emergencyCurrent)} dari target ${formatCompact(health.emergencyTarget)}",
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
            )
            Spacer(Modifier.height(6.dp))
            LinearProgressIndicator(
                progress = { emergencyFraction.toFloat() },
                modifier = Modifier.fillMaxWidth().height(8.dp),
                color = if (emergencyFraction >= 1f) Teal700 else Amber600,
                trackColor = MaterialTheme.colorScheme.surfaceVariant,
            )

            Spacer(Modifier.height(12.dp))
            Row(verticalAlignment = Alignment.CenterVertically) {
                Text(
                    "Target tabungan",
                    style = MaterialTheme.typography.bodyMedium,
                    fontWeight = FontWeight.SemiBold,
                    modifier = Modifier.weight(1f),
                )
                Text(
                    if (activeGoals.isEmpty()) {
                        "Belum ada target"
                    } else {
                        "${formatCompact(goalCurrent)} dari ${formatCompact(goalTarget)}"
                    },
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                )
                Icon(
                    Icons.AutoMirrored.Filled.KeyboardArrowRight,
                    contentDescription = null,
                    tint = MaterialTheme.colorScheme.onSurfaceVariant,
                    modifier = Modifier.size(20.dp),
                )
            }
            if (activeGoals.isNotEmpty()) {
                Spacer(Modifier.height(6.dp))
                val animatedGoal by animateFloatAsState(
                    targetValue = goalFraction.toFloat(),
                    animationSpec = tween(700),
                    label = "target-tabungan",
                )
                LinearProgressIndicator(
                    progress = { animatedGoal },
                    modifier = Modifier.fillMaxWidth().height(8.dp),
                    color = Teal700,
                    trackColor = MaterialTheme.colorScheme.surfaceVariant,
                )
            }
        }
    }
}

@Composable
private fun ClickableSummaryRow(
    label: String,
    value: String,
    valueColor: Color,
    onClick: () -> Unit,
) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .clickable(onClick = onClick)
            .padding(vertical = 4.dp),
        verticalAlignment = Alignment.CenterVertically,
    ) {
        Text(
            label,
            style = MaterialTheme.typography.bodyMedium,
            fontWeight = FontWeight.SemiBold,
            modifier = Modifier.weight(1f),
        )
        Text(
            value,
            style = MaterialTheme.typography.bodyMedium,
            fontWeight = FontWeight.Bold,
            color = valueColor,
        )
        Icon(
            Icons.AutoMirrored.Filled.KeyboardArrowRight,
            contentDescription = null,
            tint = MaterialTheme.colorScheme.onSurfaceVariant,
            modifier = Modifier.size(20.dp),
        )
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
            Icon(
                Icons.Filled.Lightbulb,
                contentDescription = null,
                tint = Amber600,
                modifier = Modifier.size(20.dp),
            )
            Spacer(Modifier.width(10.dp))
            Column(Modifier.weight(1f)) {
                Text(
                    "Saran Kang Cuan untukmu",
                    style = MaterialTheme.typography.labelLarge,
                    color = Amber600,
                )
                Spacer(Modifier.height(3.dp))
                Text(
                    nudge.message.ifBlank { "Ada kabar kecil tentang keuangan keluarga." },
                    style = MaterialTheme.typography.bodyMedium,
                )
            }
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

private fun formatSignedFull(value: Double): String = if (value >= 0) {
    "Rp ${formatCompact(value)}"
} else {
    "-Rp ${formatCompact(kotlin.math.abs(value))}"
}