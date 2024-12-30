<?php namespace Invbit\Core;

defined( 'ABSPATH' ) or die( );

if ( ! class_exists( 'WC_Email' ) ) return;

/**
 * Add new email.
 *
 * @package Betheme
 * @author  Invbit
 * @link    https://invbit.com
 */
class WCCustomerShippingOrder extends \WC_Email {

	/**
	 * 	Constructor
	 */
	public function __construct( ) {

    	// Email slug we can use to filter other data.
		$this->id          		= 'wc_customer_shipping_order';
		$this->title       		= __( 'Pedido Enviado al Cliente', 'betheme' );
		$this->description 		= __( 'Un email enviado cuando el estado del pedido cambia a enviado.', 'betheme' );

    	// For admin area to let the user know we are sending this email to customers.
		$this->customer_email 	= true;
		$this->heading     		= __( 'Pedido Enviado', 'betheme' );

		// translators: placeholder is {blogname}, a variable that will be substituted when email is sent out
		$this->subject     		= __( 'Pedido enviado', 'betheme' );

    	// Template paths.
		$this->template_base    = get_stylesheet_directory( ) . '/Core/Templates/';
		$this->template_html    = 'Emails/WCCustomerShippingOrder.php';
		$this->template_plain   = 'Emails/Plain/WCCustomerShippingOrder.php';
    
    	// Notify customer about the shipment.
		add_action( 'woocommerce_order_status_seur-shipment', [ $this, 'trigger' ] );

		parent::__construct( );

	}


  	/**
	 * 	Trigger Function that will send this email to the customer.
	 */
	public function trigger( $order_id ) {

		// Uncomment the following line to send the email.
		// $this->notifyShippedOrderByEmail( $order_id );
		$this->sendPushNotificationAboutShippedOrder( $order_id );

	}

  	/**
	 * 	Send email to the customer.
	 */
	private function notifyShippedOrderByEmail( $order_id ) {

		$this->object = wc_get_order( $order_id );

		$this->recipient = version_compare( '3.0.0', WC( )->version, '>' )
			? $this->object->billing_email
			: $this->object->get_billing_email( );

		if ( ! $this->is_enabled( ) or ! $this->get_recipient( ) ) return;

		$this->send(
			$this->get_recipient( ),
			$this->get_subject( ),
			$this->get_content( ),
			$this->get_headers( ),
			$this->get_attachments( )
		);

	} 


  	/**
	 * 	Push notification to user's device.
	 */
	private function sendPushNotificationAboutShippedOrder( $order_id ) {

		$order = wc_get_order( $order_id );

		if ( ! get_user_meta( $order->get_user( )->ID, 'player_id' ) ) return;

		$tracking = $order->get_meta( '_seur_shipping_id_number' );
		$title    = __( 'Pedido enviado!', 'betheme' );
		$content  = $tracking
			? sprintf( __( 'El número de seguimiento de tu envío es %s. Recuerda que puedes hacer el seguimiento desde Cuenta > Mis Pedidos.', 'betheme' ), $tracking )
			: __( 'Hemos realizado el envío de tu pedido y pronto te llegará. Gracias por tu compra!', 'betheme' );
		$push = PushController::getInstance( );
		$push->sendNotification( [ $order->get_user( )->user_login ], $title, $content, 'orders' );

	}


  	/**
	 * 	Get content html.
	 */
	public function get_content_html( ) {

		return wc_get_template_html( $this->template_html, [
			'order'         => $this->object,
			'email_heading' => $this->get_heading( ),
			'sent_to_admin' => false,
			'plain_text'    => false,
			'email'			=> $this
		], '', $this->template_base );

	}


	/**
	 * 	Get content plain.
	 */
	public function get_content_plain( ) {

		return wc_get_template_html( $this->template_plain, [
			'order'         => $this->object,
			'email_heading' => $this->get_heading( ),
			'sent_to_admin' => false,
			'plain_text'    => true,
			'email'			=> $this
		], '', $this->template_base );

	}

}