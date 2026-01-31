<?php

namespace App\Services;

use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrCodeService
{
    public function generate(string $url): string
    {
        $previousLevel = error_reporting(E_ALL & ~E_DEPRECATED);

        try {
            return QrCode::format('png')
                ->size(150)
                ->margin(1)
                ->generate($url);
        } finally {
            error_reporting($previousLevel);
        }
    }
}
