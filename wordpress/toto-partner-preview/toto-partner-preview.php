<?php
/**
 * Plugin Name: Toto Partner Booking Preview
 * Description: Displays the Toto hotel booking prototype using [toto_partner_preview]. Demo data stays in the visitor's browser.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Toto Company
 * License: GPL-2.0-or-later
 */

if (!defined('ABSPATH')) {
    exit;
}

function toto_partner_preview_render() {
    $source = plugins_url('assets/index.html', __FILE__);
    return '<section aria-label="Toto booking demo">'
        . '<p>ডেমো: তথ্য এই ব্রাউজারে থাকে। বাস্তব বুকিং বা অতিথির তথ্য ব্যবহার করবেন না।</p>'
        . '<p><a href="' . esc_url($source) . '" target="_blank" rel="noopener">সম্পূর্ণ ডেমো খুলুন</a></p>'
        . '<iframe title="Toto Partner Network booking demo" src="' . esc_url($source) . '" style="width:100%;height:85vh;min-height:600px;border:0" loading="lazy"></iframe>'
        . '</section>';
}
add_shortcode('toto_partner_preview', 'toto_partner_preview_render');

function toto_partner_preview_admin_menu() {
    add_management_page('Toto Booking Preview', 'Toto Booking Preview', 'manage_options', 'toto-partner-preview', 'toto_partner_preview_admin_page');
}
add_action('admin_menu', 'toto_partner_preview_admin_menu');

function toto_partner_preview_admin_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    echo '<div class="wrap"><h1>Toto Booking Preview</h1>';
    echo '<p>একটি WordPress পেজে Shortcode ব্লক যোগ করে <code>[toto_partner_preview]</code> লিখুন। এটি ব্রাউজারভিত্তিক ডেমো; WordPress-এর ব্যবহারকারী ও ডেটাবেসের সঙ্গে যুক্ত নয়।</p>';
    echo toto_partner_preview_render();
    echo '</div>';
}
