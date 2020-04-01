<?php
$color = get_user_meta( $profileuser->ID, 'wbe_calendar_color', true );
?>
<tr class="user-calendar-color">
	<th scope="row"><?php esc_html_e( 'Calendar Color', 'woocommerce-bookings-extensions' ); ?></th>
	<td>
		<label for="wbe_calendar_color"><input name="wbe_calendar_color" type="text" id="wbe_calendar_color" class="wp-color-picker-field" value="<?php echo esc_html( $color ); ?>" />
		</label>
	</td>
</tr>
