<?php
/**
 * The template used for number fields in the booking form, such as persons or durations.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-bookings-extensions/booking-view.php.
 *
 * HOWEVER, on occasion we will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @author  Marius Bezuidenhout
 * @since   1.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/** @var \WC_Booking[] $bookings */
// default template
$unix_timestamp        = strtotime( 'now' );
$next_booking_time     = '';
$next_booking_date     = '';
$current_booking_title = '';
$current_booking_end   = '';

if ( ! empty( $bookings['next'] ) ) {
	/** @var WC_Booking $booking */
	$booking           = $bookings['next']['booking'];
	$next_booking_time = date( get_option( 'time_format' ), $booking->get_start() );
	$next_booking_date = date( get_option( 'date_format' ), $booking->get_start() );
}

if ( ! empty( $bookings['now'] ) ) {
	/** @var WC_Booking $booking */
	$booking               = $bookings['now']['booking'];
	$current_booking_end   = date( get_option( 'time_format' ), $booking->get_end() );
	$current_booking_title = $bookings['now']['organizer'];
}

wp_enqueue_script( 'booking-view' );

?>
<!doctype html>
<html lang="en-ZA">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
	<title><?php echo esc_html( wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ) ); ?> – <?php echo esc_html( $product->get_name() ); ?></title>
	<style type="text/css">

		html, body {
			height: 100%;
			width: 100%;
			margin: 0;
			padding: 0;
			overflow: hidden;
		}

		.current-booking-container {
			position: absolute;
			left: 0;
			top: 33%;
			transform: translateY(-33%);
			padding: 15px;
		}

		.next-booking-container {
			position: absolute;
			right: 0;
			bottom: 5%;
			padding: 15px;
		}

		.product.product-title {
			padding: 0 20px;
		}

		.current-time {
			position: absolute;
			top: 10%;
			transform: translateY(-10%);
			padding: 10px 15px;
			right: 0;
			font-size: 4.2em;
		}

		.current-status {
			bottom: 0;
			left: 0;
			position: absolute;
			padding: 10px 15px;
		}

		@media only screen and (max-height: 768px) {
			body {
				font-size: 20px;
			}
		}

		@media only screen and (min-height: 768px) {
			body {
				font-size: 36px;
			}
		}

		@media only screen and (min-height: 992px) {
			body {
				font-size: 45px;
			}
		}

		@media only screen and (min-height: 1200px) {
			body {
				font-size: 54px;
			}
		}

	</style>
</head>
<body class="single-screen">
	<h1 class="product product-title"><?php echo esc_html( $product->get_name() ); ?></h1>
	<h2 class="server current-time" id="current-time"></h2>
	<div class="current-booking-container">
		<h2 class="current-booking current-booking-title" id="current-booking-title"><?php echo esc_html( $current_booking_title ); ?></h2>
		<h3 class="current-booking current-booking-end" id="current-booking-end"><?php echo $current_booking_end; ?></h3>
	</div>
	<h2 class="current-booking current-status" id="current-status"><?php empty( $bookings['now'] ) ? esc_html_e( 'Available', 'woocommerce-bookings-extensions' ) : esc_html_e( 'In-use', 'woocommerce-bookings-extensions' ); ?></h2>
	<div class="next-booking-container">
		<h2 class="next-booking next-booking-title" id="next-booking-title"><?php esc_html_e( 'Next booking', 'woocommerce-bookings-extensions' ); ?></h2>
		<div class="next-booking next-booking-time" id="next-booking-time"><?php echo $next_booking_time; ?></div>
		<div class="next-booking next-booking-date" id="next-booking-date"><?php echo $next_booking_date; ?></div>
	</div>
	<?php
	$scripts = wp_print_scripts();
	?>
</body>
</html>
