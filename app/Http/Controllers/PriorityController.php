<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Services\PriorityService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PriorityController extends Controller
{
    public function index(Request $request, PriorityService $priority): View
    {
        $countryId = $request->integer('country') ?: null;
        $rows = $priority->rank($countryId);
        $evidence = $rows->where('gap_score', '>', 0)->values();

        // Simulasi alokasi anggaran: greedy berdasarkan skor efektif
        $budget = max(0, (float) $request->query('budget', 1500000));
        $remaining = $budget;
        $allocation = collect();
        foreach ($evidence->where('handled', false) as $row) {
            if ($row['estimated_cost'] <= $remaining) {
                $allocation->push($row);
                $remaining -= $row['estimated_cost'];
            }
        }

        return view('priorities.index', [
            'rows' => $evidence,
            'noEvidence' => $rows->where('gap_score', 0)->count(),
            'countries' => Country::orderBy('name')->get(),
            'countryId' => $countryId,
            'budget' => $budget,
            'allocation' => $allocation,
            'remaining' => $remaining,
        ]);
    }
}
