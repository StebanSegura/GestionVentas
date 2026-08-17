<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte - Importación #{{ $import->id }}</title>
    <style>
        body { font-family: -apple-system, Arial, sans-serif; max-width: 900px; margin: 40px auto; color: #1f2937; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 24px; }
        .card { border: 1px solid #e5e7eb; border-radius: 10px; padding: 18px; }
        .kpi { font-size: 1.8rem; font-weight: 700; color: #2563eb; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: .9rem; }
        th, td { padding: 6px 8px; border-bottom: 1px solid #f0f0f0; text-align: left; }
        a { color: #2563eb; text-decoration: none; }
    </style>
</head>
<body>
    <p><a href="{{ route('dashboard') }}">← Volver al dashboard</a></p>
    <h1>Reporte de la importación #{{ $import->id }} ({{ $import->filename }})</h1>

    <div class="card" style="margin-top:16px;">
        <div>Ingresos Totales</div>
        <div class="kpi" id="totalRevenue">Cargando...</div>
    </div>

    <div class="grid">
        <div class="card">
            <h3>Top 5 Productos</h3>
            <table id="topProducts"><tbody></tbody></table>
        </div>
        <div class="card">
            <h3>Por Categoría</h3>
            <table id="byCategory"><tbody></tbody></table>
        </div>
        <div class="card">
            <h3>Por País</h3>
            <table id="byCountry"><tbody></tbody></table>
        </div>
        <div class="card">
            <h3>Errores de esta importación</h3>
            <p>{{ $import->error_count }} filas con errores.</p>
            <a href="/api/imports/{{ $import->id }}/errors" target="_blank">Ver detalle de errores (JSON)</a>
        </div>
    </div>

    <script>
        fetch('/api/reports/summary?import_id={{ $import->id }}')
            .then(res => res.json())
            .then(data => {
                document.getElementById('totalRevenue').textContent =
                    '$' + Number(data.total_revenue).toLocaleString();

                const fill = (id, rows, labelKey, valueKey) => {
                    const tbody = document.querySelector('#' + id + ' tbody');
                    tbody.innerHTML = rows.map(r =>
                        `<tr><td>${r[labelKey]}</td><td>$${Number(r.revenue).toLocaleString()}</td></tr>`
                    ).join('');
                };

                fill('topProducts', data.top_products, 'product_name');
                fill('byCategory', data.by_category, 'category');
                fill('byCountry', data.by_country, 'country');
            });
    </script>
</body>
</html>
