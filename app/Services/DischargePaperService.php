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
        $fullPath = Storage::disk('local')->path($inputPath);

        // Check using Storage first (more reliable)
        if (! Storage::disk('local')->exists($inputPath)) {
            throw new \RuntimeException("PDF file not found in Storage at: {$inputPath}");
        }

        // Also check filesystem directly
        if (! file_exists($fullPath)) {
            throw new \RuntimeException("PDF file not found on filesystem at: {$fullPath} (Storage path: {$inputPath})");
        }

        // Verify file is readable
        if (! is_readable($fullPath)) {
            throw new \RuntimeException("PDF file exists but is not readable at: {$fullPath}");
        }

        $pdf = new Fpdi;
        $pageCount = $pdf->setSourceFile($fullPath);

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

        Storage::disk('local')->makeDirectory(dirname($outputPath));
        $pdf->Output('F', Storage::disk('local')->path($outputPath));
    }

    private function addQrToWord(string $inputPath, string $outputPath, string $qrImage): void
    {
        $fullPath = Storage::disk('local')->path($inputPath);
        $fullOutputPath = Storage::disk('local')->path($outputPath);

        // Check using Storage first (more reliable)
        if (! Storage::disk('local')->exists($inputPath)) {
            throw new \RuntimeException("Word file not found in Storage at: {$inputPath}");
        }

        // Also check filesystem directly
        if (! file_exists($fullPath)) {
            throw new \RuntimeException("Word file not found on filesystem at: {$fullPath} (Storage path: {$inputPath})");
        }

        // Verify file is readable
        if (! is_readable($fullPath)) {
            throw new \RuntimeException("Word file exists but is not readable at: {$fullPath}");
        }

        $tempQr = null;

        try {
            // Load the Word document
            $phpWord = WordIOFactory::load($fullPath);
            $tempQr = $this->storeTempQr($qrImage);
            $sections = $phpWord->getSections();

            if (empty($sections)) {
                throw new \RuntimeException('Word document has no sections');
            }

            // Add QR code to header of first section
            $header = $sections[0]->addHeader();

            // Verify temp QR file exists and is readable
            if (! file_exists($tempQr) || ! is_readable($tempQr)) {
                throw new \RuntimeException("Temporary QR image file not accessible at: {$tempQr}");
            }

            // Add image directly to header
            // PhpWord's addImage method accepts width, height, and optional positioning
            $header->addImage($tempQr, [
                'width' => 70,
                'height' => 70,
            ]);

            // Ensure output directory exists
            Storage::disk('local')->makeDirectory(dirname($outputPath));

            // Save the modified document
            $writer = WordIOFactory::createWriter($phpWord, 'Word2007');
            $writer->save($fullOutputPath);

            // Verify output file was created
            if (! file_exists($fullOutputPath)) {
                throw new \RuntimeException("Failed to save Word document with QR code at: {$fullOutputPath}");
            }

            if (! Storage::disk('local')->exists($outputPath)) {
                throw new \RuntimeException("Output file not found in Storage at: {$outputPath} (full path: {$fullOutputPath})");
            }
        } catch (\PhpOffice\PhpWord\Exception\Exception $e) {
            throw new \RuntimeException("PhpWord error: {$e->getMessage()}", 0, $e);
        } catch (\Exception $e) {
            throw new \RuntimeException("Error processing Word document: {$e->getMessage()}", 0, $e);
        } finally {
            // Clean up temp QR file
            if ($tempQr && file_exists($tempQr)) {
                @unlink($tempQr);
            }
        }
    }

    private function storeTempQr(string $qrImage): string
    {
        $tempQr = tempnam(sys_get_temp_dir(), 'qr-').'.png';
        file_put_contents($tempQr, $qrImage);

        return $tempQr;
    }
}
