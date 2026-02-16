<?php
declare(strict_types=1);

namespace FS\ImporterCore\Normalizer;

final class SizeNormalizer
{
    public static function normalize(mixed $raw): ?string
    {
        if (!$raw) {
            return null;
        }

        $value = strtolower(trim((string) $raw));

        // Limpieza básica
        $value = str_replace(['eu', 'size'], '', $value);
        $value = trim($value);

        // Soporte unicode fracciones (½, ⅓, ⅔)
        $value = str_replace(['½', '⅓', '⅔'], [' 1/2', ' 1/3', ' 2/3'], $value);

        $eu = null;

        // 42 2/3 o 42-2/3
        if (preg_match('~^(\d+)[\s\-]+(\d)/(\d)$~', $value, $m)) {

            $base = (float) $m[1];
            $num  = (float) $m[2];
            $den  = (float) $m[3];

            if ($den > 0) {
                $eu = round($base + ($num / $den), 2);
            }
        }

        // 44 1/2
        elseif (preg_match('~^(\d+)[\s\-]+1/2$~', $value, $m)) {
            $eu = (float)$m[1] + 0.5;
        }

        // UK conversion
        elseif (str_contains($value, 'uk')) {
            $num = (float) preg_replace('~[^0-9\.]~', '', $value);
            if ($num > 0) {
                $eu = self::ukToEu($num);
            }
        }

        // US conversion
        elseif (str_contains($value, 'us')) {
            $num = (float) preg_replace('~[^0-9\.]~', '', $value);
            if ($num > 0) {
                $eu = self::usToEu($num);
            }
        }

        // Decimal o entero simple
        elseif (preg_match('~^\d+(\.\d+)?$~', $value)) {
            $eu = (float) $value;
        }

        if ($eu === null) {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | Normalización final como STRING
        |--------------------------------------------------------------------------
        | Evitamos 41.0 → "41"
        | Mantenemos 42.5 → "42.5"
        */

       if (floor($eu) === $eu) {
            return (string) (int) $eu;
        }

        return rtrim(rtrim((string)$eu, '0'), '.');
    }

    private static function ukToEu(float $uk): float
    {
        return round($uk + 33.5, 2);
    }

    private static function usToEu(float $us): float
    {
        return round($us + 33, 2);
    }
}
