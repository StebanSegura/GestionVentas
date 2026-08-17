<?php

namespace App\Jobs;

use App\Models\Import;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 1800;

    protected int $importId;
    protected string $filePath;

    protected int $chunkSize = 1000;

    public function __construct(int $importId, string $filePath)
    {
        $this->importId = $importId;
        $this->filePath = $filePath;
    }

    public function handle(): void
    {
        $import = Import::findOrFail($this->importId);

        $handle = fopen($this->filePath, 'r');
        if ($handle === false) {
            $import->update(['status' => 'failed']);
            return;
        }

        // Saltar cabecera
        $header = fgetcsv($handle);

        $rowNumber = 1;
        $totalRows = 0;
        $successCount = 0;
        $errorCount = 0;

        $salesBuffer = [];
        $errorsBuffer = [];

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;
            $totalRows++;

            $data = $this->mapRow($row, $header);
            $validationError = $this->validateRow($data);

            if ($validationError !== null) {
                $errorCount++;
                $errorsBuffer[] = [
                    'import_id' => $this->importId,
                    'row_number' => $rowNumber,
                    'reason' => $validationError,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            } else {
                $successCount++;
                $total = round($data['quantity'] * $data['unit_price'] * (1 - $data['discount']), 2);

                $salesBuffer[] = [
                    'import_id' => $this->importId,
                    'order_id' => $data['order_id'],
                    'order_date' => $data['date'],
                    'customer_id' => $data['customer_id'],
                    'customer_name' => $data['customer_name'],
                    'product_id' => $data['product_id'],
                    'product_name' => $data['product_name'],
                    'category' => $data['category'],
                    'quantity' => $data['quantity'],
                    'unit_price' => $data['unit_price'],
                    'discount' => $data['discount'],
                    'total' => $total,
                    'country' => $data['country'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (count($salesBuffer) >= $this->chunkSize) {
                DB::table('sales')->insert($salesBuffer);
                $salesBuffer = [];
            }
            if (count($errorsBuffer) >= $this->chunkSize) {
                DB::table('import_errors')->insert($errorsBuffer);
                $errorsBuffer = [];
            }
        }

        // Insertar lo que quedó en los buffers
        if (!empty($salesBuffer)) {
            DB::table('sales')->insert($salesBuffer);
        }
        if (!empty($errorsBuffer)) {
            DB::table('import_errors')->insert($errorsBuffer);
        }

        fclose($handle);

        $import->update([
            'status' => 'completed',
            'total_rows' => $totalRows,
            'success_count' => $successCount,
            'error_count' => $errorCount,
            'processed_at' => now(),
        ]);

        // Limpieza: ya no se necesita el archivo crudo en disco.
        @unlink($this->filePath);
    }

    protected function mapRow(array $row, array $header): array
    {
        $combined = @array_combine($header, $row) ?: [];

        return [
            'order_id' => trim($combined['order_id'] ?? ''),
            'date' => trim($combined['date'] ?? ''),
            'customer_id' => trim($combined['customer_id'] ?? ''),
            'customer_name' => trim($combined['customer_name'] ?? ''),
            'product_id' => trim($combined['product_id'] ?? ''),
            'product_name' => trim($combined['product_name'] ?? ''),
            'category' => trim($combined['category'] ?? ''),
            'quantity' => $combined['quantity'] ?? null,
            'unit_price' => $combined['unit_price'] ?? null,
            'discount' => $combined['discount'] ?? null,
            'country' => trim($combined['country'] ?? ''),
        ];
    }

    /**
     * Devuelve un string con el motivo del error, o null si la fila es válida.
     */
    protected function validateRow(array $data): ?string
    {
        if ($data['order_id'] === '' || $data['product_id'] === '' || $data['customer_id'] === '') {
            return 'Campos obligatorios vacíos (order_id, product_id o customer_id)';
        }

        if (!is_numeric($data['quantity']) || (int) $data['quantity'] <= 0) {
            return 'Cantidad (quantity) inválida o en cero';
        }

        if (!is_numeric($data['unit_price']) || (float) $data['unit_price'] < 0) {
            return 'Precio unitario (unit_price) negativo o inválido';
        }

        if (!is_numeric($data['discount']) || (float) $data['discount'] < 0 || (float) $data['discount'] > 1) {
            return 'Descuento (discount) fuera de rango [0-1]';
        }

        try {
            Carbon::createFromFormat('Y-m-d', $data['date']);
        } catch (\Exception $e) {
            return 'Formato de fecha inválido (esperado YYYY-MM-DD)';
        }

        if ($data['category'] === '' || $data['country'] === '') {
            return 'Categoría o país vacío';
        }

        // Normalizar tipos para el insert
        $data['quantity'] = (int) $data['quantity'];
        $data['unit_price'] = (float) $data['unit_price'];
        $data['discount'] = (float) $data['discount'];

        return null;
    }
}
