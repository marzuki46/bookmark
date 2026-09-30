package com.keuangan.app

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.ui.Modifier
import com.keuangan.app.ui.AppRoot
import com.keuangan.app.ui.theme.AppearanceController
import com.keuangan.app.ui.theme.AppLockController
import com.keuangan.app.ui.theme.KangCuanFloatController
import com.keuangan.app.ui.theme.KeuanganTheme
import com.keuangan.app.ui.theme.ProvideAppTextScale
import com.keuangan.app.ui.theme.ThemeController

class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        ErrorReporter.install(application as KeuanganApp)
        ThemeController.init(this)
        AppearanceController.init(this)
        AppLockController.init(this)
        KangCuanFloatController.init(this)
        setContent {
            val themeId = ThemeController.themeId.value
            KeuanganTheme(themeId = themeId) {
                Surface(
                    modifier = Modifier.fillMaxSize(),
                    color = MaterialTheme.colorScheme.background,
                ) {
                    // Reader's text size applies to every screen at once.
                    ProvideAppTextScale { AppRoot() }
                }
            }
        }
    }
}
