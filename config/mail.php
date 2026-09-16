<?php

// Configuration du moteur d'envoi des e-mails.
// 'engine' => 'mail'   : envoi via la fonction PHP mail() du serveur.
// 'engine' => 'brevo'  : envoi via SMTP Brevo (smtp-relay.brevo.com).
// Ce fichier est réécrit par le panneau d'administration (app/Services/MailConfig.php).

return [
    'engine' => 'mail',
    'from' => 'contact@cours-reseaux.fr',
    'to' => 'contact@cours-reseaux.fr',
    'from_name' => 'Cours-Reseaux',
    'quota' => [
        'hour_max' => 20,
        'day_max' => 60,
    ],
    'log_enabled' => false,
    'brevo' => [
        'smtp_host' => 'smtp-relay.brevo.com',
        'smtp_port' => 587,
        'smtp_user' => '',
        'smtp_password' => '',
        'smtp_security' => 'tls',
    ],
];