<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Settings fields and permalink flush for product file downloads.
 *
 * @package PPCart
 * @subpackage PPCart/includes
 */
trait PPCart_Files_Settings_Trait
{
    public function flush_permalinks($old_value, $new_value)
    {
        // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.flush_rewrite_rules_flush_rewrite_rules -- Required after download slug option changes.
        flush_rewrite_rules();
    }

    public function download_slug_setting($options)
    {
        $options['settings']['download_slug'] = [
                'type'          => 'text',
                'label'         => esc_html__('Download URL slug', 'publishpress-cart'),
                'subsection'    => 'downloads',
                'settings'      => [
                    'id'            => 'ppcart_download_slug',
                    'value'         => 'file',
                    'description'   => esc_html__('URL base for secure download links', 'publishpress-cart'),
                ],
        ];

        return $options;
    }

    public function upload_dir($pathdata)
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Upload context is validated by the media workflow; this only adjusts upload path.
        $upload_type = isset($_POST['type']) ? sanitize_text_field(wp_unslash($_POST['type'])) : '';
        if ('ppcart_upload' === $upload_type) {
            if (empty($pathdata['subdir'])) {
                $pathdata['path']   = $pathdata['path'] . '/ppcart-uploads';
                $pathdata['url']    = $pathdata['url'] . '/ppcart-uploads';
                $pathdata['subdir'] = '/ppcart-uploads';
            } else {
                $new_subdir = '/ppcart-uploads' . $pathdata['subdir'];
                $pathdata['path']   = str_replace($pathdata['subdir'], $new_subdir, $pathdata['path']);
                $pathdata['url']    = str_replace($pathdata['subdir'], $new_subdir, $pathdata['url']);
                $pathdata['subdir'] = str_replace($pathdata['subdir'], $new_subdir, $pathdata['subdir']);
            }
        }
        return $pathdata;
    }

    public function login_to_download_setting($options)
    {
        $options['settings']['login_download'] = [
                'type'          => 'checkbox',
                'label'         => esc_html__('Logged in user downloads only', 'publishpress-cart'),
                'subsection'    => 'downloads',
                'settings'      => [
                    'id'            => 'ppcart_login_to_download',
                    'value'         => '1',
                    'description'   => esc_html__('Require users to log in before downloading files', 'publishpress-cart'),
                ],
        ];
        return $options;
    }

    public function files_tab($tabs)
    {
        $tabs['files'] = __('Files', 'publishpress-cart');
        return $tabs;
    }

    public function file_group($groups)
    {
        $groups[] = 'files';
        return $groups;
    }

    /**
     * Product metabox field schema for downloadable files.
     *
     * @param array $fields Existing metabox fields.
     * @return array
     */
    public function file_fields($fields)
    {
        return [
            [
                'class'         => 'ppcart-repeater',
                'id'            => '_ppcart_files',
                'label-add'     => __('+ Add New', 'publishpress-cart'),
                'label-edit'    => __('Edit File', 'publishpress-cart'),
                'label-header'  => __('File', 'publishpress-cart'),
                'label-remove'  => __('Remove File', 'publishpress-cart'),
                'title-field'   => 'name',
                'type'          => 'repeater',
                'value'         => '',
                'class_size'    => '',
                'fields'        => [
                    [
                        'text' => [
                            'class'         => 'widefat ppcart-unique',
                            'description'   => '',
                            'id'            => 'file_id',
                            'label'         => __('File ID', 'publishpress-cart'),
                            'placeholder'   => '',
                            'type'          => 'text',
                            'value'         => '',
                            'class_size'    => 'hide',
                        ]],
                    [
                        'file-upload' => [
                            'class'         => 'select file_url required',
                            'id'            => 'file_url',
                            'label'         => __('File URL', 'publishpress-cart'),
                            'placeholder'   => '',
                            'type'          => 'file-upload',
                            'field-type'    => 'url',
                            'value'         => '',
                            'class_size'    => '',
                            'label-remove'      => __('Clear', 'publishpress-cart'),
                            'label-upload'      => __('Upload File', 'publishpress-cart'),
                    ]],
                    [
                    'text' => [
                        'class'         => 'select file_name required repeater-title',
                        'id'            => 'file_name',
                        'label'         => __('File Name', 'publishpress-cart'),
                        'placeholder'   => '',
                        'type'          => 'text',
                        'value'         => '',
                        'class_size'    => 'one-half',
                    ]],
                    [
                        'select' => [
                            'class'         => 'ppcart-selectize multiple',
                            'description'   => __('Give access only if the order is for a specific payment plan (or purchase type) for this product. Leave blank to give access any time this product is ordered.', 'publishpress-cart'),
                            'id'            => 'file_plan',
                            'label'         => __('Restrict by payment plan / purchase type', 'publishpress-cart'),
                            'placeholder'   => __('Any', 'publishpress-cart'),
                            'type'          => 'select',
                            'value'         => '',
                            'class_size'    => 'one-half',
                            'selections'    => PPCart_Product_Metaboxes::get_payment_plans(),
                            'conditional_logic' =>  [
                                    [
                                        'field' => 'services',
                                        'value' => '', // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
                                        'compare' => '!=', // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
                                    ],
                                ],
                        ]],
                    [
                        'text' => [
                            'class'     => 'widefat',
                            'description'   => '',
                            'id'            => 'file_limit',
                            'label'     => __('Download limit', 'publishpress-cart'),
                            'placeholder'   => '&infin;',
                            'type'      => 'number',
                            'value'     => '',
                            'class_size'        => 'one-half',
                            'conditional_logic' => [
                                [
                                    'field' => ppcart_meta_key('manage_stock'),
                                    'value' => true,
                                ],
                            ],
                        ],
                    ],
                    [
                        'checkbox' => [
                            'class'     => '',
                            'description'   => __("Don't force download.", 'publishpress-cart'),
                            'id'            => 'file_redirect',
                            'label'     => __('Redirect to file', 'publishpress-cart'),
                            'placeholder'   => '',
                            'type'      => 'checkbox',
                            'value'     => '',
                            'class_size' => '',
                            'conditional_logic' => '',
                        ],
                    ],
                ],
            ],
        ];
    }
}
