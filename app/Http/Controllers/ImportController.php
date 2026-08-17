<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessImportJob;
use App\Models\Import;
use Illuminate\Http\Request;

class ImportController extends Controller
{
    // GET /api/imports
    public function index()
    {
        $imports = Import::orderBy('created_at', 'desc')->paginate(15);

        return response()->json($imports);
    }

    // POST /api/imports
    public function store(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:51200'], // hasta 50MB
        ]);

        $uploadedFile = $request->file('file');

        // Guardamos el archivo en storage/app/imports (fuera de public) con nombre único.
        $storedPath = $uploadedFile->store('imports');
        $absolutePath = storage_path('app/' . $storedPath);

        $import = Import::create([
            'filename' => $uploadedFile->getClientOriginalName(),
            'status' => 'processing',
        ]);

        // Respuesta inmediata: el procesamiento pesado se hace en background.
        ProcessImportJob::dispatch($import->id, $absolutePath);

        return response()->json([
            'message' => 'Archivo recibido, procesamiento iniciado.',
            'import' => $import,
        ], 202);
    }

    // GET /api/imports/{id}/errors
    public function errors(int $id)
    {
        $import = Import::findOrFail($id);
        $errors = $import->errors()->orderBy('row_number')->paginate(50);

        return response()->json($errors);
    }

    // DELETE /api/imports/{id}
    public function destroy(int $id)
    {
        $import = Import::findOrFail($id);

        // Gracias a cascadeOnDelete() en las migraciones, sales e import_errors
        // se eliminan automáticamente en una sola operación.
        $import->delete();

        return response()->json(['message' => 'Importación eliminada correctamente.']);
    }

    // Vistas Blade
    public function uploadView()
    {
        return view('imports.upload');
    }

    public function dashboardView()
    {
        $imports = Import::orderBy('created_at', 'desc')->paginate(15);

        return view('imports.dashboard', compact('imports'));
    }
}
