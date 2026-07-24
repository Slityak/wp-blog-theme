<?php
/**
 * Minimál, önálló sablon (header/footer nélkül is működik).
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
<header class="site-header">
    <?php blog_nav_menu(); ?>
</header>
<main class="site-main">
    <?php blog_breadcrumbs(); ?>

    <h1><?php bloginfo('name'); ?></h1>
    <?php if (have_posts()) : ?>
        <?php while (have_posts()) : the_post(); ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                <div class="entry-content"><?php the_content(); ?></div>
            </article>
        <?php endwhile; ?>
    <?php else : ?>
        <p><?php esc_html_e('Nincs megjeleníthető tartalom.', 'blog'); ?></p>
    <?php endif; ?>
</main>
<?php wp_footer(); ?>
</body>
</html>
