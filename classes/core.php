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
 * @package SPP_Core
  * @author Jonathan Wondrusch <jonathan@redplanet.io?
 */
class SPP_Core {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	const VERSION = '1.4.0';

	/**
	 * The variable name is used as the text domain when internationalizing strings
	 * of text. Its value should match the Text Domain file header in the main
	 * plugin file.
	 *
	 * @since    0.8.0
	 *
	 * @var      string
	 */
	protected $plugin_slug = 'askpat-player';

	/**
	 * Instance of this class.
	 *
	 * @since    0.8.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	protected $_ajax;

	/**
	 * Default (Green) Color for SPP/STP
	 *
	 * @since   1.0.2
	 *
	 * @var     string
	 */
	const SPP_DEFAULT_PLAYER_COLOR = '#60b86c';

	/**
	 * Soundcloud API URL 
	 *
	 * @since   1.0.3
	 *
	 * @var     string
	 */
	const SPP_SOUNDCLOUD_API_URL = 'https://api.soundcloud.com';

	/**
	 * Initialize the plugin by setting localization and loading public scripts
	 * and styles.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Activate plugin when new blog is added
		add_action( 'wpmu_new_blog', array( $this, 'activate_new_site' ) );

		// Load public-facing style sheet and JavaScript.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		add_shortcode( 'smart_track_player', array( $this, 'shortcode_smart_track_player' ) );
		add_shortcode( 'smart_track_player_latest', array( $this, 'shortcode_smart_track_player_latest' ) );
		add_shortcode( 'smart_podcast_player', array( $this, 'shortcode_smart_podcast_player' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'fonts' ) );

		// Start Remove >=1.0.5 release - Track feeds are SPP not just soundcloud
		add_action( 'wp_ajax_nopriv_get_soundcloud_tracks', array( $this, 'ajax_get_tracks' ) );
		add_action( 'wp_ajax_get_soundcloud_tracks', array( $this, 'ajax_get_tracks' ) );
		// End Remove

		add_action( 'wp_ajax_nopriv_get_spplayer_tracks', array( $this, 'ajax_get_tracks' ) );
		add_action( 'wp_ajax_get_spplayer_tracks', array( $this, 'ajax_get_tracks' ) );

		add_action( 'wp_ajax_nopriv_get_soundcloud_track', array( $this, 'ajax_get_soundcloud_track' ) );
		add_action( 'wp_ajax_get_soundcloud_track', array( $this, 'ajax_get_soundcloud_track' ) );

		add_action( 'template_redirect', array( $this, 'force_download' ), 1 );

		add_action( 'template_redirect', array( $this, 'cache_bust' ), 1 );

		add_action( 'init', array( $this, 'upgrade' ) );

		add_action( 'wp_footer', array( $this, 'add_body_class' ) );

		// Use shortcodes in text widgets.
		add_filter('widget_text', 'do_shortcode');

	}

	/**
	 * Return the plugin slug.
	 *
	 * @since    0.8.0
	 *
	 * @return    Plugin slug variable.
	 */
	public function get_plugin_slug() {
		return $this->plugin_slug;
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
	 * Fired when the plugin is activated.
	 *
	 * @since    0.8.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Activate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       activated on an individual blog.
	 */
	public static function activate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide  ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_activate();
				}

				restore_current_blog();

			} else {
				self::single_activate();
			}

		} else {
			self::single_activate();
		}

	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since    0.8.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Deactivate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       deactivated on an individual blog.
	 */
	public static function deactivate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_deactivate();

				}

				restore_current_blog();

			} else {
				self::single_deactivate();
			}

		} else {
			self::single_deactivate();
		}

	}

	/**
	 * Fired when a new site is activated with a WPMU environment.
	 *
	 * @since    0.8.0
	 *
	 * @param    int    $blog_id    ID of the new blog.
	 */
	public function activate_new_site( $blog_id ) {

		if ( 1 !== did_action( 'wpmu_new_blog' ) ) {
			return;
		}

		switch_to_blog( $blog_id );
		self::single_activate();
		restore_current_blog();

	}

	/**
	 * Get all blog ids of blogs in the current network that are:
	 * - not archived
	 * - not spam
	 * - not deleted
	 *
	 * @since    0.8.0
	 *
	 * @return   array|false    The blog ids, false if no matches.
	 */
	private static function get_blog_ids() {

		global $wpdb;

		// get an array of blog ids
		$sql = "SELECT blog_id FROM $wpdb->blogs
			WHERE archived = '0' AND spam = '0'
			AND deleted = '0'";

		return $wpdb->get_col( $sql );

	}

	/**
	 * Fired for each blog when the plugin is activated.
	 *
	 * @since    0.8.0
	 */
	private static function single_activate() {}

	/**
	 * Fired for each blog when the plugin is deactivated.
	 *
	 * @since    0.8.0
	 */
	private static function single_deactivate() {}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    0.8.0
	 */
	public function load_plugin_textdomain() {

		$domain = $this->plugin_slug;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, FALSE, basename( plugin_dir_path( dirname( __FILE__ ) ) ) . '/languages/' );

	}

	/**
	 * Register and enqueue public-facing style sheet.
	 *
	 * @since    0.8.0
	 */
	public function enqueue_styles() {
	
		$advanced_options = get_option( 'spp_player_advanced');
		$css_important = isset( $advanced_options['css_important'] ) ? $advanced_options['css_important'] : "false";
		
		// If the css_important option is set to true, use the override CSS file
		if ("true" == $css_important) {
			$css_file = "css/style-override.css";
		} else {
			$css_file = "css/style.css";
		}
		wp_enqueue_style( $this->plugin_slug . '-plugin-styles', SPP_ASSETS_URL . $css_file, array(), self::VERSION );
	}

	/**
	 * Register and enqueues public-facing JavaScript files.
	 *
	 * @since    0.8.0
	 */
	public function enqueue_scripts() {

		global $post;

		$general_options = get_option( 'spp_player_general', array( 'show_title' => 'Podcast Episode' ) );
		$api_options = get_option( 'spp_player_soundcloud', array( 'consumer_key' => '' ) );
		$api_consumer_key = isset( $api_options['consumer_key'] ) ? $api_options['consumer_key'] : '';
		
		// Only one file for all of the Javascript, as it all auto loaded into main.min.js
		wp_register_script( $this->plugin_slug . '-plugin-script', SPP_ASSETS_URL . 'js/main.min.js', array( 'jquery', 'underscore' ), self::VERSION, true );

		$soundcloud = get_option( 'spp_player_soundcloud' );
		$key = isset( $soundcloud[ 'consumer_key' ] ) ? $soundcloud[ 'consumer_key' ] : '';
		wp_localize_script( $this->plugin_slug . '-plugin-script', 'AP_Player', array(
			'homeUrl' => home_url(),
			'baseUrl' => SPP_ASSETS_URL . 'js/',
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'soundcloudConsumerKey' => $key,
			'version' => self::VERSION,
			'licensed' => self::is_paid_version()
		));

		// Handle OptimizePress enqueue script
		if( is_object( $post ) && get_post_meta( $post->ID, '_optimizepress_pagebuilder', true ) == 'Y' ) {
			// Enqueue the Javascript file, unless this is a
			// Thrive Content Builder page (HS 3831)
			$thrive_content_builder = false;
			$tve = filter_input( INPUT_GET, 'tve' );
			if ( $tve == 'true' ) {
				include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
				if( is_plugin_active( 'thrive-visual-editor/thrive-visual-editor.php' ) ) {
					$thrive_content_builder = true;
				}
			}
			if( ! $thrive_content_builder ) {
				wp_enqueue_script( $this->plugin_slug . '-plugin-script' );
			}
		}

	}

	/**
	 * Output the shortcode for social customization or default it
	 * 
	 * @param  array  $atts Shortcode arguments array
	 * @return string $html Shortcode HTML
	 */
	public function shortcode_social_customize ( $atts = array(), $full_player = true) {

		$search_array = array(
				'social_twitter'=>'social_twitter','social_facebook'=>'social_facebook','social_gplus'=>'social_gplus',
				'social_linkedin'=>'social_linkedin','social_pinterest'=>'social_pinterest',
				'social_stumble'=>'social_stumble','social_email'=>'social_email');

		$html = '';

		$customized = false;

		if( isset( $atts['social'] ) && $atts['social'] == 'false' ) {
			$html .= ' data-social="' . $atts['social'] . '" ';
			return $html;
		}

		foreach ( $search_array as $value ) {
			if ( is_array ($atts) && array_key_exists( $value, $atts ) ) 
				$customized = true;
	
			if ( $customized )
				break;	
		}	 

		if ( !$customized ) {
			$atts['social']='true';
			$atts['social_twitter']='true';
			$atts['social_facebook']='true';
			$atts['social_gplus']='true';

			if ( $full_player )
				$atts['social_email']='true';
		}


		if( isset( $atts['social'] ) && $atts['social'] )
			$html .= ' data-social="' . $atts['social'] . '" ';

		foreach ( $search_array as $key => $value ) {
			if( isset( $atts[$value] ) ) {
				$html .= ' data-' . $key . '="' . $atts[$value] . '" ';
			}
		}

		return $html;

	}
	

	/**
	 * Output the shortcode for the podcast player
	 * 
	 * @param  array  $atts Shortcode arguments array
	 * @return string $html Shortcode HTML
	 */
	public function shortcode_smart_podcast_player( $atts = array() ) {

		$options = get_option( 'spp_player_defaults' );

		// Enqueue the Javascript file, unless this is a
		// Thrive Content Builder page (HS 3831)
		$thrive_content_builder = false;
		$tve = filter_input( INPUT_GET, 'tve' );
		if ( $tve == 'true' ) {
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			if( is_plugin_active( 'thrive-visual-editor/thrive-visual-editor.php' ) ) {
				$thrive_content_builder = true;
			}
		}
		if( ! $thrive_content_builder ) {
			wp_enqueue_script( $this->plugin_slug . '-plugin-script' );
		}

		$seed = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		$uniq_id = array();

		for ($i=0; $i < 8; $i++) { 
			$index = rand( 0, 61 );
			$uniq_id[] = $seed[$index];
		}
		
		$uid = implode( '', $uniq_id );
		
		// Include some intelligent defaults based on the options.
		extract( shortcode_atts( array(
			'url' => '',
			'style' => ( isset( $options['style'] ) ? $options['style'] : 'light' ),
			'numbering' => '',
			'show_episode_numbers' => 'true',
			'episode_limit' => ( isset( $options['episode_limit'] ) ? $options['episode_limit'] : '' ),
			'show_name' => ( isset( $options['show_name'] ) ? $options['show_name'] : '' ),
			'image' => '',
			'color' => ( isset( $options['bg_color'] ) ? $options['bg_color'] : self::SPP_DEFAULT_PLAYER_COLOR ),
			'link_color' => ( isset( $options['link_color'] ) ? $options['link_color'] : self::SPP_DEFAULT_PLAYER_COLOR ),
			'loaded_color' => 'not set',
			'played_color' => 'not set',
			'hashtag' => '',
			'permalink' => '',
			'download' => ( isset( $options['download'] ) ? $options['download'] : 'true' ),
			'subscription' => ( isset( $options['subscription'] ) ? $options['subscription'] : '' ),
			'hide_listens' => 'false',
			'html_assets' => 'false',
			'social' => 'true',
			'social_twitter' => 'true',
			'social_facebook' => 'true',
			'social_gplus' => 'true',
			'social_linkedin' => 'false',
			'social_stumble' => 'false',
			'social_pinterest' => 'false',
			'social_email' => 'true',
			'speedcontrol' => 'true',
			'poweredby' => ( isset( $options['poweredby'] ) ? $options['poweredby'] : 'true' ),
			'featured_episode' => '',
			'episode_timer' => 'down',
			'sort' => ( isset( $options['sort_order'] ) ? $options['sort_order'] : 'newest' )
		), $atts ) );
		
		if( $html_assets == 'true' ) {
			add_action( 'wp_footer', array( $this, 'add_assets_to_html' ) );
		}

		// Check URL to see if it is an html link or a url
		if( strpos( $url, ' href="' ) !== false ) {
			preg_match( '/href="(.+)"/', $url, $match);
			$url = parse_url( $match[1] );
		}

		$url = $url ? $url : ( isset( $options['url'] ) ? $options['url'] : '' );

		$html = '<div data-stream="' . $url . '" class="smart-podcast-player ';
		
		$free_colors = self::get_free_colors();
		$free_colors = array_change_key_case( $free_colors, CASE_LOWER );
		// If the user put in the name of a known color, replace it with the hex code
		if( array_key_exists( $color, $free_colors ) ) 
			$color = $free_colors[ $color ];

		if( !self::is_paid_version() ) {
			$color = self::SPP_DEFAULT_PLAYER_COLOR;
			$link_color = self::SPP_DEFAULT_PLAYER_COLOR;
			$download = false;
			$social = false;
			$speedcontrol = false;
			$poweredby = true;
			$sort = 'newest';
		}


		if( $loaded_color === 'not set' ) {
			require_once( SPP_PLUGIN_BASE . 'classes/utils/color.php' );
			$brightness = SPP_Utils_Color::get_brightness( $color );
			$dimmed = SPP_Utils_Color::tint_hex( $color, 0.9 );
			if( $brightness < 0.2 ) {
				$loaded_color = SPP_Utils_Color::add_hex( $dimmed, '1a1a1a' );
			} else {
				$loaded_color = $dimmed;
			}
		}
		if( $played_color === 'not set' ) {
			require_once( SPP_PLUGIN_BASE . 'classes/utils/color.php' );
			$brightness = SPP_Utils_Color::get_brightness( $color );
			$dimmed = SPP_Utils_Color::tint_hex( $loaded_color, 0.9 );
			if( $brightness < 0.2 ) {
				$played_color = SPP_Utils_Color::add_hex( $dimmed, '1a1a1a' );
			} else {
				$played_color = $dimmed;
			}
		}
		$this->color_array = array(
				'$color' => $color,
				'$link_color' => $link_color,
				'$loaded_color' => $loaded_color,
				'$played_color' => $played_color,
		);
		add_action( 'wp_footer', array( &$this, 'add_dynamic_css' ) );
			
		// Add all of the data attributes to the player div
		$html .= ' smart-podcast-player-' . str_replace( '#', '', $color ) . '  spp-color-' . str_replace( '#', '', $color ) . ' ';

		$html .= ' spp-link-color-' . str_replace( '#', '', $link_color ) . ' ';

		if( $style != 'light' )
			$html .= 'smart-podcast-player-' . $style . ' ';

		$html .= '" ';

		if( $numbering )
			$html .= 'data-numbering="' . $numbering . '" ';

		if( $show_episode_numbers )
			$html .= 'data-show_episode_numbers="' . $show_episode_numbers . '" ';
		
		if( $episode_limit )
			$html .= 'data-episode_limit="' . $episode_limit . '" ';

		if( $download )
			$html .= 'data-download="' . $download . '" ';

		if( $permalink )
			$html .= 'data-permalink="' . $permalink . '" ';

		if( $show_name )
			$html .= 'data-show-name="' . $show_name . '" ';

		if( $hashtag )
			$html .= 'data-hashtag="' . $hashtag . '" ';

		if( $image )
			$html .= 'data-image="' . $image . '" ';

		if( $color )
			$html .= 'data-color="' . $color . '" ';

		if( $link_color )
			$html .= 'data-link-color="' . $link_color . '" ';

		if( $featured_episode )
			$html .= 'data-featured_episode="' . $featured_episode . '" ';

		if( $sort ) {
			$sort = $sort == 'newest' || $sort == 'oldest' ? $sort : 'newest';
			$html .= 'data-sort="' . $sort . '" ';
		}

		if( $social )
			$html .= $this->shortcode_social_customize( $atts, true );

		if( $speedcontrol )
			$html .= 'data-speedcontrol="' . $speedcontrol . '" ';

		if( $poweredby )
			$html .= 'data-poweredby="' . $poweredby . '" ';

		if( $subscription )
			$html .= 'data-subscription="' . $subscription . '" ';

		if( $hide_listens )
			$html .= 'data-hide_listens="' . $hide_listens . '" ';
		
		if( $episode_timer == 'up' || $episode_timer == 'none' )
			$html .= 'data-episode_timer="' . $episode_timer . '" ';
		
		if( self::is_paid_version() )
			$html .= 'data-paid="true" ';

		$html .= 'data-uid="' . $uid . '" ';
		$html .= '></div>';

		// Output the shortcode HTML, javascript will take over after that.
		if( $thrive_content_builder ) {
			return $html . '<p>Smart Podcast Player</p><p>Feed URL: ' . $url . '</p>';
		}
		return $html;	

	}

	/**
	 * Output the shortcode for the track player
	 * @param  array  $atts Shortcode arguments, needs to be extracted
	 * @return string $html Shortcode HTML
	 */
	public function shortcode_smart_track_player( $atts = array() ) {

		// Include the MP3 class to handle MP3 data
		require_once( SPP_PLUGIN_BASE . 'classes/mp3.php' );

		// Enqueue the Javascript file, unless this is a
		// Thrive Content Builder page (HS 3831)
		$thrive_content_builder = false;
		$tve = filter_input( INPUT_GET, 'tve' );
		if ( $tve == 'true' ) {
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			if( is_plugin_active( 'thrive-visual-editor/thrive-visual-editor.php' ) ) {
				$thrive_content_builder = true;
			}
		}
		if( ! $thrive_content_builder ) {
			wp_enqueue_script( $this->plugin_slug . '-plugin-script' );
		}

		// Intelligent defaults
		extract( shortcode_atts( array(
			'url' => '',
			'style' => 'light',
			'show_numbering' => '',
			'title' => '',
			'image' => '',
			'download' => 'true',
			'html_assets' => 'false',
			'social' => 'true',
			'social_twitter' => 'true',
			'social_facebook' => 'true',
			'social_gplus' => 'true',
			'social_linkedin' => 'false',
			'social_stumble' => 'false',
			'social_pinterest' => 'false',
			'social_email' => 'false',
			'speedcontrol' => 'true',
			'color' => '',
			'sticky' => '',
			'episode_timer' => 'down',
			'artist' => ''
		), $atts ) );

		if( !self::is_paid_version() ) {
			$atts['color'] = self::SPP_DEFAULT_PLAYER_COLOR;
			$atts['download'] = false;
			$atts['social'] = false;
			$atts['speedcontrol'] = false;
		}
		
		if( true == $atts['html_assets'] ) {
			add_action( 'wp_footer', array( $this, 'add_assets_to_html' ) );
		}

		// Check URL to see if it is an html link or a url
		// Users were very often including an HTML link (<a href=""></a>) 
		// instead of just a raw URL

		if( strpos( $url, 'href=' ) !== false ) {

			$xml = simplexml_load_string( $url );
		    $list = $xml->xpath("//@href");

		    $preparedUrls = array();
		    foreach($list as $item) {
		    	$i = $item;
		        $item = parse_url($item);
		        $preparedUrls[] = $item['scheme'] . '://' .  $item['host'] . $item['path'];
		    }

		    $url = $preparedUrls[0];

		}

		$url = $url ? $url : '';
		
		// Verify the URL is for an MP3 or M4A file
		$is_audio = false;
		if( strpos( $url, 'soundcloud.com' ) !== false ) {
			$test = rtrim( $url, '/' );
			$count = substr_count( $test, '/' );
			if( $count > 3 && strpos( $url, '/sets/' ) === false ) {
				$is_audio = true;
			}		
		} else {
			if( strpos( $url, '.mp3' ) !== false || strpos( $url, '.m4a' ) !== false ) {
				$is_audio = true;
			}
		}

		// If it's not an MP3 or M4A, we give nothing out so as to not crash the page.
		if( !$is_audio )
			return;

		$html = $this->get_track_mp3_html( $url, $atts );
		if( $thrive_content_builder ) {
			return $html . '<p>Smart Track Player</p><p>URL: ' . $url . '</p>';
		} else {
			return $html;
		}

	}
	

	/**
	 * Output the shortcode for the latest episode track player
	 * @param  array  $atts Shortcode arguments, needs to be extracted
	 * @return string $html Shortcode HTML
	 */
	public function shortcode_smart_track_player_latest( $atts = array() ) {

		// Include the MP3 class to handle MP3 data
		require_once( SPP_PLUGIN_BASE . 'classes/mp3.php' );

		$atts = $this->get_first_track_attributes($atts);
		return $this->shortcode_smart_track_player($atts);

	}
	

	/**
	 * Get the URL, Artist, and Title of the first track of the feed
	 * @param  array 	$atts 		Existing attributes.  $atts['url']
	 *                              should be a feed.
	 * @return Modified attributes
	 */
	public function get_first_track_attributes( $atts ) {

		$feed_url = $atts['url'];
		
		// Cache of the feed.  Reused from ajax_get_tracks
		$transient = 'spp_cachea_' . substr( preg_replace("/[^a-zA-Z0-9]/", '', self::VERSION . $feed_url ), -32 );
		$data = get_transient( $transient );
		
		if( $data && isset( $data[ 'tracks' ] ) ) {
			// Use the cached value
			$tracks = $data[ 'tracks' ];
		} else if( strpos( $feed_url, 'http://soundcloud.com/' ) !== false
				|| strpos( $feed_url, 'https://soundcloud.com/' ) !== false ) {
			// Probably a user profile or set list (Soundcloud specific)
			$tracks = self::get_soundcloud_tracks( $feed_url, 1 );
		} else {
			// Assume an RSS feed.  This includes URLs like
			// feeds.soundcloud.com/something and api.soundcloud.com/something.
			$tracks = self::get_rss_tracks( $feed_url, 1 );
		}

		// Save in a transient
		if ( is_array( $data ) && is_array( $data['tracks'] ) || !empty ( $data['tracks'] ) ) {
			$settings = get_option( 'spp_player_advanced' );
			$val = isset( $settings['cache_timeout'] ) ? $settings['cache_timeout'] : '15';
			if ( $val > 60 || $val < 5 || !is_numeric( $val ) )
				$val = 15;
			set_transient( $transient, $data, $val * MINUTE_IN_SECONDS );
		}
		else {
			// Prevent crazy load and re-fetching
			set_transient( $transient, $data, MINUTE_IN_SECONDS );
		}
		
		// If the data comes from Soundcloud, we use the uri instead of the
		// stream_url.  This is independent of retrieving the track list.
		if( strpos( $feed_url, 'soundcloud.com' ) !== false ) {
			$audio_url = $tracks[0]->uri;
		} else {
			$audio_url = $tracks[0]->stream_url;
		}
		
		// Set attributes for the shortcode based on the latest feed data
		$atts['url'] = $audio_url;
		// If present, we use the artist (show name) and title from the feed
		// instead of from the ID3 tag.
		if( !isset( $atts['title'] ) || empty( $atts['title'] ) )
			if( isset( $tracks[0]->title) && !empty( $tracks[0]->title ) )
				$atts['title'] = $tracks[0]->title;
		if( !isset( $atts['artist'] ) || empty( $atts['artist'] ) )
			if( isset( $tracks[0]->show_name) && !empty( $tracks[0]->show_name ) )
				$atts['artist'] = $tracks[0]->show_name;
		return $atts;

	}

	/**
	 * Output HTML for a single 
	 * @param  string 	$audio_url 	Link to an MP3
	 * @param  array 	$atts      	Array of shortcode attributes
	 * @return string 	$html 		HTML output for shortcode
	 */
	public function get_track_mp3_html( $audio_url, $atts ) {

		$options = get_option( 'spp_player_defaults' );

		$seed = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		$uniq_id = array();

		for ($i=0; $i < 8; $i++) { 
			$index = rand( 0, 61 );
			$uniq_id[] = $seed[$index];
		}

		$uid = implode( '', $uniq_id );

		extract( shortcode_atts( array(
			'url' => '',
			'style' => 'light',
			'show_numbering' => '',
			'title' => '',
			'image' => ( isset( $options['stp_image'] ) ? $options['stp_image'] : '' ),
			'download' => ( isset( $options['download'] ) ? $options['download'] : 'true' ),
			'social' => 'true',
			'social_twitter' => 'true',
			'social_facebook' => 'true',
			'social_gplus' => 'true',
			'social_linkedin' => 'false',
			'social_stumble' => 'false',
			'social_pinterest' => 'false',
			'social_email' => 'false',
			'speedcontrol' => 'true',
			'color' => ( isset( $options['bg_color'] ) ? $options['bg_color'] : self::SPP_DEFAULT_PLAYER_COLOR ),
			'loaded_color' => 'not set',
			'played_color' => 'not set',
			'sticky' => '',
			'episode_timer' => 'down',
			'artist' => ( isset( $options['artist_name'] ) ? $options['artist_name'] : '' )
		), $atts ) );
		
		if( $loaded_color === 'not set' ) {
			require_once( SPP_PLUGIN_BASE . 'classes/utils/color.php' );
			$brightness = SPP_Utils_Color::get_brightness( $color );
			$dimmed = SPP_Utils_Color::tint_hex( $color, 0.9 );
			if( $brightness < 0.2 ) {
				$loaded_color = SPP_Utils_Color::add_hex( $dimmed, '1a1a1a' );
			} else {
				$loaded_color = $dimmed;
			}
		}
		if( $played_color === 'not set' ) {
			require_once( SPP_PLUGIN_BASE . 'classes/utils/color.php' );
			$brightness = SPP_Utils_Color::get_brightness( $color );
			$dimmed = SPP_Utils_Color::tint_hex( $loaded_color, 0.9 );
			if( $brightness < 0.2 ) {
				$played_color = SPP_Utils_Color::add_hex( $dimmed, '1a1a1a' );
			} else {
				$played_color = $dimmed;
			}
		}
		$this->color_array = array(
				'$color' => $color,
				'$link_color' => isset( $options['link_color'] )
						? $options['link_color']
						: self::SPP_DEFAULT_PLAYER_COLOR,
				'$loaded_color' => $loaded_color,
				'$played_color' => $played_color,
		);
		add_action( 'wp_footer', array( &$this, 'add_dynamic_css' ) );

		$class = 'smart-track-player ';

		$transient = 'spp_cachem_' . substr( preg_replace("/[^a-zA-Z0-9]/", '', md5($audio_url) ), -32 );
		$no_cache = isset( $_GET['spp_no_cache'] ) && $_GET['spp_no_cache'] == 'true' ? 'true' : 'false';
			
		$data = array();
		
		// If the user typed the name of a known color, replace it with the hex code
		$free_colors = self::get_free_colors();
		$free_colors = array_change_key_case( $free_colors, CASE_LOWER );
		if( array_key_exists( $color, $free_colors ) ) 
			$color = $free_colors[ $color ];
		
		if ( ( ( false === ( $data = get_transient( $transient ) ) && strpos( $url, 'soundcloud.com' ) === false ) ) || $no_cache == 'true' ) {
			$data = array();
		}

		if( $style != 'light' )
			$class .= ' stp-' . $style . ' ';

		// Add the color class every time
		$class .= ' stp-color-' . str_replace( '#', '', $color ) . ' ';

		$html = '<div class="' . trim( $class ) . '" data-url="' . $url . '" ';

		if( $show_numbering )
			$html .= 'data-numbering="' . $show_numbering . '" ';

		if( $image )
			$html .= 'data-image="' . $image . '" ';

		if( $download )
			$html .= 'data-download="' . $download . '" ';

		if( $color != '' ) 
			$html .= 'data-color="' . str_replace( '#', '', $color ) . '" ';

		if( $title != '' ) {
			$html .= 'data-title="' . $title . '" ';
		} else {
			if( isset( $data['title'] ) )
				$html .= 'data-title="' . $data['title'] . '" ';
			elseif( isset( $data['album'] ) )
				$html .= 'data-title="' . $data['album'] . '" ';
			elseif( isset( $data['artist'] ) )
				$html .= 'data-title="' . $data['artist'] . '" ';
			elseif( isset( $options['show_name']  ) && $options['show_name'] != '' && !empty( $data ) )
				$html .= 'data-title="' . $options['show_name']  . '" ';
		}

		if( $artist != '' ) {
			$html .= 'data-artist="' . $artist . '" ';
		} else {
			if( isset( $data['artist'] ) )
				$html .= 'data-artist="' . $data['artist'] . '" ';
			elseif( isset( $data['album'] ) )
				$html .= 'data-title="' . $data['album'] . '" ';
			elseif( isset( $options['show_name']  ) && $options['show_name'] != '' && !empty( $data ) )
				$html .= 'data-title="' . $options['show_name']  . '" ';
		}
		
		if( self::is_paid_version() )
			$html .= 'data-paid="true" ';

		if( $social )
			$html .= $this->shortcode_social_customize( $atts, false );

		if( $speedcontrol )
			$html .= 'data-speedcontrol="' . $speedcontrol . '" ';

		if( empty( $data ) && ( $title == '' )  )
			$html .= 'data-get="true" ';

		// Only one sticky STP is allowed
		static $have_sticky_stp = false;
		if( $sticky != "" && !$have_sticky_stp ) {
			$html .= 'data-sticky="' . $sticky . '" ';
			$have_sticky_stp = true;
		}
		
		if( $episode_timer == 'up' || $episode_timer == 'none' )
			$html .= 'data-episode_timer="' . $episode_timer . '" ';

		$html .= 'data-uid="' . $uid . '" ';
		
		require_once( SPP_PLUGIN_BASE . 'classes/download.php' );
		$download_id = SPP_Download::save_download_id($url);
		$html .= 'data-download_id="' . $download_id . '" ';

		$html .= '></div>';
		
		return $html;

	}

	/**
	 * Initialize handler for AJAX calls
	 * @return void
	 */
	public function ajax() {
		$this->_ajax = new SPP_Admin_Ajax();
	}
	
	
	/**
	 * Get track data via AJAX
	 * @return 	json 	JSON object representing all tracks
	 */
	public function ajax_get_tracks() {

		//global $wpdb;

		$url = isset( $_POST['stream'] ) ? $_POST['stream'] : '';
		$episode_limit = isset( $_POST['episode_limit'] ) ? $_POST['episode_limit'] : 0;

		//BPD URL based transient?
		//$existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->options WHERE option_value LIKE %s", '%' . $url . '%' ) );
		//$transient =  empty( $existing ) ? self::generate_playlist_id() : str_replace( '_transient_', '', $existing->option_name );
		if ( !empty( $url ) )
			$transient = 'spp_cachea_' . substr( preg_replace("/[^a-zA-Z0-9]/", '', self::VERSION . $url . (string)$episode_limit ), -32 );

		$no_cache = filter_input( INPUT_GET, 'spp_no_cache' ) ? filter_input( INPUT_GET, 'spp_no_cache' ) : 'false';
		
		if( ( false === ( $data = get_transient( $transient ) ) || !isset( $data['tracks'] ) ) || $no_cache == 'true' ) {

			$data = array(
				'url' => $url,
				'tracks' => array()
			);

			if( $url ) {
				
				// Limit the free version to ten tracks
				if( !self::is_paid_version() ) {
					$episode_limit = 10;
				}
				
				if( strpos( $url, 'http://soundcloud.com/' ) !== false || strpos( $url, 'http://soundcloud.com/sets/' ) !== false || strpos( $url, 'https://soundcloud.com/' ) !== false || strpos( $url, 'https://soundcloud.com/sets/' ) !== false ) {

					$data['tracks'] = $this->get_soundcloud_tracks( $url, $episode_limit );

				} else {
					$data['tracks'] = $this->get_rss_tracks( $url, $episode_limit );

				}

				if ( is_array( $data['tracks'] ) || !empty ( $data['tracks'] ) ) {
					 	$settings = get_option( 'spp_player_advanced' );

        				$val = isset( $settings['cache_timeout'] ) ? $settings['cache_timeout'] : '15';
        				if ( $val > 60 || $val < 5 || !is_numeric( $val ) )
        					$val = 15;
						set_transient( $transient, $data, $val * MINUTE_IN_SECONDS );
				}
				else {
					// Prevent crazy load and re-fetching
					set_transient( $transient, $data, MINUTE_IN_SECONDS );
				}
			} 

		}
		
		header('Content-Type: application/json');
		echo json_encode( $data['tracks'] );

		exit;

	}

	/**
	 * Called by SPP_Core::ajax_get_tracks, specifically to retrieve SoundCloud tracks
	 * @param  string 	$url 		URL of SoundCloud feed
	 * @return array 	$tracks		Array of tracks
	 */
	public static function get_soundcloud_tracks( $url, $episode_limit ) {
		
		$tracks = array();
		$api_options = get_option( 'spp_player_soundcloud', array( 'consumer_key' => '' ) );
		$api_consumer_key = isset( $api_options['consumer_key'] ) ? $api_options['consumer_key'] : '';

		// Determine if it's a feed URL
		if( strpos( $url, '/sets/' ) === false ) {
			
			$user_id = '';

			$url_prof = self::SPP_SOUNDCLOUD_API_URL . '/resolve?url=' . urlencode( $url ) . '&format=json&consumer_key=' . $api_consumer_key;
			$transient = 'spp_cachep_' . substr( preg_replace("/[^a-zA-Z0-9]/", '', md5( $url_prof ) ), -32 );
			
			if(  false === ( $profile = get_transient( $transient ) )  ) {

				$response = wp_remote_get( $url_prof );
				if( !is_wp_error( $response ) && ( $response['response']['code'] < 400 ) ) {

					$profile = json_decode( $response['body'] );

					if ( !empty ( $profile  ) && isset( $profile->id ) )
							set_transient( $transient, $profile, 5 * MINUTE_IN_SECONDS );

				}

			}

			$user_id = $profile->id;
			$track_count = $profile->track_count;

			if ( !is_numeric( $track_count ) || $track_count <= 0 )
				$track_count = 1;

			$offset = 0;
			$limit = 200;
			$tracks_arr = array();
			
			// Limit the number of episodes
			if( $episode_limit > 0 && $episode_limit < $track_count ) {
				$track_count = $episode_limit;
				$limit = $episode_limit;
			}

			$transient = 'spp_caches_' . substr( preg_replace("/[^a-zA-Z0-9]/", '', self::VERSION . $url . (string)$track_count ), -32 );

			if(  false === ( $tracks = get_transient ( $transient ) ) ) {

				$url = self::SPP_SOUNDCLOUD_API_URL . '/users/' . $user_id . '/tracks?format=json&client_id=' . $api_consumer_key . '&limit=' . $limit .'&linked_partitioning=1';

				while ( $track_count > $offset ) {

						$response = wp_remote_get( $url );
						if( !is_wp_error( $response ) && ( $response['response']['code'] < 400 ) ) {

							$json_obj = json_decode( $response['body'] );
							$tracks_arr[] = json_encode( $json_obj->collection );

							if ( empty( $json_obj->next_href ) )
								break;
							
							$url =  $json_obj->next_href; 
							
						}

					$offset += 200;

				}

				if ( is_array( $tracks_arr ) && !empty( $tracks_arr ) ) {

					if ( empty($tracks) && ( count( $tracks_arr ) == 1 ) ) {
							$tracks = json_decode( $tracks_arr[0] );
					}

					else {
							
						foreach($tracks_arr as $val) {

							if ( empty($tracks) ) {
								$tracks = $val;
							}
							else {
									if ( is_array( $tracks ) )
										$tracks = array_merge( $tracks, json_decode( $val, true ) ); 
									else
										$tracks = array_merge( json_decode( $tracks, true ), json_decode( $val, true ) ); 
							}
						}

						$tracks = json_decode( json_encode( $tracks ) );

					}

					if ( !empty ( $tracks ) )
						set_transient( $transient, $tracks , 4 * HOUR_IN_SECONDS );
				}
			}

		// Or if it's a profile URL
		} else {

			$url = self::SPP_SOUNDCLOUD_API_URL . '/resolve?url=' . urlencode( $url ) . '&format=json&consumer_key=' . $api_consumer_key;
			$transient = 'spp_cachesu_' . substr( preg_replace("/[^a-zA-Z0-9]/", '', md5( $url ) . (string)$track_count ), -32 );
			
			if(  false === ( $tracks = get_transient( $transient ) )  ) {

				$response = wp_remote_get( $url );
				if( !is_wp_error( $response ) && ( $response['response']['code'] < 400 ) ) {

					$playlist = json_decode( $response['body'] );
					$tracks = $playlist->tracks;
					
					// Limit the number of episodes
					if( $episode_limit > 0 && count( $tracks ) > $episode_limit ) {
						$tracks = array_slice( $tracks, 0, $episode_limit );
					}

					if ( !empty ( $tracks ) )
						set_transient( $transient, $tracks , 5 * MINUTE_IN_SECONDS );

				}

			}

		}

		if ( !empty( $tracks ) ) {
			return $tracks;
		}
		else
		{
			for( $track_count = 0; $track_count < 10; ++$track_count ) {
				$transient = 'spp_caches_' . substr( preg_replace("/[^a-zA-Z0-9]/", '', self::VERSION . $url . substr( $track_count, -1 ) ), -32 );
				$tracks = null;
				if( ( $tracks = get_transient ( $transient ) ) && !empty( $tracks ) ) {
					return $tracks;
				}
			}
			return null;
		}
	}

	/**
	 * Rewrite of WP Core fetch_feed function, removing the WP_SimplePie_File, which was causing issues 
	 * with FeedBlitz feeds
	 * 
	 * @param  string $url Url of RSS feed
	 * @return void
	 */
	public static function fetch_feed( $url ) {

		require_once( ABSPATH . WPINC . '/class-simplepie.php' );
		require_once( ABSPATH . WPINC . '/class-feed.php' );

		$rss = new SimplePie();

		$rss->set_sanitize_class( 'WP_SimplePie_Sanitize_KSES' );

		// We must manually overwrite $feed->sanitize because SimplePie's
		// constructor sets it before we have a chance to set the sanitization class
		$rss->sanitize = new WP_SimplePie_Sanitize_KSES();
		$rss->set_cache_class( 'WP_Feed_Cache' );
		if( strpos( 'feedblitz.com', $url ) === false ) {
			$rss->set_file_class( 'WP_SimplePie_File' );
		}
		$rss->set_feed_url( $url );
		// extend for slow feed generation/hosts
		$rss->set_timeout(15);

		// Also changed cache duration
		$rss->set_cache_duration( 5 * MINUTE_IN_SECONDS );

		// The Wordpress 4.5 update broke lots of feeds by setting the type
		// to application/octet-stream instead of application/rss+xml.
		// I don't know the root cause, but I do know the fix: force_feed.
		$rss->force_feed(true);
		
		$rss->init();
		$rss->handle_content_type();

		if ( $rss->error() )
			return new WP_Error( 'simplepie-error', $rss->error() );

		return $rss;

	}

	/**
	 * Retrieve track data from RSS feeds
	 * 
	 * @param  string $url URL of the RSS feed
	 * @return array 	Data for all of the tracks
	 */
	public static function get_rss_tracks( $url, $episode_limit ) {

		$rss = self::fetch_feed( $url );

		if( is_wp_error( $rss ) )
			return array();

		$transient = 'spp_cachesx_' . substr( preg_replace("/[^a-zA-Z0-9]/", '', self::VERSION . $url  ), -32 );
		
		// See if RAW XML is already available from SimplePie. Indicates when feed new/changed too.
		if ( $rss->get_raw_data() ) {
				$data = $rss->get_raw_data();
				set_transient( $transient, $data , HOUR_IN_SECONDS );
		}
		else {	
			if(  false === ( $data = get_transient( $transient ) )  ) {
				$data = wp_remote_retrieve_body ( wp_remote_get( $url ) );

				if ( !empty ( $data ) && !is_wp_error( $data )  )
					set_transient( $transient, $data , 5 * MINUTE_IN_SECONDS );
			}
		}

		if ( !empty ( $data ) )
			$xml = simplexml_load_string( $data );	// URL file-access is disabled? HS1438

		if ( empty( $xml ) || empty( $data ) )
			$xml = simplexml_load_file( $url ); 	// Raw xml so we can fetch other data

		$base = new StdClass;
		$user = new StdClass;

		// Many of these fields are pulled from the data that soundcloud includes in their track player
		$attr = array( 'kind', 'id', 'created_at', 'user_id', 'duration', 'user_id', 'duration', 'commentable', 'state', 'original_content_size', 'sharing', 'tag_list', 'permalink', 'streamable', 'embeddable_by', 'downloadable', 'purchase_url', 'label_id', 'purchase_title', 'genre', 'title', 'description', 'label_name', 'release', 'track_type', 'key_signature', 'isrc', 'video_url', 'bpm', 'release_year', 'release_month', 'release_day', 'original_format', 'license', 'uri', 'user', 'permalink_url', 'artwork_url', 'waveform_url', 'stream_url', 'download_url', 'download_count', 'favoritings_count', 'comment_count', 'attachments_uri', 'episode_number', 'content' );

		$user_attr = array( 'id', 'kind', 'permalink', 'username', 'uri', 'permalink_url', 'avatar_url' );

		foreach( $attr as $a ) {
			$base->{$a} = '';
		}

		foreach( $user_attr as $a ) {
			$user->{$a} = '';
		}

		$base->user = $user;

		$channel = $xml->channel;
		$items = $channel->item;

		$tracks = array();

		$episode_number = count( $items );
		$i = 0;

		if( !is_wp_error( $rss ) ) {
		
			require_once( SPP_PLUGIN_BASE . 'classes/download.php' );

			foreach ( $rss->get_items() as $item) {
				
				$enclosures = $item->get_enclosures();
				$enclosure = $item->get_enclosure();

				foreach( $enclosures as $enc ) {
					if( $enc->handler == 'mp3' ) {
						$enclosure = $enc;
					}
				}

	 			$track = clone( $base );
				$date = new DateTime( $item->get_date() );

				$track->id = $i;
				$track->title = $item->get_title();
				
				// Set the show notes based on user's selected option
				$advanced_options = get_option( 'spp_player_advanced');
				$show_notes = isset( $advanced_options['show_notes'] )
							? $advanced_options['show_notes'] : "description";
				switch ($show_notes) {
					case "description":
						$description = $item->get_description();
						break;
					case "content":
						$description = $item->get_content();
						break;
					case "itunes_summary":
						$description = $item->get_item_tags(
								SIMPLEPIE_NAMESPACE_ITUNES, 'summary');
						$description = strip_shortcodes($description[0]["data"]);
						break;
					case "itunes_subtitle":
						$description = $item->get_item_tags(
								SIMPLEPIE_NAMESPACE_ITUNES, 'subtitle');
						$description = strip_shortcodes($description[0]["data"]);
						break;
					default:
						$description = $item->get_description();
						break;
				}
				$description = strip_tags( $description, '<p><a>' );
				$track->description = self::scrub_html( $description );

				$item_link = $item->get_link();
				$track->permalink_url = is_null($item_link) ? "" : $item_link;
				$track->uri = is_null($item_link) ? "" : $item_link;
				$track->stream_url = $enclosure->link;
				$track->download_url = $enclosure->link;
				$track->duration = $enclosure->duration;
				$track->created_at = $date->format( 'Y/m/d h:i:s O' );
				
				// HS4058: The string '?#' was being appended to the enclosure's link
				if( substr( $track->stream_url, -2) == "?#" ) {
					$track->stream_url = substr( $track->stream_url, 0, -2 );
				}
				
				$track->artwork_url = (string) $channel->image->url;
				if ( stripos( $track->artwork_url, "http://i1.sndcdn.com" ) !== FALSE )
					$track->artwork_url = str_replace( "http://i1.sndcdn.com", "//i1.sndcdn.com", $track->artwork_url );
				
				if( $track->artwork_url == '' || empty( $track->artwork_url ) ) {

					if( is_array( $enclosure->thumbnails ) && !empty( $enclosure->thumbnails[0] ) && $enclosure->thumbnails[0] != '' ) {
						
						$track->artwork_url = $enclosure->thumbnails[0];

					} else {

						$itunes_image = $rss->get_channel_tags( SIMPLEPIE_NAMESPACE_ITUNES, 'image' );

						if( is_array( $itunes_image ) ) {
							$track->artwork_url = $itunes_image[0]['attribs']['']['href'];	
						}

					}					

				}
				
				$track->show_name = (string) $channel->title;
				$track->episode_number = $episode_number;
				$track->download_id = SPP_Download::save_download_id($enclosure->link);

				if( !empty( $track->stream_url ) && $track->stream_url != '' ) {
					$tracks[] = $track;	
					$episode_number--;
				} else {}

				$i++;
				
				// Limit the number of episodes
				if( $episode_limit > 0 && $i >= $episode_limit )
					break;
				
			}
		}

		return $tracks;
		
	}

	/**
	 * Return the data for an array of Soundcloud stream URLs
	 * 
	 * @return JSON Array
	 */
	public function ajax_get_soundcloud_track() {

		$api_options = get_option( 'spp_player_soundcloud', array( 'consumer_key' => '' ) );
		$api_consumer_key = isset( $api_options['consumer_key'] ) ? $api_options['consumer_key'] : '';

		$url_array = isset( $_POST['streams'] ) ? $_POST['streams'] : '';

		$track_array = array();
		foreach( $url_array as $url ) {
			if ( !empty( $url ) )
				$transient = 'spp_cachet_' . substr( preg_replace("/[^a-zA-Z0-9]/", '', md5($url) ), -32 );
			
			// User in HS 3788 had a feed in which each enclosure matched the regexp below.  Using the resolve
			// URL didn't work for this one, so I added this specific match.  There is likely a better way.
			// It would involve finding out all of the possible Soundcloud URLs.
			if( 1 == preg_match( '/feeds\.soundcloud\.com\/stream\/(\d+)/', $url, $matches ) ) {
				$url = self::SPP_SOUNDCLOUD_API_URL . '/tracks/' . $matches[1] . '?consumer_key=' . $api_consumer_key;
			} else {
				$url = self::SPP_SOUNDCLOUD_API_URL . '/resolve.json?url=' . urlencode( $url ) . '&consumer_key=' . $api_consumer_key;
			}

			if(  false === ( $track = get_transient( $transient ) )  ) {

				$response = wp_remote_get( $url );
				if( !is_wp_error( $response ) && ( $response['response']['code'] < 400 ) ) {
					$track = json_decode( $response['body'] );

					if ( !empty ( $track  ) ) {
						
						$settings = get_option( 'spp_player_advanced' );

						$val = isset( $settings['cache_timeout'] ) ? $settings['cache_timeout'] : '15';
						if ( $val > 60 || $val < 5 || !is_numeric( $val ) )
							$val = 15;
						set_transient( $transient, $track, $val * HOUR_IN_SECONDS );
					}
				}
			}
			$track_array[] = $track;
		}
		
		header('Content-Type: application/json');
		echo json_encode( $track_array );

		exit;

	}

	/**
	 * Automatically include google fonts on people's pages to use with the player
	 * 
	 * @return void
	 */
	public function fonts() {
		wp_enqueue_style( $this->plugin_slug . '-plugin-fonts',
				'https://fonts.googleapis.com/css?family=Open+Sans:400italic,600italic,700italic,400,600,700',
				array(), self::VERSION);
	}

	/**
	 * Use the SPP_Download class to force file downloads based on methods available
	 * 
	 * @return void
	 */
	public function force_download() {
		if( isset( $_GET['spp_download'] ) ) {
			require_once( SPP_PLUGIN_BASE . 'classes/download.php' );
			$download_id = $_GET['spp_download'];
			$download = new SPP_Download( $download_id );
			$download->get_file();
			exit;
		}
	}

	/**
	 * Delete the internal spp_cache when the URL variables are present
	 * 
	 * @return void
	 */
	public function cache_bust() {

		$bust = filter_input( INPUT_GET, 'spp_cache' );

		if( $bust == 'bust' ) {
		
			self::clear_cache();
			
		}

	}
	
	public function clear_cache() {
	
		if (current_user_can( 'update_plugins' ) ) {

				global $wpdb;

				$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->options WHERE autoload='no' AND option_name LIKE %s", '%spp\_cache%' ) );
				
				$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->options WHERE autoload='no' AND option_name LIKE %s", '%spp\_license\_chk' ) );
				
				$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->options WHERE autoload='no' AND option_name LIKE %s", '%spp\_feed_%' ) );
				
				// Remove custom CSS files
				$to_delete = array();
				if( $dh = opendir( SPP_ASSETS_PATH . 'css/' ) ) {
					while(( $file = readdir( $dh ) ) !== false ) {
						if( preg_match( '/custom.*\.css/', $file ) ) {
							$to_delete[] = SPP_ASSETS_PATH . 'css/' . $file ;
						}
					}
				}
				foreach( $to_delete as $file ) {
					unlink( $file );
				}
		}
	
	}

	/**
	 * Scrub the HTML passed in for any attributes we don't want, like class, style, and ID
	 * 
	 * @param  string $input Can be any valid HTML text
	 * @return string $output Scrubbed HTML output, minus the doctype
	 */
	public static function scrub_html( $input ) {

		if( !extension_loaded( 'libxml' ) || !extension_loaded( 'dom' ) || empty( $input ) )
			return $input;

		if (function_exists("mb_convert_encoding")) {
			require_once( dirname( __FILE__ ) . '/vendor/SmartDOMDocument.php' );
			$dom = new SmartDOMDocument;
		} else {
			$dom = new DOMDocument;
		}
		
		$dom->loadHTML( $input );

		$xpath = new DOMXPath( $dom );
		$nodes = $xpath->query('//@*');

		foreach ($nodes as $node) {
			if( $node->nodeName == 'style' || $node->nodeName == 'class' || $node->nodeName == 'id' ) {
			    $node->parentNode->removeAttribute($node->nodeName);
			}
		}

		$links = $dom->getElementsByTagName('a');

		foreach ( $links as $item ) {
			$item->setAttribute('target','_blank');  
		}
		
		$output = preg_replace('~<(?:!DOCTYPE|/?(?:html|body))[^>]*>\s*~i', '', $dom->saveHTML() ); // Extract w/o the doctype and html/body tags
		
		return $output;

	}

	/**
	 * Used when determining if we're dealing with an MP3 or an RSS Feed
	 * @param  string $url 
	 * @return string 'feed' or 'mp3' only
	 */
	public function get_url_type( $url ) {
			
		$type = false;

		if( strpos( $url, 'soundcloud.com' ) !== false ) {

			$test = rtrim( $url, '/' );
			$count = substr_count( $test, '/' );

			if( $count > 3 && strpos( $url, '/sets/' ) === false ) {

				$type = 'mp3';

			}

			if( $count <= 3 ) {

				$feed = self::fetch_feed( $url );

				if( is_wp_error( $feed ) )
					return $type;

				$feed->init();
				$feed->handle_content_type();

				if ( !$feed->error() ) 
					$type = 'feed';

			}			

		} else {

			if( strpos( $url, '.mp3' ) !== false ) {

				$type = 'mp3';

			} else {

				$feed = self::fetch_feed( $url );

				if( is_wp_error( $feed ) )
					return $type;
				
				$feed->init();
				$feed->handle_content_type();

				if ( !$feed->error() ) 
					$type = 'feed';

			}

		}


		return $type;

	}
	
	/**
	 * Tells whether this version is the paid or free version
	 *
	 * @return true if this is the paid version of the player, false otherwise
     *
	 * @since 1.0.2
	 */
	public static function is_paid_version() {
		
		$settings = get_option( 'spp_player_general' );

		if( !isset( $settings[ 'license_key' ] ) || empty( $settings[ 'license_key' ] ) ) 
			return false;

		$transient = 'spp_license_chk';
		
		if ( false !== ( $check = get_transient( $transient ) ) ) {
			return true;
		}

		// plugin updater class confirms valid checks
		$optionName = 'external_updates-smart-podcast-player';
		$state = get_site_option($optionName, null);
		
		if ( !empty($state) && is_object($state) && isset($state->update) && is_object($state->update) ){
			set_transient( $transient, time(), 1 * WEEK_IN_SECONDS );
			return true;
		}

		return false;
	
	}
	
	/**
	 * Gets an array of the colors included in the free version
	 *
	 * @return an array of the colors included in the free version
	 *
	 * @since 1.0.20
	 */
	public static function get_free_colors() {
		return array( 'Green' => self::SPP_DEFAULT_PLAYER_COLOR ,
				'Blue' => '#006cb5',
				'Yellow' => '#f0af00',
				'Orange' => '#e7741b',
				'Red' => '#dc1a26',
				'Purple' => '#943f93' );
	}

	/**
	 * Process upgrade of the plugin
	 * 
	 * @return void
	 */
	public function upgrade() {

	    $version = get_option( 'spp_version' );

	    if ( $version != self::VERSION ) {

	    	add_option( 'spp_version', self::VERSION );
	        
	        // Migrate old option names to the new ones if any of the new ones don't exist
	        if(( 
	        	!get_option( 'spp_player_general' ) || 
	        	!get_option( 'spp_player_defaults' ) || 
	        	!get_option( 'spp_player_soundcloud' ) 
	        	) && ( 
	        	get_option( 'ap_player_general' ) !== false || 
	        	get_option( 'ap_player_defaults' ) !== false || 
	        	get_option( 'ap_player_soundcloud' ) !== false 
	        )) { 
	        	$this->migrate_options(); 
	        }

	    }

	}

	/**
	 * Migrate old ap_* based options to spp_* based options based on the version of the plugin
	 * 
	 * @return void
	 */
	public function migrate_options() {
		
		$options = array(
			'ap_player_general' => 'spp_player_general',
			'ap_player_default' => 'spp_player_defaults',
			'ap_player_soundcloud' => 'spp_player_soundcloud'
		);

		foreach( $options as $old => $new ) {
			
			$option = get_option( $old );
			
			if( get_option( $new ) == false && $option !== false ) {
				add_option( $new, $option );
			}

			delete_option( $old );

		}

	}

	/**
	 * Automatically add spp as a body class
	 * 
	 * @return  void
	 */
	public function add_body_class() {
		echo "\n" . '<script type="text/javascript">document.getElementsByTagName(\'body\')[0].className+=\' spp\'</script>' . "\n";
	}
	
	public function get_css_important_str() {
	
		$advanced_options = get_option( 'spp_player_advanced');
		$css_important = isset( $advanced_options['css_important'] ) ? $advanced_options['css_important'] : "false";
		if ("true" == $css_important) {
			$important_str = " !important";
		} else {
			// Regular styles
			$important_str = "";
		}
		return $important_str;
	}
	
	// Write the assets straight to the HTML.  This functionality should be done via
	// wp_localize_script and wp_enqueue_script, but sometimes it isn't (HS 3933).
	public function add_assets_to_html() {
	
		
		$soundcloud = get_option( 'spp_player_soundcloud' );
		$key = isset( $soundcloud[ 'consumer_key' ] ) ? $soundcloud[ 'consumer_key' ] : '';
		
		$output = '';
		$output .= '<script type="text/javascript" src="';
		$output .=    includes_url( 'js/underscore.min.js' ) . '"></script>';
		
		$output .= '<script type="text/javascript">';
		$output .=    '/* <![CDATA[ */';
		$output .=    'var AP_Player = {';
		$output .=       str_replace('/', '\\/', '"homeUrl":"' . home_url() . '",');
		$output .=       str_replace('/', '\\/', '"baseUrl":"' . SPP_ASSETS_URL .'js/') . '",';
		$output .=       str_replace('/', '\\/', '"ajaxurl":"' . admin_url( 'admin-ajax.php' ) ) .'",';
		$output .=       '"soundcloudConsumerKey":"' . $key . '",';
		$output .=       '"version":"' . self::VERSION . '",';
		$output .=       '"licensed":"' . self::is_paid_version() . '",';
		$output .=    '};';
		$output .=    '/* ]]> */';
		
		$output .= '</script>';
		$output .= '<script type="text/javascript" src="';
		$output .=    SPP_ASSETS_URL . 'js/main.min.js"></script>';
		
		$output .= '<link rel="stylesheet" id="askpat-player-plugin-styles-css" href="';
		$output .=    SPP_ASSETS_URL . 'css/style.css" type="text/css" media="all">';
		
		echo $output;

	}
	
	public function parse_dynamic_css_fragment( $expr, $color_array ) {
	
		require_once( SPP_PLUGIN_BASE . 'classes/utils/color.php' );
		preg_match( '/\s*(\$?\w+),?\s*([\d\.]*)/', $expr, $matches );
		if( $matches[1] === '$color'
				|| $matches[1] === '$link_color'
				|| $matches[1] === '$loaded_color'
				|| $matches[1] === '$played_color' ) {
			if( array_key_exists( $matches[1], $color_array ) ) {
				$color = $color_array[$matches[1]];
			} else {
				$color = str_replace( '#', '', self::SPP_DEFAULT_PLAYER_COLOR);
			}
			if( empty( $matches[2] ) ) {
				return $color;
			} else {
				// $matches[2] is the tint value.
				$tinted = SPP_Utils_Color::tint_hex( $color, $matches[2] );
				$tinted = str_replace( '#', '', $tinted);
				return $tinted;
			}
		} else if( $matches[1] === '$white_controls_url') {
			return 'url(' . SPP_ASSETS_URL . 'images/controls-white.png )';
		} else if( $matches[1] === '$black_controls_url') {
			return 'url(' . SPP_ASSETS_URL . 'images/controls-black.png )';
		} else if ( $matches[1] === '$importantStr') {
			return self::get_css_important_str();
		} else {
			return trim($matches[0]);
		}
	}
	
	public function callback_for_generate_dynamic_css( $matches ) {
		$color_array = $this->color_array_for_generate_dynamic_css;
		$brightness = $this->brightness_for_generate_dynamic_css;
		if( $brightness < 0.2 ) {
			$expr = $matches[1];
		} else if ($brightness > 0.6 ) {
			$expr = $matches[3];
		} else {
			$expr = $matches[2];
		}
		return self::parse_dynamic_css_fragment( $expr, $color_array );
	}
	
	public function generate_dynamic_css( $color_array ) {
		
		require_once( SPP_PLUGIN_BASE . 'classes/utils/color.php' );
		$css = file_get_contents(SPP_PLUGIN_BASE . 'classes/dynamic.css');
		
		// Replace semicolon-separated parenthesized expressions
		$this->color_array_for_generate_dynamic_css = $color_array;
		$this->brightness_for_generate_dynamic_css = SPP_Utils_Color::get_brightness( $color_array['$color'] );
		$css = preg_replace_callback(
				'/\(\((.*?);(.*?);(.*?)\)\)/',
				array( $this, 'callback_for_generate_dynamic_css' ),
				$css );
		// Replace other parenthesized expressions
		$this->brightness_for_generate_dynamic_css = 0;
		$css = preg_replace_callback(
				'/\(\((.*?)\)\)/',
				array( $this, 'callback_for_generate_dynamic_css' ),
				$css );
		// Remove comments
		$css = preg_replace( '/\/\*.*?\*\//m', '', $css );
		return $css;
	}

	public function add_dynamic_css() {
		
		$color_array = $this->color_array;
		
		// The generated dynamic CSS will be stored in filename
		$filename = SPP_ASSETS_PATH . 'css/custom-' . self::VERSION;
		foreach( $color_array as &$color ) {
			$color = str_replace( '#', '', $color );
			$filename = $filename . '-' . $color;
		}
		if( self::get_css_important_str() === " !important" ) {
			$filename = $filename . '-i';
		}
		$filename = $filename . '.css';
		
		// If we have already included this CSS on the page, we're done
		static $included_already = array();
		if( in_array( $filename, $included_already ) ) {
			return;
		}
	
		// Get the CSS from the file, if it exists.  Otherwise, generate and save it
		if( file_exists( $filename ) ) {
			$css = file_get_contents( $filename );
		} else {
			$css = self::generate_dynamic_css( $color_array );
			file_put_contents( $filename, $css, LOCK_EX );
		}

		// Put the generated CSS onto the page
		echo '<style>' . "\n\t";
		echo '/* Smart Podcast Player custom styles for color ' . $color_array['$color'] . " */\n";
		echo $css;
		echo '</style>' . "\n";
		
		// Make a note that we've put this CSS on the page
		$included_already[] = $filename;
		
	}

}
