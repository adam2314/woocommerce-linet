<?php //var_dump($options); ?>

<div class="repeater_text">
	<p class="description"><?php echo $args['option']['description']; ?></p>
	<table style="border-collapse: collapse;">
		<thead>
			<tr>
				<th width="40">#</th>
				<th>Linet Field</th>
				<th>Woocommerce Field</th>
				<th></th>
			</tr>
		</thead>
		<tbody>
		<?php
		function row($key,$fieldName,$value,$wc_value){
			return 	"<tr class='repeat'>
				<td class='id'>$key</td>
				<td><label>
					<input type='text' name='{$fieldName}[linet_field][]' id='{$fieldName}{$key}_linet' value='$value' placeholder='". __('Linet Field', 'wc-linet') ."' />
				</label></td>
				<td><label>
					<input type='text' name='{$fieldName}[wc_field][]' id='{$fieldName}{$key}_wc' value='$wc_value' placeholder='". __('Woocommerce Field', 'wc-linet') ."' />
				</label></td>
				<td><label><button class='remove'>Remove</button></label></td>
				</tr>";
		}

		if( is_array($options) && count($options) > 0 ) {
			foreach ( $options['linet_field'] as $key => $value ) {
				echo row($key,self::OPTION_PREFIX.$args['key'],$value,$options['wc_field'][$key]);
			}
		} else {
			echo row(0,self::OPTION_PREFIX.$args['key'],'','');
		}
		?>
		</tbody>
	</table>
	<button class="repeater_add_new">Add New Row</button>
</div>

<style>
	.repeater_text {
		background: white;
		padding: 15px;
		border: 1px solid #ddd;
	}
	.repeater_text table td, .repeater_text table th {
		padding: 5px;
		border: 0;
		width: auto;
	}

	.repeater_text table tr:nth-child(2n) td {
		background: #efefef;
	}

	.repeater_text table th {
		background: #0085ba;
		color: white;
	}
	.repeater_add_new {
		background: #0085ba;
		color: #fff;
		font-size: 14px;
		font-weight: bold;
		text-align: center;
		padding: 10px 35px;
		border: 0;
		margin-top: 15px;
		cursor: pointer;
	}
</style>

<script>
	jQuery(document).ready(function($){
		$('.repeater_add_new').on('click', function (e) {
			e.preventDefault();
			//$('.repeater_text table tbody tr:last-child');
			var tableRow    = $('.repeater_text table tbody tr:last-child'),
			cloneTableRow = tableRow.clone();

			cloneTableRow.find('input').val('');
			cloneTableRow.find('td.id').html(parseInt(cloneTableRow.find('td.id').html()) + 1);
			tableRow.after(cloneTableRow);
		});

		$('body').on('click', '.remove', function (e) {
			e.preventDefault();
			if( $('.repeater_text table tbody tr').length > 1 ) {
				var tableRow    = $(this).closest('tr');
				tableRow.remove();
			} else {
				alert('לא ניתן להסיר את האפשרות האחרונה. נקה את השדות ותשמור על מנת לבטל.')
			}
		});
	})
</script>
