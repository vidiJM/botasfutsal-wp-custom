<?php
declare(strict_types=1);

namespace FS\ImporterCore\Signature;

use FS\ImporterCore\Normalizer\TextNormalizer;

defined('ABSPATH') || exit;

/**
 * VariantSignature
 *
 * Arquitectura:
 * - Variante = Color (no talla)
 * - Oferta   = Talla + Merchant + Precio
 *
 * Nota:
 * - GTIN suele identificar SKU (a menudo talla específica). Si lo usas aquí,
 *   fragmentas variantes y rompes el modelo (variante por talla).
 */
final class VariantSignature
{
    public static function make(
        string $productId,
        ?string $gtin,
        string $colorBase
    ): string {
        $pid   = strtolower(trim($productId));
        $color = strtolower(trim(TextNormalizer::normalize($colorBase)));

        // Firma estable solo por producto + color (modelo correcto)
        return sha1($pid . '|' . $color);
    }
}
