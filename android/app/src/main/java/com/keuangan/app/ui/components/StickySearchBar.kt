package com.keuangan.app.ui.components

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.statusBarsPadding
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardActions
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.FilterList
import androidx.compose.material3.Badge
import androidx.compose.material3.BadgedBox
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.input.ImeAction
import androidx.compose.ui.unit.dp

/**
 * The search + filter header shared by Transaksi, Utang, and Target.
 *
 * Search and the filter button sit on one row so the header costs a single line
 * instead of a field plus a wrapped chip rail. When [headerGone] turns true —
 * the page title has scrolled past — the field shrinks from 48dp to 32dp and
 * drops to [labelSmall] while keeping its width and the filter button beside
 * it, so the bar keeps floating under the status bar without eating the list.
 *
 * 32dp rather than a literal half: this Compose version's OutlinedTextField
 * hardcodes its vertical content padding, so anything under ~32dp clips the
 * text vertically.
 */
@Composable
fun StickySearchBar(
    query: String,
    onQueryChange: (String) -> Unit,
    placeholder: String,
    headerGone: Boolean,
    onOpenFilters: () -> Unit,
    modifier: Modifier = Modifier,
    activeFilterCount: Int = 0,
    filterSummary: String? = null,
    onClearFilters: () -> Unit = {},
    onSearch: (() -> Unit)? = null,
) {
    val fieldHeight = if (headerGone) 32.dp else 48.dp

    Column(
        modifier = modifier
            .fillMaxWidth()
            .background(MaterialTheme.colorScheme.surface)
            .then(if (headerGone) Modifier.statusBarsPadding() else Modifier)
            .padding(top = 4.dp),
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(horizontal = 16.dp),
            verticalAlignment = Alignment.CenterVertically,
        ) {
            OutlinedTextField(
                value = query,
                onValueChange = onQueryChange,
                placeholder = {
                    Text(
                        placeholder,
                        style = if (headerGone) {
                            MaterialTheme.typography.labelSmall
                        } else {
                            MaterialTheme.typography.bodySmall
                        },
                        maxLines = 1,
                    )
                },
                singleLine = true,
                shape = RoundedCornerShape(12.dp),
                textStyle = if (headerGone) {
                    MaterialTheme.typography.labelSmall
                } else {
                    MaterialTheme.typography.bodySmall
                },
                keyboardOptions = KeyboardOptions(imeAction = ImeAction.Search),
                keyboardActions = if (onSearch != null) {
                    KeyboardActions(onSearch = { onSearch() })
                } else {
                    KeyboardActions.Default
                },
                modifier = Modifier
                    .weight(1f)
                    .height(fieldHeight),
            )
            Spacer(Modifier.width(4.dp))
            BadgedBox(
                badge = {
                    if (activeFilterCount > 0) {
                        Badge { Text(activeFilterCount.toString()) }
                    }
                },
            ) {
                IconButton(
                    onClick = onOpenFilters,
                    modifier = Modifier.size(if (headerGone) 36.dp else 44.dp),
                ) {
                    Icon(
                        Icons.Filled.FilterList,
                        contentDescription = "Buka filter",
                        tint = if (activeFilterCount > 0) {
                            MaterialTheme.colorScheme.primary
                        } else {
                            MaterialTheme.colorScheme.onSurfaceVariant
                        },
                        modifier = Modifier.size(if (headerGone) 18.dp else 22.dp),
                    )
                }
            }
        }

        if (activeFilterCount > 0 && filterSummary != null) {
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(start = 20.dp, end = 12.dp, top = 2.dp),
                verticalAlignment = Alignment.CenterVertically,
            ) {
                Icon(
                    Icons.Filled.FilterList,
                    contentDescription = null,
                    modifier = Modifier.size(16.dp),
                    tint = MaterialTheme.colorScheme.primary,
                )
                Spacer(Modifier.width(6.dp))
                Text(
                    filterSummary,
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                    modifier = Modifier.weight(1f),
                    maxLines = 1,
                    overflow = androidx.compose.ui.text.style.TextOverflow.Ellipsis,
                )
                TextButton(onClick = onClearFilters) { Text("Hapus") }
            }
        } else {
            Spacer(Modifier.height(4.dp))
        }
    }
}