<?php

/**
 * Update profile form
 *
 * @package BeTheme
 * @author  Invbit <info@invbit.com>
 * @link    https://www.invbit.com
 */

defined( 'ABSPATH' ) or die( '¯\_(ツ)_/¯' );

$customer = new WC_Customer( get_current_user_id( ) );
$name     = ! empty( $_POST['name'] ) ? esc_attr( wp_unslash( $_POST['name'] ) ) : $customer->get_first_name( );
$address  = ! empty( $_POST['address'] ) ? esc_attr( wp_unslash( $_POST['address'] ) ) : $customer->get_billing_address( );
$city     = ! empty( $_POST['city'] ) ? esc_attr( wp_unslash( $_POST['city'] ) ) : $customer->get_billing_city( );
$zipcode  = ! empty( $_POST['zipcode'] ) ? esc_attr( wp_unslash( $_POST['zipcode'] ) ) : $customer->get_billing_postcode( );
$phone    = ! empty( $_POST['phone'] ) ? esc_attr( wp_unslash( $_POST['phone'] ) ) : $customer->get_billing_phone( );
$phone    = str_replace( ' ', '', $phone );
$email    = ! empty( $_POST['email'] ) ? esc_attr( wp_unslash( $_POST['email'] ) ) : $customer->get_email( );
?>

<form
    action="<?= admin_url( 'admin-post.php' ); ?>"
    method="post"
    class="WCAccountForm woocommerce-form" 
    id="WCUpdateProfile"
    data-cart-contents="<?= WC( )->cart->get_cart_contents_count( ) ?>"
    <?php do_action( 'woocommerce_update_profile_form_tag' ); ?>
>

    <?php do_action( 'woocommerce_update_profile_form_start' ); ?>

    <h3 class="Title">Editar Perfil</h3>

    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
        <label for="updt_name">
            <?php esc_html_e( 'Name', 'woocommerce' ); ?>&nbsp;<span class="required">*</span>
        </label>
        <input
            placeholder="<?php esc_html_e( 'Name', 'woocommerce' ); ?>"
            type="text"
            class="woocommerce-Input woocommerce-Input--text input-text"
            name="name"
            id="updt_name"
            autocomplete="name"
            value="<?= $name ?>"
        />
    </p>

    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
        <label for="updt_address">
            <?php esc_html_e( 'Address', 'woocommerce' ); ?>&nbsp;<span class="required">*</span>
        </label>
        <input
            placeholder="<?php esc_html_e( 'Address', 'woocommerce' ); ?>"
            type="text"
            class="woocommerce-Input woocommerce-Input--text input-text"
            name="address"
            id="updt_address"
            autocomplete="address"
            value="<?= $address ?>"
        />
    </p>

    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
        <label for="updt_city">
            <?php esc_html_e( 'City', 'woocommerce' ); ?>&nbsp;<span class="required">*</span>
        </label>
        <input
            placeholder="<?php esc_html_e( 'City', 'woocommerce' ); ?>"
            type="text"
            class="woocommerce-Input woocommerce-Input--text input-text"
            name="city"
            id="updt_city"
            autocomplete="city"
            value="<?= $city ?>"
        />
    </p>

    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
        <label for="updt_zipcode">
            <?php esc_html_e( 'Postcode', 'woocommerce' ); ?>&nbsp;<span class="required">*</span>
        </label>
        <input
            placeholder="<?php esc_html_e( 'Postcode', 'woocommerce' ); ?>"
            type="number"
            class="woocommerce-Input woocommerce-Input--number input-number"
            name="zipcode"
            id="updt_zipcode"
            autocomplete="zipcode"
            data-current="<?= $zipcode ?>"
            value="<?= $zipcode ?>"
        />
        <?php if ( $_SESSION['errors']['zipcode'] ) : ?>
        <span class="FieldErrorMessage"><?= $_SESSION['errors']['zipcode'] ?></span>
        <?php endif; ?>
    </p>

    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
        <label for="updt_phone">
            <?php esc_html_e( 'Phone', 'woocommerce' ); ?>&nbsp;<span class="required">*</span>
        </label>
        <input
            placeholder="<?php esc_html_e( 'Phone', 'woocommerce' ); ?>"
            type="number"
            class="woocommerce-Input woocommerce-Input--number input-number"
            name="phone"
            id="updt_phone"
            autocomplete="phone"
            value="<?= $phone ?>"
        />
    </p>

    <?php do_action( 'woocommerce_update_profile_form' ); ?>

    <p class="woocommerce-form-row form-row">
        <input type="hidden" name="action" value="rebell_update_customer_profile">
        <?php wp_nonce_field( 'woocommerce-update-profile', 'woocommerce-update-profile-nonce' ); ?>
        <button
            type="submit"
            class="btn woocommerce-Button woocommerce-button woocommerce-form-update-profile__submit px-20 py-3 mt-5"
            name="update-profile"
            value="<?php esc_attr_e( 'Save', 'woocommerce' ); ?>"
        >
            <?php esc_html_e( 'Save', 'woocommerce' ); ?>
        </button>
    </p>

    <h3 class="Title mt-9">Mi cuenta</h3>

    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
        <label for="updt_email">
            <?php esc_html_e( 'Email address', 'woocommerce' ); ?>&nbsp;<span class="required">*</span>
        </label>
        <input
            placeholder="<?php esc_html_e( 'Email', 'woocommerce' ); ?>"
            type="email"
            class="woocommerce-Input woocommerce-Input--email input-email"
            name="email"
            id="updt_email"
            autocomplete="email"
            value="<?= $email ?>"
            disabled
        />
    </p>

    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
        <a class="ModifyPasswordBtn" href="<?= esc_url( wp_lostpassword_url( ) ) ?>">Cambiar la contraseña &gt;</a>
    </p>

    <?php do_action( 'woocommerce_update_profile_form_end' ); ?>

</form>