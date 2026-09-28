package com.keuangan.app.ui

import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.height
import androidx.compose.material3.Button
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.NavigationBar
import androidx.compose.material3.NavigationBarItem
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Balance
import androidx.compose.material.icons.filled.Flag
import androidx.compose.material.icons.filled.Home
import androidx.compose.material.icons.filled.MoreVert
import androidx.compose.material.icons.filled.ReceiptLong
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.unit.dp
import androidx.lifecycle.ViewModelProvider
import androidx.lifecycle.viewmodel.compose.viewModel
import androidx.lifecycle.viewmodel.initializer
import androidx.lifecycle.viewmodel.viewModelFactory
import androidx.navigation.NavDestination.Companion.hierarchy
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.currentBackStackEntryAsState
import androidx.navigation.compose.rememberNavController
import com.keuangan.app.KeuanganApp
import com.keuangan.app.ui.family.CodeLoginScreen
import com.keuangan.app.ui.family.CodeLoginViewModel
import com.keuangan.app.ui.family.FamilyAppViewModel
import com.keuangan.app.ui.family.FamilyBudgetsScreen
import com.keuangan.app.ui.family.FamilyBudgetsViewModel
import com.keuangan.app.ui.family.FamilyCategoriesScreen
import com.keuangan.app.ui.family.FamilyCategoriesViewModel
import com.keuangan.app.ui.family.FamilyDashboardScreen
import com.keuangan.app.ui.family.FamilyDashboardViewModel
import com.keuangan.app.ui.family.FamilyDebtsScreen
import com.keuangan.app.ui.family.FamilyDebtsViewModel
import com.keuangan.app.ui.family.FamilyGoalsScreen
import com.keuangan.app.ui.family.FamilyGoalsViewModel
import com.keuangan.app.ui.family.FamilyIncomeSourcesScreen
import com.keuangan.app.ui.family.FamilyIncomeSourcesViewModel
import com.keuangan.app.ui.family.FamilyMoreScreen
import com.keuangan.app.ui.family.FamilyProfileScreen
import com.keuangan.app.ui.family.FamilyProfileViewModel
import com.keuangan.app.ui.family.FamilySubscriptionScreen
import com.keuangan.app.ui.family.FamilySubscriptionViewModel
import com.keuangan.app.ui.family.FamilyTransactionsScreen
import com.keuangan.app.ui.family.FamilyTransactionsViewModel

object Routes {
    const val HOME = "home"
    const val TRANSACTIONS = "transactions"
    const val DEBTS = "debts"
    const val GOALS = "goals"
    const val MORE = "more"
    const val BUDGETS = "budgets"
    const val CATEGORIES = "categories"
    const val INCOME_SOURCES = "income-sources"
    const val PROFILE = "profile"
    const val SUBSCRIPTION = "subscription"
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
        initializer { CodeLoginViewModel(app.repository) }
        initializer { FamilyAppViewModel(app.repository) }
        initializer { FamilyDashboardViewModel(app.repository) }
        initializer { FamilyTransactionsViewModel(app.repository) }
        initializer { FamilyDebtsViewModel(app.repository) }
        initializer { FamilyGoalsViewModel(app.repository) }
        initializer { FamilyBudgetsViewModel(app.repository) }
        initializer { FamilyCategoriesViewModel(app.repository) }
        initializer { FamilyIncomeSourcesViewModel(app.repository) }
        initializer { FamilyProfileViewModel(app.repository) }
        initializer { FamilySubscriptionViewModel(app.repository) }
    }
}

private data class Tab(
    val route: String,
    val label: String,
    val icon: ImageVector,
)

private val TABS = listOf(
    Tab(Routes.HOME, "Rumah", Icons.Filled.Home),
    Tab(Routes.TRANSACTIONS, "Transaksi", Icons.Filled.ReceiptLong),
    Tab(Routes.DEBTS, "Utang", Icons.Filled.Balance),
    Tab(Routes.GOALS, "Target", Icons.Filled.Flag),
    Tab(Routes.MORE, "Lainnya", Icons.Filled.MoreVert),
)

@Composable
fun AppRoot() {
    val session: SessionViewModel = viewModel(factory = appFactory())
    val authenticated by session.authenticated.collectAsState()

    when (authenticated) {
        // Restoring the stored token; stay blank until we know which screen.
        null -> Unit

        false -> {
            val login: CodeLoginViewModel = viewModel(factory = appFactory())
            CodeLoginScreen(viewModel = login, onLoggedIn = session::onLoggedIn)
        }

        true -> {
            val shell: FamilyAppViewModel = viewModel(factory = appFactory())
            FamilyShell(viewModel = shell, onLogout = session::logout)
        }
    }
}

@Composable
private fun FamilyShell(
    viewModel: FamilyAppViewModel,
    onLogout: () -> Unit,
) {
    val familyId by viewModel.familyId.collectAsState()
    val family by viewModel.family.collectAsState()
    val loading by viewModel.loading.collectAsState()

    if (familyId == null) {
        Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
            Column(horizontalAlignment = Alignment.CenterHorizontally) {
                Text(
                    if (loading) "Menghubungkan ke keluarga…" else "Belum terhubung ke keluarga.",
                    style = MaterialTheme.typography.bodyMedium,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                )
                if (!loading) {
                    Spacer(Modifier.height(16.dp))
                    Button(onClick = viewModel::resolve) { Text("Coba lagi") }
                }
            }
        }
        return
    }

    val navController = rememberNavController()
    val backStack by navController.currentBackStackEntryAsState()
    val currentDestination = backStack?.destination

    val selectedTab = TABS.firstOrNull { tab ->
        currentDestination?.hierarchy?.any { it.route == tab.route } == true
    }

    Scaffold(
        bottomBar = {
            NavigationBar {
                TABS.forEach { tab ->
NavigationBarItem(
                        selected = selectedTab?.route == tab.route,
                        onClick = {
                            navController.navigate(tab.route) {
                                popUpTo(Routes.HOME) { saveState = true }
                                launchSingleTop = true
                                restoreState = true
                            }
                        },
                        icon = { Icon(tab.icon, contentDescription = tab.label) },
                        label = { Text(tab.label) },
                    )
                }
            }
        },
    ) { padding ->
        NavHost(
            navController = navController,
            startDestination = Routes.HOME,
            modifier = Modifier.padding(padding),
        ) {
            composable(Routes.HOME) {
                val vm: FamilyDashboardViewModel = viewModel(factory = appFactory())
                FamilyDashboardScreen(familyId = familyId!!, family = family, viewModel = vm)
            }
            composable(Routes.TRANSACTIONS) {
                val vm: FamilyTransactionsViewModel = viewModel(factory = appFactory())
                FamilyTransactionsScreen(familyId = familyId!!, viewModel = vm)
            }
            composable(Routes.DEBTS) {
                val vm: FamilyDebtsViewModel = viewModel(factory = appFactory())
                FamilyDebtsScreen(familyId = familyId!!, viewModel = vm)
            }
            composable(Routes.GOALS) {
                val vm: FamilyGoalsViewModel = viewModel(factory = appFactory())
                FamilyGoalsScreen(familyId = familyId!!, viewModel = vm)
            }
            composable(Routes.MORE) {
                FamilyMoreScreen(
                    onOpenBudgets = { navController.navigate(Routes.BUDGETS) },
                    onOpenCategories = { navController.navigate(Routes.CATEGORIES) },
                    onOpenIncomeSources = { navController.navigate(Routes.INCOME_SOURCES) },
                    onOpenProfile = { navController.navigate(Routes.PROFILE) },
                    onOpenSubscription = { navController.navigate(Routes.SUBSCRIPTION) },
                )
            }
            composable(Routes.BUDGETS) {
                val vm: FamilyBudgetsViewModel = viewModel(factory = appFactory())
                FamilyBudgetsScreen(familyId = familyId!!, viewModel = vm)
            }
            composable(Routes.CATEGORIES) {
                val vm: FamilyCategoriesViewModel = viewModel(factory = appFactory())
                FamilyCategoriesScreen(familyId = familyId!!, viewModel = vm)
            }
            composable(Routes.INCOME_SOURCES) {
                val vm: FamilyIncomeSourcesViewModel = viewModel(factory = appFactory())
                FamilyIncomeSourcesScreen(familyId = familyId!!, viewModel = vm)
            }
            composable(Routes.PROFILE) {
                val vm: FamilyProfileViewModel = viewModel(factory = appFactory())
                FamilyProfileScreen(
                    familyId = familyId!!,
                    viewModel = vm,
                    onOpenSubscription = { navController.navigate(Routes.SUBSCRIPTION) },
                    onLogout = onLogout,
                )
            }
            composable(Routes.SUBSCRIPTION) {
                val vm: FamilySubscriptionViewModel = viewModel(factory = appFactory())
                FamilySubscriptionScreen(viewModel = vm, onBack = { navController.popBackStack() })
            }
        }
    }
}