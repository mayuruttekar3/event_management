<?php
/* This function is use to register custom event status start */
add_action('init', 'fn_register_custom_post_status');
function fn_register_custom_post_status() {
	register_post_status('pending_review', [
        'label'                     => _x('Pending Review', 'event'),
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
    ]);

    register_post_status('scheduled', [
        'label'                     => _x('Scheduled', 'event'),
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
    ]);

    register_post_status('rejected', [
        'label'                     => _x('Rejected', 'event'),
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
    ]);
}
// This filter is use to change custom post status colors
add_filter('display_post_states', 'fn_change_post_status_colors', 10, 2);
function fn_change_post_status_colors($states, $post) {
	if ($post->post_type === 'event') {
        if ($post->post_status === 'pending_review') {
            $states[] = '<span style="color:#FFA500;">Pending Review</span>';
        } elseif ($post->post_status === 'scheduled') {
            $states[] = '<span style="color:#000000;">Scheduled</span>';
        } elseif ($post->post_status === 'rejected') {
            $states[] = '<span style="color:#DC3232;">Rejected</span>';
        }
    }
    return $states;
}
/* This function is use to register custom event status start */

/* Admin custom filter start */
add_action('restrict_manage_posts', 'fn_add_event_past_upcoming_filter');
function fn_add_event_past_upcoming_filter() {
	global $typenow;

	if ($typenow == 'event') {
		$filter = $_GET['event_time_filter'] ?? '';
		?>
		<select name="event_time_filter">
			<option value="">All Events</option>
			<option value="past" <?= selected($filter, 'past') ?>>Past Events</option>
			<option value="future" <?= selected($filter, 'future') ?>>Upcoming Events</option>
		</select>
		<?php
	}
}

add_action('restrict_manage_posts', 'fn_add_event_city_dropdown_filter');
function fn_add_event_city_dropdown_filter() {
	global $typenow;

    if ($typenow === 'event') {
        $selected_slug = $_GET['city'] ?? '';

        // Get all terms
        $categories = get_terms([
            'taxonomy' => 'city',
            'hide_empty' => false,
        ]);

        if (!empty($categories) && !is_wp_error($categories)) {
            echo '<select name="city">';
            echo '<option value="">' . esc_html__('All Cities') . '</option>';

            foreach ($categories as $cat) {
                $selected = ($selected_slug === $cat->slug) ? ' selected="selected"' : '';
                echo '<option value="' . esc_attr($cat->slug) . '"' . $selected . '>' . esc_html($cat->name) . '</option>';
            }

            echo '</select>';
        }
    }
}

add_filter('parse_query', 'fn_add_tax_query_into_existing_loop', 99);
function fn_add_tax_query_into_existing_loop($query) {
	global $pagenow;
    if (is_admin() && $pagenow == 'edit.php' && $query->get('post_type') === 'event' && isset($_GET['city']) && is_numeric($_GET['city'])) {
        $query->set('tax_query', [[
            'taxonomy' => 'city',
            'field' => 'slug',
            'terms' => intval($_GET['city']),
        ]]);
    }
}

add_filter('pre_get_posts', 'fn_modify_post_query');
function fn_modify_post_query($query) {
	if (!is_admin() || !$query->is_main_query() || $query->get('post_type') !== 'event') {
        return;
    }
    //~ echo "<pre>";
		//~ print_r($query);
    //~ echo "</pre>";
	//~ die('XOXO 99');
	
    $filter = $_GET['event_time_filter'] ?? '';
    if ($filter === 'past') {
        $query->set('meta_query', [[
            'key' => '_event_end',
            'value' => current_time('Y-m-d H:i:s'),
            'compare' => '<',
            'type' => 'DATETIME',
        ]]);
    } elseif ($filter === 'future') {
        $query->set('meta_query', [[
            'key' => '_event_start',
            'value' => current_time('Y-m-d H:i'),
            'compare' => '>=',
            'type' => 'DATETIME',
        ]]);
    }
}
/* Admin custom filter end */


/* Register custom roles start */
add_action('init', 'register_custom_event_roles');
function register_custom_event_roles() {
    
    // Who only submit events
    add_role('event_submitter', 'Event Submitter', [
        'read' => true,
        'edit_events' => true,
        'edit_event' => true,
        'delete_events' => false,
        'delete_event' => false,
        'publish_events' => false,
        'edit_published_events' => false,
    ]);

    // Who only change events status
    add_role('event_moderator', 'Event Moderator', [
        'read' => true,
        'edit_events' => true,
        'edit_others_events' => true,
        'delete_events' => true,
        'edit_event' => true,
        'delete_event' => true,
        'publish_events' => false,
        'edit_published_events' => false,
    ]);

    // Who do eveything in events
    add_role('event_admin', 'Event Admin', [
        'read' => true,
        'edit_events' => true,
        'edit_others_events' => true,
        'publish_events' => true,
        'delete_events' => true,
        'delete_others_events' => true,
        'edit_published_events' => true,
        'delete_published_events' => true,
    ]);
}

// This will add all Capabilities to wordpress admin
add_action('admin_init', function () {
    $role = get_role('administrator');

    if ($role) {
        $caps = [
            'edit_event',
            'read_event',
            'delete_event',
            'edit_events',
            'edit_others_events',
            'publish_events',
            'read_private_events',
            'delete_events',
            'delete_private_events',
            'delete_published_events',
            'delete_others_events',
            'edit_private_events',
            'edit_published_events',
        ];

        foreach ($caps as $cap) {
            $role->add_cap($cap);
        }
    }
});
/* Register custom roles end */
