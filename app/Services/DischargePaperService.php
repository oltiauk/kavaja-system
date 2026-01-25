<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use setasign\Fpdi\Fpdi;

class DischargePaperService
{
    public function addQrCode(string $inputPath, string $outputPath, string $qrImage): void
    {
        $extension = strtolower(pathinfo($inputPath, PATHINFO_EXTENSION));

        if ($extension === 'pdf') {
            $this->addQrToPdf($inputPath, $outputPath, $qrImage);

            return;
        }

        if (in_array($extension, ['doc', 'docx'], true)) {
            $this->addQrToWord($inputPath, $outputPath, $qrImage);
        }
    }

    private function addQrToPdf(string $inputPath, string $outputPath, string $qrImage): void
    {
        $pdf = new Fpdi;
        $pageCount = $pdf->setSourceFile(storage_path("app/{$inputPath}"));

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templateId = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($templateId);

            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId);

            if ($pageNo === 1) {
                $tempQr = $this->storeTempQr($qrImage);
                $qrSize = 25;
                $x = $size['width'] - $qrSize - 10;
                $y = 10;

                $pdf->Image($tempQr, $x, $y, $qrSize, $qrSize);
                @unlink($tempQr);
            }
        }

        Storage::makeDirectory(dirname($outputPath));
        $pdf->Output('F', storage_path("app/{$outputPath}"));
    }

    private function addQrToWord(string $inputPath, string $outputPath, string $qrImage): void
    {
        $phpWord = WordIOFactory::load(storage_path("app/{$inputPath}"));
        $tempQr = $this->storeTempQr($qrImage);
        $sections = $phpWord->getSections();

        if (isset($sections[0])) {
            $header = $sections[0]->addHeader();
            $header->addImage($tempQr, [
                'width' => 70,
                'height' => 70,
                'positioning' => 'absolute',
                'posHorizontal' => 'right',
                'posVertical' => 'top',
            ]);
        }

        Storage::makeDirectory(dirname($outputPath));

        $writer = WordIOFactory::createWriter($phpWord, 'Word2007');
        $writer->save(storage_path("app/{$outputPath}"));

        @unlink($tempQr);
    }

    private function storeTempQr(string $qrImage): string
    {
        $tempQr = tempnam(sys_get_temp_dir(), 'qr-').'.png';
        file_put_contents($tempQr, $qrImage);

        return $tempQr;
    }
}
