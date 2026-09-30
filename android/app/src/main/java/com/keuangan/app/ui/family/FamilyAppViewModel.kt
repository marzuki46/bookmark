package com.keuangan.app.ui.family

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.keuangan.app.data.FamilyDto
import com.keuangan.app.data.KeuanganRepository
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch

/**
 * Owns the bottom-navigation shell. The logged-in user belongs to exactly one
 * family, so this is the single place that resolves it; every child screen
 * reads [familyId] from here instead of refetching the list.
 */
class FamilyAppViewModel(private val repository: KeuanganRepository) : ViewModel() {

    /** Id of the logged-in member, used to resolve this user's row in [family]. */
    val currentUserId: Int? get() = repository.currentUserId

    /**
     * Display name of the logged-in member, kept as a fallback for when
     * [currentUserId] could not be matched against [family].
     */
    val currentUserName: String? get() = repository.currentUserName

    private val _familyId = MutableStateFlow<Int?>(null)
    val familyId: StateFlow<Int?> = _familyId.asStateFlow()

    private val _family = MutableStateFlow<FamilyDto?>(null)
    val family: StateFlow<FamilyDto?> = _family.asStateFlow()

    private val _memberName = MutableStateFlow<String?>(null)
    val memberName: StateFlow<String?> = _memberName.asStateFlow()

    private val _loading = MutableStateFlow(true)
    val loading: StateFlow<Boolean> = _loading.asStateFlow()

    init {
        // Show the last known family immediately while the fresh membership
        // request runs. This removes the blank startup state on slow networks.
        repository.cachedFamilyId?.let { cachedId ->
            _familyId.value = cachedId
            _loading.value = false
        }
        _memberName.value = repository.currentUserName
        resolve()
    }

    fun resolve() {
        viewModelScope.launch {
            // Keep the greeting's name warm. The dashboard shows it before any
            // child screen has had a chance to call /me, and a cold start with an
            // empty token store would otherwise greet nobody.
            repository.currentUser().let { result ->
                if (result is com.keuangan.app.data.ApiResult.Ok) {
                    result.value.name
                        ?.substringBefore(' ')
                        ?.takeIf { it.isNotBlank() }
                        ?.let { _memberName.value = it }
                }
            }

            repository.loadFamily(force = true)
            _family.value = repository.family.value
            _familyId.value = repository.familyId

            // A member row matched by id is the better source: it is the name the
            // rest of the family sees, not just the account display name.
            _memberName.value = repository.currentUserName
                ?: _family.value?.members
                    ?.firstOrNull { it.userId == repository.currentUserId }
                    ?.name
                    ?.substringBefore(' ')
                    ?.takeIf { it.isNotBlank() }
            _loading.value = false
        }
    }
}
