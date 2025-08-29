<?php


namespace App\Services;
use Illuminate\Support\Facades\Http;


class LegacyClient
{

    protected static function http()
    {
        return Http::baseUrl(rtrim(env('LEGACY_INTERNAL_URL'), '/'))
            ->timeout(30);
    }

    public static function pdf(int $visitId)
    {
        return self::http()
            ->accept('application/pdf')
            ->get("/visit/{visitId}/export_to_pdf");
    }
}
