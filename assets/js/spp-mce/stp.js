( function() {
    tinymce.PluginManager.add( 'stp', function( editor, url ) {
	
		var fileHelperText = 'Use this option for episode posts, and anytime you'
		                   + ' don\'t want the episode to change when your feed is updated.'
						   + ' Enter the file URL below.';
		var feedHelperText = 'Use this option to always display the latest episode'
		                   + ' from your feed.  When your feed is updated, this player'
						   + ' will automatically update as well!'
						   + ' Enter the feed URL below.';
		var savedFileUrlInput = '';
		
		// Shorter name for the default options from the settings page
		var def = smart_podcast_player_user_settings;

        // Add a button that opens a window
        editor.addButton( 'stp_button_key', {

            icon: 'stp-icon',
			tooltip: 'Insert Smart Track Player shortcode',
            onclick: function() {
				
				var colorPrompt;
				var downloadablePrompt;
				var socialPrompt;
				if( isPaidVersionAdmin() ) {
					// For the paid version, the color goes in a text box
					colorPrompt = {
						type: 'textbox',
						name: 'color',
						label: 'Color (Hex) #',
						value: def.bg_color ? def.bg_color : ''
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
									<td><input type="checkbox" id="spp_socialopt_email">Email</input></td> \
									<td></td> \
								</tr> \
								</table>'
					};
				} else {
					// For the free version, only one color is available
					colorPrompt = {
						type: 'listbox',
						name: 'color',
						label: 'Color',
						onselect: function(e) {},
						values: [{text: 'Green', value: '60b86c'}],
						disabled: true,
						tooltip: 'Upgrade to choose any color of the rainbow!'
					}
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

                    title: 'Smart Track Player Shortcode',
                    body: [
						{
						    type: 'container',
							name: 'header_1',
							html: '<em>For displaying individual podcast episodes.</em><br><br>'
							    + '<p><em>Enter default values in Settings —> Smart Podcast Player.</em></p>'
							    + '<div class="spp-mce-hr"></div>'
								+ '<p style="font-weight: bold">1. Choose the episode to display</p>'
						},
						{
							type: 'listbox',
							name: 'fileOrFeed',
							label: '',
							onselect: function(e) {
								var textEl = document.getElementById('fileOrFeedHelperText');
								var urlEl = document.getElementById('stp_builder_url_textbox');
								if( this.state.data.value == "file") {
									textEl.innerText = fileHelperText;
									urlEl.value = savedFileUrlInput;
								} else {
									textEl.innerText = feedHelperText;
									savedFileUrlInput = urlEl.value;
									urlEl.value = def.url ? def.url : '';
								}
							},
							values: [{
								text: 'Play a specific episode of your podcast', value: 'file'
							    }, {
								text: 'Play the most recent episode in your feed', value: 'feed'
							}]
						},
						{
						    type: 'container',
							name: 'fileOrFeedHelper',
							html: '<div id="fileOrFeedHelperText" style="width: 400px; white-space: normal">'
							      + fileHelperText + '<br>&nbsp;</div>',
						},
	                    {
	                        type: 'textbox',
	                        name: 'url',
	                        label: 'URL',
							id: 'stp_builder_url_textbox'
	                    },
						{
						    type: 'container',
							name: 'header_2',
							html: '<div class="spp-mce-hr"></div>'
							    + '<p style="font-weight: bold">2. Customize the appearance</p>'
						},
	                    colorPrompt,
	                    {
	                        type: 'textbox',
	                        name: 'image',
	                        label: 'Image URL',
							value: def.stp_image ? def.stp_image : ''
	                    },
						{
						    type: 'container',
							name: 'header_3',
							html: '<div class="spp-mce-hr"></div>'
							    + '<p style="font-weight: bold">3. Customize the track information</p>'
						},
	                    {
	                        type: 'textbox',
	                        name: 'artist',
	                        label: 'Artist',
							value: def.artist_name ? def.artist_name : ''
	                    },
	                    {
	                        type: 'textbox',
	                        name: 'title',
	                        label: 'Title'
	                    },
						{
						    type: 'container',
							name: 'header_4',
							html: '<div class="spp-mce-hr"></div>'
							    + '<p style="font-weight: bold">4. Customize the buttons</p>'
						},
	                    socialPrompt,
						socialOptionsPrompt,
	                    downloadablePrompt
 
	                ],
					buttons: [ 
						{ text: "Build Shortcode", subtype: 'primary', onclick: 'submit' },
						{ text: "Cancel", onclick: 'close' }
					],
                    onsubmit: function( e ) {

                    	var shortcode = '[smart_track_player';
						
						if( e.data.fileOrFeed == 'feed' )
							shortcode += '_latest';

                    	if( e.data.url != '' )
                    		shortcode += ' url="' + e.data.url + '" ';

                    	if( e.data.title != '' )
                    		shortcode += ' title="' + e.data.title + '" ';

                    	if( e.data.artist != '' && ( !def.artist_name || def.artist_name != e.data.artist ) )
                    		shortcode += ' artist="' + e.data.artist + '" ';

                    	if( e.data.image != '' && ( !def.stp_image || def.stp_image != e.data.image ) )
                    		shortcode += ' image="' + e.data.image + '" ';

                    	if( isPaidVersionAdmin() ) {

							if( e.data.color != '' && ( !def.bg_color || def.bg_color != e.data.color ) )
                    			shortcode += ' color="' + e.data.color + '" ';

							if( def.download ) {
								if( e.data.download != def.download )
									shortcode += ' download="' + e.data.download + '" ';
							} else {
								if( e.data.download != 'true' )
									shortcode += ' download="' + e.data.download + '" ';
							}

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
                    	}

                    	shortcode += ']';

                        // Insert content when the window form is submitted
                        if( e.data.url != '' )
                            editor.insertContent( shortcode );
                        else
                            editor.windowManager.alert("URL is required.");

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
