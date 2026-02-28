<?php
declare(strict_types=1);

namespace FS\ShortcodeSuite\REST;

use FS\ShortcodeSuite\Data\Services\Search_Service;
use WP_REST_Request;
use WP_REST_Response;

defined('ABSPATH') || exit;

final class Search_Controller
{
    private Search_Service $service;

    public function __construct(Search_Service $service)
    {
        $this->service = $service;
    }

    public function register_routes(): void
    {
        register_rest_route(
            'fs/v1',
            '/search',
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'handle'],
                'permission_callback' => '__return_true',
                'args'                => [
                    'q' => [
                        'required'          => false,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                        'default'           => '',
                    ],
                    'marca' => [
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_title',
                    ],
                    'superficie' => [
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_title',
                    ],
                    'genero' => [
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_title',
                    ],
                    'talla' => [
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_title',
                    ],
                    'color' => [
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_title',
                    ],
                    'price_min' => [
                        'type'              => 'number',
                        'sanitize_callback' => static fn($v) => is_numeric($v) ? (float) $v : null,
                    ],
                    'price_max' => [
                        'type'              => 'number',
                        'sanitize_callback' => static fn($v) => is_numeric($v) ? (float) $v : null,
                    ],
                    'orderby' => [
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_key',
                    ],
                ],
            ]
        );
    }

public function handle(WP_REST_Request $request): WP_REST_Response
{
    try {

        $q = (string) $request->get_param('q');

        $filters = [
            'marca'      => $request->get_param('marca'),
            'superficie' => $request->get_param('superficie'),
            'genero'     => $request->get_param('genero'),
            'talla'      => $request->get_param('talla'),
            'color'      => $request->get_param('color'),
            'price_min'  => $request->get_param('price_min'),
            'price_max'  => $request->get_param('price_max'),
        ];

        $filters = array_filter(
            $filters,
            static fn($v) => $v !== null && $v !== ''
        );

        // 🔥 Siempre usar search()
        $result = $this->service->search($q, $filters);
        error_log(print_r($result, true));
        return new WP_REST_Response($result, 200);

    } catch (\Throwable $e) {

        error_log('[Search_Controller] ' . $e->getMessage());

        return new WP_REST_Response([
            'error'   => 'search_error',
            'message' => $e->getMessage(),
        ], 500);
    }
}
}