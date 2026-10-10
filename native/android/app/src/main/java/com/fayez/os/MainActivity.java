package com.fayez.os;
import android.os.Bundle;
import android.view.WindowManager;
import androidx.activity.OnBackPressedCallback;
import com.getcapacitor.BridgeActivity;
import androidx.core.view.WindowCompat;
import androidx.core.view.ViewCompat;
import androidx.core.view.WindowInsetsCompat;
import androidx.core.graphics.Insets;
import android.view.View;

public class MainActivity extends BridgeActivity {
    private boolean wasPaused;
    private void notifyWeb(String event) {
        if (getBridge() != null && getBridge().getWebView() != null)
            getBridge().getWebView().evaluateJavascript("window.dispatchEvent(new Event('" + event + "'));", null);
    }
    @Override public void onPause() { wasPaused = true; notifyWeb("fayez:native-pause"); super.onPause(); }
    @Override public void onResume() { super.onResume(); if (wasPaused) { wasPaused = false; notifyWeb("fayez:native-resume"); } }

    @Override public void onCreate(Bundle savedInstanceState) {
        registerPlugin(FayezNative.class);
        super.onCreate(savedInstanceState);
        // Protect screen captures/recent-task previews; this is not storage encryption.
        WindowCompat.setDecorFitsSystemWindows(getWindow(), false);
        View content = findViewById(android.R.id.content);
        ViewCompat.setOnApplyWindowInsetsListener(content, (view, windowInsets) -> {
            Insets insets = windowInsets.getInsets(WindowInsetsCompat.Type.systemBars() | WindowInsetsCompat.Type.displayCutout() | WindowInsetsCompat.Type.ime());
            view.setPadding(insets.left, insets.top, insets.right, insets.bottom);
            return WindowInsetsCompat.CONSUMED;
        });
        ViewCompat.requestApplyInsets(content);
        getWindow().addFlags(WindowManager.LayoutParams.FLAG_SECURE);
        getOnBackPressedDispatcher().addCallback(this, new OnBackPressedCallback(true) {
            @Override public void handleOnBackPressed() {
                if (getBridge() == null || getBridge().getWebView() == null) { moveTaskToBack(true); return; }
                getBridge().getWebView().evaluateJavascript("(function(){if(window.FayezOS){window.dispatchEvent(new Event('fayez:android-back'));return true;}return false;})()", value -> { if (!"true".equals(value)) moveTaskToBack(true); });
            }
        });
    }
}
