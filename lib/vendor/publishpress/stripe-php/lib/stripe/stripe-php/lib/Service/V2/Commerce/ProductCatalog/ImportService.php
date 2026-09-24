<?php

// File generated from our OpenAPI spec

namespace PublishPress\Stripe\Service\V2\Commerce\ProductCatalog;

/**
 * @phpstan-import-type RequestOptionsArray from \PublishPress\Stripe\Util\RequestOptions
 *
 * @psalm-import-type RequestOptionsArray from \PublishPress\Stripe\Util\RequestOptions
 */
class ImportService extends \PublishPress\Stripe\Service\AbstractService
{
    /**
     * Returns a list of ProductCatalogImport objects.
     *
     * @param null|array{created?: string, created_gt?: string, created_gte?: string, created_lt?: string, created_lte?: string, feed_type?: string, limit?: int, status?: string} $params
     * @param null|RequestOptionsArray|\PublishPress\Stripe\Util\RequestOptions $opts
     *
     * @return \PublishPress\Stripe\V2\Collection<\PublishPress\Stripe\V2\Commerce\ProductCatalogImport>
     *
     * @throws \PublishPress\Stripe\Exception\ApiErrorException if the request fails
     */
    public function all($params = null, $opts = null)
    {
        return $this->requestCollection('get', '/v2/commerce/product_catalog/imports', $params, $opts, [
            'response_schema' => [
                'kind' => 'object',
                'fields' => [
                    'data' => [
                        'kind' => 'array',
                        'element' => [
                            'kind' => 'object',
                            'fields' => [
                                'status_details' => [
                                    'kind' => 'object',
                                    'fields' => [
                                        'processing' => [
                                            'kind' => 'object',
                                            'fields' => [
                                                'error_count' => [
                                                    'kind' => 'int64_string',
                                                ],
                                                'success_count' => [
                                                    'kind' => 'int64_string',
                                                ],
                                            ],
                                        ],
                                        'succeeded' => [
                                            'kind' => 'object',
                                            'fields' => [
                                                'success_count' => [
                                                    'kind' => 'int64_string',
                                                ],
                                            ],
                                        ],
                                        'succeeded_with_errors' => [
                                            'kind' => 'object',
                                            'fields' => [
                                                'error_count' => [
                                                    'kind' => 'int64_string',
                                                ],
                                                'error_file' => [
                                                    'kind' => 'object',
                                                    'fields' => [
                                                        'size' => [
                                                            'kind' => 'int64_string',
                                                        ],
                                                    ],
                                                ],
                                                'samples' => [
                                                    'kind' => 'array',
                                                    'element' => [
                                                        'kind' => 'object',
                                                        'fields' => [
                                                            'row' => [
                                                                'kind' => 'int64_string',
                                                            ],
                                                        ],
                                                    ],
                                                ],
                                                'success_count' => [
                                                    'kind' => 'int64_string',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * Creates a ProductCatalogImport.
     *
     * @param null|array{feed_type: string, metadata: array<string, string>, mode: string} $params
     * @param null|RequestOptionsArray|\PublishPress\Stripe\Util\RequestOptions $opts
     *
     * @return \PublishPress\Stripe\V2\Commerce\ProductCatalogImport
     *
     * @throws \PublishPress\Stripe\Exception\ApiErrorException if the request fails
     */
    public function create($params = null, $opts = null)
    {
        return $this->request('post', '/v2/commerce/product_catalog/imports', $params, $opts, [
            'response_schema' => [
                'kind' => 'object',
                'fields' => [
                    'status_details' => [
                        'kind' => 'object',
                        'fields' => [
                            'processing' => [
                                'kind' => 'object',
                                'fields' => [
                                    'error_count' => ['kind' => 'int64_string'],
                                    'success_count' => [
                                        'kind' => 'int64_string',
                                    ],
                                ],
                            ],
                            'succeeded' => [
                                'kind' => 'object',
                                'fields' => [
                                    'success_count' => [
                                        'kind' => 'int64_string',
                                    ],
                                ],
                            ],
                            'succeeded_with_errors' => [
                                'kind' => 'object',
                                'fields' => [
                                    'error_count' => ['kind' => 'int64_string'],
                                    'error_file' => [
                                        'kind' => 'object',
                                        'fields' => [
                                            'size' => [
                                                'kind' => 'int64_string',
                                            ],
                                        ],
                                    ],
                                    'samples' => [
                                        'kind' => 'array',
                                        'element' => [
                                            'kind' => 'object',
                                            'fields' => [
                                                'row' => [
                                                    'kind' => 'int64_string',
                                                ],
                                            ],
                                        ],
                                    ],
                                    'success_count' => [
                                        'kind' => 'int64_string',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * Retrieves a ProductCatalogImport by ID.
     *
     * @param string $id
     * @param null|array $params
     * @param null|RequestOptionsArray|\PublishPress\Stripe\Util\RequestOptions $opts
     *
     * @return \PublishPress\Stripe\V2\Commerce\ProductCatalogImport
     *
     * @throws \PublishPress\Stripe\Exception\ApiErrorException if the request fails
     */
    public function retrieve($id, $params = null, $opts = null)
    {
        return $this->request('get', $this->buildPath('/v2/commerce/product_catalog/imports/%s', $id), $params, $opts, [
            'response_schema' => [
                'kind' => 'object',
                'fields' => [
                    'status_details' => [
                        'kind' => 'object',
                        'fields' => [
                            'processing' => [
                                'kind' => 'object',
                                'fields' => [
                                    'error_count' => ['kind' => 'int64_string'],
                                    'success_count' => [
                                        'kind' => 'int64_string',
                                    ],
                                ],
                            ],
                            'succeeded' => [
                                'kind' => 'object',
                                'fields' => [
                                    'success_count' => [
                                        'kind' => 'int64_string',
                                    ],
                                ],
                            ],
                            'succeeded_with_errors' => [
                                'kind' => 'object',
                                'fields' => [
                                    'error_count' => ['kind' => 'int64_string'],
                                    'error_file' => [
                                        'kind' => 'object',
                                        'fields' => [
                                            'size' => [
                                                'kind' => 'int64_string',
                                            ],
                                        ],
                                    ],
                                    'samples' => [
                                        'kind' => 'array',
                                        'element' => [
                                            'kind' => 'object',
                                            'fields' => [
                                                'row' => [
                                                    'kind' => 'int64_string',
                                                ],
                                            ],
                                        ],
                                    ],
                                    'success_count' => [
                                        'kind' => 'int64_string',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }
}
