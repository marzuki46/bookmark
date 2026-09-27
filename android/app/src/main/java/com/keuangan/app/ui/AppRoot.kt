package com.keuangan.app.ui

import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.platform.LocalContext
import androidx.lifecycle.ViewModel
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
    const val LOGIN = "login"
    const val DASHBOARD = "dashboard"
    const val TRANSACTIONS = "transactions"
    const val CATEGORIES = "categories"
}

/**
 * Builds a factory bound to the app-wide repository. `viewModel()` only
 * consults the factory on first creation, so rebuilding it is harmless.
 */
@Composable
private fun <T : ViewModel> appViewModel(create: (KeuanganRepositoryHolder) -> T): T {
    val app = LocalContext.current.applicationContext as KeuanganApp
    val factory = viewModelFactory {
        initializer { create(KeuanganRepositoryHolder(app.repository)) }
    }
    return viewModel(factory = factory)
}

@JvmInline
value class KeuanganRepositoryHolder(val repository: com.keuangan.app.data.KeuanganRepository)

@Composable
fun AppRoot() {
    val session: SessionViewModel = appViewModel { SessionViewModel(it.repository) }
    val authenticated by session.authenticated.collectAsState()

    when (authenticated) {
        null -> Unit // restoring the stored token
        false -> {
            val login: LoginViewModel = appViewModel { LoginViewModel(it.repository) }
            LoginScreen(viewModel = login, onLoggedIn = session::onLoggedIn)
        }
        true -> {
            val navController = rememberNavController()
            NavHost(navController = navController, startDestination = Routes.DASHBOARD) {
                composable(Routes.DASHBOARD) {
                    val vm: DashboardViewModel = appViewModel { DashboardViewModel(it.repository) }
                    DashboardScreen(
                        viewModel = vm,
                        onOpenTransactions = { navController.navigate(Routes.TRANSACTIONS) },
                        onOpenCategories = { navController.navigate(Routes.CATEGORIES) },
                        onLogout = session::logout,
                    )
                }
                composable(Routes.TRANSACTIONS) {
                    val vm: TransactionsViewModel = appViewModel { TransactionsViewModel(it.repository) }
                    TransactionsScreen(viewModel = vm, onBack = { navController.popBackStack() })
                }
                composable(Routes.CATEGORIES) {
                    val vm: CategoriesViewModel = appViewModel { CategoriesViewModel(it.repository) }
                    CategoriesScreen(viewModel = vm, onBack = { navController.popBackStack() })
                }
            }
        }
    }
}
