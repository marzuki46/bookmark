package com.keuangan.app.ui.family

import android.widget.Toast
import androidx.compose.foundation.layout.Arrangement
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
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material3.Button
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.FilterChip
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Switch
import androidx.compose.material3.Text
import androidx.compose.material3.TopAppBar
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.unit.dp
import com.keuangan.app.data.AdvisorProfileRequest

/**
 * Profil & pengaturan pendamping — the family facts Kang Cuan uses to build a
 * plan: income basis, household size, housing, protection and priorities.
 * Only the family owner can save (enforced by the server); others see the form
 * and a friendly 403 message if they try.
 */
@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun FamilyAdvisorProfileScreen(
    familyId: Int,
    viewModel: FamilyMoreViewModel,
    onBack: () -> Unit,
) {
    val state by viewModel.state.collectAsState()
    val context = LocalContext.current

    LaunchedEffect(familyId) {
        viewModel.loadAdvisor(familyId)
    }

    LaunchedEffect(state.advisorProfileSaved) {
        if (state.advisorProfileSaved) {
            Toast.makeText(context, "Profil pendamping tersimpan.", Toast.LENGTH_SHORT).show()
            onBack()
        }
    }

    val profile = state.advisor?.profile

    var monthlyIncome by remember(profile) { mutableStateOf(profile?.monthlyIncome?.toPlain() ?: "") }
    var incomeType by remember(profile) { mutableStateOf(profile?.incomeType ?: "fixed") }
    var membersCount by remember(profile) { mutableStateOf(profile?.membersCount?.toString() ?: "") }
    var dependentsCount by remember(profile) { mutableStateOf(profile?.dependentsCount?.toString() ?: "") }
    var housingType by remember(profile) { mutableStateOf(profile?.housingType) }
    var hasProtection by remember(profile) { mutableStateOf(profile?.hasProtection ?: false) }
    var uncoveredMembers by remember(profile) { mutableStateOf(profile?.uncoveredMembers?.toString() ?: "") }
    var essentialOverride by remember(profile) { mutableStateOf(profile?.monthlyEssentialOverride?.toPlain() ?: "") }
    var notes by remember(profile) { mutableStateOf(profile?.notes ?: "") }
    var priorities by remember(profile) { mutableStateOf(profile?.priorities?.toSet() ?: emptySet()) }
    var error by remember { mutableStateOf<String?>(null) }

    val housingOptions = listOf(
        "own_paid" to "Milik rumah (lunas)",
        "own_installment" to "Milik rumah (masih cicilan)",
        "rent" to "Kontrakan / sewa",
        "stay_family" to "Tinggal bersama keluarga",
    )

    val priorityPool = listOf(
        "Dana darurat",
        "Pendidikan anak",
        "Cicilan rumah",
        "Transportasi",
        "Jajan anak",
        "Liburan",
        "Kesehatan",
        "Menabung",
    )
    val allPriorityOptions = (priorityPool + (priorities - priorityPool.toSet())).distinct()

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Profil & pengaturan") },
                navigationIcon = {
                    IconButton(onClick = onBack) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Kembali")
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
                Card(colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant)) {
                    Text(
                        "Isi kondisi keluarga agar saran Kang Cuan jitu. Kolom kosong dibiarkan, dan bisa kembali kapan saja.",
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                        modifier = Modifier.padding(12.dp),
                    )
                }
            }

            item {
                OutlinedTextField(
                    value = monthlyIncome,
                    onValueChange = { monthlyIncome = it.filter(Char::isDigit).take(12) },
                    label = { Text("Pemasukan tetap bulanan") },
                    placeholder = { Text("Kosongkan = pakai catatan") },
                    singleLine = true,
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                    modifier = Modifier.fillMaxWidth(),
                )
            }

            item {
                Text("Jenis pemasukan", style = MaterialTheme.typography.labelLarge, fontWeight = FontWeight.SemiBold)
                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    FilterChip(
                        selected = incomeType == "fixed",
                        onClick = { incomeType = "fixed" },
                        label = { Text("Tetap") },
                    )
                    FilterChip(
                        selected = incomeType == "irregular",
                        onClick = { incomeType = "irregular" },
                        label = { Text("Tidak tetap") },
                    )
                }
            }

            item {
                Row(horizontalArrangement = Arrangement.spacedBy(10.dp)) {
                    OutlinedTextField(
                        value = membersCount,
                        onValueChange = { membersCount = it.filter(Char::isDigit).take(2) },
                        label = { Text("Anggota keluarga") },
                        singleLine = true,
                        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                        modifier = Modifier.weight(1f),
                    )
                    OutlinedTextField(
                        value = dependentsCount,
                        onValueChange = { dependentsCount = it.filter(Char::isDigit).take(2) },
                        label = { Text("Tanggungan") },
                        singleLine = true,
                        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                        modifier = Modifier.weight(1f),
                    )
                }
            }

            item {
                Text("Status rumah", style = MaterialTheme.typography.labelLarge, fontWeight = FontWeight.SemiBold)
                Column(verticalArrangement = Arrangement.spacedBy(6.dp)) {
                    housingOptions.forEach { (value, label) ->
                        FilterChip(
                            selected = housingType == value,
                            onClick = { housingType = if (housingType == value) null else value },
                            label = { Text(label) },
                            modifier = Modifier.fillMaxWidth(),
                        )
                    }
                }
            }

            item {
                Row(
                    modifier = Modifier.fillMaxWidth().padding(vertical = 2.dp),
                    verticalAlignment = Alignment.CenterVertically,
                ) {
                    Column(Modifier.weight(1f)) {
                        Text("Ada perlindungan (asuransi)", style = MaterialTheme.typography.bodyLarge)
                        Text(
                            "Kesehatan, jiwa atau dana perlindungan lain untuk keluarga.",
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                        )
                    }
                    Switch(checked = hasProtection, onCheckedChange = { hasProtection = it })
                }
            }

            if (hasProtection) {
                item {
                    OutlinedTextField(
                        value = uncoveredMembers,
                        onValueChange = { uncoveredMembers = it.filter(Char::isDigit).take(2) },
                        label = { Text("Anggota belum terlindungi") },
                        singleLine = true,
                        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                        modifier = Modifier.fillMaxWidth(),
                    )
                }
            }

            item {
                Text("Prioritas keuangan", style = MaterialTheme.typography.labelLarge, fontWeight = FontWeight.SemiBold)
                Text(
                    "Pilih beberapa. Disimpan apa adanya untuk dipertimbangkan Kang Cuan.",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                )
                Spacer(Modifier.height(4.dp))
                Column(verticalArrangement = Arrangement.spacedBy(6.dp)) {
                    allPriorityOptions.forEach { option ->
                        val selected = option in priorities
                        FilterChip(
                            selected = selected,
                            onClick = {
                                priorities = if (selected) priorities - option else priorities + option
                            },
                            label = { Text(option) },
                            modifier = Modifier.fillMaxWidth(),
                        )
                    }
                }
            }

            item {
                OutlinedTextField(
                    value = essentialOverride,
                    onValueChange = { essentialOverride = it.filter(Char::isDigit).take(12) },
                    label = { Text("Kebutuhan pokok per bulan — opsional") },
                    placeholder = { Text("Kosongkan = perkiraan otomatis") },
                    singleLine = true,
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                    modifier = Modifier.fillMaxWidth(),
                )
            }

            item {
                OutlinedTextField(
                    value = notes,
                    onValueChange = { notes = it.take(500) },
                    label = { Text("Catatan — opsional") },
                    placeholder = { Text("Contoh: pemasukan dari usaha diisi parsial") },
                    minLines = 2,
                    maxLines = 4,
                    modifier = Modifier.fillMaxWidth(),
                )
            }

            if (error != null) {
                item { Text(error!!, color = MaterialTheme.colorScheme.error, style = MaterialTheme.typography.bodyMedium) }
            }

            item {
                Button(
                    onClick = {
                        val parsed = buildProfileRequest(
                            monthlyIncome = monthlyIncome,
                            incomeType = incomeType,
                            membersCount = membersCount,
                            dependentsCount = dependentsCount,
                            housingType = housingType,
                            hasProtection = hasProtection,
                            uncoveredMembers = uncoveredMembers,
                            essentialOverride = essentialOverride,
                            notes = notes,
                            priorities = priorities,
                        )
                        if (parsed == null) {
                            error = "Periksa angka yang dimasukkan."
                        } else {
                            error = null
                            viewModel.saveAdvisorProfile(familyId, parsed)
                        }
                    },
                    enabled = !state.advisorSavingProfile,
                    modifier = Modifier.fillMaxWidth().height(48.dp),
                ) {
                    if (state.advisorSavingProfile) {
                        CircularProgressIndicator(modifier = Modifier.size(18.dp), strokeWidth = 2.dp)
                    } else {
                        Text("Simpan profil")
                    }
                }
            }
        }
    }
}

private fun Double.toPlain(): String =
    if (this == toLong().toDouble()) toLong().toString() else toString()

private fun buildProfileRequest(
    monthlyIncome: String,
    incomeType: String,
    membersCount: String,
    dependentsCount: String,
    housingType: String?,
    hasProtection: Boolean,
    uncoveredMembers: String,
    essentialOverride: String,
    notes: String,
    priorities: Set<String>,
): AdvisorProfileRequest? {
    val monthly = monthlyIncome.toDoubleOrNull()
    if (monthlyIncome.isNotEmpty() && (monthly == null || monthly < 0)) return null
    val members = membersCount.toIntOrNull()
    if (membersCount.isNotEmpty() && (members == null || members !in 1..20)) return null
    val dependents = dependentsCount.toIntOrNull()
    if (dependentsCount.isNotEmpty() && (dependents == null || dependents !in 0..20)) return null
    val uncovered = uncoveredMembers.toIntOrNull()
    if (uncoveredMembers.isNotEmpty() && (uncovered == null || uncovered !in 0..20)) return null
    val essential = essentialOverride.toDoubleOrNull()
    if (essentialOverride.isNotEmpty() && (essential == null || essential < 0)) return null

    return AdvisorProfileRequest(
        monthlyIncome = monthly,
        incomeType = incomeType,
        membersCount = members,
        dependentsCount = dependents,
        housingType = housingType,
        hasProtection = hasProtection,
        uncoveredMembers = uncovered,
        priorities = priorities.toList(),
        monthlyEssentialOverride = essential,
        notes = notes.ifBlank { null },
    )
}