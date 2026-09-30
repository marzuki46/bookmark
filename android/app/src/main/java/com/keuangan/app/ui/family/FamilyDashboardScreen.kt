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
import androidx.compose.foundation.pager.HorizontalPager
import androidx.compose.foundation.pager.rememberPagerState
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.lazy.rememberLazyListState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.KeyboardArrowRight
import androidx.compose.material.icons.automirrored.filled.TrendingUp
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.Balance
import androidx.compose.material.icons.filled.CalendarMonth
import androidx.compose.material.icons.filled.ChildCare
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.DateRange
import androidx.compose.material.icons.filled.Flag
import androidx.compose.material.icons.filled.Group
import androidx.compose.material.icons.filled.GridView
import androidx.compose.material.icons.filled.LightMode
import androidx.compose.material.icons.filled.Lightbulb
import androidx.compose.material.icons.filled.NightsStay
import androidx.compose.material.icons.filled.NorthEast
import androidx.compose.material.icons.filled.Refresh
import androidx.compose.material.icons.filled.SouthWest
import androidx.compose.material.icons.filled.Today
import androidx.compose.material.icons.filled.Wallet
import androidx.compose.material.icons.filled.WbSunny
import androidx.compose.material.icons.filled.WbTwilight
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.HorizontalDivider
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
import androidx.compose.runtime.remember
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.keuangan.app.data.FamilyDto
import com.keuangan.app.data.FamilyForecastDto
import com.keuangan.app.data.FamilyGoalDto
import com.keuangan.app.data.FamilyHealthDto
import com.keuangan.app.data.ForecastLicenseDto
import com.keuangan.app.data.ForecastWindowDto
import com.keuangan.app.data.InsightDto
import com.keuangan.app.data.IncomeBySourceDto
import com.keuangan.app.data.NudgeDto
import com.keuangan.app.ui.formatCompact
import com.keuangan.app.ui.formatRupiah
import com.keuangan.app.ui.components.collapsingHeader
import com.keuangan.app.ui.components.rememberHeaderCollapse
import com.keuangan.app.ui.theme.Amber100
import com.keuangan.app.ui.theme.Amber600
import com.keuangan.app.ui.theme.AppThemes
import com.keuangan.app.ui.theme.Red600
import com.keuangan.app.ui.theme.Teal100
import com.keuangan.app.ui.theme.Teal700
import com.keuangan.app.ui.theme.Teal900
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
    memberName: String?,
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

    val listState = rememberLazyListState()
    val headerIndex = if (state.error != null) 1 else 0
    val headerFraction by rememberHeaderCollapse(listState, headerIndex)

    LazyColumn(
        state = listState,
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
                        modifier = Modifier.collapsingHeader { headerFraction },
                            familyName = family?.name ?: "Keluarga",
                            memberName = memberName,
                            health = health,
                            refreshing = state.refreshing,
                            onRefresh = { viewModel.refresh(familyId) },
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

            // Forecast and the prayer card are independent of the health score,
            // so they live outside its block: a failed /summary call must not
            // take the cash-flow outlook down with it.
            state.forecast?.let { forecast ->
                item { SectionTitle("Laporan kamu hari ini") }
                item { ForecastStrip(forecast.data, forecast.license) }
            }
            if (java.time.LocalTime.now().hour >= 18) {
                item { EveningPrayerCard() }
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

/** "Selamat pagi kak Budi," — first name only, time-of-day aware. */
private fun greetingFor(memberName: String?): String {
    val firstName = memberName?.trim()?.substringBefore(' ')?.takeIf { it.isNotBlank() }
    return if (firstName != null) "Selamat ${greetingPeriod()} kak $firstName," else "Selamat ${greetingPeriod()},"
}

/** Time-of-day bucket shared by the greeting text and its icon. */
private fun greetingPeriod(): String {
    val hour = java.time.LocalTime.now().hour
    return when (hour) {
        in 0..10 -> "pagi"
        in 11..14 -> "siang"
        in 15..17 -> "sore"
        else -> "malam"
    }
}

/** Icon that matches the greeting period, so the header reads at a glance. */
private fun greetingIcon(): ImageVector = when (greetingPeriod()) {
    "pagi" -> Icons.Filled.WbSunny
    "siang" -> Icons.Filled.LightMode
    "sore" -> Icons.Filled.WbTwilight
    else -> Icons.Filled.NightsStay
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
    modifier: Modifier = Modifier,
    familyName: String,
    memberName: String?,
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
        modifier = modifier.fillMaxWidth(),
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
                    Box(
                        Modifier
                            .size(36.dp)
                            .background(Color.White.copy(alpha = 0.18f), CircleShape),
                        contentAlignment = Alignment.Center,
                    ) {
                        Icon(
                            greetingIcon(),
                            contentDescription = null,
                            tint = contentOn,
                            modifier = Modifier.size(20.dp),
                        )
                    }
                    Spacer(Modifier.width(8.dp))
                    Column(Modifier.weight(1f)) {
                        // Two lines: a long name plus the period word does not fit on
                        // one line on a narrow phone, and truncating it to
                        // "Selamat siang kak Mar..." greets nobody.
                        Text(
                            greetingFor(memberName),
                            style = MaterialTheme.typography.titleLarge,
                            fontWeight = FontWeight.Bold,
                            color = contentOn,
                            maxLines = 2,
                            overflow = TextOverflow.Ellipsis,
                            softWrap = true,
                        )
                        Text(
                            "Soal cuan, urusan Kang Cuan",
                            style = MaterialTheme.typography.labelMedium,
                            color = mutedOn,
                            maxLines = 1,
                            overflow = TextOverflow.Ellipsis,
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
                Spacer(Modifier.height(10.dp))
                Text(
                    "Sisa bulan ini · ${familyName} · ${SimpleDateFormat("MMMM yyyy", Locale("id", "ID")).format(Date())}",
                    style = MaterialTheme.typography.labelLarge,
                    color = mutedOn,
                    maxLines = 1,
                    overflow = TextOverflow.Ellipsis,
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
        // A fixed height keeps both rows even, and the label is allowed two
        // lines on narrow phones so "Anggaran" is never cut in half. The column
        // must fill the cell width, otherwise it shrink-wraps to the icon and
        // centring has no space to work with, leaving icon and label hugging the
        // left edge.
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .height(96.dp)
                .padding(horizontal = 8.dp, vertical = 8.dp),
            horizontalAlignment = Alignment.CenterHorizontally,
            verticalArrangement = Arrangement.Center,
        ) {
            Box(
                Modifier
                    .size(38.dp)
                    .background(MaterialTheme.colorScheme.primaryContainer, CircleShape),
                contentAlignment = Alignment.Center,
            ) {
                Icon(
                    tile.icon,
                    contentDescription = null,
                    tint = MaterialTheme.colorScheme.primary,
                    modifier = Modifier.size(22.dp),
                )
            }
            Spacer(Modifier.height(6.dp))
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

/**
 * Today / this week / this month, each shown next to the matching previous
 * window so the reader sees direction, not just a number. Null pct means the
 * previous window was empty, so we say so instead of printing a bogus 0%.
 *
 * Layout: today gets a full-width hero because it is the number people open
 * the app for; week and month share one row underneath. Three equal columns
 * used to squeeze rupiah figures into ~110dp each and wrap mid-amount.
 */
/** One swipeable page of the forecast pager. */
private data class ForecastPage(
    val label: String,
    val netCaption: String,
    val window: ForecastWindowDto,
    val icon: ImageVector,
)

@Composable
private fun ForecastStrip(forecast: FamilyForecastDto, license: ForecastLicenseDto) {
    // One full-width card per window, swiped horizontally. Splitting three windows
    // across a grid gave each one half the screen, so the label and the rupiah
    // figure ended up stacked and wrapped instead of read side by side.
    val pages = listOf(
        ForecastPage("Hari ini", "Sisa kas hari ini", forecast.today, Icons.Filled.Today),
        ForecastPage("Minggu ini", "Sisa minggu ini", forecast.week, Icons.Filled.DateRange),
        ForecastPage("Bulan ini", "Sisa bulan ini", forecast.month, Icons.Filled.CalendarMonth),
    )
    val pagerState = rememberPagerState(pageCount = { pages.size })

    Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
        HorizontalPager(state = pagerState, modifier = Modifier.fillMaxWidth()) { page ->
            ForecastCard(
                modifier = Modifier.fillMaxWidth(),
                page = pages[page],
                license = license,
            )
        }
        PageDots(current = pagerState.currentPage, count = pages.size)
    }
}

/** Swipe position readout: one dot per window, the active one stretched. */
@Composable
private fun PageDots(current: Int, count: Int) {
    Row(
        modifier = Modifier.fillMaxWidth(),
        horizontalArrangement = Arrangement.Center,
        verticalAlignment = Alignment.CenterVertically,
    ) {
        repeat(count) { index ->
            val active = index == current
            Box(
                Modifier
                    .padding(horizontal = 3.dp)
                    .height(6.dp)
                    .width(if (active) 20.dp else 6.dp)
                    .background(
                        color = if (active) {
                            MaterialTheme.colorScheme.primary
                        } else {
                            MaterialTheme.colorScheme.outlineVariant
                        },
                        shape = CircleShape,
                    ),
            )
        }
    }
}

@Composable
private fun ForecastCard(
    modifier: Modifier,
    page: ForecastPage,
    license: ForecastLicenseDto,
) {
    // Net is only meaningful when both streams are visible: the server zeroes a
    // hidden stream, so subtracting it would print a confident wrong number.
    val netKnown = license.incomeVisible && license.expenseVisible
    val net = page.window.current.income - page.window.current.expense

    Card(
        modifier = modifier,
        colors = CardDefaults.cardColors(containerColor = Teal100),
        elevation = CardDefaults.cardElevation(defaultElevation = 0.dp),
    ) {
        Column(Modifier.padding(16.dp)) {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Box(
                    Modifier
                        .size(38.dp)
                        .background(Color.White.copy(alpha = 0.55f), CircleShape),
                    contentAlignment = Alignment.Center,
                ) {
                    Icon(
                        page.icon,
                        contentDescription = null,
                        tint = Teal700,
                        modifier = Modifier.size(20.dp),
                    )
                }
                Spacer(Modifier.width(10.dp))
                Column(Modifier.weight(1f)) {
                    Text(
                        page.label,
                        style = MaterialTheme.typography.titleMedium,
                        fontWeight = FontWeight.Bold,
                        color = Teal900,
                        maxLines = 1,
                        overflow = TextOverflow.Ellipsis,
                    )
                    Text(
                        page.netCaption,
                        style = MaterialTheme.typography.labelSmall,
                        color = Teal900.copy(alpha = 0.75f),
                        maxLines = 1,
                        overflow = TextOverflow.Ellipsis,
                    )
                }
            }

            Spacer(Modifier.height(12.dp))

            // The hero figure gets its own full-width line. Sharing a row with the
            // title was what forced the text to break mid-number.
            Text(
                if (netKnown) formatRupiah(net) else "Tidak terlihat",
                style = MaterialTheme.typography.headlineMedium,
                fontWeight = FontWeight.Bold,
                color = if (net >= 0) Teal700 else Red600,
                maxLines = 1,
                overflow = TextOverflow.Ellipsis,
            )
            Text(
                if (netKnown) "Sisa setelah dikurangi pengeluaran" else "Sembunyikan salah satu catatan untuk melihat sisa",
                style = MaterialTheme.typography.labelSmall,
                color = Teal900.copy(alpha = 0.75f),
                maxLines = 2,
                overflow = TextOverflow.Ellipsis,
            )

            Spacer(Modifier.height(12.dp))
            HorizontalDivider(color = Teal900.copy(alpha = 0.12f))
            Spacer(Modifier.height(12.dp))

            Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                ForecastStat(
                    modifier = Modifier.weight(1f),
                    caption = "Masuk",
                    icon = Icons.Filled.SouthWest,
                    amount = page.window.current.income,
                    delta = page.window.delta.incomeDelta,
                    pct = page.window.delta.incomePct,
                    visible = license.incomeVisible,
                    goodWhenUp = true,
                )
                ForecastStat(
                    modifier = Modifier.weight(1f),
                    caption = "Keluar",
                    icon = Icons.Filled.NorthEast,
                    amount = page.window.current.expense,
                    delta = page.window.delta.expenseDelta,
                    pct = page.window.delta.expensePct,
                    visible = license.expenseVisible,
                    goodWhenUp = false,
                )
            }
        }
    }
}

@Composable
private fun ForecastStat(
    modifier: Modifier,
    caption: String,
    icon: ImageVector,
    amount: Double,
    delta: Double,
    pct: Double?,
    visible: Boolean,
    goodWhenUp: Boolean,
) {
    Column(modifier) {
        Row(verticalAlignment = Alignment.CenterVertically) {
            Icon(
                icon,
                contentDescription = null,
                tint = Teal900.copy(alpha = 0.7f),
                modifier = Modifier.size(14.dp),
            )
            Spacer(Modifier.width(4.dp))
            Text(
                caption,
                style = MaterialTheme.typography.labelMedium,
                color = Teal900.copy(alpha = 0.8f),
                maxLines = 1,
                overflow = TextOverflow.Ellipsis,
            )
        }
        Spacer(Modifier.height(2.dp))
        Text(
            if (visible) formatRupiah(amount) else "—",
            style = MaterialTheme.typography.titleSmall,
            fontWeight = FontWeight.Bold,
            color = Teal900,
            maxLines = 1,
            overflow = TextOverflow.Ellipsis,
        )
        if (!visible) {
            Text(
                "Disembunyikan",
                style = MaterialTheme.typography.labelSmall,
                color = Teal900.copy(alpha = 0.7f),
                maxLines = 1,
                overflow = TextOverflow.Ellipsis,
            )
            return
        }
        val better = if (goodWhenUp) delta >= 0 else delta <= 0
        val tint = when {
            delta == 0.0 -> Teal900.copy(alpha = 0.7f)
            better -> Teal700
            else -> Red600
        }
        Text(
            buildString {
                append(if (delta >= 0) "+" else "−")
                append(formatRupiah(kotlin.math.abs(delta)))
                if (pct != null) append(" (${if (pct >= 0) "+" else "−"}${formatPct(pct)})")
            },
            style = MaterialTheme.typography.labelSmall,
            color = tint,
            fontWeight = FontWeight.Medium,
            maxLines = 1,
            overflow = TextOverflow.Ellipsis,
        )
    }
}

/** "12,5%" — one decimal, but never a trailing ".0". */
private fun formatPct(pct: Double): String {
    val rounded = kotlin.math.round(pct * 10.0) / 10.0
    return if (rounded == rounded.toLong().toDouble()) {
        "${rounded.toLong()}%"
    } else {
        "${"%.1f".format(java.util.Locale("id", "ID"), rounded)}%"
    }
}

@Composable
private fun EveningPrayerCard() {
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = Amber100),
        elevation = CardDefaults.cardElevation(defaultElevation = 0.dp),
    ) {
        Column(Modifier.padding(14.dp)) {
            Text(
                "Doa malam",
                style = MaterialTheme.typography.labelMedium,
                fontWeight = FontWeight.SemiBold,
                color = Amber600,
            )
            Spacer(Modifier.height(4.dp))
            Text(
                "Semoga apa yang sudah dikeluarkan hari ini segera berbuah hasil. Besok, apa pun rencanamu, sisihkan dulu sedikit untuk ditabung sebelum pengeluaran lain.",
                style = MaterialTheme.typography.bodyMedium,
            )
        }
    }
}
