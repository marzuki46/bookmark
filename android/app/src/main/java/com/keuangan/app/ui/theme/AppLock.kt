package com.keuangan.app.ui.theme

import android.content.Context
import android.util.Base64
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.Button
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.getValue
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.ui.unit.dp
import java.security.MessageDigest
import java.security.SecureRandom

object AppLockController {
    private const val PREFS = "app_lock"
    private const val KEY_ENABLED = "enabled"
    private const val KEY_SALT = "salt"
    private const val KEY_HASH = "hash"

    val enabled = mutableStateOf(false)

    fun init(context: Context) {
        enabled.value = context.getSharedPreferences(PREFS, Context.MODE_PRIVATE)
            .getBoolean(KEY_ENABLED, false)
    }

    fun setPin(context: Context, pin: String) {
        val salt = ByteArray(16).also { SecureRandom().nextBytes(it) }
        context.getSharedPreferences(PREFS, Context.MODE_PRIVATE).edit()
            .putBoolean(KEY_ENABLED, true)
            .putString(KEY_SALT, Base64.encodeToString(salt, Base64.NO_WRAP))
            .putString(KEY_HASH, digest(salt, pin))
            .apply()
        enabled.value = true
    }

    fun disable(context: Context) {
        context.getSharedPreferences(PREFS, Context.MODE_PRIVATE).edit().clear().apply()
        enabled.value = false
    }

    fun verify(context: Context, pin: String): Boolean {
        val prefs = context.getSharedPreferences(PREFS, Context.MODE_PRIVATE)
        val salt = prefs.getString(KEY_SALT, null)?.let { Base64.decode(it, Base64.NO_WRAP) } ?: return false
        val expected = prefs.getString(KEY_HASH, null) ?: return false
        return MessageDigest.isEqual(expected.toByteArray(), digest(salt, pin).toByteArray())
    }

    private fun digest(salt: ByteArray, pin: String): String =
        MessageDigest.getInstance("SHA-256").digest(salt + pin.toByteArray()).joinToString("") { "%02x".format(it) }
}

@Composable
fun AppLockScreen(onUnlocked: () -> Unit) {
    val context = androidx.compose.ui.platform.LocalContext.current
    var pin by androidx.compose.runtime.remember { mutableStateOf("") }
    var error by androidx.compose.runtime.remember { mutableStateOf(false) }

    Column(
        modifier = Modifier.fillMaxSize().padding(28.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.Center,
    ) {
        Text("Aplikasi terkunci", style = MaterialTheme.typography.headlineSmall)
        Spacer(Modifier.padding(6.dp))
        Text("Masukkan PIN untuk membuka data keluarga.", color = MaterialTheme.colorScheme.onSurfaceVariant)
        Spacer(Modifier.padding(8.dp))
        OutlinedTextField(
            value = pin,
            onValueChange = { pin = it.filter(Char::isDigit).take(6); error = false },
            label = { Text("PIN aplikasi") },
            singleLine = true,
            visualTransformation = PasswordVisualTransformation(),
            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.NumberPassword),
            modifier = Modifier.fillMaxWidth(),
        )
        if (error) Text("PIN salah.", color = MaterialTheme.colorScheme.error)
        Spacer(Modifier.padding(6.dp))
        Button(onClick = {
            if (AppLockController.verify(context, pin)) onUnlocked() else error = true
        }) { Text("Buka aplikasi") }
    }
}
