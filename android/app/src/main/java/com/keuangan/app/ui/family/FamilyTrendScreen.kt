package com.keuangan.app.ui.family

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.keuangan.app.data.TrendPointDto
import com.keuangan.app.ui.components.GradientHeader
import com.keuangan.app.ui.formatRupiah
import com.keuangan.app.ui.theme.Red600
import com.keuangan.app.ui.theme.Teal700

@Composable
fun FamilyTrendScreen(
    familyId: Int,
    viewModel: FamilyTrendViewModel,
) {
    val state by viewModel.state.collectAsState()

    LaunchedEffect(familyId) {
        viewModel.load(familyId)
    }

    Column(
        modifier = Modifier
            .fillMaxSize()
            .verticalScroll(rememberScrollState()),
    ) {
        GradientHeader(
            title = "Tren Keluarga",
            subtitle = "Pemasukan vs pengeluaran, 6 bulan terakhir",
        )

        when {
            state.loading -> Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                CircularProgressIndicator()
            }
            state.error != null -> Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                Column(horizontalAlignment = Alignment.CenterHorizontally) {
                    Text(state.error!!, color = MaterialTheme.colorScheme.error)
                    Spacer(Modifier.height(12.dp))
                    androidx.compose.material3.Button(onClick = { viewModel.retry(familyId) }) {
                        Text("Coba lagi")
                    }
                }
            }
            state.points.isEmpty() -> Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                Text(
                    "Belum ada data. Catat pemasukan dan pengeluaran dulu ya.",
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                )
            }
            else -> TrendContent(points = state.points)
        }
    }
}

@Composable
private fun TrendContent(points: List<TrendPointDto>) {
    val totalIncome = points.sumOf { it.income }
    val totalExpense = points.sumOf { it.expense }
    val allTime = totalIncome - totalExpense
    val maxValue = maxOf(points.maxOfOrNull { it.income } ?: 0.0, points.maxOfOrNull { it.expense } ?: 0.0, 1.0)

    Column(Modifier.padding(16.dp)) {
        Row(horizontalArrangement = Arrangement.spacedBy(10.dp)) {
            SummaryChip("Pemasukan", formatRupiah(totalIncome), Teal700, Modifier.weight(1f))
            SummaryChip("Pengeluaran", formatRupiah(totalExpense), Red600, Modifier.weight(1f))
            SummaryChip(
                "Selisih",
                formatRupiah(allTime),
                if (allTime >= 0) Teal700 else Red600,
                Modifier.weight(1f),
            )
        }

        Spacer(Modifier.height(20.dp))

        Card(
            colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
            elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
            modifier = Modifier.fillMaxWidth(),
        ) {
            Column(
                Modifier
                    .fillMaxWidth()
                    .padding(12.dp),
                horizontalAlignment = Alignment.CenterHorizontally,
            ) {
                Row(
                    Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceEvenly,
                    verticalAlignment = Alignment.Bottom,
                ) {
                    points.forEach { point ->
                        TrendBar(point = point, maxValue = maxValue, modifier = Modifier.weight(1f))
                    }
                }

                Spacer(Modifier.height(16.dp))

                Column(horizontalAlignment = Alignment.CenterHorizontally) {
                    Row(horizontalArrangement = Arrangement.spacedBy(16.dp)) {
                        LegendDot(Teal700)
                        Text("Pemasukan", style = MaterialTheme.typography.labelSmall)
                        LegendDot(Red600)
                        Text("Pengeluaran", style = MaterialTheme.typography.labelSmall)
                    }
                    Spacer(Modifier.height(6.dp))
                    Text(
                        "Total selisih: ${formatRupiah(allTime)}",
                        style = MaterialTheme.typography.labelMedium,
                        color = if (allTime >= 0) Teal700 else Red600,
                        fontWeight = FontWeight.SemiBold,
                    )
                }
            }
        }
    }
}

@Composable
private fun SummaryChip(label: String, value: String, color: androidx.compose.ui.graphics.Color, modifier: Modifier) {
    Card(
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
        modifier = modifier,
    ) {
        Column(
            Modifier
                .fillMaxWidth()
                .padding(12.dp),
            horizontalAlignment = Alignment.CenterHorizontally,
        ) {
            Text(
                label,
                style = MaterialTheme.typography.labelSmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
            )
            Spacer(Modifier.height(4.dp))
            Text(
                value,
                style = MaterialTheme.typography.titleSmall,
                fontWeight = FontWeight.SemiBold,
                color = color,
                maxLines = 1,
            )
        }
    }
}

private const val CHART_HEIGHT = 132f

@Composable
private fun TrendBar(point: TrendPointDto, maxValue: Double, modifier: Modifier = Modifier) {
    val incomeH = (point.income / maxValue * CHART_HEIGHT).toFloat().dp
    val expenseH = (point.expense / maxValue * CHART_HEIGHT).toFloat().dp

    Column(modifier, horizontalAlignment = Alignment.CenterHorizontally) {
        Text(
            (point.net.takeIf { it != 0.0 })?.let { if (it > 0) "+" else "-" } ?: "",
            style = MaterialTheme.typography.labelSmall,
            color = if (point.net >= 0) Teal700 else Red600,
        )
        Spacer(Modifier.height(2.dp))
        Row(
            verticalAlignment = Alignment.Bottom,
            horizontalArrangement = Arrangement.spacedBy(3.dp),
        ) {
            VerticalBar(height = incomeH, color = Teal700)
            VerticalBar(height = expenseH, color = Red600)
        }
        Spacer(Modifier.height(6.dp))
        Text(point.label, style = MaterialTheme.typography.labelSmall)
    }
}

@Composable
private fun VerticalBar(height: androidx.compose.ui.unit.Dp, color: androidx.compose.ui.graphics.Color) {
    Box(
        Modifier
            .width(9.dp)
            .height(height.coerceAtLeast(2.dp))
            .background(color, RoundedCornerShape(topStart = 3.dp, topEnd = 3.dp)),
    )
}

@Composable
private fun LegendDot(color: androidx.compose.ui.graphics.Color) {
    Box(
        Modifier
            .size(8.dp)
            .background(color, RoundedCornerShape(4.dp)),
    )
}