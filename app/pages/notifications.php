<!-- /notificaciones — Web Push prefs + per-device subscribe + test push.
     Catalog of which notifications fire and when lives in
     docs/notifications-spec.md. -->

<div class="hidden lg:flex items-center justify-between mb-5">
    <div>
        <h1 class="text-2xl font-semibold">Notificaciones</h1>
        <p class="text-sm text-muted mt-1">Avisos de fijos próximos, vencidos y resúmenes.</p>
    </div>
</div>

<div class="space-y-4 max-w-2xl">

    <!-- Master switch -->
    <div class="card">
        <label class="flex items-center justify-between gap-3">
            <div>
                <p class="font-medium">Notificaciones activas</p>
                <p class="text-xs text-muted mt-0.5">Si lo apagás, no recibís ningún tipo de aviso.</p>
            </div>
            <input type="checkbox" id="notif-master" class="h-5 w-5 accent-accent">
        </label>
    </div>

    <!-- Per-kind toggles -->
    <div class="card space-y-3" id="notif-kinds-card">
        <p class="text-xs font-semibold tracking-wide uppercase text-muted">Tipos</p>
        <label class="flex items-center justify-between gap-3">
            <div>
                <p class="font-medium">Recordatorios diarios</p>
                <p class="text-xs text-muted mt-0.5">Fijos próximos a vencer y vencidos. Sólo te aviso si hay algo.</p>
            </div>
            <input type="checkbox" id="notif-daily" class="h-5 w-5 accent-accent">
        </label>
        <label class="flex items-center justify-between gap-3">
            <div>
                <p class="font-medium">Resumen semanal</p>
                <p class="text-xs text-muted mt-0.5">Domingos a las 8 — gastos, ingresos y anomalías de la semana.</p>
            </div>
            <input type="checkbox" id="notif-weekly" class="h-5 w-5 accent-accent">
        </label>
        <label class="flex items-center justify-between gap-3">
            <div>
                <p class="font-medium">Alertas de anomalías</p>
                <p class="text-xs text-muted mt-0.5">Cuando algo se sale demasiado de tu promedio.</p>
            </div>
            <input type="checkbox" id="notif-anomalies" class="h-5 w-5 accent-accent">
        </label>
    </div>

    <!-- Per-device subscribe + test -->
    <div class="card space-y-3">
        <p class="text-xs font-semibold tracking-wide uppercase text-muted">Este dispositivo</p>
        <p id="notif-device-status" class="text-sm text-muted">&nbsp;</p>
        <div class="flex flex-col sm:flex-row sm:flex-wrap gap-2">
            <button type="button" id="notif-subscribe-btn"        class="btn btn-primary w-full sm:w-auto">Activar notificaciones</button>
            <button type="button" id="notif-unsubscribe-btn"      class="btn btn-outline w-full sm:w-auto hidden">Desactivar en este dispositivo</button>
            <button type="button" id="notif-test-btn" disabled    class="btn btn-outline w-full sm:w-auto">Enviar push de prueba</button>
        </div>
    </div>

</div>

<script>
const VAPID_PUBLIC_KEY = '<?= $config['vapid_public_key'] ?>';

// Web Push subscription expects the public key as a Uint8Array (raw bytes,
// not base64-url). Browser API decoded helper.
function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const raw = atob(base64);
    const out = new Uint8Array(raw.length);
    for (let i = 0; i < raw.length; i++) out[i] = raw.charCodeAt(i);
    return out;
}

async function getRegistration() {
    if (!('serviceWorker' in navigator)) return null;
    return await navigator.serviceWorker.ready;
}

async function getCurrentSubscription() {
    const reg = await getRegistration();
    if (!reg) return null;
    return await reg.pushManager.getSubscription();
}

async function refreshDeviceUI() {
    const status = document.getElementById('notif-device-status');
    const subBtn = document.getElementById('notif-subscribe-btn');
    const unsubBtn = document.getElementById('notif-unsubscribe-btn');
    const testBtn = document.getElementById('notif-test-btn');

    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
        status.textContent = 'Este navegador no soporta notificaciones push.';
        subBtn.disabled = true;
        return;
    }
    if (Notification.permission === 'denied') {
        status.textContent = 'Permiso denegado. Cambialo desde la configuración del navegador.';
        subBtn.disabled = true;
        return;
    }

    const sub = await getCurrentSubscription();
    if (sub) {
        status.textContent = '✅ Notificaciones activas en este dispositivo.';
        subBtn.classList.add('hidden');
        unsubBtn.classList.remove('hidden');
        testBtn.disabled = false;
    } else {
        status.textContent = 'Las notificaciones están desactivadas en este dispositivo.';
        subBtn.classList.remove('hidden');
        unsubBtn.classList.add('hidden');
        testBtn.disabled = true;
    }
}

async function subscribeDevice() {
    try {
        const permission = await Notification.requestPermission();
        if (permission !== 'granted') {
            toast('Permiso denegado', 'error');
            return;
        }
        const reg = await getRegistration();
        // Pass the raw ArrayBuffer rather than the Uint8Array view — iOS
        // Safari historically rejects the view with "must contain a valid
        // P-256 key" even when the bytes are correct.
        const sub = await reg.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY).buffer,
        });
        const json = sub.toJSON();
        const result = await api.post('/notifications/subscribe', json);
        if (result?.subscribed) {
            toast('Notificaciones activadas', 'success');
            refreshDeviceUI();
        } else {
            toast(result?.error || 'No se pudo activar', 'error');
        }
    } catch (err) {
        console.error(err);
        toast('Error: ' + err.message, 'error');
    }
}

async function unsubscribeDevice() {
    const sub = await getCurrentSubscription();
    if (!sub) return refreshDeviceUI();
    const endpoint = sub.endpoint;
    await sub.unsubscribe();
    await api.post('/notifications/unsubscribe', { endpoint });
    toast('Notificaciones desactivadas en este dispositivo', 'success');
    refreshDeviceUI();
}

async function sendTestPush() {
    const result = await api.post('/notifications/test-push', {});
    if (result?.sent != null) {
        toast(`Enviado a ${result.sent} dispositivo(s)`, 'success');
    } else {
        toast(result?.error || 'No se pudo enviar', 'error');
    }
}

async function loadPrefs() {
    const prefs = await api.get('/notifications/prefs');
    if (!prefs || prefs.error) return;
    document.getElementById('notif-master').checked = !prefs.master_off;
    document.getElementById('notif-daily').checked = prefs.daily_enabled;
    document.getElementById('notif-weekly').checked = prefs.weekly_enabled;
    document.getElementById('notif-anomalies').checked = prefs.anomaly_alerts_enabled;
    updateKindsDisabled();
}

function updateKindsDisabled() {
    const masterOn = document.getElementById('notif-master').checked;
    document.getElementById('notif-kinds-card').classList.toggle('opacity-50', !masterOn);
    ['notif-daily', 'notif-weekly', 'notif-anomalies'].forEach(id => {
        document.getElementById(id).disabled = !masterOn;
    });
}

async function savePrefs() {
    const body = {
        master_off:             !document.getElementById('notif-master').checked,
        daily_enabled:           document.getElementById('notif-daily').checked,
        weekly_enabled:          document.getElementById('notif-weekly').checked,
        anomaly_alerts_enabled:  document.getElementById('notif-anomalies').checked,
    };
    await api.put('/notifications/prefs', body);
}

document.getElementById('notif-master').addEventListener('change', () => {
    updateKindsDisabled();
    savePrefs();
});
['notif-daily', 'notif-weekly', 'notif-anomalies'].forEach(id => {
    document.getElementById(id).addEventListener('change', savePrefs);
});
document.getElementById('notif-subscribe-btn').addEventListener('click', subscribeDevice);
document.getElementById('notif-unsubscribe-btn').addEventListener('click', unsubscribeDevice);
document.getElementById('notif-test-btn').addEventListener('click', sendTestPush);

// Wait for Firebase to finish restoring its session before hitting the API —
// otherwise api.js sees a null token and redirects to /login.
mangosAuth.ready.then(async user => {
    if (!user) return;
    await loadPrefs();
    await refreshDeviceUI();
});
</script>
