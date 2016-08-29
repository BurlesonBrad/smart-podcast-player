<?php

$tabs = array( 
  'general' => array( 'label' => 'General', 'settings' => 'spp-player-general' ), 
  'soundcloud' => array( 'label' => 'SoundCloud', 'settings' => 'spp-player-soundcloud' ),
  'defaults' => array( 'label' => 'Player Defaults', 'settings' => 'spp-player-defaults' ),
  'advanced' => array( 'label' => 'Advanced', 'settings' => 'spp-player-advanced'  
) );

$current_tab = isset( $_GET['tab'] ) && array_key_exists( $_GET['tab'], $tabs ) ? $_GET['tab'] : 'general';

?>

<div class="wrap settings-<?php echo $current_tab; ?>">

  <h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

  <div class="nav-tabs">
    <h2 class="nav-tab-wrapper">
    <?php foreach( $tabs as $key => $tab ) : ?>
          <a href="<?php echo SPP_SETTINGS_URL; ?>&tab=<?php echo $key; ?>" class="<?php echo $current_tab == $key ? 'nav-tab nav-tab-active' : 'nav-tab'; ?>"><?php echo $tab['label']; ?></a>    
    <?php endforeach; ?>
    </h2>
  </div>
   
  <?php if( $current_tab == 'advanced' ) { ?>
    <?php $redirect = urlencode( $_SERVER['REQUEST_URI'] ); ?>
    <form method="POST" action="<?php echo admin_url( 'admin-post.php' ); ?>">
	  <?php if( $_GET["spp_cache"] == 'cleared' ) { ?>
	    <div class="updated">
          <p>Smart Podcast Player cache cleared.</p>
        </div>
	  <?php } ?>
	  <input type="hidden" name="action" value="clear_spp_cache">
	  <?php wp_nonce_field( "clear_spp_cache", "clear_spp_cache_nonce", FALSE ); ?>
	  <input type="hidden" name="_wp_http_referer" value="<?php echo $redirect; ?>">
	  <table class="form-table">
	    <tr>
		  <th scope="row">Clear SPP Cache: </th>
		  <td>
		    <input type="submit" name="submit" id="submit" class="button button-secondary" value="Clear Cache">
		  </td>
		</tr>
	  </table>
	</form>
  <?php } ?>

  <form method="POST" action="options.php">
        
    <?php // The settings key refers the set of settings for a particular tab  
    if( isset( $tabs[ $current_tab ] ) ) {

      settings_fields( $tabs[ $current_tab ]['settings'] );
      do_settings_sections( $tabs[ $current_tab ]['settings'] );
      // do_settings_fields( 'spp-player', $tabs[ $current_tab ]['settings'] );    

    }

    submit_button(); 

  ?>
  </form>

</div>