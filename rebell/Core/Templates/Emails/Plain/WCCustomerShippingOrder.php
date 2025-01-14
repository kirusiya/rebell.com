<?php
/**
 * Admin cancelled order email (plain text)
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/admin-cancelled-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see 	    https://docs.woothemes.com/document/template-structure/
 * @author  	WooThemes
 * @package 	WooCommerce/Templates/Emails/Plain
 * @version 	2.5.0
 */

defined( 'ABSPATH' ) or die( );

echo "= " . $email_heading . " =\n\n";

echo sprintf( __( 'The order #%d has been cancelled. The order details:', 'woocommerce' ), $order->id ) . "\n\n";

/* translators: %s: Customer first name */
printf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $order->get_billing_first_name( ) ) );
print "\n\n";

/* translators: %s: Order number */
print __( 'Sólo queríamos informarte de que hemos enviado tu pedido y pronto lo tendrás en tus manos.', 'betheme' );
print "\n\n";

if ( $trackingCode = $order->get_meta( '_seur_shipping_id_number' ) ) {
    printf( __( 'Este es es el código de seguimiento de tu pedido: %s', 'betheme' ), "<strong>$trackingCode</strong>" );
    print "\n\n";
    print "https://www.seur.com/livetracking/pages/seguimiento-online.do?segOnlineIdentificador={$trackingCode}";
    print "\n\n";
}


echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/**
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Emails::order_schema_markup() Adds Schema.org markup.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/**
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/**
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );