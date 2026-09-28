package com.keuangan.app.ui.theme

import android.app.Activity
import androidx.compose.foundation.isSystemInDarkTheme
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Shapes
import androidx.compose.material3.Typography
import androidx.compose.material3.darkColorScheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.runtime.Composable
import androidx.compose.runtime.SideEffect
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.toArgb
import androidx.compose.ui.platform.LocalView
import androidx.compose.ui.text.TextStyle
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.core.view.WindowCompat

// --- Brand palette: emerald + sunshine, cheerful & easy on the eye ---
val Emerald500 = Color(0xFF10B981)
val Emerald600 = Color(0xFF059669)
val Emerald700 = Color(0xFF047857)
val Emerald800 = Color(0xFF065F46)
val Emerald100 = Color(0xFFD1FAE5)
val Emerald50 = Color(0xFFECFDF5)

val Amber500 = Color(0xFFF59E0B)
val Amber600 = Color(0xFFD97706)
val Amber200 = Color(0xFFFDE68A)
val Amber100 = Color(0xFFFEF3C7)

val Sky500 = Color(0xFF0EA5E9)
val Rose500 = Color(0xFFF43F5E)

val Red600 = Color(0xFFDC2626)
val Red100 = Color(0xFFFEE2E2)
val Slate900 = Color(0xFF0F172A)
val Slate500 = Color(0xFF64748B)
val Slate100 = Color(0xFFF1F5F9)

// Kept as aliases — screen code has always referenced these names.
val Teal700 = Color(0xFF0F766E)
val Teal600 = Color(0xFF0D9488)
val Teal100 = Color(0xFFCCFBF1)

private val LightColors = lightColorScheme(
    primary = Emerald600,
    onPrimary = Color.White,
    primaryContainer = Emerald100,
    onPrimaryContainer = Emerald800,
    secondary = Amber500,
    onSecondary = Color.White,
    secondaryContainer = Amber100,
    onSecondaryContainer = Color(0xFF78350F),
    tertiary = Sky500,
    onTertiary = Color.White,
    tertiaryContainer = Color(0xFFE0F2FE),
    onTertiaryContainer = Color(0xFF0C4A6E),
    error = Red600,
    onError = Color.White,
    errorContainer = Red100,
    onErrorContainer = Red600,
    background = Color(0xFFF3F7F4),
    onBackground = Color(0xFF10201A),
    surface = Color.White,
    onSurface = Color(0xFF10201A),
    surfaceVariant = Color(0xFFE7F0EA),
    onSurfaceVariant = Color(0xFF55665D),
    outline = Color(0xFFC9D7CF),
    outlineVariant = Color(0xFFE1EBE5),
)

private val DarkColors = darkColorScheme(
    primary = Color(0xFF34D399),
    onPrimary = Color(0xFF04301C),
    primaryContainer = Color(0xFF0E5947),
    onPrimaryContainer = Color(0xFFA7F3D0),
    secondary = Color(0xFFFBBF24),
    onSecondary = Color(0xFF3E2C04),
    secondaryContainer = Color(0xFF5A3E00),
    onSecondaryContainer = Color(0xFFFFE9A3),
    tertiary = Color(0xFF38BDF8),
    onTertiary = Color(0xFF082F49),
    tertiaryContainer = Color(0xFF155E75),
    onTertiaryContainer = Color(0xFFBAE6FD),
    error = Color(0xFFF87171),
    onError = Color(0xFF450A0A),
    errorContainer = Color(0xFF7F1D1D),
    onErrorContainer = Color(0xFFFECACA),
    background = Color(0xFF0C1310),
    onBackground = Color(0xFFDCEAE2),
    surface = Color(0xFF111A16),
    onSurface = Color(0xFFDCEAE2),
    surfaceVariant = Color(0xFF1D2922),
    onSurfaceVariant = Color(0xFF93A79C),
    outline = Color(0xFF2F463B),
    outlineVariant = Color(0xFF26392F),
)

// Rounded & friendly.
private val AppShapes = Shapes(
    extraSmall = RoundedCornerShape(10.dp),
    small = RoundedCornerShape(14.dp),
    medium = RoundedCornerShape(20.dp),
    large = RoundedCornerShape(28.dp),
    extraLarge = RoundedCornerShape(36.dp),
)

private val AppTypography = Typography(
    headlineSmall = TextStyle(fontSize = 26.sp, fontWeight = FontWeight.ExtraBold),
    titleLarge = TextStyle(fontSize = 22.sp, fontWeight = FontWeight.Bold),
    titleMedium = TextStyle(fontSize = 17.sp, fontWeight = FontWeight.SemiBold),
    bodyLarge = TextStyle(fontSize = 16.sp),
    bodyMedium = TextStyle(fontSize = 14.sp),
    labelLarge = TextStyle(fontSize = 14.sp, fontWeight = FontWeight.SemiBold),
)

/** Signature header gradient: fresh mint → deep emerald. */
val HappyHeaderGradient = Brush.verticalGradient(listOf(Emerald500, Emerald700))

@Composable
fun KeuanganTheme(
    darkTheme: Boolean = isSystemInDarkTheme(),
    content: @Composable () -> Unit,
) {
    val colors = if (darkTheme) DarkColors else LightColors
    val view = LocalView.current

    if (!view.isInEditMode) {
        SideEffect {
            val window = (view.context as Activity).window
            window.statusBarColor = colors.background.toArgb()
            WindowCompat.getInsetsController(window, view).isAppearanceLightStatusBars = !darkTheme
        }
    }

    MaterialTheme(
        colorScheme = colors,
        typography = AppTypography,
        shapes = AppShapes,
        content = content,
    )
}