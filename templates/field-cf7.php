<?php
$linet_sync='';

if(isset($options["sync"])&&$options["sync"]=="on"){
	$linet_sync="checked";
}

 ?>

<input type='checkbox' name='<?=self::OPTION_PREFIX.$args['key'];?>[sync]' <?=$linet_sync;?> id='<?=self::OPTION_PREFIX.$args['key'];?>_sync' />
<label for="<?=self::OPTION_PREFIX.$args['key'];?>_sync"> <?=__('Sync', 'wc-linet');?></label>

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
	""=>__("None", 'wp-linet'),
	//"value"=>__("Value", 'wp-linet'),
	"map"=>__("Map", 'wp-linet')
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
				<td><?= $field->name; ?></td>
			<td>
				<select name='<?=self::OPTION_PREFIX.$args['key'];?>[<?= $field->name; ?>][value_type]'>
				 <?php
				 foreach ($contact_fields as $field_key => $cfield) {
					 if($field_key==$value_type)
					 echo "<option selected='selected' value='$field_key'> $cfield</option>";
					 else
						echo "<option value='$field_key'> $cfield</option>";
					}
				?>
			</select>
		</td>
		<td>
		<input type='text' name='<?=self::OPTION_PREFIX.$args['key'];?>[<?= $field->name; ?>][linet_value]' value='<?=$linet_value;?>' placeholder=' <?=__('Linet Value', 'wc-linet');?>' />
		</td>
		</tr>
<?php
		}
		?>

	</tbody>
</table>
