package com.keuangan.app.data

import kotlinx.serialization.KSerializer
import kotlinx.serialization.descriptors.PrimitiveKind
import kotlinx.serialization.descriptors.PrimitiveSerialDescriptor
import kotlinx.serialization.descriptors.SerialDescriptor
import kotlinx.serialization.encoding.Decoder
import kotlinx.serialization.encoding.Encoder
import kotlinx.serialization.json.JsonDecoder
import kotlinx.serialization.json.JsonElement
import kotlinx.serialization.json.JsonPrimitive

/**
 * Accepts a JSON boolean as `true`/`false` or raw `1`/`0`.
 *
 * Laravel's JSON output is inconsistent: model-cast booleans come out as
 * `true`/`false`, but integer columns (e.g. `setup_completed`) come out as
 * `1`/`0`. Rather than argue with the server on every field, this serializer
 * tolerates both spellings.
 */
object IntBooleanSerializer : KSerializer<Boolean> {
    override val descriptor: SerialDescriptor =
        PrimitiveSerialDescriptor("Boolean.AsInt", PrimitiveKind.BOOLEAN)

    override fun serialize(encoder: Encoder, value: Boolean) {
        encoder.encodeBoolean(value)
    }

    override fun deserialize(decoder: Decoder): Boolean {
        val json = decoder as? JsonDecoder ?: throw IllegalStateException("Int-boolean needs a JSON decoder")
        val element = json.decodeJsonElement() as? JsonPrimitive ?: throw IllegalStateException("Expected a boolean token")
        return when (element.content.trim()) {
            "true", "True", "TRUE", "1" -> true
            "false", "False", "FALSE", "0" -> false
            else -> throw IllegalStateException("Cannot read boolean from '${element.content}'")
        }
    }

    /** Helpers so call sites can peek/rewrite a raw element before it is decoded. */
    fun fromElement(element: JsonElement): Boolean = when (val text = (element as? JsonPrimitive)?.content) {
        "true", "True", "TRUE", "1" -> true
        "false", "False", "FALSE", "0" -> false
        else -> throw IllegalStateException("Cannot read boolean from '$text'")
    }
}