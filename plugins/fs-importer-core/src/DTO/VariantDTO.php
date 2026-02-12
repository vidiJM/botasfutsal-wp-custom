<?php
namespace FS\ImporterCore\DTO;

/**
 * DTO de variante (SKU)
 */
final class VariantDTO
{
    public string $variantId;
    public string $color = 'SIN_COLOR';

    public ?string $imageMain = null;
    public array $images = [];

    /** @var OfferDTO[] */
    public array $offers = [];

    public ?string $surface = null;

    // 🔥 NECESARIOS PARA RELACIONES
    public ?string $productId = null;
    /** @var string[] lista de géneros válidos (hombre, mujer, infantil) */
    public array $genderRaw = [];
    public ?string $gtin = null;
    public ?string $colorRaw = null;

    /**
     * Devuelve la clave de color normalizada (mayúsculas, sin espacios extremos).
     */
    public function getNormalizedColorKey(): string
    {
        return strtoupper(trim($this->color));
    }
}