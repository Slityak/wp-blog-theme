<?php
/**
 * Főoldal – blog 1. és 2. rész.
 *
 * @package blog
 */

if (!defined('ABSPATH')) {
    exit;
}
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<main class="site-main">
    <h1><?php bloginfo('name'); ?></h1>

    <?php if (isset($_GET['torolve'])) : ?>
        <p class="notice <?php echo '1' === $_GET['torolve'] ? 'notice-success' : 'notice-error'; ?>">
            <?php echo '1' === $_GET['torolve']
                ? esc_html__('A bejegyzés törölve. Az adminisztrátor e-mail értesítést kapott.', 'blog')
                : esc_html__('A törlés nem sikerült.', 'blog'); ?>
        </p>
    <?php endif; ?>

    <section class="blog-lista">
        <h2><?php esc_html_e('Blog bejegyzések', 'blog'); ?></h2>

        <?php
        // A feladat kérése szerint query_posts-tal kérdezzük le a blog bejegyzéseket.
        query_posts([
            'post_type'      => 'blog',
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ]);
        ?>

        <?php if (have_posts()) : ?>
            <table class="blog-tabla">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Cím', 'blog'); ?></th>
                        <th><?php esc_html_e('Dátum', 'blog'); ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php while (have_posts()) : the_post(); ?>
                        <tr>
                            <td><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></td>
                            <td><?php echo esc_html(get_the_date()); ?></td>
                            <td>
                                <form method="post"
                                      onsubmit="return confirm('<?php echo esc_js(sprintf(__('Biztosan törlöd a(z) „%s” bejegyzést?', 'blog'), get_the_title())); ?>');">
                                    <?php wp_nonce_field('blog_delete_' . get_the_ID()); ?>
                                    <input type="hidden" name="blog_delete" value="<?php echo esc_attr(get_the_ID()); ?>">
                                    <button type="submit" class="torles-gomb"><?php esc_html_e('Törlés', 'blog'); ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p><?php esc_html_e('Nincs blog bejegyzés.', 'blog'); ?></p>
        <?php endif; ?>

        <?php wp_reset_query(); ?>
    </section>

    <?php if (shortcode_exists('varos_valaszto')) : ?>
        <?php echo do_shortcode('[varos_valaszto]'); ?>
    <?php endif; ?>
</main>
<?php wp_footer(); ?>
</body>
</html>
