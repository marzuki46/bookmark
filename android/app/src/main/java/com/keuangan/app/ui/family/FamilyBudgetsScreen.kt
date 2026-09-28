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
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBackIos
import androidx.compose.material.icons.automirrored.filled.ArrowForwardIos
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
import androidx.compose.material3.LinearProgressIndicator
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
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.unit.dp
import com.keuangan.app.data.FamilyBudgetDto
import com.keuangan.app.ui.components.GradientHeader
import com.keuangan.app.ui.formatRupiah
import com.keuangan.app.ui.theme.Amber100
import com.keuangan.app.ui.theme.Amber600
import com.keuangan.app.ui.theme.Red100
import com.keuangan.app.ui.theme.Red600
import com.keuangan.app.ui.theme.Teal100
import com.keuangan.app.ui.theme.Teal700

private val MONTH_NAMES = listOf(
    "Januari", "Februari", "Maret", "April", "Mei", "Juni",
    "Juli", "Agustus", "September", "Oktober", "November", "Desember",
)

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun FamilyBudgetsScreen(
    familyId: Int,
    viewModel: FamilyBudgetsViewModel,
) {
    val state by viewModel.state.collectAsState()
    var pendingDelete by remember { mutableStateOf<FamilyBudgetDto?>(null) }

    LaunchedEffect(familyId) {
        viewModel.load(familyId)
    }

    Scaffold(
        topBar = {
            GradientHeader(
                title = "Anggaran",
                subtitle = "Tetapkan batas dan kendalikan pengeluaran",
            )
        },
        floatingActionButton = {
            FloatingActionButton(onClick = viewModel::openCreate) {
                Icon(Icons.Filled.Add, contentDescription = "Tambah anggaran")
            }
        },
    ) { padding ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(padding),
        ) {
            Row(
                modifier = Modifier.fillMaxWidth().padding(horizontal = 16.dp),
                verticalAlignment = Alignment.CenterVertically,
            ) {
                IconButton(onClick = { viewModel.shiftMonth(familyId, -1) }) {
                    Icon(Icons.AutoMirrored.Filled.ArrowBackIos, contentDescription = "Bulan sebelumnya")
                }
                Text(
                    "${MONTH_NAMES.getOrElse(state.month - 1) { "" }} ${state.year}",
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.SemiBold,
                    modifier = Modifier.weight(1f),
                    textAlign = androidx.compose.ui.text.style.TextAlign.Center,
                )
                IconButton(onClick = { viewModel.shiftMonth(familyId, 1) }) {
                    Icon(Icons.AutoMirrored.Filled.ArrowForwardIos, contentDescription = "Bulan berikutnya")
                }
            }
            Spacer(Modifier.height(8.dp))

            val total = state.items.sumOf { it.amount }
            val spent = state.items.sumOf { it.spent }
            if (total > 0) {
                Row(Modifier.padding(horizontal = 16.dp), verticalAlignment = Alignment.CenterVertically) {
                    LinearProgressIndicator(
                        progress = { (spent / total).toFloat().coerceIn(0f, 1f) },
                        modifier = Modifier.weight(1f).height(8.dp),
                        color = if (spent > total) Red600 else Amber600,
                        trackColor = MaterialTheme.colorScheme.surfaceVariant,
                    )
                    Spacer(Modifier.size(12.dp))
                    Text(
                        "${formatInt(spent)} / ${formatInt(total)}",
                        style = MaterialTheme.typography.labelMedium,
                        color = if (spent > total) Red600 else MaterialTheme.colorScheme.onSurfaceVariant,
                    )
                }
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
                        "Belum ada anggaran bulan ini",
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                    )
                }
                else -> LazyColumn(
                    contentPadding = PaddingValues(16.dp),
                    verticalArrangement = Arrangement.spacedBy(10.dp),
                ) {
                    items(state.items, key = { it.id }) { budget ->
                        BudgetRow(
                            budget = budget,
                            onEdit = { viewModel.openEdit(budget) },
                            onDelete = { pendingDelete = budget },
                        )
                    }
                }
            }
        }
    }

    if (!state.loading) {
        state.form?.let { form ->
            BudgetFormDialog(
                form = form,
                categories = state.categories,
                existingIds = state.items.map { it.categoryId }.toSet(),
                monthLabel = "${MONTH_NAMES.getOrElse(state.month - 1) { "" }} ${state.year}",
                saving = state.saving,
                error = state.formError,
                onChange = viewModel::updateForm,
                onSave = { viewModel.saveForm(familyId) },
                onDismiss = viewModel::closeForm,
            )
        }
    }

    pendingDelete?.let { budget ->
        AlertDialog(
            onDismissRequest = { pendingDelete = null },
            title = { Text("Hapus anggaran?") },
            text = { Text(budget.categoryName ?: "Anggaran ini akan dihapus.") },
            confirmButton = {
                TextButton(onClick = {
                    viewModel.delete(familyId, budget)
                    pendingDelete = null
                }) { Text("Hapus", color = Red600) }
            },
            dismissButton = {
                TextButton(onClick = { pendingDelete = null }) { Text("Batal") }
            },
        )
    }
}

private fun formatAmount(value: Double): String =
    if (value == value.toLong().toDouble()) value.toLong().toString() else value.toString()

private fun formatInt(value: Double): String =
    if (value == value.toLong().toDouble()) value.toLong().toString() else value.toString()

@Composable
private fun BudgetRow(
    budget: FamilyBudgetDto,
    onEdit: () -> Unit,
    onDelete: () -> Unit,
) {
    val over = budget.spent > budget.amount
    val fraction = if (budget.amount > 0) (budget.spent / budget.amount).coerceIn(0.0, 1.0) else 0.0

    Card(
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
        modifier = Modifier.fillMaxWidth(),
    ) {
        Column(Modifier.padding(14.dp)) {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Text(
                    budget.categoryName ?: "Tanpa kategori",
                    style = MaterialTheme.typography.bodyLarge,
                    maxLines = 1,
                    modifier = Modifier.weight(1f),
                )
                Text(
                    "${formatRupiah(budget.spent)} / ${formatRupiah(budget.amount)}",
                    style = MaterialTheme.typography.labelMedium,
                    color = if (over) Red600 else MaterialTheme.colorScheme.onSurfaceVariant,
                )
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
            Spacer(Modifier.height(6.dp))
            LinearProgressIndicator(
                progress = { fraction.toFloat() },
                modifier = Modifier.fillMaxWidth().height(6.dp),
                color = when {
                    over -> Red600
                    fraction >= 0.8 -> Red100
                    fraction >= 0.5 -> Amber600
                    else -> Teal700
                },
                trackColor = MaterialTheme.colorScheme.surfaceVariant,
            )
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun BudgetFormDialog(
    form: BudgetForm,
    categories: List<com.keuangan.app.data.FamilyCategoryDto>,
    existingIds: Set<Int?>,
    monthLabel: String,
    saving: Boolean,
    error: String?,
    onChange: ((BudgetForm) -> BudgetForm) -> Unit,
    onSave: () -> Unit,
    onDismiss: () -> Unit,
) {
    val editingExisting = form.categoryId != null && form.categoryId in existingIds

    AlertDialog(
        onDismissRequest = onDismiss,
        title = { Text(if (editingExisting) "Ubah Anggaran" else "Tambah Anggaran • $monthLabel") },
        text = {
            Column {
                if (!editingExisting) {
                    Text("Kategori", style = MaterialTheme.typography.labelMedium)
                    Spacer(Modifier.height(4.dp))
                    LazyRow(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                        items(categories) { category ->
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
                    Spacer(Modifier.height(10.dp))
                }
                OutlinedTextField(
                    value = form.amount,
                    onValueChange = { value -> onChange { it.copy(amount = value) } },
                    label = { Text("Jumlah anggaran") },
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
