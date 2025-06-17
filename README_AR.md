# إطار عمل PHP المصغر "microR"

## نظرة عامة

`microR.php` هو إطار عمل PHP مصغر في ملف واحد، ويشمل:
- الأمان (XSS، CSRF، CORS، HTTPS)
- ORM بسيط وآمن
- التحكم في الوصول حسب الدور (ACL)
- التوجيه البسيط (مع دعم المسارات البرمجية)
- قوالب مع حماية XSS
- التحميل التلقائي للفئات

---

## 1. الأمان

### XSS
جميع المتغيرات المُمررة إلى القوالب عبر `render` يتم تهريبها تلقائيًا.

```php
$safe = MicroR::sanitize($input);
```

### CSRF
توليد رمز للتحقق من صحة النماذج وفحصه عند الإرسال.

```php
$token = MicroR::csrfToken(); // يوضع في حقل مخفي
if (!MicroR::checkCsrf($_POST['csrf_token'])) die('CSRF!');
```

### CORS
تفعيل رؤوس CORS لواجهات البرمجة.

```php
MicroR::enableCORS(['https://my-domain.com']);
```

### فرض HTTPS
إعادة التوجيه تلقائيًا إلى HTTPS.

```php
MicroR::forceHTTPS();
```

---

## 2. ORM بسيط

### الاتصال
```php
$mf = new MicroR('mysql:host=localhost;dbname=test', 'root', '');
```

### استعلام SELECT
```php
$users = $mf->find('users', ['id' => 1]);
```

### إدراج
```php
$mf->save('users', ['name' => 'Alice', 'email' => 'alice@mail.com']);
```

### بحث نصي
```php
$users = $mf->search('users', ['name' => 'ali']);
```

---

## 3. التحكم في الوصول (ACL)

### تعريف دور وصلاحية
```php
$mf->addRole('admin');
$mf->allow('admin', 'edit');
```

### التحقق من الصلاحية
```php
if ($mf->isAllowed('admin', 'edit')) { /* ... */ }
```

---

## 4. التوجيه

### تعريف مسار بسيط
```php
MicroR::route('GET', '/home', function() {
    echo 'مرحبًا!';
});
```

### تعريف مسار برمجي
```php
MicroR::route('GET', '/user/{id}', function($id) {
    echo "ملف المستخدم #$id";
});
```

### تنفيذ التوجيه
```php
MicroR::dispatch();
```

---

## 5. القوالب

### عرض قالب مع متغيرات محمية من XSS
```php
MicroR::render('template.html', ['user' => $users[0]->name, 'csrf' => MicroR::csrfToken()]);
```

في القالب HTML:
```html
<h1>مرحبًا <?= $user ?></h1>
<input type="hidden" name="csrf_token" value="<?= $csrf ?>">
```

---

## 6. التحميل التلقائي للفئات

### تحميل جميع الفئات من مجلد
```php
MicroR::loadClasses(__DIR__ . '/myClassesFolder');
```

---

## مثال كامل

**index.php**
```php
require 'microR.php';

MicroR::forceHTTPS();
MicroR::enableCORS(['*']);

MicroR::route('GET', '/', function() {
    MicroR::render('template.html', [
        'user' => 'العالم',
        'csrf' => MicroR::csrfToken()
    ]);
});

MicroR::route('GET', '/user/{id}', function($id) {
    echo "ملف المستخدم #$id";
});

MicroR::dispatch();
```

**template.html**
```html
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>الرئيسية</title>
</head>
<body>
    <h1>مرحبًا <?= $user ?></h1>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <button type="submit">إرسال</button>
    </form>
</body>
</html>
```

---

## ملاحظات
- جميع المتغيرات في القوالب محمية تلقائيًا من هجمات XSS.
- يجب التحقق من رمز CSRF عند إرسال النماذج.
- التوجيه يسمح بإنشاء تطبيقات ويب بسيطة وآمنة، مع دعم المسارات البرمجية.
- ORM محمي من حقن SQL عبر التحقق من أسماء الجداول والحقول واستخدام الاستعلامات المحضرة.

---

لأي سؤال أو تطوير إضافي، راجع الكود المصدري أو اطلب المساعدة!
