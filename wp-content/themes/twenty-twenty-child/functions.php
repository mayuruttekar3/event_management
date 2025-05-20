<?php

// Load default jquery min on header
add_filter('wp_enqueue_scripts', 'insert_jquery', 1);
function insert_jquery()
{
    wp_enqueue_script('jquery', false, array(), false, false);
}
/*Rest API*/
include('rest-api-event-submission.php');
include('rest-apis-event-listing.php');
include('admin-event-filter.php');
include('admin-theme-options.php');
include('custom-shortcodes.php');

// Register custom posttype with role capabilities
add_action('init', 'fn_register_event_post_type');
function fn_register_event_post_type() {
    $labels = array(
        'name' => 'Events',
        'singular_name' => 'Event',
        'add_new' => 'Add New Event',
        'edit_item' => 'Edit Event',
        'new_item' => 'New Event',
        'view_item' => 'View Event',
        'all_items' => 'All Events',
        'menu_name' => 'Events',
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'hierarchical' => true,
        'has_archive' => true,
        'rewrite' => array('slug' => 'events', 'with_front' => false),
        'supports' => array('title', 'editor', 'thumbnail', 'custom-fields'),
        'show_in_rest' => true,
        'capability_type' => 'event',
		'map_meta_cap' => true,
		'capabilities' => [
			'edit_post' => 'edit_event',
			'read_post' => 'read_event',
			'delete_post' => 'delete_event',
			'edit_posts' => 'edit_events',
			'edit_others_posts' => 'edit_others_events',
			'publish_posts' => 'publish_events',
			'read_private_posts' => 'read_private_events',
			'delete_posts' => 'delete_events',
			'delete_private_posts' => 'delete_private_events',
			'delete_published_posts' => 'delete_published_events',
			'delete_others_posts' => 'delete_others_events',
			'edit_private_posts' => 'edit_private_events',
			'edit_published_posts' => 'edit_published_events',
		],
    );

    register_post_type('event', $args);
}

// Register category for event posttype
add_action('init', 'fn_register_city_taxonomy');
function fn_register_city_taxonomy() {
    $labels = array(
        'name' => 'Cities',
        'singular_name' => 'City',
        'search_items' => 'Search Cities',
        'all_items' => 'All Cities',
        'parent_item' => 'Parent City',
        'parent_item_colon' => 'Parent City:',
        'edit_item' => 'Edit City',
        'update_item' => 'Update City',
        'add_new_item' => 'Add New City',
        'new_item_name' => 'New City Name',
        'menu_name' => 'Cities',
    );

    $args = array(
        'hierarchical' => true,
        'labels' => $labels,
        'show_ui' => true,
        'show_admin_column' => true,
        'rewrite' => array('slug' => 'city'),
        'show_in_rest' => true,
    );

    register_taxonomy('city', array('event'), $args);
}


// Add meta boxes for event posts
add_action('add_meta_boxes', 'fn_add_event_meta_boxes');
function fn_add_event_meta_boxes() {
    add_meta_box('event_duration', 'Event Duration', 'render_event_duration_box', 'event', 'normal', 'high');
    add_meta_box('organizer_details', 'Organizer Details', 'render_organizer_details_box', 'event', 'normal', 'high');
    add_meta_box('venue_details', 'Venue (with Map Coordinates)', 'render_venue_box', 'event', 'normal', 'high');
    add_meta_box('ticket_price', 'Ticket Price', 'render_ticket_price_box', 'event', 'normal', 'high');
}

function render_event_duration_box($post) {
    $start = get_post_meta($post->ID, '_event_start', true);
    $end = get_post_meta($post->ID, '_event_end', true);
    ?>
    <label>Start Date & Time:</label><br>
    <input type="text" class="admin-datepicker" name="event_start" value="<?php echo esc_attr($start); ?>"><br><br>
    <label>End Date & Time:</label><br>
    <input type="text" class="admin-datepicker" name="event_end" value="<?php echo esc_attr($end); ?>">
    <?php
}

function render_organizer_details_box($post) {
    $name = get_post_meta($post->ID, '_organizer_name', true);
    $email = get_post_meta($post->ID, '_organizer_email', true);
    $phone = get_post_meta($post->ID, '_organizer_phone', true);
    ?>
    <label>Name:</label><br>
    <input type="text" name="organizer_name" value="<?php echo esc_attr($name); ?>"><br><br>
    <label>Email:</label><br>
    <input type="email" name="organizer_email" value="<?php echo esc_attr($email); ?>"><br><br>
    <label>Phone (+91-XXX-XXX-XXXX):</label><br>
    <input type="text" name="organizer_phone" value="<?php echo esc_attr($phone); ?>">
    <?php
}

function render_venue_box($post) {
    $venue = get_post_meta($post->ID, '_event_venue', true);
    $lat = get_post_meta($post->ID, '_event_lat', true);
    $lng = get_post_meta($post->ID, '_event_lng', true);
    ?>
    <label>Venue Name:</label><br>
    <input type="text" name="event_venue" value="<?php echo esc_attr($venue); ?>"><br><br>
    <label>Latitude:</label><br>
    <input type="text" name="event_lat" value="<?php echo esc_attr($lat); ?>"><br><br>
    <label>Longitude:</label><br>
    <input type="text" name="event_lng" value="<?php echo esc_attr($lng); ?>">
    <?php
}

function render_ticket_price_box($post) {
    $price = get_post_meta($post->ID, '_ticket_price', true);
    ?>
    <label>Ticket Price (₹):</label><br>
    <input type="number" step="0.01" name="ticket_price" value="<?php echo esc_attr($price); ?>">
    <?php
}

// Save meta boxes values
add_action('save_post', 'fn_save_event_meta_boxes');
function fn_save_event_meta_boxes($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

    if (get_post_type($post_id) != 'event') return;

    if (isset($_POST['event_start'])) {
        update_post_meta($post_id, '_event_start', sanitize_text_field($_POST['event_start']));
    }
    if (isset($_POST['event_end'])) {
        update_post_meta($post_id, '_event_end', sanitize_text_field($_POST['event_end']));
    }

    if (isset($_POST['organizer_name'])) {
        update_post_meta($post_id, '_organizer_name', sanitize_text_field($_POST['organizer_name']));
    }
    if (isset($_POST['organizer_email'])) {
        update_post_meta($post_id, '_organizer_email', sanitize_email($_POST['organizer_email']));
    }
    if (isset($_POST['organizer_phone'])) {
        update_post_meta($post_id, '_organizer_phone', sanitize_text_field($_POST['organizer_phone']));
    }

    if (isset($_POST['event_venue'])) {
        update_post_meta($post_id, '_event_venue', sanitize_text_field($_POST['event_venue']));
    }
    if (isset($_POST['event_lat'])) {
        update_post_meta($post_id, '_event_lat', sanitize_text_field($_POST['event_lat']));
    }
    if (isset($_POST['event_lng'])) {
        update_post_meta($post_id, '_event_lng', sanitize_text_field($_POST['event_lng']));
    }

    if (isset($_POST['ticket_price'])) {
        update_post_meta($post_id, '_ticket_price', floatval($_POST['ticket_price']));
    }
}

// This function is use to get country wise states wise city on frontend event form and we pass this in  localize script to get without performing ajax call for cities 
function get_city_taxonomy_hierarchy() {
    
    $city_tree = [];
    $hierarchy = [];
    $terms = get_terms([
        'taxonomy' => 'city',
        'hide_empty' => false,
    ]);

    foreach ($terms as $term) {
        $term->children = [];
        $city_tree[$term->term_id] = $term;
    }

    foreach ($terms as $term) {
        if ($term->parent != 0 && isset($city_tree[$term->parent])) {
            $city_tree[$term->parent]->children[] = $term;
        }
    }

    foreach ($city_tree as $term) {
        if ($term->parent == 0) {
            $states = [];
            foreach ($term->children as $state) {
                $cities = [];
                foreach ($state->children as $city) {
                    $cities[] = [
                        'id' => $city->term_id,
                        'name' => $city->name
                    ];
                }
                $states[] = [
                    'id' => $state->term_id,
                    'name' => $state->name,
                    'cities' => $cities
                ];
            }
            $hierarchy[] = [
                'id' => $term->term_id,
                'name' => $term->name,
                'states' => $states
            ];
        }
    }

    return $hierarchy;
}

// This is common datetime picker which used in both side front/admin
function enqueue_common_datetime_assets() {
    wp_enqueue_script('jquery-ui-datepicker');
	wp_enqueue_style('jquery-ui-css', 'https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css');
    wp_enqueue_script( 'jquery-ui-timepicker-addon','https://cdnjs.cloudflare.com/ajax/libs/jquery-ui-timepicker-addon/1.6.3/jquery-ui-timepicker-addon.min.js', ['jquery', 'jquery-ui-datepicker'], null, true );
    wp_enqueue_style( 'jquery-ui-timepicker-addon','https://cdnjs.cloudflare.com/ajax/libs/jquery-ui-timepicker-addon/1.6.3/jquery-ui-timepicker-addon.min.css' );
}

// Frontend register script
add_action('wp_enqueue_scripts','fn_enqueue_custom_scripts');
function fn_enqueue_custom_scripts() {
	enqueue_common_datetime_assets();
    wp_enqueue_script('event-form', get_stylesheet_directory_uri() . '/js/event-form.js', array(), null, true);

    wp_localize_script('event-form', 'EventFormData', [
        'cityHierarchy' => get_city_taxonomy_hierarchy(),
        'nonce' => wp_create_nonce('wp_rest'),
	]);
}

// Backend register script
add_action('admin_enqueue_scripts', 'enqueue_admin_event_datetime_scripts',99);
function enqueue_admin_event_datetime_scripts($hook) {
    global $post_type;

    if ($post_type === 'event') {
        enqueue_common_datetime_assets();
		wp_enqueue_script('admin-event-date-script', get_stylesheet_directory_uri() . '/js/admin-event-datepicker.js', array(), null, true);
    }
}

