<?php

/**
 * Login form
 *
 * @package BeTheme
 * @author  Invbit <info@invbit.com>
 * @link    https://www.invbit.com
 */

defined( 'ABSPATH' ) or die( '¯\_(ツ)_/¯' );

do_action( 'woocommerce_before_customer_login_form' ); ?>

<form class="WCAccountForm woocommerce-form woocommerce-form-login login" method="post">

    <?php do_action( 'woocommerce_login_form_start' ); ?>

    <h3 class="Title">Inicio de Sesión</h3>

    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
        <label for="username">
            <?php esc_html_e( 'Username or email address', 'woocommerce' ); ?>&nbsp;<span class="required">*</span>
        </label>
        <input
            placeholder="<?php esc_html_e( 'Username', 'woocommerce' ); ?>"
            type="text"
            class="woocommerce-Input woocommerce-Input--text input-text"
            name="username"
            id="username"
            autocomplete="username"
            value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>"
        />
    </p>

    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
        <label for="password">
            <?php esc_html_e( 'Password', 'woocommerce' ); ?>&nbsp;<span class="required">*</span>
        </label>
        <input
            placeholder="<?php esc_html_e( 'Password', 'woocommerce' ); ?>"
            class="woocommerce-Input woocommerce-Input--text input-text"
            type="password"
            name="password"
            id="password"
            autocomplete="current-password"
        />
    </p>

    <?php do_action( 'woocommerce_login_form' ); ?>

    <p class="form-row">
        <label class="woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme">
            <input
                class="woocommerce-form__input woocommerce-form__input-checkbox"
                name="rememberme"
                type="checkbox"
                id="rememberme"
                value="forever"
            /> 
            <span><?php esc_html_e( 'Remember me', 'woocommerce' ); ?></span>
        </label>
        <?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>
        <button
            type="submit"
            class="btn woocommerce-button woocommerce-form-login__submit"
            name="login"
            value="<?php esc_attr_e( 'Log in', 'woocommerce' ); ?>"
        ><?php esc_html_e( 'Log in', 'woocommerce' ); ?></button>
    </p>

    <p class="woocommerce-LostPassword lost_password">
        <a href="<?php echo esc_url( wp_lostpassword_url() ); ?>">
            <?php esc_html_e( 'Lost your password?', 'woocommerce' ); ?>
        </a>
    </p>

    <?php do_action( 'woocommerce_login_form_end' ); ?>

</form>