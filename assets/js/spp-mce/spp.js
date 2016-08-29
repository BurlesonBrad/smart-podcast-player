( function() {
    tinymce.PluginManager.add( 'spp', function( editor, url ) {
		
		// Shorter name for the default options from the settings page
		var def = smart_podcast_player_user_settings;

        // Add a button that opens a window
        editor.addButton( 'spp_button_key', {

            icon: 'spp-icon',
			tooltip: 'Insert Smart Podcast Player shortcode',
            onclick: function() {
				
				var colorPrompt;
				var downloadablePrompt;
				var socialPrompt;

				if( isPaidVersionAdmin() ) {
					// For the paid version, the color goes in a text box
					colorPrompt = {
						type: 'textbox',
						name: 'color',
						label: 'Player color (Hex) #',
						value: def.bg_color ? def.bg_color : ''
					};
					linkColorPrompt = {
						type: 'textbox',
						name: 'link_color',
						label: 'Link color (Hex) #',
						value: def.link_color ? def.link_color : ''
					};
					// The paid version has the downloadable option
	                downloadablePrompt = {
						type: 'listbox',
						name: 'download',
						label: 'Display download button',
						onselect: function(e) {},
						values: def.download && def.download == 'false' ?
									[{text: 'No', value: 'false'}, {text: 'Yes', value: 'true'}]
								:
									[{text: 'Yes', value: 'true'}, {text: 'No', value: 'false'}]
					};

					socialPrompt = {
	                     type: 'listbox',
	                     name: 'social',
	                     label: 'Display social sharing buttons',
	                     onselect: function(e) {},
	                     values: [{text: 'Yes', value: 'on'}, {text: 'No', value: 'off'}]
	                };
					socialOptionsPrompt = {
						type: 'container',
						name: 'social_opts',
						label: 'Social sharing buttons',
						tooltip: 'Choose up to seven social sharing sites.',
						html: '<table> \
								<tr> \
									<td><input type="checkbox" id="spp_socialopt_twitter" checked>Twitter</input></td> \
									<td><input type="checkbox" id="spp_socialopt_facebook" checked>Facebook</input></td> \
								</tr> \
								<tr> \
									<td><input type="checkbox" id="spp_socialopt_gplus" checked>Google+</input></td> \
									<td><input type="checkbox" id="spp_socialopt_linkedin">LinkedIn</input></td> \
								</tr> \
								<tr> \
									<td><input type="checkbox" id="spp_socialopt_stumble">StumbleUpon</input></td> \
									<td><input type="checkbox" id="spp_socialopt_pinterest">Pinterest</input></td> \
								</tr> \
								<tr> \
									<td><input type="checkbox" id="spp_socialopt_email" checked>Email</input></td> \
									<td></td> \
								</tr> \
								</table>'
					};

				} else {
					// For the free version, only one color is available
					colorPrompt = {
						type: 'listbox',
						name: 'color',
						label: 'Player color',
						onselect: function(e) {},
						values: [{text: 'Green', value: '60b86c'}],
						disabled: true,
						tooltip: 'Upgrade to choose any color of the rainbow!'
					};
					linkColorPrompt = {
						type: 'listbox',
						name: 'color',
						label: 'Link color',
						onselect: function(e) {},
						values: [{text: 'Green', value: '60b86c'}],
						disabled: true,
						tooltip: 'Upgrade to choose any color of the rainbow!'
					};
					// The free version has no downloadable option
					  downloadablePrompt = {
						type: 'listbox',
						name: 'download',
						label: 'Display download button',
						onselect: function(e) {},
						values: [{text: 'No', value: 'false'}],
						disabled: true,
						tooltip: 'If you upgrade, you can decide whether to add a nifty download button to your player.'
					};

					socialPrompt = {
	                     type: 'listbox',
	                     name: 'social',
	                     label: 'Display social sharing buttons',
	                     onselect: function(e) {},
	                     values: [{text: 'No', value: 'off'}],
						 disabled: true,
						 tooltip: 'Your listeners can tell their friends about your show with ease when you upgrade.'
	                };
					socialOptionsPrompt = {
						type: 'container',
						name: 'social_opts',
						label: 'Social sharing buttons',
						disabled: true,
						tooltip: 'Your listeners can tell their friends about your show with ease when you upgrade.',
						html: '<table> \
								<tr> \
									<td><input type="checkbox" id="spp_socialopt_twitter" disabled><label class="mce-label mce-disabled">Twitter</label></input></td> \
									<td><input type="checkbox" id="spp_socialopt_facebook" disabled><label class="mce-label mce-disabled">Facebook</label></input></td> \
								</tr> \
								<tr> \
									<td><input type="checkbox" id="spp_socialopt_gplus" disabled><label class="mce-label mce-disabled">Google+</label></input></td> \
									<td><input type="checkbox" id="spp_socialopt_linkedin" disabled><label class="mce-label mce-disabled">LinkedIn</label></input></td> \
								</tr> \
								<tr> \
									<td><input type="checkbox" id="spp_socialopt_stumble" disabled><label class="mce-label mce-disabled">StumbleUpon</label></input></td> \
									<td><input type="checkbox" id="spp_socialopt_pinterest" disabled><label class="mce-label mce-disabled">Pinterest</label></input></td> \
								</tr> \
								<tr> \
									<td><input type="checkbox" id="spp_socialopt_email" disabled><label class="mce-label mce-disabled">Email</label></input></td> \
									<td></td> \
								</tr> \
								</table>'
					};
				}
				
                // Open window
                editor.windowManager.open( {

                    title: 'Smart Podcast Player Shortcode',
                    body: [
						{
						    type: 'container',
							name: 'header_1',
							html: '<em>For building the full podcast player. '
							    + '<a target="_blank" href="http://support.smartpodcastplayer.com/article/56-getting-started-8-adding-the-full-smart-podcast-player">Click here</a>'
								+ ' to watch a support video.</em><br><br>'
							    + '<p><em>Enter default values in Settings —> Smart Podcast Player.</em></p>'
								+ '<div class="spp-mce-hr"></div>'
								+ '<p style="font-weight: bold">1. Enter your podcast\'s RSS feed</p>'
						},
	                    {
	                        type: 'textbox',
	                        name: 'url',
	                        label: 'URL',
							value: def.url ? def.url : ''
	                    },
						{
						    type: 'container',
							name: 'header_2',
							html: '<div class="spp-mce-hr"></div>'
							    + '<p style="font-weight: bold">2. Customize the appearance</p>'
						},
	                    colorPrompt,
						linkColorPrompt,
	                    {
	                        type: 'listbox',
	                        name: 'style',
	                        label: 'Style',
	                        onselect: function(e) {},
	                        values: def.style && def.style == 'dark' ?
										[{text: 'Dark', value: 'dark'}, {text: 'Light', value: 'light'}]
									:
										[{text: 'Light', value: 'light'}, {text: 'Dark', value: 'dark'}]
	                    },
	                    {
	                        type: 'textbox',
	                        name: 'show_name',
	                        label: 'Show Name',
							value: def.show_name ? def.show_name : ''
	                    },
						{
						    type: 'container',
							name: 'show_name_help',
							html: '<em>Leave blank to get your show name from your RSS feed.</em>'
						},
						{
						    type: 'container',
							name: 'header_3',
							html: '<div class="spp-mce-hr"></div>'
							    + '<p style="font-weight: bold">3. Customize the buttons</p>'
						},
	                    socialPrompt,
						socialOptionsPrompt,
	                    /*{
	                        type: 'listbox',
	                        name: 'poweredby',
	                        label: 'Powered by SPP in Footer',
	                        onselect: function(e) {},
	                        values: [{text: 'On', value: 'on'}, {text: 'Off', value: 'off'}]
	                    },*/
	                    downloadablePrompt
 
	                ],
					buttons: [ 
						{ text: "Build Shortcode", subtype: 'primary', onclick: 'submit' },
						{ text: "Cancel", onclick: 'close' }
					],
                    onsubmit: function( e ) {

                    	var shortcode = '[smart_podcast_player';

                    	if( e.data.url != '' && ( !def.url || def.url != e.data.url ) )
                    		shortcode += ' url="' + e.data.url + '" ';

                    	if( def.style ) {
							if( e.data.style != def.style )
								shortcode += ' style="' + e.data.style + '" ';
						} else {
							if( e.data.style != 'light' )
								shortcode += ' style="' + e.data.style + '" ';
						}

                    	if( e.data.show_name != '' && ( !def.show_name || def.show_name != e.data.show_name ) )
                    		shortcode += ' show_name="' + e.data.show_name + '" ';

                    	if( isPaidVersionAdmin() ) {

							if( e.data.color != '' && ( !def.bg_color || def.bg_color != e.data.color ) )
                    			shortcode += ' color="' + e.data.color + '" ';

							if( e.data.link_color != '' && ( !def.link_color || def.link_color != e.data.link_color ) )
                    			shortcode += ' link_color="' + e.data.link_color + '" ';

                    		if( e.data.social != 'on' ) {
                    			shortcode += ' social="false" ';
							} else {
                    			shortcode += ' social="true" ';
								if( jQuery("#spp_socialopt_twitter").is( ":checked" ) )
									shortcode += ' social_twitter="true" ';
								if( jQuery("#spp_socialopt_facebook").is( ":checked" ) )
									shortcode += ' social_facebook="true" ';
								if( jQuery("#spp_socialopt_gplus").is( ":checked" ) )
									shortcode += ' social_gplus="true" ';
								if( jQuery("#spp_socialopt_linkedin").is( ":checked" ) )
									shortcode += ' social_linkedin="true" ';
								if( jQuery("#spp_socialopt_stumble").is( ":checked" ) )
									shortcode += ' social_stumble="true" ';
								if( jQuery("#spp_socialopt_pinterest").is( ":checked" ) )
									shortcode += ' social_pinterest="true" ';
								if( jQuery("#spp_socialopt_email").is( ":checked" ) )
									shortcode += ' social_email="true" ';
							}

							if( def.download ) {
								if( e.data.download != def.download )
									shortcode += ' download="' + e.data.download + '" ';
							} else {
								if( e.data.download != 'true' )
									shortcode += ' download="' + e.data.download + '" ';
							}
                    	}

                    	//  if( e.data.poweredby != 'on' )
                    	//		shortcode += ' poweredby="false" ';

                    	shortcode += ']';

                        // Insert content when the window form is submitted
                        editor.insertContent( shortcode );
                    }

                } );
				
				// Add a scroll bar to the side, and make room for it
				var mceWindows = top.tinymce.activeEditor.windowManager.getWindows();
				var win = document.getElementById(mceWindows[0]._id);
				var viewportHeight = Math.max(document.documentElement.clientHeight,
						window.innerHeight || 0);
				var currentHeight = parseInt(win.style.height);
				var currentWidth = parseInt(win.style.width) 
				win.style.height = Math.min(currentHeight + 25, viewportHeight - 20) + "px";
				win.style.width = currentWidth + 25 + "px";
				win.style.overflow = "scroll";

            }

        } );

    } );

} )();
