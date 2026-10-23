<?php
/**
 * Homepage seasons.
 *
 * The homepage has one slot under the hero that shows the current season:
 * registration open, thank-you after the event, or get-involved in the off
 * season. Each season's content is a synced pattern (wp_block) that editors fill
 * in; which one shows is a single option Editors set under Pages → Homepage
 * season, so switching never touches the page layout.
 *
 * @package SFV_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const SFV_SEASON_OPTION = 'sfv_homepage_season';

/**
 * Seasons in the order they happen. Each maps to the synced pattern (by slug)
 * that holds its content.
 */
function sfv_seasons() {
	return array(
		'registration' => array(
			'label'       => __( 'Registration open', 'sfv-theme' ),
			'description' => __( 'Before the event: who can register, the event details and a Register now button.', 'sfv-theme' ),
			'block_slug'  => 'season-registration',
		),
		'thank-you'    => array(
			'label'       => __( 'Thank you', 'sfv-theme' ),
			'description' => __( 'Right after the event: thanks to everyone who came, with photos from the day.', 'sfv-theme' ),
			'block_slug'  => 'season-thank-you',
		),
		'get-involved' => array(
			'label'       => __( 'Get involved', 'sfv-theme' ),
			'description' => __( 'The off season: how to volunteer, sponsor or donate before the next event.', 'sfv-theme' ),
			'block_slug'  => 'season-get-involved',
		),
	);
}

/**
 * The season to show: a valid ?sfv_season_preview= for people who can edit
 * pages, otherwise the saved option.
 */
function sfv_current_season() {
	$seasons = sfv_seasons();

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only preview for logged-in editors.
	$preview = isset( $_GET['sfv_season_preview'] ) ? sanitize_key( wp_unslash( $_GET['sfv_season_preview'] ) ) : '';
	if ( $preview && isset( $seasons[ $preview ] ) && current_user_can( 'edit_pages' ) ) {
		return $preview;
	}

	$saved = get_option( SFV_SEASON_OPTION, 'registration' );

	return isset( $seasons[ $saved ] ) ? $saved : 'registration';
}

function sfv_season_block_id( $season ) {
	$seasons = sfv_seasons();

	if ( ! isset( $seasons[ $season ] ) ) {
		return 0;
	}

	$block = get_page_by_path( $seasons[ $season ]['block_slug'], OBJECT, 'wp_block' );

	return $block ? (int) $block->ID : 0;
}

function sfv_register_season_block() {
	register_block_type( get_theme_file_path( 'blocks/homepage-season' ) );
}
add_action( 'init', 'sfv_register_season_block' );

// ---------------------------------------------------------------------------
// Pages → Homepage season
// ---------------------------------------------------------------------------

function sfv_season_admin_menu() {
	add_pages_page(
		__( 'Homepage season', 'sfv-theme' ),
		__( 'Homepage season', 'sfv-theme' ),
		'edit_pages',
		'sfv-homepage-season',
		'sfv_season_render_admin_page'
	);
}
add_action( 'admin_menu', 'sfv_season_admin_menu' );

function sfv_season_save() {
	if ( ! isset( $_POST['sfv_season_nonce'] ) ) {
		return;
	}

	check_admin_referer( 'sfv_season_save', 'sfv_season_nonce' );

	if ( ! current_user_can( 'edit_pages' ) ) {
		wp_die( esc_html__( 'Not allowed.', 'sfv-theme' ) );
	}

	$season = isset( $_POST['sfv_season'] ) ? sanitize_key( wp_unslash( $_POST['sfv_season'] ) ) : '';

	if ( isset( sfv_seasons()[ $season ] ) ) {
		update_option( SFV_SEASON_OPTION, $season );
	}

	wp_safe_redirect( add_query_arg( array( 'page' => 'sfv-homepage-season', 'updated' => '1' ), admin_url( 'edit.php?post_type=page' ) ) );
	exit;
}
add_action( 'admin_init', 'sfv_season_save' );

function sfv_season_render_admin_page() {
	$current = get_option( SFV_SEASON_OPTION, 'registration' );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Homepage season', 'sfv-theme' ); ?></h1>

		<?php if ( isset( $_GET['updated'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
			<div class="notice notice-success is-dismissible"><p>
				<?php esc_html_e( 'Homepage season updated.', 'sfv-theme' ); ?>
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'View the homepage', 'sfv-theme' ); ?></a>
			</p></div>
		<?php endif; ?>

		<p><?php esc_html_e( 'Choose what the homepage shows under the main photo. The rest of the homepage stays the same. Edit each season’s words and photos ahead of time, preview it, then switch.', 'sfv-theme' ); ?></p>

		<form method="post">
			<?php wp_nonce_field( 'sfv_season_save', 'sfv_season_nonce' ); ?>
			<fieldset>
				<legend class="screen-reader-text"><?php esc_html_e( 'Homepage season', 'sfv-theme' ); ?></legend>
				<table class="widefat striped" role="presentation">
					<tbody>
					<?php foreach ( sfv_seasons() as $key => $season ) : ?>
						<?php $block_id = sfv_season_block_id( $key ); ?>
						<tr>
							<td style="width:2em;vertical-align:top;padding-top:14px;">
								<input type="radio" id="sfv-season-<?php echo esc_attr( $key ); ?>" name="sfv_season" value="<?php echo esc_attr( $key ); ?>" aria-describedby="sfv-season-<?php echo esc_attr( $key ); ?>-desc" <?php checked( $current, $key ); ?>>
							</td>
							<td>
								<label for="sfv-season-<?php echo esc_attr( $key ); ?>"><strong><?php echo esc_html( $season['label'] ); ?></strong>
									<?php if ( $current === $key ) : ?>
										&mdash; <?php esc_html_e( 'showing now', 'sfv-theme' ); ?>
									<?php endif; ?>
								</label>
								<p id="sfv-season-<?php echo esc_attr( $key ); ?>-desc" style="margin:4px 0 8px;"><?php echo esc_html( $season['description'] ); ?></p>
								<?php if ( $block_id ) : ?>
									<a href="<?php echo esc_url( get_edit_post_link( $block_id ) ); ?>"><?php echo esc_html( sprintf( /* translators: %s: season name */ __( 'Edit “%s” content', 'sfv-theme' ), $season['label'] ) ); ?></a>
									&nbsp;|&nbsp;
									<a href="<?php echo esc_url( add_query_arg( 'sfv_season_preview', $key, home_url( '/' ) ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Preview on the homepage', 'sfv-theme' ); ?><span class="screen-reader-text"> <?php esc_html_e( '(opens in a new tab)', 'sfv-theme' ); ?></span></a>
								<?php else : ?>
									<em><?php esc_html_e( 'No content yet. Ask a maintainer to create it.', 'sfv-theme' ); ?></em>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			</fieldset>
			<?php submit_button( __( 'Switch homepage season', 'sfv-theme' ) ); ?>
		</form>
	</div>
	<?php
}
