<?php
$linet_sync='';

if(isset($options["sync"])&&$options["sync"]=="on"){
	$linet_sync="checked";
}

 ?>

<input type='checkbox' name='<?php echo esc_attr((self::OPTION_PREFIX.$args['key']));?>[sync]' <?php echo esc_attr($linet_sync);?> id='<?php echo esc_attr(self::OPTION_PREFIX.$args['key']);?>_sync' />
<label for="<?php echo esc_attr(self::OPTION_PREFIX.$args['key']);?>_sync"> <?php echo esc_html__('Sync', 'linet-erp-woocommerce-integration');?></label>

<table style="border-collapse: collapse;">
	<thead>
		<tr>
			<th>Field</th>
			<th>Map</th>
			<th>Value</th>
		</tr>
	</thead>
	<tbody>
<?php //var_dump($args);

$contact_fields=array(
	""=>__("None", 'linet-erp-woocommerce-integration'),
	//"value"=>__("Value", 'linet-erp-woocommerce-integration'),
	"map"=>__("Map", 'linet-erp-woocommerce-integration')
);

$ContactForm = WPCF7_ContactForm::get_instance( $args["option"]["payload"]["form_id"]);
$fields = $ContactForm->scan_form_tags();

foreach ($fields as $field) {
	if( $field->name == "" ) continue;

	$value_type='';
	$linet_value='';
	if(isset($options[$field->name])&&isset($options[$field->name]['value_type'])){
		$value_type=$options[$field->name]['value_type'];
	}

	if(isset($options[$field->name])&&isset($options[$field->name]['linet_value'])){
		$linet_value=$options[$field->name]['linet_value'];
	}

	?>
			<tr>
				<td><?php echo  esc_html($field->name); ?></td>
			<td>
				<select name='<?php echo esc_attr(self::OPTION_PREFIX.$args['key']);?>[<?php echo  esc_attr($field->name); ?>][value_type]'>
				 <?php
				 foreach ($contact_fields as $field_key => $cfield) {
					 if($field_key==$value_type)
					 echo "<option selected='selected' value='".esc_attr($field_key)."'>".esc_html($cfield)."</option>";
					 else
						echo "<option value='".esc_attr($field_key)."'>".esc_html($cfield)."</option>";
					}
				?>
			</select>
		</td>
		<td>
		<input type='text' name='<?php echo esc_attr(self::OPTION_PREFIX.$args['key']);?>[<?php echo  esc_attr($field->name); ?>][linet_value]' value='<?php echo esc_attr($linet_value);?>' placeholder=' <?php echo esc_html__('Linet Value', 'linet-erp-woocommerce-integration');?>' />
		</td>
		</tr>
<?php
		}
		?>

	</tbody>
</table>
