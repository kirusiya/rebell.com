( function( $ ) {

  /**
   *  Modals.
   */
  const initializeModals = function ( ) {
    MicroModal.init( );

    $( document ).on( 'click', '[data-open-modal]', function( event ) {
      event.preventDefault();

      const productID = $( this ).data( 'open-modal' );

      $('.loadingIcon').removeClass('opacity-0');

      $.ajax({
        url: '/wp-admin/admin-ajax.php',
        type: 'GET',
        data: {
            action: 'get_product_modal_content',
            product_id: productID
        },
        success: function (response) {
            $('#ProductModalHeader').html(response.header);
            $('#ProductModalContent').html(response.content);
            MicroModal.show('ProductModal')
            $('.loadingIcon').addClass('opacity-0');
        },
        error: function () {
            console.error('Error al cargar el contenido del producto.');
            $('.loadingIcon').addClass('opacity-0');
        }
      });
    } );

    $( document ).on( 'click', '[data-close-modal]', function( event ) {
      console.log('dale')
      event.preventDefault();
      MicroModal.close('ProductModal')
    } );
  }

  /**
   *  Add to cart logic.
   */
  const addToCartHandler = function ( ) {

    function ajaxAddToCart( btnElement, product_id, qty, cartItemID = null, ) {
      const data = {
        action       : 'rebell_woocommerce_ajax_add_to_cart',
        product_id   : product_id,
        product_sku  : '',
        quantity     : qty,
        cart_item_id : cartItemID
      };

      $.ajax( {
        type : 'post',
        url  : wc_add_to_cart_params.ajax_url,
        data : data,
        beforeSend: function ( response ) {
          btnElement.removeClass( 'added' ).addClass( 'loading' );
        },
        complete: function ( response ) {
          btnElement.addClass( 'added' ).removeClass( 'loading' );
        },
        success: function ( response ) {
          if ( response.error && response.product_url ) {
            alert( rebell.outOfStock );
            window.location = window.location;
            return;
          }
          $( document.body ).trigger( 'added_to_cart', [ response.fragments, response.cart_hash, btnElement ] );
        }
      } );
    }


    $( 'button[type=submit].ajax_add_to_cart' ).each( function( ) {
      const quantity = $( this ).siblings( '.quantity' );
      if ( quantity.is( ':visible' ) ) {
        $( this ).hide( );
      }
    } );

    /**
     * Handle quantity input action
     */
    $( document ).on( 'click', '[data-qty-action]', function ( ) {
      const action = $( this ).data( 'qty-action' );
      const id     = $( this ).data( 'qty-id' );
      const prodId = $( this ).data( 'product-id' );
      const input  = $( '[data-qty-input=' + id + ']' );

      if ( action == 'minus' && input.val( ) <= 0 ) {
        return;
      }

      if ( action == 'plus' ) {
        input.val( +input.val( ) + 1 );
      } else {
        input.val( input.val( ) > 0 ? +input.val( ) - 1 : 0 );
      }

      if ( input.val( ) > 0 ) {
        // $( `.quantity.${id}` ).show( );
        $( `.quantity.${id} + button.button` ).css( 'display', 'none' );
        $( `.quantity.${id}` ).parent( ).parent( ).addClass( 'added-to-cart' );
      } else {
        // $( `.quantity.${id}` ).hide( );
        $( `.quantity.${id} + button.button` ).css( 'display', 'inline-block' );
        $( `.quantity.${id}` ).parent( ).parent( ).removeClass( 'added-to-cart' );
      }

      if ( prodId ) {
        const name = $( this ).siblings( '.QuantityInput' ).prop( 'name' );
        const cartItemID = name ? name.match(/cart\[(.*)\]\[qty\]/)[1] : null;
  
        ajaxAddToCart( $( this ), prodId, input.val( ), cartItemID );
      }

      input.trigger( 'change' );
    } );

    /**
     * Handle add to cart button
     */
    $( document ).on( 'click', '.add_to_cart_button', function( ) {
      $( this ).siblings( '.quantity' ).css( 'display', 'inline-block' );
      $( this ).css( 'display', 'none' );
      $( this ).siblings( '.quantity' ).children( '.QuantityWrapper' ).children( 'input' ).val( 1 );
    } );

    /**
     * Handle quantity input change
     */
    $( document ).on( 'click', '[data-qty-input]', function( target ) {
      const itemTotal = $( this ).closest( 'tr' ).prev( ).find( '.product-price > .woocommerce-Price-amount' );
      const lineTotal = $( this ).closest( 'tr' ).next( ).find( '.product-total > .woocommerce-Price-amount' );
  
      if ( ! itemTotal.length || ! lineTotal.length ) return;
  
      const price    = itemTotal.html( ).substr( 0, itemTotal.html( ).indexOf( '<' ) ).replace( ',', '.' );
      const currency = lineTotal.html( ).substr( lineTotal.html( ).indexOf( '<' ), lineTotal.html( ).length );
      const total    = ( $( this ).val( ) * price ).toFixed( 2 ).replace( '.', ',' );
  
      lineTotal.html( total + currency );
    } );

    /**
     * Handle add or subtract quantity from cart item
     */
    $( document ).on( 'click', '.AddQuantityToCart, .SubtractQuantityToCart', function( e ) {
      e.preventDefault( );
  
      const $thisButton  = $( this );
      const idinput      = $thisButton.data( 'qty-id' );
      const input        = $( '[data-qty-input=' + idinput + ']' );
      const product_qty  = input.val( );
      const product_id   = $thisButton.data( 'product_id' );

      ajaxAddToCart( $thisButton, product_id, product_qty );
    } );

    /**
     * Handle events
     */
    $( 'body' ).on( 'added_to_cart', function( ) {
      $( 'body' ).addClass( 'AddedToCart' );
      setTimeout( ( ) => { $( 'body' ).removeClass( 'AddedToCart' ); }, 500 );
    } );

  }


  /**
   *  Add to cart a custom product.
   */
  const addCustomProductToCartHandler = function ( ) {

    const handleAddToCartValidation = function ( ) {
      const requiredInput = { };
      $( '.Extras .Options' ).children( 'input[data-required=true]' ).each(( idx, el ) => {
        const inputName = $( el ).attr( 'name' );
        if ( requiredInput.hasOwnProperty( inputName ) && requiredInput[ inputName ] ) {
          return;
        }
        requiredInput[ inputName ] = $( el ).prop( 'checked' );
      } );

      const propErrors = {
        'toppings'           : 'un topping',
        'custom_ingredients' : 'un ingrediente',
        'side_dishes'        : 'un complemento',
        'fries'              : 'unas patatas',
        'sauces'             : 'una salsa',
        'drinks'             : 'una bebida',
        'extras'             : 'un extra',
      }

      return Object.keys( requiredInput )
        .filter( i => !requiredInput[i] )
        .map( err => propErrors[err] )
        .join( ', ' )
        .replace( new RegExp( /(\b,\s\b)(?!.*\1)/ ), ' y ' )
    }

    $( document ).on( 'click', '.addCustomProductToCart', function( e ) {
      e.preventDefault( );
  
      const $thisButton = $( this );
      const quantity    = $thisButton.data( 'qty' );
      const product_id  = $thisButton.data( 'product_id' );
      const action      = 'rebell_woocommerce_ajax_add_to_cart';

      const validationErrors = handleAddToCartValidation( );

      if ( validationErrors ) {
        $( '#ExtrasValidation_Modal #ExtrasValidation_ModalContent').text( `Tienes que elegir ${validationErrors}` );

        return MicroModal.show( 'ExtrasValidation_Modal' );
      }

      let extras = {}
      Object.keys(rebell.customProps).forEach( ( prop ) => {
        const _prop = prop === 'ingredients' ? `custom_${prop}` : prop;
        const selector = $( `[name=${_prop}]` );

        let selected = [ ];
        selector.each( ( index, element ) => {
          if ( $( element ).attr( 'checked' ) === 'checked' ) {
            selected.push({
              name      : $(element).val(),
              price     : $(element).data('price'),
              spiciness : $(element).data('spiciness') || 0,
              selected  : true
            })
          }
        } );
        extras = {...extras, [prop]: selected}
      });

      const request = {
        type : 'post',
        url  : wc_add_to_cart_params.ajax_url,
        data : { action, product_id, quantity, extras }
      }
  
      $.ajax( {
        ...request,
        beforeSend: function ( response ) {
          $thisButton.removeClass( 'added' ).addClass( 'loading' );
        },
        complete: function ( response ) {
          $thisButton.addClass( 'added' ).removeClass( 'loading' );
        },
        success: function ( response ) {
          if ( response.error && response.product_url ) {
            alert( rebell.outOfStock );
            window.location = window.location;
            return;
          }
          $( document.body ).trigger( 'added_to_cart', [ response.fragments, response.cart_hash, $thisButton ] );
        }
      } );
    } );

  }

  /**
   *  My Account > My Coupons
   */
  const myCouponsHandler = function ( ) {

    $( '[data-apply]' ).click( function( ) {
      const $button = $( this );

      if ( $button.is( '.processing' ) ) {
        return false;
      }

      $button.addClass( 'processing' ).block( {
        message: null,
        overlayCSS: {
          background: 'black',
          opacity: 0.6
        }
      } );

      $.ajax( {
        type : 'POST',
        url  : wc_add_to_cart_params.ajax_url,
        data : {
          action      : 'apply_my_coupon',
          security    : $( '[name=apply-coupon-nonce]' ).val( ),
          coupon_code : $button.data( 'apply' )
        },
        success : function( code ) {
          $( '.woocommerce-error, .woocommerce-message' ).remove( );
          $button.removeClass( 'processing' ).unblock( );

          if ( code ) {
            $( '#MessagesBox' ).html( code );
            $( '#MessagesBox' ).slideDown( );
          }

          if ( $( code ).hasClass( 'alert_success' ) ) {
            $( '[data-apply]' ).each(function( ) {
              $( this ).prop( 'disabled', false );
              $( this ).children( '.use' ).removeClass( 'hidden' );
              $( this ).children( '.used' ).addClass( 'hidden' );
            } );
            $button.prop( 'disabled', true );
            $button.children( '.use' ).addClass( 'hidden' );
            $button.children( '.used' ).removeClass( 'hidden' );
          }
        }
      } );
    } );

  }

  /**
   *  Handle order type change and time options.
   */
  const CartOrderTypeHandler = function ( ) {

    $( '.woocommerce-cart .OrderType input[name=order_type]' ).change( function( ) {
      const $input    = $( this );
      const label     = $( `[for=${$input.attr( 'id' )}]` );

      const type      = label.data( 'type' );
      const isOpen    = label.data( 'open' );

      if ( !isOpen ) {
        const message = type === 'delivery'
          ? 'En estos momentos no tenemos servicio a domicilio. ¡Disculpa las molestias!'
          : 'En estos momentos no tenemos servicio para recoger en el local. ¡Disculpa las molestias!';

        $( '#CartErrors_Modal #CartErrors_ModalContent').text( message );
  
        return MicroModal.show( 'CartErrors_Modal' );
      }

      $( '#OrderSchedule .title' ).text( type === 'delivery'
        ? '¿A qué hora quieres recibir tu pedido?'
        : '¿A qué hora quieres recoger tu pedido?'
      );

      const schedules = {
        delivery: $( '#OrderSchedule select' ).data( 'delivery-schedule' ),
        takeaway: $( '#OrderSchedule select' ).data( 'takeaway-schedule' ),
      }

      $( '#OrderSchedule select' ).val( '' );
      $( '#OrderSchedule select option:not(:first)' ).remove( );
      $.each(schedules[type], (i, item) => $( '#OrderSchedule select' ).append( new Option( item, item ) ) );

      $( '#OrderSchedule' ).removeClass( 'hidden' );

    } );

  }

  /**
   *  Handle cart validation before proceeding to checkout.
   */
  const CartValidationHandler = function ( event ) {

    event.preventDefault( );

    const $button = $( this );
    let error;

    if ( $( '.CartAlert' ).length ) {
      error = 'Te faltan ' + $( '.CartAlert' ).data( 'missing' ) + '€ para llegar al pedido mínimo';
    }

    if ( $( '[name=order_type]:checked' ).length <= 0 ) {
      error = 'Debes seleccionar si el pedido es para recoger o para envío a domicilio';
    } else if ( ! $( '[name=schedule]' ).val( ) ) {
      error = 'Debes especificar una hora';
    }

    if ( error ) {
      $( '#CartErrors_Modal #CartErrors_ModalContent').text( error );

      return MicroModal.show( 'CartErrors_Modal' );
    }

    const data = {
      action        : 'rebell_set_order_type_and_schedule',
      order_type    : $( '[name=order_type]:checked' ).val( ),
      scheduled_for : $( '[name=schedule]' ).val( )
    };

    $.ajax( {
      type : 'post',
      url  : wc_add_to_cart_params.ajax_url,
      data : data,
      beforeSend: function ( response ) {
        $button.addClass( 'loading' );
      },
      complete: function ( response ) {
        $button.removeClass( 'loading' );
      },
      error: function ( error ) {
        $( '#CartErrors_Modal #CartErrors_ModalContent').text( error.responseJSON.data.message );

        return MicroModal.show( 'CartErrors_Modal' );
      },
      success: function ( response ) {
        window.location = $button.attr( 'href' );
      }
    } );

  }


  if ( $( '#ProductModal' ).length ) {
    initializeModals( );
  }

  if ( $( '[data-qty-input]' ).length ) {
    addToCartHandler( );
  }
  
  $( document.body ).on( 'updated_cart_totals', function ( ) {
    addToCartHandler( );
  } );

  addCustomProductToCartHandler( );

  if ( $( '.WCAccountCoupons' ).length ) {
    myCouponsHandler( );
  }

  if ( $( '.woocommerce-cart' ).length ) {

    CartOrderTypeHandler( );

    $( document ).on( 'wc_fragments_refreshed', function( ) {
      CartOrderTypeHandler( );
    } );

    $( '.checkout-button' ).click( CartValidationHandler );

    $( '#EmptyCart' ).on( 'click', function( evt ) {
      MicroModal.show( $( evt.target ).data( 'modal-id' ) );
    } );
  }

  // Handle zipcode changes
  if ( $( '#WCUpdateProfile' ).length ) {
    $( '#WCUpdateProfile' ).on( 'submit', function( evt ) {
      evt.preventDefault( );

      const hasCart  = $( this ).data( 'cart-contents' ) > 0;
      const current  = $( '#updt_zipcode' ).data( 'current' );
      const newValue = $( '#updt_zipcode' ).val( );

      if ( hasCart && current != newValue ) {
        const emptyCart = confirm( 'Tienes platos en el carrito que se eliminarán si cambias de código postal, ¿deseas continuar?' );

        if ( ! emptyCart ) {
          return;
        }

        $( this ).append( '<input type="hidden" name="empty_cart" />');
      }

      
      evt.currentTarget.submit();
    } );
  }

} )( jQuery );