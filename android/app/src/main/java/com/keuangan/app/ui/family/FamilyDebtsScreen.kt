package com.keuangan.app.ui.family

import androidx.compose.foundation.combinedClickable
import androidx.compose.foundation.ExperimentalFoundationApi
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.ExperimentalLayoutApi
import androidx.compose.foundation.layout.FlowRow
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
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.lazy.rememberLazyListState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.Edit
import androidx.compose.material.icons.filled.Payment
import androidx.compose.material.icons.filled.ReceiptLong
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Button
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.FilterChip as M3FilterChip
import androidx.compose.material3.FloatingActionButton
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.LinearProgressIndicator
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.ModalBottomSheet
import androidx.compose.material3.OutlinedButton
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
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.unit.dp
import com.keuangan.app.data.FamilyDebtDto
import com.keuangan.app.ui.components.DateField
import com.keuangan.app.ui.components.GradientHeader
import com.keuangan.app.ui.components.KangCuanTipCard
import com.keuangan.app.ui.components.StickySearchBar
import com.keuangan.app.ui.formatShortDate
import com.keuangan.app.ui.formatRupiah
import com.keuangan.app.ui.theme.Amber100
import com.keuangan.app.ui.theme.Amber600
import com.keuangan.app.ui.theme.Red600
import com.keuangan.app.ui.theme.Teal700

@OptIn(ExperimentalMaterial3Api::class, ExperimentalLayoutApi::class, ExperimentalFoundationApi::class)
@Composable
fun FamilyDebtsScreen(
    familyId: Int,
    viewModel: FamilyDebtsViewModel,
) {
    val state by viewModel.state.collectAsState()
    var pendingDelete by remember { mutableStateOf<FamilyDebtDto?>(null) }
    var showFilters by remember { mutableStateOf(false) }
    val listState = rememberLazyListState()
    val headerGone by remember { derivedStateOf { listState.firstVisibleItemIndex > 0 } }

    LaunchedEffect(familyId) {
        viewModel.load(familyId)
    }

    Scaffold(
        floatingActionButton = {
            FloatingActionButton(onClick = viewModel::openCreate) {
                Icon(Icons.Filled.Add, contentDescription = "Tambah hutang/piutang")
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
            verticalArrangement = Arrangement.spacedBy(10.dp),
        ) {
            item {
                GradientHeader(
                    title = "Hutang & Piutang",
                    subtitle = "Pantau tagihan, tetap tenang dan teratur",
                )
            }

            stickyHeader {
                StickySearchBar(
                    query = state.search,
                    onQueryChange = viewModel::onSearchChange,
                    placeholder = "Cari hutang/piutang",
                    headerGone = headerGone,
                    onOpenFilters = { showFilters = true },
                    activeFilterCount = activeDebtsFilterCount(state),
                    filterSummary = activeDebtsFilterSummary(state),
                    onClearFilters = viewModel::clearFiltersKeepSearch,
                )
            }

            state.actionMessage?.let { message ->
                item {
                    Card(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(horizontal = 16.dp),
                        colors = CardDefaults.cardColors(containerColor = Amber100),
                    ) {
                        Row(Modifier.padding(12.dp), verticalAlignment = Alignment.Top) {
                            Text(
                                "✅  $message",
                                style = MaterialTheme.typography.bodyMedium,
                                modifier = Modifier.weight(1f),
                            )
                            IconButton(onClick = viewModel::dismissMessage, modifier = Modifier.size(28.dp)) {
                                Icon(
                                    Icons.Filled.Close,
                                    contentDescription = "Tutup",
                                    modifier = Modifier.size(16.dp),
                                    tint = Amber600,
                                )
                            }
                        }
                    }
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
                state.allItems.isEmpty() -> item {
                    Box(Modifier.fillParentMaxSize(), contentAlignment = Alignment.Center) {
                        Text(
                            "Belum ada catatan hutang",
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                        )
                    }
                }
                state.items.isEmpty() -> item {
                    Box(
                        Modifier.fillParentMaxWidth().padding(24.dp),
                        contentAlignment = Alignment.Center,
                    ) {
                        Text(
                            "Tidak ada yang cocok dengan pencarian/filter",
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                        )
                    }
                }
                else -> items(state.items, key = { it.id }) { debt ->
                    Box(Modifier.padding(horizontal = 16.dp)) {
                        DebtCard(
                            debt = debt,
                            onEdit = { viewModel.openEdit(debt) },
                            onDelete = { pendingDelete = debt },
                            onPay = { viewModel.openPayment(debt) },
                        )
                    }
                }
            }

            if (!state.loading && state.error == null && state.items.isNotEmpty()) {
                item {
                    Box(Modifier.padding(horizontal = 16.dp)) {
                        KangCuanTipCard(
                            message = "Bayar cicilan yang bunganya paling tinggi lebih dulu (strategi avalanche). Setiap pembayaran yang dicatat mengurangi beban bulan depan.",
                        )
                    }
                }
            }
        }
    }

    if (showFilters) {
        DebtsFilterSheet(
            type = state.typeFilter,
            status = state.statusFilter,
            onDismiss = { showFilters = false },
            onApply = { type, status ->
                viewModel.applyFilters(type, status)
                showFilters = false
            },
        )
    }

    state.form?.let { form ->
        DebtFormDialog(
            form = form,
            saving = state.saving,
            error = state.formError,
            onChange = viewModel::updateForm,
            onSave = { viewModel.saveForm(familyId) },
            onDismiss = viewModel::closeForm,
        )
    }

    state.paymentDebt?.let { debt ->
        PaymentDialog(
            debt = debt,
            amount = state.paymentAmount,
            saving = state.saving,
            error = state.formError,
            onAmountChange = viewModel::onPaymentAmountChange,
            onConfirm = { viewModel.confirmPayment(familyId) },
            onDismiss = viewModel::closePayment,
        )
    }

    pendingDelete?.let { debt ->
        AlertDialog(
            onDismissRequest = { pendingDelete = null },
            title = { Text("Hapus ${if (debt.type == "payable") "hutang" else "piutang"}?") },
            text = { Text(debt.name) },
            confirmButton = {
                TextButton(onClick = {
                    viewModel.delete(familyId, debt)
                    pendingDelete = null
                }) { Text("Hapus", color = Red600) }
            },
            dismissButton = {
                TextButton(onClick = { pendingDelete = null }) { Text("Batal") }
            },
        )
    }
}

fun debtTypeLabel(type: String): String = if (type == "payable") "Utang" else "Piutang"

fun debtStatusLabel(debt: FamilyDebtDto): String = when {
    debt.status == "settled" -> "Lunas"
    debt.isOverdue -> "Lewat jatuh tempo"
    debt.remainingAmount <= 0 -> "Lunas"
    debt.paidAmount > 0 -> "Berjalan"
    else -> "Baru"
}

@Composable
internal fun DebtsFilterChip(label: String, selected: Boolean, onClick: () -> Unit) {
    M3FilterChip(selected = selected, onClick = onClick, label = { Text(label) })
}

private fun activeDebtsFilterCount(state: FamilyDebtsUiState): Int {
    var count = 0
    if (state.typeFilter != null) count++
    if (state.statusFilter != null) count++
    return count
}

private fun activeDebtsFilterSummary(state: FamilyDebtsUiState): String? {
    val parts = buildList {
        state.typeFilter?.let { add(debtTypeLabel(it)) }
        state.statusFilter?.let { add(if (it == "settled") "Lunas" else "Belum Lunas") }
    }
    return parts.takeIf { it.isNotEmpty() }?.joinToString(" • ")
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun DebtsFilterSheet(
    type: String?,
    status: String?,
    onDismiss: () -> Unit,
    onApply: (type: String?, status: String?) -> Unit,
) {
    var selectedType by remember { mutableStateOf(type) }
    var selectedStatus by remember { mutableStateOf(status) }

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
                "Filter hutang & piutang",
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.SemiBold,
            )
            Spacer(Modifier.height(14.dp))

            Text("Jenis", style = MaterialTheme.typography.labelMedium)
            Spacer(Modifier.height(6.dp))
            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                DebtsFilterChip("Semua", selectedType == null) { selectedType = null }
                DebtsFilterChip("Hutang", selectedType == "payable") { selectedType = "payable" }
                DebtsFilterChip("Piutang", selectedType == "receivable") { selectedType = "receivable" }
            }

            Spacer(Modifier.height(14.dp))
            Text("Status", style = MaterialTheme.typography.labelMedium)
            Spacer(Modifier.height(6.dp))
            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                DebtsFilterChip("Semua", selectedStatus == null) { selectedStatus = null }
                DebtsFilterChip("Belum Lunas", selectedStatus == "open") { selectedStatus = "open" }
                DebtsFilterChip("Lunas", selectedStatus == "settled") { selectedStatus = "settled" }
            }

            Spacer(Modifier.height(20.dp))
            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                OutlinedButton(
                    onClick = onDismiss,
                    modifier = Modifier.weight(1f),
                ) { Text("Batal") }
                Button(
                    onClick = { onApply(selectedType, selectedStatus) },
                    modifier = Modifier.weight(1f),
                ) { Text("Terapkan") }
            }
        }
    }
}

@OptIn(ExperimentalFoundationApi::class)
@Composable
private fun DebtCard(
    debt: FamilyDebtDto,
    onEdit: () -> Unit,
    onDelete: () -> Unit,
    onPay: () -> Unit,
) {
    val isPayable = debt.type == "payable"
    val fraction = if (debt.amount > 0) (debt.paidAmount / debt.amount).coerceIn(0.0, 1.0) else 0.0
    val settled = debt.status == "settled"

    Card(
        colors = CardDefaults.cardColors(
            containerColor = if (settled) MaterialTheme.colorScheme.surfaceVariant else MaterialTheme.colorScheme.surface,
        ),
        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
        modifier = Modifier
            .fillMaxWidth()
            .combinedClickable(
                onClick = onEdit,
                onLongClick = onDelete,
            ),
    ) {
        Column(Modifier.padding(14.dp)) {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Icon(
                    Icons.Filled.ReceiptLong,
                    contentDescription = null,
                    tint = if (settled) MaterialTheme.colorScheme.onSurfaceVariant else Amber600,
                    modifier = Modifier.size(18.dp),
                )
                Spacer(Modifier.size(8.dp))
                Text(
                    "${debt.name} • ${debtTypeLabel(debt.type)}",
                    style = MaterialTheme.typography.bodyLarge,
                    maxLines = 1,
                    modifier = Modifier.weight(1f),
                )
                Text(
                    debtStatusLabel(debt),
                    style = MaterialTheme.typography.labelSmall,
                    color = if (debt.isOverdue && !settled) Red600 else MaterialTheme.colorScheme.onSurfaceVariant,
                )
            }
            Spacer(Modifier.height(6.dp))
            Row(verticalAlignment = Alignment.CenterVertically) {
                Column(Modifier.weight(1f)) {
                    Text(
                        "Sisa ${formatRupiah(debt.remainingAmount)}",
                        style = MaterialTheme.typography.titleMedium,
                        fontWeight = FontWeight.SemiBold,
                        color = if (isPayable) Red600 else Teal700,
                    )
                    Text(
                        listOfNotNull(
                            if (debt.interestRate != null && debt.interestRate > 0) "Bunga ${debt.interestRate}%" else null,
                            if (debt.installment != null) "Angsuran ${formatRupiah(debt.installment)}" else null,
                            debt.dueDate?.let { "Jatuh tempo ${formatShortDate(it)}" },
                        ).joinToString(" • ").ifBlank { "Dari ${formatRupiah(debt.amount)}" },
                        style = MaterialTheme.typography.labelSmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                    )
                }
                IconButton(onClick = onPay, enabled = !settled, modifier = Modifier.size(36.dp)) {
                    Icon(
                        Icons.Filled.Payment,
                        contentDescription = "Bayar",
                        tint = if (settled) MaterialTheme.colorScheme.onSurfaceVariant else Amber600,
                        modifier = Modifier.size(20.dp),
                    )
                }
                IconButton(onClick = onEdit, modifier = Modifier.size(32.dp)) {
                    Icon(
                        Icons.Filled.Edit,
                        contentDescription = "Ubah",
                        modifier = Modifier.size(16.dp),
                    )
                }
            }
            Spacer(Modifier.height(6.dp))
            LinearProgressIndicator(
                progress = { fraction.toFloat() },
                modifier = Modifier.fillMaxWidth().height(6.dp),
                color = if (settled) Teal700 else if (debt.isOverdue) Red600 else Amber600,
                trackColor = MaterialTheme.colorScheme.surfaceVariant,
            )
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun DebtFormDialog(
    form: DebtForm,
    saving: Boolean,
    error: String?,
    onChange: ((DebtForm) -> DebtForm) -> Unit,
    onSave: () -> Unit,
    onDismiss: () -> Unit,
) {
    AlertDialog(
        onDismissRequest = onDismiss,
        title = { Text(if (form.id == null) "Tambah Hutang" else "Ubah Hutang") },
        text = {
            Column {
                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    M3FilterChip(
                        selected = form.type == "payable",
                        onClick = { onChange { it.copy(type = "payable") } },
                        label = { Text("Utang") },
                    )
                    M3FilterChip(
                        selected = form.type == "receivable",
                        onClick = { onChange { it.copy(type = "receivable") } },
                        label = { Text("Piutang") },
                    )
                }
                Spacer(Modifier.height(10.dp))
                OutlinedTextField(
                    value = form.name,
                    onValueChange = { value -> onChange { it.copy(name = value) } },
                    label = { Text("Nama") },
                    singleLine = true,
                    modifier = Modifier.fillMaxWidth(),
                )
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
                    value = form.interestRate,
                    onValueChange = { value -> onChange { it.copy(interestRate = value) } },
                    label = { Text("Bunga (%) — opsional") },
                    singleLine = true,
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Decimal),
                    modifier = Modifier.fillMaxWidth(),
                )
                Spacer(Modifier.height(10.dp))
                OutlinedTextField(
                    value = form.installment,
                    onValueChange = { value -> onChange { it.copy(installment = value) } },
                    label = { Text("Angsuran per bulan — opsional") },
                    singleLine = true,
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                    modifier = Modifier.fillMaxWidth(),
                )
                Spacer(Modifier.height(10.dp))
                DateField(
                    label = "Jatuh tempo — opsional",
                    value = form.dueDate,
                    onChange = { date -> onChange { it.copy(dueDate = date.orEmpty()) } },
                )
                Spacer(Modifier.height(10.dp))
                OutlinedTextField(
                    value = form.notes,
                    onValueChange = { value -> onChange { it.copy(notes = value) } },
                    label = { Text("Catatan — opsional") },
                    modifier = Modifier.fillMaxWidth(),
                )
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

@Composable
private fun PaymentDialog(
    debt: FamilyDebtDto,
    amount: String,
    saving: Boolean,
    error: String?,
    onAmountChange: (String) -> Unit,
    onConfirm: () -> Unit,
    onDismiss: () -> Unit,
) {
    AlertDialog(
        onDismissRequest = onDismiss,
        title = { Text("Bayar ${debt.name}") },
        text = {
            Column {
                Text(
                    "Sisa: ${formatRupiah(debt.remainingAmount)}",
                    style = MaterialTheme.typography.bodyMedium,
                )
                Spacer(Modifier.height(10.dp))
                OutlinedTextField(
                    value = amount,
                    onValueChange = onAmountChange,
                    label = { Text("Jumlah pembayaran") },
                    singleLine = true,
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                    modifier = Modifier.fillMaxWidth(),
                )
                if (error != null) {
                    Spacer(Modifier.height(10.dp))
                    Text(error, color = MaterialTheme.colorScheme.error)
                }
            }
        },
        confirmButton = {
            Button(onClick = onConfirm, enabled = !saving) {
                if (saving) {
                    CircularProgressIndicator(
                        modifier = Modifier.size(16.dp),
                        strokeWidth = 2.dp,
                        color = MaterialTheme.colorScheme.onPrimary,
                    )
                } else {
                    Text("Bayar")
                }
            }
        },
        dismissButton = { TextButton(onClick = onDismiss) { Text("Batal") } },
    )
}
