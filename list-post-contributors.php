<?php
/*
Plugin Name: List Post Contributors
Plugin URI:  http://mariadanieldeepak.com/list-post-authors
Description: Allows you to list multiple contributors for WordPress posts
Version:     0.1.0
Author:      Maria Daniel Deepak
Author URI:  http://mariadanieldeepak.com/
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

List Post Contributors is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.
 
List Post Contributors is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with List Post Contributors. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

// The meta key name to store in `wp_postmeta` table
const LPC_META_KEY = 'list_post_contributors';

function lpc_meta_box_setup() {
    add_meta_box( 
        'lpc-meta-box',
        __( 'List Post Contributors' ),
        'render_lpc_meta_box',
        'post',
        'normal',
        'default'
    );
}
add_action( 'add_meta_boxes_post', 'lpc_meta_box_setup' );

function render_lpc_meta_box( $object ) {
	// Get the already store contributors value if any
	$meta_value = explode(',', get_post_meta( $object->ID, LPC_META_KEY, true ) );
	
	// Get the list of contributors
	$contributors = get_contributors();

	// Display the contributors in the metabox
	if ( ! empty( $contributors ) ) {
		foreach ( $contributors as $contributor ) {
			$checked = in_array( (string)$contributor->ID, $meta_value ) ? "checked" : "";
			if( $checked ) {
				echo '<p><input type="checkbox" name="post-contributors[]" value="' . $contributor->ID . '" checked="checked" />' . $contributor->display_name . '</p>';
			} else {
				echo '<p><input type="checkbox" name="post-contributors[]" value="' . $contributor->ID . '" />' . $contributor->display_name . '</p>';
			}			
		}
	} else {
		echo 'No contributors found.';
	}
	
	// Set nonce to indicate that the request is raised from current site
	wp_nonce_field( basename( __FILE__ ), 'lpc_class_nonce' );
}

function lpc_save_meta( $post_id, $post ) {
	// Verify the nonce before proceeding
	if ( !isset( $_POST['lpc_class_nonce'] ) || !wp_verify_nonce( $_POST['lpc_class_nonce'], basename( __FILE__ ) ) )
		return $post_id;

	// Get the post type object
	$post_type = get_post_type_object( $post->post_type );

	// Check if the current user has permission to edit the post
	if ( !current_user_can( $post_type->cap->edit_post, $post_id ) )
		return $post_id;

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
      }

	// Get the posted data and sanitize it for use
	$new_meta_value = implode(',', array_map( 'intval', array_map( 'trim', ( isset( $_POST['post-contributors'] ) ? $_POST['post-contributors'] : '' ) ) ) );

  	// Get the meta value of the custom field key
  	$meta_value = get_post_meta( $post_id, LPC_META_KEY, true );

	// If a new meta value was added and there was no previous value, add it
	if ( $new_meta_value && '' == $meta_value )
		add_post_meta( $post_id, LPC_META_KEY, $new_meta_value, true );

	// If the new meta value does not match the old value, update it
	elseif ( $new_meta_value && $new_meta_value != $meta_value )
		update_post_meta( $post_id, LPC_META_KEY, $new_meta_value );

	// If there is no new meta value but an old value exists, delete it
	elseif ( '' == $new_meta_value && $meta_value )
		delete_post_meta( $post_id, LPC_META_KEY, $meta_value );
}
// Save post meta on the 'save_post' hook
add_action( 'save_post_post', 'lpc_save_meta', 10, 2 );


function add_contirbutors_box( $content ) {
    global $post;
    $contributors_list = '';
    
    $contributors_id = explode(',', get_post_meta( $post->ID, LPC_META_KEY, true ) );
    $contributors = get_contributors();
	if( !empty( $contributors ) && !empty( $contributors_id ) ) {
		// Contributors Loop
		foreach ( $contributors as $contributor ) {
			if ( in_array( $contributor->ID, $contributors_id ) ) {
				$contributors_list .= '<li>' . get_avatar( $contributor->user_email, 32 ) . '<a href="' . get_author_posts_url( $contributor->ID ) . '">' . $contributor->display_name . '</a></li>';	
			}
		}
		wp_enqueue_style( 'list_post_contributors', plugins_url( 'lib/css/list_post_contributors.css', __FILE__ ) );
		$contributors_box_html = '
		<div id = "lpc-contributors_box">
			<h6>Contributors List</h6>
			<ul class="lpc-contributors_list">
				' . $contributors_list . '  			
			</ul>
		</div>';
		if( !empty( $contributors ) ) {
			return $content . $contributors_box_html;
		} 
	}
    return $content;
}
add_filter('the_content', 'add_contirbutors_box');

function get_contributors() {
	// Fetch list of contributors (author, editor & administrator)
	$user_roles = array( 
			"author", 
			"editor", 
			"administrator"
	);
	$args = array(
		'role__in' => $user_roles
	 ); 
	
	// The Query
	$user_query = new WP_User_Query( $args );
	return $user_query->results;
}