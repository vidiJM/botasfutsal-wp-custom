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
                'args' => [
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
        $query = (string) $request->get_param('q');
        $query = trim($query);
    
        $filters = [
            'marca'      => $request->get_param('marca'),
            'superficie' => $request->get_param('superficie'),
            'genero'     => $request->get_param('genero'),
            'talla'      => $request->get_param('talla'),
            'color'      => $request->get_param('color'),
            'orderby'    => $request->get_param('orderby'),
        ];
    
        $filters = array_filter($filters);
    
        // 👇 Ahora permitimos query vacío
        $results = $this->service->search($query ?? '', $filters);
    
        return new WP_REST_Response($results, 200);
    }

}
