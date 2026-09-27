package com.keuangan.app.ui

import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.platform.LocalContext
import androidx.lifecycle.ViewModelProvider
import androidx.lifecycle.viewmodel.compose.viewModel
import androidx.lifecycle.viewmodel.initializer
import androidx.lifecycle.viewmodel.viewModelFactory
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.rememberNavController
import com.keuangan.app.KeuanganApp
import com.keuangan.app.ui.categories.CategoriesScreen
import com.keuangan.app.ui.categories.CategoriesViewModel
import com.keuangan.app.ui.dashboard.DashboardScreen
import com.keuangan.app.ui.dashboard.DashboardViewModel
import com.keuangan.app.ui.login.LoginScreen
import com.keuangan.app.ui.login.LoginViewModel
import com.keuangan.app.ui.transactions.TransactionsScreen
import com.keuangan.app.ui.transactions.TransactionsViewModel

object Routes {
    const val DASHBOARD = "dashboard"
    const val TRANSACTIONS = "transactions"
    const val CATEGORIES = "categories"
}

/**
 * One factory for every view model, bound to the app-wide repository.
 *
 * `viewModel()` only consults the factory on first creation, so rebuilding it
 * on recomposition is harmless. Each call site names its own view model type,
 * which keeps the generic call site reified and compiles cleanly.
 */
@Composable
private fun appFactory(): ViewModelProvider.Factory {
    val app = LocalContext.current.applicationContext as KeuanganApp
    return viewModelFactory {
        initializer { SessionViewModel(app.repository) }
        initializer { LoginViewModel(app.repository) }
        initializer { DashboardViewModel(app.repository) }
        initializer { TransactionsViewModel(app.repository) }
        initializer { CategoriesViewModel(app.repository) }
    }
}

@Composable
fun AppRoot() {
    val session: SessionViewModel = viewModel(factory = appFactory())
    val authenticated by session.authenticated.collectAsState()

    when (authenticated) {
        // Restoring the stored token; stay blank until we know which screen.
        null -> Unit

        false -> {
            val login: LoginViewModel = viewModel(factory = appFactory())
            LoginScreen(viewModel = login, onLoggedIn = session::onLoggedIn)
        }

        true -> {
            val navController = rememberNavController()
            NavHost(navController = navController, startDestination = Routes.DASHBOARD) {
                composable(Routes.DASHBOARD) {
                    val vm: DashboardViewModel = viewModel(factory = appFactory())
                    DashboardScreen(
                        viewModel = vm,
                        onOpenTransactions = { navController.navigate(Routes.TRANSACTIONS) },
                        onOpenCategories = { navController.navigate(Routes.CATEGORIES) },
                        onLogout = session::logout,
                    )
                }
                composable(Routes.TRANSACTIONS) {
                    val vm: TransactionsViewModel = viewModel(factory = appFactory())
                    TransactionsScreen(viewModel = vm, onBack = { navController.popBackStack() })
                }
                composable(Routes.CATEGORIES) {
                    val vm: CategoriesViewModel = viewModel(factory = appFactory())
                    CategoriesScreen(viewModel = vm, onBack = { navController.popBackStack() })
                }
            }
        }
    }
}
