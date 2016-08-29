<?php

class SPP_Utils_Color {

	/**
	 * [get_brightness description]
	 * @param  string $hex A valid hex color, with or without the #
	 * @return float Brightness between 0-1 
	 */
	public static function get_brightness( $hex ) {

		// Just make sure there are no pound signs on the hex
		$hex = str_replace( '#', '', $hex );

		//break up the color in its RGB components
		$r = hexdec(substr($hex,0,2));
		$g = hexdec(substr($hex,2,2));
		$b = hexdec(substr($hex,4,2));
		
		// Brightness formula.  This treats all colors equally, but shouldn't
		// be changed because users' players will change as a result.
		return ($r + $g + $b) / 765;

	}

	/**
	 * Lighten or darken the hex color
	 * 
	 * @param  string $color  hex string
	 * @param  float $amount Works best with 0-3
	 * @return string hex color
	 */
	public static function tint_hex( $color, $amount ) {

		$color_r = substr( str_replace( '#', '', $color ), 0, 2 );
		$color_r_hover = dechex ( floor( hexdec( $color_r ) * $amount ) );
		$color_r_hover = hexdec( $color_r_hover ) > 255 ? dechex( 255 ) : $color_r_hover;

		$color_g = substr( str_replace( '#', '', $color ), 2, 2 );
		$color_g_hover = dechex ( floor( hexdec( $color_g ) * $amount ) );
		$color_g_hover = hexdec( $color_g_hover ) > 255 ? dechex( 255 ) : $color_g_hover;

		$color_b = substr( str_replace( '#', '', $color ), 4, 2 );
		$color_b_hover = dechex ( floor( hexdec( $color_b ) * $amount ) );
		$color_b_hover = hexdec( $color_b_hover ) > 255 ? dechex( 255 ) : $color_b_hover;

		return '#' . str_pad( $color_r_hover, 2, '0', STR_PAD_LEFT ) . str_pad( $color_g_hover, 2, '0', STR_PAD_LEFT ) . str_pad( $color_b_hover, 2, '0', STR_PAD_LEFT );

	}
	
	/**
	 * Adds or subtracts $amount from the $color.  Saturates on the end.
	 * $amount is specified as a string of hex digits, possibly preceded with '-'.
	 * Examples: add_hex( '#555555', '-101010' ) returns '454545'
	 *           add_hex( 'fafafa', '101010' ) returns 'ffffff'
	 */
	public static function add_hex( $color, $amount ) {
	
		$color = str_replace( '#', '', $color );
		$amount = str_replace( '#', '', $amount );
		$subtract = substr( $amount, 0, 1 ) === '-';
		$amount = str_replace( '-', '', $amount );
		
		$color_r = hexdec( substr( $color, 0, 2 ) );
		$amount_r = hexdec( substr( $amount, 0, 2) );
		$result_r = $subtract ? $color_r - $amount_r : $color_r + $amount_r;
		if( $result_r > 255 ) {
			$result_r = dechex( 255 );
		} else if ($result_r < 0 ) {
			$result_r = dechex( 0 );
		} else {
			$result_r = dechex( $result_r );
		}
		
		$color_g = hexdec( substr( $color, 2, 2 ) );
		$amount_g = hexdec( substr( $amount, 0, 2) );
		$result_g = $subtract ? $color_g - $amount_g : $color_g + $amount_g;
		if( $result_g > 255 ) {
			$result_g = dechex( 255 );
		} else if ($result_g < 0 ) {
			$result_g = dechex( 0 );
		} else {
			$result_g = dechex( $result_g );
		}
		
		$color_b = hexdec( substr( $color, 4, 2 ) );
		$amount_b = hexdec( substr( $amount, 0, 2) );
		$result_b = $subtract ? $color_b - $amount_b : $color_b + $amount_b;
		if( $result_b > 255 ) {
			$result_b = dechex( 255 );
		} else if ($result_g < 0 ) {
			$result_b = dechex( 0 );
		} else {
			$result_b = dechex( $result_b );
		}
		
		return str_pad( $result_r, 2, '0', STR_PAD_LEFT )
			 . str_pad( $result_g, 2, '0', STR_PAD_LEFT )
			 . str_pad( $result_b, 2, '0', STR_PAD_LEFT );
	}

	/**
	 * Is this a valid six digit hex color?
	 * 
	 * @param  string  $color 
	 * @return boolean
	 */
	public static function is_hex( $color ) {

		$is_color = false;

		if( preg_match('/^#[a-f0-9]{6}$/i', $color) || preg_match('/^[a-f0-9]{6}$/i', $color) ) { 
		    $is_color = true;
		} 

		return $is_color;

	}

	/**
	 * Convert a hex color to HSL values for color math
	 * 
	 * @param  string $hex hexidecimal color
	 * @return array( hue, saturation, lambda )
	 */
	public static function hex_to_hsl( $hex ) {
		
		$oldR = hexdec(substr($hex,0,2));
	    $oldG = hexdec(substr($hex,2,2));
	    $oldB = hexdec(substr($hex,4,2));

		$var_R = $oldR / 255;
		$var_G = $oldG / 255;
		$var_B = $oldB / 255;

		$var_Min = min($var_R, $var_G, $var_B);
		$var_Max = max($var_R, $var_G, $var_B);
		$del_Max = $var_Max - $var_Min;

		$V = $var_Max;

		if ($del_Max == 0) {

		  $H = 0;
		  $S = 0;

		} else {

		  $S = $del_Max / $var_Max;

		  $del_R = ( ( ( $var_Max - $var_R ) / 6 ) + ( $del_Max / 2 ) ) / $del_Max;
		  $del_G = ( ( ( $var_Max - $var_G ) / 6 ) + ( $del_Max / 2 ) ) / $del_Max;
		  $del_B = ( ( ( $var_Max - $var_B ) / 6 ) + ( $del_Max / 2 ) ) / $del_Max;

		  if      ($var_R == $var_Max) $H = $del_B - $del_G;
		  else if ($var_G == $var_Max) $H = ( 1 / 3 ) + $del_R - $del_B;
		  else if ($var_B == $var_Max) $H = ( 2 / 3 ) + $del_G - $del_R;

		  if ($H<0) $H++;
		  if ($H>1) $H--;
		}

		$hsl = array( $H, $S, $V );

		return $hsl;

	}

}
