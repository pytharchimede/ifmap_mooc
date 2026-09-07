<?php
namespace App\Services;

use App\Core\Database;

final class OfficialPdfBridge
{
    public static function download(array $payload): never
    {
        $type = (string)($payload['type'] ?? '');
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];
        $brand = self::brand();

        if ($type === 'certificate') {
            OfficialPdfExporterV2::certificate($data, $brand, !empty($payload['specimen']));
        }
        if ($type === 'attestation') {
            OfficialPdfExporterV2::attestation($data, $brand);
        }

        throw new \RuntimeException('Type de document officiel non pris en charge par TCPDF.');
    }

    private static function brand(): array
    {
        $brand = [
            'name' => 'IFMAP Learning',
            'primary' => '#5547e8',
            'accent' => '#f59e0b',
            'logo' => null,
            'signature' => null,
        ];
        try {
            foreach (Database::connection()->query("SELECT `key`,`value` FROM settings WHERE `group`='branding'") as $row) {
                if (array_key_exists($row['key'], $brand)) {
                    $brand[$row['key']] = $row['value'];
                }
            }
        } catch (\Throwable $e) {
            error_log('TCPDF branding: ' . $e->getMessage());
        }
        return $brand;
    }
}
