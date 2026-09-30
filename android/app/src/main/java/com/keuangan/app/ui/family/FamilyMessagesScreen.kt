package com.keuangan.app.ui.family

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
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
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Delete
import androidx.compose.material.icons.filled.MailOutline
import androidx.compose.material.icons.filled.NightsStay
import androidx.compose.material.icons.filled.CalendarMonth
import androidx.compose.material.icons.filled.Schedule
import androidx.compose.material.icons.filled.WbSunny
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Surface
import androidx.compose.material3.Switch
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
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
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.unit.dp
import androidx.compose.foundation.text.KeyboardOptions
import com.keuangan.app.reminder.KangCuanSlot

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun FamilyMessagesScreen(
    viewModel: FamilyMessagesViewModel,
    onBack: () -> Unit,
) {
    val state by viewModel.state.collectAsState()
    var confirmClear by remember { mutableStateOf(false) }

    LaunchedEffect(Unit) {
        viewModel.load()
        viewModel.refresh()
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Pesan dari Kang Cuan") },
                navigationIcon = {
                    IconButton(onClick = onBack) {
                        Icon(Icons.Filled.Schedule, contentDescription = "Kembali")
                    }
                },
            )
        },
    ) { padding ->
        LazyColumn(
            modifier = Modifier
                .fillMaxSize()
                .padding(padding),
            contentPadding = PaddingValues(16.dp, 12.dp, 16.dp, 40.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp),
        ) {
            item {
                Text(
                    "Alarm kecil dari Kang Cuan: penyemangat pagi, rekap malam, dan " +
                        "refleksi bulanan. Pesan terkirim tersimpan aman di HP kamu — bisa " +
                        "dihapus kapan saja.",
                    style = MaterialTheme.typography.bodyMedium,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                )
            }

            if (state.actionMessage != null) {
                item {
                    Card(colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant)) {
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Text(
                                state.actionMessage!!,
                                style = MaterialTheme.typography.bodySmall,
                                modifier = Modifier.weight(1f).padding(12.dp),
                            )
                            IconButton(onClick = viewModel::dismissMessage) {
                                Icon(Icons.Filled.Delete, contentDescription = "Tutup", modifier = Modifier.size(16.dp))
                            }
                        }
                    }
                }
            }

            item {
                ScheduleCard(
                    schedule = state.schedule,
                    onTogglePagi = viewModel::setPagiEnabled,
                    onToggleMalam = viewModel::setMalamEnabled,
                    onToggleBulanan = viewModel::setBulananEnabled,
                    onSetPagiTime = viewModel::setPagiTime,
                    onSetMalamTime = viewModel::setMalamTime,
                    onSetBulananTime = viewModel::setBulananTime,
                    onSetBulananDay = viewModel::setBulananDay,
                )
            }

            item {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically,
                ) {
                    Text(
                        "Pesan terkirim (${state.messages.size})",
                        style = MaterialTheme.typography.titleMedium,
                        fontWeight = FontWeight.SemiBold,
                    )
                    TextButton(
                        onClick = { confirmClear = true },
                        enabled = state.messages.isNotEmpty(),
                    ) {
                        Icon(Icons.Filled.Delete, contentDescription = null, modifier = Modifier.size(16.dp))
                        Spacer(Modifier.size(4.dp))
                        Text("Hapus semua")
                    }
                }
            }

            if (state.messages.isEmpty()) {
                item {
                    Column(
                        modifier = Modifier.fillMaxWidth().padding(vertical = 24.dp),
                        horizontalAlignment = Alignment.CenterHorizontally,
                    ) {
                        Icon(
                            Icons.Filled.MailOutline,
                            contentDescription = null,
                            tint = MaterialTheme.colorScheme.onSurfaceVariant,
                            modifier = Modifier.size(40.dp),
                        )
                        Spacer(Modifier.height(8.dp))
                        Text(
                            "Belum ada pesan. Nyalakan alarm di atas dan " +
                                "Kang Cuan akan mengirim pesan pertamamu.",
                            style = MaterialTheme.typography.bodyMedium,
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                        )
                    }
                }
            }

            items(state.messages, key = { it.id }) { message ->
                MessageCard(
                    title = message.title,
                    slot = message.slot,
                    body = message.body,
                    time = formatMessageTime(message.sentAt),
                    onDelete = { viewModel.deleteMessage(message.id) },
                )
            }
        }
    }

    if (confirmClear) {
        AlertDialog(
            onDismissRequest = { confirmClear = false },
            title = { Text("Hapus semua pesan?") },
            text = { Text("Semua pesan Kang Cuan di HP akan dihapus permanen. Ini tidak menghapus data keuangan keluarga.") },
            confirmButton = {
                TextButton(
                    onClick = {
                        viewModel.clearMessages()
                        confirmClear = false
                    },
                ) { Text("Hapus") }
            },
            dismissButton = { TextButton(onClick = { confirmClear = false }) { Text("Batal") } },
        )
    }
}

private data class ScheduleRow(
    val icon: ImageVector,
    val title: String,
    val subtitle: String,
    val enabled: Boolean,
    val onToggle: (Boolean) -> Unit,
    val timeText: String,
    val onEditTime: () -> Unit,
)

@Composable
private fun ScheduleCard(
    schedule: com.keuangan.app.data.KangCuanSchedule,
    onTogglePagi: (Boolean) -> Unit,
    onToggleMalam: (Boolean) -> Unit,
    onToggleBulanan: (Boolean) -> Unit,
    onSetPagiTime: (Int, Int) -> Unit,
    onSetMalamTime: (Int, Int) -> Unit,
    onSetBulananTime: (Int, Int) -> Unit,
    onSetBulananDay: (Int) -> Unit,
) {
    var editingTime by remember { mutableStateOf<Pair<String, IntArray>?>(null) }
    val timeDialogState = editingTime

    Card(
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
        modifier = Modifier.fillMaxWidth(),
    ) {
        Column(Modifier.padding(14.dp)) {
            Text("Jadwal Alarm", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
            Spacer(Modifier.height(4.dp))
            Text(
                "Dibunyikan oleh HP seperti alarm biasa — server tidak mengirim push.",
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
            )
            Spacer(Modifier.height(10.dp))

            val rows = listOf(
                ScheduleRow(
                    icon = Icons.Filled.WbSunny,
                    title = "Penyemangat Pagi",
                    subtitle = "Saat memulai hari",
                    enabled = schedule.pagiEnabled,
                    onToggle = onTogglePagi,
                    timeText = String.format("%02d:%02d", schedule.pagiHour, schedule.pagiMinute),
                    onEditTime = {
                        editingTime = KangCuanSlot.PAGI to intArrayOf(schedule.pagiHour, schedule.pagiMinute)
                    },
                ),
                ScheduleRow(
                    icon = Icons.Filled.NightsStay,
                    title = "Rekap Malam",
                    subtitle = "Evaluasi transaksi hari ini",
                    enabled = schedule.malamEnabled,
                    onToggle = onToggleMalam,
                    timeText = String.format("%02d:%02d", schedule.malamHour, schedule.malamMinute),
                    onEditTime = {
                        editingTime = KangCuanSlot.MALAM to intArrayOf(schedule.malamHour, schedule.malamMinute)
                    },
                ),
                ScheduleRow(
                    icon = Icons.Filled.CalendarMonth,
                    title = "Refleksi Bulanan",
                    subtitle = "Bahan renungan setiap tanggal ${schedule.bulananDay}, malam hari",
                    enabled = schedule.bulananEnabled,
                    onToggle = onToggleBulanan,
                    timeText = String.format("%02d:%02d", schedule.bulananHour, schedule.bulananMinute),
                    onEditTime = {
                        editingTime = KangCuanSlot.BULANAN to intArrayOf(schedule.bulananHour, schedule.bulananMinute)
                    },
                ),
            )

            rows.forEach { row ->
                Row(
                    modifier = Modifier.fillMaxWidth().padding(vertical = 8.dp),
                    verticalAlignment = Alignment.CenterVertically,
                ) {
                    Icon(
                        row.icon,
                        contentDescription = null,
                        tint = MaterialTheme.colorScheme.primary,
                        modifier = Modifier.size(22.dp),
                    )
                    Spacer(Modifier.size(12.dp))
                    Column(Modifier.weight(1f)) {
                        Text(row.title, style = MaterialTheme.typography.bodyMedium, fontWeight = FontWeight.Medium)
                        Text(row.subtitle, style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                    }
                    TextButton(onClick = row.onEditTime, enabled = row.enabled) {
                        Text(row.timeText)
                    }
                    Switch(checked = row.enabled, onCheckedChange = row.onToggle)
                }
            }

            if (schedule.bulananEnabled) {
                Text(
                    "Tanggal refleksi bisa diubah dari 1–28. Tengah malam pertama selalu menjadi lembaran baru.",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                )
            }
        }
    }

    if (timeDialogState != null) {
        TimeEditDialog(
            slot = timeDialogState.first,
            hour = timeDialogState.second[0],
            minute = timeDialogState.second[1],
            onConfirm = { h, m ->
                when (timeDialogState.first) {
                    KangCuanSlot.PAGI -> onSetPagiTime(h, m)
                    KangCuanSlot.MALAM -> onSetMalamTime(h, m)
                    else -> onSetBulananTime(h, m)
                }
                editingTime = null
            },
            onDismiss = { editingTime = null },
        )
    }
}

@Composable
private fun TimeEditDialog(
    slot: String,
    hour: Int,
    minute: Int,
    onConfirm: (Int, Int) -> Unit,
    onDismiss: () -> Unit,
) {
    var hourText by remember { mutableStateOf(hour.toString()) }
    var minuteText by remember { mutableStateOf(minute.toString()) }

    AlertDialog(
        onDismissRequest = onDismiss,
        title = { Text(if (slot == KangCuanSlot.BULANAN) "Jam Refleksi Bulanan" else "Jam alarm") },
        text = {
            Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                OutlinedTextField(
                    value = hourText,
                    onValueChange = { value -> hourText = value.filter { it.isDigit() }.take(2) },
                    label = { Text("Jam (0–23)") },
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                    singleLine = true,
                    modifier = Modifier.fillMaxWidth(),
                )
                OutlinedTextField(
                    value = minuteText,
                    onValueChange = { value -> minuteText = value.filter { it.isDigit() }.take(2) },
                    label = { Text("Menit (0–59)") },
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                    singleLine = true,
                    modifier = Modifier.fillMaxWidth(),
                )
            }
        },
        confirmButton = {
            TextButton(
                onClick = {
                    val h = hourText.toIntOrNull() ?: 7
                    val m = minuteText.toIntOrNull() ?: 0
                    onConfirm(h.coerceIn(0, 23), m.coerceIn(0, 59))
                },
            ) { Text("Simpan") }
        },
        dismissButton = { TextButton(onClick = onDismiss) { Text("Batal") } },
    )
}

@Composable
private fun MessageCard(
    title: String,
    slot: String,
    body: String,
    time: String,
    onDelete: () -> Unit,
) {
    val containerColor = when (slot) {
        KangCuanSlot.PAGI -> MaterialTheme.colorScheme.primaryContainer.copy(alpha = 0.35f)
        KangCuanSlot.BULANAN -> MaterialTheme.colorScheme.tertiaryContainer.copy(alpha = 0.35f)
        else -> MaterialTheme.colorScheme.secondaryContainer.copy(alpha = 0.35f)
    }
    Card(
        colors = CardDefaults.cardColors(containerColor = containerColor),
        elevation = CardDefaults.cardElevation(defaultElevation = 0.dp),
        modifier = Modifier.fillMaxWidth(),
    ) {
        Column(Modifier.padding(14.dp)) {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Column(Modifier.weight(1f)) {
                    Text(title, style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.SemiBold)
                    Text(time, style = MaterialTheme.typography.labelSmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                }
                IconButton(onClick = onDelete) {
                    Icon(Icons.Filled.Delete, contentDescription = "Hapus pesan", tint = MaterialTheme.colorScheme.onSurfaceVariant, modifier = Modifier.size(18.dp))
                }
            }
            Spacer(Modifier.height(8.dp))
            Text(body, style = MaterialTheme.typography.bodyMedium)
        }
    }
}