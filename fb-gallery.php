<?php
/*
Plugin Name: Facebook Vinyl
Plugin URI: http://rjksn.me/
Description: A plugin that will allow you to display a facebook gallery in your WordPress.
Author: Ryan Jackson
Version: 0.1.2
Author URI: http://rjksn.me/
*/

class FB_Gallery { 
	public $plugin_url;
	 
	function __construct() {
		
		$this->plugin_url = plugin_dir_url(__FILE__);
		
	    // Add shortcode
		add_shortcode( 'fbgallery', array( $this, 'rjksn_fb_gallery_shortcode') );

	    // Adding "embed form" button
        add_action('media_buttons_context', array( $this, 'add_form_button'));

		add_action('admin_footer',  array( $this, 'add_mce_popup'));
	}
	
	
/********************************************************************
**  ADD EDITOR PAGE BUTTONS
********************************************************************/	
	public function add_form_button( $context ) {
	//    $is_post_edit_page = in_array(RG_CURRENT_PAGE, array('post.php', 'page.php', 'page-new.php', 'post-new.php'));
	//    if(!$is_post_edit_page)
	//        return $context;

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
				shortcode = '[fbgallery id=' + form_id;
				
				if( !jQuery("#display_title").is(":checked") ) { 
					shortcode += ' title=';
				}

				if( !jQuery("#display_description").is(":checked") ) { 
					shortcode += ' desc=';
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
                            Enter your page's username, or ID in the box below.<br/>
                            For example: http://facebook.com/rjksn -> rjksn
                        </span>
                    </div>
                    <div style="padding:15px 15px 0 15px;">
                        <input type="text" name="fbg_fbpage" id="fbg_fbpage" /><br/>
                        <div style="padding:8px 0 0 0; font-size:11px; font-style:italic; color:#5A5A5A">What is the page name of the gallery?</div>
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
                    <div style="padding:15px;">
                        <input type="button" class="button-primary" value="Insert Form" onclick="InsertGallery();"/>&nbsp;&nbsp;&nbsp;
                    <a class="button" style="color:#bbb;" href="#" onclick="tb_remove(); return false;">Cancel</a>
                    </div>
                </div>
            </div>
        </div>

        <?php
    }
	
	public function rjksn_fb_gallery_shortcode( $atts ) {
		extract( shortcode_atts( array(
			'id'     => '', 
			'class'  => 'default',
			'title'  => 'h3',
			'desc'   => 'p',
			'link'   => true,
			'limit'  => ''

		), $atts ) );

		if ( !is_numeric( $id ) ) return '';

		// Clear variables.
		$output_gallery = ''; $output_images = '';

		// Fetch the data
		$curl = curl_init();
			curl_setopt ($curl, CURLOPT_URL, 'https://graph.facebook.com/' . esc_attr( $id ) );
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			$album_details_raw = curl_exec ($curl);
		curl_close ($curl);

		$curl_2 = curl_init();
			curl_setopt ($curl_2, CURLOPT_URL, 'https://graph.facebook.com/' . esc_attr( $id ) . '/photos' );
			curl_setopt($curl_2, CURLOPT_RETURNTRANSFER, 1);
			$photo_details_raw = curl_exec ($curl_2);
		curl_close ($curl_2);

		$album_details = json_decode( $album_details_raw ); 
		$photo_details = json_decode( $photo_details_raw ); 
		
		// Process the data
		$output_images = '';

		if ( !is_int( $limit ) ) $limit = '';
		$limit_count = 0;

		if ( isset( $photo_detais ) && is_array ( $photo_details ) 
			foreach( $photo_details->data as $image_package ) {
				$output_images .= '
					<div class="fbg_image_thumbnail">
						<a class="fbg_image_link" href="' . $image_package->images[0]->source . '" target="_blank" title="' . $image_package->name . '">
							<img src="' . $image_package->images[5]->source . '" width="' . $image_package->images[5]->width . '" height="' . $image_package->images[5]->height . '" />
						</a>
						<a class="fbg_fb_image_link" href="' . $image_package->link . '" title="Facebook: ' . $image_package->name . '">View on Facebook</a>
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
}
$fbgallery = new FB_Gallery;



// We need some CSS to position the paragraph
add_action( 'wp_head', 'rjksn_fbg_css' );
function rjksn_fbg_css() {
	echo "
	<style type='text/css'>
	.fbg_wrapper:after, .fbg_image_wrapper:after { clear: both; display: block; content: ' '; height: 0; overflow: hidden; visibility: hidden; }

	.fbg_image_wrapper { 
	
	}
	
	.fbg_image_thumbnail { position: relative; float: left; line-height: 0; margin-right: 10px; margin-top: 10px; }
	.fbg_image_thumbnail .fbg_image_link { display: block; border: 1px solid #ccc; padding: 5px; width: 180px; height: 135px; }
	.fbg_image_thumbnail .fbg_image_link:hover { border: 1px solid #aaa; }

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