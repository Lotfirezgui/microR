<?php
require 'microR.php';

// Connexion à la base (adapter les identifiants si besoin)
$mf = new MicroR('mysql:host=localhost;dbname=test', 'root', '');

// Récupération d'un utilisateur fictif (ou valeur par défaut)
$users = $mf->find('users', ['id' => 1]);

// Affichage du template HTML avec protection XSS et CSRF
// $user et $csrf sont injectés dans le template

echo MicroR::render('template.html', [
    'user' => $users[0]->name ?? 'Invité',
    'csrf' => MicroR::csrfToken()
]);
