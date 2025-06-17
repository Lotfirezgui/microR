# PHP Micro-framework "microR"

## Overview

`microR.php` is a single-file PHP micro-framework, featuring:
- Security (XSS, CSRF, CORS, HTTPS)
- Minimal secure ORM
- ACL (role-based access control)
- Simple routing (with parameterized routes)
- Templating with XSS protection
- Class autoloading

---

## 1. Security

### XSS
All variables injected into templates via `render` are automatically escaped.

```php
$safe = MicroR::sanitize($input);
```

### CSRF
Generates a token to place in forms and verifies it on submission.

```php
$token = MicroR::csrfToken(); // To be placed in a hidden field
if (!MicroR::checkCsrf($_POST['csrf_token'])) die('CSRF!');
```

### CORS
Enables CORS headers for APIs.

```php
MicroR::enableCORS(['https://my-domain.com']);
```

### Force HTTPS
Automatically redirects to HTTPS.

```php
MicroR::forceHTTPS();
```

---

## 2. Minimal ORM

### Connection
```php
$mf = new MicroR('mysql:host=localhost;dbname=test', 'root', '');
```

### SELECT Query
```php
$users = $mf->find('users', ['id' => 1]);
```

### Insert
```php
$mf->save('users', ['name' => 'Alice', 'email' => 'alice@mail.com']);
```

### Text Search
```php
$users = $mf->search('users', ['name' => 'ali']);
```

---

## 3. ACL (Access Control List)

### Define a role and permission
```php
$mf->addRole('admin');
$mf->allow('admin', 'edit');
```

### Check access
```php
if ($mf->isAllowed('admin', 'edit')) { /* ... */ }
```

---

## 4. Routing

### Define a simple route
```php
MicroR::route('GET', '/home', function() {
    echo 'Welcome!';
});
```

### Define a parameterized route
```php
MicroR::route('GET', '/user/{id}', function($id) {
    echo "User profile #$id";
});
```

### Dispatch routes
```php
MicroR::dispatch();
```

---

## 5. Templating

### Render a view with XSS-protected variables
```php
MicroR::render('template.html', ['user' => $users[0]->name, 'csrf' => MicroR::csrfToken()]);
```

In the HTML template:
```html
<h1>Hello <?= $user ?></h1>
<input type="hidden" name="csrf_token" value="<?= $csrf ?>">
```

---

## 6. Class Autoloading

### Load all classes from a folder
```php
MicroR::loadClasses(__DIR__ . '/myClassesFolder');
```

---

## Complete Example

**index.php**
```php
require 'microR.php';

MicroR::forceHTTPS();
MicroR::enableCORS(['*']);

MicroR::route('GET', '/', function() {
    MicroR::render('template.html', [
        'user' => 'World',
        'csrf' => MicroR::csrfToken()
    ]);
});

MicroR::route('GET', '/user/{id}', function($id) {
    echo "User profile #$id";
});

MicroR::dispatch();
```

**template.html**
```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Home</title>
</head>
<body>
    <h1>Hello <?= $user ?></h1>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <button type="submit">Send</button>
    </form>
</body>
</html>
```

---

## Notes
- All variables injected into templates are automatically protected against XSS attacks.
- The CSRF token must be checked when submitting a form.
- Routing allows you to create simple and secure web applications, including with dynamic parameters.
- The ORM is protected against SQL injection by validating table/field names and using prepared statements.

---

For any questions or extensions, check the source code or ask for help!
