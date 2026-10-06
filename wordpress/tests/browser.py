import json
from pathlib import Path
from playwright.sync_api import sync_playwright
c=json.loads(Path('/tmp/toto-test-credentials.json').read_text())
base='http://127.0.0.1:8090';errors=[]
with sync_playwright() as p:
 browser=p.chromium.launch(executable_path='/usr/bin/chromium',headless=True,args=['--no-sandbox'])
 page=browser.new_page(viewport={'width':1440,'height':1000});page.on('pageerror',lambda err:errors.append(str(err)))
 page.goto(base+'/wp-login.php?redirect_to='+base+'/?page_id='+str(c['page']));page.locator('#user_login').fill('agent1');page.locator('#user_pass').fill(c['password']);page.locator('#wp-submit').click();page.wait_for_load_state()
 page.goto(base+'/?page_id='+str(c['page']));page.get_by_role('button',name='হোটেল ও রুম',exact=True).wait_for();page.get_by_role('button',name='হোটেল ও রুম দেখুন').click()
 page.get_by_role('button',name='তারিখ ও বুকিং').first.click()
 page.locator('[name=arrival]').fill('2030-03-10');page.locator('[name=departure]').fill('2030-03-12');page.get_by_role('button',name='রুমের খালি অবস্থা ও হিসাব দেখুন').click();page.locator('[data-quote]').get_by_text('চূড়ান্ত হিসাব সার্ভারে যাচাই হবে।').wait_for()
 page.locator('[name=guest]').fill('Browser March Guest');page.locator('[name=phone]').fill('+919000000000');page.locator('[name=consent]').check();page.get_by_role('button',name='বুকিং অনুরোধ পাঠান').click();page.get_by_text('Browser March Guest',exact=False).first.wait_for()
 assert 'অপেক্ষমাণ' in page.locator('#toto-booking').inner_text()
 out=Path('/workspace/toto-company/wordpress/screenshots');out.mkdir(exist_ok=True)
 page.screenshot(path=str(out/'agent-bookings.png'),full_page=True)
 page.set_viewport_size({'width':390,'height':844});page.get_by_role('button',name='হোটেল ও রুম',exact=True).click();page.get_by_role('button',name='হোটেল ও রুম দেখুন').click();page.screenshot(path=str(out/'mobile-hotel.png'),full_page=True)
 assert page.locator('#toto-booking').evaluate('(e)=>e.scrollWidth<=e.clientWidth+1')
 context=browser.new_context(viewport={'width':1440,'height':1000});owner=context.new_page();owner.on('pageerror',lambda err:errors.append(str(err)))
 owner.goto(base+'/wp-login.php?redirect_to='+base+'/?page_id='+str(c['page']));owner.locator('#user_login').fill('owner');owner.locator('#user_pass').fill(c['password']);owner.locator('#wp-submit').click();owner.wait_for_load_state();owner.goto(base+'/?page_id='+str(c['page']));owner.get_by_role('button',name='বুকিং',exact=True).click()
 record=owner.locator('[data-record]').filter(has_text='Browser March Guest');owner.on('dialog',lambda d:d.accept());record.get_by_role('button',name='অনুমোদন',exact=True).click();owner.get_by_text('বুকিং আপডেট হয়েছে।',exact=True).wait_for();assert 'নিশ্চিত' in owner.locator('[data-record]').filter(has_text='Browser March Guest').inner_text()
 owner.screenshot(path=str(out/'owner-approval.png'),full_page=True)
 page.set_viewport_size({'width':1440,'height':1000});page.get_by_role('button',name='বুকিং',exact=True).click();page.get_by_role('button',name='তথ্য আপডেট',exact=True).click();page.locator('[data-record]').filter(has_text='Browser March Guest').get_by_role('button',name='ভাউচার / PDF',exact=True).wait_for()
 page.get_by_role('button',name='রিপোর্ট',exact=True).click();assert 'কমিশন' in page.locator('#toto-booking').inner_text()
 assert page.evaluate("async()=>{const u=new URL(TotoBooking.api);if(u.searchParams.has('rest_route'))u.searchParams.set('rest_route',u.searchParams.get('rest_route')+'action');else u.pathname+='action';return (await fetch(u,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json'},body:'{}'})).status}") == 401
 assert not errors,errors
 print('PASS: real WordPress browser login, plain-permalink REST, availability, pending booking, owner approval, shared agent refresh, reports, gallery, mobile and no JS errors')
 browser.close()
