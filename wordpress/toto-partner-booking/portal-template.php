<?php
if (!defined('ABSPATH')) { exit; }
// Render before wp_head so shortcode scripts/styles are enqueued in time.
$portal = Toto_Partner_Booking::portal();
?><!doctype html>
<html <?php language_attributes(); ?>>
<head><meta charset="<?php bloginfo('charset'); ?>"><meta name="viewport" content="width=device-width, initial-scale=1"><?php wp_head(); ?></head>
<body <?php body_class(); ?> style="margin:0;background:#edf3ef">
<?php wp_body_open(); ?>
<div style="max-width:1248px;margin:auto;padding:12px">
<?php echo $portal; ?>
<footer style="padding:20px;text-align:center">Toto Company · Hotel Partner Network<?php if(is_user_logged_in()): ?> · <a href="<?php echo esc_url(wp_logout_url(get_permalink())); ?>">লগআউট</a><?php endif; ?></footer>
</div>
<?php wp_footer(); ?>
</body></html>
