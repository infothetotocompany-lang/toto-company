<?php
/**
 * Plugin Name: Toto Partner Hotel Booking
 * Description: WordPress agent and hotel booking portal with shared inventory and hotel approval.
 * Version: 0.1.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Toto Company
 * License: GPL-2.0-or-later
 */
if (!defined('ABSPATH')) { exit; }
final class Toto_Partner_Booking {
    const NS = 'toto-booking/v1';
    static function table($name) { global $wpdb; return $wpdb->prefix . 'toto_' . $name; }
    static function install() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $c = $wpdb->get_charset_collate();
        $schemas = array(
            'rooms' => 'id bigint unsigned NOT NULL AUTO_INCREMENT, hotel bigint unsigned NOT NULL, name varchar(180) NOT NULL, total int NOT NULL DEFAULT 0, capacity int NOT NULL DEFAULT 2, rate decimal(12,2) NOT NULL DEFAULT 0, PRIMARY KEY (id), KEY hotel (hotel)',
            'inventory' => 'id bigint unsigned NOT NULL AUTO_INCREMENT, room bigint unsigned NOT NULL, day date NOT NULL, total int NOT NULL, rate decimal(12,2) NOT NULL, stopped tinyint NOT NULL DEFAULT 0, PRIMARY KEY (id), UNIQUE KEY room_day (room,day)',
            'bookings' => 'id bigint unsigned NOT NULL AUTO_INCREMENT, hotel bigint unsigned NOT NULL, room bigint unsigned NOT NULL, agent bigint unsigned NOT NULL, guest varchar(180) NOT NULL, phone varchar(40) NOT NULL, arrival date NOT NULL, departure date NOT NULL, rooms int NOT NULL, adults int NOT NULL, ages text NOT NULL, meal varchar(30) NOT NULL, notes text NOT NULL, status varchar(30) NOT NULL, quote longtext NOT NULL, reason text NOT NULL, created datetime NOT NULL, PRIMARY KEY (id), KEY stock (room,status,arrival,departure), KEY agent (agent), KEY hotel (hotel)',
            'events' => 'id bigint unsigned NOT NULL AUTO_INCREMENT, booking bigint unsigned NOT NULL, actor bigint unsigned NOT NULL, action varchar(60) NOT NULL, detail text NOT NULL, created datetime NOT NULL, PRIMARY KEY (id), KEY booking (booking)',
            'payments' => 'id bigint unsigned NOT NULL AUTO_INCREMENT, booking bigint unsigned NOT NULL, actor bigint unsigned NOT NULL, amount decimal(12,2) NOT NULL, reference varchar(180) NOT NULL, created datetime NOT NULL, PRIMARY KEY (id), KEY booking (booking)'
        );
        foreach ($schemas as $name => $columns) {
            $columns = str_replace(array(', ', 'PRIMARY KEY ('), array(",\n", 'PRIMARY KEY  ('), $columns);
            dbDelta('CREATE TABLE ' . self::table($name) . " (\n$columns\n) ENGINE=InnoDB $c;");
        }
        add_role('toto_agent', 'Toto Booking Agent', array('read' => true, 'toto_agent' => true));
        add_role('toto_hotel', 'Toto Hotel Authority', array('read' => true, 'toto_hotel' => true));
        if (!get_post((int)get_option('toto_portal_page'))) {
            $page = wp_insert_post(array('post_type'=>'page','post_status'=>'draft','post_title'=>'Toto Hotel Booking','post_content'=>'[toto_booking]'));
            if ($page && !is_wp_error($page)) { update_option('toto_portal_page',$page,false); update_post_meta($page,'_wp_page_template','toto-booking-standalone.php'); }
        }
    }
    static function boot() {
        register_post_type('toto_hotel', array('label' => 'Toto Hotels', 'public' => false, 'show_ui' => true, 'show_in_menu' => true, 'supports' => array('title','editor'), 'capability_type' => 'post', 'capabilities' => array('edit_posts'=>'manage_options','edit_post'=>'manage_options','read_post'=>'manage_options','delete_post'=>'manage_options','publish_posts'=>'manage_options','create_posts'=>'manage_options'), 'map_meta_cap'=>false));
        add_shortcode('toto_booking', array(__CLASS__, 'portal'));
    }
    static function admin() { return current_user_can('manage_options'); }
    static function allowed() { return is_user_logged_in() && (self::admin() || current_user_can('toto_agent') || current_user_can('toto_hotel')); }
    static function manages($hotel) { return self::admin() || (current_user_can('toto_hotel') && in_array((int)$hotel, array_map('intval', (array)get_user_meta(get_current_user_id(),'toto_hotels',true)), true)); }
    static function error($message, $status=400) { return new WP_Error('toto_error',$message,array('status'=>$status)); }
    static function routes() {
        foreach (array('state'=>'GET','availability'=>'GET','booking'=>'POST','action'=>'POST','hotel'=>'POST','room'=>'POST','inventory'=>'POST','payment'=>'POST','seed'=>'POST') as $path=>$method) {
            register_rest_route(self::NS, '/' . $path, array('methods'=>$method,'callback'=>array(__CLASS__,$path),'permission_callback'=>array(__CLASS__,'allowed')));
        }
    }
    static function hotel_data($id) {
        $p=get_post($id); if (!$p || $p->post_type!=='toto_hotel' || $p->post_status!=='publish') { return null; }
        $d=(array)get_post_meta($id,'toto_profile',true);
        return array_merge(array('phone'=>'','address'=>'','child_free'=>5,'child_fee'=>0,'tax'=>0,'breakfast'=>0,'half'=>0,'full'=>0,'policy'=>'','ready'=>false,'photos'=>array(),'individualRooms'=>array()),$d,array('id'=>(int)$id,'name'=>$p->post_title,'description'=>$p->post_content));
    }
    static function state($request) {
        global $wpdb;
        $hotels=array(); foreach(get_posts(array('post_type'=>'toto_hotel','post_status'=>'publish','numberposts'=>-1)) as $p) { $h=self::hotel_data($p->ID);$h['manage']=self::manages($p->ID);$hotels[]=$h; }
        $filter=self::admin() ? '1=1' : $wpdb->prepare('agent=%d',get_current_user_id());
        if (!self::admin() && current_user_can('toto_hotel')) { $ids=array_filter(array_map('intval',(array)get_user_meta(get_current_user_id(),'toto_hotels',true))); $filter='hotel IN (' . ($ids?implode(',',$ids):'0') . ')'; }
        $bookings=$wpdb->get_results('SELECT * FROM '.self::table('bookings')." WHERE $filter ORDER BY id DESC LIMIT 500",ARRAY_A);
        foreach($bookings as &$b) { $b['quote']=json_decode($b['quote'],true);$b['ages']=json_decode($b['ages'],true);$b['events']=$wpdb->get_results($wpdb->prepare('SELECT actor,action,detail,created FROM '.self::table('events').' WHERE booking=%d ORDER BY id',$b['id']),ARRAY_A);$b['payments']=$wpdb->get_results($wpdb->prepare('SELECT amount,reference,created FROM '.self::table('payments').' WHERE booking=%d ORDER BY id',$b['id']),ARRAY_A); }
        return array('hotels'=>$hotels,'rooms'=>$wpdb->get_results('SELECT * FROM '.self::table('rooms'),ARRAY_A),'bookings'=>$bookings,'admin'=>self::admin(),'agent'=>current_user_can('toto_agent')||self::admin(),'user'=>wp_get_current_user()->display_name,'commission'=>(float)get_user_meta(get_current_user_id(),'toto_commission',true),'currency'=>'INR');
    }
    static function dates($a,$d) {
        $start=DateTimeImmutable::createFromFormat('!Y-m-d',(string)$a); $end=DateTimeImmutable::createFromFormat('!Y-m-d',(string)$d);
        if (!$start || !$end || $start->format('Y-m-d')!==$a || $end->format('Y-m-d')!==$d || $end <= $start || $start->format('Y-m-d')<current_time('Y-m-d') || $start->diff($end)->days>60) { return self::error('সঠিক ভবিষ্যৎ তারিখ দিন; সর্বোচ্চ ৬০ রাত।'); }
        $days=array(); for($day=$start;$day<$end;$day=$day->modify('+1 day')) { $days[]=$day->format('Y-m-d'); } return $days;
    }
    static function stock($room,$days,$exclude=0) {
        global $wpdb; $out=array();
        foreach($days as $day) {
            $override=$wpdb->get_row($wpdb->prepare('SELECT * FROM '.self::table('inventory').' WHERE room=%d AND day=%s',$room['id'],$day),ARRAY_A);
            $used=(int)$wpdb->get_var($wpdb->prepare('SELECT COALESCE(SUM(rooms),0) FROM '.self::table('bookings')." WHERE room=%d AND status IN ('confirmed','cancel_requested') AND arrival<=%s AND departure>%s AND id<>%d",$room['id'],$day,$day,$exclude));
            $out[]=array('day'=>$day,'total'=>(int)($override?$override['total']:$room['total']),'available'=>max(0,(int)($override?$override['total']:$room['total'])-$used),'rate'=>(float)($override?$override['rate']:$room['rate']),'stopped'=>$override?(bool)$override['stopped']:false);
        } return $out;
    }
    static function availability($r) {
        global $wpdb; $days=self::dates($r['arrival'],$r['departure']);if(is_wp_error($days))return $days;
        $room=$wpdb->get_row($wpdb->prepare('SELECT * FROM '.self::table('rooms').' WHERE id=%d',$r['room']),ARRAY_A);if(!$room)return self::error('রুম পাওয়া যায়নি।',404);
        return self::stock($room,$days);
    }
    static function event($id,$action,$detail='') { global $wpdb; return $wpdb->insert(self::table('events'),array('booking'=>$id,'actor'=>get_current_user_id(),'action'=>$action,'detail'=>$detail,'created'=>current_time('mysql'))); }
    static function booking($r) {
        global $wpdb;if(!self::admin()&&!current_user_can('toto_agent'))return self::error('শুধু এজেন্ট বুকিং অনুরোধ করবেন।',403);
        $days=self::dates($r['arrival'],$r['departure']);if(is_wp_error($days))return $days;
        $room=$wpdb->get_row($wpdb->prepare('SELECT * FROM '.self::table('rooms').' WHERE id=%d',$r['room']),ARRAY_A);if(!$room)return self::error('রুম পাওয়া যায়নি।');
        $h=self::hotel_data($room['hotel']);if(!$h||empty($h['ready']))return self::error('হোটেলের দাম ও নীতি কর্তৃপক্ষ নিশ্চিত করেননি।');
        if(!is_numeric($r['rooms'])||floor((float)$r['rooms'])!==(float)$r['rooms']||!is_numeric($r['adults'])||floor((float)$r['adults'])!==(float)$r['adults'])return self::error('রুম ও অতিথির সংখ্যা পূর্ণসংখ্যায় দিন।');
        $count=(int)$r['rooms'];$adults=(int)$r['adults'];$ages=$r['ages'];
        if(!is_array($ages)||count($ages)>40||$count<1||$count>50||$adults<1||$adults+count($ages)>$count*(int)$room['capacity'])return self::error('রুম ও অতিথির সংখ্যা/ক্ষমতা মিলছে না।');
        foreach($ages as $age)if(!is_numeric($age)||$age<0||$age>17||floor($age)!=(float)$age)return self::error('শিশুর বয়স ০–১৭ বছর দিন।');
        $guest=sanitize_text_field($r['guest']);$phone=sanitize_text_field($r['phone']);if(!$guest||strlen($guest)>180||!preg_match('/^[+0-9() -]{6,40}$/',$phone)||!$r['consent'])return self::error('অতিথির নাম, ফোন ও নীতির সম্মতি দিন।');
        $meal=sanitize_key($r['meal']);if(!in_array($meal,array('room','breakfast','half','full'),true))return self::error('খাবারের প্যাকেজ সঠিক নয়।');
        $stock=self::stock($room,$days);$base=0;foreach($stock as $night){if($night['stopped']||$night['available']<$count)return self::error('নির্বাচিত রাতে পর্যাপ্ত রুম নেই।',409);$base+=$night['rate']*$count;}
        $children=0;foreach($ages as $age)if($age>(int)$h['child_free'])$children+=(float)$h['child_fee']*count($days);
        $meals=$meal==='room'?0:(float)$h[$meal]*($adults+count($ages))*count($days);$subtotal=round($base+$children+$meals,2);$tax=round($subtotal*(float)$h['tax']/100,2);
        $quote=array('currency'=>'INR','nightly'=>$stock,'base'=>round($base,2),'children'=>$children,'meals'=>$meals,'tax'=>$tax,'total'=>round($subtotal+$tax,2),'commission'=>round($base*min(100,max(0,(float)get_user_meta(get_current_user_id(),'toto_commission',true)))/100,2),'policy'=>$h['policy']);
        $wpdb->query('START TRANSACTION');
        $ok=$wpdb->insert(self::table('bookings'),array('hotel'=>$room['hotel'],'room'=>$room['id'],'agent'=>get_current_user_id(),'guest'=>$guest,'phone'=>$phone,'arrival'=>$r['arrival'],'departure'=>$r['departure'],'rooms'=>$count,'adults'=>$adults,'ages'=>wp_json_encode(array_values(array_map('intval',$ages))),'meal'=>$meal,'notes'=>sanitize_textarea_field($r['notes']),'status'=>'pending','quote'=>wp_json_encode($quote),'reason'=>'','created'=>current_time('mysql')));
        if(!$ok){$wpdb->query('ROLLBACK');return self::error('সংরক্ষণ ব্যর্থ হয়েছে।',500);}$id=$wpdb->insert_id;if(false===self::event($id,'requested')){$wpdb->query('ROLLBACK');return self::error('কাজের ইতিহাস সংরক্ষণ ব্যর্থ।',500);}$wpdb->query('COMMIT');return array('id'=>$id,'status'=>'pending','quote'=>$quote);
    }
    static function action($r) {
        global $wpdb;
        $initial=$wpdb->get_row($wpdb->prepare('SELECT * FROM '.self::table('bookings').' WHERE id=%d',$r['id']),ARRAY_A);if(!$initial)return self::error('বুকিং পাওয়া যায়নি।',404);
        $owner=self::manages($initial['hotel']);$agent=(int)$initial['agent']===get_current_user_id();if(!$owner&&!$agent)return self::error('অনুমতি নেই।',403);
        $wpdb->query('START TRANSACTION');
        $room=$wpdb->get_row($wpdb->prepare('SELECT * FROM '.self::table('rooms').' WHERE id=%d FOR UPDATE',$initial['room']),ARRAY_A);
        $b=$wpdb->get_row($wpdb->prepare('SELECT * FROM '.self::table('bookings').' WHERE id=%d FOR UPDATE',$initial['id']),ARRAY_A);
        $op=sanitize_key($r['action']);$next=null;$reason=sanitize_textarea_field($r['reason']);
        if($op==='approve'&&$owner&&$b['status']==='pending') {
            $h=self::hotel_data($b['hotel']);$days=self::dates($b['arrival'],$b['departure']);
            if(!$room||!$h||!$h['ready']||is_wp_error($days)){ $wpdb->query('ROLLBACK');return self::error('হোটেল/তারিখ আবার পরীক্ষা করুন।',409); }
            foreach(self::stock($room,$days) as $night)if($night['stopped']||$night['available']<(int)$b['rooms']){$wpdb->query('ROLLBACK');return self::error('অন্য বুকিংয়ে রুম পূর্ণ হয়েছে।',409);}
            $next='confirmed';
        }
        if($op==='reject'&&$owner&&$b['status']==='pending'&&$reason)$next='rejected';
        if($op==='cancel'&&$agent&&in_array($b['status'],array('pending','confirmed'),true)&&$reason)$next=$b['status']==='pending'?'cancelled':'cancel_requested';
        if($op==='approve_cancel'&&$owner&&$b['status']==='cancel_requested')$next='cancelled';
        if($op==='keep'&&$owner&&$b['status']==='cancel_requested'&&$reason)$next='confirmed';
        if(!$next){$wpdb->query('ROLLBACK');return self::error('অবস্থা/অনুমতি/কারণ পরীক্ষা করুন।',409);}
        if(false===$wpdb->update(self::table('bookings'),array('status'=>$next,'reason'=>$reason),array('id'=>$b['id']))||false===self::event($b['id'],$op,$reason)){$wpdb->query('ROLLBACK');return self::error('সংরক্ষণ ব্যর্থ।',500);}
        $wpdb->query('COMMIT');return array('status'=>$next);
    }
    static function hotel($r) {
        $id=(int)$r['id'];if(!$id&&!self::admin())return self::error('অনুমতি নেই।',403);if($id&&(!self::hotel_data($id)||!self::manages($id)))return self::error('অনুমতি নেই।',403);
        $name=sanitize_text_field($r['name']);if(!$name)return self::error('হোটেলের নাম দিন।');
        $profile=array('address'=>sanitize_textarea_field($r['address']),'phone'=>sanitize_text_field($r['phone']),'policy'=>sanitize_textarea_field($r['policy']),'ready'=>(bool)$r['ready']);
        foreach(array('child_free'=>17,'child_fee'=>100000,'tax'=>100,'breakfast'=>100000,'half'=>100000,'full'=>100000) as $key=>$max){$v=$r[$key];if(!is_numeric($v)||$v<0||$v>$max)return self::error('দাম/নীতির সংখ্যা সঠিক নয়।');$profile[$key]=(float)$v;}
        if($profile['ready']&&(!$profile['policy']||!$profile['phone']))return self::error('চালু করার আগে নিজস্ব ফোন ও নীতি দিন।');
        $existing=$id?self::hotel_data($id):array();foreach(array('photos','individualRooms','source_note') as $key)if(isset($existing[$key]))$profile[$key]=$existing[$key];
        $id=wp_insert_post(array('ID'=>$id,'post_type'=>'toto_hotel','post_status'=>'publish','post_title'=>$name,'post_content'=>sanitize_textarea_field($r['description'])),true);if(is_wp_error($id))return $id;update_post_meta($id,'toto_profile',$profile);return array('id'=>$id);
    }
    static function room($r) {
        global $wpdb;$hotel=(int)$r['hotel'];if(!self::hotel_data($hotel)||!self::manages($hotel))return self::error('অনুমতি নেই।',403);
        if(!is_numeric($r['total'])||floor((float)$r['total'])!==(float)$r['total']||!is_numeric($r['capacity'])||floor((float)$r['capacity'])!==(float)$r['capacity'])return self::error('মোট রুম ও ক্ষমতা পূর্ণসংখ্যায় দিন।');$id=(int)$r['id'];$total=(int)$r['total'];$cap=(int)$r['capacity'];$rate=$r['rate'];$name=sanitize_text_field($r['name']);if(!$name||$total<0||$total>1000||$cap<1||$cap>20||!is_numeric($rate)||$rate<0||$rate>10000000)return self::error('রুমের তথ্য পরীক্ষা করুন।');
        $wpdb->query('START TRANSACTION');if($id){$old=$wpdb->get_row($wpdb->prepare('SELECT * FROM '.self::table('rooms').' WHERE id=%d FOR UPDATE',$id),ARRAY_A);if(!$old||(int)$old['hotel']!==$hotel){$wpdb->query('ROLLBACK');return self::error('রুম পাওয়া যায়নি।');}
        $peak=(int)$wpdb->get_var($wpdb->prepare('SELECT COALESCE(MAX(occupied),0) FROM (SELECT SUM(b.rooms) occupied FROM '.self::table('bookings').' b JOIN (SELECT DISTINCT room,arrival FROM '.self::table('bookings')." WHERE status IN ('confirmed','cancel_requested')) d ON b.room=d.room AND b.arrival<=d.arrival AND b.departure>d.arrival WHERE b.room=%d AND b.status IN ('confirmed','cancel_requested') AND b.departure>%s GROUP BY d.arrival) occupancy",$id,current_time('Y-m-d')));
        if($total<$peak){$wpdb->query('ROLLBACK');return self::error('নিশ্চিত বুকিংয়ের নিচে রুম কমানো যাবে না।',409);}}
        $data=array('hotel'=>$hotel,'name'=>$name,'total'=>$total,'capacity'=>$cap,'rate'=>round((float)$rate,2));$ok=$id?$wpdb->update(self::table('rooms'),$data,array('id'=>$id)):$wpdb->insert(self::table('rooms'),$data);if($ok===false){$wpdb->query('ROLLBACK');return self::error('সংরক্ষণ ব্যর্থ।',500);}if(!$id)$id=$wpdb->insert_id;$wpdb->query('COMMIT');return array('id'=>$id);
    }
    static function inventory($r) {
        global $wpdb;$id=(int)$r['room'];$wpdb->query('START TRANSACTION');$room=$wpdb->get_row($wpdb->prepare('SELECT * FROM '.self::table('rooms').' WHERE id=%d FOR UPDATE',$id),ARRAY_A);if(!$room||!self::manages($room['hotel'])){$wpdb->query('ROLLBACK');return self::error('অনুমতি নেই।',403);}
        if(!is_numeric($r['total'])||floor((float)$r['total'])!==(float)$r['total']){$wpdb->query('ROLLBACK');return self::error('রুম সংখ্যা পূর্ণসংখ্যায় দিন।');}$days=self::dates($r['arrival'],$r['departure']);$total=(int)$r['total'];$rate=$r['rate'];if(is_wp_error($days)||$total<0||$total>1000||!is_numeric($rate)||$rate<0||$rate>10000000){$wpdb->query('ROLLBACK');return self::error('ইনভেন্টরি/তারিখ পরীক্ষা করুন।');}
        foreach($days as $day){$used=(int)$wpdb->get_var($wpdb->prepare('SELECT COALESCE(SUM(rooms),0) FROM '.self::table('bookings')." WHERE room=%d AND status IN ('confirmed','cancel_requested') AND arrival<=%s AND departure>%s",$id,$day,$day));if($total<$used){$wpdb->query('ROLLBACK');return self::error('নিশ্চিত বুকিংয়ের নিচে ইনভেন্টরি কমানো যাবে না।',409);}
        if(false===$wpdb->replace(self::table('inventory'),array('room'=>$id,'day'=>$day,'total'=>$total,'rate'=>round((float)$rate,2),'stopped'=>(int)(bool)$r['stopped']))){$wpdb->query('ROLLBACK');return self::error('সংরক্ষণ ব্যর্থ।',500);}}
        $wpdb->query('COMMIT');return array('saved'=>count($days));
    }
    static function payment($r) {
        global $wpdb;$wpdb->query('START TRANSACTION');$b=$wpdb->get_row($wpdb->prepare('SELECT * FROM '.self::table('bookings').' WHERE id=%d FOR UPDATE',$r['id']),ARRAY_A);
        if(!$b||!self::manages($b['hotel'])){$wpdb->query('ROLLBACK');return self::error('অনুমতি নেই।',403);}
        $amount=$r['amount'];$ref=sanitize_text_field($r['reference']);$paid=(float)$wpdb->get_var($wpdb->prepare('SELECT COALESCE(SUM(amount),0) FROM '.self::table('payments').' WHERE booking=%d',$b['id']));$quote=json_decode($b['quote'],true);
        if(($b['status']==='cancelled'&&(float)$amount>0)||!is_numeric($amount)||round((float)$amount,2)==0||!$ref||$paid+(float)$amount<0||$paid+(float)$amount>(float)$quote['total']||!in_array($b['status'],array('confirmed','cancel_requested','cancelled'),true)){$wpdb->query('ROLLBACK');return self::error('পেমেন্ট/রিফান্ডের পরিমাণ বা অবস্থা পরীক্ষা করুন।');}
        $ok=$wpdb->insert(self::table('payments'),array('booking'=>$b['id'],'actor'=>get_current_user_id(),'amount'=>round((float)$amount,2),'reference'=>$ref,'created'=>current_time('mysql')));if(!$ok||false===self::event($b['id'],'payment_recorded',$ref)){$wpdb->query('ROLLBACK');return self::error('সংরক্ষণ ব্যর্থ।',500);}$wpdb->query('COMMIT');return array('recorded'=>true);
    }
    static function seed($r) {
        if(!self::admin())return self::error('অনুমতি নেই।',403);
        if(!add_option('toto_leo_seeded','installing','','no'))return self::error('লিওর তথ্য ইতিমধ্যে যোগ হয়েছে।',409);
        $data=json_decode(file_get_contents(__DIR__.'/assets/leo.json'),true);
        $id=wp_insert_post(array('post_type'=>'toto_hotel','post_status'=>'publish','post_title'=>'HOTEL LEO INTERNATIONAL','post_content'=>'Upper Sichey, Gangtok. Imported listing; owner must verify current inventory and policies.'),true);if(is_wp_error($id)){delete_option('toto_leo_seeded');return $id;}
        $photos=array();foreach($data['photos'] as $file)$photos[]=plugins_url('assets/leo/'.$file,__FILE__);
        update_post_meta($id,'toto_profile',array('address'=>$data['address'],'photos'=>$photos,'individualRooms'=>$data['individualRooms'],'ready'=>false,'source_note'=>'14 listed rooms; representative room photos. Indicative INR conversion 2026-10-05: 1 BDT = 0.78428014 INR. Owner verification required.'));
        global $wpdb;foreach($data['rooms'] as $room)$wpdb->insert(self::table('rooms'),array('hotel'=>$id,'name'=>$room['name'],'total'=>$room['total'],'capacity'=>$room['guests'],'rate'=>$room['rate']));update_option('toto_leo_seeded',$id,false);return array('id'=>$id);
    }
    static function portal() {
        if (!defined('DONOTCACHEPAGE')) define('DONOTCACHEPAGE',true);
        wp_enqueue_style('toto-booking',plugins_url('assets/portal.css',__FILE__),array(),'0.1.0');
        if(!is_user_logged_in())return '<div id="toto-booking"><header class="toto-header"><small>TOTO COMPANY · HOTEL PARTNER NETWORK</small><h1>আপনার হোটেল বুকিং পোর্টাল</h1><p>অনুমোদিত এজেন্ট ও হোটেল কর্তৃপক্ষের জন্য</p></header><div class="toto-card" style="max-width:480px;margin:24px auto"><h2>লগইন করুন</h2>'.wp_login_form(array('echo'=>false)).'<p><a href="'.esc_url(wp_lostpassword_url(get_permalink())).'">পাসওয়ার্ড ভুলে গেছেন?</a></p><p>নতুন অ্যাকাউন্টের জন্য কোম্পানির অ্যাডমিনের সঙ্গে যোগাযোগ করুন।</p></div></div>';
        if(!self::allowed())return '<div id="toto-booking"><p>আপনার অ্যাকাউন্টকে এজেন্ট বা হোটেল কর্তৃপক্ষ হিসেবে সক্রিয় করতে কোম্পানির অ্যাডমিনের সঙ্গে যোগাযোগ করুন।</p></div>';
        wp_enqueue_script('toto-booking',plugins_url('assets/portal.js',__FILE__),array(),'0.1.0',true);
        wp_localize_script('toto-booking','TotoBooking',array('api'=>rest_url(self::NS.'/'),'nonce'=>wp_create_nonce('wp_rest')));
        return '<div id="toto-booking"><p>পোর্টাল লোড হচ্ছে…</p></div>';
    }
    static function setup_menu() { add_management_page('Toto Booking Setup','Toto Booking Setup','manage_options','toto-booking-setup',array(__CLASS__,'setup_page')); }
    static function setup_page() {
        if(!self::admin())return;
        $id=(int)get_option('toto_portal_page');
        echo '<div class="wrap"><h1>Toto Hotel Booking</h1><p>WordPress-এর shared database, agent login ও hotel approval ব্যবস্থা।</p><ol><li>পোর্টালের draft পেজে Preview দিয়ে পরীক্ষা করুন। অনুমোদিত হলে Publish করুন। বর্তমান homepage পরিবর্তন করা হয়নি।</li><li>পোর্টালে লিওর তথ্য যোগ করুন। হোটেলের নিজস্ব ফোন, বর্তমান রেট, রুম ও নীতি যাচাই করে বুকিং চালু করুন।</li><li>Users → Add New দিয়ে Toto Booking Agent এবং Toto Hotel Authority অ্যাকাউন্ট তৈরি করুন।</li><li>হোটেল কর্তৃপক্ষের User Profile-এ Assigned hotel IDs লিখুন; এজেন্টের কমিশন দিন। হোটেলের ID পোর্টালে নামে পাশে দেখা যায়।</li><li>পোর্টাল পেজকে cache থেকে বাদ দিন। HTTPS চালু রাখুন।</li></ol>';
        if($id){echo '<p><a class="button button-primary" href="'.esc_url(get_preview_post_link($id)).'">পোর্টাল Preview</a> <a class="button" href="'.esc_url(get_edit_post_link($id)).'">পেজ Edit / Publish</a></p>';}
        echo '<p>পেমেন্ট/রিফান্ড এখানে হিসাব হিসেবে নথিভুক্ত হয়; online payment gateway বা SMS পাঠানো যুক্ত নেই। পুরোনো browser demo-র বুকিং এখানে নিজে থেকে import হয় না।</p></div>';
    }
    static function templates($templates) { $templates['toto-booking-standalone.php']='Toto Booking — Full page';return $templates; }
    static function template($template) { return is_page() && get_page_template_slug()==='toto-booking-standalone.php' ? __DIR__.'/portal-template.php' : $template; }
    static function no_cache() { if(is_page() && (get_page_template_slug()==='toto-booking-standalone.php' || has_shortcode((string)get_post_field('post_content',get_queried_object_id()),'toto_booking'))){if(!defined('DONOTCACHEPAGE'))define('DONOTCACHEPAGE',true);nocache_headers();} }
    static function profile_fields($user) {
        if(!self::admin())return;
        echo '<h2>Toto booking access</h2><p>Role: Toto Booking Agent / Toto Hotel Authority নির্বাচন করুন।</p><p><label>Assigned hotel IDs (comma separated) <input name="toto_hotels" value="'.esc_attr(implode(',',(array)get_user_meta($user->ID,'toto_hotels',true))).'"></label></p><p><label>Agent commission % <input type="number" min="0" max="100" step="0.01" name="toto_commission" value="'.esc_attr(get_user_meta($user->ID,'toto_commission',true)).'"></label></p>';
    }
    static function save_profile($id) {
        if(!self::admin()||!current_user_can('edit_user',$id))return;
        if(isset($_POST['toto_hotels']))update_user_meta($id,'toto_hotels',array_values(array_filter(array_map('absint',explode(',',sanitize_text_field(wp_unslash($_POST['toto_hotels'])))))));
        if(isset($_POST['toto_commission']))update_user_meta($id,'toto_commission',min(100,max(0,(float)$_POST['toto_commission'])));
    }
}
register_activation_hook(__FILE__,array('Toto_Partner_Booking','install'));
add_action('init',array('Toto_Partner_Booking','boot'));
add_action('rest_api_init',array('Toto_Partner_Booking','routes'));
add_action('show_user_profile',array('Toto_Partner_Booking','profile_fields'));
add_action('edit_user_profile',array('Toto_Partner_Booking','profile_fields'));
add_action('personal_options_update',array('Toto_Partner_Booking','save_profile'));
add_action('edit_user_profile_update',array('Toto_Partner_Booking','save_profile'));
add_action('admin_menu',array('Toto_Partner_Booking','setup_menu'));
add_filter('theme_page_templates',array('Toto_Partner_Booking','templates'));
add_filter('template_include',array('Toto_Partner_Booking','template'));
add_action('template_redirect',array('Toto_Partner_Booking','no_cache'));
