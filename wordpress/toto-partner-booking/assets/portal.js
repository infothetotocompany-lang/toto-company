/* Toto WordPress portal: server-side access, pricing and stock are authoritative. */
(() => {
  'use strict';
  const root = document.getElementById('toto-booking');
  if (!root) return;
  let state, view = 'hotels', selected = 0, nightly = [];
  const e = value => String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  const money = n => new Intl.NumberFormat('en-IN', {style:'currency', currency:'INR'}).format(Number(n || 0));
  const status = {pending:'অপেক্ষমাণ',confirmed:'নিশ্চিত',rejected:'প্রত্যাখ্যাত',cancel_requested:'বাতিলের অনুমোদন বাকি',cancelled:'বাতিল'};
  const hotel = id => state.hotels.find(h => Number(h.id) === Number(id));
  const room = id => state.rooms.find(r => Number(r.id) === Number(id));
  const field = (name, label, type = 'text', value = '', extra = '') => `<label>${label}<input name="${name}" type="${type}" value="${e(value)}" ${extra}></label>`;
  const textarea = (name, label, value = '') => `<label>${label}<textarea name="${name}">${e(value)}</textarea></label>`;
  const options = (items, value) => items.map(i => `<option value="${e(i.id)}" ${Number(i.id) === Number(value) ? 'selected' : ''}>${e(i.name)}</option>`).join('');
  async function api(path, data) {
    const [endpoint, query = ''] = path.split('?');
    const url = new URL(TotoBooking.api);
    if (url.searchParams.has('rest_route')) url.searchParams.set('rest_route', url.searchParams.get('rest_route') + endpoint);
    else url.pathname += endpoint;
    for (const [key, value] of new URLSearchParams(query)) url.searchParams.set(key,value);
    const response = await fetch(url, {method:data ? 'POST' : 'GET', credentials:'same-origin', headers:{'X-WP-Nonce':TotoBooking.nonce,...(data ? {'Content-Type':'application/json'} : {})}, body:data ? JSON.stringify(data) : undefined});
    const result = await response.json();
    if (!response.ok) throw new Error(result.message || 'অনুরোধ ব্যর্থ হয়েছে।');
    return result;
  }
  function message(text, bad = false) {
    const box = root.querySelector('[data-message]'); box.textContent = text; box.classList.toggle('bad',bad);box.focus();
  }
  async function refresh() { state = await api('state'); render(); }
  function hotelCards() {
    return `<div class="toto-grid">${state.hotels.map(h => `<article class="toto-card">${h.photos?.length ? `<img class="toto-cover" src="${e(h.photos[0])}" alt="${e(h.name)}">` : ''}<h3>${e(h.name)}</h3><p>${e(h.address)}</p><p>${e(h.phone || 'হোটেলের ফোন নিশ্চিত করা বাকি')}</p><p>${h.ready ? 'বুকিং চালু' : 'মালিকের তথ্য ও নীতি নিশ্চিত করা বাকি'}</p><button data-hotel="${h.id}">হোটেল ও রুম দেখুন</button></article>`).join('')}</div>${state.admin ? '<button data-seed>লিওর প্রকাশিত রুম ও ছবি যোগ করুন</button><button data-new-hotel>নতুন হোটেল</button>' : ''}`;
  }
  function details() {
    const h = hotel(selected); if (!h) return hotelCards();
    return `<h2>${e(h.name)} <small>#${h.id}</small></h2><p>${e(h.address)} · ${e(h.phone || 'ফোন নিশ্চিত করা বাকি')}</p><p>${e(h.description)}</p><div class="toto-gallery">${(h.photos || []).map((src,i) => `<img loading="lazy" src="${e(src)}" alt="হোটেলের প্রকাশিত ছবি ${i+1}">`).join('')}</div><p>${e(h.source_note || '')}</p><h3>রুম ও প্রতি রাতের বেস রেট</h3><div class="toto-grid">${state.rooms.filter(r => Number(r.hotel) === Number(h.id)).map(r => `<article class="toto-card"><h4>${e(r.name)}</h4><p>${money(r.rate)} · মোট ${e(r.total)} রুম · প্রতি রুমে সর্বোচ্চ ${e(r.capacity)} অতিথি</p>${state.agent && h.ready ? `<button data-book="${r.id}">তারিখ ও বুকিং</button>` : ''}${h.manage ? `<button data-room="${r.id}">রুমের তথ্য</button><button data-stock="${r.id}">তারিখ অনুযায়ী ইনভেন্টরি</button>` : ''}</article>`).join('')}</div><h3>হোটেলের নীতি</h3><p class="toto-lines">${e(h.policy || 'নীতি নিশ্চিত করা বাকি')}</p><p>শিশু: ${e(h.child_free)} বছর পর্যন্ত অতিরিক্ত রুম চার্জ নেই; এরপর ${money(h.child_fee)} / শিশু / রাত। খাবার সব অতিথির জন্য প্রতি রাতে: breakfast ${money(h.breakfast)}, half board ${money(h.half)}, full board ${money(h.full)}। ট্যাক্স: ${e(h.tax)}%।</p>${h.individualRooms?.length ? `<details><summary>প্রকাশিত ${h.individualRooms.length} পৃথক রুমের তালিকা</summary><p>রুমের ছবি প্রতিনিধিত্বমূলক। বুকিং রুমের ধরন ও সংখ্যায় হয়; নির্দিষ্ট রুম নম্বর বরাদ্দ করা হয় না।</p><ul>${h.individualRooms.map(r => `<li>${e(r.number)} · ${e(r.floor)} · ${e(r.description)}</li>`).join('')}</ul></details>` : ''}${h.manage ? `<button data-edit-hotel="${h.id}">হোটেল, ফোন ও নীতি আপডেট</button><button data-room="0">নতুন রুমের ধরন</button>` : ''}`;
  }
  function bookingForm(id) {
    const r = room(id); const h = hotel(r.hotel); nightly = [];
    return `<h2>বুকিং অনুরোধ: ${e(h.name)} / ${e(r.name)}</h2><form data-form="booking"><input type="hidden" name="room" value="${r.id}"><div class="toto-grid">${field('guest','অতিথির নাম','text','','required maxlength="180"')}${field('phone','অতিথির ফোন','tel','','required maxlength="40"')}${field('arrival','চেক-ইন','date','','required')}${field('departure','চেক-আউট','date','','required')}${field('rooms','রুম সংখ্যা','number',1,'required min="1" max="50"')}${field('adults','প্রাপ্তবয়স্ক','number',2,'required min="1" max="1000"')}${field('ages','শিশুদের বয়স (কমা দিয়ে, যেমন ৩,৭)','text','','placeholder="3,7"')}<label>খাবার<select name="meal"><option value="room">Room only</option><option value="breakfast">Breakfast</option><option value="half">Half board</option><option value="full">Full board</option></select></label></div>${textarea('notes','বিশেষ অনুরোধ')}<button type="button" data-check>রুমের খালি অবস্থা ও হিসাব দেখুন</button><div data-quote aria-live="polite"></div><p class="toto-lines">${e(h.policy)}</p><label class="toto-check"><input name="consent" type="checkbox" required> হোটেলের নীতি ও অতিথির তথ্য ব্যবহারের সম্মতি নেওয়া হয়েছে।</label><p>অনুরোধ পাঠালে অপেক্ষমাণ থাকবে। হোটেল অনুমোদনের আগে রুম ধরে রাখা হয় না।</p><button type="submit">বুকিং অনুরোধ পাঠান</button></form>`;
  }
  function quoteForm(form) {
    if (!nightly.length) return;
    const d = Object.fromEntries(new FormData(form)); const r = room(d.room), h = hotel(r.hotel);
    const ages = d.ages.trim() ? d.ages.split(',').map(x => Number(x.trim())) : [];
    const base = nightly.reduce((s,n) => s + n.rate * Number(d.rooms), 0);
    const children = ages.filter(a => a > h.child_free).length * Number(h.child_fee) * nightly.length;
    const meals = d.meal === 'room' ? 0 : Number(h[d.meal]) * (Number(d.adults) + ages.length) * nightly.length;
    const tax = Math.round((base+children+meals) * h.tax)/100;
    form.querySelector('[data-quote]').innerHTML = `<p>রুম ${money(base)} + শিশু ${money(children)} + খাবার ${money(meals)} + ট্যাক্স ${money(tax)} = <strong>${money(base+children+meals+tax)}</strong></p><p>উপলব্ধ প্রতি রাতে: ${nightly.map(n => `${e(n.day)}: ${n.stopped ? 'বুকিং বন্ধ' : e(n.available)+' রুম'}`).join(' · ')}</p><p>চূড়ান্ত হিসাব সার্ভারে যাচাই হবে।</p>`;
  }
  function bookingCards() {
    return `<h2>বুকিং ও অনুমোদন</h2><label>নাম/বুকিং নম্বর খুঁজুন<input data-search></label><label>অবস্থা<select data-status><option value="">সব</option>${Object.keys(status).map(s => `<option value="${s}">${status[s]}</option>`).join('')}</select></label><div data-bookings>${state.bookings.map(b => {
      const manages = hotel(b.hotel)?.manage, paid = b.payments.reduce((s,p) => s+Number(p.amount),0);
      return `<article class="toto-card" data-record data-key="${e((b.id+' '+b.guest+' '+b.phone).toLowerCase())}" data-state="${e(b.status)}"><h3>#${b.id} · ${e(b.guest)} <span class="toto-status">${status[b.status] || e(b.status)}</span></h3><p>${e(hotel(b.hotel)?.name)} / ${e(room(b.room)?.name)}</p><p>${e(b.phone)} · ${e(b.arrival)} → ${e(b.departure)} · ${e(b.rooms)} রুম · ${e(b.adults)} বড় + ${b.ages.length} শিশু</p><p>মোট ${money(b.quote.total)} · নথিভুক্ত পেমেন্ট ${money(paid)} · বাকি ${money(b.quote.total-paid)} · কমিশন ${money(b.quote.commission)}</p><p>${e(b.notes)} ${e(b.reason)}</p>${manages && b.status === 'pending' ? `<button data-action="approve" data-id="${b.id}">অনুমোদন</button><button data-action="reject" data-id="${b.id}">প্রত্যাখ্যান</button>` : ''}${state.agent && ['pending','confirmed'].includes(b.status) ? `<button data-action="cancel" data-id="${b.id}">বাতিলের অনুরোধ</button>` : ''}${manages && b.status === 'cancel_requested' ? `<button data-action="approve_cancel" data-id="${b.id}">বাতিল অনুমোদন</button><button data-action="keep" data-id="${b.id}">বুকিং রাখুন</button>` : ''}${manages && ['confirmed','cancel_requested','cancelled'].includes(b.status) ? `<button data-payment="${b.id}">পেমেন্ট/রিফান্ড নথিভুক্ত</button>` : ''}${b.status === 'confirmed' ? `<button data-voucher="${b.id}">ভাউচার / PDF</button>` : ''}<details><summary>হিসাব ও কাজের ইতিহাস</summary><p>রুম ${money(b.quote.base)} · শিশু ${money(b.quote.children)} · খাবার ${money(b.quote.meals)} · ট্যাক্স ${money(b.quote.tax)}</p><p>${e(b.quote.policy)}</p>${b.events.map(v => `<p>${e(v.created)} · ${e(v.action)} · ${e(v.detail)}</p>`).join('')}${b.payments.map(p => `<p>${e(p.created)} · ${money(p.amount)} · ${e(p.reference)}</p>`).join('')}</details></article>`;
    }).join('') || '<p>এখনও বুকিং নেই।</p>'}</div><p>সর্বশেষ সর্বোচ্চ ৫০০ বুকিং দেখানো হয়।</p>`;
  }
  function hotelForm(id) {
    const h = hotel(id) || {id:0,child_free:5,child_fee:0,tax:0,breakfast:0,half:0,full:0};
    return `<h2>হোটেলের তথ্য ও নিশ্চিত নীতি</h2><form data-form="hotel"><input type="hidden" name="id" value="${h.id}"><div class="toto-grid">${field('name','হোটেলের নাম','text',h.name,'required')}${field('phone','হোটেলের নিজস্ব ফোন','tel',h.phone)}${field('child_free','শিশু ফ্রি বয়স','number',h.child_free,'min="0" max="17"')}${field('child_fee','এরপর শিশু / রাত (INR)','number',h.child_fee,'min="0" step="0.01"')}${field('tax','ট্যাক্স %','number',h.tax,'min="0" max="100" step="0.01"')}${field('breakfast','Breakfast / অতিথি / রাত','number',h.breakfast,'min="0" step="0.01"')}${field('half','Half board / অতিথি / রাত','number',h.half,'min="0" step="0.01"')}${field('full','Full board / অতিথি / রাত','number',h.full,'min="0" step="0.01"')}</div>${textarea('address','ঠিকানা',h.address)}${textarea('description','বিবরণ',h.description)}${textarea('policy','চেক-ইন/আউট, শিশু, বাতিল, ট্যাক্স ও পেমেন্টের নিশ্চিত নীতি',h.policy)}<label class="toto-check"><input type="checkbox" name="ready" ${h.ready?'checked':''}> ফোন, রেট, ইনভেন্টরি ও নীতি যাচাই করেছি; বুকিং চালু করুন।</label><button>সংরক্ষণ</button></form>`;
  }
  function roomForm(id) {
    const r = room(id) || {id:0,hotel:selected,total:1,capacity:2,rate:0};
    return `<h2>রুমের ধরন</h2><form data-form="room"><input type="hidden" name="id" value="${r.id}"><input type="hidden" name="hotel" value="${r.hotel}">${field('name','রুমের ধরন','text',r.name,'required')}${field('total','মোট রুম','number',r.total,'required min="0" max="1000"')}${field('capacity','প্রতি রুমে মোট অতিথি (শিশুসহ)','number',r.capacity,'required min="1" max="20"')}${field('rate','বেস রেট INR / রাত','number',r.rate,'required min="0" step="0.01"')}<button>সংরক্ষণ</button></form>`;
  }
  function stockForm(id) {
    const r = room(id);
    return `<h2>${e(r.name)}: রাত অনুযায়ী ইনভেন্টরি</h2><p>শেষ তারিখের রাত অন্তর্ভুক্ত নয়। নিশ্চিত বুকিংয়ের নিচে রুম কমানো যাবে না।</p><form data-form="inventory"><input type="hidden" name="room" value="${r.id}">${field('arrival','শুরু','date','','required')}${field('departure','শেষ (exclusive)','date','','required')}${field('total','এই সময়ের মোট রুম','number',r.total,'required min="0" max="1000"')}${field('rate','INR / রাত','number',r.rate,'required min="0" step="0.01"')}<label class="toto-check"><input type="checkbox" name="stopped"> নতুন বুকিং বন্ধ (stop sale)</label><button>সংরক্ষণ</button></form>`;
  }
  function reports() {
    const confirmed = state.bookings.filter(b => ['confirmed','cancel_requested'].includes(b.status));
    return `<h2>বুকিং রিপোর্ট</h2><div class="toto-grid"><article class="toto-card">নিশ্চিত / বাতিলের অপেক্ষায়: ${confirmed.length}</article><article class="toto-card">বুকিং মূল্য: ${money(confirmed.reduce((s,b)=>s+Number(b.quote.total),0))}</article><article class="toto-card">কমিশন: ${money(confirmed.reduce((s,b)=>s+Number(b.quote.commission),0))}</article></div><p>এটি আপনার অনুমোদিত সর্বশেষ ৫০০ বুকিংয়ের সারাংশ; পেমেন্ট সংগ্রহের রিপোর্ট আলাদা।</p><button data-export>অনুমোদিত বুকিং CSV ডাউনলোড</button>`;
  }
  function render(content) {
    root.innerHTML = `<header class="toto-header"><div><small>TOTO COMPANY · HOTEL PARTNER NETWORK</small><h1>হোটেল বুকিং পোর্টাল</h1><p>${e(state.user)} · তথ্য WordPress ডেটাবেসে সংরক্ষিত</p></div></header><nav><button data-view="hotels">হোটেল ও রুম</button><button data-view="bookings">বুকিং</button><button data-view="reports">রিপোর্ট</button><button data-refresh>তথ্য আপডেট</button></nav><p data-message role="status" tabindex="-1"></p><main>${content || (view==='bookings'?bookingCards():view==='reports'?reports():view==='details'?details():hotelCards())}</main>`;
  }
  root.addEventListener('submit', async event => {
    const form = event.target.closest('[data-form]'); if (!form) return; event.preventDefault();
    const data = Object.fromEntries(new FormData(form)), path = form.dataset.form;
    for (const name of ['ready','stopped','consent']) data[name] = Boolean(form.elements[name]?.checked);
    if (path==='booking') data.ages = data.ages.trim() ? data.ages.split(',').map(x=>Number(x.trim())) : [];
    const button = form.querySelector('button[type="submit"],button:not([type])'); if(button) button.disabled=true;
    try { await api(path,data); view=path==='booking'?'bookings':'details';if(path==='hotel')view='hotels';await refresh();message('সংরক্ষণ হয়েছে।'); }
    catch(error){message(error.message,true);if(button)button.disabled=false;}
  });
  root.addEventListener('input', event => {
    const form=event.target.closest('[data-form="booking"]');if(form){if(['arrival','departure'].includes(event.target.name)){nightly=[];form.querySelector('[data-quote]').textContent='তারিখ বদলেছে; আবার খালি অবস্থা পরীক্ষা করুন।';}else quoteForm(form);}
    filterBookings();
  });
  function filterBookings(){const q=root.querySelector('[data-search]')?.value.toLowerCase()||'',s=root.querySelector('[data-status]')?.value||'';root.querySelectorAll('[data-record]').forEach(el=>{el.hidden=!el.dataset.key.includes(q)||(s&&el.dataset.state!==s);});}
  root.addEventListener('change',filterBookings);
  root.addEventListener('click', async event => {
    const button=event.target.closest('button');if(!button)return;
    try {
      if(button.dataset.view){view=button.dataset.view;render();}
      if(button.hasAttribute('data-refresh'))await refresh();
      if(button.dataset.hotel){selected=Number(button.dataset.hotel);view='details';render();}
      if(button.dataset.book)render(bookingForm(button.dataset.book));
      if(button.dataset.editHotel)render(hotelForm(button.dataset.editHotel));
      if(button.hasAttribute('data-new-hotel'))render(hotelForm(0));
      if(button.hasAttribute('data-room'))render(roomForm(button.dataset.room));
      if(button.dataset.stock)render(stockForm(button.dataset.stock));
      if(button.hasAttribute('data-seed')){button.disabled=true;await api('seed',{});await refresh();message('লিওর প্রকাশিত তথ্য যোগ হয়েছে। মালিকের যাচাইয়ের আগে বুকিং বন্ধ থাকবে।');}
      if(button.hasAttribute('data-check')){const f=button.closest('form'),d=Object.fromEntries(new FormData(f));nightly=await api(`availability?room=${encodeURIComponent(d.room)}&arrival=${encodeURIComponent(d.arrival)}&departure=${encodeURIComponent(d.departure)}`);quoteForm(f);}
      if(button.dataset.action){let reason='';if(['reject','cancel','keep'].includes(button.dataset.action)){reason=window.prompt('কারণ লিখুন');if(!reason)return;}if(!window.confirm('এই বুকিংয়ের পরিবর্তন নিশ্চিত করবেন?'))return;button.disabled=true;await api('action',{id:button.dataset.id,action:button.dataset.action,reason});await refresh();message('বুকিং আপডেট হয়েছে।');}
      if(button.dataset.payment){const amount=window.prompt('প্রাপ্ত পেমেন্ট INR; রিফান্ড হলে ঋণাত্মক পরিমাণ লিখুন');if(amount===null)return;const reference=window.prompt('রসিদ/ব্যাংক রেফারেন্স ও বিবরণ');if(!reference)return;await api('payment',{id:button.dataset.payment,amount,reference});await refresh();message('হিসাব নথিভুক্ত হয়েছে। এই ব্যবস্থা অর্থ লেনদেন করে না।');}
      if(button.dataset.voucher){const b=state.bookings.find(b=>Number(b.id)===Number(button.dataset.voucher));const popup=window.open('','_blank');if(!popup)throw new Error('ভাউচারের জন্য popup অনুমতি দিন।');popup.opener=null;popup.document.write(`<!doctype html><html lang="bn"><meta charset="utf-8"><title>Booking ${b.id}</title><body><h1>Toto Company · Confirmed booking #${b.id}</h1><h2>${e(hotel(b.hotel)?.name)}</h2><p>${e(hotel(b.hotel)?.address)} · ${e(hotel(b.hotel)?.phone)}</p><h3>${e(b.guest)} · ${e(b.phone)}</h3><p>${e(b.arrival)} → ${e(b.departure)} · ${e(room(b.room)?.name)} · ${e(b.rooms)} rooms</p><p>${e(b.adults)} adults + ${b.ages.length} children</p><p>Total ${money(b.quote.total)}</p><p>${e(b.quote.policy)}</p><p>Confirmed accommodation voucher; payment record must be checked separately.</p></body></html>`);popup.document.close();popup.focus();popup.print();}
      if(button.hasAttribute('data-export')){const cell=v=>'"'+String(/^[=+@\-\t\r\n]/.test(String(v))?"'"+v:v).replace(/"/g,'""')+'"';const rows=[['ID','Hotel','Guest','Phone','Arrival','Departure','Rooms','Status','INR Total','INR Commission'],...state.bookings.map(b=>[b.id,hotel(b.hotel)?.name,b.guest,b.phone,b.arrival,b.departure,b.rooms,b.status,b.quote.total,b.quote.commission])];const url=URL.createObjectURL(new Blob(['\ufeff'+rows.map(row=>row.map(cell).join(',')).join('\r\n')],{type:'text/csv;charset=utf-8'}));const link=document.createElement('a');link.href=url;link.download='toto-bookings.csv';link.click();setTimeout(()=>URL.revokeObjectURL(url),1000);}
    }catch(error){button.disabled=false;message(error.message,true);}
  });
  refresh().catch(error=>{root.textContent=error.message;});
})();
