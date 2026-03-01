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

        <section class="fs-player-types" aria-labelledby="fs-player-types-title">
            <div class="fs-player-types__container">

                <!-- ================= HEADER ================= -->

                <header class="fs-player-types__header">
                    <h2 
                        id="fs-player-types-title"
                        class="fs-player-types__title"
                    >
                        <?php echo esc_html__('Elige tu tipo de juego', 'fs-shortcode-suite'); ?>
                    </h2>

                    <p class="fs-player-types__subtitle">
                        <?php echo esc_html__('Encuentra las botas perfectas según tu estilo en pista.', 'fs-shortcode-suite'); ?>
                    </p>
                </header>

                <!-- ================= GRID ================= -->

                <div class="fs-player-types__grid">

                    <?php
                    echo $this->card(
                        496413,
                        home_url('/zapatillas-futbol-sala-para/resistencia/'),
                        false // primera imagen eager
                    );

                    echo $this->card(
                        496417,
                        home_url('/zapatillas-futbol-sala-para/calidad-precio/')
                    );

                    echo $this->card(
                        496411,
                        home_url('/zapatillas-futbol-sala-para/velocidad/')
                    );

                    echo $this->card(
                        496412,
                        home_url('/zapatillas-futbol-sala-para/control/')
                    );
                    ?>

                </div>

            </div>
        </section>

        <?php
        return (string) ob_get_clean();
    }

    /**
     * Card generator
     */
    private function card(
        int $image_id,
        string $url,
        bool $lazy = true
    ): string {

        // Primera imagen above-the-fold no debe ir lazy
        $loading  = $lazy ? 'lazy' : 'eager';
        $fetch    = $lazy ? 'auto' : 'high';

        $image_html = wp_get_attachment_image(
            $image_id,
            'large',
            false,
            [
                'class'         => 'fs-player-types__image',
                'loading'       => $loading,
                'decoding'      => 'async',
                'fetchpriority' => $fetch,
            ]
        );

        if (!$image_html) {
            return '';
        }

        ob_start();
        ?>

        <article class="fs-player-types__card">
            <a 
                href="<?php echo esc_url($url); ?>" 
                class="fs-player-types__link"
            >
                <div class="fs-player-types__media">
                    <?php echo $image_html; ?>
                </div>
            </a>
        </article>

        <?php
        return (string) ob_get_clean();
    }
}