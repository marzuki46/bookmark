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
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.Category
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
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.keuangan.app.data.FamilyCategoryDto
import com.keuangan.app.ui.components.GradientHeader
import com.keuangan.app.ui.theme.Teal700

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun FamilyCategoriesScreen(
    familyId: Int,
    viewModel: FamilyCategoriesViewModel,
) {
    val state by viewModel.state.collectAsState()

    LaunchedEffect(familyId) {
        viewModel.load(familyId)
    }

    Scaffold(
        topBar = {
            GradientHeader(
                title = "Kategori",
                subtitle = "Kelompokkan pengeluaran & pemasukan",
            )
        },
        floatingActionButton = {
            FloatingActionButton(onClick = viewModel::openCreate) {
                Icon(Icons.Filled.Add, contentDescription = "Tambah kategori")
            }
        },
    ) { padding ->
        when {
            state.loading -> Box(Modifier.padding(padding).fillMaxSize(), contentAlignment = Alignment.Center) {
                CircularProgressIndicator()
            }
            state.error != null -> Box(Modifier.padding(padding).fillMaxSize(), contentAlignment = Alignment.Center) {
                Text(state.error!!, color = MaterialTheme.colorScheme.error)
            }
            else -> LazyColumn(
                modifier = Modifier.padding(padding).fillMaxSize(),
                contentPadding = PaddingValues(16.dp),
                verticalArrangement = Arrangement.spacedBy(8.dp),
            ) {
                items(state.items, key = { it.id }) { category ->
                    CategoryRow(
                        category = category,
                        onEdit = { viewModel.openEdit(category) },
                    )
                }
            }
        }
    }

    state.form?.let { form ->
        CategoryFormDialog(
            form = form,
            saving = state.saving,
            error = state.formError,
            onChange = viewModel::updateForm,
            onSave = { viewModel.saveForm(familyId) },
            onDismiss = viewModel::closeForm,
        )
    }
}

@Composable
private fun CategoryRow(
    category: FamilyCategoryDto,
    onEdit: () -> Unit,
) {
    Card(
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
        modifier = Modifier.fillMaxWidth(),
    ) {
        Row(
            modifier = Modifier.padding(12.dp),
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Icon(
                Icons.Filled.Category,
                contentDescription = null,
                tint = Teal700,
                modifier = Modifier.size(20.dp),
            )
            Spacer(Modifier.size(10.dp))
            Column(Modifier.weight(1f)) {
                Text(category.name, style = MaterialTheme.typography.bodyLarge, maxLines = 1)
                Text(
                    if (category.type == "expense") "Pengeluaran" else "Pemasukan",
                    style = MaterialTheme.typography.labelSmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                )
            }
            IconButton(onClick = onEdit) {
                Icon(Icons.Filled.Edit, contentDescription = "Ubah")
            }
        }
    }
}

@Composable
private fun CategoryFormDialog(
    form: CategoryForm,
    saving: Boolean,
    error: String?,
    onChange: ((CategoryForm) -> CategoryForm) -> Unit,
    onSave: () -> Unit,
    onDismiss: () -> Unit,
) {
    AlertDialog(
        onDismissRequest = onDismiss,
        title = { Text(if (form.id == null) "Tambah Kategori" else "Ubah Kategori") },
        text = {
            Column {
                OutlinedTextField(
                    value = form.name,
                    onValueChange = { value -> onChange { it.copy(name = value) } },
                    label = { Text("Nama kategori") },
                    singleLine = true,
                    modifier = Modifier.fillMaxWidth(),
                )
                Spacer(Modifier.height(10.dp))
                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    FilterChip(
                        selected = form.type == "expense",
                        onClick = { onChange { it.copy(type = "expense") } },
                        label = { Text("Pengeluaran") },
                    )
                    FilterChip(
                        selected = form.type == "income",
                        onClick = { onChange { it.copy(type = "income") } },
                        label = { Text("Pemasukan") },
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