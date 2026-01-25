<?php

namespace App\Services;

use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrCodeService
{
    public function generate(string $url): string
    {
        return QrCode::format('png')
            ->size(150)
            ->margin(1)
            ->generate($url);
    }
}
