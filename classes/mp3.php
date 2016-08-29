<?php

if( !class_exists( 'getID3' ) )
	require_once( SPP_PLUGIN_BASE . '/includes/getid3/getid3.php' );

class SPP_MP3 {
	
	const DEFAULT_BUFFER = 786432; // 768 kb

	public static function get_data( $url_in ) {

		$method = self::get_method();

		$data = array();

		$url = self::safe_spp_url( $url_in );

		if ( empty( $url ) )
			return $data;
				
		if( $method == 'curl' ) {
			$data = self::get_curl_data( $url );
		}

		if( $method == 'fopen' ) {
			$data = self::get_fopen_data( $url );
		}
		
		return $data;

	}

	public function safe_spp_url ( $url = null ) {

		/*if ( preg_match( '/\.php$/i', $url ) )
		return false;*/

		if( strpos( strtolower( $url ), 'http' ) !== 0 ) {
			$clean = trailingslashit( home_url() ). $url;
			$url = $clean;
		}
		return esc_url_raw( str_replace('../', '', $url ), array('http','https') );

	}

	private static function get_method() {

		return function_exists( 'curl_version' ) ? 'curl' : ( ini_get('allow_url_fopen') ? 'fopen' : false );
		// return ini_get('allow_url_fopen') ? 'fopen' : (  function_exists( 'curl_version' ) ? 'curl' : false );
			
	}

	private static function get_curl_data( $url ) {

		$data = array();
		$file = self::get_curl_file_segment( $url );

		if( $file ) {
			$data = self::get_id3_data( $file );
			unlink( $file );
		}

		return $data;

	}

	private static function get_fopen_data( $url ) {

		$data = array();
		$file = self::get_fopen_file_segment( $url );

		if( $file ) {
			$data = self::get_id3_data( $file );
			unlink( $file );
		}

		return $data;

	}

	private static function get_curl_file_segment( $url ) {

	    $file = tempnam( SPP_PLUGIN_BASE . 'classes/tmp/', "mp3_");

	    if( !is_writable( $file ) )
	    	return false;

		$fh = fopen( $file, 'w+' );

		$ch = curl_init();
		
		// If we're running under Windows, then PHP's maximum script
		// execution time includes OS calls.  This is bad news for
		// running downloads, as it will likely time out.  Here, we
		// reduce our curl timeout to something less than PHP will give
		// us. 8 seconds should be plenty to run the rest of the script.
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			$timeout = ini_get('max_execution_time') - 8;
		} else {
			$timeout = 60;
		}

		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_TIMEOUT, $timeout);
		curl_setopt( $ch, CURLOPT_HEADER, 0 ); 
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_FILE, $fh );

		$max_redirects = 10;

		if (ini_get('open_basedir') === '' && ini_get('safe_mode' === 'Off')) { 
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_MAXREDIRS, $max_redirects);
			$response = curl_exec($ch);
		} else {
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
			$mr = $max_redirects;
			if ($mr > 0) { 
				$newurl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
				
				$rcurl = curl_copy_handle($ch);
				curl_setopt($rcurl, CURLOPT_HEADER, true);
				curl_setopt($rcurl, CURLOPT_NOBODY, true);
				curl_setopt($rcurl, CURLOPT_FORBID_REUSE, false);
				curl_setopt($rcurl, CURLOPT_RETURNTRANSFER, true);

				do {
					curl_setopt($rcurl, CURLOPT_URL, $newurl);
					$header = curl_exec($rcurl);
					if (curl_errno($rcurl)) {
						$code = 0;
					} else {
						$code = curl_getinfo($rcurl, CURLINFO_HTTP_CODE);
						if ($code == 301 || $code == 302) {
							preg_match('/Location:(.*?)\n/', $header, $matches);
							$newurl = trim(array_pop($matches));
						} else {
							$code = 0;
						}
					}
				} while ($code && --$mr);
				curl_close($rcurl);
				if ($mr > 0) {
					curl_setopt($ch, CURLOPT_URL, $newurl);
				} 
			}
			
			if($mr == 0 && $max_redirects > 0) {
				$response = false;
			} else {
				$response = curl_exec($ch);
			}
		}

		curl_close( $ch );
		
		fclose( $fh );
		
		return $file;

	}

	private static function get_fopen_file_segment( $url ) {

		$file_size = self::get_fopen_file_size( $url );

		$file = tempnam( SPP_PLUGIN_BASE . 'classes/tmp/', "mp3_");

		if( !is_writable( $file ) )
	    	return false;

	    $remote_file = fopen( $url, "r" );
	    $mp3 = fopen( $file, "a" );

	    while( !feof( $remote_file ) ) {

			fwrite( $mp3, fread( $remote_file, 4096 ) );
		    flush();
		    ob_flush();

		}

		fclose( $remote_file );
		fclose( $mp3 );

		return $file;

	}	

	/**
	 * Get size of the file 
	 * @param  $url
	 * @return number 	Size of the file
	 */
	private static function get_fopen_file_size( $url ) {

		$head = get_headers( $url, true );
		$file_size = $head['Content-Length'] ? $head['Content-Length'] : 0;

		if( is_array( $file_size ) ) {
			
			foreach( $file_size as $fs ) {
				if( $fs > 0 ) {
					$file_size = $fs;
				}
			}

			$file_size = !is_array( $file_size ) ? $file_size : 0;

		}
		
		return $file_size;
	
	}

	/**
	 * Retrieve the data of an MP3 file using the getID3 file
	 * 
	 * @param  filestream $file A File resource, must be MP3
	 * @return array       MP3 File Data
	 */
	private static function get_id3_data( $file ) {
		
		$id3 = new getID3;
		$info = $id3->analyze( $file );
	    getid3_lib::CopyTagsToComments( $info ); 

	    $data = array();
	    if( isset( $info['id3v2']['comments'] ) ) {
	    	foreach( $info['id3v2']['comments'] as $key => $tag ) {
			    	if( isset( $tag[0] ) && ( is_string( $tag[0] ) || is_numeric( $tag[0] ) ) )
    					$data[$key] = $tag[0];
    				else if( isset( $tag[0] ) )
    					$data[$key] = "";

	    	}
	    }

	    return $data;

	}

}
