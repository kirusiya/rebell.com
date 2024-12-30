<?php
/**
 * Lost password reset form.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/form-reset-password.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.5.5
 */

defined( 'ABSPATH' ) or die( '¯\_(ツ)_/¯' );



do_action( 'woocommerce_before_reset_password_form' ); ?>

<form method="post" class="WCAccountForm woocommerce-ResetPassword lost_reset_password">

	<h3 class="Title"><?= __( 'Nueva contraseña', 'betheme' ) ?></h3>

	<p><?= apply_filters(
		'woocommerce_reset_password_message',
		esc_html__( 'Enter a new password below.', 'woocommerce' ) );
	?></p>

	<p class="woocommerce-form-row form-row">
		<input
			type="password"
			class="woocommerce-Input woocommerce-Input--text input-text"
			name="password_1"
			id="password_1"
			autocomplete="new-password"
			placeholder="<?php esc_html_e( 'New password', 'woocommerce' ); ?>"
		/>
	</p>
	<p class="woocommerce-form-row form-row">
		<input
			type="password"
			class="woocommerce-Input woocommerce-Input--text input-text"
			name="password_2"
			id="password_2"
			autocomplete="new-password"
			placeholder="<?php esc_html_e( 'Re-enter new password', 'woocommerce' ); ?>"
		/>
	</p>

	<input type="hidden" name="reset_key" value="<?= esc_attr( $args['key'] ); ?>" />
	<input type="hidden" name="reset_login" value="<?= esc_attr( $args['login'] ); ?>" />

	<div class="clear"></div>

	<?php do_action( 'woocommerce_resetpassword_form' ); ?>

	<p class="woocommerce-form-row form-row">
		<input
			type="hidden"
			name="wc_reset_password"
			value="true"
		/>
		<button
			type="submit"
			class="btn"
			value="<?php esc_attr_e( 'Save', 'woocommerce' ); ?>"
		>
			<?php esc_html_e( 'Save', 'woocommerce' ); ?>
		</button>
	</p>

	<?php wp_nonce_field( 'reset_password', 'woocommerce-reset-password-nonce' ); ?>

</form>

<?php do_action( 'woocommerce_after_reset_password_form' );
