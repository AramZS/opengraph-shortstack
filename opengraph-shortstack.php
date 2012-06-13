<?php

/*
Plugin Name: Open Graph Shortstack
Plugin URI: http://aramzs.me/og-shortstack
Description: This plugin does some nifty stuff with open graph. 
Version: 0.2
Author: Aram Zucker-Scharff
Author URI: http://aramzs.me
License: GPL2
*/

/*  Copyright 2012  Aram Zucker-Scharff  (email : azuckers@gmu.edu)

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

require_once("OpenGraph.php");

//Based on code at http://wefunction.com/2008/10/tutorial-creating-custom-write-panels-in-wordpress/

$og_link_new_meta_boxes =
	array(
		"oglink" => array(
		
			"name" => "oglink",
			"std" => "",
			"description" => "Add a link to the webpage you want Open Graph data from."
			)
		
	);

function og_link_new_meta_boxes() {

	global $post, $og_link_new_meta_boxes;
	foreach($og_link_new_meta_boxes as $meta_box){
		$meta_box_value = get_post_meta($post->ID, $meta_box['name'], true);
		if($meta_box_value == ""){
			$meta_box_value=$meta_box['std'];
		}
	}

		echo'<input type="hidden" name="'.$meta_box['name'].'_noncename" id="'.$meta_box['name'].'_noncename" value="'.wp_create_nonce( plugin_basename(__FILE__) ).'" />';
		echo'<p><input type="text" name="'.$meta_box['name'].'" value="'.$meta_box_value.'" size="55" /><br />';
		echo'<label for="'.$meta_box['name'].'">'.$meta_box['description'].'</label></p>';
}

function og_link_create_meta_box() {
	if ( function_exists('add_meta_box') ) {
		add_meta_box( 'og_link_new_meta_boxes', 'Open Graph Link', 'og_link_new_meta_boxes', 'post', 'normal', 'high' );
	}
}

function og_link_save_postdata( $post_id ) {
	global $post, $og_link_new_meta_boxes;

	foreach($og_link_new_meta_boxes as $meta_box) {
		if ( !wp_verify_nonce( $_POST[$meta_box['name'].'_noncename'], plugin_basename(__FILE__) )) {
			return $post_id;
		}

		if ( 'page' == $_POST['post_type'] ) {
			if ( !current_user_can( 'edit_page', $post_id ))
			return $post_id;
		} else {
			if ( !current_user_can( 'edit_post', $post_id ))
			return $post_id;
		}

		$data = $_POST[$meta_box['name']];

		if(get_post_meta($post_id, $meta_box['name']) == "")
			add_post_meta($post_id, $meta_box['name'], $data, true);
		elseif($data != get_post_meta($post_id, $meta_box['name'], true))
			update_post_meta($post_id, $meta_box['name'], $data);
		elseif($data == "")
		delete_post_meta($post_id, $meta_box['name'], get_post_meta($post_id, $meta_box['name'], true));
	}
}
add_action('admin_menu', 'og_link_create_meta_box');
add_action('save_post', 'og_link_save_postdata');

function og_additive($content) {
	if( is_singular() && is_main_query() ) {

		$postID = get_the_ID();

		add_post_meta($postID, 'opengraph_image_cache', '', true);
		add_post_meta($postID, 'opengraph_title_cache', '', true);
		add_post_meta($postID, 'opengraph_descrip_cache', '', true);

		$checkogcache = get_post_meta($postID, 'opengraph_image_cache', true);
		
		$oguserlink = get_post_meta($postID, 'oglink', true);
	
/** for testing 
			$page = $oguserlink;
			$node = OpenGraph::fetch($page);
			
			$ogImage = $node->image;
			$ogTitle = $node->title;
			$ogDescrip = $node->description;
			print_r( $node );
			die();
**/	
		
		
		if (empty($checkogcache)){

			$page = $oguserlink;
			
			$node = OpenGraph::fetch($page);
			
			$ogImage = $node->image;
			$ogTitle = $node->title;
			$ogDescrip = $node->description;
			
			update_post_meta($postID, 'opengraph_title_cache', $ogTitle);
			update_post_meta($postID, 'opengraph_descrip_cache', $ogDescrip);
			


			
			
			if ( (strlen($ogImage)) > 0 ){
			
				$imgParts = pathinfo($ogImage);
				$imgExt = $imgParts['extension'];
				$imgTitle = $imgParts['filename'];

				
				//'/' . get_option(upload_path, 'wp-content/uploads') . '/' . date("o") 
				$uploads = wp_upload_dir();
				$ogCacheImg = 'wp-content/uploads' . $uploads[subdir] . "/" . $postID . "-" . $imgTitle . "." . $imgExt;
				
				
				if ( !file_exists($ogCacheImg) ) {
				

					copy($ogImage, $ogCacheImg);
				
				}
				//$ogCacheImg = $ogImage;
				
			} else {
			
				$oglinkpath = plugin_dir_url(__FILE__);
			
				$ogCacheImg = $oglinkpath . 'link.png';
			
			}
			
			update_post_meta($postID, 'opengraph_image_cache', $ogCacheImg);
			
		} else {
		
			$ogCacheImg = get_post_meta($postID, 'opengraph_image_cache', true);
			
		}
		
		$ogTitle = get_post_meta($postID, 'opengraph_title_cache', true);
		$ogDescrip = get_post_meta($postID, 'opengraph_descrip_cache', true);

		$new_content = 
		'<div class="oglinkbox">
			<div class="oglinkimg">
				<a href="' . $oguserlink . '" title="' . $ogTitle . '"><img alt="' .  $ogTitle . '" src="' . $ogCacheImg . '" /></a>
			</div>
			<div class="oglinkcontent">
				<h4><a href="' . $oguserlink . '" title="' . $ogTitle . '">' . $ogTitle . '</a></h4>
				<p>' . $ogDescrip . '</p>
			</div>';
			
		$content .= $new_content;	
	}	
	return $content;
}
add_filter('the_content', 'og_additive', 2);


?>