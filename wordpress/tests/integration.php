<?php
// Run only against an isolated disposable WordPress installation.
require '/var/www/html/wp-load.php';
if(getenv('WORDPRESS_DB_NAME')!=='toto_test'||strpos(get_option('siteurl'),'http://127.0.0.1:')!==0){exit('Disposable loopback test site required.');}
$c=json_decode(file_get_contents('/tmp/toto-test-credentials.json'),true);$u=$c['users'];
function call_api($who,$path,$data=array(),$method='POST') {
 wp_set_current_user($who);$r=new WP_REST_Request($method,'/toto-booking/v1/'.$path);
 if($method==='GET')$r->set_query_params($data);else $r->set_body_params($data);
 return rest_do_request($r);
}
function check($yes,$name){if(!$yes)throw new Exception('FAIL: '.$name);echo 'PASS: '.$name."\n";}
function ok($response,$name){check($response->get_status()===200,$name.' (HTTP '.$response->get_status().')');return $response->get_data();}
update_option('home','http://127.0.0.1:8090');update_option('siteurl','http://127.0.0.1:8090');
check(call_api(0,'state',array(),'GET')->get_status()===401,'anonymous denied');
check(call_api($u['outsider'],'state',array(),'GET')->get_status()===403,'unassigned account denied');
$seed=ok(call_api($u['admin'],'seed'),'hotel import');$hotel=$seed['id'];
check(call_api($u['admin'],'seed')->get_status()===409,'duplicate import prevented');
update_user_meta($u['owner'],'toto_hotels',array($hotel));update_user_meta($u['agent1'],'toto_commission',8);
$s=ok(call_api($u['agent1'],'state',array(),'GET'),'agent state');check(count($s['rooms'])===6&&count($s['hotels'][0]['individualRooms'])===14,'six categories and 14 physical profiles');
$rid=$s['rooms'][0]['id'];$arrival=gmdate('Y-m-d',time()+10*86400);$departure=gmdate('Y-m-d',time()+12*86400);
$input=array('room'=>$rid,'arrival'=>$arrival,'departure'=>$departure,'guest'=>'Test Guest','phone'=>'+919000000000','rooms'=>1,'adults'=>1,'ages'=>array(7),'meal'=>'breakfast','consent'=>true,'notes'=>'<script>alert(1)</script>');
check(call_api($u['agent1'],'booking',$input)->get_status()===400,'unverified hotel cannot accept requests');
$profile=array('id'=>$hotel,'name'=>'HOTEL LEO INTERNATIONAL','phone'=>'+919000000000','address'=>'Test address','description'=>'Test hotel','policy'=>'Test cancellation and child policy','ready'=>true,'child_free'=>5,'child_fee'=>500,'tax'=>12,'breakfast'=>250,'half'=>600,'full'=>900);
check(call_api($u['agent1'],'hotel',$profile)->get_status()===403,'agent cannot change hotel policy');
ok(call_api($u['owner'],'hotel',$profile),'assigned owner confirms policies');
$room=array('id'=>$rid,'hotel'=>$hotel,'name'=>'Test room','total'=>1,'capacity'=>2,'rate'=>1000);
ok(call_api($u['owner'],'room',$room),'owner sets inventory');
$invalid=$input;$invalid['adults']=3;check(call_api($u['agent1'],'booking',$invalid)->get_status()===400,'occupancy guard');
$invalid=$input;$invalid['ages']=array(-1);check(call_api($u['agent1'],'booking',$invalid)->get_status()===400,'child-age validation');
$invalid=$input;$invalid['departure']=$arrival;check(call_api($u['agent1'],'booking',$invalid)->get_status()===400,'zero-night guard');
$b1=ok(call_api($u['agent1'],'booking',$input),'first pending request');$b2=ok(call_api($u['agent2'],'booking',$input),'second pending request');
check(abs($b1['quote']['total']-4480)<0.001&&abs($b1['quote']['commission']-160)<0.001,'server price child meals tax commission');
$a=ok(call_api($u['agent1'],'availability',array('room'=>$rid,'arrival'=>$arrival,'departure'=>$departure),'GET'),'availability');check($a[0]['available']===1,'pending holds no inventory');
check(call_api($u['agent1'],'action',array('id'=>$b1['id'],'action'=>'approve'))->get_status()===409,'agent cannot approve own request');
check(call_api($u['agent2'],'action',array('id'=>$b1['id'],'action'=>'cancel','reason'=>'test'))->get_status()===403,'agent cannot touch another agent booking');
update_user_meta($u['owner'],'toto_hotels',array());
check(call_api($u['owner'],'action',array('id'=>$b1['id'],'action'=>'approve'))->get_status()===403,'unassigned hotel authority cannot approve');
$hidden=ok(call_api($u['owner'],'state',array(),'GET'),'unassigned hotel authority state');check(count($hidden['bookings'])===0,'hotel scope hides guest data');
update_user_meta($u['owner'],'toto_hotels',array($hotel));
ok(call_api($u['owner'],'action',array('id'=>$b1['id'],'action'=>'approve')),'owner approval');
check(call_api($u['owner'],'action',array('id'=>$b2['id'],'action'=>'approve'))->get_status()===409,'overbooking guard');
$room['total']=0;check(call_api($u['owner'],'room',$room)->get_status()===409,'base inventory cannot undercut confirmed reservations');
check(call_api($u['owner'],'inventory',array('room'=>$rid,'arrival'=>$arrival,'departure'=>$departure,'total'=>0,'rate'=>1000,'stopped'=>false))->get_status()===409,'daily inventory cannot undercut confirmed reservations');
check(call_api($u['agent1'],'payment',array('id'=>$b1['id'],'amount'=>100,'reference'=>'Test'))->get_status()===403,'agent cannot record payment');
ok(call_api($u['owner'],'payment',array('id'=>$b1['id'],'amount'=>100,'reference'=>'TEST RECEIPT')),'payment ledger');
check(call_api($u['owner'],'payment',array('id'=>$b1['id'],'amount'=>10000,'reference'=>'bad'))->get_status()===400,'overpayment guard');
ok(call_api($u['agent1'],'action',array('id'=>$b1['id'],'action'=>'cancel','reason'=>'Guest request')),'cancel request');
$a=ok(call_api($u['agent1'],'availability',array('room'=>$rid,'arrival'=>$arrival,'departure'=>$departure),'GET'),'cancel-pending availability');check($a[0]['available']===0,'cancel request still holds stock');
ok(call_api($u['owner'],'action',array('id'=>$b1['id'],'action'=>'approve_cancel')),'hotel approves cancellation');
ok(call_api($u['owner'],'payment',array('id'=>$b1['id'],'amount'=>-100,'reference'=>'TEST REFUND')),'refund ledger');
check(call_api($u['owner'],'payment',array('id'=>$b1['id'],'amount'=>10,'reference'=>'bad'))->get_status()===400,'cancelled bookings cannot collect payment');
$a=ok(call_api($u['agent1'],'availability',array('room'=>$rid,'arrival'=>$arrival,'departure'=>$departure),'GET'),'released availability');check($a[0]['available']===1,'approved cancellation releases rooms');
$s=ok(call_api($u['agent2'],'state',array(),'GET'),'agent privacy state');check(count($s['bookings'])===1&&(int)$s['bookings'][0]['id']===$b2['id'],'guest privacy by agent');
// Duplicate arrival dates must not multiply the occupied-room sum.
$b3=ok(call_api($u['agent1'],'booking',$input),'concurrency contender');
file_put_contents('/tmp/toto-race.json',json_encode(array('ids'=>array($b2['id'],$b3['id']),'room'=>$rid,'hotel'=>$hotel,'arrival'=>$arrival,'departure'=>$departure)));
echo "Core shared-database workflow verified.\n";
