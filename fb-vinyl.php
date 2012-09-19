<?php
/*
Plugin Name: Facebook Vinyl
Plugin URI: http://wordpress.org/extend/plugins/facebook-vinyl/
Description: A plugin that will allow you to display a Facebook gallery in your WordPress.
Author: Ryan Jackson
Version: 0.2.3
Author URI: http://rjksn.me/
*/

class FB_Vinyl { 
	public $plugin_url;
	private $cachedir;
	private $cachetime;
	private $cacheext;
	
	function __construct() {

		$this->variable_init();
		
	    // Add shortcode
		add_shortcode( 'fbgallery', array( $this, 'fbvinyl_gallery_shortcode') );
		add_shortcode( 'fbvinyl', array( $this, 'fbvinyl_gallery_shortcode') );

	    // Adding "embed form" button
        add_action( 'media_buttons_context', array( $this, 'add_form_button' ) );
		add_action( 'wp_head', array( $this, 'header_css' ) );

		add_action('admin_footer',  array( $this, 'add_mce_popup'));

	}
	
	private function variable_init() { 
		$this->plugin_url = plugin_dir_url(__FILE__);
		$this->cachedir   = dirname( __FILE__ ) . '/cache/';
		$this->cachetime  = 3600;
		$this->cacheext   = 'cache';
	}
	
	
/********************************************************************
**  ADD EDITOR PAGE BUTTONS
********************************************************************/	
	public function add_form_button( $context ) {

	    $button_image = $this->plugin_url . "images/facebook-gallery-select.png";
	    $out = '<a href="#TB_inline?width=480&inlineId=select_FBG_gallery" class="thickbox" id="add_FBG" title="Add Facebook Gallery"><img src="' . $button_image . '" alt="Add Facebook Gallery" /></a>';
	    return $context . $out;
	}
	
	public function add_mce_popup() {
        ?>
        <script>
			jQuery(function() {
				jQuery('#fbg_fbpage').change( function(event) {
					fbg_fbpage = jQuery('#fbg_fbpage').val();
					fbg_fbpage = fbg_fbpage.replace('http://', '').replace('https://', '').replace('facebook.com/', '');
					
					
					jQuery.ajax({
					    dataType: 'jsonp',
					  	url: 'https://graph.facebook.com/' + fbg_fbpage + '/albums/',
					    type: 'GET',
					    success: addGalleryOptions,
					    error: function( data ) {
					        alert('Facebook\'s Graph API might be down.');
					    }
					});
				});
			});
			
			function addGalleryOptions( data ) {
				galleries = data.data;
				
				var selectgallery;
				for (var i = 0; i < data.data.length; i++) {
					selectgallery += '<option value="' + data.data[i].id + '">' + data.data[i].name + ' (' + data.data[i].count + ')</option>';
				}
				jQuery('#add_gallery_id option').remove();
				jQuery('#add_gallery_id').append( '<option value="">Select a Gallery</option>' + selectgallery );
			}

            function InsertGallery(){
                var form_id = jQuery("#add_gallery_id option:selected").val();
                if(form_id == ""){
                    alert("Please select a gallery");
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
                        <h3 style="color:#5A5A5A!important; font-family:Georgia,Times New Roman,Times,serif!important; font-size:1.8em!important; font-weight:normal!important;">Pick a Gallery</h3>
                        <span>
                            Enter your page&rsquo;s username, or ID in the box below.<br/>
                            For example: http://facebook.com/flypaperagency -> flypaperagency
                        </span>
                    </div>
                    <div style="padding:15px 15px 0 15px;">
                        <input type="text" name="fbg_fbpage" id="fbg_fbpage" /><br/>
                        <div style="padding:8px 0 0 0; font-size:11px; font-style:italic; color:#5A5A5A">What is the page name of the gallery? (Enter it then tab out)</div>
                    </div>

                    <div style="padding:15px 15px 0 15px;">
                        <div id="dropdownwrap">
                        	<span>Select the desired photo album here.</span>
							<select id="add_gallery_id">
                            	<option value="">Enter the page above, to get the galleries</option>
                        	</select>
						</div>
					<br/>
                        <div style="padding:8px 0 0 0; font-size:11px; font-style:italic; color:#5A5A5A">Can't find your gallery? Make sure it is active.</div>
                    </div>
                    <div style="padding:15px 15px 0 15px;">
                        <input type="checkbox" id="display_title" checked='checked' /> <label for="display_title">Display gallery title</label> &nbsp;&nbsp;&nbsp;
                        <input type="checkbox" id="display_description" checked='checked' /> <label for="display_description">Display form description</label>&nbsp;&nbsp;&nbsp;
                    </div>
                    <div style="padding:15px 15px 0 15px;">
                        <input type="text" name="display_limit" id="display_limit" value="25" /><br/>
                        <div style="padding:8px 0 0 0; font-size:11px; font-style:italic; color:#5A5A5A">Limit the number of results (Facebook&rsquo; s default is 25).</div>
                    </div>
                    <div style="padding:15px;">
                        <input type="button" class="button-primary" value="Insert Form" onclick="InsertGallery();"/>&nbsp;&nbsp;&nbsp;
                    <a class="button" style="color:#bbb;" href="#" onclick="tb_remove(); return false;">Cancel</a>
                    </div>
                </div>
            </div>
        </div>

        <?php
    }
	
	public function fbvinyl_gallery_shortcode( $atts ) {
		extract( shortcode_atts( array(
			'id'     => '', 
			'class'  => 'default',
			'title'  => 'h3',
			'desc'   => 'p',
			'link'   => true,
			'limit'  => '25'

		), $atts ) );

		if ( !is_numeric( $id ) ) return '';
		if ( !is_numeric( $limit ) ) $limit = 25;

		// Clear variables.
		$output_gallery = ''; $output_images = '';

		$album_details = $this->get_album_data( $id ); 
		$photo_details = $this->get_photo_data( $id, $limit ); 
		
		// Process the data
		$output_images = '';

		if ( !is_int( $limit ) ) $limit = '';
		$limit_count = 0;

		if ( isset( $photo_details->data ) && is_array ( $photo_details->data ) ) {
			foreach( $photo_details->data as $image_package ) {
				$output_images .= '
					<div class="fbg_image_thumbnail">
						<a class="fbg_image_link" rel="gallery-' . $id . '" href="' . $image_package->images[0]->source . '" target="_blank" title="' . $image_package->name . '">
							<img src="' . $image_package->images[5]->source . '" width="' . $image_package->images[5]->width . '" height="' . $image_package->images[5]->height . '" />
						</a>
						<a class="fbg_fb_image_link" rel="nofollow" href="' . $image_package->link . '" title="Facebook: ' . $image_package->name . '">View on Facebook</a>
					</div>';
			}
		} else {
			$output_images = '<!-- There was a problem getting the images in the album. Are there images in it? -->';
		}
		
		$output_gallery = '<div class="fbg_wrapper' . ( !empty( $class ) ? ' ' . $class : '' ) . '">
			<div class="fbg_description">' . 
				( !empty( $title ) ? '<' . $title . '>' . $album_details->name        . '</' . $title . '>' : '' ) .
				( !empty( $desc  ) ? '<' . $desc  . '>' . $album_details->description . '</' . $desc  . '>' : '' ) . '
			</div>
			<div class="fbg_image_wrapper">' . $output_images . '</div> ' .

				( !empty( $link ) ? '<a href="' . $album_details->link . '" title="Facebook: ' . $album_details->name .  '" class="fbg_fb_link" target="_blank">View on Facebook</a>': '' ) . '
			</div>';

		return $output_gallery;
	}
	
	private function get_album_data( $id ) { 
		return $this->load_data( 'https://graph.facebook.com/' . esc_attr( $id ), 'a-' );
	}

	private function get_photo_data( $id, $limit = 25 ) { 
		return $this->load_data( 'https://graph.facebook.com/' . esc_attr( $id ) . '/photos/?limit=' . (string)$limit, 'p-' );
	}

	private function load_data( $url, $pre = NULL ) {

	    $cachefile = $this->cachedir . $pre . md5( $url ) . '.' . $this->cacheext;

	    $cachefile_created = ( ( @file_exists($cachefile) ) ) ? @filemtime( $cachefile ) : 0;
	    @clearstatcache();

	    // Show file from cache if still valid
	    if ( time() - $this->cachetime < $cachefile_created ) {
			return json_decode( file_get_contents( $cachefile ) );
	    }

		// Fetch the data
		$curl = curl_init();
			curl_setopt ($curl, CURLOPT_URL, $url );
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			$data = curl_exec ($curl);
		curl_close ($curl);		
		
		$fp = @fopen($cachefile, 'w');

	    // save the contents of output buffer to the file
	    @fwrite( $fp, $data );
	    @fclose( $fp );
		
		return json_decode( $data );
	}
	
	public function header_css() {
		echo "
		<style type='text/css'>
		.fbg_wrapper:after, .fbg_image_wrapper:after { clear: both; display: block; content: ' '; height: 0; overflow: hidden; visibility: hidden; }
		.fbg_wrapper { padding-bottom: 1em; }
		
		.fbg_image_thumbnail { border: 1px solid #ccc; padding: 5px; position: relative; float: left; line-height: 0; margin-right: 10px; margin-top: 10px; }
		.fbg_image_thumbnail:hover { border: 1px solid #aaa; }
		
		.fbg_image_thumbnail .fbg_image_link { display: block;  width: 180px; height: 135px; overflow: hidden; }

		.fbg_image_thumbnail .fbg_fb_image_link { display: none; }
		.fbg_image_thumbnail:hover .fbg_fb_image_link { 
			z-index: 99; 
			display: block; 
			width: 100%; 
			height: 12px; 
			text-indent: 40px; 
			background-color: #3B5998;
			background-color: rgba(0,0,0,.5);
			background-image: url(" . plugin_dir_url(__FILE__) . "images/fb-link-tab.png);
			background-repeat: no-repeat;
			background-position: left top;
			position: absolute; 
			left: 0; 
			bottom: 12px; 
			font-size: 11px; 
			font-family: \"lucinda grande\", sans-serif; 
			color: #fff !important;
			line-height: 0;
			padding-top: 12px;
		}

		.fbg_wrapper .fbg_fb_link { font-size: 11px; font-family: \"lucinda grande\", sans-serif; align: right; color: #3B5998; }

		</style>
		";
	}
}
$fb_vinyl_gallery = new FB_Vinyl;