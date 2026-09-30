package com.keuangan.app.ui.family

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.unit.dp
import com.keuangan.app.ui.components.GradientHeader
import com.keuangan.app.ui.components.KangCuanTipCard
import com.keuangan.app.ui.formatRupiah
import com.keuangan.app.ui.theme.Amber100
import com.keuangan.app.ui.theme.Amber600
import com.keuangan.app.ui.theme.Teal700

/**
 * Markup presets. "Rendah" is the floor that still doesn't sell at a loss,
 * "Menengah" is a healthy retail margin, "Mahal" leaves room for negotiation.
 */
private data class SaleTier(val label: String, val markupPercent: Int)

private val SALE_TIERS = listOf(
    SaleTier("Rendah", 10),
    SaleTier("Menengah", 20),
    SaleTier("Mahal", 30),
)

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun HppCalculatorScreen(onBack: () -> Unit) {
    var totalCost by rememberSaveable { mutableStateOf("") }
    var quantity by rememberSaveable { mutableStateOf("") }

    // Costs the seller rarely remembers to include: packaging, shipping and a
    // small allowance for spoilage/wrong guesses. Better to over-count than to
    // sell below cost and discover it at month end.
    var includePackaging by rememberSaveable { mutableStateOf(true) }
    var includeShipping by rememberSaveable { mutableStateOf(true) }
    var wastePercent by rememberSaveable { mutableStateOf("5") }

    var packagingCost by rememberSaveable { mutableStateOf("") }
    var shippingCost by rememberSaveable { mutableStateOf("") }

    val cost = parseRupiah(totalCost)
    val qty = parseRupiah(quantity)
    val packaging = if (includePackaging) parseRupiah(packagingCost) else 0.0
    val shipping = if (includeShipping) parseRupiah(shippingCost) else 0.0
    val waste = (cost * parseRupiah(wastePercent) / 100.0)

    val totalAllIn = cost + packaging + shipping + waste
    val hpp = if (qty > 0) totalAllIn / qty else 0.0
    val hasInput = cost > 0 && qty > 0

    Scaffold(
        topBar = {
            GradientHeader(
                title = "Kalkulator HPP",
                subtitle = "Harga pokok per unit & saran harga jual",
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
        LazyColumn(
            modifier = Modifier
                .fillMaxSize()
                .padding(padding),
            contentPadding = PaddingValues(16.dp, 12.dp, 16.dp, 32.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp),
        ) {
            item {
                Card(
                    modifier = Modifier.fillMaxWidth(),
                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                    elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
                ) {
                    Column(Modifier.padding(14.dp)) {
                        Text(
                            "Biaya produksi",
                            style = MaterialTheme.typography.titleSmall,
                            fontWeight = FontWeight.SemiBold,
                        )
                        Spacer(Modifier.height(10.dp))
                        OutlinedTextField(
                            value = totalCost,
                            onValueChange = { totalCost = it.filterNumeric() },
                            label = { Text("Total biaya buat 1 batch") },
                            singleLine = true,
                            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                            modifier = Modifier.fillMaxWidth(),
                        )
                        Spacer(Modifier.height(10.dp))
                        OutlinedTextField(
                            value = quantity,
                            onValueChange = { quantity = it.filterNumeric() },
                            label = { Text("Jumlah produk jadi") },
                            singleLine = true,
                            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                            modifier = Modifier.fillMaxWidth(),
                        )
                    }
                }
            }

            item {
                Card(
                    modifier = Modifier.fillMaxWidth(),
                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                    elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
                ) {
                    Column(Modifier.padding(14.dp)) {
                        Text(
                            "Biaya yang sering terlewat",
                            style = MaterialTheme.typography.titleSmall,
                            fontWeight = FontWeight.SemiBold,
                        )
                        Spacer(Modifier.height(4.dp))
                        Text(
                            "Kalau ada, masukkan juga. Ini yang sering bikin harga jual sebenarnya lebih rendah dari perkiraan.",
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                        )
                        Spacer(Modifier.height(10.dp))
                        OutlinedTextField(
                            value = packagingCost,
                            onValueChange = { packagingCost = it.filterNumeric() },
                            label = { Text("Kemasan per batch") },
                            singleLine = true,
                            enabled = includePackaging,
                            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                            modifier = Modifier.fillMaxWidth(),
                        )
                        Spacer(Modifier.height(10.dp))
                        OutlinedTextField(
                            value = shippingCost,
                            onValueChange = { shippingCost = it.filterNumeric() },
                            label = { Text("Ongkir per batch") },
                            singleLine = true,
                            enabled = includeShipping,
                            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                            modifier = Modifier.fillMaxWidth(),
                        )
                        Spacer(Modifier.height(10.dp))
                        OutlinedTextField(
                            value = wastePercent,
                            onValueChange = { wastePercent = it.filterNumeric() },
                            label = { Text("Sisipan untuk cacat / tak laku (%)") },
                            singleLine = true,
                            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                            modifier = Modifier.fillMaxWidth(),
                        )
                    }
                }
            }

            item {
                Card(
                    modifier = Modifier.fillMaxWidth(),
                    colors = CardDefaults.cardColors(containerColor = Teal700),
                ) {
                    Column(Modifier.padding(14.dp)) {
                        Text(
                            "Harga pokok per unit",
                            style = MaterialTheme.typography.labelMedium,
                            color = Color.White.copy(alpha = 0.85f),
                        )
                        Spacer(Modifier.height(2.dp))
                        Text(
                            if (hasInput) formatRupiah(hpp) else "â€”",
                            style = MaterialTheme.typography.headlineSmall,
                            fontWeight = FontWeight.Bold,
                            color = Color.White,
                        )
                        if (hasInput) {
                            Spacer(Modifier.height(2.dp))
                            Text(
                                "Total biaya masuk ${formatRupiah(totalAllIn)} untuk $qty unit",
                                style = MaterialTheme.typography.bodySmall,
                                color = Color.White.copy(alpha = 0.85f),
                            )
                        }
                    }
                }
            }

            if (hasInput) {
                item {
                    Text(
                        "Saran harga jual",
                        style = MaterialTheme.typography.titleSmall,
                        fontWeight = FontWeight.SemiBold,
                    )
                }
                items(SALE_TIERS, key = { it.label }) { tier ->
                    val price = hpp * (1 + tier.markupPercent / 100.0)
                    SaleTierCard(
                        tier = tier,
                        hpp = hpp,
                        price = price,
                        highlight = tier.markupPercent == 20,
                    )
                }
                item {
                    Text(
                        "Belum termasuk biaya hidup dan pajak. Kalau harga jualnya terasa terlalu dekat dengan HPP, biasanya mark-up-nya belum cukup untuk menutup ongkos hidup.",
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                    )
                }
            } else {
                item {
                    EmptyHint(
                        "Isi total biaya dan jumlah produk dulu, nanti HPP dan saran harga jualnya muncul di sini.",
                    )
                }
            }

            item {
                KangCuanTipCard(
                    message = "Banyak yang jago hitung HPP tapi lupa biaya kemasan dan ongkir. Masukkan dua angka itu, dan harga jual yang kamu pakai biasanya naik 10-20%.",
                )
            }
        }
    }
}

@Composable
private fun SaleTierCard(
    tier: SaleTier,
    hpp: Double,
    price: Double,
    highlight: Boolean,
) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(
            containerColor = if (highlight) Amber100 else MaterialTheme.colorScheme.surface,
        ),
        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
    ) {
        Row(
            Modifier.padding(14.dp),
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Column(Modifier.weight(1f)) {
                Text(
                    tier.label,
                    style = MaterialTheme.typography.titleSmall,
                    fontWeight = FontWeight.SemiBold,
                )
                Text(
                    "Markup ${tier.markupPercent}% di atas HPP",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                )
                if (highlight) {
                    Text(
                        "Paling aman untuk pemula",
                        style = MaterialTheme.typography.labelSmall,
                        color = Amber600,
                    )
                }
            }
            Column(horizontalAlignment = Alignment.End) {
                Text(
                    formatRupiah(price),
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.Bold,
                    color = Teal700,
                )
                Text(
                    "Laba ${formatRupiah(price - hpp)}/unit",
                    style = MaterialTheme.typography.labelSmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                )
            }
        }
    }
}

@Composable
private fun EmptyHint(message: String) {
    Column(
        modifier = Modifier
            .fillMaxWidth()
            .padding(vertical = 20.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
    ) {
        Text(
            message,
            style = MaterialTheme.typography.bodyMedium,
            color = MaterialTheme.colorScheme.onSurfaceVariant,
        )
    }
}

/** Digits only; "." is allowed once so "12.5" survives on a numeric keyboard. */
private fun String.filterNumeric(): String {
    var seenDot = false
    return filter { ch ->
        when {
            ch.isDigit() -> true
            ch == '.' && !seenDot -> { seenDot = true; true }
            else -> false
        }
    }
}

/** Accepts "12000" and "12,500" â€” a comma thousands separator is still common here. */
private fun parseRupiah(raw: String): Double {
    if (raw.isBlank()) return 0.0
    return raw.replace(",", "").toDoubleOrNull() ?: 0.0
}