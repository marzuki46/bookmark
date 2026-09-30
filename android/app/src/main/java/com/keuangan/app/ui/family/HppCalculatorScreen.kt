package com.keuangan.app.ui.family

import androidx.compose.foundation.background
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
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.AutoAwesome
import androidx.compose.material.icons.filled.Build
import androidx.compose.material.icons.filled.Delete
import androidx.compose.material.icons.filled.Inventory2
import androidx.compose.material3.Button
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableLongStateOf
import androidx.compose.runtime.mutableStateListOf
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.saveable.Saver
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.runtime.setValue
import androidx.compose.runtime.snapshots.SnapshotStateList
import androidx.compose.runtime.toMutableStateList
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import com.keuangan.app.data.PricingResponse
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

/** A consumable or a tool that goes into the cost of one batch. */
private enum class HppKind { BAHAN, ALAT }

/**
 * One line of the cost breakdown.
 *
 * [qty] and [price] are per batch for a BAHAN line (2 kg x Rp 15.000). For an
 * ALAT line they describe the tool itself, and [usesPerBatch] divides it so a
 * Rp 600.000 blender that lasts 12 batches only charges Rp 50.000 to this one.
 */
private data class HppLine(
    val id: Long,
    val kind: HppKind,
    val name: String,
    val qty: Double,
    val price: Double,
    val usesPerBatch: Double,
) {
    /** What this line contributes to a single batch. */
    val batchCost: Double
        get() = when (kind) {
            HppKind.BAHAN -> qty * price
            HppKind.ALAT -> if (usesPerBatch > 0) price / usesPerBatch else 0.0
        }
}

/**
 * Lines survive rotation via a flat saver: every field is a primitive, so the
 * whole list can ride in the bundle without needing a Parcelable.
 */
private val hppLinesSaver: Saver<SnapshotStateList<HppLine>, List<Any>> = Saver(
    save = { lines ->
        lines.flatMap { listOf(it.id, it.kind.name, it.name, it.qty, it.price, it.usesPerBatch) }
    },
    restore = { flat ->
        flat.chunked(6)
            .map {
                HppLine(
                    id = it[0] as Long,
                    kind = HppKind.valueOf(it[1] as String),
                    name = it[2] as String,
                    qty = it[3] as Double,
                    price = it[4] as Double,
                    usesPerBatch = it[5] as Double,
                )
            }
            .toMutableStateList()
    },
)

private fun newLine(kind: HppKind, id: Long) = HppLine(
    id = id,
    kind = kind,
    name = "",
    qty = 0.0,
    price = 0.0,
    usesPerBatch = 1.0,
)

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun HppCalculatorScreen(onBack: () -> Unit, viewModel: HppViewModel) {
    // Itemised inputs: what goes in, how much, and what the tools cost. The
    // single "total biaya" box was the complaint — a seller cannot sanity-check
    // a total they never had to build up.
    val lines = rememberSaveable(saver = hppLinesSaver) { mutableStateListOf<HppLine>() }
    var nextId by rememberSaveable { mutableLongStateOf(1L) }

    var quantity by rememberSaveable { mutableStateOf("") }
    var productName by rememberSaveable { mutableStateOf("") }
    var competitionPrice by rememberSaveable { mutableStateOf("") }

    // Costs the seller rarely remembers to include: packaging, shipping and a
    // small allowance for spoilage/wrong guesses. Better to over-count than to
    // sell below cost and discover it at month end.
    var includePackaging by rememberSaveable { mutableStateOf(true) }
    var includeShipping by rememberSaveable { mutableStateOf(true) }
    var wastePercent by rememberSaveable { mutableStateOf("5") }

    var packagingCost by rememberSaveable { mutableStateOf("") }
    var shippingCost by rememberSaveable { mutableStateOf("") }

    val pricing by viewModel.pricing.collectAsState()
    val pricingLoading by viewModel.loading.collectAsState()
    val pricingError by viewModel.error.collectAsState()

    val materialsCost = lines.filter { it.kind == HppKind.BAHAN }.sumOf { it.batchCost }
    val toolsCost = lines.filter { it.kind == HppKind.ALAT }.sumOf { it.batchCost }
    val cost = materialsCost + toolsCost
    val qty = parseRupiah(quantity)
    val packaging = if (includePackaging) parseRupiah(packagingCost) else 0.0
    val shipping = if (includeShipping) parseRupiah(shippingCost) else 0.0
    val waste = (cost * parseRupiah(wastePercent) / 100.0)

    val totalAllIn = cost + packaging + shipping + waste
    val hpp = if (qty > 0) totalAllIn / qty else 0.0
    val hasInput = cost > 0 && qty > 0

    fun update(id: Long, transform: (HppLine) -> HppLine) {
        val index = lines.indexOfFirst { it.id == id }
        if (index >= 0) lines[index] = transform(lines[index])
    }

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
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Text(
                                "Bahan yang dipakai",
                                style = MaterialTheme.typography.titleSmall,
                                fontWeight = FontWeight.SemiBold,
                                modifier = Modifier.weight(1f),
                            )
                            TextButton(
                                onClick = {
                                    lines.add(newLine(HppKind.BAHAN, nextId))
                                    nextId += 1
                                },
                            ) {
                                Icon(Icons.Filled.Add, contentDescription = null, modifier = Modifier.size(16.dp))
                                Spacer(Modifier.width(4.dp))
                                Text("Tambah")
                            }
                        }
                        Spacer(Modifier.height(4.dp))
                        OutlinedTextField(
                            value = productName,
                            onValueChange = { productName = it },
                            label = { Text("Nama produk yang dijual") },
                            singleLine = true,
                            modifier = Modifier.fillMaxWidth(),
                        )
                        Spacer(Modifier.height(8.dp))
                        OutlinedTextField(
                            value = quantity,
                            onValueChange = { quantity = it.filterNumeric() },
                            label = { Text("Jumlah produk jadi per batch") },
                            singleLine = true,
                            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                            modifier = Modifier.fillMaxWidth(),
                        )
                        Spacer(Modifier.height(12.dp))

                        if (lines.none { it.kind == HppKind.BAHAN }) {
                            Text(
                                "Belum ada bahan. Tap Tambah untuk isi misalnya \"Tepung 2 kg @ Rp 15.000\".",
                                style = MaterialTheme.typography.bodySmall,
                                color = MaterialTheme.colorScheme.onSurfaceVariant,
                            )
                        }
                        lines.forEach { line ->
                            if (line.kind == HppKind.BAHAN) {
                                HppLineEditor(
                                    line = line,
                                    onName = { text -> update(line.id) { it.copy(name = text) } },
                                    onQty = { text -> update(line.id) { it.copy(qty = parseRupiah(text)) } },
                                    onPrice = { text -> update(line.id) { it.copy(price = parseRupiah(text)) } },
                                    onRemove = { lines.remove(line) },
                                )
                            } else {
                                HppLineEditor(
                                    line = line,
                                    onName = { text -> update(line.id) { it.copy(name = text) } },
                                    onPrice = { text -> update(line.id) { it.copy(price = parseRupiah(text)) } },
                                    onUses = { text ->
                                        update(line.id) { it.copy(usesPerBatch = parseRupiah(text).coerceAtLeast(1.0)) }
                                    },
                                    onRemove = { lines.remove(line) },
                                )
                            }
                        }

                        Spacer(Modifier.height(8.dp))
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Text(
                                "Alat yang dipakai",
                                style = MaterialTheme.typography.titleSmall,
                                fontWeight = FontWeight.SemiBold,
                                modifier = Modifier.weight(1f),
                            )
                            TextButton(
                                onClick = {
                                    lines.add(newLine(HppKind.ALAT, nextId))
                                    nextId += 1
                                },
                            ) {
                                Icon(Icons.Filled.Add, contentDescription = null, modifier = Modifier.size(16.dp))
                                Spacer(Modifier.width(4.dp))
                                Text("Tambah")
                            }
                        }
                        Spacer(Modifier.height(4.dp))
                        Text(
                            "Alat tidak habis dipakai. Masukkan harganya sekali, lalu bagi ke beberapa batch supaya tidak membebani satu batch saja.",
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                        )
                        Spacer(Modifier.height(8.dp))
                        if (lines.none { it.kind == HppKind.ALAT }) {
                            Text(
                                "Belum ada alat yang dicatat.",
                                style = MaterialTheme.typography.bodySmall,
                                color = MaterialTheme.colorScheme.onSurfaceVariant,
                            )
                        }
                        lines.filter { it.kind == HppKind.ALAT }.forEach { line ->
                            HppLineEditor(
                                line = line,
                                onName = { text -> update(line.id) { it.copy(name = text) } },
                                onPrice = { text -> update(line.id) { it.copy(price = parseRupiah(text)) } },
                                onUses = { text ->
                                    update(line.id) { it.copy(usesPerBatch = parseRupiah(text).coerceAtLeast(1.0)) }
                                },
                                onRemove = { lines.remove(line) },
                            )
                        }
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
                            if (hasInput) formatRupiah(hpp) else "—",
                            style = MaterialTheme.typography.headlineSmall,
                            fontWeight = FontWeight.Bold,
                            color = Color.White,
                        )
                        if (hasInput) {
                            Spacer(Modifier.height(6.dp))
                            CostBreakdown(
                                materials = materialsCost,
                                tools = toolsCost,
                                packaging = packaging,
                                shipping = shipping,
                                waste = waste,
                                total = totalAllIn,
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
                    PricingAdvisor(
                        hpp = hpp,
                        quantity = qty,
                        wastePercent = parseRupiah(wastePercent),
                        product = productName,
                        competitionText = competitionPrice,
                        onCompetitionChange = { competitionPrice = it.filterNumeric() },
                        loading = pricingLoading,
                        error = pricingError,
                        recommendation = pricing,
                        onAsk = {
                            viewModel.recommend(
                                hpp = hpp,
                                quantity = qty,
                                wastePercent = parseRupiah(wastePercent),
                                product = productName,
                                competition = parseRupiah(competitionPrice),
                            )
                        },
                        onClear = viewModel::clear,
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
                        "Isi bahan, jumlah produk jadi dulu, nanti HPP dan saran harga jualnya muncul di sini.",
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

/**
 * One editable cost row. A BAHAN line asks for quantity x unit price; an ALAT
 * line asks for the tool's price and how many batches it is shared over, which
 * is the distinction that keeps a one-off purchase out of a single batch.
 */
@Composable
private fun HppLineEditor(
    line: HppLine,
    onName: (String) -> Unit,
    onQty: ((String) -> Unit)? = null,
    onPrice: (String) -> Unit,
    onUses: ((String) -> Unit)? = null,
    onRemove: () -> Unit,
) {
    val icon: ImageVector = if (line.kind == HppKind.BAHAN) Icons.Filled.Inventory2 else Icons.Filled.Build

    Column(Modifier.padding(top = 10.dp)) {
        Row(verticalAlignment = Alignment.CenterVertically) {
            Box(
                Modifier
                    .size(28.dp)
                    .background(MaterialTheme.colorScheme.primaryContainer, CircleShape),
                contentAlignment = Alignment.Center,
            ) {
                Icon(
                    icon,
                    contentDescription = null,
                    tint = MaterialTheme.colorScheme.primary,
                    modifier = Modifier.size(16.dp),
                )
            }
            Spacer(Modifier.width(8.dp))
            OutlinedTextField(
                value = line.name,
                onValueChange = onName,
                label = { Text(if (line.kind == HppKind.BAHAN) "Nama bahan" else "Nama alat") },
                singleLine = true,
                modifier = Modifier.weight(1f),
            )
            IconButton(onClick = onRemove) {
                Icon(
                    Icons.Filled.Delete,
                    contentDescription = "Hapus baris",
                    tint = MaterialTheme.colorScheme.onSurfaceVariant,
                )
            }
        }

        Row(
            Modifier.padding(start = 36.dp),
            horizontalArrangement = Arrangement.spacedBy(8.dp),
        ) {
            if (onQty != null) {
                OutlinedTextField(
                    value = line.qty.numberAsText(),
                    onValueChange = onQty,
                    label = { Text("Jumlah") },
                    singleLine = true,
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                    modifier = Modifier.weight(1f),
                )
            }
            OutlinedTextField(
                value = line.price.numberAsText(),
                onValueChange = onPrice,
                label = { Text(if (line.kind == HppKind.BAHAN) "Harga satuan" else "Harga alat") },
                singleLine = true,
                keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                modifier = Modifier.weight(1f),
            )
            if (onUses != null) {
                OutlinedTextField(
                    value = line.usesPerBatch.numberAsText(),
                    onValueChange = onUses,
                    label = { Text("Jumlah batch") },
                    singleLine = true,
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                    modifier = Modifier.weight(1f),
                )
            }
        }

        Text(
            if (line.kind == HppKind.BAHAN) {
                "Masuk ke modal: ${formatRupiah(line.batchCost)}"
            } else {
                "Dibagi ke ${line.usesPerBatch.toInt()} batch = ${formatRupiah(line.batchCost)} per batch"
            },
            style = MaterialTheme.typography.labelSmall,
            color = MaterialTheme.colorScheme.onSurfaceVariant,
            maxLines = 1,
            overflow = TextOverflow.Ellipsis,
        )
    }
}

/** Shows where the per-unit figure came from, so a wrong HPP is traceable. */
@Composable
private fun CostBreakdown(
    materials: Double,
    tools: Double,
    packaging: Double,
    shipping: Double,
    waste: Double,
    total: Double,
) {
    Column(Modifier.fillMaxWidth()) {
        CostRow("Bahan", materials)
        if (tools > 0) CostRow("Alat (dibagi per batch)", tools)
        if (packaging > 0) CostRow("Kemasan", packaging)
        if (shipping > 0) CostRow("Ongkir", shipping)
        if (waste > 0) CostRow("Sisipan cacat / tak laku", waste)
        Spacer(Modifier.height(4.dp))
        CostRow("Total satu batch", total, emphasise = true)
    }
}

@Composable
private fun CostRow(label: String, value: Double, emphasise: Boolean = false) {
    Row(Modifier.fillMaxWidth()) {
        Text(
            label,
            style = if (emphasise) MaterialTheme.typography.labelMedium else MaterialTheme.typography.labelSmall,
            color = Color.White.copy(alpha = if (emphasise) 1f else 0.85f),
            fontWeight = if (emphasise) FontWeight.SemiBold else FontWeight.Normal,
            modifier = Modifier.weight(1f),
            maxLines = 1,
            overflow = TextOverflow.Ellipsis,
        )
        Text(
            formatRupiah(value),
            style = MaterialTheme.typography.labelSmall,
            color = Color.White,
            fontWeight = if (emphasise) FontWeight.Bold else FontWeight.Medium,
        )
    }
}

/**
 * Asks the server for a selling-price opinion on this product's cost.
 *
 * Falls back gracefully: when AI is off the server still returns a computed
 * band, and the card says so rather than pretending the model answered.
 */
@Composable
private fun PricingAdvisor(
    hpp: Double,
    quantity: Double,
    wastePercent: Double,
    product: String,
    competitionText: String,
    onCompetitionChange: (String) -> Unit,
    loading: Boolean,
    error: String?,
    recommendation: PricingResponse?,
    onAsk: () -> Unit,
    onClear: () -> Unit,
) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
        shape = RoundedCornerShape(16.dp),
    ) {
        Column(Modifier.padding(14.dp)) {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Icon(
                    Icons.Filled.AutoAwesome,
                    contentDescription = null,
                    tint = Amber600,
                    modifier = Modifier.size(18.dp),
                )
                Spacer(Modifier.width(8.dp))
                Text(
                    "Tentukan harga yang pantas",
                    style = MaterialTheme.typography.titleSmall,
                    fontWeight = FontWeight.SemiBold,
                    modifier = Modifier.weight(1f),
                )
            }
            Spacer(Modifier.height(4.dp))
            Text(
                "Bandingkan modal yang sudah dihitung dengan harga pasar dan harga pesaing.",
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
            )
            Spacer(Modifier.height(10.dp))

            OutlinedTextField(
                value = competitionText,
                onValueChange = onCompetitionChange,
                label = { Text("Harga pesaing (opsional)") },
                singleLine = true,
                keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                modifier = Modifier.fillMaxWidth(),
            )

            Spacer(Modifier.height(10.dp))
            Button(
                onClick = onAsk,
                enabled = !loading && hpp > 0,
                modifier = Modifier.fillMaxWidth(),
            ) {
                if (loading) {
                    CircularProgressIndicator(
                        modifier = Modifier.size(16.dp),
                        strokeWidth = 2.dp,
                        color = MaterialTheme.colorScheme.onPrimary,
                    )
                } else {
                    Text("Minta saran harga")
                }
            }

            if (error != null) {
                Spacer(Modifier.height(8.dp))
                Text(
                    error,
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.error,
                )
            }

            if (recommendation != null && recommendation.recommended > 0) {
                Spacer(Modifier.height(12.dp))
                Text(
                    "Saran harga: ${formatRupiah(recommendation.recommended)}",
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.Bold,
                    color = Teal700,
                )
                Text(
                    "Wajar antara ${formatRupiah(recommendation.min)} dan ${formatRupiah(recommendation.max)}",
                    style = MaterialTheme.typography.labelSmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                )
                recommendation.rationale?.takeIf { it.isNotBlank() }?.let { rationale ->
                    Spacer(Modifier.height(6.dp))
                    Text(
                        rationale,
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                    )
                }
                Spacer(Modifier.height(6.dp))
                Text(
                    if (recommendation.aiEnabled) {
                        "Dihitung dengan bantuan AI, sudah dibatasi agar tidak di bawah modal."
                    } else {
                        "AI belum aktif, jadi ini perhitungan standar 30% di atas modal."
                    },
                    style = MaterialTheme.typography.labelSmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                )
                Spacer(Modifier.height(4.dp))
                TextButton(onClick = onClear) { Text("Minta ulang") }
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
/** Renders a whole number without the ".0" a raw Double would otherwise print. */
private fun Double.numberAsText(): String =
    if (this == toLong().toDouble()) toLong().toString() else "%.2f".format(this)

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

/** Accepts "12000" and "12,500" — a comma thousands separator is still common here. */
private fun parseRupiah(raw: String): Double {
    if (raw.isBlank()) return 0.0
    return raw.replace(",", "").toDoubleOrNull() ?: 0.0
}