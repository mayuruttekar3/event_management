<?php
/*
add_action( 'widgets_init', 'fn_register_custom_sidebar_widget' );
function fn_register_custom_sidebar_widget() {
    register_sidebar( array(
        'name'          => __( 'Event Sidebar', 'textdomain' ),
        'id'            => 'event_sidebar',
        'description'   => __( 'Sidebar for displaying a random event', 'textdomain' ),
        'before_widget' => '<div class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ) );
}*/

add_shortcode('display_random_event', 'fn_display_random_upcoming_event');
function fn_display_random_upcoming_event() {
    $transient_key = 'random_upcoming_event';
    $event_output = get_transient( $transient_key );

    if ( false === $event_output ) {
        // No cached output, fetch a random upcoming event
        $today = date( 'Y-m-d H:i:s' );
        $args = array(
            'post_type'      => 'event',
            'posts_per_page' => 1,
            'orderby'        => 'rand',
            'meta_query'     => array(
                array(
                    'key'     => '_event_start',
                    'value'   => $today,
                    'compare' => '>=',
                    'type'    => 'DATE',
                ),
            ),
        );
        $event_query = new WP_Query( $args );

        ob_start();
        if ( $event_query->have_posts() ) {
            while ( $event_query->have_posts() ) {
                $event_query->the_post();
                ?>
                <div class="random-event">
					<?php if ( has_post_thumbnail() ) : ?>
						<div class="event-thumb">
							<?php the_post_thumbnail( 'medium' ); ?>
						</div>
					<?php endif; ?>
                    <h4><?php the_title(); ?></h4>
                </div>
                <?php
            }
            wp_reset_postdata();
        } else {
            echo '<p>No upcoming events found.</p>';
        }

        $event_output = ob_get_clean();
        set_transient( $transient_key, $event_output, 1 * MINUTE_IN_SECONDS ); // Event change after every 1 minute
    }

    echo $event_output;
}
