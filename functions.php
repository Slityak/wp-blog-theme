<?php
/**
 * Blog téma – functions.php
 *
 * Blog 1. rész: "blog" post type + frontend törlés e-mail értesítéssel.
 * Blog 2. rész: város-választó form mentése JavaScript nélkül.
 *
 * @package blog
 */

if (!defined('ABSPATH')) {
    exit;
}

define('BLOG_THEME_VERSION', '0.1.0');

add_action('after_setup_theme', function () {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('automatic-feed-links');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script']);

    register_nav_menus([
        'primary' => __('Fő menü', 'blog'),
    ]);
});

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'blog',
        get_stylesheet_uri(),
        [],
        BLOG_THEME_VERSION
    );
});

/* -------------------------------------------------------------------------
 * 1. rész – "blog" egyéni bejegyzéstípus
 * ---------------------------------------------------------------------- */

add_action('init', function () {
    register_post_type('blog', [
        'labels' => [
            'name'          => __('Blog', 'blog'),
            'singular_name' => __('Blog bejegyzés', 'blog'),
            'add_new'       => __('Új bejegyzés', 'blog'),
            'add_new_item'  => __('Új blog bejegyzés', 'blog'),
            'edit_item'     => __('Blog bejegyzés szerkesztése', 'blog'),
            'all_items'     => __('Összes bejegyzés', 'blog'),
        ],
        'public'       => true,
        'has_archive'  => true,
        'menu_icon'    => 'dashicons-welcome-write-blog',
        'supports'     => ['title', 'editor', 'thumbnail', 'excerpt'],
        'show_in_rest' => true,
    ]);
});

/* -------------------------------------------------------------------------
 * 1. rész – Frontend törlés + admin e-mail értesítés
 * ---------------------------------------------------------------------- */

add_action('template_redirect', function () {
    if (!isset($_POST['blog_delete'])) {
        return;
    }

    $post_id = (int) $_POST['blog_delete'];
    $nonce   = isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '';

    if (!wp_verify_nonce($nonce, 'blog_delete_' . $post_id)) {
        wp_die(esc_html__('Érvénytelen vagy lejárt kérés.', 'blog'), '', ['response' => 403]);
    }

    $post = get_post($post_id);
    if (!$post || 'blog' !== $post->post_type) {
        wp_die(esc_html__('A bejegyzés nem található.', 'blog'), '', ['response' => 404]);
    }

    $title = $post->post_title;

    if (wp_delete_post($post_id, true)) {
        $admin_email = get_option('admin_email');
        $subject     = __('Blog bejegyzés törölve', 'blog');
        $message     = sprintf(
            /* translators: 1: bejegyzés címe, 2: dátum és idő */
            __("A(z) \"%1\$s\" című blog bejegyzést törölték a weboldal frontend felületéről.\n\nIdőpont: %2\$s", 'blog'),
            $title,
            wp_date(get_option('date_format') . ' ' . get_option('time_format'))
        );

        wp_mail($admin_email, $subject, $message);

        // Dev környezetben nincs SMTP – a log alapján ellenőrizhető a levél.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf('[blog] E-mail a részére: %s | Tárgy: %s | %s', $admin_email, $subject, $message));
        }

        wp_safe_redirect(add_query_arg('torolve', '1', home_url('/')));
        exit;
    }

    wp_safe_redirect(add_query_arg('torolve', '0', home_url('/')));
    exit;
});

/*
 * A 2. rész (város-választó form) külön pluginbe került: varos-valaszto.
 * A frontenden a [varos_valaszto] shortcode jeleníti meg, a beállításai
 * az adminban a Beállítások → Város választó oldalon szerkeszthetők.
 */
