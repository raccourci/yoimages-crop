<?php

if ( ! defined ( 'ABSPATH' ) ) {
	die ( 'No script kiddies please!' );
}

function wprie_crop_image() {
	$req_post = esc_html( $_POST['post'] );
	if ( current_user_can( 'edit_post', $req_post ) ) {
		$req_size = esc_html( $_POST['size'] );
		$req_width = esc_html( $_POST['width'] );
		$req_height = esc_html( $_POST['height'] );
		$req_x = esc_html( $_POST['x'] );
		$req_y = esc_html( $_POST['y'] );
		$req_quality = esc_html( $_POST['quality'] );
		$img_path = _load_image_to_edit_path( $req_post );
		$attachment_metadata = wp_get_attachment_metadata( $req_post );
		$replacement = $attachment_metadata['wprie_attachment_metadata']['crop'][$req_size]['replacement'];
		$has_replacement = ! empty ( $replacement ) && get_post( $replacement );
		if ( $has_replacement ) {
			$replacement_path = _load_image_to_edit_path( $replacement );
			$img_editor = wp_get_image_editor( $replacement_path );
		} else {
			$img_editor = wp_get_image_editor( $img_path );
		}
		if ( is_wp_error( $img_editor ) ) {
			return false;
		}
		$cropped_image_sizes = wprie_get_image_sizes( $req_size );
		$img_editor->crop( $req_x, $req_y, $req_width, $req_height, $cropped_image_sizes['width'], $cropped_image_sizes['height'], false );
		$img_editor->set_quality( $req_quality );
		$img_path_parts = pathinfo($img_path);
		if ( empty( $attachment_metadata['sizes'][$req_size] ) || empty( $attachment_metadata['sizes'][$req_size]['file'] ) ) {
			$cropped_image_filename = wprie_get_cropped_image_filename( $img_path_parts['filename'], $cropped_image_sizes['width'], $cropped_image_sizes['height'], $img_path_parts['extension'] );
			$attachment_metadata['sizes'][$req_size] = array(
				'file' => $cropped_image_filename,
				'width' => $cropped_image_sizes['width'],
				'height' => $cropped_image_sizes['height'],
				'mime-type' => $attachment_metadata['sizes']['thumbnail']['mime-type']
			);
		} else {
			$cropped_image_filename = $attachment_metadata['sizes'][$req_size]['file'];
		}
		$img_editor->save( $img_path_parts['dirname'] . '/' . $cropped_image_filename );
		$attachment_metadata['sizes'][$req_size]['width'] = $cropped_image_sizes['width'];
		$attachment_metadata['sizes'][$req_size]['height'] = $cropped_image_sizes['height'];
		if ( empty( $attachment_metadata['wprie_attachment_metadata']['crop'] ) ) {
			$attachment_metadata['wprie_attachment_metadata']['crop'] = array();
		}
		$attachment_metadata['wprie_attachment_metadata']['crop'][$req_size] = array(
				'x' => $req_x,
				'y' => $req_y,
				'width' => $req_width,
				'height' => $req_height
		);
		if ( $has_replacement ) {
			$attachment_metadata['wprie_attachment_metadata']['crop'][$req_size]['replacement'] = $replacement;
		}
		wp_update_attachment_metadata( $req_post, $attachment_metadata );
		echo $cropped_image_filename;
	}
	die();
}

function wprie_edit_thumbnails_page() {
	global $wprie_image_id;
	global $wprie_image_size;
	$wprie_image_id = esc_html( $_GET ['post'] );
	$wprie_image_size = esc_html( $_GET ['size'] );
	if (current_user_can ( 'edit_post', $wprie_image_id ) ) {
		include (WPRIE_PATH . 'inc/html/edit-image-size.php');
	} else {
		die ();
	}
}

function wprie_replace_image_for_size() {
	$id = esc_html( $_POST['image'] );
	$size = esc_html( $_POST['size'] );
	if (current_user_can ( 'edit_post', $id ) ) {
		$attachment_metadata = wp_get_attachment_metadata( $id );
		$attachment_metadata['wprie_attachment_metadata']['crop'][$size]['replacement'] = esc_html( $_POST['replacement'] );
		wp_update_attachment_metadata( $id, $attachment_metadata );
	}
	die();
}

function wprie_restore_original_image_for_size() {
	$id = esc_html( $_POST['image'] );
	$size = esc_html( $_POST['size'] );
	if (current_user_can ( 'edit_post', $id ) ) {
		$attachment_metadata = wp_get_attachment_metadata( $id );
		unset( $attachment_metadata['wprie_attachment_metadata']['crop'][$size]['replacement'] );
		wp_update_attachment_metadata( $id, $attachment_metadata );
	}
	die();
}

add_action( 'wp_ajax_wprie_edit_thumbnails_page', 'wprie_edit_thumbnails_page' );
add_action( 'wp_ajax_wprie_restore_original_image_for_size', 'wprie_restore_original_image_for_size' );
add_action( 'wp_ajax_wprie_crop_image', 'wprie_crop_image' );
add_action( 'wp_ajax_wprie_replace_image_for_size', 'wprie_replace_image_for_size' );
