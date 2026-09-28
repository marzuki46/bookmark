package com.keuangan.app.ui.theme

import android.content.Context
import androidx.compose.foundation.isSystemInDarkTheme
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.ColorScheme
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Shapes
import androidx.compose.material3.Typography
import androidx.compose.material3.darkColorScheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.runtime.Composable
import androidx.compose.runtime.SideEffect
import androidx.compose.runtime.mutableStateOf
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.toArgb
import androidx.compose.ui.platform.LocalView
import androidx.compose.ui.text.TextStyle
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.core.view.WindowCompat

// --- Shared accents ---
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
val Emerald500 = Color(0xFF10B981)
val Emerald600 = Color(0xFF059669)
val Emerald700 = Color(0xFF047857)
val Emerald800 = Color(0xFF065F46)
val Emerald100 = Color(0xFFD1FAE5)
val Emerald50 = Color(0xFFECFDF5)

/** An app palette: brand gradient for the header plus light & dark schemes. */
data class AppTheme(
    val id: String,
    val title: String,
    val gradient: Brush,
    val light: ColorScheme,
    val dark: ColorScheme,
)

private val EmeraldLight = lightColorScheme(
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

private val EmeraldDark = darkColorScheme(
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

private fun lightScheme(
    primary: Color,
    container: Color,
    onContainer: Color,
    focus: Color,
    focusContainer: Color,
    bg: Color,
): ColorScheme = lightColorScheme(
    primary = primary,
    onPrimary = Color.White,
    primaryContainer = container,
    onPrimaryContainer = onContainer,
    secondary = Amber500,
    onSecondary = Color.White,
    secondaryContainer = Amber100,
    onSecondaryContainer = Color(0xFF78350F),
    tertiary = focus,
    onTertiary = Color.White,
    tertiaryContainer = focusContainer,
    onTertiaryContainer = onContainer,
    error = Red600,
    onError = Color.White,
    errorContainer = Red100,
    onErrorContainer = Red600,
    background = bg,
    onBackground = onContainer,
    surface = Color.White,
    onSurface = onContainer,
    surfaceVariant = container.copy(alpha = 0.45f),
    onSurfaceVariant = onContainer.copy(alpha = 0.72f),
    outline = onContainer.copy(alpha = 0.18f),
    outlineVariant = onContainer.copy(alpha = 0.10f),
)

private fun darkScheme(
    primary: Color,
    container: Color,
    onContainer: Color,
    focus: Color,
    focusContainer: Color,
    bg: Color,
): ColorScheme = darkColorScheme(
    primary = primary,
    onPrimary = Color(0xFF06131E),
    primaryContainer = container,
    onPrimaryContainer = onContainer,
    secondary = Color(0xFFFBBF24),
    onSecondary = Color(0xFF3E2C04),
    secondaryContainer = Color(0xFF5A3E00),
    onSecondaryContainer = Color(0xFFFFE9A3),
    tertiary = focus,
    onTertiary = Color(0xFF082F49),
    tertiaryContainer = focusContainer,
    onTertiaryContainer = onContainer,
    error = Color(0xFFF87171),
    onError = Color(0xFF450A0A),
    errorContainer = Color(0xFF7F1D1D),
    onErrorContainer = Color(0xFFFECACA),
    background = bg,
    onBackground = onContainer,
    surface = Color.White.copy(alpha = 0.04f).let {
        // surface must be opaque for cards; lift toward a lighter grey.
        Color(red = bg.red + 0.02f, green = bg.green + 0.02f, blue = bg.blue + 0.02f)
    },
    onSurface = onContainer,
    surfaceVariant = onContainer.copy(alpha = 0.14f),
    onSurfaceVariant = onContainer.copy(alpha = 0.75f),
    outline = onContainer.copy(alpha = 0.22f),
    outlineVariant = onContainer.copy(alpha = 0.12f),
)

private val OceanBlue = Color(0xFF0F78B3)
val AppThemes: List<AppTheme> = listOf(
    AppTheme(
        id = "emerald",
        title = "Zamrud",
        gradient = Brush.verticalGradient(listOf(Emerald500, Emerald700)),
        light = EmeraldLight,
        dark = EmeraldDark,
    ),
    AppTheme(
        id = "ocean",
        title = "Samudra",
        gradient = Brush.verticalGradient(listOf(Color(0xFF38BDF8), Color(0xFF0E5278))),
        light = lightScheme(
            primary = OceanBlue,
            container = Color(0xFFD9EEFB),
            onContainer = Color(0xFF1D3A5F),
            focus = Color(0xFF14B8A6),
            focusContainer = Color(0xFFCCFBF1),
            bg = Color(0xFFF0F6FA),
        ),
        dark = darkScheme(
            primary = Color(0xFF4DB6EB),
            container = Color(0xFF0E4158),
            onContainer = Color(0xFFC6EAFB),
            focus = Color(0xFF34D399),
            focusContainer = Color(0xFF0F5F5A),
            bg = Color(0xFF0B131A),
        ),
    ),
    AppTheme(
        id = "sunset",
        title = "Senja",
        gradient = Brush.verticalGradient(listOf(Color(0xFFF97316), Color(0xFFBE185D))),
        light = lightScheme(
            primary = Color(0xFFD9437E),
            container = Color(0xFFFDE3EE),
            onContainer = Color(0xFF7F1D44),
            focus = Color(0xFFF59E0B),
            focusContainer = Color(0xFFFEF3C7),
            bg = Color(0xFFFBF4F1),
        ),
        dark = darkScheme(
            primary = Color(0xFFF06A9A),
            container = Color(0xFF5C1640),
            onContainer = Color(0xFFFCD8E6),
            focus = Color(0xFFFBBF24),
            focusContainer = Color(0xFF5A3E00),
            bg = Color(0xFF1B0C10),
        ),
    ),
    AppTheme(
        id = "royal",
        title = "Kerajaan",
        gradient = Brush.verticalGradient(listOf(Color(0xFFA78BFA), Color(0xFF6D28D9))),
        light = lightScheme(
            primary = Color(0xFF8B5CF6),
            container = Color(0xFFEDE9FE),
            onContainer = Color(0xFF4C1D95),
            focus = Color(0xFF0EA5E9),
            focusContainer = Color(0xFFE0F2FE),
            bg = Color(0xFFF6F3FB),
        ),
        dark = darkScheme(
            primary = Color(0xFFC4B5FD),
            container = Color(0xFF4C1D95),
            onContainer = Color(0xFFE9DFFF),
            focus = Color(0xFF38BDF8),
            focusContainer = Color(0xFF155E75),
            bg = Color(0xFF100B1B),
        ),
    ),
    AppTheme(
        id = "ruby",
        title = "Rubi",
        gradient = Brush.verticalGradient(listOf(Color(0xFFF87171), Color(0xFF991B1B))),
        light = lightScheme(
            primary = Color(0xFFBE123C),
            container = Color(0xFFFEE2E2),
            onContainer = Color(0xFF7F1D1D),
            focus = Color(0xFFF59E0B),
            focusContainer = Color(0xFFFEF3C7),
            bg = Color(0xFFFBF3F3),
        ),
        dark = darkScheme(
            primary = Color(0xFFF87171),
            container = Color(0xFF7F1D1D),
            onContainer = Color(0xFFFECACA),
            focus = Color(0xFFFBBF24),
            focusContainer = Color(0xFF5A3E00),
            bg = Color(0xFF1A0B0B),
        ),
    ),
    AppTheme(
        id = "forest",
        title = "Hutan",
        gradient = Brush.verticalGradient(listOf(Color(0xFF84CC16), Color(0xFF3F6E0F))),
        light = lightScheme(
            primary = Color(0xFF4D7C0F),
            container = Color(0xFFECFCCB),
            onContainer = Color(0xFF365314),
            focus = Color(0xFF0EA5E9),
            focusContainer = Color(0xFFE0F2FE),
            bg = Color(0xFFF5F7EE),
        ),
        dark = darkScheme(
            primary = Color(0xFFA3E635),
            container = Color(0xFF3F6E0F),
            onContainer = Color(0xFFEAFBD0),
            focus = Color(0xFF38BDF8),
            focusContainer = Color(0xFF155E75),
            bg = Color(0xFF10120C),
        ),
    ),
)

/**
 * Picked theme id, observable without an Activity reference so any Composable
 * (headers, pickers) can react to the current selection instantly.
 */
object ThemeController {
    private const val PREFS = "app_theme"
    private const val KEY = "theme_id"

    val themeId = mutableStateOf(AppThemes.first().id)

    fun init(context: Context) {
        val saved = context.getSharedPreferences(PREFS, Context.MODE_PRIVATE)
            .getString(KEY, null)
        if (saved != null && AppThemes.any { it.id == saved }) {
            themeId.value = saved
        }
    }

    fun select(context: Context, id: String) {
        if (AppThemes.none { it.id == id }) return
        themeId.value = id
        context.getSharedPreferences(PREFS, Context.MODE_PRIVATE)
            .edit()
            .putString(KEY, id)
            .apply()
    }
}

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

@Composable
fun KeuanganTheme(
    themeId: String = AppThemes.first().id,
    darkTheme: Boolean = isSystemInDarkTheme(),
    content: @Composable () -> Unit,
) {
    val theme = AppThemes.firstOrNull { it.id == themeId } ?: AppThemes.first()
    val colors = if (darkTheme) theme.dark else theme.light
    val view = LocalView.current

    if (!view.isInEditMode) {
        SideEffect {
            val window = (view.context as android.app.Activity).window
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