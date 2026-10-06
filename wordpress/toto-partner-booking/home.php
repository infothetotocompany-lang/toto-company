<?php
if (!defined('ABSPATH')) exit;
function toto_company_home() {
    wp_enqueue_style('toto-home',plugins_url('assets/home.css',__FILE__),array(),'0.2.0');
    $portal=get_permalink((int)get_option('toto_portal_page'));
    if(!$portal)$portal=home_url('/toto-hotel-booking/');
    $source=json_decode(file_get_contents(__DIR__.'/assets/leo.json'),true);
    $photo=plugins_url('assets/leo/1693985481leo11.jpg',__FILE__);
    $room=plugins_url('assets/leo/1693985481leo44.jpg',__FILE__);
    $posts=get_posts(array('post_type'=>'toto_hotel','post_status'=>'publish','numberposts'=>6));
    ob_start(); ?>
    <div class="toto-site">
      <header class="tc-nav"><a class="tc-brand" href="<?php echo esc_url(home_url('/')); ?>"><span class="tc-mark">T</span><span>TOTO COMPANY<small>HOTEL PARTNER NETWORK</small></span></a><nav aria-label="প্রধান মেনু"><a href="#hotels">হোটেল</a><a href="#how">বুকিং পদ্ধতি</a><a href="#about">আমাদের সম্পর্কে</a><a class="tc-button gold" href="<?php echo esc_url($portal); ?>">এজেন্ট লগইন ↗</a></nav></header>
      <section class="tc-hero"><div><span class="tc-kicker">GANGTOK · SIKKIM · INDIA</span><h1>আপনার ভ্রমণের<br><em>নির্ভরযোগ্য ঠিকানা।</em></h1><p>হোটেলের তথ্য দেখুন, পছন্দের রুম বেছে নিন এবং বুকিং অনুরোধ পাঠান। হোটেল কর্তৃপক্ষের অনুমোদনে আপনার বুকিং নিশ্চিত হবে।</p><a class="tc-button" href="<?php echo esc_url($portal); ?>">বুকিং পোর্টাল খুলুন ↗</a><a class="tc-text-link" href="#hotels">হোটেল দেখুন ↓</a><div class="tc-trust"><span>হোটেলের নিজস্ব তথ্য</span><span>এজেন্টদের জন্য পৃথক অ্যাকাউন্ট</span><span>অনুমোদিত বুকিং</span></div></div><div class="tc-hero-photo"><img src="<?php echo esc_url($photo); ?>" alt="হোটেল লিও ইন্টারন্যাশনালের প্রকাশিত বাইরের ছবি"><div class="tc-photo-label"><small>OUR HOTEL IN GANGTOK</small><strong>Hotel Leo International</strong><span>Upper Sichey, Gangtok</span></div></div></section>
      <section id="hotels" class="tc-section"><div class="tc-heading"><div><span class="tc-kicker">FIND YOUR STAY</span><h2>আপনার পরবর্তী ঠিকানা</h2></div><a href="<?php echo esc_url($portal); ?>">রুম ও উপলব্ধতা দেখুন ↗</a></div>
      <?php if($posts): foreach($posts as $p): $h=Toto_Partner_Booking::hotel_data($p->ID);$image=!empty($h['photos'])?$h['photos'][0]:$photo; ?>
        <article class="tc-hotel"><img loading="lazy" src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($h['name']); ?>"><div><span class="tc-kicker">HOTEL PARTNER</span><h3><?php echo esc_html($h['name']); ?></h3><p><?php echo esc_html($h['address']); ?></p><p><?php echo esc_html($h['description']); ?></p><a class="tc-button" href="<?php echo esc_url($portal); ?>">রুম দেখুন ও লগইন করুন ↗</a></div></article>
      <?php endforeach; else: ?>
        <article class="tc-hotel"><img loading="lazy" src="<?php echo esc_url($room); ?>" alt="লিও ইন্টারন্যাশনালের প্রতিনিধিত্বমূলক রুমের ছবি"><div><span class="tc-kicker">OUR HOTEL · GANGTOK</span><h3>Hotel Leo International</h3><p><?php echo esc_html($source['address']); ?></p><p>Upper Sichey-তে আপনার গ্যাংটক ভ্রমণের ঠিকানা। রুমের ছবি প্রতিনিধিত্বমূলক; বর্তমান রেট, রুম ও নীতি হোটেল কর্তৃপক্ষ নিশ্চিত করবেন।</p><a class="tc-button" href="<?php echo esc_url($portal); ?>">রুম ও বুকিং পোর্টাল ↗</a></div></article>
      <?php endif; ?></section>
      <section id="how" class="tc-section"><span class="tc-kicker">SIMPLE, CLEAR BOOKING</span><h2>তিনটি ধাপে বুকিং</h2><div class="tc-steps"><article><span>01</span><h3>হোটেল ও রুম দেখুন</h3><p>অ্যাকাউন্টে লগইন করে আপনার তারিখের রুম, রেট ও হোটেলের নীতি দেখুন।</p></article><article><span>02</span><h3>অনুরোধ পাঠান</h3><p>অতিথি, রুম ও শিশুদের তথ্য দিয়ে বুকিং অনুরোধ করুন। প্রথমে এটি অপেক্ষমাণ থাকবে।</p></article><article><span>03</span><h3>অনুমোদন ও ভাউচার</h3><p>হোটেল অনুমোদন দিলে বুকিং নিশ্চিত হবে। নিশ্চিত বুকিংয়ের ভাউচার সংগ্রহ করুন।</p></article></div></section>
      <section id="about" class="tc-about tc-section"><div><span class="tc-kicker">TOTO COMPANY</span><h2>এজেন্ট ও হোটেলের<br>একটি গুছানো সংযোগ।</h2></div><div><p>প্রবীর দত্তের Toto Company-এর হোটেল পার্টনার বুকিং ব্যবস্থা। হোটেল কর্তৃপক্ষ তাঁদের তথ্য ও রুমের উপলব্ধতা পরিচালনা করেন; এজেন্টরা অতিথিদের জন্য বুকিং অনুরোধ করেন।</p><p>আমাদের নিজস্ব হোটেল লিও ইন্টারন্যাশনাল, গ্যাংটক। প্রতিটি বুকিংয়ের চূড়ান্ত শর্ত সংশ্লিষ্ট হোটেলের নীতি অনুযায়ী।</p></div></section>
      <section class="tc-partner"><div><span class="tc-kicker">FOR HOTEL AUTHORITIES</span><h2>আপনার হোটেল। আপনার নিয়ন্ত্রণ।</h2><p>রুম ও রেট আপডেট করুন, অনুরোধ দেখুন এবং বুকিং অনুমোদন দিন।</p></div><a class="tc-button gold" href="<?php echo esc_url($portal); ?>">হোটেল কর্তৃপক্ষের লগইন ↗</a></section>
      <footer class="tc-footer"><a class="tc-brand" href="<?php echo esc_url(home_url('/')); ?>"><span class="tc-mark">T</span><span>TOTO COMPANY<small>HOTEL PARTNER NETWORK</small></span></a><p>Gangtok, Sikkim · hocab.in</p><a href="<?php echo esc_url($portal); ?>">বুকিং পোর্টাল ↗</a></footer>
    </div>
    <?php return ob_get_clean();
}
function toto_company_install_home() {
    if(!current_user_can('manage_options'))wp_die('অনুমতি নেই।');
    check_admin_referer('toto_install_home');
    $id=(int)get_option('toto_home_page');
    if(!get_post($id))$id=0;
    $id=wp_insert_post(array('ID'=>$id,'post_type'=>'page','post_status'=>'publish','post_title'=>'Toto Company — Hotel Partner Network','post_content'=>'[toto_company_home]'),true);
    if(is_wp_error($id))wp_die(esc_html($id->get_error_message()));
    if(!get_option('toto_previous_front_page'))update_option('toto_previous_front_page',array('show_on_front'=>get_option('show_on_front'),'page_on_front'=>get_option('page_on_front')),false);
    update_option('toto_home_page',$id,false);update_post_meta($id,'_wp_page_template','toto-company-home.php');update_option('show_on_front','page');update_option('page_on_front',$id);
    wp_safe_redirect(get_permalink($id));exit;
}
add_shortcode('toto_company_home','toto_company_home');
add_action('admin_post_toto_install_home','toto_company_install_home');
