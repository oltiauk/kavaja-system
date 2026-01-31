<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Diagnosis;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DiagnosisController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $query = $request->get('q', '');
        $limit = min((int) $request->get('limit', 8), 20);

        if (strlen(trim($query)) < 2) {
            return response()->json(['suggestions' => []]);
        }

        $suggestions = Diagnosis::search($query)
            ->limit($limit)
            ->pluck('name')
            ->toArray();

        return response()->json(['suggestions' => $suggestions]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $diagnosis = Diagnosis::findOrCreateByName($request->input('name'));

        return response()->json([
            'name' => $diagnosis->name,
            'created' => $diagnosis->wasRecentlyCreated,
        ]);
    }
}
