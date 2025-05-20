<?php
// http://localhost/wp_sample/wp-json/events/v1/list
// http://localhost/wp_sample/wp-json/events/v1/list?start_date=2025-05-21&end_date=2025-06-30
/* Rest API start */

add_action('rest_api_init','fn_register_rest_apis');
function fn_register_rest_apis() {
	register_rest_route('events/v1', '/list', [
        'methods'  => 'GET',
        'callback' => 'get_custom_events_api',
        'permission_callback' => '__return_true',
    ]);
}
function get_custom_events_api($request) {
	
	$params = $request->get_params();
	
	if ( get_option('maintenance_flag') ) {
		return new WP_REST_Response(['success' => false, 'message' => 'Event maintenance mode'], 500);
	}

    $paged = isset($params['page']) ? max(1, intval($params['page'])) : 1;
    $per_page = isset($params['per_page']) ? max(1, intval($params['per_page'])) : 10;

    $meta_query = [];
    $tax_query = [];
    
    if (!empty($params['start_date']) || !empty($params['end_date'])) {
        $date_range = ['relation' => 'AND'];

        if (!empty($params['start_date'])) {
            $date_range[] = [
                'key'     => '_event_start',
                'value'   => sanitize_text_field($params['start_date']),
                'compare' => '>=',
                'type'    => 'DATE',
            ];
        }

        if (!empty($params['end_date'])) {
            $date_range[] = [
                'key'     => '_event_end',
                'value'   => sanitize_text_field($params['end_date']),
                'compare' => '<=',
                'type'    => 'DATE',
            ];
        }

        $meta_query[] = $date_range;
    }

    // Filter by city (taxonomy slug or ID)
    if (!empty($params['city'])) {
        $tax_query[] = [
            'taxonomy' => 'city',
            'field'    => is_numeric($params['city']) ? 'term_id' : 'slug',
            'terms'    => sanitize_text_field($params['city']),
        ];
    }

    // Sorting
    $orderby = 'meta_value';
    $order = 'DESC';
    $meta_key = '_event_start';

    if (!empty($params['orderby'])) {
        switch ($params['orderby']) {
            case 'date':
            default:
                $meta_key = '_event_start';
                break;
        }
    }

	

    $query = new WP_Query([
        'post_type'      => 'event',
        'posts_per_page' => $per_page,
        'paged'          => $paged,
        'post_status'    => 'publish',
        's'              => sanitize_text_field($params['search'] ?? ''),
        'orderby'        => $orderby,
        'order'          => $order,
        'meta_key'       => $meta_key,
        'meta_query'     => $meta_query,
        'tax_query'      => $tax_query,
    ]);

    $events = [];
    
    //~ echo "<pre>";
		//~ print_r( $query );
    //~ echo "</pre>";
    //~ die('XOXO 91');

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();

            $events[] = [
                'id'       => get_the_ID(),
                'title'    => get_the_title(),
                'link'     => get_permalink(),
                'start'    => get_post_meta(get_the_ID(), '_event_start', true),
                'end'      => get_post_meta(get_the_ID(), '_event_end', true),
                'city'     => wp_get_post_terms(get_the_ID(), 'city', ['fields' => 'names']),
            ];
        }
        wp_reset_postdata();
    }

    return rest_ensure_response([
        'total'      => $query->found_posts,
        'page'       => $paged,
        'per_page'   => $per_page,
        'total_page' => $query->max_num_pages,
        'events'     => $events,
    ]);
}

// This filter is use for set limit
add_filter('rest_pre_dispatch', 'fn_limit_access_same_ip', 10, 3);
function fn_limit_access_same_ip($result, $server, $request) {
	
	if ($request->get_route() === '/events/v1/list') {
		
        //$ip = '127.0.0.1'; // This is for localhost
        $ip = $_SERVER['REMOTE_ADDR']; // This is for actual address
        $key = 'event_api_' . md5($ip);
        $count = get_transient($key) ?: 0;

        if ($count > 30) {
            return new WP_Error('rate_limit', 'Rate limit exceeded. Try again later.', ['status' => 429]);
        }
        set_transient($key, $count + 1, MINUTE_IN_SECONDS);
    }
    return $result;
}
/* Rest API end */
