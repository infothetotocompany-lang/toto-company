<?php
require '/var/www/html/wp-load.php';
$c=json_decode(file_get_contents('/tmp/toto-test-credentials.json'),true);$race=json_decode(file_get_contents('/tmp/toto-race.json'),true);
wp_set_current_user($c['users']['owner']);$r=new WP_REST_Request('POST','/toto-booking/v1/action');$r->set_body_params(array('id'=>$race['ids'][(int)$argv[1]],'action'=>'approve'));
$result=rest_do_request($r);echo $result->get_status();
