package com.keuangan.app.scan

import android.Manifest
import android.content.Intent
import android.content.pm.PackageManager
import android.graphics.Color
import android.os.Bundle
import android.view.Gravity
import android.widget.FrameLayout
import android.widget.TextView
import androidx.activity.ComponentActivity
import androidx.activity.result.contract.ActivityResultContracts
import androidx.camera.core.CameraSelector
import androidx.camera.core.ImageAnalysis
import androidx.camera.core.ImageProxy
import androidx.camera.core.Preview
import androidx.camera.lifecycle.ProcessCameraProvider
import androidx.camera.view.PreviewView
import androidx.core.content.ContextCompat
import com.google.zxing.BinaryBitmap
import com.google.zxing.DecodeHintType
import com.google.zxing.MultiFormatReader
import com.google.zxing.PlanarYUVLuminanceSource
import com.google.zxing.common.HybridBinarizer
import java.util.Hashtable
import java.util.concurrent.Executors

/**
 * Camera + QR reader for the family login code. Parsing runs on the ZXing
 * core (pure Java), so no ML Kit or Play Services dependency is needed.
 * Returns the decoded code via [EXTRA_CODE] with RESULT_OK.
 */
class BarcodeScanActivity : ComponentActivity() {

    companion object {
        const val EXTRA_CODE = "extra_code"
        private const val PREVIEW_SCAN_MS = 150L
    }

    private val reader = MultiFormatReader()
    private val hints = Hashtable<DecodeHintType, Any>()
    private lateinit var previewView: PreviewView
    private val executor = Executors.newSingleThreadExecutor()
    private var finished = false

    private val permissionLauncher = registerForActivityResult(
        ActivityResultContracts.RequestPermission(),
    ) { granted ->
        if (granted) {
            startCamera()
        } else {
            finish()
        }
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        hints[DecodeHintType.TRY_HARDER] = true
        hints[DecodeHintType.CHARACTER_SET] = "UTF-8"

        previewView = PreviewView(this).apply {
            scaleType = PreviewView.ScaleType.FILL_CENTER
        }

        val hint = TextView(this).apply {
            text = "Arahkan ke barcode kode keluarga sampai terbaca"
            setTextColor(Color.WHITE)
            textSize = 15f
            gravity = Gravity.CENTER
            setPadding(24, 20, 24, 20)
        }
        val container = FrameLayout(this).apply {
            addView(previewView)
            addView(hint, FrameLayout.LayoutParams(FrameLayout.LayoutParams.MATCH_PARENT, FrameLayout.LayoutParams.WRAP_CONTENT, Gravity.BOTTOM).apply {
                bottomMargin = 40
            })
        }
        setContentView(container)

        if (ContextCompat.checkSelfPermission(this, Manifest.permission.CAMERA) == PackageManager.PERMISSION_GRANTED) {
            startCamera()
        } else {
            permissionLauncher.launch(Manifest.permission.CAMERA)
        }
    }

    private fun startCamera() {
        val providerFuture = ProcessCameraProvider.getInstance(this)
        providerFuture.addListener({
            val cameraProvider = providerFuture.get()
            val preview = Preview.Builder().build().also {
                it.surfaceProvider = previewView.surfaceProvider
            }
            val analysis = ImageAnalysis.Builder()
                .setBackpressureStrategy(ImageAnalysis.STRATEGY_KEEP_ONLY_LATEST)
                .setOutputImageFormat(ImageAnalysis.OUTPUT_IMAGE_FORMAT_YUV_420_888)
                .build()
            analysis.setAnalyzer(executor) { image ->
                decodeImage(image)?.let { code ->
                    if (!finished) {
                        val ret = synchronized(this) {
                            if (finished) null else { finished = true; Unit }
                        }
                        if (ret != null) {
                            val data = Intent().putExtra(EXTRA_CODE, code)
                            runOnUiThread {
                                setResult(RESULT_OK, data)
                                finish()
                            }
                        }
                    }
                }
                image.close()
            }
            try {
                cameraProvider.unbindAll()
                cameraProvider.bindToLifecycle(
                    this,
                    CameraSelector.DEFAULT_BACK_CAMERA,
                    preview,
                    analysis,
                )
            } catch (_: Exception) {
                finish()
            }
        }, ContextCompat.getMainExecutor(this))
    }

    private fun decodeImage(image: ImageProxy): String? {
        val plane = image.planes[0]
        val buffer = plane.buffer
        val data = ByteArray(buffer.remaining())
        buffer.get(data)
        try {
            val source = PlanarYUVLuminanceSource(
                data,
                plane.rowStride,
                image.height,
                0, 0,
                image.width,
                image.height,
                false,
            )
            val result = reader.decode(BinaryBitmap(HybridBinarizer(source)), hints)
            return result.text?.trim().orEmpty().ifEmpty { null }
        } catch (_: Exception) {
            return null
        }
    }

    override fun onDestroy() {
        super.onDestroy()
        executor.shutdown()
    }
}