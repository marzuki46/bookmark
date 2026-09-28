package com.keuangan.app.ui.family

import android.app.DownloadManager
import android.net.Uri
import android.os.Environment
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.KeyboardArrowRight
import androidx.compose.material.icons.filled.AccountBalanceWallet
import androidx.compose.material.icons.filled.AttachMoney
import androidx.compose.material.icons.filled.Category
import androidx.compose.material.icons.filled.Group
import androidx.compose.material.icons.filled.SystemUpdate
import androidx.compose.material.icons.filled.WorkspacePremium
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.keuangan.app.BuildConfig
import com.keuangan.app.ui.components.GradientHeader
import com.keuangan.app.ui.theme.Amber100
import com.keuangan.app.ui.theme.Amber600
import com.keuangan.app.ui.theme.Teal700

private data class MenuItem(
    val title: String,
    val subtitle: String,
    val icon: ImageVector,
    val onOpen: () -> Unit,
)

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun FamilyMoreScreen(
    viewModel: FamilyMoreViewModel,
    onOpenBudgets: () -> Unit,
    onOpenCategories: () -> Unit,
    onOpenIncomeSources: () -> Unit,
    onOpenProfile: () -> Unit,
    onOpenSubscription: () -> Unit,
) {
    val state by viewModel.state.collectAsState()
    val context = LocalContext.current

    Scaffold(
        topBar = {
            GradientHeader(
                title = "Lainnya",
                subtitle = "Kelola anggaran, kategori & akun keluarga",
            )
        },
    ) { padding ->
        val menu = listOf(
            MenuItem("Anggaran", "Atur batas pengeluaran per bulan", Icons.Filled.AccountBalanceWallet, onOpenBudgets),
            MenuItem("Sumber Pemasukan", "Gaji, usaha, sampingan", Icons.Filled.AttachMoney, onOpenIncomeSources),
            MenuItem("Kategori", "Kelompok pengeluaran & pemasukan", Icons.Filled.Category, onOpenCategories),
            MenuItem("Langganan", "Status paket & pembayaran", Icons.Filled.WorkspacePremium, onOpenSubscription),
            MenuItem("Keluarga & Akun", "Anggota, peran, kode login", Icons.Filled.Group, onOpenProfile),
            MenuItem("Periksa Pembaruan", "Versi ${BuildConfig.VERSION_NAME} · lihat & unduh versi baru", Icons.Filled.SystemUpdate, viewModel::checkUpdates),
        )

        LazyColumn(
            modifier = Modifier
                .fillMaxSize()
                .padding(padding),
            contentPadding = PaddingValues(16.dp),
            verticalArrangement = Arrangement.spacedBy(10.dp),
        ) {
            items(menu, key = { it.title }) { item ->
                MoreRow(item)
            }
        }
    }

    if (state.checkingUpdate) {
        AlertDialog(
            onDismissRequest = {},
            title = { Text("Memeriksa pembaruan") },
            text = {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    CircularProgressIndicator(modifier = Modifier.size(20.dp), strokeWidth = 2.dp)
                    Spacer(Modifier.size(12.dp))
                    Text("Menghubungi server…")
                }
            },
            confirmButton = { TextButton(onClick = viewModel::dismissUpdates) { Text("Tutup") } },
        )
    } else if (state.updateError != null) {
        AlertDialog(
            onDismissRequest = {},
            title = { Text("Gagal memeriksa") },
            text = { Text(state.updateError ?: "") },
            confirmButton = { TextButton(onClick = viewModel::dismissUpdates) { Text("Tutup") } },
        )
    } else if (state.update != null) {
        val update = state.update!!
        if (!update.updateAvailable) {
            AlertDialog(
                onDismissRequest = viewModel::dismissUpdates,
                title = { Text("Aplikasi terbaru") },
                text = { Text("Kamu sudah memakai KEUANGAN versi ${BuildConfig.VERSION_NAME}. Tidak ada pembaruan.") },
                confirmButton = { TextButton(onClick = viewModel::dismissUpdates) { Text("Tutup") } },
            )
        } else {
            AlertDialog(
                onDismissRequest = viewModel::dismissUpdates,
                title = { Text("Versi baru tersedia") },
                text = {
                    Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                        Text(
                            "KEUANGAN ${update.latestVersionName}",
                            style = MaterialTheme.typography.titleMedium,
                            fontWeight = FontWeight.Bold,
                        )
                        if (update.notes.isNotBlank()) {
                            Text(update.notes, style = MaterialTheme.typography.bodyMedium)
                        }
                        if (update.downloadUrl.isBlank()) {
                            Card(colors = CardDefaults.cardColors(containerColor = Amber100)) {
                                Text(
                                    "Kontak admin untuk mendapatkan APK terbaru.",
                                    style = MaterialTheme.typography.bodySmall,
                                    color = Amber600,
                                    modifier = Modifier.padding(12.dp),
                                )
                            }
                        }
                    }
                },
                confirmButton = {
                    if (update.downloadUrl.isNotBlank()) {
                        TextButton(onClick = {
                            val manager = context.getSystemService(DownloadManager::class.java)
                            val request = DownloadManager.Request(Uri.parse(update.downloadUrl)).apply {
                                setTitle("KEUANGAN ${update.latestVersionName}")
                                setDescription("Mengunduh pembaruan aplikasi…")
                                setNotificationVisibility(DownloadManager.Request.VISIBILITY_VISIBLE_NOTIFY_COMPLETED)
                                setMimeType("application/vnd.android.package-archive")
                                setDestinationInExternalPublicDir(
                                    Environment.DIRECTORY_DOWNLOADS,
                                    "KEUANGAN-${update.latestVersionName}.apk",
                                )
                            }
                            manager.enqueue(request)
                            viewModel.dismissUpdates()
                        }) { Text("Unduh") }
                    }
                },
                dismissButton = { TextButton(onClick = viewModel::dismissUpdates) { Text("Nanti") } },
            )
        }
    }
}

@Composable
private fun MoreRow(item: MenuItem) {
    Card(
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
        modifier = Modifier
            .fillMaxWidth()
            .clickable(onClick = item.onOpen),
    ) {
        Row(
            modifier = Modifier.padding(14.dp),
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Icon(item.icon, contentDescription = null, tint = Teal700, modifier = Modifier.size(22.dp))
            Spacer(Modifier.size(12.dp))
            Column(Modifier.weight(1f)) {
                Text(item.title, style = MaterialTheme.typography.bodyLarge, fontWeight = FontWeight.SemiBold)
                Text(
                    item.subtitle,
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                )
            }
            Icon(
                Icons.AutoMirrored.Filled.KeyboardArrowRight,
                contentDescription = null,
                tint = MaterialTheme.colorScheme.onSurfaceVariant,
            )
        }
    }
}