# Project Map — Super3000 Admin Dashboard

## Structure
- `resources/views/layouts/admin.blade.php`
  - Layout الأساسي للـ Admin (Navbar/Sidebar/Footer).
- `resources/views/layouts/blank.blade.php`
  - Layout مبسط لصفحات خاصة (مثل الطباعة).
- `resources/views/admin/**`
  - صفحات الـ Admin (Dashboard/Products/Orders/Invoices/Users/Notifications...).
- `routes/web.php`
  - جميع المسارات تحت `/{locale}/admin`.
- `public/`
  - ملفات JS/CSS المخصصة للواجهة.
- `public/sneat-bootstrap-html-admin-template-free/`
  - قالب Sneat الأصلي (مصدر UI).

## Files We Modify (UI/Behavior)
- `resources/views/layouts/admin.blade.php`
  - أيقونات اللغة/الثيم/الإشعارات، Sidebar order، My Profile link.
- `resources/views/admin/notifications/index.blade.php`
  - صفحة الإشعارات.
- `resources/views/admin/myprofile/index.blade.php`
  - صفحة البروفايل (جاهزة للربط).
- `routes/web.php`
  - Routes للصفحات الجديدة.
- `public/theme.css`
  - Dark mode palette (strict).
- `public/ui.js`
  - تفاعلات UI (إشعارات، أحداث عامة).
- `public/lang.js`
  - إدارة اللغة + RTL + ترجمة فورية.
- `public/theme.js`
  - إدارة الثيم (Light/Dark/System).
- `public/sidebar.js`
  - Hover/Pin/Collapse للسـايدبار.
- `public/i18n/ar.json` + `public/i18n/en.json`
  - قاموس الترجمة للواجهة.

## Responsibilities
- **layout**: هيكلة الواجهة + أماكن ربط الـ JS/CSS.
- **lang.js**: يغيّر النصوص فورًا ويخزن اللغة في `localStorage`.
- **theme.js**: يطبق الثيم ويخزن الاختيار في `localStorage`.
- **sidebar.js**: يفتح/يغلق السايدبار ويحفظ الحالة.
- **ui.js**: يربط الأحداث (مثل Mark all notifications).
