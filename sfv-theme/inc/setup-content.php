<?php
/**
 * Tools → Set up site content (Administrators only).
 *
 * Used once at cutover, right after this theme is activated, to create the
 * pages and shared sections from the theme's patterns. Runs the content builder
 * in 'create' mode: it only adds what is missing and never changes anything that
 * exists, so pressing it twice is harmless.
 *
 * @package SFV_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function sfv_setup_content_menu() {
	add_management_page(
		__( 'Set up site content', 'sfv-theme' ),
		__( 'Set up site content', 'sfv-theme' ),
		'manage_options',
		'sfv-setup-content',
		'sfv_setup_content_render'
	);
}
add_action( 'admin_menu', 'sfv_setup_content_menu' );

/**
 * Items the builder creates, for the status table: [post type, slug, label].
 */
function sfv_setup_content_items() {
	return array(
		array( 'wp_block', 'sponsors', __( 'Shared section: Sponsors', 'sfv-theme' ) ),
		array( 'wp_block', 'season-registration', __( 'Shared section: Season – registration open', 'sfv-theme' ) ),
		array( 'wp_block', 'season-thank-you', __( 'Shared section: Season – thank you', 'sfv-theme' ) ),
		array( 'wp_block', 'season-get-involved', __( 'Shared section: Season – get involved', 'sfv-theme' ) ),
		array( 'wp_block', SFV_FOOTER_LINKS_SLUG, __( 'Shared section: Footer links', 'sfv-theme' ) ),
		array( 'page', 'home', __( 'Page: Home (becomes the homepage)', 'sfv-theme' ) ),
		array( 'page', 'register', __( 'Page: Register', 'sfv-theme' ) ),
		array( 'page', 'faq', __( 'Page: FAQ (created as a draft)', 'sfv-theme' ) ),
		array( 'page', 'photos', __( 'Page: Photos (created as a draft)', 'sfv-theme' ) ),
		array( 'page', 'pattern-library', __( 'Page: Pattern library (private)', 'sfv-theme' ) ),
	);
}

function sfv_setup_content_render() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Not allowed.', 'sfv-theme' ) );
	}

	$log   = null;
	$error = '';

	if ( isset( $_POST['sfv_setup_content_nonce'] ) ) {
		check_admin_referer( 'sfv_setup_content', 'sfv_setup_content_nonce' );

		try {
			$log = sfv_build_site_content( 'create' );
		} catch ( RuntimeException $e ) {
			$error = $e->getMessage();
		}
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Set up site content', 'sfv-theme' ); ?></h1>

		<?php if ( $error ) : ?>
			<div class="notice notice-error"><p><?php echo esc_html( $error ); ?></p></div>
		<?php elseif ( null !== $log ) : ?>
			<div class="notice notice-success"><p><?php esc_html_e( 'Done. Here is what happened:', 'sfv-theme' ); ?></p>
				<ul style="list-style:disc;margin-left:2em;">
					<?php foreach ( $log as $line ) : ?>
						<li><?php echo esc_html( $line ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<p><?php esc_html_e( 'Creates the pages and shared sections this theme needs, from its built-in layouts, using photos already in the media library. Use it once, right after activating the theme.', 'sfv-theme' ); ?></p>
		<p><strong><?php esc_html_e( 'It only adds what is missing. Nothing that already exists is changed, so pressing it again is safe.', 'sfv-theme' ); ?></strong></p>

		<table class="widefat striped" style="max-width:720px;">
			<thead><tr><th><?php esc_html_e( 'Item', 'sfv-theme' ); ?></th><th><?php esc_html_e( 'Status', 'sfv-theme' ); ?></th></tr></thead>
			<tbody>
			<?php foreach ( sfv_setup_content_items() as list( $type, $slug, $label ) ) : ?>
				<?php $post = get_page_by_path( $slug, OBJECT, $type ); ?>
				<tr>
					<td><?php echo esc_html( $label ); ?></td>
					<td>
						<?php if ( $post ) : ?>
							<?php esc_html_e( 'Exists (will not be changed)', 'sfv-theme' ); ?>
						<?php else : ?>
							<strong><?php esc_html_e( 'Will be created', 'sfv-theme' ); ?></strong>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>

		<form method="post" style="margin-top:1.5em;">
			<?php wp_nonce_field( 'sfv_setup_content', 'sfv_setup_content_nonce' ); ?>
			<?php submit_button( __( 'Create missing content', 'sfv-theme' ), 'primary', 'submit', false ); ?>
		</form>
	</div>
	<?php
}
