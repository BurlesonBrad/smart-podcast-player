<div class="wrap">

	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

	<form method="POST" action="options.php">
        <?php 
        	settings_fields( 'ap-player' );
        	do_settings_sections( 'ap-player' );
        	submit_button(); 
        ?>
       </form>

</div>