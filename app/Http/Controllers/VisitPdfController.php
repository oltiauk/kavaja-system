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

    private const LEFT_MARGIN = 20;

    private const RIGHT_EDGE = 190;

    private const CONTENT_WIDTH = 170;

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
        $y = $this->drawSection($pdf, $y, __('app.labels.clinical_examination'), $encounter->clinical_examination);
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
        $pdf->Line(self::LEFT_MARGIN, 42, self::RIGHT_EDGE, 42);
    }

    private function drawPatientInfo(\FPDF $pdf, Encounter $encounter, float $y): float
    {
        $pdf->SetY($y);

        $patient = $encounter->patient;
        $leftX = self::LEFT_MARGIN;
        $rightX = 110;

        $lineHeight = 6;

        // Collect fields — only include filled values
        $leftFields = array_filter([
            $patient?->full_name ? [__('app.labels.patient'), $patient->full_name] : null,
            $patient?->date_of_birth ? [__('app.labels.year_of_birth'), $patient->date_of_birth->format('Y')] : null,
            $patient?->residency ? [__('app.labels.residency'), $patient->residency] : null,
            $encounter->admission_date ? [__('app.labels.date'), $encounter->admission_date->format('d.m.Y')] : null,
        ]);

        $rightFields = array_filter([
            $patient?->national_id ? [__('app.labels.national_id'), $patient->national_id] : null,
            $patient?->health_insurance_number ? [__('app.labels.insurance'), $patient->health_insurance_number] : null,
            $encounter->doctor_name ? [__('app.labels.doctor'), $encounter->doctor_name] : null,
        ]);

        $leftFields = array_values($leftFields);
        $rightFields = array_values($rightFields);
        $maxRows = max(count($leftFields), count($rightFields));

        for ($i = 0; $i < $maxRows; $i++) {
            if (isset($leftFields[$i])) {
                $this->drawField($pdf, $leftX, $y, $leftFields[$i][0], $leftFields[$i][1]);
            }
            if (isset($rightFields[$i])) {
                $this->drawField($pdf, $rightX, $y, $rightFields[$i][0], $rightFields[$i][1]);
            }
            $y += $lineHeight;
        }

        $y += 3;

        // Separator line
        $pdf->SetDrawColor(...self::LIGHT_GRAY);
        $pdf->SetLineWidth(0.3);
        $pdf->Line(self::LEFT_MARGIN, $y, self::RIGHT_EDGE, $y);

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

        // Section title - uppercase, bold, centered
        $pdf->SetXY(self::LEFT_MARGIN, $y);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetTextColor(...self::BLACK);
        $pdf->Cell(self::CONTENT_WIDTH, 6, $this->encode(mb_strtoupper($title)), 0, 1, 'C');

        $y += 8;

        // Content - centered
        $pdf->SetXY(self::LEFT_MARGIN, $y);
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(...self::DARK_GRAY);
        $pdf->MultiCell(self::CONTENT_WIDTH, 5, $this->encode($content), 0, 'C');

        return $pdf->GetY() + 8;
    }

    private function drawSignature(\FPDF $pdf, Encounter $encounter): void
    {
        // Disable auto page break for bottom positioning
        $pdf->SetAutoPageBreak(false);

        // Position signature at bottom of page
        $y = 260;

        // Signature area - right aligned
        $signWidth = 60;
        $signX = self::RIGHT_EDGE - $signWidth;

        // Signature line
        $pdf->SetDrawColor(...self::DARK_GRAY);
        $pdf->SetLineWidth(0.3);
        $pdf->Line($signX, $y, self::RIGHT_EDGE, $y);

        // Doctor name
        $pdf->SetXY($signX, $y + 2);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetTextColor(...self::BLACK);
        $pdf->Cell($signWidth, 5, $this->encode($encounter->doctor_name ?? '—'), 0, 1, 'C');

        // Role label
        $pdf->SetXY($signX, $y + 7);
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(...self::GRAY);
        $pdf->Cell($signWidth, 5, $this->encode(__('app.labels.doctor')), 0, 1, 'C');
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
