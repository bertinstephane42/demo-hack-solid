<?php

namespace App\Http\Controllers;

use Core\Request;
use Core\Response;
use App\Services\Auth;
use App\Services\BrevoMailer;
use App\Services\BrevoConfig;

class AdminController extends Controller
{
    protected Auth $auth;
    protected BrevoMailer $brevo;
    protected BrevoConfig $brevoConfig;

    public function __construct(Auth $auth, BrevoMailer $brevo, BrevoConfig $brevoConfig)
    {
        parent::__construct();
        $this->auth = $auth;
        $this->brevo = $brevo;
        $this->brevoConfig = $brevoConfig;
    }

    public function showLogin(): string
    {
        if ($this->auth->check()) {
            Response::redirect(route('admin.dashboard'))->send();
            exit;
        }

        $error = $_SESSION['_admin_error'] ?? null;
        unset($_SESSION['_admin_error']);

        return $this->view('admin/login', [
            'title' => 'Administration — Cours-Réseaux',
            'year' => \date('Y'),
            'error' => $error,
            'throttle_left' => $this->auth->throttleSecondsLeft(),
            'csrf_token' => $this->csrfToken(),
        ], 'layouts/admin');
    }

    public function login(Request $request): void
    {
        $email = trim((string) $request->body('email'));
        $password = (string) $request->body('password');

        $throttleLeft = $this->auth->throttleSecondsLeft();
        if ($throttleLeft > 0) {
            $this->auth->logAttempt($email, 'throttled', 'wait=' . $throttleLeft . 's');
            $_SESSION['_admin_error'] = 'Trop de tentatives. Réessayez dans ' . $throttleLeft . ' seconde(s).';
            Response::redirect(route('admin.login'))->send();
            exit;
        }

        if (!$this->validCsrf($request)) {
            $this->auth->logAttempt($email, 'csrf');
            $_SESSION['_admin_error'] = 'Jetons de sécurité invalides. Rechargez la page.';
            Response::redirect(route('admin.login'))->send();
            exit;
        }

        if ($this->auth->attempt($email, $password)) {
            $this->auth->logAttempt($email, 'success');
            Response::redirect(route('admin.dashboard'))->send();
            exit;
        }

        $this->auth->logAttempt($email, 'fail');
        $_SESSION['_admin_error'] = 'Identifiants incorrects.';
        Response::redirect(route('admin.login'))->send();
        exit;
    }

    public function logout(): void
    {
        $this->auth->logout();
        Response::redirect(route('admin.login'))->send();
        exit;
    }

    public function dashboard(): string
    {
        $this->auth->requireAuth();

        $flash = $_SESSION['_admin_flash'] ?? null;
        unset($_SESSION['_admin_flash']);

        $settings = $this->brevoConfig->all();

        return $this->view('admin/dashboard', [
            'title' => 'Envoi des mails — Cours-Réseaux',
            'year' => \date('Y'),
            'admin_email' => $this->auth->user(),
            'flash' => $flash,
            'settings' => $settings,
            'csrf_token' => $this->csrfToken(),
        ], 'layouts/admin');
    }

    public function saveSettings(Request $request): void
    {
        $this->auth->requireAuth();

        if (!$this->validCsrf($request)) {
            $_SESSION['_admin_flash'] = ['type' => 'danger', 'message' => 'Jetons de sécurité invalides. Recommencez.'];
            Response::redirect(route('admin.dashboard'))->send();
            exit;
        }

        $apiKey = trim((string) $request->body('api_key'));
        $senderName = trim((string) $request->body('sender_name'));
        $senderEmail = trim((string) $request->body('sender_email'));

        if ($senderEmail !== '' && !filter_var($senderEmail, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['_admin_flash'] = ['type' => 'danger', 'message' => "L'adresse expéditeur n'est pas valide."];
            Response::redirect(route('admin.dashboard'))->send();
            exit;
        }

        $ok = $this->brevoConfig->save([
            'api_key' => $apiKey,
            'sender_name' => $senderName,
            'sender_email' => $senderEmail,
        ]);

        $_SESSION['_admin_flash'] = $ok
            ? ['type' => 'success', 'message' => 'Paramètres Brevo enregistrés.']
            : ['type' => 'danger', 'message' => "Échec de l'enregistrement des paramètres."];

        Response::redirect(route('admin.dashboard'))->send();
        exit;
    }

    public function send(Request $request): void
    {
        $this->auth->requireAuth();

        if (!$this->validCsrf($request)) {
            $_SESSION['_admin_flash'] = ['type' => 'danger', 'message' => 'Jetons de sécurité invalides. Recommencez.'];
            Response::redirect(route('admin.dashboard'))->send();
            exit;
        }

        $toEmail = trim((string) $request->body('to_email'));
        $toName = trim((string) $request->body('to_name'));
        $subject = trim((string) $request->body('subject'));
        $html = trim((string) $request->body('message'));

        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['_admin_flash'] = ['type' => 'danger', 'message' => "L'adresse destinataire n'est pas valide."];
            Response::redirect(route('admin.dashboard'))->send();
            exit;
        }

        if ($subject === '' || $subject === null) {
            $_SESSION['_admin_flash'] = ['type' => 'danger', 'message' => 'Le sujet est requis.'];
            Response::redirect(route('admin.dashboard'))->send();
            exit;
        }

        if (strlen($html) < 1) {
            $_SESSION['_admin_flash'] = ['type' => 'danger', 'message' => 'Le message est requis.'];
            Response::redirect(route('admin.dashboard'))->send();
            exit;
        }

        $ok = $this->brevo->send($toEmail, $toName, $subject, $html);

        $type = $ok ? 'success' : 'danger';
        $message = $ok
            ? 'Mail envoyé avec succès via Brevo à ' . htmlspecialchars($toEmail) . '.'
            : 'Échec de l\'envoi : ' . htmlspecialchars($this->brevo->lastError);

        $_SESSION['_admin_flash'] = ['type' => $type, 'message' => $message];

        Response::redirect(route('admin.dashboard'))->send();
        exit;
    }

    protected function csrfToken(): string
    {
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = \bin2hex(\random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }

    protected function validCsrf(Request $request): bool
    {
        $token = (string) $request->body('_csrftoken');
        $session = (string) ($_SESSION['_csrf_token'] ?? '');
        return $token !== '' && $session !== '' && \hash_equals($session, $token);
    }
}