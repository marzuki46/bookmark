package com.keuangan.app.ui.theme

import android.content.Context
import androidx.compose.runtime.mutableStateOf

/**
 * Whether the floating Kang Cuan avatar floats above the bottom bar on the
 * Ringkasan tab. The reader can hide it from the bottom sheet it opens, and
 * turn it back on from the Kang Cuan menu in Lainnya.
 */
object KangCuanFloatController {
    private const val PREFS = "app_appearance"
    private const val KEY_VISIBLE = "kang_cuan_float_visible"

    val visible = mutableStateOf(true)

    fun init(context: Context) {
        visible.value = context.getSharedPreferences(PREFS, Context.MODE_PRIVATE)
            .getBoolean(KEY_VISIBLE, true)
    }

    fun setVisible(context: Context, value: Boolean) {
        visible.value = value
        context.getSharedPreferences(PREFS, Context.MODE_PRIVATE)
            .edit()
            .putBoolean(KEY_VISIBLE, value)
            .apply()
    }

    fun toggle(context: Context) = setVisible(context, !visible.value)
}