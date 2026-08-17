<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard de Importaciones</title>
    <style>
        body { font-family: -apple-system, Arial, sans-serif; max-width: 900px; margin: 40px auto; color: #1f2937; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { text-align: left; padding: 10px 12px; border-bottom: 1px solid #e5e7eb; font-size: .92rem; }
        th { background: #f9fafb; }
        .badge { padding: 3px 10px; border-radius: 999px; font-size: .78rem; }
        .badge-completed { background: #dcfce7; color: #166534; }
        .badge-processing { background: #fef9c3; color: #854d0e; }
        .badge-failed { background: #fee2e2; color: #991b1b; }
        a.button { background: #2563eb; color: #fff; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-size: .9rem; }
        a { color: #2563eb; text-decoration: none; }
    </style>
</head>
<body>
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h1>Historial de Importaciones</h1>
        <a class="button" href="{{ route('upload') }}">+ Nueva importación</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th><th>Archivo</th><th>Estado</th><th>Filas OK</th><th>Errores</th><th>Fecha</th><th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($imports as $import)
                <tr>
                    <td>{{ $import->id }}</td>
                    <td>{{ $import->filename }}</td>
                    <td><span class="badge badge-{{ $import->status }}">{{ $import->status }}</span></td>
                    <td>{{ $import->success_count }}</td>
                    <td>{{ $import->error_count }}</td>
                    <td>{{ $import->created_at->format('Y-m-d H:i') }}</td>
                    <td>
                        <a href="{{ route('imports.detail', $import->id) }}">Ver reporte</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7">Aún no hay importaciones.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top:20px;">
        {{ $imports->links() }}
    </div>
</body>
</html>
