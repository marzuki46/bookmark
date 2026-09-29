package com.keuangan.app.ui.components

import androidx.compose.animation.AnimatedVisibility
import androidx.compose.animation.core.tween
import androidx.compose.animation.slideInVertically
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.offset
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.statusBarsPadding
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.rounded.CalendarMonth
import androidx.compose.material.icons.rounded.Check
import androidx.compose.material.icons.rounded.Lightbulb
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.DatePicker
import androidx.compose.material3.DatePickerDialog
import androidx.compose.material3.DropdownMenuItem
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.ExposedDropdownMenuBox
import androidx.compose.material3.ExposedDropdownMenuDefaults
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.MenuAnchorType
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.OutlinedTextFieldDefaults
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.rememberDatePickerState
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import com.keuangan.app.ui.formatFullDate
import com.keuangan.app.ui.parseIsoDate
import com.keuangan.app.ui.theme.AppThemes
import com.keuangan.app.ui.theme.ThemeController
import java.time.Instant
import java.time.LocalDate
import java.time.ZoneOffset

/** Happy gradient banner used as the top of every screen. Follows the theme. */
@Composable
fun GradientHeader(
    title: String,
    subtitle: String? = null,
    modifier: Modifier = Modifier,
    trailing: (@Composable () -> Unit)? = null,
) {
    val appTheme = AppThemes.firstOrNull { it.id == ThemeController.themeId.value }
        ?: AppThemes.first()
    Box(
        modifier = modifier
            .fillMaxWidth()
            .statusBarsPadding()
            .clip(RoundedCornerShape(bottomStart = 20.dp, bottomEnd = 20.dp))
            .background(appTheme.gradient)
            .padding(horizontal = 18.dp, vertical = 13.dp),
    ) {
        Box(
            Modifier
                .align(Alignment.BottomEnd)
                .offset(x = 26.dp, y = 34.dp)
                .size(96.dp)
                .background(Color.White.copy(alpha = 0.10f), CircleShape),
        )
        Box(
            Modifier
                .align(Alignment.TopEnd)
                .offset(x = 14.dp, y = (-12).dp)
                .size(38.dp)
                .background(Color.White.copy(alpha = 0.08f), CircleShape),
        )
        Column(Modifier.fillMaxWidth(0.86f)) {
            Text(
                text = title,
                style = MaterialTheme.typography.titleLarge,
                color = Color.White,
            )
            if (subtitle != null) {
                Spacer(Modifier.height(2.dp))
                Text(
                    text = subtitle,
                    style = MaterialTheme.typography.bodySmall,
                    color = Color.White.copy(alpha = 0.92f),
                    maxLines = 1,
                    overflow = TextOverflow.Ellipsis,
                )
            }
        }
        trailing?.let { action ->
            Box(Modifier.align(Alignment.CenterEnd)) { action() }
        }
    }
}

/**
 * The closing card every list page ends with. It fills what used to be dead
 * space under short lists, and stays identical on every screen so the app reads
 * as one surface. [icon] lets a page pick a matching glyph.
 */
@Composable
fun KangCuanTipCard(
    message: String,
    modifier: Modifier = Modifier,
    icon: androidx.compose.ui.graphics.vector.ImageVector = Icons.Rounded.Lightbulb,
    action: (@Composable () -> Unit)? = null,
) {
    Card(
        modifier = modifier.fillMaxWidth(),
        shape = RoundedCornerShape(18.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.55f)),
        elevation = CardDefaults.cardElevation(defaultElevation = 0.dp),
    ) {
        Row(
            Modifier.padding(14.dp),
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Box(
                Modifier
                    .size(34.dp)
                    .background(MaterialTheme.colorScheme.primaryContainer, CircleShape),
                contentAlignment = Alignment.Center,
            ) {
                Icon(
                    icon,
                    contentDescription = null,
                    tint = MaterialTheme.colorScheme.primary,
                    modifier = Modifier.size(19.dp),
                )
            }
            Spacer(Modifier.size(12.dp))
            Column(Modifier.weight(1f)) {
                Text(
                    "Tips Kang Cuan",
                    style = MaterialTheme.typography.labelLarge,
                    color = MaterialTheme.colorScheme.onSurface,
                )
                Spacer(Modifier.height(2.dp))
                Text(
                    message,
                    style = MaterialTheme.typography.bodyMedium,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                )
                action?.let {
                    Spacer(Modifier.height(6.dp))
                    it()
                }
            }
        }
    }
}

/** Text field that opens the Material3 date picker; stores ISO `yyyy-MM-dd`. */
@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun DateField(
    label: String,
    value: String?,
    onChange: (String?) -> Unit,
    modifier: Modifier = Modifier,
) {
    var open by remember { mutableStateOf(false) }
    val initialMillis = remember(value) { parseIsoDate(value)?.time }

    val isToday = value == LocalDate.now().toString()
    OutlinedTextField(
        value = if (isToday) "Hari ini" else formatFullDate(value),
        onValueChange = {},
        readOnly = true,
        label = { Text(label) },
        placeholder = { Text("Pilih tanggal") },
        trailingIcon = {
            IconButton(onClick = { open = true }) {
                Icon(
                    Icons.Rounded.CalendarMonth,
                    contentDescription = "Pilih tanggal",
                    tint = MaterialTheme.colorScheme.primary,
                )
            }
        },
        colors = OutlinedTextFieldDefaults.colors(
            focusedBorderColor = MaterialTheme.colorScheme.primary,
            unfocusedBorderColor = MaterialTheme.colorScheme.outline,
            focusedContainerColor = MaterialTheme.colorScheme.surface,
            unfocusedContainerColor = MaterialTheme.colorScheme.surface,
        ),
        modifier = modifier.fillMaxWidth(),
    )

    if (open) {
        val state = rememberDatePickerState(
            initialSelectedDateMillis = initialMillis ?: System.currentTimeMillis(),
        )
        DatePickerDialog(
            onDismissRequest = { open = false },
            confirmButton = {
                TextButton(
                    onClick = {
                        state.selectedDateMillis?.let { millis ->
                            val day = Instant.ofEpochMilli(millis)
                                .atZone(ZoneOffset.UTC)
                                .toLocalDate()
                            onChange(day.toString())
                        }
                        open = false
                    },
                ) { Text("Simpan") }
            },
            dismissButton = {
                TextButton(onClick = { open = false }) { Text("Batal") }
            },
        ) {
            DatePicker(state = state)
        }
    }
}

data class ChoiceItem(val id: Int, val name: String)

/**
 * Single-select dropdown. Tapping the already-selected item clears the
 * selection (mirrors the old chips behaviour). Used for categories/sources.
 */
@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ChoiceDropdown(
    label: String,
    choices: List<ChoiceItem>,
    selectedId: Int?,
    onSelect: (Int?) -> Unit,
    modifier: Modifier = Modifier,
    placeholder: String = "Pilih…",
) {
    var expanded by remember { mutableStateOf(false) }
    val selected = choices.firstOrNull { it.id == selectedId }

    ExposedDropdownMenuBox(
        expanded = expanded,
        onExpandedChange = { expanded = it },
        modifier = modifier.fillMaxWidth(),
    ) {
        OutlinedTextField(
            value = selected?.name.orEmpty(),
            onValueChange = {},
            readOnly = true,
            label = { Text(label) },
            placeholder = { Text(placeholder) },
            trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = expanded) },
            colors = ExposedDropdownMenuDefaults.textFieldColors(),
            modifier = Modifier
                .menuAnchor(MenuAnchorType.PrimaryNotEditable)
                .fillMaxWidth(),
        )
        ExposedDropdownMenu(
            expanded = expanded,
            onDismissRequest = { expanded = false },
        ) {
            choices.forEach { choice ->
                val checked = choice.id == selectedId
                DropdownMenuItem(
                    text = { Text(choice.name) },
                    trailingIcon = if (checked) {
                        {
                            Icon(
                                Icons.Rounded.Check,
                                contentDescription = null,
                                tint = MaterialTheme.colorScheme.primary,
                            )
                        }
                    } else null,
                    onClick = {
                        onSelect(if (checked) null else choice.id)
                        expanded = false
                    },
                )
            }
        }
    }
}
