<?php
/**
 * Smart Podcast Player
 * 
 * @package   SPP_Core
 * @author    jonathan@redplanet.io
 * @link      http://www.smartpodcastplayer.com
 * @copyright 2015 SPI Labs, LLC
 */

/**
  * @package SPP_Admin_Core
  * @author Jonathan Wondrusch <jonathan@redplanet.io?
 */

class SPP_Admin_Core {

	protected $_settings = array();

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Slug of the plugin screen.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_screen_hook_suffix = null;

	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a
	 * settings page and menu.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		$plugin = SPP_Core::get_instance();
		$this->plugin_slug = $plugin->get_plugin_slug();

		// Support for PHP < 5.3
		if (!defined('__DIR__')) {
			define('__DIR__', dirname(__FILE__));
		}

		// Add an action link pointing to the options page.
		$plugin_basename = plugin_basename( plugin_dir_path( __DIR__ ) . $this->plugin_slug . '.php' );
		add_filter( 'plugin_action_links_' . $plugin_basename, array( $this, 'add_action_links' ) );

		add_action( 'init', array( $this, 'settings' ) );

		add_action( 'admin_init', array( $this, 'update' ) );

		add_action( 'admin_notices', array( $this, 'license_key_notice' ) );

		//add_action( 'save_post', array( $this, 'fix_shortcodes' ) );

		//add_action( 'save_post', array( $this, 'spp_async_save' ) );

		add_action( 'wp_ajax_nopriv_fetch_track_data', array( $this, 'fetch_track_data' ) );
		add_action( 'wp_ajax_fetch_track_data', array( $this, 'fetch_track_data' ) );
		
		add_action( 'admin_post_clear_spp_cache', 'SPP_Admin_Core::clear_spp_cache_fn' );

		global $pagenow;

		if ( $pagenow == 'post.php' || $pagenow == 'post-new.php' ||  $pagenow == 'options-general.php' || $pagenow != 'widgets.php' || current_user_can('publish_posts') ) {

			// Load admin style sheet and JavaScript.
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

			// add new buttons
			add_filter('mce_buttons', array( $this, 'register_buttons' ) );

			add_filter('mce_external_plugins', array( $this, 'register_tinymce_javascript' ) );

			add_action( 'admin_head', array( $this, 'fb_add_tinymce' ) );

			add_action( 'admin_head', array( $this, 'admin_css' ) );

		}


	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
		
	}

	/**
	 * Register and enqueue admin-specific style sheet.
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_styles() {

		if( is_admin() ) {
        	wp_enqueue_style( 'wp-color-picker' );
        }

	}

	/**
	 * Register and enqueue admin-specific JavaScript.
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_scripts() {

		$advanced_options = get_option( 'spp_player_advanced');
		$color_pickers = isset( $advanced_options['color_pickers'] ) ? $advanced_options['color_pickers'] : "true";
		
		// Load the color pickers if the option is true or unset
		if ("true" == $color_pickers) { 
			$dependencies = array('jquery', 'wp-color-picker');
		} else {
			$dependencies = array('jquery');
		}
		wp_enqueue_script( $this->plugin_slug . '-admin-script',
				SPP_ASSETS_URL . 'js/admin-spp.min.js',
				$dependencies,
				SPP_Core::VERSION );

		wp_localize_script( $this->plugin_slug . '-admin-script',
				'Smart_Podcast_Player_Admin',
				array('licensed' => SPP_Core::is_paid_version()));
				
		wp_localize_script( $this->plugin_slug . '-admin-script',
				'smart_podcast_player_user_settings',
				get_option( 'spp_player_defaults' ));


	}

	public function settings() {

		require_once( SPP_PLUGIN_BASE . 'classes/admin/settings.php' );
		$this->settings = new SPP_Admin_Settings();
		
	}

	/**
	 * Add settings action link to the plugins page.
	 *
	 * @since    1.0.0
	 */
	public function add_action_links( $links ) {

		return array_merge(
			array(
				'settings' => '<a href="' . admin_url( 'options-general.php?page=' . $this->plugin_slug ) . '">' . __( 'Settings', $this->plugin_slug ) . '</a>'
			),
			$links
		);

	}

	public function update() {

		/*----------------------------------------------------------------------------*
		 * Update functionality
		 *----------------------------------------------------------------------------*/

		// 	WP 4.2+ now AJAX's plugin update now requests
		//	if( defined( 'DOING_AJAX' ) && DOING_AJAX )
        //        return;

		if (file_exists(SPP_PLUGIN_BASE . 'includes/plugin-update-checker.php'))
		{
			//Use version 1.6 of the update checker.
			require SPP_PLUGIN_BASE . 'includes/plugin-update-checker.php';

			$settings = get_option( 'spp_player_general' );
			$license_key = isset( $settings[ 'license_key' ] ) ? $settings[ 'license_key' ] : 'nokey';

			$spp_update_checker = new PluginUpdateChecker_1_6 (
			    $this->getMetaFileURL() . trim($license_key),
			    SPP_PLUGIN_BASE . 'smart-podcast-player.php',
			    'smart-podcast-player',
				12
			);

			// Allow manual "check for updates" from plugin page and display check results
			if ( isset( $_GET['puc_check_for_updates'] ) )
	            $spp_update_checker->handleManualCheck();
	        elseif ( current_user_can( 'update_plugins' ) )
	        	$spp_update_checker->maybeCheckForUpdates();
    	}

	}

	public function getMetaFileURL() {
		return 'https://smartpodcastplayer.com/license/check/';
	}

	public function license_key_notice() {

		//$settings = get_option( 'spp_player_general' );

		if( !SPP_Core::is_paid_version() ) {
	    ?>
	    <div class="error">
	        <p style="line-height: 30px;"><?php _e( 'Please enter your Smart Podcast Player license key to get updates and support! <a href="' . SPP_SETTINGS_URL . '" class="button" style="float: right;">Go to Settings</a>', 'smart-podcast-player' ); ?></p>
	    </div>
	     <div class="notice">
	        <p style="line-height: 30px;"><?php _e( $this->license_paid_notice_text() ) ?></p>
	    </div>
	    <?php
		}
	}

	public function license_paid_notice_text() {

		$link = "<a href='https://smartpodcastplayer.com'>";
		$link_end = "</a>";

		$phrases = array();
		$phrases[0] = "Hey there! We hope you’ve been enjoying the free version of the Smart Podcast Player. You’re welcome to keep using it for all eternity, but eternity would be a little boring without the ability to post more than 10 episodes. Upgrade to the full version ".$link."right here".$link_end."!";
		$phrases[1] = "We hope you’re getting the most out of the Smart Podcast Player! To get even more, consider ".$link." upgrading to the full version".$link_end.".";
		$phrases[2] = "Have a need for speed (controls)? What about a download button so your listeners can take your episodes with them on the go? Then ".$link." check out the full version".$link_end." of the Smart Podcast Player!";
		$phrases[3] = "His free version of the Smart Podcast Player worked great. But when he ".$link." upgraded to the full version".$link_end.", he was astonished…";
		$phrases[4] = "The free player's gonna play, play, play, play, play, but that green color looks the same, same, same, same, same, so just upgrade, 'grade, 'grade, 'grade, ‘grade, 'grade, ‘grade. ".$link."Upgrade it now, upgrade it now.".$link_end." ";
		$phrases[5] = "Wish your listeners could share your podcast episodes via Twitter, Facebook, Google+, email, facsimile, Morse code, carrier pigeon, and more? We can help you with the first four—but only if you ".$link."upgrade to the full version".$link_end."  of the Smart Podcast Player.";
		$phrases[6] = $link."Upgrade to the full version".$link_end." of the Smart Podcast Player and get access to our awesome email tech support. It’s like AAA for podcasting!";
		$phrases[7] = "The full version of the Smart Podcast Player is like the club sandwich to the free version’s grilled cheese. They’re both delicious—one just gives you a little more. Visit ".$link."smartpodcastplayer.com".$link_end." to upgrade now.";
		return $phrases[array_rand($phrases)];

	}

	public function download_progress( $download_size, $downloaded, $upload_size, $uploaded ) {

	    $percent = $downloaded / $download_size;
		
		return ( $downloaded > ( 512 * 1024 ) ) ? 1 : 0;

	}

	public function fix_shortcodes( $post_id ) {

		global $wpdb;

		if( !class_exists( 'getID3' ) )
			require_once( SPP_PLUGIN_BASE . 'includes/getid3/getid3.php' );
		
		$post = get_post( $post_id );

		$content = $post->post_content;

		preg_match_all( '/smart_track_player.*url="(.*)?"\s/', $content, $matches );

		foreach( $matches[1] as $m ) {

			if( strpos( $m, 'href=' ) !== false ) {

				$xml = simplexml_load_string( $m );
			    $list = $xml->xpath("//@href");

			    $preparedUrls = array();
			    foreach($list as $item) {
			    	$i = $item;
			        $item = parse_url($item);
			        $preparedUrls[] = $item['scheme'] . '://' .  $item['host'] . $item['path'];
			    }

			    $url = $preparedUrls[0];

				$content = str_replace( $m, $url, $content );

			}

		}

		preg_match_all( '/smart_podcast_player.*url="(.*)?"\s/', $content, $matches );

		foreach( $matches[1] as $m ) {

			if( strpos( $m, 'href=' ) !== false ) {

				$xml = simplexml_load_string( $m );
			    $list = $xml->xpath("//@href");

			    $preparedUrls = array();
			    foreach($list as $item) {
			    	$i = $item;
			        $item = parse_url($item);
			        $preparedUrls[] = $item['scheme'] . '://' .  $item['host'] . $item['path'];
			    }

			    $url = $preparedUrls[0];

				$content = str_replace( $m, $url, $content );

			}

		}

		$wpdb->update( $wpdb->posts, array( 'post_content' => $content ), array( 'ID' => $post_id ) );

	}

	public function spp_async_save( $post_id ){

		if( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || ( defined( 'DOING_CRON' ) && DOING_CRON ) )
			return false;
		
		wp_clear_scheduled_hook( 'spp_async_save', array( $post_id ) );
		wp_schedule_single_event( time(), 'spp_async_save', array( $post_id ) );

	}

	public function fetch_track_data() {

		require_once( SPP_PLUGIN_BASE . 'classes/mp3.php' );

		$url = isset( $_POST['url'] ) ? $_POST['url'] : false;

		if( !$url ) {
			echo '0';
			die();
		}

		// Check if last fetch was last successful
		$transient_success = 'spp_cachem_' . 'track_fetch_check';

		$fallback = false;

		$transient = 'spp_cachem_' . substr( preg_replace("/[^a-zA-Z0-9]/", '', md5($url) ), -32 );

		$no_cache = filter_input( INPUT_GET, 'spp_no_cache' ) ? filter_input( INPUT_GET, 'spp_no_cache' ) : 'false';

		if( ( false === ( $data = get_transient( $transient ) ) ) || $no_cache == 'true' ) {

			$cache_time = DAY_IN_SECONDS;

			if ( get_transient( $transient_success ) ) {
				$fallback = true;
				$data = $this->fetch_track_data_fallback( $url );
			}

			if( ( false === ( $check = get_transient( $transient_success ) ) ) || !is_array( $data ) ) {
				$fallback = false;
				set_transient( $transient_success, $transient_success, DAY_IN_SECONDS );
				$data = SPP_MP3::get_data( $url );
				$cache_time = 4 * WEEK_IN_SECONDS;
			}

			if( is_array( $data ) ) {

				if ( !empty( $data ) || isset( $data['title'] ) || isset( $data['artist'] ) || count( $data , COUNT_RECURSIVE) >= 2 )
					$cache_time = YEAR_IN_SECONDS;

				set_transient( $transient, $data, $cache_time );

				if ( !$fallback )
					delete_transient( $transient_success );
			}
			else {
				// Prevent continous re-fetching
				set_transient( $transient, $data, MINUTE_IN_SECONDS );
			}

		}

		echo is_array( $data ) ? json_encode( $data ) : '0';

		die();

	}

	public function fetch_track_data_fallback ( $url = null) {

		$transient = 'spp_cachef_' . substr( preg_replace("/[^a-zA-Z0-9]/", '', md5($url) ), -32 );
		if ( $data = get_transient( $transient ) )
			return $data;

		$settings = get_option( 'spp_player_general' );
		$license_key = isset( $settings[ 'license_key' ] ) ? trim($settings[ 'license_key' ]) : 'nokey';

		$response = wp_remote_get(
		    "https://go.smartpodcastplayer.com/trackdata/?url=" . $url . "&license_key=" . $license_key,
		    array(
		        'timeout' => 10,
		        'sslverify' => false
		    )
		);

		if( !is_wp_error( $response ) && ( $response['response']['code'] < 400 ) ) {
			$data = json_decode( wp_remote_retrieve_body( $response ) , true );
			set_transient( $transient, $data, HOUR_IN_SECONDS );
			return $data;
		}
		return null;

	}

	public function register_buttons($buttons) {
	   array_push( $buttons, 'separator', 'spp' );
	   array_push( $buttons, 'separator', 'stp' );
	   return $buttons;
	}

	public function register_tinymce_javascript( $plugin_array ) {
	   $plugin_array['spp'] = SPP_PLUGIN_URL . '/assets/js/spp-mce/spp.js' . '?v=' . SPP_Core::VERSION;
	   $plugin_array['stp'] = SPP_PLUGIN_URL . '/assets/js/spp-mce/stp.js' . '?v=' . SPP_Core::VERSION;
	   return $plugin_array;
	}

	public function fb_add_tinymce() {
	    global $typenow;
	    global $pagenow;

	    // only on Post Type: post and page
	    if( ! in_array( $typenow, array( 'post', 'page' ) ) && $pagenow != 'post.php' && $pagenow != 'post-new.php' )
	        return ;

	    add_filter( 'mce_external_plugins', array( $this, 'fb_add_tinymce_plugin' ) );
	    // Add to line 1 form WP TinyMCE
	    add_filter( 'mce_buttons', array( $this, 'fb_add_tinymce_button' ) );

	}

	// inlcude the js for tinymce
	public function fb_add_tinymce_plugin( $plugin_array ) {

	    $plugin_array['spp'] = SPP_PLUGIN_URL . '/assets/js/spp-mce/spp.js' . '?v=' . SPP_Core::VERSION;
	    $plugin_array['stp'] = SPP_PLUGIN_URL . '/assets/js/spp-mce/stp.js' . '?v=' . SPP_Core::VERSION;
	    
	    return $plugin_array;
	}

	// Add the button key for address via JS
	public function fb_add_tinymce_button( $buttons ) {

	    array_push( $buttons, 'spp_button_key' );
	    array_push( $buttons, 'stp_button_key' );

	    return $buttons;
	    
	}

	public function admin_css() {

		echo '<style>' . "\n\t";
			echo '.spp-indented-option { margin-left: 50px; }' . "\n\t";
			echo 'th.spp-wider-column { width: 250px; }' . "\n\t";
			echo '.mce-container .spp-mce-hr { '
					. 'border-top: 1px solid #444;'
					. 'margin-top: 5px;'
					. 'margin-bottom: 5px;'
				    . '}' . "\n\t";
			echo '.spp-color-picker .wp-picker-container { position: relative; top: 4px; left: 2px; }' . "\n\t";
			echo '.spp-color-picker .wp-picker-container a { margin: 0; }' . "\n\t";
			echo 'i.mce-i-stp-icon { background: transparent url("' . SPP_PLUGIN_URL . 'assets/images/stp-icon.png" ) 0 0 no-repeat; background-size: 100%; }' . "\n\t";
			echo 'i.mce-i-spp-icon { background: transparent url("' . SPP_PLUGIN_URL . 'assets/images/spp-icon.png" ) 0 0 no-repeat; background-size: 100%; }' . "\n\t";
		echo '</style>';

	}
	
	public function clear_spp_cache_fn() {
	
		if ( ! wp_verify_nonce( $_POST[ 'clear_spp_cache_nonce' ], 'clear_spp_cache' ) )
            die( 'Invalid nonce.' . var_export( $_POST, true ) );
		SPP_Core::clear_cache();
		if ( ! isset ( $_POST['_wp_http_referer'] ) )
            die( 'Missing target.' );
		
		$url = add_query_arg( 'spp_cache', 'cleared', urldecode( $_POST['_wp_http_referer'] ) );
        wp_safe_redirect( $url );
        exit;
	}

}
