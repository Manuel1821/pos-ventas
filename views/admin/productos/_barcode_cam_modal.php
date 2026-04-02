<!-- Modal: escanear código de barras con la cámara (productos) -->
<div class="modal fade" id="productBarcodeCameraModal" tabindex="-1" aria-labelledby="productBarcodeCameraModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title" id="productBarcodeCameraModalLabel">
                    <i class="bi bi-upc-scan me-2"></i> Escanear con cámara
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body pt-2">
                <p id="productBarcodeCameraStatus" class="small text-muted mb-2">Iniciando cámara...</p>
                <div id="productBarcodeScannerRegion" class="rounded-3 overflow-hidden bg-dark" style="min-height:200px;"></div>
                <p class="small text-muted mt-2 mb-0">Apunta al código. Al leerlo se rellenará el campo «Código de barras».</p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary rounded-3" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
(function () {
    var modalEl = document.getElementById('productBarcodeCameraModal');
    var regionId = 'productBarcodeScannerRegion';
    var statusEl = document.getElementById('productBarcodeCameraStatus');
    var barcodeInput = document.getElementById('barcode');
    var openBtn = document.getElementById('productBarcodeOpenCameraBtn');
    if (!modalEl || !barcodeInput) return;

    var stream = null;
    var intervalId = null;
    var busy = false;
    var lastVal = '';
    var lastAt = 0;
    var detector = null;
    var html5Scanner = null;
    var videoEl = null;

    function setStatus(msg) {
        if (statusEl) statusEl.textContent = msg;
    }

    function clearDetectorInterval() {
        if (intervalId) {
            clearInterval(intervalId);
            intervalId = null;
        }
        detector = null;
    }

    function stopVideoTracks() {
        if (stream) {
            stream.getTracks().forEach(function (t) { t.stop(); });
            stream = null;
        }
        if (videoEl && videoEl.parentNode) {
            videoEl.parentNode.removeChild(videoEl);
            videoEl = null;
        }
    }

    function stopCameraScanner() {
        clearDetectorInterval();
        stopVideoTracks();
        busy = false;
        if (html5Scanner) {
            html5Scanner.stop().then(function () {
                try { html5Scanner.clear(); } catch (e) {}
                html5Scanner = null;
            }).catch(function () {
                html5Scanner = null;
            });
        }
    }

    function applyCode(raw) {
        var v = String(raw || '').trim();
        if (!v) return;
        if (v.length > 120) v = v.substring(0, 120);
        barcodeInput.value = v;
        barcodeInput.dispatchEvent(new Event('input', { bubbles: true }));
        setStatus('Código capturado.');
        stopCameraScanner();
        try {
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                var inst = bootstrap.Modal.getInstance(modalEl) || bootstrap.Modal.getOrCreateInstance(modalEl);
                inst.hide();
            }
        } catch (e) {}
    }

    function startBarcodeDetectorPath() {
        if (!('BarcodeDetector' in window)) return false;
        try {
            detector = new window.BarcodeDetector({
                formats: ['ean_13', 'ean_8', 'upc_a', 'upc_e', 'code_128', 'code_39', 'itf', 'codabar', 'qr_code']
            });
        } catch (e) {
            return false;
        }
        var region = document.getElementById(regionId);
        if (!region) return false;
        region.innerHTML = '';
        videoEl = document.createElement('video');
        videoEl.setAttribute('playsinline', '');
        videoEl.setAttribute('autoplay', '');
        videoEl.setAttribute('muted', '');
        videoEl.style.width = '100%';
        videoEl.style.display = 'block';
        region.appendChild(videoEl);

        navigator.mediaDevices.getUserMedia({
            video: { facingMode: { ideal: 'environment' } },
            audio: false
        }).then(function (s) {
            stream = s;
            videoEl.srcObject = s;
            setStatus('Apunta al código de barras…');
            intervalId = setInterval(function () {
                if (busy || !detector || !videoEl || videoEl.readyState < 2) return;
                busy = true;
                detector.detect(videoEl).then(function (codes) {
                    if (codes && codes.length > 0) {
                        var val = String(codes[0].rawValue || '').trim();
                        if (val) {
                            var now = Date.now();
                            if (val === lastVal && (now - lastAt) < 1200) {
                                busy = false;
                                return;
                            }
                            lastVal = val;
                            lastAt = now;
                            applyCode(val);
                        }
                    }
                }).catch(function () {}).finally(function () { busy = false; });
            }, 320);
        }).catch(function () {
            stopVideoTracks();
            clearDetectorInterval();
            detector = null;
            var region = document.getElementById(regionId);
            if (region) region.innerHTML = '';
            startHtml5QrcodePath();
        });
        return true;
    }

    function startHtml5QrcodePath() {
        if (typeof Html5Qrcode === 'undefined') {
            setStatus('No se pudo cargar el lector de cámara. Recarga la página.');
            return;
        }
        var region = document.getElementById(regionId);
        if (!region) return;
        region.innerHTML = '';
        html5Scanner = new Html5Qrcode(regionId);
        var cfg = { fps: 8, qrbox: { width: 280, height: 140 }, aspectRatio: 1.7777778 };
        html5Scanner.start(
            { facingMode: 'environment' },
            cfg,
            function (decodedText) {
                var val = String(decodedText || '').trim();
                if (!val) return;
                var now = Date.now();
                if (val === lastVal && (now - lastAt) < 1200) return;
                lastVal = val;
                lastAt = now;
                applyCode(val);
            },
            function () {}
        ).catch(function () {
            setStatus('No se pudo usar la cámara. Revisa permisos o prueba con Chrome.');
        });
        setStatus('Apunta al código de barras…');
    }

    function startScanner() {
        lastVal = '';
        lastAt = 0;
        stopCameraScanner();
        setStatus('Solicitando cámara…');
        var region = document.getElementById(regionId);
        if (region) region.innerHTML = '';

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            setStatus('Este navegador no permite usar la cámara desde aquí.');
            return;
        }

        if (startBarcodeDetectorPath()) return;
        startHtml5QrcodePath();
    }

    modalEl.addEventListener('hidden.bs.modal', function () {
        stopCameraScanner();
        var region = document.getElementById(regionId);
        if (region) region.innerHTML = '';
        setStatus('Iniciando cámara...');
    });

    modalEl.addEventListener('shown.bs.modal', function () {
        startScanner();
    });

    if (openBtn) {
        openBtn.addEventListener('click', function () {
            try {
                var m = bootstrap.Modal.getOrCreateInstance(modalEl);
                m.show();
            } catch (e) {
                modalEl.classList.add('show');
                startScanner();
            }
        });
    }
})();
</script>
