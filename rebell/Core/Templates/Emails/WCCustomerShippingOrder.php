<?php defined( 'ABSPATH' ) or die( ); ?>

<?php do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<p><?= /* translators: %s: Customer first name */
    sprintf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $order->get_billing_first_name( ) ) );
?></p>

<p><?= /* translators: %s: Order number */
    __( 'Sólo queríamos informarte de que hemos enviado tu pedido y pronto lo tendrás en tus manos.', 'betheme' )
?></p>

<?php if ( $trackingCode = $order->get_meta( '_seur_shipping_id_number' ) ) : ?>
    <p><?=
        sprintf( __( 'Este es es el código de seguimiento de tu pedido: %s', 'betheme' ), "<strong>$trackingCode</strong>" )
    ?></p>
    <p>
        <a
            href="https://www.seur.com/livetracking/pages/seguimiento-online.do?segOnlineIdentificador=<?= $trackingCode ?>"
        ><?= __( 'Ampliar seguimiento', 'betheme' ) ?></a>
    </p>
<?php endif; ?>

<?php do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email ); ?>

<?php do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email ); ?>

<?php do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email ); ?>

<?=  $additional_content ? wp_kses_post( wpautop( wptexturize( $additional_content ) ) ) : null ?>

<?php do_action( 'woocommerce_email_footer', $email ); ?>
