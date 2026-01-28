<?php

namespace App\Http\Controllers;

use App\Models\Encounter;
use Illuminate\Http\Response;

class VisitPdfController extends Controller
{
    // B&W friendly colors
    private const BLACK = [0, 0, 0];

    private const DARK_GRAY = [51, 51, 51];

    private const GRAY = [128, 128, 128];

    private const LIGHT_GRAY = [200, 200, 200];

    public function show(Encounter $encounter): Response
    {
        $this->authorize('view', $encounter);

        if ($encounter->type !== 'visit') {
            abort(404);
        }

        $pdf = new \FPDF('P', 'mm', 'A4');
        $pdf->SetMargins(20, 15, 20);
        $pdf->AddPage();
        $pdf->SetAutoPageBreak(true, 30);

        $this->drawHeader($pdf);
        $y = $this->drawPatientInfo($pdf, $encounter, 48);
        $y = $this->drawSection($pdf, $y, __('app.labels.main_complaint'), $encounter->main_complaint);
        $y = $this->drawSection($pdf, $y, __('app.labels.diagnosis'), $encounter->diagnosis);
        $y = $this->drawSection($pdf, $y, __('app.labels.treatment'), $encounter->treatment);
        $this->drawSignature($pdf, $encounter);

        return response($pdf->Output('S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="visit-'.$encounter->id.'.pdf"',
        ]);
    }

    private function drawHeader(\FPDF $pdf): void
    {
        // Logo - large
        $logoPath = public_path('images/kavaja-logo.png');
        if (is_file($logoPath)) {
            $pdf->Image($logoPath, 15, 8, 55);
        }

        // Contact info - right side
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(...self::GRAY);
        $pdf->SetXY(130, 12);
        $pdf->Cell(60, 4, 'Tel: 038 60 60 62 | 044 466 096', 0, 1, 'R');
        $pdf->SetXY(130, 16);
        $pdf->Cell(60, 4, 'kavajahospital@gmail.com', 0, 1, 'R');
        $pdf->SetXY(130, 20);
        $pdf->Cell(60, 4, $this->encode('Magjistralja Prishtine-Ferizaj, km 5'), 0, 1, 'R');

        // Separator line
        $pdf->SetDrawColor(...self::LIGHT_GRAY);
        $pdf->SetLineWidth(0.3);
        $pdf->Line(20, 42, 190, 42);
    }

    private function drawPatientInfo(\FPDF $pdf, Encounter $encounter, float $y): float
    {
        $pdf->SetY($y);

        // Two column layout
        $leftX = 20;
        $rightX = 110;
        $lineHeight = 6;

        // Row 1
        $this->drawField($pdf, $leftX, $y, __('app.labels.patient'), $encounter->patient?->full_name ?? '—');
        $this->drawField($pdf, $rightX, $y, __('app.labels.national_id'), $encounter->patient?->national_id ?? '—');
        $y += $lineHeight;

        // Row 2
        $this->drawField($pdf, $leftX, $y, __('app.labels.year_of_birth'), $encounter->patient?->date_of_birth?->format('Y') ?? '—');
        $this->drawField($pdf, $rightX, $y, __('app.labels.insurance'), $encounter->patient?->health_insurance_number ?? '—');
        $y += $lineHeight;

        // Row 3
        $this->drawField($pdf, $leftX, $y, __('app.labels.residency'), $encounter->patient?->residency ?? '—');
        $this->drawField($pdf, $rightX, $y, __('app.labels.doctor'), $encounter->doctor_name ?? '—');
        $y += $lineHeight;

        // Row 4
        $this->drawField($pdf, $leftX, $y, __('app.labels.date'), $encounter->admission_date?->format('d.m.Y') ?? '—');
        $y += $lineHeight + 3;

        // Separator line
        $pdf->SetDrawColor(...self::LIGHT_GRAY);
        $pdf->SetLineWidth(0.3);
        $pdf->Line(20, $y, 190, $y);

        return $y + 8;
    }

    private function drawField(\FPDF $pdf, float $x, float $y, string $label, string $value): void
    {
        $pdf->SetXY($x, $y);
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(...self::GRAY);
        $pdf->Cell(30, 5, $this->encode($label.'  '), 0, 0);

        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetTextColor(...self::DARK_GRAY);
        $pdf->Cell(50, 5, $this->encode($value), 0, 0);
    }

    private function drawSection(\FPDF $pdf, float $y, string $title, ?string $content): float
    {
        if (empty($content)) {
            return $y;
        }

        // Check if we need a new page
        if ($y > 240) {
            $pdf->AddPage();
            $y = 20;
        }

        // Section title - uppercase, bold
        $pdf->SetXY(20, $y);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetTextColor(...self::BLACK);
        $pdf->Cell(0, 6, $this->encode(mb_strtoupper($title)), 0, 1);

        $y += 8;

        // Content
        $pdf->SetXY(20, $y);
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(...self::DARK_GRAY);
        $pdf->MultiCell(170, 5, $this->encode($content));

        return $pdf->GetY() + 8;
    }

    private function drawSignature(\FPDF $pdf, Encounter $encounter): void
    {
        // Disable auto page break for bottom positioning
        $pdf->SetAutoPageBreak(false);

        // Position signature at bottom of page
        $y = 260;

        // Signature area - right aligned
        $signX = 130;

        // Signature line
        $pdf->SetDrawColor(...self::DARK_GRAY);
        $pdf->SetLineWidth(0.3);
        $pdf->Line($signX, $y, 190, $y);

        // Doctor name
        $pdf->SetXY($signX, $y + 2);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetTextColor(...self::BLACK);
        $pdf->Cell(60, 5, $this->encode($encounter->doctor_name ?? '—'), 0, 1, 'C');

        // Role label
        $pdf->SetXY($signX, $y + 7);
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(...self::GRAY);
        $pdf->Cell(60, 5, $this->encode(__('app.labels.doctor')), 0, 1, 'C');
    }

    private function encode(string $text): string
    {
        // Handle Albanian characters
        $replacements = [
            'ë' => 'e',
            'Ë' => 'E',
            'ç' => 'c',
            'Ç' => 'C',
            'é' => 'e',
            'É' => 'E',
            'è' => 'e',
            'È' => 'E',
        ];

        $text = str_replace(array_keys($replacements), array_values($replacements), $text);
        $converted = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $text);

        return $converted !== false ? $converted : $text;
    }
}
