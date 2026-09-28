package com.keuangan.app

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.ui.Modifier
import com.keuangan.app.ui.AppRoot
import com.keuangan.app.ui.theme.KeuanganTheme
import com.keuangan.app.ui.theme.ThemeController

class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        ErrorReporter.install(application as KeuanganApp)
        ThemeController.init(this)
        setContent {
            val themeId = ThemeController.themeId.value
            KeuanganTheme(themeId = themeId) {
                Surface(
                    modifier = Modifier.fillMaxSize(),
                    color = MaterialTheme.colorScheme.background,
                ) {
                    AppRoot()
                }
            }
        }
    }
}
