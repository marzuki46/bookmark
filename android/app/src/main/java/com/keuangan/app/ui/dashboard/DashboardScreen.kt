package com.keuangan.app.ui.dashboard

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.RowScope
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.TrendingDown
import androidx.compose.material.icons.automirrored.filled.TrendingUp
import androidx.compose.material.icons.filled.Category
import androidx.compose.material.icons.filled.Logout
import androidx.compose.material.icons.filled.Psychology
import androidx.compose.material.icons.filled.ReceiptLong
import androidx.compose.material.icons.filled.Refresh
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Button
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.FilterChip
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.LinearProgressIndicator
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.TopAppBar
import androidx.compose.material3.TopAppBarDefaults
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.keuangan.app.data.CategoryBreakdownDto
import com.keuangan.app.data.TransactionDto
import com.keuangan.app.ui.charts.BarDatum
import com.keuangan.app.ui.charts.CategoryDonutChart
import com.keuangan.app.ui.charts.ChartLegend
import com.keuangan.app.ui.charts.DonutSlice
import com.keuangan.app.ui.charts.IncomeExpenseBarChart
import com.keuangan.app.ui.charts.LineDatum
import com.keuangan.app.ui.charts.TrendLineChart
import com.keuangan.app.ui.formatRupiah
import com.keuangan.app.ui.formatShortDate
import com.keuangan.app.ui.healthLabel
import com.keuangan.app.ui.theme.Amber600
import com.keuangan.app.ui.theme.Red600
import com.keuangan.app.ui.theme.Teal700

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun DashboardScreen(
    viewModel: DashboardViewModel,
    onOpenTransactions: () -> Unit,
    onOpenCategories: () -> Unit,
    onLogout: () -> Unit,
) {
    val state by viewModel.state.collectAsState()
    val data = state.data

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Ringkasan", fontWeight = FontWeight.SemiBold) },
                actions = {
                    IconButton(onClick = viewModel::refresh) {
                        Icon(Icons.Filled.Refresh, contentDescription = "Muat ulang")
                    }
                    IconButton(onClick = onLogout) {
                        Icon(Icons.Filled.Logout, contentDescription = "Keluar")
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = MaterialTheme.colorScheme.background,
                ),
            )
        },
    ) { padding ->
        when {
            state.loading && data == null -> Box(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(padding),
                contentAlignment = Alignment.Center,
            ) { CircularProgressIndicator() }

            state.error != null && data == null -> ErrorState(
                message = state.error!!,
                onRetry = viewModel::refresh,
                modifier = Modifier
                    .fillMaxSize()
                    .padding(padding),
            )

            else -> LazyColumn(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(padding),
                contentPadding = PaddingValues(16.dp),
                verticalArrangement = Arrangement.spacedBy(16.dp),
            ) {
                item { PeriodRow(selected = state.period, onSelect = viewModel::setPeriod) }

                if (state.refreshing) {
                    item { LinearProgressIndicator(Modifier.fillMaxWidth()) }
                }

                item {
                    SummaryCard(
                        balance = data?.stats?.balance ?: 0.0,
                        income = data?.stats?.totalIncome ?: 0.0,
                        expense = data?.stats?.totalExpense ?: 0.0,
                        savingsRate = data?.stats?.savingsRate ?: 0.0,
                        health = data?.insights?.health,
                        periodLabel = data?.period?.label.orEmpty(),
                    )
                }

                item {
                    ChartCard(title = "Tren 6 Bulan") {
                        if (data == null) {
                            Box(Modifier.height(160.dp))
                        } else {
                            IncomeExpenseBarChart(
                                data = data.monthly.map {
                                    BarDatum(it.label, it.income, it.expense)
                                },
                                incomeColor = Teal700,
                                expenseColor = Amber600,
                            )
                            Spacer(Modifier.height(10.dp))
                            ChartLegend(
                                listOf("Pemasukan" to Teal700, "Pengeluaran" to Amber600),
                            )
                        }
                    }
                }

                item {
                    ChartCard(title = "Pengeluaran per Kategori") {
                        val slices = (data?.expenseByCategory ?: emptyList())
                            .filter { it.total > 0 }
                            .map {
                                DonutSlice(it.name, it.total, parseColor(it.color, Amber600))
                            }
                        if (slices.isEmpty()) {
                            Text(
                                "Belum ada pengeluaran pada periode ini.",
                                style = MaterialTheme.typography.bodyMedium,
                                color = MaterialTheme.colorScheme.onSurfaceVariant,
                            )
                        } else {
                            Row(verticalAlignment = Alignment.CenterVertically) {
                                CategoryDonutChart(
                                    slices = slices,
                                    centerLabel = formatRupiah(slices.sumOf { it.value }),
                                )
                                Spacer(Modifier.width(12.dp))
                                CategoryList(
                                    data?.expenseByCategory.orEmpty().take(5),
                                )
                            }
                        }
                    }
                }

                item {
                    ChartCard(title = "Pengeluaran Harian") {
                        val daily = data?.daily.orEmpty().filter { it.expense > 0 }
                        if (daily.isEmpty()) {
                            Text(
                                "Belum ada pengeluaran harian.",
                                style = MaterialTheme.typography.bodyMedium,
                                color = MaterialTheme.colorScheme.onSurfaceVariant,
                            )
                        } else {
                            TrendLineChart(
                                data = daily.map { LineDatum(it.date, it.expense) },
                                lineColor = Red600,
                            )
                        }
                    }
                }

                item {
                    Card(
                        onClick = viewModel::loadAdvice,
                        colors = CardDefaults.cardColors(
                            containerColor = MaterialTheme.colorScheme.primaryContainer,
                        ),
                        modifier = Modifier.fillMaxWidth(),
                    ) {
                        Row(
                            modifier = Modifier.padding(16.dp),
                            verticalAlignment = Alignment.CenterVertically,
                        ) {
                            Icon(
                                Icons.Filled.Psychology,
                                contentDescription = null,
                                tint = MaterialTheme.colorScheme.onPrimaryContainer,
                            )
                            Spacer(Modifier.width(12.dp))
                            Column(Modifier.weight(1f)) {
                                Text(
                                    "Saran AI",
                                    fontWeight = FontWeight.SemiBold,
                                    color = MaterialTheme.colorScheme.onPrimaryContainer,
                                )
                                Text(
                                    "Dapatkan analisis keuanganmu",
                                    style = MaterialTheme.typography.bodySmall,
                                    color = MaterialTheme.colorScheme.onPrimaryContainer,
                                )
                            }
                            if (state.adviceLoading) {
                                CircularProgressIndicator(
                                    modifier = Modifier.size(18.dp),
                                    strokeWidth = 2.dp,
                                )
                            }
                        }
                    }
                }

                item {
                    Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                        QuickAction(
                            icon = Icons.Filled.ReceiptLong,
                            label = "Transaksi",
                            onClick = onOpenTransactions,
                            modifier = Modifier.weight(1f),
                        )
                        QuickAction(
                            icon = Icons.Filled.Category,
                            label = "Kategori",
                            onClick = onOpenCategories,
                            modifier = Modifier.weight(1f),
                        )
                    }
                }

                data?.insights?.biggestExpense?.let { biggest ->
                    item {
                        ChartCard(title = "Pengeluaran Terbesar") {
                            Text(
                                biggest.description ?: "Tanpa keterangan",
                                fontWeight = FontWeight.SemiBold,
                            )
                            Spacer(Modifier.height(2.dp))
                            Text(
                                "${formatRupiah(biggest.amount)} • ${formatShortDate(biggest.date)}",
                                style = MaterialTheme.typography.bodyMedium,
                                color = MaterialTheme.colorScheme.onSurfaceVariant,
                            )
                        }
                    }
                }

                if (!data?.recent.isNullOrEmpty()) {
                    item {
                        ChartCard(title = "Transaksi Terbaru") {
                            data?.recent.orEmpty().take(5).forEach { transaction ->
                                RecentRow(transaction)
                                Spacer(Modifier.height(8.dp))
                            }
                            TextButton(onClick = onOpenTransactions) {
                                Text("Lihat semua")
                            }
                        }
                    }
                }
            }
        }
    }

    state.advice?.let { advice ->
        AlertDialog(
            onDismissRequest = viewModel::dismissAdvice,
            confirmButton = { TextButton(onClick = viewModel::dismissAdvice) { Text("Tutup") } },
            title = { Text("Saran AI") },
            text = { Text(advice) },
        )
    }
}

@Composable
private fun PeriodRow(selected: String, onSelect: (String) -> Unit) {
    LazyRow(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
        items(PERIODS) { (value, label) ->
            FilterChip(
                selected = selected == value,
                onClick = { onSelect(value) },
                label = { Text(label) },
            )
        }
    }
}

@Composable
private fun SummaryCard(
    balance: Double,
    income: Double,
    expense: Double,
    savingsRate: Double,
    health: String?,
    periodLabel: String,
) {
    Card(
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
        modifier = Modifier.fillMaxWidth(),
    ) {
        Column(Modifier.padding(16.dp)) {
            Text(
                periodLabel.ifBlank { "Ringkasan" },
                style = MaterialTheme.typography.labelMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
            )
            Spacer(Modifier.height(4.dp))
            Text(
                formatRupiah(balance),
                style = MaterialTheme.typography.headlineSmall,
                fontWeight = FontWeight.Bold,
                color = if (balance >= 0) Teal700 else Red600,
            )
            Spacer(Modifier.height(12.dp))
            Row(horizontalArrangement = Arrangement.spacedBy(20.dp)) {
                Stat(
                    label = "Pemasukan",
                    value = income,
                    color = Teal700,
                    icon = Icons.AutoMirrored.Filled.TrendingUp,
                )
                Stat(
                    label = "Pengeluaran",
                    value = expense,
                    color = Amber600,
                    icon = Icons.AutoMirrored.Filled.TrendingDown,
                )
            }
            if (income > 0) {
                Spacer(Modifier.height(14.dp))
                Text(
                    "Tingkat tabungan ${"%.1f".format(savingsRate)}% • ${healthLabel(health)}",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                )
            }
        }
    }
}

@Composable
private fun Stat(
    label: String,
    value: Double,
    color: Color,
    icon: androidx.compose.ui.graphics.vector.ImageVector,
) {
    Row(verticalAlignment = Alignment.CenterVertically) {
        Icon(icon, contentDescription = null, tint = color, modifier = Modifier.size(18.dp))
        Spacer(Modifier.width(6.dp))
        Column {
            Text(label, style = MaterialTheme.typography.labelSmall)
            Text(
                formatRupiah(value),
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.SemiBold,
            )
        }
    }
}

@Composable
private fun ChartCard(title: String, content: @Composable () -> Unit) {
    Card(
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
        modifier = Modifier.fillMaxWidth(),
    ) {
        Column(Modifier.padding(16.dp)) {
            Text(title, style = MaterialTheme.typography.titleMedium)
            Spacer(Modifier.height(12.dp))
            content()
        }
    }
}

@Composable
private fun RowScope.CategoryList(data: List<CategoryBreakdownDto>) {
    Column(Modifier.weight(1f)) {
        data.forEach { category ->
            Row(
                verticalAlignment = Alignment.CenterVertically,
                modifier = Modifier.padding(vertical = 3.dp),
            ) {
                Box(
                    Modifier
                        .size(10.dp)
                        .clip(CircleShape)
                        .background(parseColor(category.color, Amber600)),
                )
                Spacer(Modifier.width(8.dp))
                Column(Modifier.weight(1f)) {
                    Text(
                        category.name,
                        style = MaterialTheme.typography.bodyMedium,
                        maxLines = 1,
                    )
                    Text(
                        "${"%.0f".format(category.share)}%",
                        style = MaterialTheme.typography.labelSmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                    )
                }
                Text(
                    formatRupiah(category.total),
                    style = MaterialTheme.typography.bodyMedium,
                    fontWeight = FontWeight.Medium,
                )
            }
        }
    }
}

@Composable
private fun QuickAction(
    icon: androidx.compose.ui.graphics.vector.ImageVector,
    label: String,
    onClick: () -> Unit,
    modifier: Modifier = Modifier,
) {
    Card(
        modifier = modifier.clickable(onClick = onClick),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
    ) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(vertical = 18.dp),
            horizontalAlignment = Alignment.CenterHorizontally,
        ) {
            Icon(icon, contentDescription = null, tint = MaterialTheme.colorScheme.primary)
            Spacer(Modifier.height(6.dp))
            Text(label, style = MaterialTheme.typography.bodyMedium)
        }
    }
}

@Composable
private fun RecentRow(transaction: TransactionDto) {
    Row(verticalAlignment = Alignment.CenterVertically) {
        Column(Modifier.weight(1f)) {
            Text(
                transaction.description?.ifBlank { transaction.category?.name ?: "Transaksi" }
                    ?: "Transaksi",
                style = MaterialTheme.typography.bodyMedium,
                maxLines = 1,
            )
            Text(
                "${transaction.category?.name ?: "Tanpa kategori"} • ${formatShortDate(transaction.date)}",
                style = MaterialTheme.typography.labelSmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
            )
        }
        Text(
            (if (transaction.type == "income") "+" else "-") + formatRupiah(transaction.amount),
            style = MaterialTheme.typography.bodyMedium,
            fontWeight = FontWeight.SemiBold,
            color = if (transaction.type == "income") Teal700 else Red600,
        )
    }
}

@Composable
private fun ErrorState(message: String, onRetry: () -> Unit, modifier: Modifier = Modifier) {
    Column(
        modifier = modifier.padding(32.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.Center,
    ) {
        Text("Gagal memuat data", style = MaterialTheme.typography.titleMedium)
        Spacer(Modifier.height(6.dp))
        Text(
            message,
            style = MaterialTheme.typography.bodyMedium,
            color = MaterialTheme.colorScheme.onSurfaceVariant,
        )
        Spacer(Modifier.height(16.dp))
        Button(onClick = onRetry) { Text("Coba lagi") }
    }
}

private fun parseColor(hex: String?, fallback: Color): Color = runCatching {
    Color(android.graphics.Color.parseColor(hex))
}.getOrDefault(fallback)
