<?php

namespace App\Http\Controllers;

use App\Models\Area;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AreaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'governorate_id' => ['required', 'integer', 'exists:governorates,id'],
        ]);

        $areas = Area::query()
            ->where('governorate_id', $request->integer('governorate_id'))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'name_ar']);

        return response()->json($areas);
    }
}
