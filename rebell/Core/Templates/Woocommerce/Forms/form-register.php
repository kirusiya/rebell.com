<?php

/**
 * Register form
 *
 * @package BeTheme
 * @author  Invbit <info@invbit.com>
 * @link    https://www.invbit.com
 */

defined( 'ABSPATH' ) or die( '¯\_(ツ)_/¯' );

do_action( 'woocommerce_before_customer_login_form' ); ?>

<form
    method="post"
    class="WCAccountForm woocommerce-form woocommerce-form-register register" 
    <?php do_action( 'woocommerce_register_form_tag' ); ?>
>

    <?php do_action( 'woocommerce_register_form_start' ); ?>

    <h3 class="Title">Quiero registrarme</h3>

    <?php if ( 'no' === get_option( 'woocommerce_registration_generate_username' ) ) : ?>

        <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
            <label for="reg_username">
                <?php esc_html_e( 'Username', 'woocommerce' ); ?>&nbsp;<span class="required">*</span>
            </label>
            <input
                placeholder="<?php esc_html_e( 'Username', 'woocommerce' ); ?>"
                type="text"
                class="woocommerce-Input woocommerce-Input--text input-text"
                name="username"
                id="reg_username"
                autocomplete="username"
                value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>"
            />
        </p>

    <?php endif; ?>

    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
        <label for="reg_name">
            <?php esc_html_e( 'Name', 'woocommerce' ); ?>&nbsp;<span class="required">*</span>
        </label>
        <input
            placeholder="<?php esc_html_e( 'Name', 'woocommerce' ); ?>"
            type="text"
            class="woocommerce-Input woocommerce-Input--text input-text"
            name="first_name"
            id="reg_first_name"
            autocomplete="first_name"
            value="<?= ( ! empty( $_POST['first_name'] ) ) ? esc_attr( wp_unslash( $_POST['first_name'] ) ) : ''; ?>"
            required
        />
    </p>

    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
        <label for="reg_phone">
            <?php esc_html_e( 'Phone', 'woocommerce' ); ?>&nbsp;<span class="required">*</span>
        </label>
        <input
            placeholder="<?php esc_html_e( 'Phone', 'woocommerce' ); ?>"
            type="number"
            class="woocommerce-Input woocommerce-Input--text input-text"
            name="phone"
            id="reg_phone"
            autocomplete="phone"
            value="<?= ( ! empty( $_POST['phone'] ) ) ? esc_attr( wp_unslash( $_POST['phone'] ) ) : ''; ?>"
            required
        />
    </p>

    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
        <label for="reg_address">
            <?php esc_html_e( 'Address', 'woocommerce' ); ?>&nbsp;<span class="required">*</span>
        </label>
        <input
            placeholder="<?php esc_html_e( 'Address', 'woocommerce' ); ?>"
            type="text"
            class="woocommerce-Input woocommerce-Input--text input-text"
            name="address"
            id="reg_address"
            autocomplete="address"
            value="<?= ( ! empty( $_POST['address'] ) ) ? esc_attr( wp_unslash( $_POST['address'] ) ) : ''; ?>"
            required
        />
    </p>

    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
        <label for="reg_city">
            <?php esc_html_e( 'City', 'woocommerce' ); ?>&nbsp;<span class="required">*</span>
        </label>
        <input
            placeholder="<?php esc_html_e( 'City', 'woocommerce' ); ?>"
            type="text"
            class="woocommerce-Input woocommerce-Input--text input-text"
            name="city"
            id="reg_city"
            autocomplete="city"
            value="<?= ( ! empty( $_POST['city'] ) ) ? esc_attr( wp_unslash( $_POST['city'] ) ) : ''; ?>"
            required
        />
    </p>

    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
        <label for="reg_zipcode">
            <?php esc_html_e( 'Postcode', 'woocommerce' ); ?>&nbsp;<span class="required">*</span>
        </label>
        <input
            placeholder="<?php esc_html_e( 'Postcode', 'woocommerce' ); ?>"
            type="number"
            class="woocommerce-Input woocommerce-Input--text input-text"
            name="zipcode"
            id="reg_zipcode"
            autocomplete="zipcode"
            value="<?= ( ! empty( $_POST['zipcode'] ) ) ? esc_attr( wp_unslash( $_POST['zipcode'] ) ) : ''; ?>"
            required
        />
    </p>

    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
        <label for="reg_email">
            <?php esc_html_e( 'Email address', 'woocommerce' ); ?>&nbsp;<span class="required">*</span>
        </label>
        <input
            placeholder="<?php esc_html_e( 'Email', 'woocommerce' ); ?>"
            type="email"
            class="woocommerce-Input woocommerce-Input--text input-text"
            name="email"
            id="reg_email"
            autocomplete="email"
            value="<?= ( ! empty( $_POST['email'] ) ) ? esc_attr( wp_unslash( $_POST['email'] ) ) : ''; ?>"
            required
        />
    </p>

    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
        <label for="reg_password">
            <?php esc_html_e( 'Password', 'woocommerce' ); ?>&nbsp;<span class="required">*</span>
        </label>
        <input
            placeholder="<?php esc_html_e( 'Password', 'woocommerce' ); ?>"
            type="password"
            class="woocommerce-Input woocommerce-Input--text input-text"
            name="password"
            id="reg_password"
            autocomplete="new-password"
            value="<?= ( ! empty( $_POST['password'] ) ) ? esc_attr( $_POST['password'] ) : ''; ?>"
            required
        />
    </p>

    <?php do_action( 'woocommerce_register_form' ); ?>

    <p class="woocommerce-form-row form-row">
        <?php wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce' ); ?>
        <button
            type="submit"
            class="btn woocommerce-Button woocommerce-button woocommerce-form-register__submit"
            name="wc_register"
            value="<?php esc_attr_e( 'Register', 'woocommerce' ); ?>"
        >
            <?php esc_html_e( 'Register', 'woocommerce' ); ?>
        </button>
    </p>

    <?php do_action( 'woocommerce_register_form_end' ); ?>

</form>