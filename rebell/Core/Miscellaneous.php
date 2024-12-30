<?php defined( 'ABSPATH' ) or die( '¯\_(ツ)_/¯' );


function sc_map($attr, $content = null)
{
	extract(shortcode_atts(array(
		'lat' 		=> '',
		'lng' 		=> '',
		'zoom' 		=> 13,
		'height' 	=> 200,

		'type' 		=> 'ROADMAP',
		'controls' 	=> '',
		'draggable' => '',
		'border' 	=> '',

		'icon' 		=> '',
		'styles'	=> '',
		'latlng' 	=> '',

		'title'		=> '',
		'telephone'	=> '',
		'email' 	=> '',
		'www' 		=> '',
		'style' 	=> 'box',

		'uid' 		=> uniqid(),
	), $attr));

	// image | visual composer fix
	$icon = mfn_vc_image($icon);

	// border
	if ($border) {
		$class = 'has_border';
	} else {
		$class = 'no_border';
	}

	// controls
	$zoomControl = $mapTypeControl = $streetViewControl = 'false';
	if (!$controls) $zoomControl = 'true';
	if (strpos($controls, 'zoom') !== false) 		$zoomControl = 'true';
	if (strpos($controls, 'mapType') !== false) 	$mapTypeControl = 'true';
	if (strpos($controls, 'streetView') !== false) 	$streetViewControl = 'true';

	if ($api_key = trim(mfn_opts_get('google-maps-api-key'))) {
		$api_key = '?key=' . $api_key;
	}
	wp_enqueue_script('google-maps-leaflet', get_stylesheet_directory_uri() . '/js/leaflet/leaflet.js', false, null, true);
	wp_enqueue_style('google-maps-leaflet-css', get_stylesheet_directory_uri() . '/js/leaflet/leaflet.css');
	wp_enqueue_script('google-maps', 'http' . mfn_ssl() . '://maps.google.com/maps/api/js' . $api_key, false, null, true);
	wp_enqueue_script('google-maps-leaflet-google', get_stylesheet_directory_uri() . '/js/leaflet/leaflet-google.js', false, null, true);
	$output = '<script>';
	//<![CDATA[
	$output .= 'function google_maps_' . $uid . '(){';

	$output .= 'var latlng = new L.LatLng(' . $lat . ',' . $lng
		. ');  ';

	// draggable
	if ($draggable == 'disable') {
		$output .= 'var draggable = false;';
	} elseif ($draggable == 'disable-mobile') {
		$output .= 'var draggable = jQuery(document).width() > 767 ? true : false;';
	} else {
		$output .= 'var draggable = true;';
	}

	$output .= 'var myOptions = {';
	$output .= 'zoom				: ' . intval($zoom) . ',';
	$output .= 'center				: latlng,';
	$output .= 'mapTypeId			: L.Google.' . $type . ',';
	if ($styles) $output .= 'styles	: ' . $styles . ',';
	$output .= 'draggable			: draggable,';
	$output .= 'zoomControl			: ' . $zoomControl . ',';
	$output .= 'mapTypeControl		: ' . $mapTypeControl . ',';
	$output .= 'streetViewControl	: ' . $streetViewControl . ',';
	$output .= 'scrollwheel			: false';
	$output .= '};';

	$output .= '
var map = new L.Map(document.getElementById("google-map-area-' . $uid . '"), myOptions);

';
	if ($icon) $output .= '
var LeafIcon = L.Icon.extend({     iconSize:     [38, 95],
        shadowSize:   [50, 64],
        iconAnchor:   [22, 94],
        shadowAnchor: [4, 62],
        popupAnchor:  [-3, -76]});
var Iconver = new LeafIcon({iconUrl: \'' .   $icon . '\' });
';

	/*$output .= 'var marker = new L.Marker({';
					$output .= 'position			: latlng,';
 					if( $icon ) $output .= 'icon	: "'. $icon .'",';
                    $output .= 'map					: map';
    
                $output .= '});';*/
	if ($icon)
		$output .= '
        L.marker([latlng.lat, latlng.lng], {icon: Iconver}).addTo(map);';
	else
		$output .= '
        L.marker([latlng.lat, latlng.lng]).addTo(map);';
	$output .= '
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png").addTo(map);
    ';

	// additional markers
	if ($latlng) {

		// remove white spaces
		$latlng = str_replace(' ', '', $latlng);

		// explode array
		$latlng = explode(';', $latlng);

		foreach ($latlng as $k => $v) {
			$vEx = explode(',', $v);
			if (isset($vEx[2])) {
				$customIcon = $vEx[2];
			} else {
				$customIcon = $icon;
			}

			$output .= 'latlng.lat = '.$vEx[0].';';
			$output .= 'latlng.lng = '.$vEx [1].';'; 
			if ($icon)
				$output .= 'L.marker([latlng.lat, latlng.lng], {icon: Iconver}).addTo(map);';
			else
				$output .= 'L.marker([latlng.lat, latlng.lng]).addTo(map);';


			$markerID = $k + 1;
			//$markerID = 'marker' . $markerID;

			// custom marker icon
		


		



		//	$output .= 'var ' . $markerID . ' = new L.Marker({';
		//	$output .= 'position			: new L.LatLng(' . $vEx[0] . ',' . $vEx[1] . '),';
		//	if ($customIcon) $output .= 'icon	: "' . $customIcon . '",';
		//	$output .= 'map					: map';
		//	$output .= '});';
		}
	}

	$output .= '}
            ';

	$output .= 'jQuery(document).ready(function($){
                ';
	$output .= 'google_maps_' . $uid . '();
                ';
	$output .= '});
            ';
	//]]>
	$output .= '</script>' . "\n";

	$output .= '<div class="google-map-wrapper ' . $class . '">';

	if ($title || $content) {
		$output .= '<div class="google-map-contact-wrapper style-' . esc_attr($style) . '">';
		$output .= '<div class="get_in_touch">';
		if ($title) $output .= '<h3>' . $title . '</h3>';
		$output .= '<div class="get_in_touch_wrapper">';
		$output .= '<ul>';
		if ($content) {
			$output .= '<li class="address">';
			$output .= '<span class="icon"><i class="icon-location"></i></span>';
			$output .= '<span class="address_wrapper">' . do_shortcode($content) . '</span>';
			$output .= '</li>';
		}
		if ($telephone) {
			$output .= '<li class="phone">';
			$output .= '<span class="icon"><i class="icon-phone"></i></span>';
			$output .= '<p><a href="tel:' . str_replace(' ', '', $telephone) . '">' . $telephone . '</a></p>';
			$output .= '</li>';
		}
		if ($email) {
			$output .= '<li class="mail">';
			$output .= '<span class="icon"><i class="icon-mail"></i></span>';
			$output .= '<p><a href="mailto:' . $email . '">' . $email . '</a></p>';
			$output .= '</li>';
		}
		if ($www) {
			$output .= '<li class="www">';
			$output .= '<span class="icon"><i class="icon-link"></i></span>';
			$output .= '<p><a target="_blank" href="http' . mfn_ssl() . '://' . $www . '">' . $www . '</a></p>';
			$output .= '</li>';
		}
		$output .= '</ul>';
		$output .= '</div>';
		$output .= '</div>';
		$output .= '</div>';
	}

	$output .= '<div class="google-map" id="google-map-area-' . $uid . '" style="width:100%; height:' . intval($height) . 'px;">&nbsp;</div>';

	$output .= '</div>' . "\n";

	return $output;
}