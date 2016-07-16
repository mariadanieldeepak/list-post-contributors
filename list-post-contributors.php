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

const LPC_META_KEY = 'list_post_contributors';

add_action( 'add_meta_boxes_post', 'lpc_meta_box_setup' );

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

function render_lpc_meta_box( $object ) {
	// Fetch list of users (author, editor & administrator)
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

	$meta_value = explode(',', get_post_meta( $object->ID, LPC_META_KEY, true ) );
	// User Loop
	if ( ! empty( $user_query->results ) ) {
		foreach ( $user_query->results as $user ) {
			$checked = in_array( (string)$user->ID, $meta_value ) ? "checked" : "";
			if( $checked ) {
				echo '<p><input type="checkbox" name="post-contributors[]" value="' . $user->ID . '" checked="checked" />' . $user->display_name . '</p>';
			} else {
				echo '<p><input type="checkbox" name="post-contributors[]" value="' . $user->ID . '" />' . $user->display_name . '</p>';
			}
			
		}
	} else {
		echo 'No users found.';
	}
	wp_nonce_field( basename( __FILE__ ), 'lpc_class_nonce' );
}

function lpc_save_meta( $post_id, $post ) {
	/* Verify the nonce before proceeding. */
	if ( !isset( $_POST['lpc_class_nonce'] ) || !wp_verify_nonce( $_POST['lpc_class_nonce'], basename( __FILE__ ) ) )
		return $post_id;

	/* Get the post type object. */
	$post_type = get_post_type_object( $post->post_type );

	/* Check if the current user has permission to edit the post. */
	if ( !current_user_can( $post_type->cap->edit_post, $post_id ) )
		return $post_id;

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
      }

    $new_meta_value = '';
	/* Get the posted data and sanitize it for use as an HTML class. */
	if ( isset( $_POST['post-contributors'] ) ) {
		$list_of_users = $_POST['post-contributors'];
		$new_meta_value = implode(',', array_map( 'intval', array_map( 'trim',  $list_of_users ) ) );
	}

	/* Get the meta key. */
  	$meta_key = LPC_META_KEY;

  	/* Get the meta value of the custom field key. */
  	$meta_value = get_post_meta( $post_id, $meta_key, true );

	/* If a new meta value was added and there was no previous value, add it. */
	if ( $new_meta_value && '' == $meta_value )
		add_post_meta( $post_id, $meta_key, $new_meta_value, true );

	/* If the new meta value does not match the old value, update it. */
	elseif ( $new_meta_value && $new_meta_value != $meta_value )
		update_post_meta( $post_id, $meta_key, $new_meta_value );

	/* If there is no new meta value but an old value exists, delete it. */
	elseif ( '' == $new_meta_value && $meta_value )
		delete_post_meta( $post_id, $meta_key, $meta_value );
}
/* Save post meta on the 'save_post' hook. */
add_action( 'save_post_post', 'lpc_save_meta', 10, 2 );

function log_me($message) {
    if (WP_DEBUG === true) {
        if (is_array($message) || is_object($message)) {
            error_log(print_r($message, true));
        } else {
            error_log($message);
        }
    }
}