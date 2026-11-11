<?php
/**
 * Front-end markup for sfv/event-details, from Pages → Event settings.
 *
 * @package SFV_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sfv_event = sfv_event();
$sfv_date  = sfv_event_date_label();
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'sfv-event-details' ) ); ?>>
	<?php if ( $sfv_date ) : ?>
		<p><strong>Date:</strong> <?php echo esc_html( $sfv_date ); ?></p>
	<?php endif; ?>
	<?php if ( $sfv_event['checkin_start'] && $sfv_event['checkin_end'] ) : ?>
		<p><strong>Event Check-In:</strong> <?php echo esc_html( sfv_event_time_label( $sfv_event['checkin_start'] ) . ' to ' . sfv_event_time_label( $sfv_event['checkin_end'] ) ); ?></p>
	<?php endif; ?>
	<?php if ( $sfv_event['start'] && $sfv_event['end'] ) : ?>
		<p><strong>Surfing &amp; Fun:</strong> <?php echo esc_html( sfv_event_time_label( $sfv_event['start'] ) . ' – ' . sfv_event_time_label( $sfv_event['end'] ) ); ?></p>
	<?php endif; ?>
	<?php if ( $sfv_event['place'] ) : ?>
		<p><strong>Location:</strong> <?php echo esc_html( $sfv_event['place'] ); ?>
			<?php if ( $sfv_event['landmark'] ) : ?>
				<br><em class="sfv-event-details__note"><?php echo esc_html( $sfv_event['landmark'] ); ?></em>
			<?php endif; ?>
		</p>
	<?php endif; ?>
	<?php if ( '' !== $sfv_event['cost'] ) : ?>
		<p><strong>$<?php echo esc_html( $sfv_event['cost'] ); ?> registration fee</strong></p>
	<?php endif; ?>
	<?php if ( '' !== $sfv_event['surfer_limit'] ) : ?>
		<p><strong>Registration is limited to <?php echo esc_html( $sfv_event['surfer_limit'] ); ?> surfers</strong></p>
	<?php endif; ?>
</div>
