package com.keuangan.app.ui.theme

import android.content.Context
import androidx.compose.runtime.Composable
import androidx.compose.runtime.CompositionLocalProvider
import androidx.compose.runtime.mutableFloatStateOf
import androidx.compose.runtime.mutableStateOf
import androidx.compose.ui.platform.LocalDensity
import androidx.compose.ui.unit.Density

/**
 * How the app moves between screens. The user picks one, so "crawling" motion
 * (a slow, wide glide) is available for anyone who prefers calmer transitions.
 */
enum class MotionStyle(val label: String, val description: String) {
    SLOW("Siput halus", "Geser pelan dan lebar, seperti siput berjalan"),
    FAST("Cepat", "Geser singkat supaya tabel panjang terasa ringan"),
    NONE("Tanpa animasi", "Langsung pindah, tanpa geser sama sekali"),
}

data class TextSizeOption(val scale: Float, val label: String)

/**
 * User-facing appearance preferences, persisted next to the theme pick.
 *
 * `textScale` multiplies the density font scale for the whole app, so every
 * screen honours the reader's size without each Composable re-implementing it.
 */
object AppearanceController {
    private const val PREFS = "app_appearance"
    private const val KEY_TEXT_SCALE = "text_scale"
    private const val KEY_MOTION = "motion"

    val TextSizes: List<TextSizeOption> = listOf(
        TextSizeOption(1.0f, "Normal"),
        TextSizeOption(1.15f, "Besar"),
        TextSizeOption(1.3f, "Sangat besar"),
    )

    val textScale = mutableFloatStateOf(TextSizes.first().scale)
    val motion = mutableStateOf(MotionStyle.SLOW)

    fun init(context: Context) {
        val prefs = context.getSharedPreferences(PREFS, Context.MODE_PRIVATE)

        val savedScale = prefs.getFloat(KEY_TEXT_SCALE, -1f)
        if (savedScale > 0f) {
            textScale.floatValue = TextSizes.minByOrNull { kotlin.math.abs(it.scale - savedScale) }?.scale
                ?: TextSizes.first().scale
        }

        motion.value = MotionStyle.entries.firstOrNull { it.name == prefs.getString(KEY_MOTION, null) }
            ?: MotionStyle.SLOW
    }

    fun selectTextScale(context: Context, scale: Float) {
        val option = TextSizes.firstOrNull { it.scale == scale } ?: return
        textScale.floatValue = option.scale
        context.getSharedPreferences(PREFS, Context.MODE_PRIVATE)
            .edit()
            .putFloat(KEY_TEXT_SCALE, option.scale)
            .apply()
    }

    fun selectMotion(context: Context, style: MotionStyle) {
        motion.value = style
        context.getSharedPreferences(PREFS, Context.MODE_PRIVATE)
            .edit()
            .putString(KEY_MOTION, style.name)
            .apply()
    }
}

/**
 * Applies the reader's text size to everything below, on top of whatever the
 * system font scale already is.
 */
@Composable
fun ProvideAppTextScale(content: @Composable () -> Unit) {
    val density = LocalDensity.current
    val scale = AppearanceController.textScale.floatValue

    val scaled = if (scale == 1f) {
        density
    } else {
        Density(density = density.density, fontScale = density.fontScale * scale)
    }

    CompositionLocalProvider(LocalDensity provides scaled, content = content)
}
