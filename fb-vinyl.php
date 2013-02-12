<?php
/*
Plugin Name: Facebook Vinyl
Plugin URI: http://wordpress.org/extend/plugins/facebook-vinyl/
Description: A plugin that will allow you to display a Facebook gallery in your WordPress.
Author: Ryan Jackson
Version: 1.0
Author URI: http://rjksn.me/
*/

class FB_Vinyl { 
	public $plugin_url;
	private $cachedir;
	private $cachetime;
	private $cacheext;
	private $functional;
	private $options;
	
	function __construct() {

		$this->variable_init();

		// Add shortcode
		add_shortcode( 'fbgallery', array( $this, 'fbvinyl_gallery_shortcode') );
		add_shortcode( 'fbvinyl', array( $this, 'fbvinyl_gallery_shortcode') );

		// Adding "embed form" button
		add_action('media_buttons_context', array( $this, 'add_form_button' ) );
		
		if ( true != $this->options['remove_styles'] )
			add_action('wp_enqueue_scripts', array($this, 'load_styles'));
		
		add_action('admin_footer',  array( $this, 'add_mce_popup'));

	}
	
	/**
	 * Initiate the plugin variables.
	 *
	 * @since 1.0
	 */
	private function variable_init() { 
		$this->start_options();
		
		$this->plugin_url = plugin_dir_url(__FILE__);
		$this->cachedir   = dirname( __FILE__ ) . '/cache/';
		$this->cachetime  = 3600;
		$this->cacheext   = 'cache';

		$this->functional = $this->verify_requirements();
	}

	/**
	 * A function to run through the required features
	 *
	 * @since 1.0
	 */
	private function verify_requirements(){ 
		/* Check to see if cURL is installed */
		if( !$this->iscurlinstalled() ) return false;

		/* Check to see if the cache directory is writable */ 
		if( !$this->cache_permissions_check() ) return false;

		return true;
	}

	
	/**
	 * Check if cURL is installed
	 *
	 * @since 1.0
	 */
	private function iscurlinstalled() {
		if( in_array( 'curl', get_loaded_extensions() ) )
			return true;

		add_action( 'admin_notices',  array( $this, 'admin_notice_curl') );
		return false;
	}

	/**
	 * Display a notice if cURL is not installed
	 *
	 * @since 1.0
	 */
	function admin_notice_curl(){
		echo '<div class="error"><p>'. __('Facebook Vinyl uses cURL, which is not loaded on your site.', 'fbvinyl') . '</p></div>';
	}




	/**
	 * Check if the cache is writable
	 *
	 * @since 1.0
	 */
	private function cache_permissions_check() {
		if( is_writable( $this->cachedir ) ) return true;

		add_action( 'admin_notices',  array( $this, 'admin_notice_cache') );
		return false;
	}

	/**
	 * Display a notice the cache is not writable
	 *
	 * @since 1.0
	 */
	function admin_notice_cache(){
		echo '<div class="error"><p>' . __('Facebook Vinyl‘s cache directory is not writable', 'fbvinyl') . '</p></div>';
	}

	


	/**
	 * Load the styles into the header section of the blog
	 *
	 * @since 0.1
	 */
	public function load_styles() {
		wp_register_style( 'fb-vinyl-styles', $this->plugin_url . 'css/styles.css' );
		wp_enqueue_style( 'fb-vinyl-styles' );
	}







/********************************************************************
**  ADD EDITOR PAGE BUTTONS
********************************************************************/	



	/**
	 * Add the button for the popup that is defined below.
	 *
	 * @since 0.1
	 */

	public function add_form_button( $context ) {

		$button_image = $this->plugin_url . "images/facebook-gallery-select.png";
		$out = '<a href="#TB_inline?width=480&inlineId=select_FBG_gallery" class="thickbox" id="add_FBG" title="' . __('Add Facebook Gallery', 'fbvinyl') . '"><img src="' . $button_image . '" alt="' . __('Add Facebook Gallery', 'fbvinyl') . '" /></a>';
		return $context . $out;
	}
	


	/**
	 * Output the content for the popup that adds the galleries.
	 *
	 * @since 0.1
	 */

	public function add_mce_popup() {
		?>
		<script>
			var delay = (function(){
				var timer = 0;
				return function(callback, ms){
					clearTimeout (timer);
					timer = setTimeout(callback, ms);
				};
			})();

			jQuery(function() {
				var timer = 0;
				if ( jQuery('#fbg_fbpage').val() != '' ) { 
					fbv_getalbums();
				}
				jQuery('#fbg_fbpage').keyup( function(event) {
					
					clearTimeout(timer);
					
					timer = setTimeout(function(){
						jQuery('#fbv_loading').fadeIn();
						jQuery('#fbv_failed').hide();

						fbg_fbpage = jQuery('#fbg_fbpage').val();
						fbg_fbpage = fbg_fbpage.replace('http://', '').replace('https://', '').replace('facebook.com/', '');
						
						jQuery.ajax({
							dataType: 'jsonp',
						  	url: 'https://graph.facebook.com/' + fbg_fbpage + '/albums/',
							type: 'GET',
							success: addGalleryOptions,
							error: function( data ) {
								alert( __('Facebook‘s Graph API might be down.', 'fbvinyl') );
							}
						});
					}, 300);
				
				});
			});
		
			function fbv_getalbums() {
				
				fbg_fbpage = jQuery('#fbg_fbpage').val();
				fbg_fbpage = fbg_fbpage.replace('http://', '').replace('https://', '').replace('facebook.com/', '');
				
				jQuery.ajax({
					dataType: 'jsonp',
				  	url: 'https://graph.facebook.com/' + fbg_fbpage + '/albums/',
					type: 'GET',
					success: addGalleryOptions,
					error: function( data ) {
						alert( __('Facebook‘s Graph API might be down.', 'fbvinyl' ) );
					}
				});
			}

			function addGalleryOptions( data ) {

				jQuery('#fbv_loading').hide()
				if ( typeof data.data == 'undefined' ) {
					jQuery('#fbv_failed').show();
					return false;
				}

				galleries = data.data;
				
				var selectgallery;
				for (var i = 0; i < data.data.length; i++) {
					selectgallery += '<option value="' + data.data[i].id + '">' + data.data[i].name + ' (' + data.data[i].count + ')</option>';
				}
				jQuery('#add_gallery_id option').remove();
				jQuery('#add_gallery_id').append( '<option value=""><?php _e('Select a Gallery', 'fbvinyl' ); ?></option>' + selectgallery );
			}

			function InsertGallery(){
				var form_id = jQuery("#add_gallery_id option:selected").val();
				if(form_id == ''){
					alert( __('Please select a gallery.', 'fbvinyl' ) );
					return;
				}
				
				var shortcode; 
				shortcode = '[fbvinyl id=' + form_id;
				
				if( !jQuery("#display_title").is(":checked") ) { 
					shortcode += ' title=""';
				}

				if( !jQuery("#display_description").is(":checked") ) { 
					shortcode += ' desc=""';
				}

				if( '25' != jQuery("#display_limit").val() ) { 
					shortcode += ' limit="' + jQuery("#display_limit").val() + '"';
				}
				
				window.send_to_editor( shortcode + ']');
			}
		</script>

		<div id="select_FBG_gallery" style="display:none;">
			<div class="wrap">
				<div>
					<div style="padding:15px 15px 0 15px;">
						<h3 style="color:#5A5A5A!important; font-family:Georgia,Times New Roman,Times,serif!important; font-size:1.8em!important; font-weight:normal!important;">
							<?php _e('Pick a Gallery', 'fbvinyl'); ?>
						</h3>
						<span>
							<p>
								<?php _e( 'Enter your page‘s username, or ID in the box below.', 'fbvinyl' ); ?> <br/>
							<?php _e( 'For example: <code>http://facebook.com/flypaperagency</code> username is <code>flypaperagency</code>', 'fbvinyl' ); ?></p>
						</span>
					</div>
					<div style="padding:15px 15px 0 15px;">
						<label for="fbg_fbpage"><strong><?php _e('Facebook Page', 'fbvinyl' ); ?></strong></label>
						<br/>
						<input type="text" style="width: 250px;" name="fbg_fbpage" id="fbg_fbpage" value="<?php echo $this->options['default_page']; ?>" />
						
						<div style="display:inline; padding:4px 0;">
							<span id="fbv_loading" style="display: none;"><img src="<?php echo $this->plugin_url; ?>images/loading.gif" /></span>
							<span id="fbv_failed" style="display: none;"><img src="<?php echo $this->plugin_url; ?>images/failed.gif" /></span>
						</div>
						<br/>
					</div>

					<div style="padding:15px 15px 0 15px;">
						<div id="dropdownwrap">
							<label for="add_gallery_id">
								<strong>
									<?php _e('Facebook Gallery', 'fbvinyl'); ?>
								</strong>
							</label>
							<br/>
							<select id="add_gallery_id" name="add_gallery_id">
								<option value=""><?php _e('Enter the page above, to get the galleries', 'fbvinyl' ); ?></option>
							</select>
						</div>
					<br/>
						<div style="padding:8px 0 0 0; font-size:11px; font-style:italic; color:#5A5A5A"><?php _e('Can‘t find your gallery? Make sure it is active.', 'fbvinyl'); ?></div>
					</div>
					<div style="padding:15px 15px 0 15px;">
						<input type="checkbox" id="display_title" checked='checked' />
						<label for="display_title">
							<?php _e('Display gallery title', 'fbvinyl' ); ?>
						</label> &nbsp;&nbsp;&nbsp;
						
						<input type="checkbox" id="display_description" checked='checked' />
						
						<label for="display_description">
							<?php _e('Display form description', 'fbvinyl' ); ?>
						</label>
						&nbsp;&nbsp;&nbsp;
					</div>
					<div style="padding:15px 15px 0 15px;">
						<input type="text" name="display_limit" id="display_limit" value="<?php echo $this->options['gallery_limit']; ?>" /><br/>
						<div style="padding:8px 0 0 0; font-size:11px; font-style:italic; color:#5A5A5A">
							<?php sprintf( __('Limit the number of results (Facebook‘s default is %d).', 'fbvinyl' ), 25 ); ?>
						</div>
					</div>
					<div style="padding:15px;">
						<input type="button" class="button-primary" value="<?php _e('Insert Gallery', 'fbvinyl'); ?>" onclick="InsertGallery();"/>&nbsp;&nbsp;&nbsp;
						<a class="button" style="color:#bbb;" href="#" onclick="tb_remove(); return false;"><?php _e('Cancel', 'fbvinyl'); ?></a>
					</div>
				</div>
			</div>
		</div>

		<?php
	}
	
	
	/**
	 * The main part of the plugin. This displays the Facebook content.
	 *
	 * @since 0.1
	 */
	public function fbvinyl_gallery_shortcode( $atts ) {
		extract( shortcode_atts( array(
			'id'	 => '', 
			'class'  => 'default',
			'title'  => 'h3',
			'desc'   => 'p',
			'link'   => true,
			'limit'  => '25'
		), $atts ) );

		if ( !is_numeric( $id ) ) return '';
		if ( !is_numeric( $limit ) ) $limit = 25;

		// Clear variables.
		$output_gallery = $output_images = '';

		$album_details = $this->get_album_data( $id ); 
		$photo_details = $this->get_photo_data( $id, $limit ); 
		

		// Process the data
		if ( !is_int( $limit ) ) $limit = '';
		$limit_count = 0;

		if ( isset( $photo_details->data ) && is_array ( $photo_details->data ) ) {
			foreach( $photo_details->data as $image_package ) {

				$iloop = count( $image_package->images ) - 1; 
				while ( $image_package->images[ $iloop ]->width < 180 ) $iloop--; 
				
				//print_r($image_package);
				
				
				$name   = $this->clean( $image_package->from->name );
				$full   = $image_package->images[0];
				$thumb  = $image_package->images[ $iloop ];
				$link   = $image_package->link;
				
				
				
				$output_images .= '
					<div class="fbg_image_thumbnail">
						<a class="fbg_image_link" rel="gallery-' . $id . '" href="' . $full->source . '" target="_blank" title="' . $name . '">
							<img src="' . $thumb->source . '" width="' . $thumb->width . '" height="' . $thumb->height . '" alt="' . $name . '" />
						</a>
						<a class="fbg_fb_image_link" rel="nofollow" href="' . $link . '" title="Facebook: ' . $name . '">' . __('View on Facebook', 'fbvinyl') . '</a>
					</div>';
			}
		} else {
			$output_images = '<!-- There was a problem getting the images in the album. Are there images in it? -->';
		}


		/* Album Details 
		 * 
		 *
		 */

		$output_gallery = '<div class="fbg_wrapper' . ( !empty( $class ) ? ' ' . $class : '' ) . '">
			<div class="fbg_description">' . 
				( !empty( $title ) ? '<' . $title . '>' . $this->clean( $album_details->name )		. '</' . $title . '>' : '' ) .
				( !empty( $desc  ) && isset( $album_details->description ) ? '<' . $desc  . '>' . $this->clean( $album_details->description ) . '</' . $desc  . '>' : '' ) . '
			</div>
			<div class="fbg_image_wrapper">' . $output_images . '</div> ' .

				( !empty( $link ) ? '<a href="' . $album_details->link . '" title="Facebook: ' . $album_details->name .  '" class="fbg_fb_link" target="_blank">' . __('View on Facebook', 'fbvinyl' ) . '</a>': '' ) . '
			</div>';

		return $output_gallery;
	}
	
	private function clean( $string ) {
		return htmlspecialchars( preg_replace("/\s+/", " ", $string) );
	}

	/**
	 * Get the album details from Facebook. Uses load_data
	 *
	 * @since 0.1
	 */
	private function get_album_data( $id ) { 
		return $this->load_data( 'https://graph.facebook.com/' . esc_attr( $id ), 'a-' );
	}


	/**
	 * Get the photo data from facebook. Uses load data
	 *
	 * @since 0.1
	 */
	private function get_photo_data( $id, $limit = 25 ) { 
		return $this->load_data( 'https://graph.facebook.com/' . esc_attr( $id ) . '/photos/?limit=' . (string)$limit, 'p-' );
	}


	/**
	 * Load the data from the specified URL and saves it to the cache
	 *
	 * @since 0.1
	 */
	private function load_data( $url, $pre = NULL ) {

		$cachefile = $this->cachedir . $pre . md5( $url ) . '.' . $this->cacheext;

		$cachefile_created = ( @file_exists($cachefile) ) ? @filemtime( $cachefile ) : 0;
		@clearstatcache();

		// Show file from cache if still valid
		if ( time() - $this->cachetime < $this->cached($cachefile) )
			return json_decode( file_get_contents( $cachefile ) );


		// Do the remote call to Facebook's Graph API
		$response = wp_remote_get( $url );

		// Check to make sure there was not an error
		if( is_wp_error( $response ) )
			return false;
		
		// If the response returned is valid use that data
 		if( '200' == $response['response']['code'] ) {
			$fp = @fopen($cachefile, 'w'); @fwrite( $fp, $response['body'] ); @fclose( $fp );
		} 

		// Since there was an error, make sure there was a cache file
		if ( ! $this->cached($cachefile) ) {
			return false;
		}
		
		// If the cache file exists, return that.
		return json_decode( file_get_contents( $cachefile ) );
	}
	
	private function cached( $cachefile ) { 
		return ( @file_exists($cachefile) ) ? @filemtime( $cachefile ) : 0;
	}
	
	
	
	
	
	
		
/********************************************************************
**  OPTIONS
********************************************************************/





	/**
	 * Start the setup of the options pages
	 *
	 * @since 1.0
	 */
	private function start_options() {
		add_action('admin_init', array( $this, 'options_init' ) );
		add_action('admin_menu', array( $this, 'options_add_page') );
		
		$options = get_option('fbvinyl_options');
		$this->options = $this->options_validate( $options );

		$this->cachetime = $this->options['cache_time'];
		
	}
	
	/**
	 * Init plugin options to white list our options
	 *
	 * @since 1.0
	 */
	public function options_init(){
		register_setting( 'fbvinyl_options_group', 'fbvinyl_options', array( $this, 'options_validate' ) );
	}

	/**
	 * Add menu page
	 *
	 * @since 1.0
	 */
	public function options_add_page() {
		add_options_page( 'Facebook Vinyl', 'Facebook Vinyl', 'manage_options', 'fbvinyl_options', array( $this, 'options_do_page' ) );
	}
	
	/**
	 * Output the options page
	 *
	 * @since 1.0
	 */
	public function options_do_page() { 
		
	?>
		<div class="wrap">
			<div id="icon-options-general" class="icon32"><br></div>
			<h2><?php _e('Facebook Vinyl Options', 'fbvinyl' ); ?></h2>
			
			<form method="post" action="options.php">
				<?php settings_fields('fbvinyl_options_group'); ?>
				<?php $fbvinyl_options = $this->options_validate( get_option('fbvinyl_options') ); ?>
				
				<table class="form-table">
					<tr>
						<td colspan="2">
							<p>
								<?php _e('This plugin integrates Facebook albums into WordPress with an easy to use editor popup. To make it a little more useful we‘re adding default options to make things easier. Please review the options below and change any of the settings to make the plugin better for you.', 'fbvinyl'); ?>
							</p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e('Default Facebook Page', 'fbvinyl'); ?></th>
						<td>
							<input type="text" name="fbvinyl_options[default_page]" value="<?php echo $fbvinyl_options['default_page']; ?>" />
						</td>
					</tr>

					<tr valign="top">
						<th scope="row"><?php _e('Thumbnail Size', 'fbvinyl'); ?></th>
						<td>
							<select name="fbvinyl_options[thumbnail_size]">
								<option value="default">Default</option>
								<option value="full">Full Size</option>
								<option value="960">960 Wide</option>
								<option value="720">720 Wide</option>
								<option value="600">600 Wide</option>
								<option value="480">480 Wide</option>
								<option value="320">320 Wide</option>
								<option value="180">180 Wide</option>
								<option value="130">130 Wide</option>
								<option value="75">75 Wide</option>
							</select>

							<input type="text" name="fbvinyl_options[thumbnail_size]" value="<?php echo $fbvinyl_options['thumbnail_size']; ?>" />
							
							<br/>
							<p>
								<?php _e('Facebook provides an array of sizes. These are based off of the resized widths.', 'fbvinyl'); ?>
							</p>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row">
							<?php _e('Cache Time', 'fbvinyl'); ?>
						</th>
						<td>
							<input type="text" name="fbvinyl_options[cache_time]" value="<?php echo $fbvinyl_options['cache_time']; ?>" /> <span class="fbv_units">ms</span>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row">
							<?php _e('Gallery Limit', 'fbvinyl'); ?>
						</th>
						<td>
							<input type="text" name="fbvinyl_options[gallery_limit]" value="<?php echo $fbvinyl_options['gallery_limit']; ?>" />
						</td>
					</tr>


					<tr valign="top">
						<th scope="row">
							<?php _e('Disable CSS', 'fbvinyl'); ?>
						</th>
						<td>
							<input name="fbvinyl_options[remove_styles]" type="checkbox" value="1" <?php checked('1', $fbvinyl_options['remove_styles'] ); ?> />
						</td>
					</tr>

				</table>
				<p class="submit">
					<input type="submit" class="button-primary" value="<?php _e('Save Changes', 'fbvinyl'); ?>" />
				</p>
			</form>
		</div>
	<?php
	
	}
	
	/**
	 * Validate the options
	 *
	 * @since 1.0
	 */
	public function options_validate( $input ) { 

		if ( !isset( $input['remove_styles'] ) )
			$input['remove_styles'] = 0;
		
		if ( !isset( $input['cache_time'] ) || !is_int( $input['cache_time'] ) )
			$input['cache_time'] = 3600;

		if ( !isset( $input['thumbnail_size'] ) || !is_int( $input['thumbnail_size'] ) )
			$input['thumbnail_size'] = 180;
		
		if ( !isset( $input['gallery_limit'] ) || !is_int( $input['gallery_limit'] ) )
			$input['gallery_limit'] = 25;

		
		return $input;
	}


}
$fb_vinyl_gallery = new FB_Vinyl;