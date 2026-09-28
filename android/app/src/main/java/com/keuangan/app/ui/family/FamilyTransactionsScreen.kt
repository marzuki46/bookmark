package com.keuangan.app.ui.family

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
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.Delete
import androidx.compose.material.icons.filled.Edit
import androidx.compose.material3.AlertDialog
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
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.keuangan.app.data.FamilyTransactionDto
import com.keuangan.app.data.IncomeSourceDto
import com.keuangan.app.ui.components.ChoiceDropdown
import com.keuangan.app.ui.components.ChoiceItem
import com.keuangan.app.ui.components.DateField
import com.keuangan.app.ui.components.GradientHeader
import com.keuangan.app.ui.formatFullDate
import com.keuangan.app.ui.formatRupiah
import com.keuangan.app.ui.theme.Amber100
import com.keuangan.app.ui.theme.Amber600
import com.keuangan.app.ui.theme.Red600
import com.keuangan.app.ui.theme.Teal700

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun FamilyTransactionsScreen(
    familyId: Int,
    viewModel: FamilyTransactionsViewModel,
) {
    val state by viewModel.state.collectAsState()
    var pendingDelete by remember { mutableStateOf<FamilyTransactionDto?>(null) }

    LaunchedEffect(familyId) {
        viewModel.load(familyId)
    }

    Scaffold(
        topBar = {
            GradientHeader(
                title = "Transaksi",
                subtitle = "Kelola pemasukan & pengeluaran keluarga",
            )
        },
        floatingActionButton = {
            FloatingActionButton(onClick = viewModel::openCreate) {
                Icon(Icons.Filled.Add, contentDescription = "Tambah transaksi")
            }
        },
    ) { padding ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(padding),
        ) {
            OutlinedTextField(
                value = state.search,
                onValueChange = viewModel::onSearchChange,
                label = { Text("Cari transaksi") },
                singleLine = true,
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 16.dp),
            )
            Spacer(Modifier.height(8.dp))

            LazyRow(
                contentPadding = PaddingValues(horizontal = 16.dp),
                horizontalArrangement = Arrangement.spacedBy(8.dp),
            ) {
                item { TxTypeChip("Semua", state.typeFilter == null) { viewModel.setTypeFilter(null) } }
                item { TxTypeChip("Pengeluaran", state.typeFilter == "expense") { viewModel.setTypeFilter("expense") } }
                item { TxTypeChip("Pemasukan", state.typeFilter == "income") { viewModel.setTypeFilter("income") } }
                item { TxPayerChip("Umum", state.payerFilter == "shared") { viewModel.setPayerFilter("shared") } }
                item { TxPayerChip("Suami", state.payerFilter == "husband") { viewModel.setPayerFilter("husband") } }
                item { TxPayerChip("Istri", state.payerFilter == "wife") { viewModel.setPayerFilter("wife") } }
            }

            Spacer(Modifier.height(8.dp))

            state.nudge?.let { nudge ->
                NudgeBanner(text = nudge, onDismiss = viewModel::dismissNudge)
                Spacer(Modifier.height(8.dp))
            }

            when {
                state.loading -> Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    CircularProgressIndicator()
                }
                state.error != null -> Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    Text(state.error!!, color = MaterialTheme.colorScheme.error)
                }
                state.items.isEmpty() -> Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    Text(
                        "Belum ada transaksi",
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                    )
                }
                else -> LazyColumn(
                    contentPadding = PaddingValues(16.dp),
                    verticalArrangement = Arrangement.spacedBy(10.dp),
                ) {
                    items(state.items, key = { it.id }) { tx ->
                        TxCard(
                            tx = tx,
                            onEdit = { viewModel.openEdit(tx) },
                            onDelete = { pendingDelete = tx },
                        )
                    }
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
            onChange = viewModel::updateForm,
            onSave = { viewModel.saveForm(familyId) },
            onDismiss = viewModel::closeForm,
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

@Composable
private fun TxTypeChip(label: String, selected: Boolean, onClick: () -> Unit) {
    FilterChip(selected = selected, onClick = onClick, label = { Text(label) })
}

@Composable
private fun TxPayerChip(label: String, selected: Boolean, onClick: () -> Unit) {
    FilterChip(selected = selected, onClick = onClick, label = { Text(label) })
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

@Composable
private fun TxCard(
    tx: FamilyTransactionDto,
    onEdit: () -> Unit,
    onDelete: () -> Unit,
) {
    Card(
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
        modifier = Modifier.fillMaxWidth(),
    ) {
        Row(
            modifier = Modifier.padding(14.dp),
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Column(Modifier.weight(1f)) {
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
            IconButton(onClick = onEdit) {
                Icon(Icons.Filled.Edit, contentDescription = "Ubah")
            }
            IconButton(onClick = onDelete) {
                Icon(
                    Icons.Filled.Delete,
                    contentDescription = "Hapus",
                    tint = MaterialTheme.colorScheme.error,
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