<?php
declare(strict_types=1);

namespace FS\ShortcodeSuite\REST;

use FS\ShortcodeSuite\Data\Services\Search_Service;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

defined('ABSPATH') || exit;

final class Selector_Wizard_Controller
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
            '/wizard',
            [
                'methods'             => 'POST',
                'callback'            => [$this, 'handleWizard'],
                'permission_callback' => '__return_true',
                'args'                => [
                    'gender'  => ['type' => 'string', 'required' => false],
                    'surface' => ['type' => 'string', 'required' => false],
                    'closure' => ['type' => 'string', 'required' => false],
                    'budget'  => ['type' => 'string', 'required' => false],
                ],
            ]
        );
    }

    public function handleWizard(WP_REST_Request $request): WP_REST_Response
    {
        try {
            $params = (array) $request->get_json_params();

            if (empty($params)) {
                return new WP_REST_Response([], 200);
            }

            $filters = [
                'gender'  => isset($params['gender'])  ? sanitize_text_field((string) $params['gender'])  : null,
                'surface' => isset($params['surface']) ? sanitize_text_field((string) $params['surface']) : null,
                'closure' => isset($params['closure']) ? sanitize_text_field((string) $params['closure']) : null,
                'budget'  => isset($params['budget'])  ? sanitize_text_field((string) $params['budget'])  : null,
            ];

            $filters = array_filter(
                $filters,
                static fn($value) => $value !== null && $value !== ''
            );

            if (empty($filters)) {
                return new WP_REST_Response([], 200);
            }

            $products = $this->service->wizard_search($filters);

            return new WP_REST_Response(is_array($products) ? $products : [], 200);

        } catch (\Throwable $e) {
            error_log('[Selector_Wizard_Controller] ' . $e->getMessage());

            return new WP_REST_Response(
                new WP_Error(
                    'wizard_error',
                    'Error processing wizard request.',
                    ['status' => 500]
                ),
                500
            );
        }
    }
}