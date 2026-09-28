package com.keuangan.app.ui.family

import android.content.Intent
import android.net.Uri

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
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.KeyboardArrowRight
import androidx.compose.material.icons.automirrored.filled.TrendingUp
import androidx.compose.material.icons.filled.AccountBalanceWallet
import androidx.compose.material.icons.filled.AttachMoney
import androidx.compose.material.icons.filled.Category
import androidx.compose.material.icons.filled.Group
import androidx.compose.material.icons.filled.Info
import androidx.compose.material.icons.filled.Palette
import androidx.compose.material.icons.filled.SystemUpdate
import androidx.compose.material.icons.filled.WorkspacePremium
import androidx.compose.material.icons.rounded.Check
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.Icon
import androidx.compose.material3.LinearProgressIndicator
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.keuangan.app.BuildConfig
import com.keuangan.app.ui.components.GradientHeader
import com.keuangan.app.ui.theme.Amber100
import com.keuangan.app.ui.theme.Amber600
import com.keuangan.app.ui.theme.AppThemes
import com.keuangan.app.ui.theme.ThemeController

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
    onOpenTrend: () -> Unit,
    onOpenProfile: () -> Unit,
    onOpenSubscription: () -> Unit,
) {
    val state by viewModel.state.collectAsState()
    val context = LocalContext.current
    var showThemePicker by remember { mutableStateOf(false) }
    var showAbout by remember { mutableStateOf(false) }

    Scaffold(
        topBar = {
            GradientHeader(
                title = "Lainnya",
                subtitle = "Kelola anggaran, kategori, tema & akun keluarga",
            )
        },
    ) { padding ->
        val menu = listOf(
            MenuItem("Anggaran", "Atur batas pengeluaran per bulan", Icons.Filled.AccountBalanceWallet, onOpenBudgets),
            MenuItem("Sumber Pemasukan", "Gaji, usaha, sampingan", Icons.Filled.AttachMoney, onOpenIncomeSources),
            MenuItem("Kategori", "Kelompok pengeluaran & pemasukan", Icons.Filled.Category, onOpenCategories),
            MenuItem("Tren Keluarga", "Grafik pemasukan vs pengeluaran", Icons.AutoMirrored.Filled.TrendingUp, onOpenTrend),
            MenuItem("Tema", "Enam warna senang untuk aplikasi", Icons.Filled.Palette, { showThemePicker = true }),
            MenuItem("Langganan", "Status paket & pembayaran", Icons.Filled.WorkspacePremium, onOpenSubscription),
            MenuItem("Keluarga & Akun", "Anggota, peran, kode login & barcode", Icons.Filled.Group, onOpenProfile),
            MenuItem("Periksa Pembaruan", "Versi ${BuildConfig.VERSION_NAME} · pasang versi baru", Icons.Filled.SystemUpdate, viewModel::checkUpdates),
            MenuItem("Tentang KEUANGAN", "Privasi, keamanan & bantuan", Icons.Filled.Info, { showAbout = true }),
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

    if (showThemePicker) {
        AlertDialog(
            onDismissRequest = { showThemePicker = false },
            title = { Text("Pilih tema") },
            text = {
                Column(verticalArrangement = Arrangement.spacedBy(2.dp)) {
                    Text(
                        "Warna yang dipilih langsung menyala di seluruh aplikasi.",
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                    )
                    Spacer(Modifier.size(10.dp))
                    AppThemes.forEach { theme ->
                        val selected = theme.id == ThemeController.themeId.value
                        Row(
                            verticalAlignment = Alignment.CenterVertically,
                            modifier = Modifier
                                .fillMaxWidth()
                                .clip(CircleShape)
                                .clickable {
                                    ThemeController.select(context.applicationContext, theme.id)
                                    showThemePicker = false
                                }
                                .padding(horizontal = 6.dp, vertical = 10.dp),
                        ) {
                            Box(
                                Modifier
                                    .size(26.dp)
                                    .clip(CircleShape)
                                    .background(theme.gradient),
                            )
                            Spacer(Modifier.size(12.dp))
                            Text(
                                theme.title,
                                style = MaterialTheme.typography.bodyLarge,
                                fontWeight = if (selected) FontWeight.Bold else FontWeight.Normal,
                            )
                            if (selected) {
                                Spacer(Modifier.weight(1f))
                                Icon(
                                    Icons.Rounded.Check,
                                    contentDescription = null,
                                    tint = MaterialTheme.colorScheme.primary,
                                )
                            }
                        }
                    }
                }
            },
            confirmButton = {
                TextButton(onClick = { showThemePicker = false }) { Text("Tutup") }
            },
        )
    }

    if (showAbout) {
        AlertDialog(
            onDismissRequest = { showAbout = false },
            title = { Text("Tentang KEUANGAN") },
            text = {
                Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                    Text(
                        "KEUANGAN dibuat khusus untuk membantu rumah tangga di Indonesia mencatat, memahami, dan merencanakan keuangan bersama.",
                        style = MaterialTheme.typography.bodyMedium,
                    )
                    Text(
                        "Data keuangan disimpan dengan aman. Akses keluarga hanya dapat dibuka oleh orang yang memiliki kode rumah tangga. Kode akses tidak disimpan sebagai teks biasa di sistem.",
                        style = MaterialTheme.typography.bodyMedium,
                    )
                    Card(colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.primaryContainer)) {
                        Column(Modifier.padding(12.dp)) {
                            Text("Butuh bantuan?", fontWeight = FontWeight.Bold)
                            Spacer(Modifier.size(2.dp))
                            Text("Tri Marzuki\nWhatsApp: 082213028718")
                        }
                    }
                }
            },
            confirmButton = {
                TextButton(onClick = {
                    context.startActivity(
                        Intent(Intent.ACTION_VIEW, Uri.parse("https://wa.me/6282213028718")),
                    )
                }) { Text("Hubungi WhatsApp") }
            },
            dismissButton = {
                TextButton(onClick = { showAbout = false }) { Text("Tutup") }
            },
        )
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
    } else if (state.installError != null) {
        AlertDialog(
            onDismissRequest = {},
            title = { Text("Belum berhasil") },
            text = { Text("${state.installError}. Kamu bisa coba lagi dari menu Periksa Pembaruan.") },
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
                onDismissRequest = {
                    if (!state.downloading) viewModel.dismissUpdates()
                },
                title = { Text(if (state.downloading) "Memperbarui…" else "Versi baru tersedia") },
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
                                    "Kontak pengurus untuk mendapatkan APK terbaru.",
                                    style = MaterialTheme.typography.bodySmall,
                                    color = Amber600,
                                    modifier = Modifier.padding(12.dp),
                                )
                            }
                        }
                        if (state.downloading) {
                            LinearProgressIndicator(
                                progress = { state.installProgress },
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .clip(CircleShape),
                            )
                            Text(
                                "Mengunduh ${(state.installProgress * 100).toInt()}% …",
                                style = MaterialTheme.typography.bodySmall,
                                color = MaterialTheme.colorScheme.onSurfaceVariant,
                            )
                        }
                    }
                },
                confirmButton = {
                    if (update.downloadUrl.isNotBlank() && !state.downloading) {
                        TextButton(onClick = {
                            viewModel.applyUpdate(context.applicationContext, update.downloadUrl)
                        }) { Text("Perbarui") }
                    }
                },
                dismissButton = {
                    TextButton(
                        onClick = viewModel::dismissUpdates,
                        enabled = !state.downloading,
                    ) { Text("Nanti") }
                },
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
            Icon(
                item.icon,
                contentDescription = null,
                tint = MaterialTheme.colorScheme.primary,
                modifier = Modifier.size(22.dp),
            )
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
