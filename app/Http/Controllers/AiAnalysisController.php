<?php

namespace App\Http\Controllers;

use App\Models\AiAnalysis;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class AiAnalysisController extends Controller
{
    public function downloadPdf(AiAnalysis $analysis)
    {
        $analysis->load('design.project');

        $pdf = Pdf::loadView('reports.analysis-report', [
            'analysis' => $analysis,
            'design' => $analysis->design,
            'results' => $analysis->results,
        ]);

        return $pdf->download("analysis-report-{$analysis->id}.pdf");
    }
}
