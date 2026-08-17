<?php

namespace App\Http\Controllers;

use App\Models\Import;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    // GET /api/reports/summary?import_id=X
    public function summary(Request $request)
    {
        $request->validate([
            'import_id' => ['required', 'integer', 'exists:imports,id'],
        ]);

        $importId = (int) $request->query('import_id');

        $data = Cache::remember("report_summary_{$importId}", 600, function () use ($importId) {
            $totalRevenue = Sale::where('import_id', $importId)->sum('total');

            $topProducts = Sale::where('import_id', $importId)
                ->select('product_id', 'product_name', DB::raw('SUM(total) as revenue'))
                ->groupBy('product_id', 'product_name')
                ->orderByDesc('revenue')
                ->limit(5)
                ->get();

            $byCategory = Sale::where('import_id', $importId)
                ->select('category', DB::raw('SUM(total) as revenue'))
                ->groupBy('category')
                ->orderByDesc('revenue')
                ->get();

            $byCountry = Sale::where('import_id', $importId)
                ->select('country', DB::raw('SUM(total) as revenue'))
                ->groupBy('country')
                ->orderByDesc('revenue')
                ->get();

            return [
                'total_revenue' => round($totalRevenue, 2),
                'top_products' => $topProducts,
                'by_category' => $byCategory,
                'by_country' => $byCountry,
            ];
        });

        return response()->json($data);
    }

    // Vista Blade de detalle
    public function detailView(int $id)
    {
        $import = Import::findOrFail($id);

        return view('imports.detail', compact('import'));
    }
}
