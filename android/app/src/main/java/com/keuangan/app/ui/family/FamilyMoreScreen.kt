package com.keuangan.app.ui.family

import android.Manifest
import android.content.Context
import android.content.pm.PackageManager
import android.os.Build

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
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.heightIn
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.lazy.rememberLazyListState
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.KeyboardArrowRight
import androidx.compose.material.icons.automirrored.filled.TrendingUp
import androidx.compose.material.icons.filled.AccountBalanceWallet
import androidx.compose.material.icons.filled.Animation
import androidx.compose.material.icons.filled.AttachMoney
import androidx.compose.material.icons.filled.Calculate
import androidx.compose.material.icons.filled.Category
import androidx.compose.material.icons.filled.ChildCare
import androidx.compose.material.icons.filled.FormatSize
import androidx.compose.material.icons.filled.Group
import androidx.compose.material.icons.filled.Info
import androidx.compose.material.icons.filled.Lock
import androidx.compose.material.icons.filled.NotificationsActive
import androidx.compose.material.icons.filled.Palette
import androidx.compose.material.icons.filled.Settings
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
import androidx.compose.material3.OutlinedTextField
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
import androidx.core.content.ContextCompat
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.foundation.text.KeyboardOptions
import com.keuangan.app.BuildConfig
import com.keuangan.app.ui.components.GradientHeader
import com.keuangan.app.ui.components.collapsingHeader
import com.keuangan.app.ui.components.rememberHeaderCollapse
import com.keuangan.app.ui.components.KangCuanTipCard
import com.keuangan.app.ui.theme.Amber100
import com.keuangan.app.ui.theme.Amber600
import com.keuangan.app.ui.theme.AppearanceController
import com.keuangan.app.ui.theme.AppLockController
import com.keuangan.app.ui.theme.AppThemes
import com.keuangan.app.ui.theme.KangCuanFloatController
import com.keuangan.app.ui.theme.MotionStyle
import com.keuangan.app.ui.theme.ThemeController

private data class MenuItem(
    val title: String,
    val subtitle: String,
    val icon: ImageVector,
    val onOpen: () -> Unit,
)

private data class MenuGroup(
    val title: String,
    val items: List<MenuItem>,
)

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun FamilyMoreScreen(
    familyId: Int,
    viewModel: FamilyMoreViewModel,
    currentUserIsOwner: Boolean,
    onOpenBudgets: () -> Unit,
    onOpenCategories: () -> Unit,
    onOpenIncomeSources: () -> Unit,
    onOpenTrend: () -> Unit,
    onOpenProfile: () -> Unit,
    onOpenSubscription: () -> Unit,
    onOpenKangCuan: () -> Unit,
    onOpenAdvisorProfile: () -> Unit,
    onOpenHppCalculator: () -> Unit,
) {
    val state by viewModel.state.collectAsState()
    val listState = rememberLazyListState()
    val headerFraction by rememberHeaderCollapse(listState)
    val context = LocalContext.current
    var showThemePicker by remember { mutableStateOf(false) }
    var showMotionPicker by remember { mutableStateOf(false) }
    var showTextSizePicker by remember { mutableStateOf(false) }
    var showLockPicker by remember { mutableStateOf(false) }
    var showAbout by remember { mutableStateOf(false) }

    androidx.compose.runtime.LaunchedEffect(familyId) {
        viewModel.loadAdvisor(familyId)
    }

    Scaffold(
        topBar = {
            GradientHeader(
                modifier = Modifier.collapsingHeader { headerFraction },
                title = "Lainnya",
                subtitle = "Kelola anggaran, kategori, tema & akun keluarga",
            )
        },
    ) { padding ->
        val menuGroups = listOf(
            MenuGroup("Keuangan", listOf(
                MenuItem("Anggaran", "Atur batas pengeluaran per bulan", Icons.Filled.AccountBalanceWallet, onOpenBudgets),
                MenuItem("Sumber Pemasukan", "Gaji, usaha, sampingan", Icons.Filled.AttachMoney, onOpenIncomeSources),
                MenuItem("Kategori", "Kelompok pengeluaran & pemasukan", Icons.Filled.Category, onOpenCategories),
                MenuItem("Tren Keluarga", "Grafik pemasukan vs pengeluaran", Icons.AutoMirrored.Filled.TrendingUp, onOpenTrend),
            )),
            MenuGroup("Tampilan", listOf(
                MenuItem("Tema", "Delapan pilihan warna untuk aplikasi", Icons.Filled.Palette, { showThemePicker = true }),
                MenuItem("Gaya animasi", AppearanceController.motion.value.label, Icons.Filled.Animation, { showMotionPicker = true }),
                MenuItem("Ukuran tulisan", AppearanceController.textSizeLabel(), Icons.Filled.FormatSize, { showTextSizePicker = true }),
                MenuItem("Kunci aplikasi", if (AppLockController.enabled.value) "Aktif" else "Nonaktif", Icons.Filled.Lock, { showLockPicker = true }),
            )),
MenuGroup("Kang Cuan", listOf(
                MenuItem("Pesan dari Kang Cuan", "Jadwal alarm & pesan tersimpan di HP", Icons.Filled.NotificationsActive, onOpenKangCuan),
                MenuItem(
                    "Model Kang Cuan",
                    if (KangCuanFloatController.visible.value) "Avatar melayang di Ringkasan: Aktif" else "Avatar melayang di Ringkasan: Nonaktif",
                    Icons.Filled.ChildCare,
                    { KangCuanFloatController.setVisible(context, !KangCuanFloatController.visible.value) },
                ),
            )),
            MenuGroup("Keluarga & Aplikasi", listOf(
                MenuItem("Izin notifikasi", notificationRowSubtitle(context), Icons.Filled.NotificationsActive, { openNotificationSettings(context) }),
                MenuItem("Langganan", "Status paket & pembayaran", Icons.Filled.WorkspacePremium, onOpenSubscription),
                MenuItem("Keluarga & Akun", "Anggota, peran, kode login & barcode", Icons.Filled.Group, onOpenProfile),
                MenuItem("Periksa Pembaruan", "Versi ${BuildConfig.VERSION_NAME} · pasang versi baru", Icons.Filled.SystemUpdate, viewModel::checkUpdates),
            )),
MenuGroup("Tools Penunjang Cuan", listOf(
                MenuItem("Kalkulator HPP", "Hitung HPP & harga jual yang masuk akal", Icons.Filled.Calculate, onOpenHppCalculator),
            )),
            MenuGroup("Bantuan", listOf(
                MenuItem("Tentang Kang Cuan", "Privasi, keamanan & bantuan", Icons.Filled.Info, { showAbout = true }),
            )),
        )

        LazyColumn(
            state = listState,
            modifier = Modifier
                .fillMaxSize()
                .padding(padding),
            contentPadding = PaddingValues(16.dp, 10.dp, 16.dp, 112.dp),
            verticalArrangement = Arrangement.spacedBy(10.dp),
        ) {
            if (state.advisorError != null) {
                item {
                    Card(colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant)) {
                        Text(
                            state.advisorError!!,
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                            modifier = Modifier.padding(12.dp),
                        )
                    }
                }
            }
item {
                AdvisorCard(
                    enabled = state.advisor?.enabled ?: false,
                    accessible = state.advisor?.accessible,
                    message = state.advisor?.message,
                    loading = state.advisorLoading,
                    toggling = state.advisorToggling,
                    canEditProfile = currentUserIsOwner,
                    onToggle = { viewModel.setAdvisorEnabled(familyId, it) },
                    onEditProfile = onOpenAdvisorProfile,
                )
            }
            items(menuGroups, key = { it.title }) { group ->
                Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                    Text(
                        group.title,
                        style = MaterialTheme.typography.labelLarge,
                        color = MaterialTheme.colorScheme.primary,
                        fontWeight = FontWeight.SemiBold,
                        modifier = Modifier.padding(top = 4.dp),
                    )
                    group.items.forEach { item -> MoreRow(item) }
                }
            }
            item {
                Spacer(Modifier.height(2.dp))
                KangCuanTipCard(
                    message = "Cek aplikasi tiap malam sebelum tidur: catat pengeluaran hari itu, lalu lihat Ringkasan. Kebiasaan kecil ini yang bikin data selalu akurat.",
                )
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

    if (showMotionPicker) {
        AlertDialog(
            onDismissRequest = { showMotionPicker = false },
            title = { Text("Gaya animasi") },
            text = {
                Column(verticalArrangement = Arrangement.spacedBy(2.dp)) {
                    Text(
                        "Pilih rasa saat pindah halaman. Siput halus paling santai, paling lambat untuk dibaca.",
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                    )
                    Spacer(Modifier.size(10.dp))
                    MotionStyle.entries.forEach { style ->
                        val selected = style == AppearanceController.motion.value
                        Row(
                            verticalAlignment = Alignment.CenterVertically,
                            modifier = Modifier
                                .fillMaxWidth()
                                .clip(CircleShape)
                                .clickable {
                                    AppearanceController.selectMotion(context.applicationContext, style)
                                    showMotionPicker = false
                                }
                                .padding(horizontal = 6.dp, vertical = 10.dp),
                        ) {
                            Column(Modifier.weight(1f)) {
                                Text(
                                    style.label,
                                    style = MaterialTheme.typography.bodyLarge,
                                    fontWeight = if (selected) FontWeight.Bold else FontWeight.Normal,
                                )
                                Text(
                                    style.description,
                                    style = MaterialTheme.typography.bodySmall,
                                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                                )
                            }
                            if (selected) {
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
                TextButton(onClick = { showMotionPicker = false }) { Text("Tutup") }
            },
        )
    }

    if (showTextSizePicker) {
        AlertDialog(
            onDismissRequest = { showTextSizePicker = false },
            title = { Text("Ukuran tulisan") },
            text = {
                Column(verticalArrangement = Arrangement.spacedBy(2.dp)) {
                    Text(
                        "Atur ukuran tulisan agar nyaman dibaca. Berlaku untuk semua halaman.",
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                    )
                    Spacer(Modifier.size(10.dp))
                    AppearanceController.TextSizes.forEach { option ->
                        val selected = option.scale == AppearanceController.textScale.floatValue
                        Row(
                            verticalAlignment = Alignment.CenterVertically,
                            modifier = Modifier
                                .fillMaxWidth()
                                .clip(CircleShape)
                                .clickable {
                                    AppearanceController.selectTextScale(context.applicationContext, option.scale)
                                    showTextSizePicker = false
                                }
                                .padding(horizontal = 6.dp, vertical = 10.dp),
                        ) {
                            Text(
                                option.label,
                                style = MaterialTheme.typography.bodyLarge,
                                fontWeight = if (selected) FontWeight.Bold else FontWeight.Normal,
                            )
                            Spacer(Modifier.weight(1f))
                            Text(
                                "Aa",
                                style = MaterialTheme.typography.titleMedium,
                                fontWeight = FontWeight.Bold,
                                color = if (selected) MaterialTheme.colorScheme.primary else MaterialTheme.colorScheme.onSurfaceVariant,
                            )
                            if (selected) {
                                Spacer(Modifier.size(10.dp))
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
                TextButton(onClick = { showTextSizePicker = false }) { Text("Tutup") }
            },
        )
    }

    if (showLockPicker) {
        AppLockDialog(
            enabled = AppLockController.enabled.value,
            context = context,
            onDismiss = { showLockPicker = false },
        )
    }

    if (showAbout) {
        AlertDialog(
            onDismissRequest = { showAbout = false },
            title = { Text("Tentang Kang Cuan") },
            text = {
                Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                    Text(
                        "Soal cuan, urusan Kang Cuan.",
                        style = MaterialTheme.typography.titleMedium,
                        fontWeight = FontWeight.Bold,
                    )
                    Text(
                        "Kang Cuan — tukang cuan keluarga. Membantu mengatur pemasukan, pengeluaran, jajan, cicilan & dana darurat agar tetap beres.",
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
            onDismissRequest = viewModel::dismissUpdates,
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
            onDismissRequest = viewModel::dismissUpdates,
            title = { Text("Belum berhasil") },
            text = { Text("${state.installError}. Kamu bisa coba lagi dari menu Periksa Pembaruan.") },
            confirmButton = { TextButton(onClick = viewModel::dismissUpdates) { Text("Tutup") } },
        )
    } else if (state.updateError != null) {
        AlertDialog(
            onDismissRequest = viewModel::dismissUpdates,
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
                text = { Text("Kamu sudah memakai Kang Cuan versi ${BuildConfig.VERSION_NAME}. Tidak ada pembaruan.") },
                confirmButton = { TextButton(onClick = viewModel::dismissUpdates) { Text("Tutup") } },
            )
        } else {
            AlertDialog(
                onDismissRequest = {
                    if (!state.downloading) viewModel.dismissUpdates()
                },
                title = { Text(if (state.downloading) "Memperbarui..." else "Versi baru tersedia") },
text = {
                    Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                        Text(
                            "Kang Cuan ${update.latestVersionName}",
                            style = MaterialTheme.typography.titleSmall,
                            fontWeight = FontWeight.Bold,
                        )
                        Text(
                            if (update.isMandatory) "Update wajib untuk melanjutkan penggunaan aplikasi." else "Update disarankan agar fitur dan keamanan tetap terbaru.",
                            style = MaterialTheme.typography.labelSmall,
                            color = if (update.isMandatory) MaterialTheme.colorScheme.error else MaterialTheme.colorScheme.onSurfaceVariant,
                            fontWeight = if (update.isMandatory) FontWeight.SemiBold else FontWeight.Normal,
                        )
                        if (update.notes.isNotBlank()) {
                            // Release notes can be a long changelog, so cap the
                            // height and let it scroll. Without this the dialog
                            // grows past the bottom of the screen.
                            Box(
                                Modifier
                                    .fillMaxWidth()
                                    .heightIn(max = 160.dp)
                                    .verticalScroll(rememberScrollState()),
                            ) {
                                Text(
                                    update.notes,
                                    style = MaterialTheme.typography.labelSmall,
                                    lineHeight = 15.sp,
                                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                                )
                            }
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
                                "Mengunduh ${(state.installProgress * 100).toInt()}% ...",
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
private fun AppLockDialog(
    enabled: Boolean,
    context: android.content.Context,
    onDismiss: () -> Unit,
) {
    var pin by remember { mutableStateOf("") }
    var confirmation by remember { mutableStateOf("") }
    var error by remember { mutableStateOf<String?>(null) }

    AlertDialog(
        onDismissRequest = onDismiss,
        title = { Text(if (enabled) "Kunci aplikasi aktif" else "Kunci aplikasi") },
        text = {
            Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                Text("PIN melindungi data keluarga saat aplikasi dibuka kembali.", style = MaterialTheme.typography.bodySmall)
                if (!enabled) {
                    OutlinedTextField(
                        value = pin,
                        onValueChange = { pin = it.filter(Char::isDigit).take(6); error = null },
                        label = { Text("PIN 4-6 digit") },
                        singleLine = true,
                        visualTransformation = PasswordVisualTransformation(),
                        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.NumberPassword),
                    )
                    OutlinedTextField(
                        value = confirmation,
                        onValueChange = { confirmation = it.filter(Char::isDigit).take(6); error = null },
                        label = { Text("Ulangi PIN") },
                        singleLine = true,
                        visualTransformation = PasswordVisualTransformation(),
                        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.NumberPassword),
                    )
                } else {
                    Text("Kunci akan aktif saat aplikasi dibuka kembali.", style = MaterialTheme.typography.bodySmall)
                }
                error?.let { Text(it, color = MaterialTheme.colorScheme.error, style = MaterialTheme.typography.bodySmall) }
            }
        },
        confirmButton = {
            if (enabled) {
                TextButton(onClick = { AppLockController.disable(context); onDismiss() }) { Text("Matikan") }
            } else {
                TextButton(onClick = {
                    when {
                        pin.length !in 4..6 -> error = "PIN harus 4-6 digit."
                        pin != confirmation -> error = "PIN tidak sama."
                        else -> { AppLockController.setPin(context, pin); onDismiss() }
                    }
                }) { Text("Aktifkan") }
            }
        },
        dismissButton = { TextButton(onClick = onDismiss) { Text("Batal") } },
    )
}

@Composable
private fun AdvisorCard(
    enabled: Boolean,
    accessible: Boolean?,
    message: String?,
    loading: Boolean,
    toggling: Boolean,
    canEditProfile: Boolean,
    onToggle: (Boolean) -> Unit,
    onEditProfile: () -> Unit,
) {
    Card(
        colors = CardDefaults.cardColors(
            containerColor = if (enabled) MaterialTheme.colorScheme.primaryContainer else MaterialTheme.colorScheme.surfaceVariant,
        ),
        modifier = Modifier.fillMaxWidth(),
    ) {
        Row(
            modifier = Modifier.padding(14.dp),
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Column(Modifier.weight(1f)) {
                Text(
                    "Pendamping Keuangan",
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.Bold,
                )
                Spacer(Modifier.size(2.dp))
                Text(
                    when {
                        loading -> "Mengecek status..."
                        message != null && !enabled -> message
                        enabled && accessible == false -> message.orEmpty()
                        enabled -> {
                            "Aktif. Kang Cuan menyusun pembagian pemasukan, kebutuhan, jajan & dana darurat berdasarkan kondisi keluarga."
                        }
                        else -> {
                            "Bantu Kang Cuan menyusun pembagian pemasukan, kebutuhan harian, jajan, cicilan & dana darurat keluarga."
                        }
                    },
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                )
                message?.takeIf { it.isNotBlank() && enabled }?.let {
                    Spacer(Modifier.size(2.dp))
                    Text(it, style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                }
            }
            Spacer(Modifier.size(12.dp))
            if (loading || toggling) {
                CircularProgressIndicator(modifier = Modifier.size(20.dp), strokeWidth = 2.dp)
            } else {
                androidx.compose.material3.Switch(
                    checked = enabled,
                    onCheckedChange = onToggle,
                )
            }
        }
        if (canEditProfile) {
            androidx.compose.material3.HorizontalDivider(
                color = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.08f),
            )
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .clickable(onClick = onEditProfile)
                    .padding(horizontal = 14.dp, vertical = 10.dp),
                verticalAlignment = Alignment.CenterVertically,
            ) {
                Icon(
                    Icons.Filled.Settings,
                    contentDescription = null,
                    tint = MaterialTheme.colorScheme.primary,
                    modifier = Modifier.size(18.dp),
                )
                Spacer(Modifier.size(10.dp))
                Text(
                    "Profil & pengaturan",
                    style = MaterialTheme.typography.bodyLarge,
                    fontWeight = FontWeight.SemiBold,
                    modifier = Modifier.weight(1f),
                )
                Icon(
                    Icons.AutoMirrored.Filled.KeyboardArrowRight,
                    contentDescription = null,
                    tint = MaterialTheme.colorScheme.onSurfaceVariant,
                )
            }
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

private fun AppearanceController.textSizeLabel(): String =
    TextSizes.firstOrNull { it.scale == textScale.floatValue }?.label ?: TextSizes.first().label

/**
 * Notification permission state in plain words, so the reader knows why the
 * daily "belum mencatat" nudge is (or is not) reaching them.
 */
private fun notificationRowSubtitle(context: Context): String {
    if (Build.VERSION.SDK_INT < 33) return "Aktif untuk pengingat harian"
    val granted = ContextCompat.checkSelfPermission(context, Manifest.permission.POST_NOTIFICATIONS) ==
        PackageManager.PERMISSION_GRANTED

    return if (granted) {
        "Aktif — pengingat harian terkirim"
    } else {
        "Belum diizinkan — ketuk untuk mengaktifkan"
    }
}

private fun openNotificationSettings(context: Context) {
    if (Build.VERSION.SDK_INT < 33) return

    val intent = android.content.Intent().apply {
        action = android.provider.Settings.ACTION_APP_NOTIFICATION_SETTINGS
        putExtra(android.provider.Settings.EXTRA_APP_PACKAGE, context.packageName)
        addFlags(android.content.Intent.FLAG_ACTIVITY_NEW_TASK)
    }
    runCatching { context.startActivity(intent) }
}
