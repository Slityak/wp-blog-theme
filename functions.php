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

define('BLOG_THEME_VERSION', '0.2.0');

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

/* -------------------------------------------------------------------------
 * Navigáció – fő menü
 * ---------------------------------------------------------------------- */

/**
 * Kiírja a "primary" helyhez rendelt menüt.
 *
 * Ha az adminban még nincs menü a helyhez rendelve, a fallback egy
 * Főoldal + Blog menüt rajzol, hogy friss telepítésen se legyen üres a fejléc.
 */
function blog_nav_menu()
{
    wp_nav_menu([
        'theme_location'  => 'primary',
        'container'       => 'nav',
        'container_class' => 'site-nav',
        'menu_class'      => 'site-nav-lista',
        'depth'           => 2,
        'fallback_cb'     => 'blog_nav_menu_fallback',
    ]);
}

/**
 * Menü fallback: Főoldal + Blog archívum.
 */
function blog_nav_menu_fallback()
{
    $archive_url = get_post_type_archive_link('blog');

    $items = [
        [
            'url'     => home_url('/'),
            'label'   => __('Főoldal', 'blog'),
            'current' => is_front_page(),
        ],
    ];

    if ($archive_url) {
        $items[] = [
            'url'     => $archive_url,
            'label'   => __('Blog', 'blog'),
            'current' => is_post_type_archive('blog') || is_singular('blog'),
        ];
    }

    echo '<nav class="site-nav"><ul class="site-nav-lista">';

    foreach ($items as $item) {
        printf(
            '<li><a href="%1$s"%2$s>%3$s</a></li>',
            esc_url($item['url']),
            $item['current'] ? ' aria-current="page"' : '',
            esc_html($item['label'])
        );
    }

    echo '</ul></nav>';
}

/* -------------------------------------------------------------------------
 * Navigáció – morzsamenü (breadcrumbs)
 * ---------------------------------------------------------------------- */

/**
 * Összeállítja a morzsamenü elemeit az aktuális nézethez.
 *
 * @return array<int, array{label: string, url: string}> Az utolsó elem az aktuális oldal, url nélkül.
 */
function blog_breadcrumb_items()
{
    $items = [
        [
            'label' => __('Főoldal', 'blog'),
            'url'   => home_url('/'),
        ],
    ];

    if (is_front_page()) {
        return [
            [
                'label' => __('Főoldal', 'blog'),
                'url'   => '',
            ],
        ];
    }

    if (is_singular()) {
        $post_type = get_post_type();

        // A "blog" bejegyzések alá beszúrjuk az archívumot is.
        if ('blog' === $post_type) {
            $archive_url = get_post_type_archive_link('blog');
            if ($archive_url) {
                $items[] = [
                    'label' => __('Blog', 'blog'),
                    'url'   => $archive_url,
                ];
            }
        }

        // Hierarchikus oldalaknál a szülők is bekerülnek, a legfelsőtől lefelé.
        if (is_page()) {
            $parents = array_reverse(get_post_ancestors(get_the_ID()));
            foreach ($parents as $parent_id) {
                $items[] = [
                    'label' => get_the_title($parent_id),
                    'url'   => get_permalink($parent_id),
                ];
            }
        }

        $items[] = [
            'label' => get_the_title(),
            'url'   => '',
        ];

        return $items;
    }

    if (is_post_type_archive() || is_category() || is_tag() || is_tax() || is_date() || is_author() || is_home()) {
        // Bejegyzéstípus-archívumnál a "Archívum:" előtag nélküli nevet használjuk.
        $label = is_post_type_archive()
            ? post_type_archive_title('', false)
            : wp_strip_all_tags(get_the_archive_title());

        $items[] = [
            'label' => $label,
            'url'   => '',
        ];

        return $items;
    }

    if (is_search()) {
        $items[] = [
            /* translators: %s: keresőkifejezés */
            'label' => sprintf(__('Keresés: %s', 'blog'), get_search_query()),
            'url'   => '',
        ];

        return $items;
    }

    if (is_404()) {
        $items[] = [
            'label' => __('Az oldal nem található', 'blog'),
            'url'   => '',
        ];
    }

    return $items;
}

/**
 * Kiírja a morzsamenüt schema.org BreadcrumbList jelöléssel.
 */
function blog_breadcrumbs()
{
    $items = blog_breadcrumb_items();

    if (count($items) < 2 && !is_front_page()) {
        return;
    }

    echo '<nav class="breadcrumbs" aria-label="' . esc_attr__('Morzsamenü', 'blog') . '">';
    echo '<ol itemscope itemtype="https://schema.org/BreadcrumbList">';

    $position = 1;

    foreach ($items as $item) {
        echo '<li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">';

        if ($item['url']) {
            printf(
                '<a itemprop="item" href="%1$s"><span itemprop="name">%2$s</span></a>',
                esc_url($item['url']),
                esc_html($item['label'])
            );
        } else {
            printf(
                '<span itemprop="name" aria-current="page">%s</span>',
                esc_html($item['label'])
            );
        }

        printf('<meta itemprop="position" content="%d">', (int) $position);
        echo '</li>';

        $position++;
    }

    echo '</ol></nav>';
}

/*
 * A 2. rész (város-választó form) külön pluginbe került: varos-valaszto.
 * A frontenden a [varos_valaszto] shortcode jeleníti meg, a beállításai
 * az adminban a Beállítások → Város választó oldalon szerkeszthetők.
 */
