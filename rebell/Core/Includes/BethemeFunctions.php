<?php defined( 'ABSPATH' ) or die( '¯\_(ツ)_/¯' );

/**
 * Extend Betheme parent theme's functions.
 *
 * @package Betheme
 * @author  Invbit
 * @link    https://invbit.com
 */

 /* ---------------------------------------------------------------------------
  * Replace script parent theme
  * --------------------------------------------------------------------------- */

 add_action('wp_enqueue_scripts', 'custom_scripts_parent', 100);
 function custom_scripts_parent()
 {
    wp_dequeue_script('jquery-scripts');
 }


 add_action( 'wp_footer', 'cart_update_qty_script', 1000);
 function cart_update_qty_script() {
     if (is_cart()) :
         ?>
         <script type="text/javascript">
                 jQuery(document).ready(function( $ ) {
			// Enable update cart button upon successful ajax call
 			$(document).ajaxSuccess(function() {
 			$( 'div.woocommerce > form button[name="update_cart"]' ).prop( 'disabled', false );
 		});
 		// Enable update cart button on initial page load
 		$( 'div.woocommerce > form button[name="update_cart"]' ).prop( 'disabled', false );

 		// Update cart when quantity pulldown is changed
 		$('body').on('change', '.Qty', function () {
		                    var quantity_selected = $("#quantity_pulldown option:selected").val();
 		       $('#product_quantity').val(quantity_selected);

 		       jQuery("[name='update_cart']").removeAttr('disabled');
 		       jQuery("[name='update_cart']").trigger("click");

 	       });

 	});

       </script>
         <?php
     endif;
 }

/**
 * Single Post Navigation | GET header navigation
 */
if ( ! function_exists( 'mfn_post_navigation_header' ) ) {

	function mfn_post_navigation_header( $post_prev, $post_next, $post_home, $translate = [ ] ) {

		$style = mfn_opts_get( 'prev-next-style' );

		$prevLink = get_permalink( $post_prev );
		$nextLink = get_permalink( $post_next );
		$homeLink = is_numeric( $post_home ) ? get_permalink( $post_home ) : $post_home;

		ob_start( ); ?>

		<div class="column one post-nav <?= esc_attr( $style ) ?>">

		<?php if ( $style == 'minimal' ) : ?>

			<?php if ( $post_prev ) : ?>
				<a class="prev" href="<?= esc_url( $prevLink ) ?>">
					<i class="icon icon-left-open-big"></i>
				</a>
			<?php endif; ?>

			<?php if ( $post_next ) : ?>
				<a class="next" href="<?= esc_url( $nextLink ) ?>">
					<i class="icon icon-right-open-big"></i>
				</a>
			<?php endif; ?>

			<?php if ( $post_home ) : ?>
				<a class="home" href="<?= esc_url( $homeLink ) ?>">
					<svg class="icon" width="22" height="22" xmlns="https://www.w3.org/2000/svg">
						<path d="M7,2v5H2V2H7 M9,0H0v9h9V0L9,0z"/>
						<path d="M20,2v5h-5V2H20 M22,0h-9v9h9V0L22,0z"/>
						<path d="M7,15v5H2v-5H7 M9,13H0v9h9V13L9,13z"/>
						<path d="M20,15v5h-5v-5H20 M22,13h-9v9h9V13L22,13z"/>
					</svg>
				</a>
			<?php endif; ?>


		<?php else : ?>

			<ul class="next-prev-nav">

				<?php if ( $post_prev ) : ?>
					<li class="prev">
						<a class="button button_js" href="<?= esc_url( $prevLink ) ?>">
							<span class="button_icon"><i class="icon-left-open"></i></span>
						</a>
					</li>
				<?php endif; ?>

				<?php if ( $post_next ) : ?>
					<li class="next">
						<a class="button button_js" href="<?= esc_url( $nextLink ) ?>">
							<span class="button_icon"><i class="icon-right-open"></i></span>
						</a>
					</li>
				<?php endif; ?>

			</ul>

			<?php if ( $post_home ) : ?>
				<a class="list-nav" href="'. esc_url( $homeLink ) .'"><i class="icon-layout"></i>'. esc_html( $translate['all'] ) .'</a>
			<?php endif; ?>

		<?php endif; ?>

		</div>

		<?php return ob_get_clean( );

	}
}




/* ---------------------------------------------------------------------------
 * Slides [slides]
 * --------------------------------------------------------------------------- */
if( ! function_exists( 'sc_slider' ) )
{
	function sc_slider( $attr, $content = null )
	{
		extract(shortcode_atts(array(

			'category' 		=> '',
			'orderby' 		=> 'date',
			'order' 		=> 'DESC',
			'style' 		=> '',		// [default], img-text, flat, carousel
			'navigation'	=> '',
		), $attr));

		$args = array(
			'post_type' 			=> 'slide',
			'posts_per_page' 		=> -1,
			'paged' 				=> -1,
			'orderby' 				=> $orderby,
			'order' 				=> $order,
			'ignore_sticky_posts' 	=> 1,
		);
		if( $category ) $args['slide-types'] = $category;

		$query = new WP_Query();
		$query->query( $args );
		$post_count = $query->post_count;

		// class
		$class = $style;
		if( $class == 'description' ) $class .= ' flat';
		if( $navigation )	$class .= ' '. $navigation;

		$output = '';
		if ($query->have_posts())
		{
			$output .= '<div class="content_slider '. $class .'">';
				$output .= '<ul class="content_slider_ul">';
					$i = 0;
					while ($query->have_posts())
					{
						$query->the_post();
						$i++;

						$output .= '<li class="content_slider_li_'. $i .'">';

							$link = get_post_meta(get_the_ID(), 'mfn-post-link', true);
							if( get_post_meta(get_the_ID(), 'mfn-post-target', true) ){
								$target = ' target="_blank"';
							} else {
								$target = false;
							}

							if( $link ) $output .= '<a href="'. $link .'" '. $target .'>';

								$output .= get_the_post_thumbnail( null, 'slider-content', array('class'=>'scale-with-grid' ) );



									$output .= '<h3 class="title">'. get_the_title( get_the_ID() ) .'</h3>';
									if( $desc = get_post_meta(get_the_ID(), 'mfn-post-desc', true) ){
										$output .= '<div class="desc">'. do_shortcode( $desc ) .'</div>';

								}

							if( $link ) $output .= '</a>';

						$output .= '</li>';
					}
				$output .= '</ul>';

				$output .= '<div class="slider_pager slider_pagination"></div>';

			$output .= '</div>'."\n";
		}
		wp_reset_query();

		return $output;
	}
}


/* ---------------------------------------------------------------------------
 * sweet alert
 * --------------------------------------------------------------------------- */
add_action('wp_enqueue_scripts', 'register_sweetalert_assets');
function register_sweetalert_assets() {
    wp_enqueue_script('sweetalert', 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js');
    wp_enqueue_style('sweetalert', 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css');
}

add_action('wp_head', 'custom_css_at_end', 100);
function custom_css_at_end() {
    echo '<link rel="stylesheet" href="' . get_template_directory_uri() . '/Core/Assets/Styles/custom.css" type="text/css" media="all">';
}
function add_stylesheet_to_head() {
    ?>
    <style>
    div:where(.swal2-container) button:where(.swal2-styled):where(.swal2-confirm) {
        background-color: #f0e74c;
    }    
    </style>
    <?php
    
}
    
add_action( 'wp_head', 'add_stylesheet_to_head' );
