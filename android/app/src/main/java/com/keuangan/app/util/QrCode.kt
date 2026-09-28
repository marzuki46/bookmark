package com.keuangan.app.util

import android.graphics.Bitmap
import android.graphics.Color
import com.google.zxing.BarcodeFormat
import com.google.zxing.EncodeHintType
import com.google.zxing.qrcode.QRCodeWriter
import com.google.zxing.qrcode.decoder.ErrorCorrectionLevel
import java.util.Hashtable

/** Tiny QR encoder backed by ZXing core; no camera or Play Services needed. */
object QrCode {
    fun bitmap(content: String, size: Int = 480): Bitmap {
        val hints = Hashtable<EncodeHintType, Any>().apply {
            this[EncodeHintType.CHARACTER_SET] = "UTF-8"
            this[EncodeHintType.ERROR_CORRECTION] = ErrorCorrectionLevel.M
            this[EncodeHintType.MARGIN] = 1
        }
        val matrix = QRCodeWriter().encode(content, BarcodeFormat.QR_CODE, size, size, hints)
        val bitmap = Bitmap.createBitmap(size, size, Bitmap.Config.ARGB_8888)
        for (x in 0 until size) {
            for (y in 0 until size) {
                bitmap.setPixel(x, y, if (matrix.get(x, y)) Color.BLACK else Color.WHITE)
            }
        }
        return bitmap
    }
}