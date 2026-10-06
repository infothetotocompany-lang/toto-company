# নিজের হোস্টিংয়ের WordPress-এ ডেমো

এই প্যাকেজ বর্তমান Toto Partner Network ডেমোকে WordPress পেজে দেখায়। এটি production booking plugin নয়। এজেন্ট/হোটেল/অ্যাডমিন নির্বাচন ডেমোর অংশ; এটি WordPress login বা access control নয়। ডেটা browser localStorage-এ থাকে, shared database-এ নয়। বাস্তব অতিথির তথ্য দেবেন না।

## ইনস্টল

1. WordPress Dashboard → Plugins → Add New Plugin → Upload Plugin খুলুন।
2. `toto-partner-preview.zip` আপলোড করে Install Now → Activate করুন।
3. Pages → Add New খুলে একটি Shortcode ব্লকে `[toto_partner_preview]` লিখুন।
4. Preview দিয়ে পরীক্ষা করুন। তারপর চাইলে পেজ Publish করুন।
5. Dashboard → Tools → Toto Booking Preview থেকেও ডেমো দেখা যায়।

প্লাগইন কোনো পেজ নিজে প্রকাশ করে না বা বর্তমান সাইটের homepage বদলায় না। Uninstall করলে browser data মুছে যায় না। প্যাকেজে হোটেলের ছয়টি ছবি embedded থাকায় আলাদা external image access লাগে না। Host যদি iframe ব্লক করে, “সম্পূর্ণ ডেমো খুলুন” লিংক ব্যবহার করুন।

## বাস্তব বুকিং চালুর পরবর্তী কাজ

WordPress user roles ও hotel/agent ownership, shared booking/inventory tables, authenticated server endpoints, concurrent inventory guards, pending/approval/cancellation transitions, guest-data protection, audit trail, backup, actual pricing/policies এবং notifications তৈরি ও staging-এ যাচাই করতে হবে। এখন কোনো live WordPress access বা site update করা হয়নি।

PHP 7.4+, WordPress 6.0+ লক্ষ্য করা হয়েছে। এই পরিবেশে PHP/WordPress runtime নেই, তাই live activation পরীক্ষা বাকি। আগে staging বা একটি draft পেজে পরীক্ষা করুন। Password চ্যাটে পাঠানোর প্রয়োজন নেই।
