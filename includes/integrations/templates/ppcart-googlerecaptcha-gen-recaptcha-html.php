<?php

if (! defined('ABSPATH')) {
    exit;
}


?>
        <div class="ppcart-row">
            <div class="ppcart-form-group ppcart-col-sm-12">
                <?php
        if ($this->version_type == 'v3') { ?>
                    <input class="ppcart-grtoken" type="hidden" name="g-recaptcha-response" data-sitekey="<?php echo esc_attr($this->grecaptcha); ?>"/>
                    <?php
        } elseif ($this->version_type == 'v2') {
            if ($this->grecaptcha_type == 'norobo') { ?>
                        <div class="g-recaptcha" data-sitekey="<?php echo esc_attr($this->grecaptcha); ?>"></div>
                        <?php
            } else { ?>
                        <div class="g-recaptcha" data-sitekey="<?php echo esc_attr($this->grecaptcha); ?>" data-size="invisible"></div>
                        <?php
            }
        }
?>
            </div>
        </div>
        <?php
add_action('wp_footer', [$this, 'add_front_scripts']);
