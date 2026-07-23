<?php

namespace Database\Seeders;

use App\Models\Translation;
use Illuminate\Database\Seeder;

class TranslationSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->rows() as $key => $row) {
            $group = $row['group'];
            foreach (['en', 'bn', 'ar', 'hi'] as $locale) {
                if (isset($row[$locale])) {
                    Translation::set($key, $locale, $row[$locale], $group);
                }
            }
        }
    }

    private function rows(): array
    {
        return [
            // Header / top bar
            'header.welcome_default' => ['group' => 'header', 'en' => 'Welcome to :site', 'bn' => ':site এ স্বাগতম', 'ar' => 'مرحبا بكم في :site', 'hi' => ':site में आपका स्वागत है'],
            'header.sell_on'         => ['group' => 'header', 'en' => 'Sell on :site', 'bn' => ':site এ বিক্রি করুন', 'ar' => 'بيع على :site', 'hi' => ':site पर बेचें'],
            'header.track_order'     => ['group' => 'header', 'en' => 'Track Order', 'bn' => 'অর্ডার ট্র্যাক করুন', 'ar' => 'تتبع الطلب', 'hi' => 'ऑर्डर ट्रैक करें'],
            'header.help_center'     => ['group' => 'header', 'en' => 'Help Center', 'bn' => 'সহায়তা কেন্দ্র', 'ar' => 'مركز المساعدة', 'hi' => 'सहायता केंद्र'],
            'header.search_placeholder' => ['group' => 'header', 'en' => 'Search in :site', 'bn' => ':site এ খুঁজুন', 'ar' => 'ابحث في :site', 'hi' => ':site में खोजें'],
            'header.see_all_results' => ['group' => 'header', 'en' => 'See all results for', 'bn' => 'সকল ফলাফল দেখুন', 'ar' => 'عرض جميع النتائج لـ', 'hi' => 'सभी परिणाम देखें'],
            'header.categories'      => ['group' => 'header', 'en' => 'Categories', 'bn' => 'ক্যাটাগরি', 'ar' => 'الفئات', 'hi' => 'श्रेणियाँ'],
            'header.products'        => ['group' => 'header', 'en' => 'Products', 'bn' => 'পণ্যসমূহ', 'ar' => 'المنتجات', 'hi' => 'उत्पाद'],
            'header.login'           => ['group' => 'header', 'en' => 'Login', 'bn' => 'লগইন', 'ar' => 'تسجيل الدخول', 'hi' => 'लॉग इन करें'],
            'header.signup'          => ['group' => 'header', 'en' => 'Sign Up', 'bn' => 'সাইন আপ', 'ar' => 'إنشاء حساب', 'hi' => 'साइन अप करें'],
            'header.my_account'      => ['group' => 'header', 'en' => 'My Account', 'bn' => 'আমার একাউন্ট', 'ar' => 'حسابي', 'hi' => 'मेरा खाता'],
            'header.my_orders'       => ['group' => 'header', 'en' => 'My Orders', 'bn' => 'আমার অর্ডার', 'ar' => 'طلباتي', 'hi' => 'मेरे ऑर्डर'],
            'header.wishlist'        => ['group' => 'header', 'en' => 'Wishlist', 'bn' => 'উইশলিস্ট', 'ar' => 'المفضلة', 'hi' => 'इच्छा-सूची'],
            'header.admin_panel'     => ['group' => 'header', 'en' => 'Admin Panel', 'bn' => 'এডমিন প্যানেল', 'ar' => 'لوحة التحكم', 'hi' => 'एडमिन पैनल'],
            'header.logout'          => ['group' => 'header', 'en' => 'Logout', 'bn' => 'লগআউট', 'ar' => 'تسجيل الخروج', 'hi' => 'लॉग आउट'],
            'header.home'            => ['group' => 'header', 'en' => 'Home', 'bn' => 'হোম', 'ar' => 'الرئيسية', 'hi' => 'होम'],
            'header.shop_all'        => ['group' => 'header', 'en' => 'Shop All', 'bn' => 'সকল পণ্য', 'ar' => 'تسوق الكل', 'hi' => 'सभी खरीदें'],
            'header.view_all_categories' => ['group' => 'header', 'en' => 'View All Categories', 'bn' => 'সকল ক্যাটাগরি দেখুন', 'ar' => 'عرض جميع الفئات', 'hi' => 'सभी श्रेणियाँ देखें'],
            'header.all_products'    => ['group' => 'header', 'en' => 'All Products', 'bn' => 'সকল পণ্য', 'ar' => 'جميع المنتجات', 'hi' => 'सभी उत्पाद'],

            // Footer
            'footer.newsletter_title'    => ['group' => 'footer', 'en' => 'Subscribe to Our Newsletter', 'bn' => 'আমাদের নিউজলেটার সাবস্ক্রাইব করুন', 'ar' => 'اشترك في نشرتنا الإخبارية', 'hi' => 'हमारे न्यूज़लेटर की सदस्यता लें'],
            'footer.newsletter_subtitle' => ['group' => 'footer', 'en' => 'Get updates on new arrivals, deals, and exclusive offers.', 'bn' => 'নতুন পণ্য, ডিল এবং বিশেষ অফার সম্পর্কে আপডেট পান।', 'ar' => 'احصل على تحديثات حول المنتجات الجديدة والعروض الحصرية.', 'hi' => 'नए उत्पादों, डील और खास ऑफर की जानकारी पाएं।'],
            'footer.subscribe'           => ['group' => 'footer', 'en' => 'Subscribe', 'bn' => 'সাবস্ক্রাইব করুন', 'ar' => 'اشتراك', 'hi' => 'सदस्यता लें'],
            'footer.email_placeholder'   => ['group' => 'footer', 'en' => 'Enter your email', 'bn' => 'আপনার ইমেইল লিখুন', 'ar' => 'أدخل بريدك الإلكتروني', 'hi' => 'अपना ईमेल दर्ज करें'],
            'footer.we_accept'           => ['group' => 'footer', 'en' => 'We accept:', 'bn' => 'আমরা গ্রহণ করি:', 'ar' => 'نقبل:', 'hi' => 'हम स्वीकार करते हैं:'],
            'footer.quick_links_default' => ['group' => 'footer', 'en' => 'Quick Links', 'bn' => 'দ্রুত লিংক', 'ar' => 'روابط سريعة', 'hi' => 'त्वरित लिंक'],
            'footer.customer_service_default' => ['group' => 'footer', 'en' => 'Customer Service', 'bn' => 'গ্রাহক সেবা', 'ar' => 'خدمة العملاء', 'hi' => 'ग्राहक सेवा'],
            'footer.contact_default'    => ['group' => 'footer', 'en' => 'Contact', 'bn' => 'যোগাযোগ', 'ar' => 'اتصل بنا', 'hi' => 'संपर्क करें'],
            'footer.home'               => ['group' => 'footer', 'en' => 'Home', 'bn' => 'হোম', 'ar' => 'الرئيسية', 'hi' => 'होम'],
            'footer.shop'               => ['group' => 'footer', 'en' => 'Shop', 'bn' => 'শপ', 'ar' => 'المتجر', 'hi' => 'दुकान'],
            'footer.blog'               => ['group' => 'footer', 'en' => 'Blog', 'bn' => 'ব্লগ', 'ar' => 'المدونة', 'hi' => 'ब्लॉग'],
            'footer.sell_on'            => ['group' => 'footer', 'en' => 'Sell on :site', 'bn' => ':site এ বিক্রি করুন', 'ar' => 'بيع على :site', 'hi' => ':site पर बेचें'],
            'footer.contact_us'         => ['group' => 'footer', 'en' => 'Contact Us', 'bn' => 'যোগাযোগ করুন', 'ar' => 'اتصل بنا', 'hi' => 'हमसे संपर्क करें'],
            'footer.faq'                => ['group' => 'footer', 'en' => 'FAQ', 'bn' => 'সাধারণ জিজ্ঞাসা', 'ar' => 'الأسئلة الشائعة', 'hi' => 'सामान्य प्रश्न'],
            'footer.track_order'        => ['group' => 'footer', 'en' => 'Track Order', 'bn' => 'অর্ডার ট্র্যাক করুন', 'ar' => 'تتبع الطلب', 'hi' => 'ऑर्डर ट्रैक करें'],
            'footer.return_policy'      => ['group' => 'footer', 'en' => 'Return Policy', 'bn' => 'রিটার্ন নীতি', 'ar' => 'سياسة الإرجاع', 'hi' => 'वापसी नीति'],
            'footer.about_us'           => ['group' => 'footer', 'en' => 'About Us', 'bn' => 'আমাদের সম্পর্কে', 'ar' => 'من نحن', 'hi' => 'हमारे बारे में'],
            'footer.terms'              => ['group' => 'footer', 'en' => 'Terms & Conditions', 'bn' => 'শর্তাবলী', 'ar' => 'الشروط والأحكام', 'hi' => 'नियम और शर्तें'],
            'footer.privacy'            => ['group' => 'footer', 'en' => 'Privacy Policy', 'bn' => 'গোপনীয়তা নীতি', 'ar' => 'سياسة الخصوصية', 'hi' => 'गोपनीयता नीति'],
            'footer.copyright_default'  => ['group' => 'footer', 'en' => '© :year :site. All rights reserved.', 'bn' => '© :year :site. সর্বস্বত্ব সংরক্ষিত।', 'ar' => '© :year :site. جميع الحقوق محفوظة.', 'hi' => '© :year :site. सर्वाधिकार सुरक्षित।'],
        ];
    }
}
