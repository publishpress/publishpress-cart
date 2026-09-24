<?php

if (! defined('ABSPATH')) {
    exit;
}


$this->set_field_groups(true);

$fields = [];
$groups = $this->get_product_field_groups('save');

foreach ($groups as $groupKey) {
    $this->$groupKey ??= [];
    $this->$groupKey = $this->filter_product_setting_tab_fields($groupKey, $this->$groupKey, 'save');
    foreach ($this->$groupKey as $group) {
        $fieldType = (isset($group['field-type'])) ? $group['field-type'] : $group['type'];
        $set = [@$group['id'], $fieldType];
        if ($group['type'] == 'repeater') {
            $r_fields = [];
            foreach ($group['fields'] as $gfield) {
                foreach ($gfield as $k => $v) {
                    $fieldType = $v['type'] ?? $k;
                    $field = [$v['id'], $fieldType];

                    $pos = strpos($v['class'], 'required');
                    if ($pos !== false && !isset($v['conditional_logic'])) {
                        $field[] = 'required';
                    }

                    $r_fields[] = $field;
                }
            }
            $set[] = $r_fields;
        }
        $fields[] = $set;
    }
}

return $fields;
