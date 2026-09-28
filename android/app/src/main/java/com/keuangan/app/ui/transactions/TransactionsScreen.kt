package com.keuangan.app.ui.transactions

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
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.Add
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
import androidx.compose.material3.TopAppBar
import androidx.compose.material3.TopAppBarDefaults
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.keuangan.app.data.TransactionDto
import com.keuangan.app.ui.components.DateField
import com.keuangan.app.ui.formatFullDate
import com.keuangan.app.ui.formatRupiah
import com.keuangan.app.ui.theme.Red600
import com.keuangan.app.ui.theme.Teal700

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun TransactionsScreen(
    viewModel: TransactionsViewModel,
    onBack: () -> Unit,
) {
    val state by viewModel.state.collectAsState()
    var pendingDelete by remember { mutableStateOf<TransactionDto?>(null) }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Transaksi", fontWeight = FontWeight.SemiBold) },
                navigationIcon = {
                    IconButton(onClick = onBack) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Kembali")
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = MaterialTheme.colorScheme.background,
                ),
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
                item {
                    FilterChip(
                        selected = state.typeFilter == null,
                        onClick = { viewModel.setTypeFilter(null) },
                        label = { Text("Semua") },
                    )
                }
                item {
                    FilterChip(
                        selected = state.typeFilter == "expense",
                        onClick = { viewModel.setTypeFilter("expense") },
                        label = { Text("Pengeluaran") },
                    )
                }
                item {
                    FilterChip(
                        selected = state.typeFilter == "income",
                        onClick = { viewModel.setTypeFilter("income") },
                        label = { Text("Pemasukan") },
                    )
                }
                items(state.categories) { category ->
                    FilterChip(
                        selected = state.categoryFilter == category.id,
                        onClick = {
                            viewModel.setCategoryFilter(
                                if (state.categoryFilter == category.id) null else category.id,
                            )
                        },
                        label = { Text(category.name) },
                    )
                }
            }

            Spacer(Modifier.height(8.dp))

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
                    items(state.items, key = { it.id }) { transaction ->
                        TransactionCard(
                            transaction = transaction,
                            onEdit = { viewModel.openEdit(transaction) },
                            onDelete = { pendingDelete = transaction },
                        )
                    }
                }
            }
        }
    }

    state.form?.let { form ->
        TransactionFormDialog(
            form = form,
            categories = state.categories,
            saving = state.saving,
            error = state.formError,
            onChange = viewModel::updateForm,
            onSave = viewModel::saveForm,
            onDismiss = viewModel::closeForm,
        )
    }

    pendingDelete?.let { transaction ->
        AlertDialog(
            onDismissRequest = { pendingDelete = null },
            title = { Text("Hapus transaksi?") },
            text = { Text(transaction.description ?: "Transaksi ini akan dihapus permanen.") },
            confirmButton = {
                TextButton(onClick = {
                    viewModel.delete(transaction)
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
private fun TransactionCard(
    transaction: TransactionDto,
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
                    transaction.description?.ifBlank {
                        transaction.category?.name ?: "Transaksi"
                    } ?: "Transaksi",
                    style = MaterialTheme.typography.bodyLarge,
                    maxLines = 1,
                )
                Spacer(Modifier.height(2.dp))
                Text(
                    "${transaction.category?.name ?: "Tanpa kategori"} • ${formatFullDate(transaction.date)}",
                    style = MaterialTheme.typography.labelSmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                )
                Spacer(Modifier.height(4.dp))
                Text(
                    (if (transaction.type == "income") "+" else "-") + formatRupiah(transaction.amount),
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.SemiBold,
                    color = if (transaction.type == "income") Teal700 else Red600,
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
private fun TransactionFormDialog(
    form: TransactionForm,
    categories: List<com.keuangan.app.data.CategoryDto>,
    saving: Boolean,
    error: String?,
    onChange: ((TransactionForm) -> TransactionForm) -> Unit,
    onSave: () -> Unit,
    onDismiss: () -> Unit,
) {
    val relevant = categories.filter { it.type == form.type }

    AlertDialog(
        onDismissRequest = onDismiss,
        title = { Text(if (form.id == null) "Tambah Transaksi" else "Ubah Transaksi") },
        text = {
            Column {
                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    FilterChip(
                        selected = form.type == "expense",
                        onClick = { onChange { it.copy(type = "expense", categoryId = null) } },
                        label = { Text("Pengeluaran") },
                    )
                    FilterChip(
                        selected = form.type == "income",
                        onClick = { onChange { it.copy(type = "income", categoryId = null) } },
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
                if (relevant.isNotEmpty()) {
                    Spacer(Modifier.height(10.dp))
                    Text("Kategori", style = MaterialTheme.typography.labelMedium)
                    Spacer(Modifier.height(4.dp))
                    LazyRow(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                        items(relevant) { category ->
                            FilterChip(
                                selected = form.categoryId == category.id,
                                onClick = {
                                    onChange {
                                        it.copy(
                                            categoryId = if (it.categoryId == category.id) null else category.id,
                                        )
                                    }
                                },
                                label = { Text(category.name) },
                            )
                        }
                    }
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
