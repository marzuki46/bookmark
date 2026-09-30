package com.keuangan.app.ui.family

import androidx.compose.foundation.clickable
import androidx.compose.foundation.Image
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
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.ContentCopy
import androidx.compose.material.icons.filled.Delete
import androidx.compose.material.icons.filled.Edit
import androidx.compose.material.icons.filled.Group
import androidx.compose.material.icons.filled.Home
import androidx.compose.material.icons.filled.Person
import androidx.compose.material.icons.filled.QrCodeScanner
import androidx.compose.material.icons.filled.Security
import androidx.compose.material.icons.filled.WorkspacePremium
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.FilterChip
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
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
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.asImageBitmap
import androidx.compose.ui.platform.LocalClipboardManager
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.AnnotatedString
import androidx.compose.ui.text.font.FontFamily
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import android.widget.Toast
import androidx.compose.foundation.shape.RoundedCornerShape
import com.keuangan.app.data.FamilyMemberDto
import com.keuangan.app.ui.components.GradientHeader
import com.keuangan.app.ui.components.TextChoiceDropdown
import com.keuangan.app.ui.formatFullDate
import com.keuangan.app.ui.theme.Amber100
import com.keuangan.app.ui.theme.Amber600
import com.keuangan.app.ui.theme.Teal100
import com.keuangan.app.ui.theme.Teal700
import com.keuangan.app.util.QrCode

/** Closed list so Kang Cuan's greetings can stay religiously neutral. */
private val RELIGION_OPTIONS = listOf(
    "Islam",
    "Kristen",
    "Katolik",
    "Hindu",
    "Buddha",
    "Konghucu",
)

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun FamilyProfileScreen(
    familyId: Int,
    viewModel: FamilyProfileViewModel,
    onOpenSubscription: () -> Unit,
    onLogout: () -> Unit,
) {
    val state by viewModel.state.collectAsState()
    var confirmLogout by remember { mutableStateOf(false) }
    var showAddMember by remember { mutableStateOf(false) }
    var showQr by remember { mutableStateOf(false) }
    var memberName by rememberSaveable { mutableStateOf("") }
    var memberRole by rememberSaveable { mutableStateOf<String?>(null) }
    var editingMember by remember { mutableStateOf<FamilyMemberDto?>(null) }
    var deletingMember by remember { mutableStateOf<FamilyMemberDto?>(null) }
    val clipboard = LocalClipboardManager.current
    val context = LocalContext.current

    fun openAddMember() {
        val family = state.family ?: return
        if (family.members.size >= 5) {
            viewModel.showMessage(
                "Anggota keluarga sudah 5 orang (batas maksimal). Kalau ingin menambah, satu anggota perlu dihapus dulu.",
            )
            return
        }
        memberName = ""
        memberRole = when {
            family.members.none { it.payerRole == "husband" } -> "husband"
            family.members.none { it.payerRole == "wife" } -> "wife"
            else -> null
        }
        showAddMember = true
    }

    LaunchedEffect(familyId) {
        viewModel.load(familyId)
    }

    Scaffold(
        topBar = {
            GradientHeader(
                title = "Keluarga & Akun",
                subtitle = "Kelola profil, peran, kode login & langganan",
            )
        },
    ) { padding ->
        LazyColumn(
            modifier = Modifier
                .fillMaxSize()
                .padding(padding),
            contentPadding = PaddingValues(16.dp, 16.dp, 16.dp, 96.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp),
        ) {
            state.actionMessage?.let { message ->
                item {
                    Row(verticalAlignment = Alignment.Top) {
                        Card(
                            modifier = Modifier.weight(1f),
                            colors = CardDefaults.cardColors(containerColor = Amber100),
                        ) {
                            Text(
                                message,
                                style = MaterialTheme.typography.bodyMedium,
                                modifier = Modifier.padding(12.dp),
                            )
                        }
                        IconButton(onClick = viewModel::dismissMessage) {
                            Icon(
                                Icons.Filled.Close,
                                contentDescription = "Tutup",
                                tint = Amber600,
                                modifier = Modifier.size(18.dp),
                            )
                        }
                    }
                }
            }

            state.error?.let { error ->
                item {
                    Text(
                        error,
                        color = MaterialTheme.colorScheme.error,
                        style = MaterialTheme.typography.bodyMedium,
                    )
                }
            }

            state.me?.let { me ->
                item {
                    ProfileCard(
                        name = me.name ?: "Akun #${me.id}",
                        email = me.email,
                        about = me.about,
                        religion = me.religion,
                        saving = state.saving,
                        onSave = viewModel::saveProfile,
                    )
                }
                item {
                    SubscriptionCard(
                        active = me.subscription?.active == true,
                        planName = me.subscription?.planName,
                        expiresAt = me.subscription?.expiresAt,
                        onOpenSubscription = onOpenSubscription,
                    )
                }
            }

            state.family?.let { family ->
                val isOwner = family.role == "owner"
                item {
                    Card(
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
                        modifier = Modifier.fillMaxWidth(),
                    ) {
                        Row(
                            Modifier
                                .fillMaxWidth()
                                .let { m ->
                                    if (isOwner) m.clickable { openAddMember() } else m
                                }
                                .padding(16.dp),
                            verticalAlignment = Alignment.CenterVertically,
                        ) {
                            Icon(
                                Icons.Filled.Home,
                                contentDescription = null,
                                tint = Teal700,
                                modifier = Modifier.size(28.dp),
                            )
                            Spacer(Modifier.size(12.dp))
                            Column(Modifier.weight(1f)) {
                                Text(
                                    family.name,
                                    style = MaterialTheme.typography.titleMedium,
                                    fontWeight = FontWeight.SemiBold,
                                )
                                Text(
                                    if (isOwner) {
                                        "Ketuk untuk tambah anggota · ${family.members.size}/5"
                                    } else {
                                        "${family.members.size}/5 anggota"
                                    },
                                    style = MaterialTheme.typography.bodySmall,
                                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                                )
                            }
                            if (isOwner) {
                                Icon(
                                    Icons.Filled.Add,
                                    contentDescription = "Tambah anggota",
                                    tint = Teal700,
                                    modifier = Modifier.size(24.dp).clickable { openAddMember() },
                                )
                            }
                        }
                    }
                }

                item { SectionHeading("Anggota") }
                items(family.members, key = { it.userId }) { member ->
                    MemberRow(
                        member = member,
                        isMe = member.userId == viewModel.currentUserId,
                        onSetRole = { role -> viewModel.setPayerRole(familyId, role) },
                        canManage = isOwner && member.userId != viewModel.currentUserId,
                        onEdit = { editingMember = member },
                        onDelete = { deletingMember = member },
                    )
                }
            }

            item { SectionHeading("Keamanan") }
            item {
                Card(
                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                    elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
                    modifier = Modifier.fillMaxWidth(),
                ) {
                    Row(Modifier.padding(14.dp), verticalAlignment = Alignment.CenterVertically) {
                        Icon(
                            Icons.Filled.Security,
                            contentDescription = null,
                            tint = Teal700,
                            modifier = Modifier.size(20.dp),
                        )
                        Spacer(Modifier.size(10.dp))
                        Column(Modifier.weight(1f)) {
                            Text("Kode login keluarga", fontWeight = FontWeight.SemiBold)
                            Text(
                                "Kode ini untuk masuk ke aplikasi. Ubah kode membuat semua perangkat lain keluar.",
                                style = MaterialTheme.typography.bodySmall,
                                color = MaterialTheme.colorScheme.onSurfaceVariant,
                            )
                        }
                        TextButton(
                            onClick = { viewModel.rotate(familyId) },
                            enabled = !state.rotating,
                        ) {
                            if (state.rotating) {
                                CircularProgressIndicator(
                                    modifier = Modifier.size(16.dp),
                                    strokeWidth = 2.dp,
                                )
                            } else {
                                Text("Ganti kode")
                            }
                        }
                    }

                    val code = state.loginCode
                    val loadingCode = state.loadingCode
                    if (code != null) {
                        Row(
                            Modifier
                                .fillMaxWidth()
                                .padding(horizontal = 14.dp, vertical = 12.dp),
                            verticalAlignment = Alignment.CenterVertically,
                        ) {
                            Card(colors = CardDefaults.cardColors(containerColor = Amber100)) {
                                Text(
                                    code,
                                    style = MaterialTheme.typography.titleMedium,
                                    fontWeight = FontWeight.Bold,
                                    color = Amber600,
                                    modifier = Modifier.padding(horizontal = 14.dp, vertical = 10.dp),
                                )
                            }
                            Spacer(Modifier.size(10.dp))
                            TextButton(onClick = {
                                clipboard.setText(AnnotatedString(code))
                                Toast.makeText(context, "Kode disalin", Toast.LENGTH_SHORT).show()
                            }) {
                                Icon(
                                    Icons.Filled.ContentCopy,
                                    contentDescription = null,
                                    modifier = Modifier.size(16.dp),
                                )
                                Spacer(Modifier.size(6.dp))
                                Text("Salin")
                            }
                            TextButton(onClick = { showQr = true }) {
                                Icon(
                                    Icons.Filled.QrCodeScanner,
                                    contentDescription = null,
                                    modifier = Modifier.size(16.dp),
                                )
                                Spacer(Modifier.size(6.dp))
                                Text("QR")
                            }
                        }
                    } else if (loadingCode) {
                        Row(
                            Modifier
                                .fillMaxWidth()
                                .padding(horizontal = 14.dp, vertical = 12.dp),
                            verticalAlignment = Alignment.CenterVertically,
                        ) {
                            CircularProgressIndicator(modifier = Modifier.size(16.dp), strokeWidth = 2.dp)
                            Spacer(Modifier.size(10.dp))
                            Text("Memuat kode…", style = MaterialTheme.typography.bodySmall)
                        }
                    }
                }
            }

            item {
                OutlinedButton(
                    onClick = { confirmLogout = true },
                    modifier = Modifier.fillMaxWidth(),
                ) {
                    Text("Keluar dari aplikasi")
                }
            }
        }
    }

    state.newCode?.let { code ->
        AlertDialog(
            onDismissRequest = viewModel::dismissNewCode,
            title = { Text("Kode keluarga baru") },
            text = {
                Column {
                    Text("Simpan di tempat aman. Format: XXXX-XXXX-XXXX-XXXX")
                    Spacer(Modifier.height(10.dp))
                    Card(colors = CardDefaults.cardColors(containerColor = Amber100)) {
                        Text(
                            code,
                            style = MaterialTheme.typography.titleMedium,
                            fontWeight = FontWeight.Bold,
                            color = Amber600,
                            modifier = Modifier.padding(16.dp),
                        )
                    }
                }
            },
            confirmButton = {
                TextButton(onClick = viewModel::dismissNewCode) { Text("Selesai") }
            },
        )
    }

    if (confirmLogout) {
        AlertDialog(
            onDismissRequest = { confirmLogout = false },
            title = { Text("Keluar?") },
            text = { Text("Kamu masih terdaftar di keluarga. Masuk kembali menggunakan kode keluarga.") },
            confirmButton = {
                TextButton(onClick = {
                    confirmLogout = false
                    onLogout()
                }) { Text("Keluar") }
            },
            dismissButton = {
                TextButton(onClick = { confirmLogout = false }) { Text("Batal") }
            },
        )
    }

    if (showAddMember) {
        AddMemberDialog(
            adding = state.addingMember,
            name = memberName,
            role = memberRole,
            onNameChange = { memberName = it },
            onRoleChange = { memberRole = it },
            onDismiss = { if (!state.addingMember) showAddMember = false },
            onConfirm = {
                showAddMember = false
                viewModel.addMember(familyId, memberName, memberRole)
            },
        )
    }

    state.newMemberCode?.let { code ->
        NewMemberCodeDialog(
            code = code,
            onCopy = {
                clipboard.setText(AnnotatedString(code))
                Toast.makeText(context, "Kode disalin", Toast.LENGTH_SHORT).show()
            },
            onDismiss = viewModel::dismissNewMemberCode,
        )
    }

    val qrCode = state.loginCode
    if (showQr && qrCode != null) {
        AlertDialog(
            onDismissRequest = { showQr = false },
            title = { Text("Kode login keluarga") },
            text = {
                Column(horizontalAlignment = Alignment.CenterHorizontally) {
                    val bitmap = remember(qrCode) { QrCode.bitmap(qrCode, 480) }
                    Image(
                        bitmap = bitmap.asImageBitmap(),
                        contentDescription = "Barcode kode login keluarga",
                        modifier = Modifier
                            .size(210.dp)
                            .clip(RoundedCornerShape(12.dp)),
                    )
                    Spacer(Modifier.height(14.dp))
                    Text(
                        qrCode,
                        style = MaterialTheme.typography.titleMedium,
                        fontWeight = FontWeight.Bold,
                        fontFamily = FontFamily.Monospace,
                    )
                    Spacer(Modifier.height(8.dp))
                    Text(
                        "Anggota lain (suami/istri) bisa menekan \"Scan barcode\" di layar masuk untuk ikut terhubung ke keluarga.",
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                        modifier = Modifier.padding(horizontal = 4.dp),
                    )
                }
            },
            confirmButton = {
                TextButton(onClick = { showQr = false }) { Text("Tutup") }
            },
        )
    }

    editingMember?.let { member ->
        EditMemberDialog(
            member = member,
            onDismiss = { editingMember = null },
            onSave = { name, role, relationship, visibility ->
                editingMember = null
                viewModel.updateMember(familyId, member, name, role, relationship, visibility)
            },
        )
    }

    deletingMember?.let { member ->
        AlertDialog(
            onDismissRequest = { deletingMember = null },
            title = { Text("Hapus anggota?") },
            text = { Text("Akses ${member.name ?: "anggota ini"} ke keluarga akan dicabut. Riwayat transaksi tetap aman.") },
            confirmButton = {
                TextButton(onClick = {
                    deletingMember = null
                    viewModel.deleteMember(familyId, member)
                }) { Text("Hapus") }
            },
            dismissButton = { TextButton(onClick = { deletingMember = null }) { Text("Batal") } },
        )
    }
}

@Composable
private fun AddMemberDialog(
    adding: Boolean,
    name: String,
    role: String?,
    onNameChange: (String) -> Unit,
    onRoleChange: (String?) -> Unit,
    onDismiss: () -> Unit,
    onConfirm: () -> Unit,
) {
    AlertDialog(
        onDismissRequest = onDismiss,
        title = { Text("Tambah anggota") },
        text = {
            Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
                OutlinedTextField(
                    value = name,
                    onValueChange = onNameChange,
                    label = { Text("Nama anggota") },
                    singleLine = true,
                    enabled = !adding,
                    modifier = Modifier.fillMaxWidth(),
                )
                Text(
                    "Peran pasangan (opsional)",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                )
                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    FilterChip(
                        selected = role == "husband",
                        onClick = { onRoleChange(if (role == "husband") null else "husband") },
                        label = { Text("Suami") },
                        enabled = !adding,
                    )
                    FilterChip(
                        selected = role == "wife",
                        onClick = { onRoleChange(if (role == "wife") null else "wife") },
                        label = { Text("Istri") },
                        enabled = !adding,
                    )
                }
            }
        },
        confirmButton = {
            TextButton(
                enabled = name.isNotBlank() && !adding,
                onClick = onConfirm,
            ) { Text("Tambah") }
        },
        dismissButton = {
            TextButton(enabled = !adding, onClick = onDismiss) { Text("Batal") }
        },
    )
}

@Composable
private fun EditMemberDialog(
    member: FamilyMemberDto,
    onDismiss: () -> Unit,
    onSave: (String, String?, String, Map<String, Boolean>) -> Unit,
) {
    var name by rememberSaveable(member.userId) { mutableStateOf(member.name.orEmpty()) }
    var role by rememberSaveable(member.userId) { mutableStateOf(member.payerRole) }
    var relationship by rememberSaveable(member.userId) { mutableStateOf(member.relationship) }

    // Absent and explicitly-null visibility both mean "not restricted yet".
    val visibility = member.visibility
    var canViewIncome by rememberSaveable(member.userId) { mutableStateOf(visibility?.get("income") ?: true) }
    var canViewExpense by rememberSaveable(member.userId) { mutableStateOf(visibility?.get("expense") ?: true) }
    var canViewDebts by rememberSaveable(member.userId) { mutableStateOf(visibility?.get("debts") ?: true) }

    AlertDialog(
        onDismissRequest = onDismiss,
        title = { Text("Ubah anggota") },
        text = {
            Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
                OutlinedTextField(
                    value = name,
                    onValueChange = { name = it },
                    label = { Text("Nama anggota") },
                    singleLine = true,
                    modifier = Modifier.fillMaxWidth(),
                )
                Text("Peran pasangan (opsional)", style = MaterialTheme.typography.bodySmall)
                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    FilterChip(selected = role == "husband", onClick = { role = if (role == "husband") null else "husband" }, label = { Text("Suami") })
                    FilterChip(selected = role == "wife", onClick = { role = if (role == "wife") null else "wife" }, label = { Text("Istri") })
                }
                Text("Hubungan", style = MaterialTheme.typography.bodySmall)
                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    FilterChip(selected = relationship == "adult", onClick = { relationship = "adult" }, label = { Text("Dewasa") })
                    FilterChip(selected = relationship == "child", onClick = { relationship = "child" }, label = { Text("Anak") })
                }
                Text("Data yang boleh dilihat", style = MaterialTheme.typography.bodySmall)
                PermissionToggle("Pemasukan", canViewIncome) { canViewIncome = it }
                PermissionToggle("Pengeluaran", canViewExpense) { canViewExpense = it }
                PermissionToggle("Hutang", canViewDebts) { canViewDebts = it }
            }
        },
        confirmButton = {
            TextButton(
                onClick = {
                    onSave(
                        name.trim(),
                        role,
                        relationship,
                        mapOf("income" to canViewIncome, "expense" to canViewExpense, "debts" to canViewDebts),
                    )
                },
                enabled = name.isNotBlank(),
            ) { Text("Simpan") }
        },
        dismissButton = { TextButton(onClick = onDismiss) { Text("Batal") } },
    )
}

@Composable
private fun PermissionToggle(label: String, checked: Boolean, onCheckedChange: (Boolean) -> Unit) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .clickable { onCheckedChange(!checked) },
        verticalAlignment = Alignment.CenterVertically,
    ) {
        androidx.compose.material3.Checkbox(checked = checked, onCheckedChange = onCheckedChange)
        Text(label, style = MaterialTheme.typography.bodyMedium)
    }
}

@Composable
private fun NewMemberCodeDialog(
    code: String,
    onCopy: () -> Unit,
    onDismiss: () -> Unit,
) {
    AlertDialog(
        onDismissRequest = onDismiss,
        title = { Text("Kode login anggota baru") },
        text = {
            Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                Text("Bagikan kode ini kepada anggota. Dia login dari HP miliknya lalu masuk ke keluarga.")
                Card(colors = CardDefaults.cardColors(containerColor = Amber100)) {
                    Text(
                        code,
                        style = MaterialTheme.typography.titleMedium,
                        fontWeight = FontWeight.Bold,
                        color = Amber600,
                        modifier = Modifier.padding(16.dp),
                    )
                }
                Text(
                    "Kode ditampilkan sekali dan tetap berlaku selama tidak diganti di menu Keamanan.",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                )
            }
        },
        confirmButton = {
            Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                TextButton(onClick = onCopy) {
                    Icon(Icons.Filled.ContentCopy, contentDescription = null, modifier = Modifier.size(16.dp))
                    Spacer(Modifier.size(6.dp))
                    Text("Salin")
                }
                TextButton(onClick = onDismiss) { Text("Selesai") }
            }
        },
    )
}

@Composable
private fun ProfileCard(
    name: String,
    email: String?,
    about: String?,
    religion: String?,
    saving: Boolean,
    onSave: (name: String, about: String, religion: String?) -> Unit,
) {
    var editing by rememberSaveable { mutableStateOf(false) }
    var draftName by rememberSaveable(name) { mutableStateOf(name) }
    var draftAbout by rememberSaveable(about) { mutableStateOf(about ?: "") }
    var draftReligion by rememberSaveable(religion) { mutableStateOf(religion ?: "") }

    Card(
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
        modifier = Modifier.fillMaxWidth(),
    ) {
        Column(Modifier.padding(16.dp)) {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Icon(
                    Icons.Filled.Person,
                    contentDescription = null,
                    tint = Teal700,
                    modifier = Modifier.size(24.dp),
                )
                Spacer(Modifier.size(10.dp))
                Column(Modifier.weight(1f)) {
                    Text(name, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.SemiBold)
                    email?.let {
                        Text(it, style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
                    }
                }
                TextButton(onClick = { editing = true }) { Text("Edit") }
            }
            (about ?: "").takeIf { it.isNotBlank() }?.let {
                Spacer(Modifier.height(10.dp))
                Text(it, style = MaterialTheme.typography.bodyMedium)
            }
            (religion ?: "").takeIf { it.isNotBlank() }?.let {
                Spacer(Modifier.height(6.dp))
                Text(
                    "Agama: $it",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                )
            }
        }
    }

    if (editing) {
        AlertDialog(
            onDismissRequest = { if (!saving) editing = false },
            title = { Text("Edit profil") },
            text = {
                Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                    OutlinedTextField(
                        value = draftName,
                        onValueChange = { draftName = it },
                        label = { Text("Nama") },
                        singleLine = true,
                        enabled = !saving,
                        modifier = Modifier.fillMaxWidth(),
                    )
                    OutlinedTextField(
                        value = draftAbout,
                        onValueChange = { draftAbout = it },
                        label = { Text("Tentang (opsional)") },
                        enabled = !saving,
                        minLines = 3,
                        modifier = Modifier.fillMaxWidth(),
                    )
                    TextChoiceDropdown(
                        label = "Agama (opsional, untuk sapaan Kang Cuan yang netral)",
                        choices = RELIGION_OPTIONS,
                        selected = draftReligion.takeIf { it.isNotBlank() },
                        onSelect = { draftReligion = it.orEmpty() },
                        modifier = Modifier.fillMaxWidth(),
                    )
                }
            },
            confirmButton = {
                TextButton(
                    enabled = !saving,
                    onClick = {
                        onSave(draftName, draftAbout, draftReligion)
                        editing = false
                    },
                ) { Text("Simpan") }
            },
            dismissButton = {
                TextButton(
                    enabled = !saving,
                    onClick = { editing = false },
                ) { Text("Batal") }
            },
        )
    }
}

@Composable
private fun SubscriptionCard(
    active: Boolean,
    planName: String?,
    expiresAt: String?,
    onOpenSubscription: () -> Unit,
) {
    val (bg, fg) = if (active) Teal100 to Teal700 else Amber100 to Amber600
    Card(
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
        modifier = Modifier.fillMaxWidth(),
    ) {
        Row(Modifier.padding(14.dp), verticalAlignment = Alignment.CenterVertically) {
            Icon(
                Icons.Filled.WorkspacePremium,
                contentDescription = null,
                tint = Teal700,
                modifier = Modifier.size(20.dp),
            )
            Spacer(Modifier.size(10.dp))
            Column(Modifier.weight(1f)) {
                Text("Langganan", fontWeight = FontWeight.SemiBold)
                if (active) {
                    Text(
                        "${planName ?: "Paket aktif"}${expiresAt?.let { " · s/d ${formatFullDate(it)}" } ?: ""}",
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                    )
                } else {
                    Text(
                        "Belum berlangganan",
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                    )
                }
            }
            TextButton(onClick = onOpenSubscription) { Text("Kelola") }
        }
    }
}

@Composable
fun SectionHeading(text: String) {
    Text(
        text,
        style = MaterialTheme.typography.titleSmall,
        fontWeight = FontWeight.SemiBold,
        modifier = Modifier.padding(top = 4.dp),
    )
}

@Composable
private fun MemberRow(
    member: FamilyMemberDto,
    isMe: Boolean,
    onSetRole: (String) -> Unit,
    canManage: Boolean,
    onEdit: () -> Unit,
    onDelete: () -> Unit,
) {
    val (chipBg, chipFg) = payerChip(member.payerRole)

    Card(
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
        modifier = Modifier.fillMaxWidth(),
    ) {
        Column(Modifier.padding(14.dp)) {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Icon(
                    if (isMe) Icons.Filled.Person else Icons.Filled.Group,
                    contentDescription = null,
                    tint = Teal700,
                    modifier = Modifier.size(22.dp),
                )
                Spacer(Modifier.size(10.dp))
                Column(Modifier.weight(1f)) {
                    Text(
                        member.name ?: "Anggota #${member.userId}",
                        style = MaterialTheme.typography.bodyLarge,
                        maxLines = 1,
                    )
                    if (isMe) {
                        Text(
                            "Akun ini",
                            style = MaterialTheme.typography.labelSmall,
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                        )
                    }
                }
                PayerBadge(member.payerRole, chipBg, chipFg)
                if (canManage) {
                    IconButton(onClick = onEdit) {
                        Icon(Icons.Filled.Edit, contentDescription = "Ubah anggota")
                    }
                    IconButton(onClick = onDelete) {
                        Icon(Icons.Filled.Delete, contentDescription = "Hapus anggota", tint = MaterialTheme.colorScheme.error)
                    }
                }
            }
            if (isMe) {
                Spacer(Modifier.height(8.dp))
                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    TextButton(
                        onClick = { onSetRole("husband") },
                        enabled = member.payerRole != "husband",
                    ) { Text("Jadikan Suami") }
                    TextButton(
                        onClick = { onSetRole("wife") },
                        enabled = member.payerRole != "wife",
                    ) { Text("Jadikan Istri") }
                }
            }
        }
    }
}

@Composable
private fun PayerBadge(role: String?, bg: Color, fg: Color) {
    Card(colors = CardDefaults.cardColors(containerColor = bg)) {
        Text(
            payerLabel(role),
            style = MaterialTheme.typography.labelMedium,
            color = fg,
            modifier = Modifier.padding(horizontal = 10.dp, vertical = 4.dp),
        )
    }
}
