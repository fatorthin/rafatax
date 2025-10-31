<?php

namespace App\Http\Controllers;

use App\Models\PayrollBonusDetail;
use App\Models\CaseProjectDetail;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PayrollBonusExportController extends Controller
{
    public function downloadSlip($detailId)
    {
        $detail = PayrollBonusDetail::with(['payrollBonus', 'staff'])->findOrFail($detailId);

        // Get case_project_detail_ids (sudah di-cast sebagai array di model)
        $caseProjectDetailIds = $detail->case_project_detail_ids ?? [];

        // Ambil detail case project
        $caseProjectDetails = CaseProjectDetail::with(['caseProject', 'caseProject.client'])
            ->whereIn('id', $caseProjectDetailIds)
            ->get();

        $data = [
            'detail' => $detail,
            'caseProjectDetails' => $caseProjectDetails,
        ];

        $pdf = Pdf::loadView('exports.payroll-bonus-slip', $data);

        $filename = 'Slip_Bonus_' . str_replace(' ', '_', $detail->staff->name) . '_' . $detail->payrollBonus->description . '.pdf';

        return $pdf->download($filename);
    }
}
