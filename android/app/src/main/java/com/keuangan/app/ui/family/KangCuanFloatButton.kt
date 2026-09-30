package com.keuangan.app.ui.family

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ChildCare
import androidx.compose.material3.Button
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.ModalBottomSheet
import androidx.compose.material3.Surface
import androidx.compose.material3.Switch
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.rememberModalBottomSheetState
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.keuangan.app.ui.theme.Amber600
import com.keuangan.app.ui.theme.AppThemes
import com.keuangan.app.ui.theme.KangCuanFloatController
import com.keuangan.app.ui.theme.ThemeController

/**
 * The floating Kang Cuan avatar that hovers just above the bottom bar on the
 * Ringkasan tab. Tapping it opens a bottom sheet with a short saran and two
 * quick actions (Lihat rencana / Sesuaikan anggaran). The reader can hide the
 * avatar from inside the sheet, and switch it back on in Lainnya → Kang Cuan.
 */
@Composable
fun KangCuanFloatButton(
    onOpenRencana: () -> Unit,
    onOpenAnggaran: () -> Unit,
) {
    val context = LocalContext.current
    var showSheet by remember { mutableStateOf(false) }

    if (!KangCuanFloatController.visible.value) return

    androidx.compose.material3.Surface(
        modifier = Modifier
            .size(56.dp)
            .clickable { showSheet = true },
        shape = CircleShape,
        shadowElevation = 8.dp,
        color = Color.Transparent,
    ) {
        GradientAvatar(
            icon = Icons.Filled.ChildCare,
            contentDescription = "Buka saran Kang Cuan",
            modifier = Modifier.size(56.dp),
        )
    }

    if (showSheet) {
        KangCuanSheet(
            onDismiss = { showSheet = false },
            onOpenRencana = {
                showSheet = false
                onOpenRencana()
            },
            onOpenAnggaran = {
                showSheet = false
                onOpenAnggaran()
            },
        )
    }
}

@Composable
private fun GradientAvatar(
    icon: androidx.compose.ui.graphics.vector.ImageVector,
    contentDescription: String?,
    modifier: Modifier = Modifier,
) {
    val appTheme = AppThemes.firstOrNull { it.id == ThemeController.themeId.value }
        ?: AppThemes.first()
    Box(
        modifier = modifier.background(appTheme.gradient, CircleShape),
        contentAlignment = Alignment.Center,
    ) {
        Icon(
            icon,
            contentDescription = contentDescription,
            tint = Color.White,
            modifier = Modifier.size(24.dp),
        )
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun KangCuanSheet(
    onDismiss: () -> Unit,
    onOpenRencana: () -> Unit,
    onOpenAnggaran: () -> Unit,
) {
    val context = LocalContext.current
    val sheetState = rememberModalBottomSheetState()

    ModalBottomSheet(
        onDismissRequest = onDismiss,
        sheetState = sheetState,
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(horizontal = 20.dp),
            verticalAlignment = Alignment.CenterVertically,
        ) {
            GradientAvatar(icon = Icons.Filled.ChildCare, contentDescription = null, modifier = Modifier.size(44.dp))
            Spacer(Modifier.width(12.dp))
            Column {
                Text("Kang Cuan", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
                Text(
                    "Tukang cuan keluarga di ujung jari.",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                )
            }
        }

        Spacer(Modifier.height(10.dp))
        Card(
            modifier = Modifier
                .fillMaxWidth()
                .padding(horizontal = 20.dp),
            colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.6f)),
        ) {
            Text(
                "Jaga rutinitas malam: catat pengeluaran hari ini, lihat Ringkasan, lalu tidur tenang. Kang Cuan siap membantu mengatur rencana dan anggaran keluarga.",
                style = MaterialTheme.typography.bodyMedium,
                modifier = Modifier.padding(14.dp),
            )
        }

        Spacer(Modifier.height(12.dp))
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(horizontal = 20.dp),
            horizontalArrangement = androidx.compose.foundation.layout.Arrangement.spacedBy(10.dp),
        ) {
            TextButton(
                onClick = onOpenRencana,
                modifier = Modifier.weight(1f),
            ) {
                Text("Lihat rencana")
            }
            Button(
                onClick = onOpenAnggaran,
                modifier = Modifier.weight(1f),
            ) {
                Text("Sesuaikan anggaran")
            }
        }

        Spacer(Modifier.height(8.dp))
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .clickable {
                    KangCuanFloatController.setVisible(context, !KangCuanFloatController.visible.value)
                }
                .padding(horizontal = 20.dp, vertical = 4.dp),
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Text(
                "Tampilkan avatar Kang Cuan di Ringkasan",
                style = MaterialTheme.typography.bodyMedium,
                color = Amber600,
                modifier = Modifier.weight(1f),
            )
            Switch(
                checked = KangCuanFloatController.visible.value,
                onCheckedChange = { value -> KangCuanFloatController.setVisible(context, value) },
            )
        }
        Spacer(Modifier.height(20.dp))
    }
}