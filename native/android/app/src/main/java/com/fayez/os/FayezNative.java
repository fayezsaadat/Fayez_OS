package com.fayez.os;

import android.app.Activity;
import android.content.Intent;
import android.net.Uri;
import android.util.Base64;
import androidx.activity.result.ActivityResult;
import com.getcapacitor.JSObject;
import com.getcapacitor.Plugin;
import com.getcapacitor.PluginCall;
import com.getcapacitor.PluginMethod;
import com.getcapacitor.annotation.ActivityCallback;
import com.getcapacitor.annotation.CapacitorPlugin;
import java.io.*;
import java.util.UUID;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;

/** Narrow, user-selected SAF export. No general filesystem, HTTP or call bridge. */
@CapacitorPlugin(name = "FayezNative")
public class FayezNative extends Plugin {
    private final ExecutorService writer = Executors.newSingleThreadExecutor();
    private File staging;
    private String token, name;
    private long expected, written;
    private boolean choosing;
    private static final long LIMIT = 64L * 1024 * 1024;
    @Override public void load() {
        File[] old = getContext().getCacheDir().listFiles((dir, file) -> file.startsWith("fayez-export-"));
        if (old != null) for (File file : old) file.delete();
    }
    @PluginMethod public synchronized void beginExport(PluginCall call) {
        if (staging != null) { call.reject("Another export is in progress."); return; }
        String raw = call.getString("name", "Fayez-export.bin");
        Integer size = call.getInt("size");
        if (size == null || size < 0 || size > LIMIT || raw.length() > 150 || raw.isEmpty()) { call.reject("Invalid export name or size (64 MiB maximum)."); return; }
        try {
            name = raw.replaceAll("[\\p{Cntrl}/\\\\:\\u202A-\\u202E\\u2066-\\u2069]", "_");
            staging = File.createTempFile("fayez-export-", ".tmp", getContext().getCacheDir());
            token = UUID.randomUUID().toString(); expected = size; written = 0; choosing = false;
            JSObject result = new JSObject(); result.put("id", token); call.resolve(result);
        } catch (IOException error) { cleanup(); call.reject("Cannot create a private staging file."); }
    }
    private boolean valid(PluginCall call) { return staging != null && token.equals(call.getString("id")); }
    @PluginMethod public synchronized void writeExport(PluginCall call) {
        if (!valid(call) || choosing) { call.reject("No matching writable export."); return; }
        String data = call.getString("data", ""); Integer offset = call.getInt("offset");
        if (offset == null || offset != written || data.length() > 65536 || !data.matches("[A-Za-z0-9+/]*={0,2}")) { call.reject("Invalid export chunk/order."); return; }
        try {
            byte[] bytes = Base64.decode(data, Base64.NO_WRAP);
            if (bytes.length > 49152 || written + bytes.length > expected) { call.reject("Export exceeds declared size."); return; }
            try (FileOutputStream out = new FileOutputStream(staging, true)) { out.write(bytes); }
            written += bytes.length; call.resolve();
        } catch (Exception error) { cleanup(); call.reject("Cannot write export. Free device storage and retry."); }
    }
    @PluginMethod public synchronized void finishExport(PluginCall call) {
        if (!valid(call) || choosing || written != expected) { call.reject("Export is incomplete or already open."); return; }
        choosing = true;
        Intent intent = new Intent(Intent.ACTION_CREATE_DOCUMENT).addCategory(Intent.CATEGORY_OPENABLE).setType("application/octet-stream").putExtra(Intent.EXTRA_TITLE, name);
        try { startActivityForResult(call, intent, "exportSelected"); }
        catch (Exception error) { cleanup(); call.reject("A system document picker is unavailable."); }
    }
    @ActivityCallback private synchronized void exportSelected(PluginCall call, ActivityResult result) {
        if (call == null) { cleanup(); return; }
        if (!valid(call)) { call.reject("Export was interrupted. Start again."); return; }
        if (result.getResultCode() != Activity.RESULT_OK || result.getData() == null || result.getData().getData() == null) { cleanup(); call.reject("Export cancelled. No saved copy was confirmed."); return; }
        Uri uri = result.getData().getData();
        if (!"content".equals(uri.getScheme())) { cleanup(); call.reject("Unsupported destination."); return; }
        final File source = staging;
        writer.execute(() -> {
        try {
        try (InputStream in = new BufferedInputStream(new FileInputStream(source)); OutputStream out = getContext().getContentResolver().openOutputStream(uri, "wt")) {
            if (out == null) throw new IOException("No destination stream");
            byte[] bytes = new byte[65536]; int count;
            while ((count = in.read(bytes)) != -1) out.write(bytes, 0, count);
            out.flush();
        }
        JSObject response = new JSObject(); response.put("saved", true); call.resolve(response);
        } catch (Exception error) { call.reject("Save failed. The destination may contain an incomplete file; verify it before relying on it."); }
        finally { synchronized (FayezNative.this) { if (staging == source) cleanup(); else source.delete(); } }
        });
    }
    @PluginMethod public synchronized void cancelExport(PluginCall call) {
        if (valid(call) && !choosing) cleanup(); call.resolve();
    }
    private void cleanup() { if (staging != null) staging.delete(); staging = null; token = null; choosing = false; }
    @Override protected void handleOnDestroy() { synchronized (this) { cleanup(); } writer.shutdownNow(); }
    @PluginMethod public void dial(PluginCall call) {
        String number = call.getString("number", "");
        if (!number.matches("\\+?[0-9]{3,30}")) { call.reject("Only ordinary phone numbers are allowed."); return; }
        try { getActivity().startActivity(new Intent(Intent.ACTION_DIAL, Uri.fromParts("tel", number, null))); call.resolve(); }
        catch (Exception error) { call.reject("No device dialer is installed. Copy the number instead."); }
    }
    @PluginMethod public void minimize(PluginCall call) { getActivity().runOnUiThread(() -> { getActivity().moveTaskToBack(true); call.resolve(); }); }
}
