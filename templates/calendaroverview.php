<div class="wbe-calendar-overview <?php echo esc_attr( $class ); ?>" id="<?php echo esc_attr( $calendar_id ); ?>">
    <div class="fc-toolbar">
        <div class="fc-left">
            <div class="fc-button-group">
                <button type="button" class="fc-prev-button fc-button" aria-label="prev">
                    <span class="fc-icon fc-icon-chevron-left"></span>
                </button>
                <button type="button" class="fc-next-button fc-button" aria-label="next">
                    <span class="fc-icon fc-icon-chevron-right"></span>
                </button>
            </div>
        </div>
        <div class="fc-center">
            <h2>Current Month</h2>
        </div>
    </div>
    <div class="fc-view-container">
        <table>
            <thead class="fc-head">
            <tr>
                <td></td>
				<?php
				/** @var \WC_Product_Booking[] $product */
				foreach ( $products as $product ):
					?>
				<td><?php echo $product->get_name(); ?></td>
				<?php
				endforeach;
				?>
            </tr>
            </thead>
			<tbody class="fc-body">
			</tbody>
        </table>
    </div>
</div>