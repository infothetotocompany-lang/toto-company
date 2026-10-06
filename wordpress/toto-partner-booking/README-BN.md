# Toto Company — WordPress বুকিং ব্যবস্থা

এই নতুন প্লাগইন WordPress-এর login এবং shared database ব্যবহার করে। পুরোনো `toto-partner-preview.zip` শুধু browser demo; এটি নতুন `toto-partner-booking.zip`-এর বিকল্প নয়।

## চালু করা

1. নিজের হোস্টিংয়ের WordPress Dashboard → Plugins → Add New → Upload Plugin-এ `toto-partner-booking.zip` ইনস্টল ও Activate করুন।
2. Tools → Toto Booking Setup খুলুন। Activate করলে একটি draft portal page তৈরি হয়; Preview দিয়ে পরীক্ষা করুন। আগে তৈরি করা পেজে ব্যবহার করতে Shortcode ব্লকে `[toto_booking]` লিখুন এবং পেজের template `Toto Booking — Full page` নির্বাচন করুন।
3. কোম্পানির admin হিসেবে পোর্টালে লিওর প্রকাশিত রুম ও ছবি যোগ করুন। হোটেলের নামের পাশে ID দেখা যাবে। Import একবারই হয়; আগে করা পরিবর্তন overwrite করে না।
4. Users → Add New-এ প্রতিটি এজেন্টকে `Toto Booking Agent`, হোটেল কর্তৃপক্ষকে `Toto Hotel Authority` role দিন। WordPress-এর নিজস্ব password reset ব্যবহার করুন।
5. হোটেল কর্তৃপক্ষের User Profile-এ `Assigned hotel IDs` লিখুন (একাধিক হলে কমা দিয়ে)। এজেন্টের User Profile-এ commission percentage দিন। শুধু কোম্পানির admin এই assignment ও commission বদলাতে পারেন।
6. হোটেল কর্তৃপক্ষ ফোন, ঠিকানা, রুম সংখ্যা/ক্ষমতা, রেট, শিশু/খাবার/ট্যাক্স/বাতিলের নীতি যাচাই করে booking enabled checkbox নির্বাচন করবেন। Import করা হোটেলে যাচাইয়ের আগে বুকিং বন্ধ থাকে।
7. আলাদা এজেন্ট ও হোটেল অ্যাকাউন্টে একটি পরীক্ষামূলক অনুরোধ ও অনুমোদন যাচাই করুন। তারপর portal page Publish করে সাইটের মেনুতে দিন। Homepage নিজে থেকে বদলায় না।

নিজের সাইটে আগে database ও files backup নিন। HTTPS রাখুন এবং portal page / logged-in users / REST endpoints page cache থেকে বাদ দিন। WordPress 6.0+, PHP 7.4+, MySQL/MariaDB-তে InnoDB দরকার।

## বুকিং ও হিসাব

- অতিথির নাম/ফোন, চেক-ইন/আউট, রুমের ধরন/সংখ্যা, প্রাপ্তবয়স্ক, শিশুদের বয়স, খাবার, বিশেষ অনুরোধ ও নীতির সম্মতি লাগে।
- সার্ভার প্রতিরাতের দাম, শিশু/খাবার/ট্যাক্স ও agent commission হিসাব করে quote snapshot রাখে। সর্বোচ্চ ৬০ রাত; checkout-এর রাত অন্তর্ভুক্ত নয়।
- অনুরোধ `pending`; রুম ধরে না। শুধু assigned hotel authority বা company admin অনুমোদন করবেন। একই শেষ রুমের দুটি অনুরোধ একসঙ্গে অনুমোদন করলে একটি stock conflict হবে।
- নিশ্চিত বুকিং `cancel_requested` হলে বাতিলের অনুমোদন পর্যন্ত রুম ধরে থাকে। হোটেল বাতিল অনুমোদন করলে রুম ছাড়ে; কারণসহ অনুরোধ প্রত্যাখ্যানও করা যায়।
- হোটেল daily inventory / rate / stop sale বদলাতে পারে। নিশ্চিত বুকিংয়ের নিচে inventory কমানো যায় না। আগে তৈরি quote নিজে থেকে repricing হয় না।
- এজেন্ট কেবল নিজের guest/booking details দেখেন। Hotel authority কেবল assigned hotels-এর booking details দেখেন। Admin সব দেখেন।
- পেমেন্ট ও refund ledger মানে হাতে পাওয়া অর্থ/ব্যাংক রেফারেন্স নথিভুক্ত করা; প্লাগইন টাকা গ্রহণ বা ফেরত পাঠায় না। Confirmed voucher browser print দিয়ে PDF করা যায়। CSV এবং report আপনার অনুমোদিত সর্বশেষ ৫০০ booking-এর জন্য।
- অনুরোধ/অনুমোদন/বাতিল/পেমেন্টের actor ও সময়ের audit trail থাকে। আলাদা account দিয়ে portal খুলে বা “তথ্য আপডেট” চাপলে shared database-এর পরিবর্তন দেখা যায়।

## লিওর সংরক্ষিত তথ্য

১৪টি প্রকাশিত individual room profile, ছয়টি room category এবং ছয়টি original hotel photo রাখা আছে। ছবিগুলি রুমের প্রতিনিধিত্বমূলক ছবি; আলাদা physical room-number assignment এই সংস্করণে নেই। Published count বর্তমান বাস্তব inventory-এর নিশ্চয়তা নয়।

Source BDT rates indicative INR-এ রূপান্তর করা: ২০২৬-১০-০৫, 1 BDT = 0.78428014 INR; 4500 → ₹3529.26, 5000 → ₹3921.40, 5500 → ₹4313.54। হোটেলের নিজস্ব phone প্রকাশিত source-এ নিশ্চিত করা যায়নি, তাই agency phone import হয়নি। বাস্তব terms/rates মালিকই নিশ্চিত করবেন।

## সংরক্ষণ ও বাকি সংযোগ

Bookings, inventory, audit ও ledger WordPress database-এর `*_toto_*` tables-এ থাকে; hotel data WordPress posts/meta-তে এবং account assignment user meta-তে থাকে। Plugin deactivate/delete করলে এই data মুছে ফেলা হয় না। পুরো WordPress database ও uploads-এর hosting backup রাখুন। Browser demo-এর localStorage data এখানে নিজে থেকে import হয় না।

এই repository-তে credentials বা বাস্তব guest data নেই। Payment gateway, email/SMS/WhatsApp delivery, scheduled backups, personalized branding/menus এবং live-host security/cache configuration আলাদা সংযোগের কাজ; এই plugin-এ সেগুলির সংযোগ দাবি করা হচ্ছে না। Guest-data retention/export/deletion ও business policies বাস্তব ব্যবহারের আগে সাইট মালিকের সঙ্গে স্থির করতে হবে।

এই পরিবেশে user's hocab.in-এ WordPress login বা upload access নেই। নিরাপদ WordPress/hosting connection না থাকায় live site update হয়নি। চ্যাটে password পাঠাবেন না। ZIP নিজের dashboard-এর Upload Plugin থেকে ইনস্টল করা যায়।

## যাচাই

আলাদা Docker WordPress/PHP 8.2/MariaDB 11.4-এ activation, InnoDB schema পুনরায় চালু করা, REST permission/privacy checks, quote math, pending/approval/cancellation, inventory guards, ledger/refund এবং simultaneous approval পরীক্ষা করা হয়েছে। Chromium-এ WordPress login, availability, request, owner approval, অন্য account-এ refresh, mobile layout এবং report পরীক্ষা করা হয়েছে। পরীক্ষা আপনার hosting/theme/plugins-এর সামঞ্জস্যের নিশ্চয়তা দেয় না; draft page-এ নিজস্ব installation পরীক্ষা করুন। `../tests`-এ পুনরায় চালানোর integration test রাখা আছে।
