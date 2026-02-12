<?php
declare(strict_types=1);

namespace FS\ImporterCore\Normalizer;

final class SizeNormalizer
{
    public static function normalize($raw): ?float
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

        // 42 2/3 o 42-2/3
        if (preg_match('~^(\d+)[\s\-]+(\d)/(\d)$~', $value, $m)) {
            $base = (float) $m[1];
            $num  = (float) $m[2];
            $den  = (float) $m[3];

            if ($den > 0) {
                return round($base + ($num / $den), 2);
            }
        }

        // 44 1/2
        if (preg_match('~^(\d+)[\s\-]+1/2$~', $value, $m)) {
            return (float)$m[1] + 0.5;
        }

        // UK conversion
        if (str_contains($value, 'uk')) {
            $num = (float) preg_replace('~[^0-9\.]~', '', $value);

            if ($num > 0) {
                return self::ukToEu($num);
            }
        }

        // US conversion
        if (str_contains($value, 'us')) {
            $num = (float) preg_replace('~[^0-9\.]~', '', $value);

            if ($num > 0) {
                return self::usToEu($num);
            }
        }

        // Decimal o entero simple
        if (preg_match('~^\d+(\.\d+)?$~', $value)) {
            return (float) $value;
        }

        return null;
    }

    private static function ukToEu(float $uk): float
    {
        // Tabla aproximada adulto masculino
        return round($uk + 33.5, 2);
    }

    private static function usToEu(float $us): float
    {
        // Tabla aproximada adulto masculino
        return round($us + 33, 2);
    }
}
