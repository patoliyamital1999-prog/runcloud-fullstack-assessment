<?php
/**
 * Plugin Name: Post Votes API
 * Plugin URI: http://localhost
 * Description: Custom voting system with REST API, admin dashboard, and Redis-ready object caching.
 * Version: 1.0
 * Author: Mital Patoliya
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('PVA_SECRET_TOKEN')) {
    define('PVA_SECRET_TOKEN', 'my_secure_secret_token_123');
}

if (!defined('PVA_CACHE_KEY')) {
    define('PVA_CACHE_KEY', 'pva_posts_api_cache');
}

if (!defined('PVA_CACHE_GROUP')) {
    define('PVA_CACHE_GROUP', 'post_votes_api');
}

if (!defined('PVA_CACHE_EXPIRATION')) {
    define('PVA_CACHE_EXPIRATION', 300);
}


function pva_get_posts_cache_key($request) {
    $version = (int) get_option('pva_posts_cache_version', 1);

    return PVA_CACHE_KEY . '_v' . $version . '_' . md5(wp_json_encode($request->get_params()));
}

# clear post case

function pva_clear_posts_cache() {
    $version = (int) get_option('pva_posts_cache_version', 1);
    update_option('pva_posts_cache_version', $version + 1, false);
}

# check vote data avalible on post_meta table
function pva_ensure_vote_meta($post_id) {
    if (!metadata_exists('post', $post_id, '_pva_upvotes')) {
        add_post_meta($post_id, '_pva_upvotes', 0, true);
    }

    if (!metadata_exists('post', $post_id, '_pva_downvotes')) {
        add_post_meta($post_id, '_pva_downvotes', 0, true);
    }
}

#Initialize vote meta
function pva_activate_plugin() {
    $posts = get_posts(array(
        'post_type' => 'post',
        'post_status' => 'any',
        'numberposts' => -1,
        'fields' => 'ids',
    ));

    foreach ($posts as $post_id) {
        pva_ensure_vote_meta($post_id);
    }
}
register_activation_hook(__FILE__, 'pva_activate_plugin');

function pva_get_cached_posts($response, $server, $request) {
    if ($request->get_route() !== '/wp/v2/posts') {
        return $response;
    }

    if ($request->get_method() !== 'GET') {
        return $response;
    }

    $cache_key = pva_get_posts_cache_key($request);

    $cached_response = wp_cache_get($cache_key, PVA_CACHE_GROUP);

    if ($cached_response !== false) {
        return rest_ensure_response($cached_response);
    }

    wp_cache_set($cache_key, $response->get_data(), PVA_CACHE_GROUP, PVA_CACHE_EXPIRATION);

    return $response;
}
add_filter('rest_post_dispatch', 'pva_get_cached_posts', 10, 3);

function pva_check_permission($request) {
    $auth_header = $request->get_header('authorization');

    if (!$auth_header) {
        return new WP_Error(
            'missing_token',
            'Authorization token is missing',
            array('status' => 401)
        );
    }

    if ($auth_header !== 'Bearer ' . PVA_SECRET_TOKEN) {
        return new WP_Error(
            'invalid_token',
            'Invalid authorization token',
            array('status' => 403)
        );
    }

    return true;
}

// Init votes
function pva_initialize_vote_counts($post_id, $post, $update) {
    if ($post->post_type !== 'post') {
        return;
    }

    if ($update) {
        return;
    }

    pva_ensure_vote_meta($post_id);
}
add_action('wp_insert_post', 'pva_initialize_vote_counts', 10, 3);


function pva_add_votes_to_rest_api() {
    register_rest_field('post', 'votes', array(
        'get_callback' => function ($post) {
            $post_id = $post['id'];
            pva_ensure_vote_meta($post_id);

            return array(
                'upvotes' => (int) get_post_meta($post_id, '_pva_upvotes', true),
                'downvotes' => (int) get_post_meta($post_id, '_pva_downvotes', true),
            );
        },
        'schema' => null,
    ));
}
add_action('rest_api_init', 'pva_add_votes_to_rest_api');


function pva_register_vote_endpoint() {
    register_rest_route('post-votes/v1', '/vote', array(
        'methods' => 'POST',
        'callback' => 'pva_handle_vote',
        'permission_callback' => 'pva_check_permission',
    ));
}
add_action('rest_api_init', 'pva_register_vote_endpoint');

function pva_handle_vote($request) {
    $post_id = (int) $request->get_param('post_id');
    $vote_type = sanitize_text_field($request->get_param('vote_type'));
    $action = sanitize_text_field($request->get_param('action'));

    if (!$post_id || get_post_type($post_id) !== 'post') {
        return new WP_Error('invalid_post', 'Invalid post ID', array('status' => 400));
    }

    if (!in_array($vote_type, array('upvote', 'downvote'), true)) {
        return new WP_Error('invalid_vote', 'Invalid vote type', array('status' => 400));
    }

    if (!in_array($action, array('increment', 'decrement'), true)) {
        return new WP_Error('invalid_action', 'Invalid action', array('status' => 400));
    }

    $meta_key = $vote_type === 'upvote' ? '_pva_upvotes' : '_pva_downvotes';

    $current_votes = (int) get_post_meta($post_id, $meta_key, true);

    if ($action === 'increment') {
        $new_votes = $current_votes + 1;
    } else {
        $new_votes = max(0, $current_votes - 1);
    }

    update_post_meta($post_id, $meta_key, $new_votes);
    pva_clear_posts_cache();

    return array(
        'success' => true,
        'post_id' => $post_id,
        'vote_type' => $vote_type,
        'action' => $action,
        'votes' => array(
            'upvotes' => (int) get_post_meta($post_id, '_pva_upvotes', true),
            'downvotes' => (int) get_post_meta($post_id, '_pva_downvotes', true),
        ),
    );
}


function pva_add_admin_menu() {
    add_menu_page(
        'Post Votes',
        'Post Votes',
        'manage_options',
        'post-votes',
        'pva_votes_admin_page',
        'dashicons-thumbs-up',
        25
    );
}
add_action('admin_menu', 'pva_add_admin_menu');

function pva_votes_admin_page() {
    $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'title';
    $order = isset($_GET['order']) && $_GET['order'] === 'desc' ? 'desc' : 'asc';

    $posts = get_posts(array(
        'post_type' => 'post',
        'post_status' => 'publish',
        'numberposts' => -1,
    ));

    usort($posts, function ($a, $b) use ($orderby, $order) {
        if ($orderby === 'upvotes') {
            $value_a = (int) get_post_meta($a->ID, '_pva_upvotes', true);
            $value_b = (int) get_post_meta($b->ID, '_pva_upvotes', true);
        } elseif ($orderby === 'downvotes') {
            $value_a = (int) get_post_meta($a->ID, '_pva_downvotes', true);
            $value_b = (int) get_post_meta($b->ID, '_pva_downvotes', true);
        } else {
            $value_a = strtolower($a->post_title);
            $value_b = strtolower($b->post_title);
        }

        if ($value_a == $value_b) {
            return 0;
        }

        if ($order === 'asc') {
            return ($value_a < $value_b) ? -1 : 1;
        }

        return ($value_a > $value_b) ? -1 : 1;
    });

    $next_order = $order === 'asc' ? 'desc' : 'asc';
    ?>
    <div class="wrap">
        <h1>Post Votes</h1>

        <table class="widefat striped">
            <thead>
                <tr>
                    <th>
                        <a href="?page=post-votes&orderby=title&order=<?php echo esc_attr($next_order); ?>">
                            Post Title
                        </a>
                    </th>
                    <th>
                        <a href="?page=post-votes&orderby=upvotes&order=<?php echo esc_attr($next_order); ?>">
                            Upvotes
                        </a>
                    </th>
                    <th>
                        <a href="?page=post-votes&orderby=downvotes&order=<?php echo esc_attr($next_order); ?>">
                            Downvotes
                        </a>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($posts as $post) : ?>
                    <tr>
                        <td><?php echo esc_html($post->post_title); ?></td>
                        <td><?php echo (int) get_post_meta($post->ID, '_pva_upvotes', true); ?></td>
                        <td><?php echo (int) get_post_meta($post->ID, '_pva_downvotes', true); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}