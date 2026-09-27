package com.keuangan.app.ui.charts

import androidx.compose.foundation.Canvas
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.size
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.geometry.Offset
import androidx.compose.ui.geometry.Size
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.Path
import androidx.compose.ui.graphics.drawscope.DrawScope
import androidx.compose.ui.graphics.drawscope.Stroke
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import kotlin.math.max

data class BarDatum(val label: String, val income: Double, val expense: Double)

/** Grouped income vs expense bars. Zero-filled months simply render as nothing. */
@Composable
fun IncomeExpenseBarChart(
    data: List<BarDatum>,
    incomeColor: Color,
    expenseColor: Color,
    modifier: Modifier = Modifier,
) {
    if (data.isEmpty()) {
        EmptyChart(modifier)
        return
    }

    val maxValue = max(
        data.maxOf { max(it.income, it.expense) },
        1.0,
    )

    Column(modifier = modifier) {
        Canvas(
            modifier = Modifier
                .fillMaxWidth()
                .height(160.dp),
        ) {
            val groupWidth = size.width / data.size
            val barWidth = (groupWidth * 0.28f).coerceAtLeast(2f)
            val gap = barWidth * 0.25f

            data.forEachIndexed { index, datum ->
                val baseX = index * groupWidth + (groupWidth - (barWidth * 2 + gap)) / 2f
                drawBar(
                    x = baseX,
                    width = barWidth,
                    value = datum.income,
                    maxValue = maxValue,
                    color = incomeColor,
                )
                drawBar(
                    x = baseX + barWidth + gap,
                    width = barWidth,
                    value = datum.expense,
                    maxValue = maxValue,
                    color = expenseColor,
                )
            }

            // Baseline
            drawLine(
                color = incomeColor.copy(alpha = 0.18f),
                start = Offset(0f, size.height),
                end = Offset(size.width, size.height),
                strokeWidth = 1.5f,
            )
        }

        Spacer(Modifier.height(6.dp))
        Row(Modifier.fillMaxWidth()) {
            data.forEach { datum ->
                Text(
                    text = datum.label,
                    style = MaterialTheme.typography.labelSmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                    modifier = Modifier.weight(1f),
                    textAlign = androidx.compose.ui.text.style.TextAlign.Center,
                )
            }
        }
    }
}

private fun DrawScope.drawBar(x: Float, width: Float, value: Double, maxValue: Double, color: Color) {
    if (value <= 0.0) return
    val fraction = (value / maxValue).toFloat().coerceIn(0f, 1f)
    val barHeight = size.height * fraction
    drawRect(
        color = color,
        topLeft = Offset(x, size.height - barHeight),
        size = Size(width, barHeight),
    )
}

data class DonutSlice(val label: String, val value: Double, val color: Color)

/** Donut with the total in the middle. */
@Composable
fun CategoryDonutChart(
    slices: List<DonutSlice>,
    centerLabel: String,
    modifier: Modifier = Modifier,
) {
    val total = slices.sumOf { it.value }
    val track = MaterialTheme.colorScheme.surfaceVariant

    if (total <= 0.0) {
        EmptyChart(modifier)
        return
    }

    Box(
        modifier = modifier.size(180.dp),
        contentAlignment = Alignment.Center,
    ) {
        Canvas(modifier = Modifier.size(180.dp)) {
            val stroke = 34f
            val inset = stroke / 2f
            val arcSize = Size(size.width - stroke, size.height - stroke)
            val topLeft = Offset(inset, inset)

            drawArc(
                color = track,
                startAngle = 0f,
                sweepAngle = 360f,
                useCenter = false,
                topLeft = topLeft,
                size = arcSize,
                style = Stroke(width = stroke),
            )

            var startAngle = -90f
            slices.forEach { slice ->
                val sweep = (slice.value / total * 360.0).toFloat()
                if (sweep <= 0f) return@forEach
                drawArc(
                    color = slice.color,
                    startAngle = startAngle,
                    sweepAngle = sweep,
                    useCenter = false,
                    topLeft = topLeft,
                    size = arcSize,
                    style = Stroke(width = stroke),
                )
                startAngle += sweep
            }
        }
        Column(horizontalAlignment = Alignment.CenterHorizontally) {
            Text(
                text = "Total",
                style = MaterialTheme.typography.labelSmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
            )
            Text(
                text = centerLabel,
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.SemiBold,
            )
        }
    }
}

data class LineDatum(val label: String, val value: Double)

@Composable
fun TrendLineChart(
    data: List<LineDatum>,
    lineColor: Color,
    modifier: Modifier = Modifier,
) {
    if (data.size < 2) {
        EmptyChart(modifier)
        return
    }

    val maxValue = max(data.maxOf { it.value }, 1.0)

    Canvas(
        modifier = modifier
            .fillMaxWidth()
            .height(150.dp),
    ) {
        val stepX = size.width / (data.size - 1)
        val path = Path()
        data.forEachIndexed { index, datum ->
            val x = index * stepX
            val y = size.height - (datum.value / maxValue).toFloat() * size.height
            if (index == 0) path.moveTo(x, y) else path.lineTo(x, y)
        }
        drawPath(path, color = lineColor, style = Stroke(width = 3f))
        data.forEachIndexed { index, datum ->
            val x = index * stepX
            val y = size.height - (datum.value / maxValue).toFloat() * size.height
            drawCircle(color = lineColor, radius = 4f, center = Offset(x, y))
        }
    }
}

@Composable
fun ChartLegend(entries: List<Pair<String, Color>>) {
    Row(
        modifier = Modifier.fillMaxWidth(),
        horizontalArrangement = Arrangement.spacedBy(16.dp),
    ) {
        entries.forEach { (label, color) ->
            Row(verticalAlignment = Alignment.CenterVertically) {
                Box(
                    modifier = Modifier
                        .size(10.dp)
                        .background(color, androidx.compose.foundation.shape.CircleShape),
                )
                Spacer(Modifier.size(6.dp))
                Text(
                    text = label,
                    style = MaterialTheme.typography.labelSmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                )
            }
        }
    }
}

@Composable
private fun EmptyChart(modifier: Modifier = Modifier) {
    Box(
        modifier = modifier
            .fillMaxWidth()
            .height(140.dp),
        contentAlignment = Alignment.Center,
    ) {
        Text(
            text = "Belum ada data",
            style = MaterialTheme.typography.bodyMedium,
            color = MaterialTheme.colorScheme.onSurfaceVariant,
        )
    }
}
