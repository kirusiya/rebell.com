/**
 * Admin Scripts.
 *
 * @author  Invbit <info@invbit.com>
 * @link    https://www.invbit.com
 */

jQuery(document).ready(function ($) {
  new WPCustomActions();
  new OrdersListController($);
});


/**
 *  Add custom WP admin actions
 */
class WPCustomActions {

  constructor() {
    jQuery('.CancelOrderButton').on('click', this.handleCancelOrder.bind(this));
  }

  handleCancelOrder(evt) {
    evt.preventDefault();

    const cancel_reason = jQuery(evt.target).siblings('.CancelOrderReason').val();
    const order_id = jQuery(evt.target).siblings('.CancelOrderID').val();
    const _wp_http_referer = jQuery('form > [name=_wp_http_referer]').val();

    if (!cancel_reason) {
      alert('Por favor, introduce un motivo para cancelar el pedido');
      return;
    }

    this._sendAction('rebell_cancel_order', { cancel_reason, order_id, _wp_http_referer });
  }

  _sendAction(action, params) {
    let formInputs = `<input type="hidden" name="action" value="${action}">`;

    jQuery.each(params, function (key, value) {
      let val = "";
      if (typeof value === 'string') {
        val = value.split('"').join('"');
        formInputs += `<input type="hidden" name="${key}" value="${val}">`;
      } else {
        value.map((input, id) => {
          val = input.split('"').join('"');
          formInputs += `<input type="hidden" name="${key}[${id}]" value="${val}">`;
        });
      }
    });

    jQuery(`<form action="${adminConfig.postUrl}" method="POST">${formInputs}</form>`)
      .appendTo(jQuery(document.body))
      .submit();
  }

  _getInputsDatesAndIDs(className) {
    const values = [];

    jQuery(className).each(function (idx, input) {
      const id = input.getAttribute('data-id');
      if (input.value) {
        values[id] = input.value;
      }
    });

    return values;
  }

}


/**
 *  Handle tickets and tiders @ order's list
 */
class OrdersListController {
  constructor($) {
    // Handle the ticket printing.
    $('.PrintTicketBtn').on('click', function (evt) {
      evt.preventDefault();

      const button   = $(this);
      const orderID  = button.data('order-id');
      const endpoint = button.data('endpoint');
      const referrer = button.data('referrer');
      const nonce    = button.data('nonce');

      $(`<form action="${endpoint}" method="POST" target="_blank">
        <input type="hidden" name="action" value="rebell_print_order_receipt">
        <input type="hidden" name="order_id" value="${orderID}">
        <input type="hidden" name="rebell_request_referrer" value="${referrer}">
        <input type="hidden" id="rebell-form-nonce" name="rebell-form-nonce" value="${nonce}" />
      </form>` ).appendTo($(document.body)).submit();
    });

    // Avoid opening the order page.
    $('.rider-finder > .wc-customer-search + .select2').on('click', function (evt) {
      evt.stopPropagation()
    });

    // Handle the rider assigning
    $('.rider-finder > button.assign').on('click', function (evt) {
      evt.stopPropagation()

      const button   = $(this);
      const riderID  = button.parent().children('[name=_selected_rider]').val();
      const orderID  = button.data('order-id');
      const endpoint = button.data('endpoint');
      const referrer = button.data('referrer');
      const nonce    = button.data('nonce');

      $(`<form action="${endpoint}" method="POST">
        <input type="hidden" name="action" value="rebell_assign_order_to_rider">
        <input type="hidden" name="rider_id" value="${riderID}">
        <input type="hidden" name="order_id" value="${orderID}">
        <input type="hidden" name="rebell_request_referrer" value="${referrer}">
        <input type="hidden" id="rebell-form-nonce" name="rebell-form-nonce" value="${nonce}" />
      </form>` ).appendTo($(document.body)).submit();
    });
  }
}