# 🏗️ خريطة شاملة لنظام Super3000 - شرح كامل

---

## 📋 نظرة عامة على المشروع

**Super3000** هو **نظام إدارة متكامل** لموقع ومتجر بيع بالجملة، يجمع بين:

- ✅ **لوحة تحكم ويب (Web Dashboard)** - واجهة رسومية للإدارة
- ✅ **API RESTful** - للتطبيقات الخارجية والموبايل
- ✅ **إدارة المخزون** - تتبع المنتجات والمخزون
- ✅ **إدارة الطلبات والدفع** - نظام تجارة إلكترونية
- ✅ **نظام الأدوار والصلاحيات** - تحكم دقيق للمستخدمين
- ✅ **تعدد اللغات** - عربي وإنجليزي

---

## 🛠️ التكنولوجيا والمتطلبات

### المتطلبات:

```
✓ PHP 8.2+
✓ Composer (مدير الحزم PHP)
✓ Node.js + npm (لأدوات الـ Frontend)
✓ SQLite أو MySQL (قاعدة البيانات)
```

### الحزم الأساسية (Composer):

```json
{
    "laravel/framework": "^12.0", // إطار العمل الأساسي
    "laravel/sanctum": "^4.0", // نظام التوكنات (API)
    "spatie/laravel-permission": "^6.0", // نظام الأدوار والصلاحيات
    "laravel/tinker": "^2.10.1" // أداة تفاعلية للتطوير
}
```

### أدوات الـ Frontend:

- **Vite** - أداة البناء الحديثة
- **Bootstrap 5** - قالب Sneat (Responsive UI)
- **JavaScript** - صفحات تفاعلية

---

## 🗂️ هيكل المشروع

```
super3000-backend/
│
├── 📁 app/                           # منطق التطبيق الأساسي
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/V1/               # 🔌 API Controllers
│   │   │   │   └── AuthController    # • تسجيل دخول / تسجيل خروج
│   │   │   │
│   │   │   └── Admin/                # 🖥️ Dashboard Controllers
│   │   │       ├── DashboardController   # لوحة التحكم الرئيسية
│   │   │       ├── ProductController     # إدارة المنتجات
│   │   │       ├── OrderController       # إدارة الطلبات
│   │   │       ├── PaymentController     # إدارة الدفع
│   │   │       ├── CustomerController    # إدارة العملاء
│   │   │       ├── UserController        # إدارة المستخدمين
│   │   │       ├── WarehouseController   # إدارة المستودعات
│   │   │       ├── ReportController      # التقارير
│   │   │       ├── RoleController        # إدارة الأدوار
│   │   │       ├── SettingsController    # الإعدادات
│   │   │       └── ... (والمزيد)
│   │   │
│   │   ├── Middleware/               # معالجات الطلبات
│   │   ├── Requests/                 # التحقق من البيانات
│   │   └── Resources/                # تنسيق الردود (API)
│   │
│   ├── 📊 Models/                    # النماذج (قاعدة البيانات)
│   │   ├── User.php                  # المستخدم
│   │   ├── Product.php               # المنتج
│   │   ├── Order.php                 # الطلب
│   │   ├── OrderItem.php             # بند الطلب
│   │   ├── Customer.php              # العميل
│   │   ├── Payment.php               # الدفع
│   │   ├── Category.php              # الفئة
│   │   ├── Warehouse.php             # المستودع
│   │   ├── ProductStock.php          # مخزون المنتج
│   │   ├── StockMovement.php         # حركة المخزون
│   │   ├── InventoryMovement.php     # تحريك المخزون
│   │   ├── ActivityLog.php           # سجل الأنشطة
│   │   └── Setting.php               # الإعدادات
│   │
│   ├── 🔔 Notifications/             # إشعارات البريد/SMS
│   │   ├── LowStockAlert.php         # تنبيه المخزون المنخفض
│   │   ├── NewOrderCreated.php       # طلب جديد
│   │   └── OrderStatusChanged.php    # تغيير حالة الطلب
│   │
│   ├── 👁️ Observers/                 # مراقبات الأحداث
│   │   ├── OrderObserver.php         # أحداث الطلب
│   │   └── ProductStockObserver.php  # أحداث المخزون
│   │
│   ├── 🔧 Services/                  # خدمات الأعمال
│   │   └── ReportService.php         # خدمة التقارير
│   │
│   ├── 📝 Support/                   # دوال مساعدة
│   │   └── ActivityLogger.php        # تسجيل الأنشطة
│   │
│   └── Providers/
│       └── AppServiceProvider.php    # تسجيل الخدمات
│
├── 📁 routes/                        # 🔗 روابط التطبيق
│   ├── api.php                       # ✨ روابط الـ API
│   ├── web.php                       # 🌐 روابط الويب (Dashboard)
│   └── console.php                   # سطر الأوامر
│
├── 📁 resources/                     # 📄 موارد الـ Frontend
│   ├── views/                        # صفحات Blade (HTML)
│   │   ├── layouts/
│   │   │   ├── admin.blade.php       # قالب الـ Dashboard
│   │   │   └── blank.blade.php       # قالب بسيط
│   │   └── admin/                    # صفحات الـ Dashboard
│   │       ├── dashboard/
│   │       ├── products/
│   │       ├── orders/
│   │       ├── payments/
│   │       ├── users/
│   │       ├── customers/
│   │       ├── warehouses/
│   │       ├── invoices/
│   │       ├── reports/
│   │       ├── settings/
│   │       └── ...
│   │
│   └── lang/                         # 🗣️ ملفات الترجمة
│       └── ar/, en/                  # عربي وإنجليزي
│
├── 📁 database/                      # 🗄️ قاعدة البيانات
│   ├── migrations/                   # تعريفات الجداول
│   │   ├── create_users_table
│   │   ├── create_products_table
│   │   ├── create_orders_table
│   │   ├── create_payments_table
│   │   ├── create_customers_table
│   │   ├── create_warehouses_table
│   │   ├── create_categories_table
│   │   ├── create_permissions_table
│   │   ├── create_roles_table
│   │   └── ...
│   │
│   ├── factories/                    # بيانات وهمية للاختبار
│   │   ├── UserFactory
│   │   ├── ProductFactory
│   │   ├── OrderFactory
│   │   └── ...
│   │
│   └── seeders/                      # بذور البيانات الأولية
│       ├── AdminUserSeeder           # إنشاء مستخدم أدمن
│       ├── RoleSeeder                # الأدوار الافتراضية
│       ├── ProductSeeder             # منتجات تجريبية
│       └── ...
│
├── 📁 public/                        # 📦 الملفات العامة
│   ├── index.php                     # نقطة الدخول الأساسية
│   ├── admin-dashboard.js            # سكريبت الـ Dashboard
│   ├── theme.css                     # تنسيق الثيم
│   ├── theme.js                      # إدارة Light/Dark Mode
│   ├── sidebar.js                    # تفاعل الـ Sidebar
│   ├── lang.js                       # إدارة اللغة والترجمة
│   ├── i18n/                         # 📚 قوامس الترجمة
│   │   ├── ar.json                   # النصوص العربية
│   │   └── en.json                   # النصوص الإنجليزية
│   └── css/, js/                     # ملفات إضافية
│
├── 📁 config/                        # ⚙️ ملفات الإعدادات
│   ├── app.php                       # معلومات التطبيق
│   ├── database.php                  # إعدادات قاعدة البيانات
│   ├── auth.php                      # إعدادات المصادقة
│   ├── permission.php                # إعدادات Spatie الأدوار
│   ├── mail.php                      # إعدادات البريد
│   ├── cache.php                     # إعدادات الـ Cache
│   └── ...
│
├── 📁 storage/                       # 📥 تخزين مؤقت
│   ├── logs/                         # السجلات
│   └── framework/                    # ملفات Cache
│
├── 📁 tests/                         # 🧪 اختبارات
│   ├── Feature/                      # اختبارات الميزات
│   └── Unit/                         # اختبارات الوحدات
│
├── .env.example                      # 📋 مثال الإعدادات
├── composer.json                     # 📦 حزم PHP
├── package.json                      # 📦 حزم Node.js
├── artisan                           # 🎯 أداة سطر الأوامر
├── phpunit.xml                       # ⚙️ إعدادات الاختبارات
└── README.md                         # 📖 التوثيق
```

---

## 🔗 خريطة الـ Routes والـ API

### 📡 API Routes (في `routes/api.php`)

#### ✨ **نقطة الدخول الأساسية:**

```
Base URL: /api/v1
معيار: RESTful + JSON
```

#### 🔐 **المصادقة (Auth) - بدون توكن:**

```
POST   /api/v1/auth/login
├─ الوصف: تسجيل الدخول
├─ البيانات المطلوبة:
│  ├─ email: البريد الإلكتروني
│  ├─ password: كلمة المرور
│  └─ device_name: [اختياري] اسم الجهاز
├─ الاستجابة:
│  └─ access_token: توكن المصادقة (Bearer Token)
│
└─ مثال CURL:
   curl -X POST http://localhost:8000/api/v1/auth/login \
     -H "Content-Type: application/json" \
     -d '{
       "email": "admin@example.com",
       "password": "password123",
       "device_name": "Mobile App"
     }'

   الاستجابة:
   {
     "data": {
       "token_type": "Bearer",
       "access_token": "eyJhbGc...",
       "user": {
         "id": 1,
         "email": "admin@example.com"
       }
     }
   }
```

#### 🔐 **المصادقة - مع توكن (معروف باسم Sanctum):**

```
POST   /api/v1/auth/logout
├─ الوصف: تسجيل الخروج
├─ معيار: must be authorized
├─ Headers مطلوبة:
│  └─ Authorization: Bearer {TOKEN}
├─ الاستجابة:
│  └─ { "data": { "message": "Logged out successfully." } }
│
└─ مثال CURL:
   curl -X POST http://localhost:8000/api/v1/auth/logout \
     -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

---

### 🌐 Web Routes (في `routes/web.php`)

#### **الهيكل العام:**

```
Base Path: /{locale}/admin
locale: 'ar' أو 'en'
```

#### 📊 **لوحة التحكم الرئيسية:**

```
GET    /{locale}/admin/dashboard
└─ الصفحة الرئيسية مع إحصائيات عامة
```

#### 📦 **إدارة المنتجات والكتالوج:**

```
GET    /{locale}/admin/catalog/products              # قائمة المنتجات
POST   /{locale}/admin/catalog/products              # إنشاء منتج جديد
GET    /{locale}/admin/catalog/products/{id}/edit    # تعديل منتج
PUT    /{locale}/admin/catalog/products/{id}         # حفظ التعديلات
DELETE /{locale}/admin/catalog/products/{id}         # حذف منتج
GET    /{locale}/admin/catalog/inventory             # إدارة المخزون
POST   /{locale}/admin/catalog/inventory             # إضافة حركة مخزون
GET    /{locale}/admin/catalog/categories            # الفئات
POST   /{locale}/admin/catalog/categories            # إنشاء فئة
```

#### 🛒 **إدارة المبيعات والطلبات:**

```
GET    /{locale}/admin/sales/orders                  # قائمة الطلبات
GET    /{locale}/admin/sales/orders/{id}             # تفاصيل الطلب
PATCH  /{locale}/admin/sales/orders/{id}/status     # تحديث حالة الطلب
GET    /{locale}/admin/sales/customers               # قائمة العملاء
POST   /{locale}/admin/sales/customers               # إنشاء عميل جديد
GET    /{locale}/admin/sales/payments                # سجل الدفع
GET    /{locale}/admin/invoices                      # الفواتير
GET    /{locale}/admin/invoices/{order}/print        # طباعة الفاتورة
```

#### 🏢 **إدارة العمليات والمستودعات:**

```
GET    /{locale}/admin/operations/warehouses         # المستودعات
POST   /{locale}/admin/operations/warehouses         # إنشاء مستودع جديد
GET    /{locale}/admin/operations/reports            # التقارير
```

#### 👥 **إدارة الأمان والمستخدمين:**

```
GET    /{locale}/admin/security/roles                # الأدوار
GET    /{locale}/admin/security/roles/{id}/edit      # تعديل دور
PUT    /{locale}/admin/security/roles/{id}           # حفظ الدور
GET    /{locale}/admin/security/users                # المستخدمون
POST   /{locale}/admin/security/users                # إنشاء مستخدم جديد
GET    /{locale}/admin/security/users/{id}/edit      # تعديل المستخدم
PUT    /{locale}/admin/security/users/{id}           # حفظ المستخدم
GET    /{locale}/admin/security/activity-logs        # سجل الأنشطة
```

#### ⚙️ **الإعدادات:**

```
GET    /{locale}/admin/settings/general              # الإعدادات العامة
PUT    /{locale}/admin/settings/general              # حفظ الإعدادات العامة
GET    /{locale}/admin/settings/appearance           # إعدادات المظهر
PUT    /{locale}/admin/settings/appearance           # حفظ إعدادات المظهر
```

#### 👤 **البروفايل والإشعارات:**

```
GET    /{locale}/admin/myprofile                     # البروفايل الشخصي
GET    /{locale}/admin/notifications                 # الإشعارات
```

#### 🚪 **الخروج:**

```
POST   /{locale}/admin/logout
└─ تسجيل الخروج وتنضيف الجلسة
```

---

## 📊 خريطة البيانات والـ Models

### 🔗 العلاقات بين النماذج:

```
┌─────────────┐
│   Users     │ (المستخدمون)
│ (id, name,  │
│  email, ..) │
└─────────────┘
      │
      ├──────── HasMany ────────→ Orders (الطلبات)
      ├──────── HasRoles ──────→ Roles (الأدوار)
      └──────── HasPermissions → Permissions (الصلاحيات)


┌──────────────┐
│  Customers   │ (العملاء)
│ (id, name,   │
│  email, ..)  │
└──────────────┘
      │
      └──────── HasMany ───────→ Orders (الطلبات)


┌──────────────┐
│   Orders     │ (الطلبات)
│ (id, order_  │
│  no, status) │
└──────────────┘
      │
      ├──────── HasMany ───────────→ OrderItems (بنود الطلب)
      ├──────── HasMany ───────────→ Payments (الدفع)
      └──────── BelongsTo ────────→ Customers (العملاء)


┌──────────────────┐
│   OrderItems     │ (بنود الطلب)
│ (id, order_id,   │
│  product_id, ..) │
└──────────────────┘
      │
      └──────── BelongsTo ─────→ Products (المنتجات)


┌──────────────┐
│  Products    │ (المنتجات)
│ (id, name,   │
│  price, sku) │
└──────────────┘
      │
      ├──────── HasMany ──────→ ProductStocks (مخزون المنتج)
      ├──────── HasMany ──────→ OrderItems (بنود الطلب)
      └──────── BelongsTo ───→ Categories (الفئات)


┌──────────────────┐
│ ProductStocks    │ (مخزون المنتج)
│ (id, product_id, │
│  warehouse_id,..)│
└──────────────────┘
      │
      ├──────── BelongsTo ──→ Products (المنتجات)
      └──────── BelongsTo ──→ Warehouses (المستودعات)


┌──────────────┐
│ Warehouses   │ (المستودعات)
│ (id, name,   │
│  location)   │
└──────────────┘
      │
      └──────── HasMany ───→ ProductStocks (مخزون)


┌──────────────┐
│ Categories   │ (الفئات)
│ (id, name,   │
│  description)│
└──────────────┘
      │
      └──────── HasMany ───→ Products (المنتجات)


┌──────────────┐
│  Payments    │ (الدفع)
│ (id, order_  │
│  id, amount) │
└──────────────┘
      │
      └──────── BelongsTo ──→ Orders (الطلبات)


┌──────────────────────┐
│  StockMovements      │ (حركة المخزون)
│ (id, product_id,     │
│  warehouse_id, type) │
└──────────────────────┘
      │
      ├──────── BelongsTo ──→ Products (المنتجات)
      └──────── BelongsTo ──→ Warehouses (المستودعات)


┌──────────────────────────┐
│  InventoryMovements      │ (تحريك المخزون)
│ (id, from_warehouse,     │
│  to_warehouse, ..)       │
└──────────────────────────┘
      │
      └──────── عمليات نقل مخزون بين المستودعات


┌──────────────┐
│  ActivityLog │ (سجل الأنشطة)
│ (id, user_id,│
│  action, ..) │
└──────────────┘
      │
      ├──────── BelongsTo ──→ Users (المستخدمون)
      └──────── تسجيل جميع الأنشطة والتغييرات
```

---

## 🔄 سير العمل والـ Data Flow

### 1️⃣ **عملية تسجيل الدخول:**

```
User (عميل الاتصال)
    │
    ├─→ POST /api/v1/auth/login
    │        (email, password, device_name)
    │
    ├─→ AuthController::login()
    │        ├─ التحقق من صحة البيانات
    │        ├─ البحث عن User بـ email
    │        ├─ التحقق من كلمة المرور (bcrypt)
    │        ├─ إنشاء Token عبر Sanctum
    │        └─ إرجاع Access Token
    │
    └─→ {token_type, access_token, user}
         ⬅ يتم حفظ التوكن في التطبيق للطلبات القادمة
```

### 2️⃣ **عملية عرض قائمة المنتجات:**

```
Admin User (مسجل دخول)
    │
    ├─→ GET /{locale}/admin/catalog/products
    │
    ├─→ ProductController::index()
    │        ├─ التحقق من الصلاحيات (middleware)
    │        ├─ جلب المنتجات من DB
    │        │   └─ SELECT * FROM products
    │        ├─ حساب الإجمالي والإحصائيات
    │        └─ تحميل صفحة Blade
    │
    └─→ products/index.blade.php
         └─ عرض الجدول + الأزرار (Edit/Delete)
```

### 3️⃣ **عملية إنشاء طلب جديد:**

```
Admin User
    │
    ├─→ POST /{locale}/admin/sales/orders
    │        (customer_id, items: [{product_id, qty}])
    │
    ├─→ OrderController::store()
    │        ├─ التحقق من صحة البيانات
    │        ├─ الحفظ في DB:
    │        │   ├─ INSERT INTO orders (...)
    │        │   └─ INSERT INTO order_items (...)
    │        ├─ تحديث مخزون المنتجات
    │        ├─ حساب الإجمالي
    │        └─ تشغيل Event: OrderCreated
    │
    ├─→ OrderObserver يستقبل الحدث
    │        ├─ إرسال إشعار للعميل
    │        └─ تسجيل النشاط في ActivityLog
    │
    └─→ redirect to orders.show (نجح)
         └─ عرض تفاصيل الطلب مع الرسالة
```

### 4️⃣ **عملية تحديث حالة الطلب:**

```
Admin User
    │
    ├─→ PATCH /{locale}/admin/sales/orders/{id}/status
    │        (status: 'paid', 'shipped', 'cancelled')
    │
    ├─→ OrderController::updateStatus()
    │        ├─ التحقق من الصلاحيات
    │        ├─ تحديث Order في DB:
    │        │   └─ UPDATE orders SET status = ? WHERE id = ?
    │        ├─ تشغيل Event: OrderStatusChanged
    │        └─ تسجيل النشاط
    │
    ├─→ OrderObserver يستقبل الحدث
    │        ├─ إرسال إشعار للعميل بالحالة الجديدة
    │        └─ تحديث ActivityLog
    │
    └─→ JSON Response: {message: "success"}
```

### 5️⃣ **عملية إدارة المخزون:**

```
Admin User
    │
    ├─→ POST /{locale}/admin/catalog/inventory
    │        (product_id, warehouse_id, qty, type: 'in'/'out')
    │
    ├─→ InventoryController::store()
    │        ├─ التحقق من صحة البيانات
    │        ├─ الحفظ في DB:
    │        │   ├─ INSERT INTO stock_movements (...)
    │        │   └─ UPDATE product_stocks SET qty = qty ± ?
    │        ├─ التحقق من المخزون المنخفض
    │        └─ تشغيل Event
    │
    ├─→ ProductStockObserver يستقبل الحدث
    │        ├─ إذا (qty < min_level):
    │        │   └─ إرسال LowStockAlert Notification
    │        └─ تسجيل النشاط
    │
    └─→ redirect back with success
```

---

## 🔐 نظام الأدوار والصلاحيات

### استخدام Spatie Permission:

```php
// التحقق من الصلاحيات في Controllers:
$this->authorize('product.edit', $product);
// أو
if (!auth()->user()->can('product.edit')) {
    // رفض الدخول
}

// الأدوار المتاحة (يمكن تخصيصها):
- Admin         // مستخدم كامل الصلاحيات
- Manager       // مدير المبيعات
- Accountant    // محاسب
- Warehouse     // أمين المستودع
- Viewer        // عارض فقط بدون تعديل
```

---

## 💾 قاعدة البيانات - الجداول الرئيسية

### جدول `users`

```sql
id              INT (PK)
name            VARCHAR(255)
email           VARCHAR(255) UNIQUE
password        VARCHAR(255) [مشفرة]
email_verified_at TIMESTAMP [اختياري]
remember_token  VARCHAR(100) [اختياري]
created_at      TIMESTAMP
updated_at      TIMESTAMP
deleted_at      TIMESTAMP [Soft Delete]
```

### جدول `products`

```sql
id              INT (PK)
name            VARCHAR(255)
sku             VARCHAR(100) UNIQUE
price           DECIMAL(10, 2)
stock_qty       INT (قديم - استخدم product_stocks بدلاً منه)
is_active       BOOLEAN
category_id     INT (FK)
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

### جدول `orders`

```sql
id              INT (PK)
order_no        VARCHAR(50) UNIQUE
customer_id     INT (FK)
status          ENUM('pending', 'paid', 'shipped', 'cancelled')
subtotal        DECIMAL(10, 2)
total           DECIMAL(10, 2)
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

### جدول `order_items`

```sql
id              INT (PK)
order_id        INT (FK)
product_id      INT (FK)
quantity        INT
unit_price      DECIMAL(10, 2)
line_total      DECIMAL(10, 2)
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

### جدول `customers`

```sql
id              INT (PK)
name            VARCHAR(255)
email           VARCHAR(255)
phone           VARCHAR(20)
address         TEXT
city            VARCHAR(100)
country         VARCHAR(100)
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

### جدول `warehouses`

```sql
id              INT (PK)
name            VARCHAR(255)
location        VARCHAR(255)
capacity        INT
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

### جدول `product_stocks`

```sql
id              INT (PK)
product_id      INT (FK)
warehouse_id    INT (FK)
quantity        INT
min_level       INT (تنبيه عند الوصول)
max_level       INT
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

### جدول `roles` (Spatie)

```sql
id              INT (PK)
name            VARCHAR(255)
guard_name      VARCHAR(255) [web, api, sanctum]
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

### جدول `permissions` (Spatie)

```sql
id              INT (PK)
name            VARCHAR(255) [مثل: product.create, product.edit]
guard_name      VARCHAR(255)
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

### جدول `activity_log`

```sql
id              INT (PK)
user_id         INT (FK)
action          VARCHAR(255) [create, update, delete]
subject_type    VARCHAR(255) [Product, Order, User]
subject_id      INT
old_values      JSON [القيم القديمة]
new_values      JSON [القيم الجديدة]
created_at      TIMESTAMP
```

---

## 🚀 بدء المشروع

### 1. التثبيت والإعداد:

```bash
# استنساخ المشروع
git clone <repo-url>
cd super3000-backend

# نسخ ملف الإعدادات
cp .env.example .env

# تثبيت PHP packages
composer install

# توليد مفتاح التطبيق
php artisan key:generate

# إنشاء قاعدة البيانات والجداول
php artisan migrate

# إدراج بيانات أولية (مستخدمين افتراضيين، إلخ)
php artisan db:seed

# تثبيت Node packages
npm install

# بناء الـ Frontend
npm run dev  # للتطوير
npm run build # للإنتاج
```

### 2. تشغيل المشروع:

```bash
# تشغيل السيرفر (يعمل على port 8000)
php artisan serve

# في ترمينال آخر: تشغيل Build tools
npm run dev

# اختياري: استماع للـ Queue
php artisan queue:listen
```

### 3. الوصول إلى الموقع:

```
Dashboard: http://localhost:8000/ar/admin
           (أو /en/admin للإنجليزية)
API Base:  http://localhost:8000/api/v1
```

---

## 📝 ملخص المتطلبات المهمة

| المتطلب           | الوصف              | الملف                   |
| ----------------- | ------------------ | ----------------------- |
| PHP 8.2+          | لغة البرمجة        | `php`                   |
| Composer          | مدير حزم PHP       | `composer.json`         |
| Laravel 12        | إطار العمل         | `composer.json`         |
| Sanctum           | توكنات API         | `config/auth.php`       |
| Spatie Permission | الأدوار والصلاحيات | `config/permission.php` |
| Node.js           | بيئة JavaScript    | `package.json`          |
| Vite              | أداة البناء        | `package.json`          |
| Bootstrap 5       | قالب CSS           | `public/`               |
| SQLite/MySQL      | قاعدة البيانات     | `.env` DB_CONNECTION    |
| Redis (اختياري)   | كـ cache وqueues   | `config/cache.php`      |

---

## 🔍 نقاط مهمة للمطورين

1. **Locale Prefix**: جميع Web routes مبدوءة بـ `/{locale}` (ar/en)
2. **Authentication**: استخدم `auth:sanctum` middleware للـ API و `auth:web` للـ Web
3. **Authorization**: استخدم Policies و `@can` في Blade أو `$this->authorize()` في Controllers
4. **Activity Logging**: كل تعديل يُسجل في `activity_log` تلقائياً
5. **Events & Observers**: استخدم Model Events لتشغيل منطق معقد
6. **Notifications**: البريد والـ SMS متاح عبر Notifications
7. **Queue**: المهام الثقيلة تُرسل للـ Queue (ترسل البريد، إنشاء التقارير، إلخ)
8. **Testing**: اكتب اختبارات في `tests/` والتشغيل عبر `php artisan test`

---

## 📊 رسم توضيحي: Request Lifecycle

```
┌─────────────────────────────────────────────────────────────────┐
│                    Client (Web Browser / API)                    │
└────────────────┬────────────────────────────────────────────────┘
                 │ HTTP Request
                 ▼
┌─────────────────────────────────────────────────────────────────┐
│                   public/index.php (Entry Point)                │
│  ✓ Loads Laravel Kernel                                         │
│  ✓ Bootstraps the Application                                   │
└────────────────┬────────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────────┐
│                    Route Dispatcher                              │
│  ✓ Matches request method + path to a route                     │
│  ✓ Extracts parameters ({locale}, {id}, etc)                    │
└────────────────┬────────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────────┐
│                 Global Middleware Stack                          │
│  ✓ SetLocaleMiddleware (sets app locale)                        │
│  ✓ EncryptCookies, TrimStrings, etc                             │
└────────────────┬────────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────────┐
│              Route-Specific Middleware                           │
│  ✓ auth:sanctum / auth:web (authentication)                     │
│  ✓ throttle (rate limiting)                                     │
│  ✓ Custom middleware                                            │
└────────────────┬────────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────────┐
│                   Controller Action                              │
│  ✓ Receives validated request                                   │
│  ✓ Queries Models (Database)                                    │
│  ✓ Business Logic (Services)                                    │
│  ✓ Returns Response (JSON / Blade View)                         │
└────────────────┬────────────────────────────────────────────────┘
                 │
                 ▼ (Optional)
┌─────────────────────────────────────────────────────────────────┐
│                 Model Observers / Events                         │
│  ✓ Hooks into create, update, delete operations                 │
│  ✓ Triggers Notifications, Activity Logging, etc                │
└────────────────┬────────────────────────────────────────────────┘
                 │
                 ▼ (Optional)
┌─────────────────────────────────────────────────────────────────┐
│                    Job Queue (Redis/DB)                          │
│  ✓ Dispatches async jobs (email, reports)                       │
│  ✓ Runs independently from HTTP request                         │
└────────────────┬────────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────────────────────┐
│                  Response Formatter                              │
│  ✓ JSON API Resource (if API)                                   │
│  ✓ Blade Template (if Web)                                      │
│  ✓ Error Handler (if exception)                                 │
└────────────────┬────────────────────────────────────────────────┘
                 │ HTTP Response
                 ▼
┌─────────────────────────────────────────────────────────────────┐
│              Browser / Mobile App displays result                │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🎯 الخلاصة

**Super3000** نظام متكامل يجمع بين:

- ✅ **Dashboard Web** لإدارة كاملة للموقع والمخزون والطلبات
- ✅ **API RESTful** للتطبيقات الخارجية والموبايل
- ✅ **نظام Roles & Permissions** للتحكم الدقيق بالصلاحيات
- ✅ **تعدد اللغات** (عربي وإنجليزي)
- ✅ **تتبع الأنشطة** لكل عملية في النظام
- ✅ **إدارة مخزون متقدمة** مع تنبيهات المخزون المنخفض
- ✅ **نظام الدفع والفواتير** المتكامل

جميع الأجزاء مترابطة وتعمل بتناسق باستخدام أفضل الممارسات في Laravel!

---

📚 **للمزيد من المعلومات:**

- [Laravel Documentation](https://laravel.com/docs)
- [Laravel Sanctum](https://laravel.com/docs/sanctum)
- [Spatie Permission](https://spatie.be/docs/laravel-permission/v6)
