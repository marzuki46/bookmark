package com.keuangan.app.ui.family

import androidx.compose.foundation.ExperimentalFoundationApi
import androidx.compose.foundation.background
import androidx.compose.foundation.combinedClickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.ExperimentalLayoutApi
import androidx.compose.foundation.layout.FlowRow
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
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.lazy.rememberLazyListState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.Edit
import androidx.compose.material.icons.filled.Flag
import androidx.compose.material.icons.filled.Savings
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
import com.keuangan.app.data.FamilyGoalDto
import com.keuangan.app.ui.components.DateField
import com.keuangan.app.ui.components.GradientHeader
import com.keuangan.app.ui.components.KangCuanTipCard
import com.keuangan.app.ui.components.StickySearchBar
import com.keuangan.app.ui.formatRupiah
import com.keuangan.app.ui.formatShortDate
import com.keuangan.app.ui.theme.Amber100
import com.keuangan.app.ui.theme.Amber600
import com.keuangan.app.ui.theme.Teal700

@OptIn(ExperimentalMaterial3Api::class, ExperimentalLayoutApi::class, ExperimentalFoundationApi::class)
@Composable
fun FamilyGoalsScreen(
    familyId: Int,
    viewModel: FamilyGoalsViewModel,
) {
    val state by viewModel.state.collectAsState()
    var pendingDelete by remember { mutableStateOf<FamilyGoalDto?>(null) }
    var showFilters by remember { mutableStateOf(false) }
    val listState = rememberLazyListState()
    val headerGone by remember { derivedStateOf { listState.firstVisibleItemIndex > 0 } }

    LaunchedEffect(familyId) {
        viewModel.load(familyId)
    }

    Scaffold(
        floatingActionButton = {
            FloatingActionButton(onClick = viewModel::openCreate) {
                Icon(Icons.Filled.Add, contentDescription = "Tambah target")
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
                    title = "Target Keuangan",
                    subtitle = "Wujudkan impian keluarga, selangkah demi selangkah",
                )
            }

            stickyHeader {
                StickySearchBar(
                    query = state.search,
                    onQueryChange = viewModel::onSearchChange,
                    placeholder = "Cari target",
                    headerGone = headerGone,
                    onOpenFilters = { showFilters = true },
                    activeFilterCount = activeGoalsFilterCount(state),
                    filterSummary = activeGoalsFilterSummary(state),
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
                        Row(Modifier.padding(12.dp), verticalAlignment = Alignment.CenterVertically) {
                            Text(
                                "🎯  $message",
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
                            "Belum ada target. Mulai dari dana darurat: sisihkan 3-6x biaya bulanan.",
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
                else -> items(state.items, key = { it.id }) { goal ->
                    Box(Modifier.padding(horizontal = 16.dp)) {
                        GoalCard(
                            goal = goal,
                            onEdit = { viewModel.openEdit(goal) },
                            onDelete = { pendingDelete = goal },
                            onContribute = { viewModel.openContribute(goal) },
                        )
                    }
                }
            }

            if (!state.loading && state.error == null && state.items.isNotEmpty()) {
                item {
                    Box(Modifier.padding(horizontal = 16.dp)) {
                        KangCuanTipCard(
                            message = "Target yang kecil tapi rutin ditabung lebih cepat tercapai daripada target besar yang hanya jadi rencana.",
                        )
                    }
                }
            }
        }
    }

    if (showFilters) {
        GoalsFilterSheet(
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
        GoalFormDialog(
            form = form,
            saving = state.saving,
            error = state.formError,
            onChange = viewModel::updateForm,
            onSave = { viewModel.saveForm(familyId) },
            onDismiss = viewModel::closeForm,
        )
    }

    state.contributeGoal?.let { goal ->
        ContributeDialog(
            goal = goal,
            amount = state.contributeAmount,
            saving = state.saving,
            error = state.formError,
            onAmountChange = viewModel::onContributeAmountChange,
            onConfirm = { viewModel.confirmContribute(familyId) },
            onDismiss = viewModel::closeContribute,
        )
    }

    pendingDelete?.let { goal ->
        AlertDialog(
            onDismissRequest = { pendingDelete = null },
            title = { Text("Hapus target?") },
            text = { Text(goal.name) },
            confirmButton = {
                TextButton(onClick = {
                    viewModel.delete(familyId, goal)
                    pendingDelete = null
                }) { Text("Hapus", color = com.keuangan.app.ui.theme.Red600) }
            },
            dismissButton = {
                TextButton(onClick = { pendingDelete = null }) { Text("Batal") }
            },
        )
    }
}

private fun activeGoalsFilterCount(state: FamilyGoalsUiState): Int {
    var count = 0
    if (state.typeFilter != null) count++
    if (state.statusFilter != null) count++
    return count
}

private fun activeGoalsFilterSummary(state: FamilyGoalsUiState): String? {
    val parts = buildList {
        state.typeFilter?.let { add(if (it == "emergency_fund") "Dana Darurat" else "Kustom") }
        state.statusFilter?.let { add(if (it == "completed") "Tercapai" else "Aktif") }
    }
    return parts.takeIf { it.isNotEmpty() }?.joinToString(" • ")
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun GoalsFilterSheet(
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
                "Filter target",
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.SemiBold,
            )
            Spacer(Modifier.height(14.dp))

            Text("Jenis", style = MaterialTheme.typography.labelMedium)
            Spacer(Modifier.height(6.dp))
            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                DebtsFilterChip("Semua", selectedType == null) { selectedType = null }
                DebtsFilterChip("Dana Darurat", selectedType == "emergency_fund") { selectedType = "emergency_fund" }
                DebtsFilterChip("Kustom", selectedType == "custom") { selectedType = "custom" }
            }

            Spacer(Modifier.height(14.dp))
            Text("Status", style = MaterialTheme.typography.labelMedium)
            Spacer(Modifier.height(6.dp))
            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                DebtsFilterChip("Semua", selectedStatus == null) { selectedStatus = null }
                DebtsFilterChip("Aktif", selectedStatus == "active") { selectedStatus = "active" }
                DebtsFilterChip("Tercapai", selectedStatus == "completed") { selectedStatus = "completed" }
            }

            Spacer(Modifier.height(20.dp))
            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                OutlinedButton(onClick = onDismiss, modifier = Modifier.weight(1f)) { Text("Batal") }
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
private fun GoalCard(
    goal: FamilyGoalDto,
    onEdit: () -> Unit,
    onDelete: () -> Unit,
    onContribute: () -> Unit,
) {
    val completed = goal.status == "completed"
    val fraction = (goal.progressPercent / 100.0).coerceIn(0.0, 1.0)

    Card(
        colors = CardDefaults.cardColors(
            containerColor = if (completed) MaterialTheme.colorScheme.surfaceVariant else MaterialTheme.colorScheme.surface,
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
                    if (goal.type == "emergency_fund") Icons.Filled.Savings else Icons.Filled.Flag,
                    contentDescription = null,
                    tint = if (goal.type == "emergency_fund") Amber600 else MaterialTheme.colorScheme.onSurfaceVariant,
                    modifier = Modifier.size(18.dp),
                )
                Spacer(Modifier.size(8.dp))
                Text(
                    goal.name,
                    style = MaterialTheme.typography.bodyLarge,
                    maxLines = 1,
                    modifier = Modifier.weight(1f),
                )
                Text(
                    if (completed) "Tercapai" else "${goal.progressPercent.toInt()}%",
                    style = MaterialTheme.typography.labelSmall,
                    color = if (completed) Teal700 else MaterialTheme.colorScheme.onSurfaceVariant,
                )
            }
            Spacer(Modifier.height(6.dp))
            Text(
                "${formatRupiah(goal.currentAmount)} dari ${formatRupiah(goal.targetAmount)}",
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.SemiBold,
                color = Teal700,
            )
            Spacer(Modifier.height(4.dp))
            Row(verticalAlignment = Alignment.CenterVertically) {
                Text(
                    listOfNotNull(
                        if (goal.monthlyAllocation > 0) "Alokasi ${formatRupiah(goal.monthlyAllocation)}/bulan" else null,
                        goal.deadline?.let { "Target ${formatShortDate(it)}" },
                    ).joinToString(" • ").ifBlank { "Sisa ${formatRupiah(goal.remainingAmount)}" },
                    style = MaterialTheme.typography.labelSmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                    modifier = Modifier.weight(1f),
                )
                IconButton(onClick = onContribute, enabled = !completed, modifier = Modifier.size(36.dp)) {
                    Icon(
                        Icons.Filled.Add,
                        contentDescription = "Tambahkan tabungan",
                        tint = if (completed) MaterialTheme.colorScheme.onSurfaceVariant else Amber600,
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
                color = if (completed) Teal700 else Amber600,
                trackColor = MaterialTheme.colorScheme.surfaceVariant,
            )
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun GoalFormDialog(
    form: GoalForm,
    saving: Boolean,
    error: String?,
    onChange: ((GoalForm) -> GoalForm) -> Unit,
    onSave: () -> Unit,
    onDismiss: () -> Unit,
) {
    AlertDialog(
        onDismissRequest = onDismiss,
        title = { Text(if (form.id == null) "Tambah Target" else "Ubah Target") },
        text = {
            Column {
                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    M3FilterChip(
                        selected = form.type == "custom",
                        onClick = { onChange { it.copy(type = "custom") } },
                        label = { Text("Kustom") },
                    )
                    M3FilterChip(
                        selected = form.type == "emergency_fund",
                        onClick = { onChange { it.copy(type = "emergency_fund") } },
                        label = { Text("Dana darurat") },
                    )
                }
                Spacer(Modifier.height(10.dp))
                OutlinedTextField(
                    value = form.name,
                    onValueChange = { value -> onChange { it.copy(name = value) } },
                    label = { Text("Nama target") },
                    singleLine = true,
                    modifier = Modifier.fillMaxWidth(),
                )
                Spacer(Modifier.height(10.dp))
                OutlinedTextField(
                    value = form.targetAmount,
                    onValueChange = { value -> onChange { it.copy(targetAmount = value) } },
                    label = { Text("Target jumlah") },
                    singleLine = true,
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                    modifier = Modifier.fillMaxWidth(),
                )
                Spacer(Modifier.height(10.dp))
                OutlinedTextField(
                    value = form.monthlyAllocation,
                    onValueChange = { value -> onChange { it.copy(monthlyAllocation = value) } },
                    label = { Text("Alokasi per bulan — opsional") },
                    singleLine = true,
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                    modifier = Modifier.fillMaxWidth(),
                )
                Spacer(Modifier.height(10.dp))
                DateField(
                    label = "Batas waktu — opsional",
                    value = form.deadline,
                    onChange = { date -> onChange { it.copy(deadline = date.orEmpty()) } },
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
private fun ContributeDialog(
    goal: FamilyGoalDto,
    amount: String,
    saving: Boolean,
    error: String?,
    onAmountChange: (String) -> Unit,
    onConfirm: () -> Unit,
    onDismiss: () -> Unit,
) {
    AlertDialog(
        onDismissRequest = onDismiss,
        title = { Text("Tambah tabungan ${goal.name}") },
        text = {
            Column {
                Text(
                    "Terkumpul ${formatRupiah(goal.currentAmount)} dari ${formatRupiah(goal.targetAmount)}",
                    style = MaterialTheme.typography.bodyMedium,
                )
                Spacer(Modifier.height(10.dp))
                OutlinedTextField(
                    value = amount,
                    onValueChange = onAmountChange,
                    label = { Text("Jumlah tabungan") },
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
                    Text("Tambahkan")
                }
            }
        },
        dismissButton = { TextButton(onClick = onDismiss) { Text("Batal") } },
    )
}