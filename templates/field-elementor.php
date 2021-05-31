<?php //var_dump($options); ?>

<div class="repeater_text">
	<p class="description"><?php echo $args['option']['description']; ?></p>
	<table class="map" style="border-collapse: collapse;">
		<thead>
			<tr>
				<th width="40">#</th>
				<th>Form Name</th>
				<th>map</th>
				<th></th>
			</tr>
		</thead>
		<tbody>
		<?php
		function row($key,$fieldName,$value='',$el_fields=array(),$li_fields=array()){
			$text='<table class="fldmap" style="border-collapse: collapse;">
				<thead>
					<tr>

						<th>elementor</th>
						<th>linet</th>
						<th>action</th>

					</tr>
				</thead>
				<tbody>';

				if(count($el_fields)==0){
					$el_fields[]='';
					$li_fields[]='';
				}

			foreach ( $el_fields as $skey => $svalue ) {

				$text.=
				"			<tr>

				<td><input type='text' name='{$fieldName}[el_field][$key][]' id='{$fieldName}{$key}_wc' value='$svalue' placeholder='". __('Elmentor Field', 'wc-linet') ."' /></td>"
				."<td><input type='text' name='{$fieldName}[li_field][$key][]' id='{$fieldName}{$key}_wc' value='$li_fields[$skey]' placeholder='". __('Linet Field', 'wc-linet') ."' /></td>"
				."<td><button class='remove_field'>Remove</button></td>"
				."</tr>"

				;


			}

			$text.=	"<tbody></table><button class='add_new_field'>Add Field</button>";


			return 	"<tr class='repeat form'>
				<td class='id'>$key</td>
				<td><label>
					<input type='text' name='{$fieldName}[form_name][]' id='{$fieldName}{$key}_linet' value='$value' placeholder='". __('Form Name', 'wc-linet') ."' />
				</label></td>
				<td>
				$text
				</td>
				<td>
				<button class='remove'>Remove</button>

				</td>
				</tr>";
		}

		if( is_array($options) && count($options) > 0 ) {
			foreach ( $options['form_name'] as $key => $value ) {
				echo row($key,self::OPTION_PREFIX.$args['key'],$value,$options['el_field'][$key],$options['li_field'][$key]);
			}
		} else {
			echo row(0,self::OPTION_PREFIX.$args['key'],'');
		}
		?>
		</tbody>
	</table>
	<button class="repeater_add_new">Add Map</button>
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
			var tableRow    = $(this).prev().find("tr.form:last");//' tbody tr:last'

			cloneTableRow = tableRow.clone();

			cloneTableRow.find('input').val('');
			cloneTableRow.find('td.id').html(parseInt(cloneTableRow.find('td.id').html()) + 1);
			tableRow.after(cloneTableRow);
		});




		$('body').on('click', '.remove', function (e) {
			e.preventDefault();
			if( $('table.map tbody tr').length > 1 ) {
				var tableRow    = $(this).closest('tr');
				tableRow.remove();
			} else {
				alert('לא ניתן להסיר את האפשרות האחרונה. נקה את השדות ותשמור על מנת לבטל.')
			}
		});










		$('body').on('click', '.add_new_field', function (e) {
			e.preventDefault();
			var tableRow    = $(this).prev().find("tr:last");//' tbody tr:last'
			console.log(tableRow);

			//'table.fldmap tbody tr'

			cloneTableRow = tableRow.clone();

			cloneTableRow.find('input').val('');
			cloneTableRow.find('td.id').html(parseInt(cloneTableRow.find('td.id').html()) + 1);
			tableRow.after(cloneTableRow);
		});



		$('body').on('click', '.remove_field', function (e) {
			e.preventDefault();
			$(this).data()

			if( $('.repeater_text table tbody tr').length > 1 ) {
				var tableRow    = $(this).closest('tr');
				tableRow.remove();
			} else {
				alert('לא ניתן להסיר את האפשרות האחרונה. נקה את השדות ותשמור על מנת לבטל.')
			}
		});



	})
</script>
