<?php

namespace App\Http\Controllers;

use App\Services\WooCommerceImportService;

class ImportController extends Controller
{
    public function runImport($categoryId, $teamId)
    {
        $service = new WooCommerceImportService();
        
        try {
            $count = $service->importByCategory($categoryId, $teamId);
            return response()->json([
                'status' => 'success',
                'message' => "Se importaron {$count} productos correctamente."
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}