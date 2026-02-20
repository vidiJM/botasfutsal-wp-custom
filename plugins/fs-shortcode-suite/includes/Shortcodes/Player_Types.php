<?php
declare(strict_types=1);

namespace FS\ShortcodeSuite\Shortcodes;

use FS\ShortcodeSuite\Core\Assets;

if (!defined('ABSPATH')) {
    exit;
}

final class Player_Types
{
    public function __construct()
    {
        add_shortcode('fs_player_types', [$this, 'render']);
    }

    public function render(): string
    {
        Assets::enqueue_player_types();

        ob_start();
        ?>

        <section class="fs-player-types">
            <div class="fs-player-types__container">

                <header class="fs-player-types__header">
                    <h2 class="fs-player-types__title">
                        <?php echo esc_html__('Elige tu tipo de juego', 'fs-shortcode-suite'); ?>
                    </h2>
                    <p class="fs-player-types__subtitle">
                        <?php echo esc_html__('Encuentra las botas perfectas según tu estilo en pista.', 'fs-shortcode-suite'); ?>
                    </p>
                </header>

                <div class="fs-player-types__grid">

                    <?php
                    echo $this->card(
                        496413,
                        home_url('/zapatillas/velocidad/'));

                    echo $this->card(
                        496417,
                        home_url('/zapatillas/control/'));

                    echo $this->card(
                        496411,
                        home_url('/zapatillas/resistencia/'));

                    echo $this->card(
                        496412,
                        home_url('/zapatillas/calidad-precio/'));
                    ?>

                </div>

            </div>
        </section>

        <?php
        return (string) ob_get_clean();
    }

    private function card(
        int $image_id,
        string $url,

    ): string {

        $image_html = wp_get_attachment_image(
            $image_id,
            'large',
            false,
            [
                'class'    => 'fs-player-types__image',
                'loading'  => 'lazy',
                'decoding' => 'async'
            ]
        );

        if (!$image_html) {
            return '';
        }

        ob_start();
        ?>

        <article class="fs-player-types__card">
            <a href="<?php echo esc_url($url); ?>" class="fs-player-types__link">

                <div class="fs-player-types__media">
                    <?php echo $image_html; ?>
                </div>
            </a>
        </article>

        <?php
        return (string) ob_get_clean();
    }
}
