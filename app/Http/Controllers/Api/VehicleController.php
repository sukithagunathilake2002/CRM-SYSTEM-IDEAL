<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VehicleController extends Controller
{
    public function getModels()
    {
        try {
            $models = Vehicle::query()
                ->select(DB::raw('DISTINCT TRIM(model) as model'))
                ->whereNotNull('model')
                ->where(DB::raw('TRIM(model)'), '!=', '')
                ->orderBy('model')
                ->pluck('model')
                ->map(fn($item) => trim($item))
                ->filter()
                ->values();
            
            return response()->json($models);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getEngines($model)
    {
        try {
            $trimmedModel = trim($model);
            
            $engines = Vehicle::where(DB::raw('TRIM(model)'), $trimmedModel)
                ->select(DB::raw('DISTINCT TRIM(engine_type) as engine_type'))
                ->whereNotNull('engine_type')
                ->where(DB::raw('TRIM(engine_type)'), '!=', '')
                ->orderBy('engine_type')
                ->pluck('engine_type')
                ->map(fn($item) => trim($item))
                ->filter()
                ->values();
            
            // Force unique values
            $uniqueEngines = $engines->unique()->values();
            
            return response()->json($uniqueEngines);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getVariants($model, $engine)
    {
        try {
            $trimmedModel = trim($model);
            $trimmedEngine = trim($engine);
            
            $variants = Vehicle::where(DB::raw('TRIM(model)'), $trimmedModel)
                ->where(DB::raw('TRIM(engine_type)'), $trimmedEngine)
                ->select(DB::raw('DISTINCT TRIM(variant) as variant'))
                ->whereNotNull('variant')
                ->where(DB::raw('TRIM(variant)'), '!=', '')
                ->orderBy('variant')
                ->pluck('variant')
                ->map(fn($item) => trim($item))
                ->filter()
                ->values();
            
            // Force unique values
            $uniqueVariants = $variants->unique()->values();
            
            return response()->json($uniqueVariants);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getAllVehicles()
    {
        try {
            $vehicles = Vehicle::query()
                ->orderBy('model')
                ->orderBy('engine_type')
                ->orderBy('variant')
                ->get(['id', 'model', 'engine_type', 'variant', 'unit_price', 'vat_amount']);
            
            return response()->json($vehicles);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}