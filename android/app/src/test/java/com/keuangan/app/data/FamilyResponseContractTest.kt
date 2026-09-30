package com.keuangan.app.data

import kotlinx.serialization.json.Json
import org.junit.Assert.assertEquals
import org.junit.Assert.assertNotNull
import org.junit.Assert.assertNull
import org.junit.Assert.assertTrue
import org.junit.Test

/**
 * Guards the response contracts that have actually broken clients before.
 *
 * Every payload below is the byte-for-byte shape the Laravel API returned when
 * these endpoints were probed, so a schema change on the server that turns a
 * field into `null` or renames it fails here instead of crashing the dashboard
 * with "unexpected JSON token at offset N".
 */
class FamilyResponseContractTest {

    private val json = Json { ignoreUnknownKeys = true }

    @Test
    fun `family detail decodes when member visibility is explicitly null`() {
        // visibility:null is what the server sends for a member that has not
        // been granted per-stream visibility yet. Declaring it non-nullable
        // made kotlinx abort with "expected start of the object".
        val payload = """
            {"data":{"id":1,"name":"Keluarga Marzuki","role":"owner","payer_role":null,
            "payer_label":null,"members_count":2,"housing_complex":null,"members":[
            {"user_id":7,"role":"owner","name":"Marzuki","payer_role":null,
            "payer_label":null,"relationship":"adult","visibility":null},
            {"user_id":9,"role":"member","name":"Siti Aminah","payer_role":"ibu",
            "payer_label":"Ibu","relationship":"adult",
            "visibility":{"income":false,"expense":true,"debts":true}}
            ]}}
        """.trimIndent()

        val family = json.decodeFromString<FamilyResponse>(payload).data

        assertEquals(2, family.members.size)
        assertEquals("Marzuki", family.members[0].name)
        assertNull("null visibility must survive as null", family.members[0].visibility)
        assertEquals(false, family.members[1].visibility?.get("income"))
        assertNull(family.housingComplex)
    }

    @Test
    fun `forecast decodes with license at the response root`() {
        val payload = """
            {"data":{"today":{"current":{"income":500000,"expense":75000},
            "previous":{"income":0,"expense":0},
            "delta":{"income_delta":500000,"income_pct":null,
            "expense_delta":75000,"expense_pct":null}},
            "week":{"current":{"income":500000,"expense":75000},
            "previous":{"income":0,"expense":0},
            "delta":{"income_delta":500000,"income_pct":null,
            "expense_delta":75000,"expense_pct":null}},
            "month":{"current":{"income":500000,"expense":75000},
            "previous":{"income":0,"expense":0},
            "delta":{"income_delta":500000,"income_pct":null,
            "expense_delta":75000,"expense_pct":null}}},
            "license":{"income_visible":true,"expense_visible":true}}
        """.trimIndent()

        val response = json.decodeFromString<FamilyForecastResponse>(payload)

        assertTrue(response.license.incomeVisible)
        assertTrue(response.license.expenseVisible)
        assertEquals(500000.0, response.data.today.current.income, 0.01)
        // A null percentage is the server saying "no previous period to compare".
        assertNull(response.data.today.delta.incomePct)
    }

    @Test
    fun `forecast decodes when both streams are hidden`() {
        val payload = """
            {"data":{"today":{"current":{"income":0,"expense":0},
            "previous":{"income":0,"expense":0},
            "delta":{"income_delta":0,"income_pct":null,
            "expense_delta":0,"expense_pct":null}},
            "week":{"current":{"income":0,"expense":0},
            "previous":{"income":0,"expense":0},
            "delta":{"income_delta":0,"income_pct":null,
            "expense_delta":0,"expense_pct":null}},
            "month":{"current":{"income":0,"expense":0},
            "previous":{"income":0,"expense":0},
            "delta":{"income_delta":0,"income_pct":null,
            "expense_delta":0,"expense_pct":null}}},
            "license":{"income_visible":false,"expense_visible":false}}
        """.trimIndent()

        val response = json.decodeFromString<FamilyForecastResponse>(payload)

        assertTrue(!response.license.incomeVisible)
        assertTrue(!response.license.expenseVisible)
    }

    @Test
    fun `insights decode with null family personal and nudge`() {
        val payload = """
            {"data":{"family":null,"personal":null,"nudge":null,
            "income_by_source":[{"income_source_id":null,"name":"Belum dikategorikan",
            "total":500000}],"week_key":"2026-W40","days_left_this_month":0}}
        """.trimIndent()

        val response = json.decodeFromString<FamilyInsightsEnvelope>(payload)

        assertNull(response.data.family)
        assertNull(response.data.personal)
        assertNull(response.data.nudge)
        assertEquals(1, response.data.incomeBySource.size)
        assertEquals(500000.0, response.data.incomeBySource[0].total, 0.01)
    }

    @Test
    fun `empty collections decode from php empty arrays`() {
        // PHP renders an empty collection as [] and an empty map as [], which is
        // why every list/map field here has a default rather than being required.
        val goals = json.decodeFromString<FamilyGoalListResponse>("""{"data":[]}""")
        val budgets = json.decodeFromString<FamilyBudgetResponse>(
            """{"data":{"month":9,"year":2026,"budgets":[]}}""",
        )
        val nudge = json.decodeFromString<NudgeResponse>("""{"data":null}""")

        assertTrue(goals.data.isEmpty())
        assertTrue(budgets.data.budgets.isEmpty())
        assertNull(nudge.data)
    }

    @Test
    fun `summary decodes a populated health block`() {
        val payload = """
            {"data":{"score":85,"grade":"Sangat Sehat","insufficient_data":false,
            "income":5000000,"expense":2750000,"savings":2250000,
            "essential_monthly":2500000,"emergency_current":1250000,
            "emergency_target":10000000,"total_debt":3500000,"planned_debt":0,
            "realized_debt_this_month":500000,"uncovered_debt":3000000,
            "recommendations":["Kebutuhan pokok masih di bawah 50% pemasukan"]}}
        """.trimIndent()

        val summary = json.decodeFromString<FamilySummaryResponse>(payload).data

        assertNotNull(summary)
        assertEquals(85, summary.score)
        assertEquals(1, summary.recommendations.size)
    }
}