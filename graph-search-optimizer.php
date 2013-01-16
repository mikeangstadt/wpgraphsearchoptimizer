<?php
/*
Plugin Name: Graph Search Optimizer
Plugin URI: http://blog.atomni.com/graph-search-optimizer
Description: This plugin automatically configures your install to use applicable Facebook OpenGraph meta tags and exposes the ability to create FBXML elements using shortcodes.
Version: v1.0
Author: Michael Angstadt
Author URI: http://www.mikeangstadt.com
License: GPLv2
*/
?>
<?php
/*  Copyright 2013  Michael Angstadt  (email : mjangstadt@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
require_once(  ABSPATH . '/wp-content/plugins/graph-search-optimizer/facebook.php');

		$appId = get_option('facebook_app_id');
		$appSecret = get_option('facebook_app_secret');
		
		$facebook = new Facebook(array(
		'appId' => $appId,
		'secret' => $appSecret
		));
		
function add_OGMetaTags()
{
	global $post; 
	setup_postdata($post);
	
	$post_id = $post->ID; 
	
	$feat_image = wp_get_attachment_url( get_post_thumbnail_id($post_id) );
	$admin_email = get_settings('admin_email');

	echo '<meta property="fb:app_id" content="'.get_option('facebook_app_id').'" />

<meta property="fb:admins" content="'.get_option('facebook_page_admin_id').'" />
<meta property="og:title" content="'.get_the_title($post_id).'" />
<meta property="og:site_name" content="'.get_bloginfo().'"/>';
  $post_type = get_post_type($post_id);
  $og_type = "website";
  switch($post_type)
  {
	case get_option('og_post_type'):	$og_type = get_option('og_custom_objecttype');
										
										if(!$feat_image || $feat_image == "")
											$feat_image = getGalleryImageURL($post_id);
										
										break;
	case "post":						$og_type = "article";
										echo "<meta property:'article:published_time' content='".get_the_date('F jS Y, g:ia')."' />";
										echo "<meta property='article:author' content='".esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) )."' />";
										$posttags = get_the_tags($post_id);
										if($posttags)
										foreach($posttags as $posttag)
										{
											echo "<meta property='article:tag' content='".$posttag->name."' />";
										}
										$categories = get_the_category($post_id);
										if($categories)
											echo "<meta property='article:section' content='".$categories[0]->name."' />";
										break;
	case "page":						$og_type = "website";
										break;
  }  if($feat_image && isset($feat_image) && $feat_image != "")
	echo '<meta property="og:image" content="'.$feat_image.'" />';  else	echo '<meta property="og:image" content="'.get_option('og_meta_defaultImage').'" />';	
  echo '<meta property="og:type" content="'.$og_type.'" />
  <meta property="og:url" content="'.get_permalink($post_id).'" />';
  
  $the_excerpt = explode(" <a href", str_replace('"', '', str_replace("'", "", get_the_excerpt())));
  
  echo '<meta property="og:description" content="'.$the_excerpt[0].'" />';
  
  
  echo '<script type="text/javascript">';
  //make sure jquery is around, then add the fb xml hooks
  echo '
  if(!window.$)
  {
	var elmScript = document.createElement("script");
	elmScript.src = "http//ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js";
	elmScript.type = "text/javascript";
	document.getElementsByTagName("head")[0].appendChild( elmScript );
  }
  $(document).ready(function(){
			$("html").attr("xmlns:og","http://ogp.me/ns#");
			$("html").attr("xmlns:fb", "https://www.facebook.com/2008/fbml");
		});';
		
  echo '</script>';
}
add_action('wp_head', 'add_OGMetaTags');

//add_shortcode( 'gso_custom_action', 'gso_custom_action_handler' );

/*function gso_custom_action_handler()
{
	global $facebook;
	$response = get_option('og_custom_action');
	
	echo "<a class='fb_custom_action' href='javascript:doCustomFacebookAction();'>Got Pierced?</a>";
	echo "<script type='text/javascript'>";
	
	$responseSplit = explode("// handle the response", $response);
	
	echo "function doCustomFacebookAction() { ".$responseSplit[0]." alert(response); $('.fb_custom_action').html('Got Pierced!'); ".$responseSplit[1]." }";
echo "</script>";
}*/

add_action('admin_menu', 'gso_create_menu');
function gso_create_menu() {

	//create new top-level menu
	add_menu_page('Graph Search Optimizer Plugin Settings', 'GSO Settings', 'administrator', __FILE__, 'gso_settings_page',plugins_url('/images/icon.png', __FILE__));

	//call register settings function
	add_action( 'admin_init', 'register_mysettings' );
}
function gso_admin_options_head(){

	print '<link rel="stylesheet" href="../wp-content/plugins/graph-search-optimizer/css/admin.css" />';
}
add_action('admin_head', 'gso_admin_options_head');

function getGalleryImageURL($post_id)
{
	$galleryImageMetaID = get_post_meta($post_id, "gallery_image", true);
	
	if($galleryImageMetaID != "" && $galleryImageMetaID > 0)
	{
		$image_attributes = wp_get_attachment_image_src(get_post_meta($post_id, "gallery_image", true), array(50,50));
		$big_images = wp_get_attachment_image_src(get_post_meta($post_id, "gallery_image", true), array(600,600));                
		
		if(isset($big_images[0]) && $big_images[0] != "")
		{
			return $big_images [0];
		}		
	}
	else 
	{
		$post_content = get_the_content($post_id);
		if(isset($post_content) && $post_content != "" && stripos($post_content, "img"))
		{	
			$doc = new DOMDocument();
			$doc->loadHTML($post_content);
			$imageTags = $doc->getElementsByTagName('img');

			foreach($imageTags as $tag) {
				$imageSrc = $tag->getAttribute('src');
			}
			
			return preg_replace('/-(\d+)x(\d+)\./i', ".", $imageSrc);
		}
	}
}

function register_mysettings() {
	//register our settings
	register_setting( 'gso-settings-group', 'facebook_app_id' );
	register_setting( 'gso-settings-group', 'facebook_page_admin_id' );
	register_setting( 'gso-settings-group', 'facebook_app_namespace' );
	register_setting( 'gso-settings-group', 'facebook_app_secret' );
	register_setting( 'gso-settings-group', 'og_meta_pages' );
	register_setting( 'gso-settings-group', 'og_meta_articles' );
	register_setting( 'gso-settings-group', 'og_post_type' );
	register_setting( 'gso-settings-group', 'og_custom_objecttype' );
	//register_setting( 'gso-settings-group', 'og_custom_action' );
	register_setting( 'gso-settings-group', 'og_meta_phone' );
	register_setting( 'gso-settings-group', 'og_meta_city' );
	register_setting( 'gso-settings-group', 'og_meta_country' );
	register_setting( 'gso-settings-group', 'og_meta_state' );	register_setting( 'gso-settings-group', 'og_meta_defaultImage' );	register_setting( 'gso-settings-group', 'og_meta_defaultImage');
}

function gso_settings_page() {
?>
<div class="wrap">
<h2>Graph Search Optimizer</h2>
<form method="post" action="options.php">
    <?php settings_fields( 'gso-settings-group' ); ?>
    <table class="form-table">
		<tr valign="top">
			<th scope="row">Facebook App ID</th>
			<td><input type="text" name="facebook_app_id" value="<?php echo get_option('facebook_app_id'); ?>" /></td>
        </tr>
		<tr valign="top">
			<th scope="row">Facebook App Secret</th>
			<td><input type="text" name="facebook_app_secret" value="<?php echo get_option('facebook_app_secret'); ?>" /></td>
        </tr>
		<tr valign="top">
			<th scope="row">Facebook App Namespace</th>
			<td><input type="text" name="facebook_app_namespace" value="<?php echo get_option('facebook_app_namespace'); ?>" /></td>
        </tr>
		<tr valign="top">
			<th scope="row">Facebook App Admin User ID</th>
			<td><input type="text" name="facebook_page_admin_id" value="<?php echo get_option('facebook_page_admin_id'); ?>" /></td>
        </tr>
		<tr valign="top">
			<th scope="row">Contact Phone Number</th>
			<td><input type="text" name="og_meta_phone" value="<?php echo get_option('og_meta_phone'); ?>" /></td>
        </tr>
		<tr valign="top">
			<th scope="row">City</th>
			<td><input type="text" name="og_meta_city" value="<?php echo get_option('og_meta_city'); ?>" /></td>
        </tr>
		<tr valign="top">
			<th scope="row">State</th>
			<td><input type="text" name="og_meta_state" value="<?php echo get_option('og_meta_state'); ?>" /></td>
        </tr>
		<tr valign="top">
			<th scope="row">Country</th>
			<td><input type="text" name="og_meta_country" value="<?php echo get_option('og_meta_country'); ?>" /></td>
        </tr>		<tr valign="top">		<th scope="row">Default Image URL</th>			<td><input type="text" name="og_meta_defaultImage" value="<?php echo get_option('og_meta_defaultImage'); ?>" /></td>		</tr>
		<tr valign="top">
			<th scope="row">Custom Object Type for Post Type (OpenGraph->Dashboard->Object Types: Get Custom Type, Copy &amp; Paste here.)</th>
			<td><label>Post Type:</label><select name="og_post_type">
					
					<?php
					$_selected = get_option('og_post_type');
					?>
					<option value="" <?php selected( $pt->name, $_selected );?>>None</option>
					<?php
					$pts = get_post_types();
					foreach ( get_post_types( array(), 'objects' ) as $pt ) :
					?>
						<option value="<?php echo $pt->name; ?>"
							<?php selected( $pt->name, $_selected ); ?>>
							<?php echo $pt->labels->name; ?>
						</option>
					<?php
					endforeach;
					?>
				</select>
				<label>Object Type</label>
				<input type="text" name="og_custom_objecttype" value="<?php echo get_option('og_custom_objecttype'); ?>" /></td>
        </tr>
		<!-- <tr valign="top">
			<th scope="row">Custom Action (OpenGraph->Dashboard->Action Types: Get Code, Copy &amp; Paste <b>Create a new <i>your action</i> action</b> code snippet for JavaScript SDK here)</th>
			<td><input type="text" name="og_custom_action" value="<?php echo get_option('og_custom_action'); ?>" /></td>
        </tr>
		<tr valign="top">
			<th>Use shortcode [gso_custom_action] to render out an action button.</th>
		</tr> -->
    </table>
    <?php submit_button(); ?>

</form>
</div>
<?php } 
?>