<?php

namespace FRFreeVendor;

/**
 * @var \WPDesk\Forms\Field $field
 * @var string $name_prefix
 * @var string $value
 */
if (!\in_array($field->get_type(), ['number', 'text', 'hidden'], \true)) {
    ?>
	<input type="hidden" name="<?php 
    echo \esc_attr($name_prefix) . '[' . \esc_attr($field->get_name()) . ']';
    ?>" value="no"/>
<?php 
}
?>

<?php 
if ($field->get_type() === 'checkbox' && $field->has_sublabel()) {
    ?>
	<label><?php 
}
?>

<input
	type="<?php 
echo \esc_attr($field->get_type());
?>"
	name="<?php 
echo \esc_attr($name_prefix) . '[' . \esc_attr($field->get_name()) . ']';
?>"

	<?php 
if ($field->has_classes()) {
    ?>
		class="<?php 
    echo \esc_attr($field->get_classes());
    ?>"
	<?php 
}
?>

	<?php 
foreach ($field->get_attributes() as $key => $atr_val) {
    echo \esc_attr($key) . '="' . \esc_attr($atr_val) . '"';
    ?>
	<?php 
}
?>

	<?php 
if (\in_array($field->get_type(), ['number', 'text', 'hidden'], \true)) {
    ?>
		value="<?php 
    echo \esc_html($value);
    ?>"
	<?php 
} else {
    ?>
		value="yes"
		<?php 
    if ($value === 'yes') {
        ?>
			checked="checked"
		<?php 
    }
    ?>
	<?php 
}
?>
/>

<?php 
if ($field->get_type() === 'checkbox' && $field->has_sublabel()) {
    ?>
	<?php 
    echo \wp_kses_post($field->get_sublabel());
    ?></label>
<?php 
}