<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cargar Ventas - Importar CSV</title>
    <style>
        body { font-family: -apple-system, Arial, sans-serif; max-width: 640px; margin: 60px auto; color: #1f2937; }
        h1 { font-size: 1.4rem; }
        .card { border: 1px solid #e5e7eb; border-radius: 10px; padding: 24px; }
        input[type=file] { display: block; margin: 16px 0; }
        button { background: #2563eb; color: #fff; border: none; padding: 10px 18px; border-radius: 6px; cursor: pointer; }
        button:disabled { opacity: .6; cursor: not-allowed; }
        #status { margin-top: 16px; font-size: .95rem; }
        a { color: #2563eb; }
    </style>
</head>
<body>
    <h1>Cargar archivo de ventas (CSV)</h1>
    <div class="card">
        <form id="uploadForm">
            <input type="file" name="file" id="fileInput" accept=".csv" required>
            <button type="submit" id="submitBtn">Subir e importar</button>
        </form>
        <div id="status"></div>
    </div>
    <p style="margin-top:20px;"><a href="{{ route('dashboard') }}">Ver historial de importaciones →</a></p>

    <script>
        document.getElementById('uploadForm').addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn = document.getElementById('submitBtn');
            const status = document.getElementById('status');
            const fileInput = document.getElementById('fileInput');

            if (!fileInput.files.length) return;

            const formData = new FormData();
            formData.append('file', fileInput.files[0]);

            btn.disabled = true;
            status.textContent = 'Subiendo y procesando en segundo plano...';

            try {
                const res = await fetch('/api/imports', { method: 'POST', body: formData });
                const data = await res.json();
                if (res.ok) {
                    status.innerHTML = `Listo. Importación #${data.import.id} en proceso. <a href="/">Ver en el dashboard</a>`;
                } else {
                    status.textContent = 'Error: ' + JSON.stringify(data);
                }
            } catch (err) {
                status.textContent = 'Error de red: ' + err.message;
            } finally {
                btn.disabled = false;
            }
        });
    </script>
</body>
</html>
