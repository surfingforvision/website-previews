<?php
/**
 * Title: Header
 * Slug: sfv/header
 * Inserter: no
 *
 * Site header: logo, name and primary navigation. Owned by Git (see CLAUDE.md);
 * rendered by parts/header.html.
 *
 * @package SFV_Theme
 */

$sfv_donate_url = 'https://www.paypal.com/ncp/payment/YZ22T3386PE3J';
?>
<!-- wp:html -->
<div class="sfv-header">
	<div class="sfv-header__bar">
		<a class="sfv-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<img class="sfv-brand__mark" src="<?php echo esc_url( get_theme_file_uri( 'assets/images/logo-eye-inverse.svg' ) ); ?>" alt="" width="76" height="30">
			<span class="sfv-brand__name">Surfing <span class="sfv-brand__for">for</span> Vision</span>
		</a>
		<nav class="sfv-nav" aria-label="Primary">
			<ul>
				<li><a href="<?php echo esc_url( home_url( '/register/' ) ); ?>"<?php sfv_current_attr( '/register/' ); ?>>Register</a></li>
				<li><a href="<?php echo esc_url( home_url( '/#about' ) ); ?>">About</a></li>
				<li><a href="<?php echo esc_url( home_url( '/#sponsors' ) ); ?>">Sponsors</a></li>
				<li><a class="sfv-nav__donate" href="<?php echo esc_url( $sfv_donate_url ); ?>">Donate</a></li>
			</ul>
		</nav>
	</div>
</div>
<!-- /wp:html -->
