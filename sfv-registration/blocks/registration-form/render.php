<?php
/**
 * Front-end markup for sfv/registration-form.
 *
 * The submission itself is handled earlier, on template_redirect, so this only
 * reads the result for the current request.
 *
 * @package SFV_Registration
 *
 * @var array $attributes Block attributes.
 */

namespace SFV\Registration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sfv_result = sfv_register_result();
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'sfv-registration' ) ); ?>>
	<?php
	if ( ! empty( $sfv_result['success'] ) ) {
		sfv_register_render_success( $attributes['successHeading'], $attributes['successMessage'] );
	} else {
		sfv_register_render_form( $sfv_result );
	}
	?>
</div>
