package com.keuangan.app.ui.family

import androidx.compose.ui.graphics.Color
import com.keuangan.app.ui.theme.Amber100
import com.keuangan.app.ui.theme.Amber600
import com.keuangan.app.ui.theme.Red100
import com.keuangan.app.ui.theme.Red600
import com.keuangan.app.ui.theme.Slate100
import com.keuangan.app.ui.theme.Slate500
import com.keuangan.app.ui.theme.Teal100
import com.keuangan.app.ui.theme.Teal700

/** Human label for the payer role a member set for themselves. */
fun payerLabel(role: String?): String = when (role) {
    "husband" -> "Suami"
    "wife" -> "Istri"
    else -> "Anggota"
}

/** Chip colours matching the payer label, so husband/wife read at a glance. */
fun payerChip(role: String?): Pair<Color, Color> = when (role) {
    "husband" -> Teal100 to Teal700
    "wife" -> Amber100 to Amber600
    else -> Slate100 to Slate500
}

/** A 0–100 family health score becomes a letter grade like a school report. */
fun gradeOf(score: Double): String = when {
    score >= 90 -> "A"
    score >= 80 -> "B"
    score >= 70 -> "C"
    score >= 60 -> "D"
    else -> "E"
}

fun gradeTint(grade: String): Pair<Color, Color> = when (grade) {
    "A" -> Teal100 to Teal700
    "B", "C" -> Amber100 to Amber600
    else -> Red100 to Red600
}