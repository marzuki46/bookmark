package com.keuangan.app.ui.family

import androidx.compose.foundation.ExperimentalFoundationApi
import androidx.compose.foundation.combinedClickable
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.ExperimentalLayoutApi
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.WindowInsets
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.statusBarsPadding
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.lazy.rememberLazyListState
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.CloudOff
import androidx.compose.material.icons.filled.Delete
import androidx.compose.material.icons.filled.Edit
import androidx.compose.material.icons.filled.FilterList
import androidx.compose.material.icons.filled.Sync
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Badge
import androidx.compose.material3.BadgedBox
import androidx.compose.material3.Button
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.FilterChip
import androidx.compose.material3.FloatingActionButton
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.ModalBottomSheet
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.rememberModalBottomSheetState
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.derivedStateOf
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.foundation.text.KeyboardActions
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.ui.text.input.ImeAction
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.unit.dp
import com.keuangan.app.data.FamilyTransactionDto
import com.keuangan.app.data.IncomeSourceDto
import com.keuangan.app.ui.components.ChoiceDropdown
import com.keuangan.app.ui.components.ChoiceItem
import com.keuangan.app.ui.components.DateField
import com.keuangan.app.ui.components.GradientHeader
import com.keuangan.app.ui.components.KangCuanTipCard
import com.keuangan.app.ui.components.StickySearchBar
import com.keuangan.app.ui.formatFullDate
import com.keuangan.app.ui.formatRupiah
import com.keuangan.app.ui.formatShortDate
import com.keuangan.app.ui.theme.Amber100
import com.keuangan.app.ui.theme.Amber600
import com.keuangan.app.ui.theme.Red600
import com.keuangan.app.ui.theme.Teal700

@OptIn(ExperimentalMaterial3Api::class, ExperimentalLayoutApi::class, ExperimentalFoundationApi::class)
@Composable
fun FamilyTransactionsScreen(
    familyId: Int,
    viewModel: FamilyTransactionsViewModel,
    payerRole: String? = null,
    isChild: Boolean = false,
) {
val state by viewModel.state.collectAsState()
    var pendingDelete by remember { mutableStateOf<FamilyTransactionDto?>(null) }
    var showFilters by remember { mutableStateOf(false) }
    val listState = rememberLazyListState()
    val headerGone by remember { derivedStateOf { listState.firstVisibleItemIndex > 0 } }

    LaunchedEffect(familyId) {
        viewModel.load(familyId)
    }

    Scaffold(
        floatingActionButton = {
            FloatingActionButton(onClick = {
                viewModel.openCreate(defaultPayer(payerRole, isChild))
            }) {
                Icon(Icons.Filled.Add, contentDescription = "Tambah transaksi")
            }
        },
        contentWindowInsets = WindowInsets(0, 0, 0, 0),
    ) { padding ->
        LazyColumn(
            state = listState,
            modifier = Modifier
                .fillMaxSize()
                .padding(padding),
            contentPadding = PaddingValues(bottom = 16.dp),
        ) {
            item {
                GradientHeader(
                    title = "Transaksi",
                    subtitle = "Kelola pemasukan & pengeluaran keluarga",
                )
            }

            stickyHeader {
                StickySearchBar(
                    query = state.search,
                    onQueryChange = viewModel::onSearchChange,
                    placeholder = "Cari transaksi",
                    headerGone = headerGone,
                    onOpenFilters = { showFilters = true },
                    onSearch = { viewModel.submitSearch(familyId) },
                    activeFilterCount = activeFilterCount(state),
                    filterSummary = activeFilterSummary(state),
                    onClearFilters = viewModel::clearFilters,
                )
            }

            if (state.offline || state.pendingCount > 0) {
                item {
                    OfflineBanner(
                        pendingCount = state.pendingCount,
                        pendingError = state.pendingError,
                        syncing = state.syncing,
                        onRetry = { viewModel.retrySync(familyId) },
                    )
                }
            }

            state.nudge?.let { nudge ->
                item {
                    NudgeBanner(text = nudge, onDismiss = viewModel::dismissNudge)
                }
            }

            when {
                state.loading -> item {
                    Box(Modifier.fillParentMaxSize(), contentAlignment = Alignment.Center) {
                        CircularProgressIndicator()
                    }
                }
                state.error != null -> item {
                    Box(Modifier.fillParentMaxSize(), contentAlignment = Alignment.Center) {
                        Text(state.error!!, color = MaterialTheme.colorScheme.error)
                    }
                }
                state.items.isEmpty() -> item {
                    Box(Modifier.fillParentMaxSize(), contentAlignment = Alignment.Center) {
                        Column(horizontalAlignment = Alignment.CenterHorizontally) {
                            Text(
                                "Belum ada transaksi",
                                color = MaterialTheme.colorScheme.onSurfaceVariant,
                            )
                            Spacer(Modifier.height(4.dp))
                            Text(
                                "Gunakan tombol + untuk mencatat.",
                                style = MaterialTheme.typography.bodySmall,
                                color = MaterialTheme.colorScheme.onSurfaceVariant,
                            )
                        }
                    }
                }
                else -> items(state.items, key = { it.id }) { tx ->
                    TxCard(
                        tx = tx,
                        pending = tx.id < 0,
                        onEdit = { viewModel.openEdit(tx) },
                        onDelete = { pendingDelete = tx },
                    )
                }
            }

            if (!state.loading && state.error == null && state.items.isNotEmpty()) {
                item {
                    Spacer(Modifier.height(2.dp))
                    KangCuanTipCard(
                        message = "Catat pengeluaran sekecil apa pun hari ini juga. Data yang rapi bikin laporan dan saran Kang Cuan lebih bisa diandalkan.",
                    )
                }
            }
        }
    }

state.form?.let { form ->
        TxFormDialog(
            form = form,
            categories = state.categories,
            incomeSources = state.incomeSources,
            saving = state.saving,
            error = state.formError,
            allowedPayers = allowedPayerOptions(payerRole, isChild, form.payer),
            onChange = viewModel::updateForm,
            onSave = { viewModel.saveForm(familyId) },
            onDismiss = viewModel::closeForm,
        )
    }

    if (showFilters) {
        FilterSheet(
            state = state,
            onDismiss = { showFilters = false },
            onApply = { type, payer, from, to ->
                viewModel.applyFilters(type, payer, from, to)
                showFilters = false
            },
        )
    }

    pendingDelete?.let { tx ->
        AlertDialog(
            onDismissRequest = { pendingDelete = null },
            title = { Text("Hapus transaksi?") },
            text = { Text(tx.description ?: "Transaksi ini akan dihapus permanen.") },
            confirmButton = {
                TextButton(onClick = {
                    viewModel.delete(familyId, tx)
                    pendingDelete = null
                }) { Text("Hapus", color = Red600) }
            },
            dismissButton = {
                TextButton(onClick = { pendingDelete = null }) { Text("Batal") }
            },
        )
    }
}

/**
 * The payer defaults to the only/appropriate option for the account's own
 * role. A wife is the "istri"; a child is given husband/wife because they
 * cannot create "umum" (shared) entries for the household budget.
 */
private fun defaultPayer(payerRole: String?, isChild: Boolean): String = when {
    payerRole == "wife" -> "wife"
    isChild -> "husband"
    else -> "shared"
}

/** Which payer options a member may pick when posting a transaction. */
private fun allowedPayerOptions(payerRole: String?, isChild: Boolean, current: String): List<String> {
    val base = when {
        payerRole == "wife" -> listOf("wife")
        isChild -> listOf("husband", "wife")
        else -> listOf("shared", "husband", "wife")
    }
    return if (current in base) base else base + current
}

private fun activeFilterCount(state: FamilyTransactionsUiState): Int {
    var count = 0
    if (state.typeFilter != null) count++
    if (state.payerFilter != null) count++
    if (state.fromFilter != null) count++
    if (state.toFilter != null) count++
    return count
}

private fun typeLabel(type: String): String = if (type == "income") "Pemasukan" else "Pengeluaran"

private fun dateRangeLabel(from: String?, to: String?): String = listOfNotNull(
    from?.let(::formatShortDate),
    to?.let(::formatShortDate),
).joinToString(" – ")

/** "Pemasukan • Ibu • 1 Mar – 9 Mar" for the chip-free summary line. */
private fun activeFilterSummary(state: FamilyTransactionsUiState): String? {
    val parts = buildList {
        state.typeFilter?.let { type -> add(typeLabel(type)) }
        state.payerFilter?.let { payer -> add(payerName(payer)) }
        if (state.fromFilter != null || state.toFilter != null) {
            add(dateRangeLabel(state.fromFilter, state.toFilter))
        }
    }
    return parts.takeIf { it.isNotEmpty() }?.joinToString(" • ")
}

@OptIn(ExperimentalMaterial3Api::class, ExperimentalLayoutApi::class, ExperimentalFoundationApi::class)
@Composable
private fun FilterSheet(
    state: FamilyTransactionsUiState,
    onDismiss: () -> Unit,
    onApply: (type: String?, payer: String?, from: String?, to: String?) -> Unit,
) {
    var selectedType by remember { mutableStateOf(state.typeFilter) }
    var selectedPayer by remember { mutableStateOf(state.payerFilter) }
    var selectedFrom by remember { mutableStateOf(state.fromFilter) }
    var selectedTo by remember { mutableStateOf(state.toFilter) }

    ModalBottomSheet(
        onDismissRequest = onDismiss,
        sheetState = rememberModalBottomSheetState(skipPartiallyExpanded = true),
    ) {
        Column(
            Modifier
                .fillMaxWidth()
                .padding(horizontal = 20.dp)
                .padding(bottom = 24.dp),
        ) {
            Text(
                "Filter Transaksi",
                style = MaterialTheme.typography.titleMedium,
            )
            Spacer(Modifier.height(14.dp))

            Text("Jenis", style = MaterialTheme.typography.labelLarge)
            Spacer(Modifier.height(6.dp))
            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                FilterChip(
                    selected = selectedType == null,
                    onClick = { selectedType = null },
                    label = { Text("Semua") },
                )
                FilterChip(
                    selected = selectedType == "expense",
                    onClick = { selectedType = "expense" },
                    label = { Text("Pengeluaran") },
                )
                FilterChip(
                    selected = selectedType == "income",
                    onClick = { selectedType = "income" },
                    label = { Text("Pemasukan") },
                )
            }
            Spacer(Modifier.height(16.dp))

            Text("User", style = MaterialTheme.typography.labelLarge)
            Spacer(Modifier.height(6.dp))
            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                FilterChip(
                    selected = selectedPayer == null,
                    onClick = { selectedPayer = null },
                    label = { Text("Semua") },
                )
                FilterChip(
                    selected = selectedPayer == "husband",
                    onClick = { selectedPayer = "husband" },
                    label = { Text("Suami") },
                )
                FilterChip(
                    selected = selectedPayer == "wife",
                    onClick = { selectedPayer = "wife" },
                    label = { Text("Istri") },
                )
            }
            Spacer(Modifier.height(16.dp))

            Text("Waktu", style = MaterialTheme.typography.labelLarge)
            Spacer(Modifier.height(6.dp))
            DateField(
                label = "Dari tanggal",
                value = selectedFrom,
                onChange = { selectedFrom = it },
            )
            Spacer(Modifier.height(8.dp))
            DateField(
                label = "Sampai tanggal",
                value = selectedTo,
                onChange = { selectedTo = it },
            )
            Spacer(Modifier.height(20.dp))

            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                TextButton(onClick = {
                    selectedType = null
                    selectedPayer = null
                    selectedFrom = null
                    selectedTo = null
                }) { Text("Reset") }
                Spacer(Modifier.weight(1f))
                Button(onClick = { onApply(selectedType, selectedPayer, selectedFrom, selectedTo) }) {
                    Text("Terapkan")
                }
                Spacer(Modifier.width(4.dp))
                TextButton(onClick = onDismiss) { Text("Batal") }
            }
        }
    }
}

@Composable
private fun OfflineBanner(
    pendingCount: Int,
    pendingError: String?,
    syncing: Boolean,
    onRetry: () -> Unit,
) {
    val text = if (pendingError != null) {
        "Masalah saat mengirim transaksi yang tersimpan: $pendingError"
    } else if (pendingCount > 0) {
        "$pendingCount transaksi tersimpan aman di ponsel dan akan dikirim otomatis saat internet kembali."
    } else {
        "Kamu sedang offline. Data terakhir tetap tampil dan transaksi baru akan tersimpan di ponsel dulu."
    }
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .padding(horizontal = 16.dp),
        colors = CardDefaults.cardColors(containerColor = Amber100),
    ) {
        Row(
            Modifier.padding(12.dp),
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Icon(
                if (pendingCount > 0) Icons.Filled.Sync else Icons.Filled.CloudOff,
                contentDescription = null,
                tint = Amber600,
                modifier = Modifier.size(18.dp),
            )
            Spacer(Modifier.size(8.dp))
            Text(
                text,
                style = MaterialTheme.typography.bodySmall,
                modifier = Modifier.weight(1f),
            )
            if (pendingCount > 0 || pendingError != null) {
                TextButton(onClick = onRetry, enabled = !syncing) {
                    if (syncing) {
                        CircularProgressIndicator(
                            modifier = Modifier.size(14.dp),
                            strokeWidth = 2.dp,
                        )
                    } else {
                        Text("Kirim sekarang")
                    }
                }
            }
        }
    }
}

@Composable
private fun NudgeBanner(text: String, onDismiss: () -> Unit) {
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .padding(horizontal = 16.dp),
        colors = CardDefaults.cardColors(containerColor = Amber100),
    ) {
        Row(Modifier.padding(12.dp), verticalAlignment = Alignment.Top) {
            Text(
                "💡  $text",
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

fun payerName(payer: String): String = when (payer) {
    "husband" -> "Suami"
    "wife" -> "Istri"
    else -> "Umum"
}

@OptIn(ExperimentalFoundationApi::class)
@Composable
private fun TxCard(
    tx: FamilyTransactionDto,
    pending: Boolean,
    onEdit: () -> Unit,
    onDelete: () -> Unit,
) {
Card(
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
        modifier = Modifier
            .fillMaxWidth()
            .combinedClickable(
                onClick = onEdit,
                onLongClick = onDelete,
            ),
    ) {
        Row(
            modifier = Modifier.padding(14.dp),
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Column(Modifier.weight(1f)) {
                if (pending) {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Icon(
                            Icons.Filled.CloudOff,
                            contentDescription = null,
                            tint = Amber600,
                            modifier = Modifier.size(14.dp),
                        )
                        Spacer(Modifier.size(4.dp))
                        Text(
                            "Belum tersinkron",
                            style = MaterialTheme.typography.labelSmall,
                            color = Amber600,
                        )
                    }
                    Spacer(Modifier.height(2.dp))
                }
                Text(
                    (tx.description?.ifBlank { null } ?: tx.category?.name) ?: "Transaksi",
                    style = MaterialTheme.typography.bodyLarge,
                    maxLines = 1,
                )
                Spacer(Modifier.height(2.dp))
                Text(
                    listOfNotNull(tx.category?.name, formatFullDate(tx.date), payerName(tx.payer))
                        .joinToString(" • "),
                    style = MaterialTheme.typography.labelSmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                )
                Spacer(Modifier.height(4.dp))
                Text(
                    (if (tx.type == "income") "+" else "-") + formatRupiah(tx.amount),
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.SemiBold,
                    color = if (tx.type == "income") Teal700 else Red600,
                )
            }
IconButton(
                onClick = onEdit,
                modifier = Modifier.size(32.dp),
            ) {
                Icon(
                    Icons.Filled.Edit,
                    contentDescription = "Ubah",
                    modifier = Modifier.size(16.dp),
                )
            }
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun TxFormDialog(
    form: TxForm,
    categories: List<com.keuangan.app.data.FamilyCategoryDto>,
    incomeSources: List<IncomeSourceDto>,
saving: Boolean,
    error: String?,
    allowedPayers: List<String>,
    onChange: ((TxForm) -> TxForm) -> Unit,
    onSave: () -> Unit,
    onDismiss: () -> Unit,
) {
    val relevant = categories.filter { it.type == form.type }

    AlertDialog(
        onDismissRequest = onDismiss,
        title = { Text(if (form.id == null) "Tambah Transaksi" else "Ubah Transaksi") },
        text = {
            Column(Modifier.verticalScroll(rememberScrollState())) {
                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    FilterChip(
                        selected = form.type == "expense",
                        onClick = { onChange { it.copy(type = "expense", categoryId = null) } },
                        label = { Text("Pengeluaran") },
                    )
                    FilterChip(
                        selected = form.type == "income",
                        onClick = { onChange { it.copy(type = "income", categoryId = null, incomeSourceId = null) } },
                        label = { Text("Pemasukan") },
                    )
                }
                Spacer(Modifier.height(10.dp))
                OutlinedTextField(
                    value = form.amount,
                    onValueChange = { value -> onChange { it.copy(amount = value) } },
                    label = { Text("Jumlah") },
                    singleLine = true,
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                    modifier = Modifier.fillMaxWidth(),
                )
                Spacer(Modifier.height(10.dp))
                OutlinedTextField(
                    value = form.description,
                    onValueChange = { value -> onChange { it.copy(description = value) } },
                    label = { Text("Keterangan") },
                    modifier = Modifier.fillMaxWidth(),
                )
                Spacer(Modifier.height(10.dp))
                DateField(
                    label = "Tanggal",
                    value = form.date,
                    onChange = { date -> onChange { it.copy(date = date.orEmpty()) } },
                )
                Spacer(Modifier.height(10.dp))
Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    mapOf("shared" to "Umum", "husband" to "Suami", "wife" to "Istri")
                        .filterKeys { it in allowedPayers }
                        .forEach { (key, label) ->
                            FilterChip(
                                selected = form.payer == key,
                                onClick = { onChange { it.copy(payer = key) } },
                                label = { Text(label) },
                            )
                        }
                }
                if (relevant.isNotEmpty()) {
                    Spacer(Modifier.height(12.dp))
                    ChoiceDropdown(
                        label = "Kategori",
                        choices = relevant.map { ChoiceItem(it.id, it.name) },
                        selectedId = form.categoryId,
                        onSelect = { id -> onChange { it.copy(categoryId = id) } },
                    )
                }
                if (form.type == "income" && incomeSources.isNotEmpty()) {
                    Spacer(Modifier.height(12.dp))
                    ChoiceDropdown(
                        label = "Sumber pemasukan",
                        choices = incomeSources.map { ChoiceItem(it.id, it.name) },
                        selectedId = form.incomeSourceId,
                        onSelect = { id -> onChange { it.copy(incomeSourceId = id) } },
                    )
                }
                if (error != null) {
                    Spacer(Modifier.height(10.dp))
                    Text(error, color = MaterialTheme.colorScheme.error)
                }
            }
        },
        confirmButton = {
            Button(onClick = onSave, enabled = !saving) {
                if (saving) {
                    CircularProgressIndicator(
                        modifier = Modifier.size(16.dp),
                        strokeWidth = 2.dp,
                        color = MaterialTheme.colorScheme.onPrimary,
                    )
                } else {
                    Text("Simpan")
                }
            }
        },
        dismissButton = { TextButton(onClick = onDismiss) { Text("Batal") } },
    )
}
