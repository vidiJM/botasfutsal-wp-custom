<?php
declare(strict_types=1);

namespace FS\ShortcodeSuite\REST;

use FS\ShortcodeSuite\Query\ProductQuery;

defined('ABSPATH') || exit;

class Product_Controller
{
    public function register_routes(): void
    {
        register_rest_route('fs/v1', '/products', [
            'methods'  => 'GET',
            'callback' => [$this, 'get_products'],
            'permission_callback' => '__return_true',
        ]);
    }

    public static function get_products($request)
    {
        $params = $request->get_params();

        $result = ProductQuery::get_products([
            'genero'     => $params['genero'] ?? '',
            'superficie' => $params['superficie'] ?? '',
            'marca'      => $params['marca'] ?? '',
            'color'      => $params['color'] ?? '',
            'talla'      => $params['talla'] ?? '',
            'precio_max' => isset($params['precio_max']) ? (float)$params['precio_max'] : 0,
            'per_page'   => 12,
            'paged'      => 1,
        ]);

        ob_start();

        $product_ids = $result['ids'] ?? [];
        $images      = $result['images'] ?? [];
        $prices      = $result['prices'] ?? [];

        if (!empty($product_ids)) {

            echo '<div class="fs-grid">';

            foreach ($product_ids as $product_id) {

                $title = get_the_title($product_id);
                $link  = get_permalink($product_id);
                $image = $images[$product_id] ?? '';
                $price = $prices[$product_id] ?? '';

                ?>
                <article class="fs-card">
                    <a href="<?php echo esc_url($link); ?>">
                        <div class="fs-card__image">
                            <?php if ($image): ?>
                                <img src="<?php echo esc_url($image); ?>"
                                     alt="<?php echo esc_attr($title); ?>">
                            <?php endif; ?>
                        </div>

                        <div class="fs-card__body">
                            <?php if ($price): ?>
                                <div class="fs-card__price">
                                    € <?php echo number_format((float)$price, 0, ",", "."); ?>
                                </div>
                            <?php endif; ?>

                            <div class="fs-card__title">
                                <?php echo esc_html($title); ?>
                            </div>
                        </div>
                    </a>
                </article>
                <?php
            }

            echo '</div>';

        } else {
            echo '<p>No se encontraron productos.</p>';
        }

        return [
          'html'   => ob_get_clean(),
          'total'  => (int) ($result['total'] ?? 0),
          'facets' => (array) ($result['facets'] ?? []),
        ];
    }
}