package com.keuangan.app.ui.components

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
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
import com.keuangan.app.ui.theme.HappyHeaderGradient
import java.time.Instant
import java.time.ZoneOffset

/** Happy gradient banner used as the top of every screen. */
@Composable
fun GradientHeader(
    title: String,
    subtitle: String? = null,
    modifier: Modifier = Modifier,
    trailing: (@Composable () -> Unit)? = null,
) {
    Box(
        modifier = modifier
            .fillMaxWidth()
            .statusBarsPadding()
            .clip(RoundedCornerShape(bottomStart = 28.dp, bottomEnd = 28.dp))
            .background(HappyHeaderGradient)
            .padding(horizontal = 22.dp, vertical = 22.dp),
    ) {
        Box(
            Modifier
                .align(Alignment.BottomEnd)
                .offset(x = 34.dp, y = 46.dp)
                .size(140.dp)
                .background(Color.White.copy(alpha = 0.10f), CircleShape),
        )
        Box(
            Modifier
                .align(Alignment.TopEnd)
                .offset(x = 18.dp, y = (-18).dp)
                .size(56.dp)
                .background(Color.White.copy(alpha = 0.08f), CircleShape),
        )
        Column(Modifier.fillMaxWidth(0.86f)) {
            Text(
                text = title,
                style = MaterialTheme.typography.headlineSmall,
                color = Color.White,
            )
            if (subtitle != null) {
                Spacer(Modifier.height(4.dp))
                Text(
                    text = subtitle,
                    style = MaterialTheme.typography.bodyMedium,
                    color = Color.White.copy(alpha = 0.92f),
                    maxLines = 2,
                    overflow = TextOverflow.Ellipsis,
                )
            }
        }
        trailing?.let { action ->
            Box(Modifier.align(Alignment.CenterEnd)) { action() }
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

    OutlinedTextField(
        value = formatFullDate(value),
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