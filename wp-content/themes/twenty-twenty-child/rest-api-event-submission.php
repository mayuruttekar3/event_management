<?php

/* Register endpoint api to submit events from frontend form */
add_action('rest_api_init', function () {
    register_rest_route('v1/events', '/submit', [
        'methods' => 'POST',
        'callback' => 'handle_event_submission',
        'permission_callback' => '__return_true',
    ]);
});

function handle_event_submission(WP_REST_Request $request) {
	
	if ( get_option('maintenance_flag') ) {
		return new WP_REST_Response(['success' => false, 'message' => 'Event maintenance mode'], 500);
	}
	
	// This is for spam check if frontend is filled with this value then we stop here
    if (!empty($request['easter_egg_note'])) {
        return new WP_REST_Response(['success' => false, 'message' => 'Bot detected.'], 403);
    }

    $title = sanitize_text_field($request['event_title']);
    $start = sanitize_text_field($request['event_start']);
    $end = sanitize_text_field($request['event_end']);
    $name = sanitize_text_field($request['organizer_name']);
    $email = sanitize_email($request['organizer_email']);
    $phone = sanitize_text_field($request['organizer_phone']);
    $venue = sanitize_text_field($request['venue']);
    $lat = sanitize_text_field($request['lat']);
    $lng = sanitize_text_field($request['lng']);
    $price = floatval($request['ticket_price']);
	
	// Here we set event post status as 'pending_review' which is out custom post status
    $event_id = wp_insert_post([
        'post_type' => 'event',
        'post_title' => $title,
        'post_status' => 'pending_review',
    ]);

    if (is_wp_error($event_id)) {
        return new WP_REST_Response(['success' => false, 'message' => 'Failed to create event.'], 500);
    }
    
    $city_term_id = intval($request['city']);
	if ($city_term_id && term_exists($city_term_id, 'city')) {
		wp_set_post_terms($event_id, [$city_term_id], 'city');
	}


    update_post_meta($event_id, '_event_start', $start);
    update_post_meta($event_id, '_event_end', $end);
    update_post_meta($event_id, '_organizer_name', $name);
    update_post_meta($event_id, '_organizer_email', $email);
    update_post_meta($event_id, '_organizer_phone', $phone);
    update_post_meta($event_id, '_event_venue', $venue);
    update_post_meta($event_id, '_event_lat', $lat);
    update_post_meta($event_id, '_event_lng', $lng);
    update_post_meta($event_id, '_ticket_price', $price);

    // Upload image handling
    if (!empty($_FILES['event_image'])) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        $upload = wp_handle_upload($_FILES['event_image'], ['test_form' => false]);
        if (!isset($upload['error']) && isset($upload['file'])) {
            $filetype = wp_check_filetype($upload['file']);
            $attachment = [
                'post_mime_type' => $filetype['type'],
                'post_title'     => sanitize_file_name($upload['file']),
                'post_status'    => 'inherit'
            ];
            $attach_id = wp_insert_attachment($attachment, $upload['file'], $event_id);
            require_once ABSPATH . 'wp-admin/includes/image.php';
            $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
            wp_update_attachment_metadata($attach_id, $attach_data);
            set_post_thumbnail($event_id, $attach_id);
        }
    }

    return new WP_REST_Response(['success' => true, 'event_id' => $event_id], 200);
}
