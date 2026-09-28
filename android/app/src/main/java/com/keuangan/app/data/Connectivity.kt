package com.keuangan.app.data

import android.content.Context
import android.net.ConnectivityManager
import android.net.Network
import android.net.NetworkCapabilities
import kotlinx.coroutines.flow.MutableSharedFlow
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asSharedFlow
import kotlinx.coroutines.flow.asStateFlow

/**
 * Observes the default network and exposes two signals the offline sync relies on:
 *  - [online]: current reachability, so screens can flip between live and cached mode;
 *  - [recovered]: emitted when connectivity comes back after having been lost,
 *    so queued writes can be replayed without the user doing anything.
 */
class Connectivity(context: Context) {

    private val cm = context.getSystemService(ConnectivityManager::class.java)
    private val _online = MutableStateFlow(currentlyOnline())
    val online: StateFlow<Boolean> = _online.asStateFlow()

    private val _recovered = MutableSharedFlow<Unit>(extraBufferCapacity = 1)
    val recovered = _recovered.asSharedFlow()

    init {
        val callback = object : ConnectivityManager.NetworkCallback() {
            override fun onAvailable(network: Network) {
                val was = _online.value
                _online.value = currentlyOnline()
                if (!was && _online.value) _recovered.tryEmit(Unit)
            }

            override fun onLost(network: Network) {
                _online.value = currentlyOnline()
            }

            override fun onCapabilitiesChanged(network: Network, capabilities: NetworkCapabilities) {
                val was = _online.value
                _online.value = currentlyOnline()
                if (!was && _online.value) _recovered.tryEmit(Unit)
            }
        }

        if (android.os.Build.VERSION.SDK_INT >= android.os.Build.VERSION_CODES.N) {
            cm.registerDefaultNetworkCallback(callback)
        }
    }

    private fun currentlyOnline(): Boolean {
        val network = cm.activeNetwork ?: return false
        val caps = cm.getNetworkCapabilities(network) ?: return false
        return caps.hasCapability(NetworkCapabilities.NET_CAPABILITY_INTERNET)
    }
}