<?php
 if(getenv('WORDPRESS_DB_NAME')!=='toto_test'){exit('Disposable toto_test database required.');}
 $_SERVER['HTTP_HOST']='127.0.0.1:8090';
 define('WP_INSTALLING', true);
 require '/var/www/html/wp-load.php';
 require_once ABSPATH.'wp-admin/includes/upgrade.php';
 require_once ABSPATH.'wp-admin/includes/plugin.php';
 $password=bin2hex(random_bytes(24));
 wp_install('Toto Test','toto_test_admin','test@example.invalid',false,'',$password);
 $result=activate_plugin('toto-partner-booking/toto-partner-booking.php');
 if(is_wp_error($result)){throw new Exception($result->get_error_message());}
 $users=array();
 foreach(array('agent1'=>'toto_agent','agent2'=>'toto_agent','owner'=>'toto_hotel','outsider'=>'subscriber') as $name=>$role){$id=wp_create_user($name,$password,$name.'@example.invalid');$u=new WP_User($id);$u->set_role($role);$users[$name]=$id;}
 $users['admin']=get_user_by('login','toto_test_admin')->ID;
 $page=wp_insert_post(array('post_type'=>'page','post_status'=>'publish','post_title'=>'Toto booking','post_content'=>'[toto_booking]'));
 file_put_contents('/tmp/toto-test-credentials.json',json_encode(array('password'=>$password,'users'=>$users,'page'=>$page)));
 echo "WordPress installed; plugin activated; test accounts created.\n";
