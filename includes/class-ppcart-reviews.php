<?php

use PublishPress\WordPressReviews\ReviewsController;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * PublishPress Cart WordPress.org review-notice integration.
 */
class PPCart_Reviews
{
    /**
     * @var ReviewsController|null
     */
    private static $review_controller = null;

    /**
     * Registers the wordpress-reviews library on admin_init.
     *
     * ReviewsController::init() inspects the current user and screen immediately,
     * so this cannot run on plugins_loaded (user is not authenticated yet).
     *
     * @return void
     */
    public static function init()
    {
        if (! is_admin() || defined('PUBLISHPRESS_CART_SKIP_REVIEWS')) {
            return;
        }

        add_action('admin_init', [ __CLASS__, 'register_reviews' ]);
    }

    /**
     * Instantiates ReviewsController and limits the notice to Cart screens.
     *
     * @return void
     */
    public static function register_reviews()
    {
        if (null !== self::$review_controller) {
            return;
        }

        if (! apply_filters('ppcart_show_reviews', true)) {
            return;
        }

        if (! class_exists(ReviewsController::class)) {
            return;
        }

        add_filter('publishpress-cart_wp_reviews_allow_display_notice', [ self::class, 'should_display_banner' ]);

        self::$review_controller = new ReviewsController(
            'publishpress-cart',
            'PublishPress Cart',
            ''
        );

        self::$review_controller->init();
    }

    /**
     * Shows the review banner only on Cart dashboard and settings, for managers.
     *
     * @param bool $should_display Library default.
     * @return bool
     */
    public static function should_display_banner($should_display)
    {
        if (! $should_display || ! is_admin() || ! current_user_can('manage_options')) {
            return false;
        }

        if (! class_exists('PPCart_Admin_Screens')) {
            return false;
        }

        return PPCart_Admin_Screens::is_dashboard_screen() || PPCart_Admin_Screens::is_settings_screen();
    }
}
