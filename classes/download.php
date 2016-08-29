<?php

class SPP_Download {

    const CHUNK_SIZE = 1048576;

	protected $_download_id;
    protected $_file;
    protected $_size = 0;
    protected $_method = 'curl';

    public function __construct( $download_id ) {

        $settings = get_option( 'spp_player_advanced' );
        $this->_method = isset( $settings['downloader'] ) ? $settings['downloader'] : 'fopen';

        if( $this->_method ) {
		
			$this->_download_id = $download_id;
			$file = self::get_download_url($download_id);
			if (empty ($file)) {
				//TODO: Add to error reporting framework
				//error_log('SPP: Download ID "' . $download_id . '" not found.');
			} else {
				$this->_file = $this->safe_spp_url( $file );
			}

        } else {
            throw new Exception( "No file download methods available", 1 );
        }

    }

    public function safe_spp_url ( $url = null ) {

        if( strpos( strtolower( $url ), 'http' ) !== 0 ) {
            $clean = trailingslashit( home_url() ). $url;
            $url = $clean;
        }
        return esc_url_raw( str_replace('../', '', $url ), array('http','https') );

    }

    /**
     * Primary function of the class, triggers file to get downloaded via the acceptable method
     * 
     * @return void
     */
    public function get_file() {

        if ( empty( $this->_file ) )
            die();

        switch ( $this->_method ) {
            case 'fopen':
                if ( ini_get( 'allow_url_fopen' ) )
                    $this->fopen_download();
                else
                    $this->smart_download();
                break;
            case 'local':
                $this->smart_download_local();
                break;
            case 'smart' :
            default:
                $this->smart_download();
                break;
        }

    }

    /**
     * Start the download via cURL
     * 
     * @return void
     */
    public function curl_download() {

        set_time_limit(0);
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-disposition: attachment; filename=' . basename( $this->_file ) );
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Expires: 0');
        header('Pragma: public');
        //header('Content-Length: '. $this->_size );

        $ch = curl_init(); // init curl resource

        curl_setopt($ch, CURLOPT_URL, $this->_file );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false ); // a true curl_exec return content
        curl_setopt($ch, CURLOPT_BUFFERSIZE, 2048 ); // 2048 2k
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, array( $this, 'curl_get_segment' ) ); // called every CURLOPT_BUFFERSIZE

        curl_exec($ch);

        curl_close( $ch );

    }

    /**
     * Get the file size via cURL
     * 
     * @return void
     */
    public function curl_get_size() {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->_file );
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_exec($ch);

        $size = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);

        return intval( $size );

    }

    /**
     * Get a portion of the file segment via cURL in order to facilitate downloads on lower memory PHP solutions
     * 
     * @return void
     */
    public function curl_get_segment( $ch, $str ){

        $len = strlen($str);
        echo( $str );

        return $len;

    }

    /**
     * Get file size with standard PHP elements
     * @return number filesize
     */
    public function fopen_get_size() {
        // BPD Fix filesize(): stat failed
        return filesize( $this->_file );
    }

    /**
     * Trigger file download with standard file download methods
     * @return void
     */
    public function fopen_download() {

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename( $this->_file ) . '"');
        header('SPP: FOP');
        //header('Content-Length: ' . $this->_size);

        // Check for output buffering
        if (ob_get_level() > 0 || ini_get('output_buffering'))
            ob_end_clean();
        //flush();

        $this->readfile_chunked( $this->_file );

        exit;

    }

    /**
     * Read a file chunk by chunk
     * @return $cnt (number of bytes delivered) | $status (for when it's finished)
     */
    public function readfile_chunked( $filename, $retbytes = TRUE) {

        $buffer = '';
        $cnt =0;

        $handle = fopen($filename, 'rb');
        if ($handle === false) {
         return false;
        }

        while (!feof($handle)) {
            $buffer = fread($handle, self::CHUNK_SIZE );
            echo $buffer;
            flush();
            if ($retbytes) {
                $cnt += strlen($buffer);
            }
        }

        $status = fclose($handle);
        if ($retbytes && $status) {
                return $cnt; // return num. bytes delivered like readfile() does.
            }

            return $status;

        }

    public function smart_download() {

        $remote_file = wp_remote_get( $this->_file );
        if( is_wp_error( $remote_file ) || empty ( $remote_file ) ) {
            $this->smart_download_local();
        }

        if ( isset( $remote_file['headers'] ) )
            $the_headers = $remote_file['headers'];
        if ( isset ( $the_headers['content-length'] ) && ($this->_size = $the_headers['content-length']) && ($the_headers['content-length'] <= 1000) )
            $this->smart_download_local();

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename( $this->_file ) . '"');

        if ( !empty ( $this->_size ) )
            header('Content-Length: ' . $this->_size);

        header('SPP: SDL');

        // Check for output buffering
        if (ob_get_level() > 0 || ini_get('output_buffering'))
            ob_end_clean();
        flush();
        echo $remote_file['body'];
        flush();
        exit;

    }

    public function smart_download_local() {

        $file = tempnam( SPP_PLUGIN_BASE . 'classes/tmp/', "dmp3_");

        if( !is_writable( $file ) ) {
            if ( ini_get( 'allow_url_fopen' ) )
                $this->fopen_download();
            else {
                return false;
            }
        }

        $fp = fopen( $file , 'w' );
        $retbytes = fwrite( $fp, wp_remote_retrieve_body( wp_remote_get($this->_file) ) );
        $status = fclose( $fp );

        if ( empty($retbytes) || empty ($status) || $retbytes <= 10000 ) {
            if ( ini_get( 'allow_url_fopen' ) )
                $this->fopen_download();
                //else
                //    return false;
        }

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename( $this->_file ) . '"');

        if ( !empty($retbytes) && $retbytes >= 1 && ($this->_size = $retbytes) )
            header('Content-Length: ' . $this->_size );
        else if ( $this->_size = filesize( $file ) )
            header('Content-Length: ' . $this->_size );

        header('SPP: SDLL');

        // Check for output buffering
        if (ob_get_level() > 0 || ini_get('output_buffering'))
            ob_end_clean();

        flush();
        $this->readfile_chunked($file);
        flush();
        unlink($file);
        exit;

    }
	
	/**
	 * Saves as a WP transient the URL of a file using its MD5.
	 * @return The download ID (MD5 of the URL)
	 * @since 1.0.6
	 */
	public static function save_download_id($url) {
		// We compute and set the transient whether or not it exists already, as
		// it's not computationally expensive to run an md5, and we want to
		// reset the expiration clock
		$download_id = md5($url);
		set_transient('spp_cacheu_' . $download_id, $url, 4 * WEEK_IN_SECONDS);
		return $download_id;
	}
	
	/**
	 * Returns the data in the WP transient for this download ID
	 * @return The URL associated with this download ID
	 * @since 1.0.6
	 */
	public static function get_download_url($download_id) {
		return get_transient('spp_cacheu_' . $download_id);
	}
}