# Micro-framework PHP « microR »

## Présentation

`microR.php` est un micro-framework PHP en un seul fichier, intégrant :
- Sécurité (XSS, CSRF, CORS, HTTPS)
- ORM minimal sécurisé
- ACL (contrôle d’accès par rôles)
- Routage simple (avec routes paramétrées)
- Templating avec protection XSS
- Autoload de classes

---

## 1. Sécurité

### XSS
Toutes les variables injectées dans les templates via `render` sont automatiquement échappées.

```php
$safe = MicroR::sanitize($input);
```

### CSRF
Génère un token à placer dans les formulaires et vérifie à la soumission.

```php
$token = MicroR::csrfToken(); // À placer dans un champ caché
if (!MicroR::checkCsrf($_POST['csrf_token'])) die('CSRF!');
```

### CORS
Active les headers CORS pour les API.

```php
MicroR::enableCORS(['https://mon-domaine.com']);
```

### Forcer HTTPS
Redirige automatiquement vers HTTPS.

```php
MicroR::forceHTTPS();
```

---

## 2. ORM Minimal

### Connexion
```php
$mf = new MicroR('mysql:host=localhost;dbname=test', 'root', '');
```

### Requête SELECT
```php
$users = $mf->find('users', ['id' => 1]);
```

### Insertion
```php
$mf->save('users', ['name' => 'Alice', 'email' => 'alice@mail.com']);
```

### Recherche textuelle
```php
$users = $mf->search('users', ['name' => 'ali']);
```

---

## 3. ACL (Contrôle d’accès)

### Définir un rôle et une permission
```php
$mf->addRole('admin');
$mf->allow('admin', 'edit');
```

### Vérifier l’accès
```php
if ($mf->isAllowed('admin', 'edit')) { /* ... */ }
```

---

## 4. Routage

### Déclarer une route simple
```php
MicroR::route('GET', '/accueil', function() {
    echo 'Bienvenue !';
});
```

### Déclarer une route paramétrée
```php
MicroR::route('GET', '/user/{id}', function($id) {
    echo "Profil de l'utilisateur #$id";
});
```

### Dispatcher les routes
```php
MicroR::dispatch();
```

---

## 5. Templating

### Afficher une vue avec variables protégées XSS
```php
MicroR::render('template.html', ['user' => $users[0]->name, 'csrf' => MicroR::csrfToken()]);
```

Dans le template HTML :
```html
<h1>Bonjour <?= $user ?></h1>
<input type="hidden" name="csrf_token" value="<?= $csrf ?>">
```

---

## 6. Autoload de classes

### Charger toutes les classes d’un dossier
```php
MicroR::loadClasses(__DIR__ . '/monDossierClasses');
```

---

## Exemple complet

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
    echo "Profil de l'utilisateur #$id";
});

MicroR::dispatch();
```

**template.html**
```html
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Accueil</title>
</head>
<body>
    <h1>Bonjour <?= $user ?></h1>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <button type="submit">Envoyer</button>
    </form>
</body>
</html>
```

---

## Notes
- Toutes les variables injectées dans les templates sont automatiquement protégées contre les attaques XSS.
- Le token CSRF doit être vérifié lors de la soumission d’un formulaire.
- Le routage permet de créer des applications web simples et sécurisées, y compris avec des paramètres dynamiques.
- L’ORM est protégé contre l’injection SQL par validation des noms de tables et champs, et requêtes préparées.

---

Pour toute question ou extension, consulte le code source ou demande de l’aide !
