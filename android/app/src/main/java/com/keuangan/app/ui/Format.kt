package com.keuangan.app.ui

import java.text.NumberFormat
import java.text.SimpleDateFormat
import java.util.Currency
import java.util.Locale
import kotlin.math.abs

private val idLocale = Locale("in", "ID")

fun formatRupiah(value: Double): String {
    val formatter = NumberFormat.getCurrencyInstance(idLocale)
    formatter.currency = Currency.getInstance("IDR")
    formatter.maximumFractionDigits = 0
    return formatter.format(value)
}

/** Compact form for axis labels: 1.2jt, 350rb, 1,5M. */
fun formatCompact(value: Double): String {
    val abs = abs(value)
    return when {
        abs >= 1_000_000_000 -> trim(value / 1_000_000_000) + "M"
        abs >= 1_000_000 -> trim(value / 1_000_000) + "jt"
        abs >= 1_000 -> trim(value / 1_000) + "rb"
        else -> value.toLong().toString()
    }
}

private fun trim(value: Double): String {
    val rounded = Math.round(value * 10) / 10.0
    return if (rounded % 1.0 == 0.0) rounded.toLong().toString() else rounded.toString()
}

private val isoDate = SimpleDateFormat("yyyy-MM-dd", Locale.US)
private val dayMonth = SimpleDateFormat("d MMM", idLocale)
private val dayMonthYear = SimpleDateFormat("d MMM yyyy", idLocale)

fun parseIsoDate(value: String?): java.util.Date? = runCatching {
    isoDate.parse(value?.take(10))
}.getOrNull()

fun formatShortDate(value: String?): String {
    val date = parseIsoDate(value) ?: return "-"
    return dayMonth.format(date)
}

fun formatFullDate(value: String?): String {
    val date = parseIsoDate(value) ?: return "-"
    return dayMonthYear.format(date)
}

fun todayIso(): String = SimpleDateFormat("yyyy-MM-dd", Locale.US).format(java.util.Date())

fun monthLabel(isoMonth: String): String = runCatching {
    val parsed = SimpleDateFormat("yyyy-MM", Locale.US).parse(isoMonth)
    SimpleDateFormat("MMM yy", idLocale).format(parsed)
}.getOrDefault(isoMonth)

fun healthLabel(health: String?): String = when (health?.lowercase()) {
    "excellent", "sangat baik" -> "Sangat baik"
    "good", "baik" -> "Baik"
    "average", "sedang" -> "Sedang"
    "bad", "buruk" -> "Buruk"
    "critical", "sangat buruk" -> "Sangat buruk"
    else -> "Belum ada data"
}
