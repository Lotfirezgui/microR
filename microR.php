<?php
/**
 * MicroFramework PHP - "microR"
 * Un seul fichier : sécurité, ORM, ACL, templating
 * Auteur : GitHub Copilot
 */
class MicroR {
    // --- Sécurité ---
    public static function sanitize($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitize'], $data);
        }
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
    public static function csrfToken() {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    public static function checkCsrf($token) {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    // --- ORM Minimal ---
    private $pdo;
    public function __construct($dsn, $user = '', $pass = '') {
        $this->pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    }
    // --- Validation des noms de table et de champs pour l'ORM ---
    private function validateIdentifier($name) {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
            throw new InvalidArgumentException('Nom de table ou de champ invalide : ' . $name);
        }
        return $name;
    }
    public function find($table, $where = [], $class = 'stdClass') {
        $table = $this->validateIdentifier($table);
        $sql = "SELECT * FROM `$table`";
        $params = [];
        if ($where) {
            $sql .= " WHERE ";
            $w = [];
            foreach ($where as $k => $v) {
                $k = $this->validateIdentifier($k);
                $w[] = "`$k` = :$k";
                $params[":$k"] = $v;
            }
            $sql .= implode(' AND ', $w);
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_CLASS, $class);
    }
    public function save($table, $data) {
        $table = $this->validateIdentifier($table);
        $fields = array_keys($data);
        foreach ($fields as $f) { $this->validateIdentifier($f); }
        $sql = "INSERT INTO `$table` (".implode(',', array_map(function($f){return "`$f`";}, $fields)).") VALUES (:".implode(',:', $fields).")";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($data);
    }
    // --- Recherche textuelle (LIKE) dans l'ORM ---
    public function search($table, $criteria = [], $class = 'stdClass') {
        $table = $this->validateIdentifier($table);
        $sql = "SELECT * FROM `$table`";
        $params = [];
        if ($criteria) {
            $sql .= " WHERE ";
            $w = [];
            foreach ($criteria as $k => $v) {
                $k = $this->validateIdentifier($k);
                $w[] = "`$k` LIKE :$k";
                $params[":$k"] = '%' . $v . '%';
            }
            $sql .= implode(' AND ', $w);
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_CLASS, $class);
    }
    // --- ACL ---
    private $roles = [];
    private $permissions = [];
    public function addRole($role) {
        $this->roles[$role] = [];
    }
    public function allow($role, $resource) {
        $this->permissions[$role][] = $resource;
    }
    public function isAllowed($role, $resource) {
        return in_array($resource, $this->permissions[$role] ?? []);
    }
    // --- Routage ---
    private static $routes = [];
    public static function route($method, $path, $callback) {
        // Convertit /user/{id} en regex et extrait les noms de paramètres
        $paramNames = [];
        $regex = preg_replace_callback('/\{([a-zA-Z0-9_]+)\}/', function($m) use (&$paramNames) {
            $paramNames[] = $m[1];
            return '([^/]+)';
        }, $path);
        $regex = '#^' . $regex . '$#';
        self::$routes[] = compact('method', 'path', 'callback', 'regex', 'paramNames');
    }
    public static function dispatch() {
        $requestUri = strtok($_SERVER['REQUEST_URI'], '?');
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        foreach (self::$routes as $route) {
            if ($route['method'] === $requestMethod) {
                if (isset($route['regex'])) {
                    if (preg_match($route['regex'], $requestUri, $matches)) {
                        array_shift($matches); // Retire le match complet
                        $params = [];
                        if (!empty($route['paramNames'])) {
                            foreach ($route['paramNames'] as $i => $name) {
                                $params[$name] = $matches[$i] ?? null;
                            }
                        }
                        return call_user_func_array($route['callback'], $params);
                    }
                } elseif ($route['path'] === $requestUri) {
                    return call_user_func($route['callback']);
                }
            }
        }
        http_response_code(404);
        echo '404 Not Found';
    }
    // --- Templating avec protection XSS ---
    public static function render($template, $vars = []) {
        $safeVars = array_map([self::class, 'sanitize'], $vars);
        extract($safeVars);
        include $template;
    }
    // --- Autoload de classes depuis un dossier ---
    public static function loadClasses($path) {
        foreach (glob(rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.php') as $file) {
            require_once $file;
        }
    }
    // --- Sécurité CORS ---
    public static function enableCORS($origins = ['*'], $methods = ['GET', 'POST', 'OPTIONS'], $headers = ['Content-Type']) {
        header('Access-Control-Allow-Origin: ' . implode(',', $origins));
        header('Access-Control-Allow-Methods: ' . implode(',', $methods));
        header('Access-Control-Allow-Headers: ' . implode(',', $headers));
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }
    // --- Forcer HTTPS ---
    public static function forceHTTPS() {
        if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
            $redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            header('Location: ' . $redirect);
            exit;
        }
    }
}
// --- Exemples d'utilisation ---
// $mf = new MicroR('mysql:host=localhost;dbname=test', 'root', '');
// $users = $mf->find('users', ['id' => 1]);
// echo MicroR::render('view.php', ['user' => $users[0]]);
// if (!MicroR::checkCsrf($_POST['csrf_token'])) die('CSRF!');
// $mf->addRole('admin'); $mf->allow('admin', 'edit');
// if ($mf->isAllowed('admin', 'edit')) { /* ... */ }
