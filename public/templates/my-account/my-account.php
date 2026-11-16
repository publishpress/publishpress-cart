<?php
if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="ppcart-my-account">
    <div class="tab">
        <ul class="ppcart-nav-tabs" role="tablist">

            <?php foreach (ppcart_account_tabs() as $tab_id => $account_tab) :?>
                <?php $ppcart_tab_selected = isset($account_tab['active']); ?>
            <li role="presentation">
                <a class="tablinks <?php if ($ppcart_tab_selected) {
                    echo 'active';
                                   } ?>"
                   href="#<?php echo esc_attr($account_tab['id']); ?>"
                   id="<?php echo esc_attr('ppcart-account-tablink-' . $account_tab['id']); ?>"
                   role="tab"
                   aria-controls="<?php echo esc_attr($account_tab['id']); ?>"
                   aria-selected="<?php echo $ppcart_tab_selected ? 'true' : 'false'; ?>"
                   data-testid="<?php echo esc_attr(ppcart_testid('ppcart-account-tab-' . $account_tab['id'])); ?>">
                    <?php echo esc_html($account_tab['title']); ?>
                </a>
            </li>
            <?php endforeach;?>

        </ul>
    </div>

    <?php foreach (ppcart_account_tabs() as $tab_id => $account_tab) :?>
        <div id="<?php echo esc_attr($account_tab['id']); ?>" class="tabcontent <?php if (isset($account_tab['active'])) {
            echo 'active';
                 } ?>" role="tabpanel" aria-labelledby="<?php echo esc_attr('ppcart-account-tablink-' . $account_tab['id']); ?>">
            <?php if (!empty($account_tab['content'])) :?>
                <?php ppcart_template($account_tab['content'], '', $account_tab); ?>
            <?php endif;?>
            <?php do_action("ppcart_tab_content_{$account_tab['id']}"); ?>
        </div>
    <?php endforeach;?>
</div>
