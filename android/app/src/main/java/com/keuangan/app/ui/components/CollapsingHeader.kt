package com.keuangan.app.ui.components

import androidx.compose.animation.core.animateFloatAsState
import androidx.compose.animation.core.tween
import androidx.compose.foundation.lazy.LazyListState
import androidx.compose.foundation.ScrollState
import androidx.compose.runtime.Composable
import androidx.compose.runtime.State
import androidx.compose.runtime.derivedStateOf
import androidx.compose.runtime.getValue
import androidx.compose.runtime.remember
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clipToBounds
import androidx.compose.ui.graphics.graphicsLayer
import androidx.compose.ui.layout.layout
import androidx.compose.ui.unit.Constraints
import kotlin.math.roundToInt

/**
 * How far the header must travel before it collapses. Small enough that it
 * leaves quickly, large enough that a slight bounce or a fresh-open overscroll
 * does not hide it the moment the screen appears.
 */
const val HEADER_COLLAPSE_TRIGGER_PX = 72f

/**
 * True once [headerIndex] has been scrolled past.
 *
 * The index is a parameter rather than assumed to be 0 because several screens
 * put an error banner or a loading row in front of the header; assuming 0 would
 * make the header collapse the moment that row leaves the top.
 */
fun isHeaderCollapsed(
    listState: LazyListState,
    headerIndex: Int = 0,
    triggerPx: Float = HEADER_COLLAPSE_TRIGGER_PX,
): Boolean {
    val first = listState.firstVisibleItemIndex
    val offset = listState.firstVisibleItemScrollOffset
    return if (first > headerIndex) true else first == headerIndex && offset > triggerPx
}

/**
 * Animated 1f-to-0f progress for a header that slides away as the user scrolls
 * down and returns the moment they scroll back up.
 */
@Composable
fun rememberHeaderCollapse(
    listState: LazyListState,
    headerIndex: Int = 0,
): State<Float> {
    val collapsed by remember(listState, headerIndex) {
        derivedStateOf { isHeaderCollapsed(listState, headerIndex) }
    }
    val fraction = animateFloatAsState(
        targetValue = if (collapsed) 0f else 1f,
        animationSpec = tween(durationMillis = HEADER_COLLAPSE_ANIM_MS),
        label = "headerCollapse",
    )
    return fraction
}

@Composable
fun rememberHeaderCollapse(scrollState: ScrollState): State<Float> {
    val collapsed by remember(scrollState) {
        derivedStateOf { scrollState.value > HEADER_COLLAPSE_TRIGGER_PX }
    }
    return animateFloatAsState(
        targetValue = if (collapsed) 0f else 1f,
        animationSpec = tween(durationMillis = HEADER_COLLAPSE_ANIM_MS),
        label = "headerCollapse",
    )
}

private const val HEADER_COLLAPSE_ANIM_MS = 200

/**
 * Shrinks a header to nothing as [fraction] goes to 0, while still occupying its
 * slot at full size when expanded.
 *
 * The child is always measured with an unbounded height and the reported size is
 * scaled afterwards. Measuring it against the already-collapsed height instead
 * is what makes this kind of header disappear for good: the child can never
 * report a non-zero height, so the header stays at zero forever.
 */
fun Modifier.collapsingHeader(fraction: () -> Float): Modifier = this
    .layout { measurable, constraints ->
        val placeable = measurable.measure(
            Constraints(
                minWidth = constraints.minWidth,
                maxWidth = constraints.maxWidth,
                minHeight = 0,
                maxHeight = Constraints.Infinity,
            ),
        )
        val shown = (placeable.height * fraction().coerceIn(0f, 1f)).roundToInt()
        layout(placeable.width, shown) {
            placeable.placeRelative(0, 0)
        }
    }
    .clipToBounds()
    .graphicsLayer { alpha = fraction().coerceIn(0f, 1f) }
