jQuery(document).ready(function() {


var grande = 0;
	jQuery( ".woocommerce-customer-details address" ).each(function( index ) {
	if(jQuery( this ).height() > grande){

		grande = jQuery( this ).height();
	}

jQuery( ".woocommerce-customer-details address" ).height(grande);


});



	jQuery('.mobile-menu-ac').click(function() {



	if(jQuery('.woocommerce-MyAccount-navigation').hasClass('open')){

			jQuery('.woocommerce-MyAccount-navigation').removeClass('open');
			jQuery('.mobile-menu-ac').removeClass('close');


		}else{

		jQuery('.mobile-menu-ac').addClass('close');
		jQuery('.woocommerce-MyAccount-navigation').addClass('open');

		}
});
});
