<?php

namespace App\Http\Controllers;

use Core\Request;
use Core\Response;
use App\Services\Auth;
use App\Services\MailConfig;
use App\Services\Mailer;
use App\Services\SmtpTransport;

class AdminController extends Controller
{
    protected Auth $auth;
    protected MailConfig $mailConfig;

    public function __construct(Auth $auth, MailConfig $mailConfig)
    {
        parent::__construct();
        $this->auth = $auth;
        $this->mailConfig = $mailConfig;
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
        ], 'layouts/admin');
    }

    public function login(Request $request): void
    {
        if (!$this->validCsrf($request)) {
            $this->auth->logAttempt('', 'csrf');
            $_SESSION['_admin_error'] = 'Session expirée. Merci de recharger la page et de réessayer.';
            Response::redirect(route('admin.login'))->send();
            exit;
        }

        $email = trim((string) $request->body('email'));
        $password = (string) $request->body('password');

        $wait = $this->auth->throttleSecondsLeft();
        if ($wait > 0) {
            $this->auth->logAttempt($email, 'throttled', 'wait=' . $wait . 's');
            $_SESSION['_admin_error'] = 'Trop de tentatives. Merci d\'attendre quelques secondes avant de réessayer.';
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

    public function logout(Request $request): void
    {
        if (!$this->validCsrf($request)) {
            Response::redirect(route('admin.dashboard'))->send();
            exit;
        }

        $this->auth->logout();
        Response::redirect(route('admin.login'))->send();
        exit;
    }

    public function dashboard(): string
    {
        $this->auth->requireAuth();

        $mailConfig = $this->mailConfig->all();
        $mailEngine = $mailConfig['engine'] ?? 'mail';
        $brevoConfigured = $this->isBrevoConfigured($mailConfig);

        $modules = [
            'mail' => [
                'title' => 'Configuration des e-mails',
                'description' => 'Moteur d\'envoi (PHP mail() ou Brevo SMTP), expéditeur, destinataire et test d\'envoi.',
                'route' => route('admin.mail'),
                'icon' => '&#128231;',
                'status' => $mailEngine === 'brevo'
                    ? ($brevoConfigured ? 'Brevo (SMTP) configuré' : 'Brevo (SMTP) — configurer')
                    : 'Moteur standard PHP mail()',
                'status_class' => $mailEngine === 'brevo' && $brevoConfigured
                    ? 'text-bg-success'
                    : 'text-bg-warning',
            ],
        ];

        $flash = $_SESSION['_admin_flash'] ?? null;
        unset($_SESSION['_admin_flash']);

        return $this->view('admin/dashboard', [
            'title' => 'Tableau de bord — Cours-Réseaux',
            'year' => \date('Y'),
            'admin_email' => $this->auth->user(),
            'modules' => $modules,
            'flash' => $flash,
        ], 'layouts/admin');
    }

    public function mail(): string
    {
        $this->auth->requireAuth();

        $form = $this->mailConfig->all();
        $success = $_SESSION['_admin_mail_success'] ?? null;
        $testSuccess = $_SESSION['_admin_mail_test_success'] ?? null;
        $error = $_SESSION['_admin_mail_error'] ?? null;
        $testError = $_SESSION['_admin_mail_test_error'] ?? null;
        unset(
            $_SESSION['_admin_mail_success'],
            $_SESSION['_admin_mail_test_success'],
            $_SESSION['_admin_mail_error'],
            $_SESSION['_admin_mail_test_error'],
        );

        return $this->renderMailPage(
            $form,
            success: $success,
            testSuccess: $testSuccess,
            error: $error,
            testError: $testError,
        );
    }

    public function doMail(Request $request): void
    {
        $this->auth->requireAuth();

        if (!$this->validCsrf($request)) {
            $_SESSION['_admin_mail_error'] = 'Session expirée. Merci de recharger la page et de réessayer.';
            Response::redirect(route('admin.mail'))->send();
            exit;
        }

        $engine = $request->body('engine', 'mail');
        if (!in_array($engine, ['mail', 'brevo'], true)) {
            $_SESSION['_admin_mail_error'] = 'Moteur d\'envoi invalide.';
            Response::redirect(route('admin.mail'))->send();
            exit;
        }

        $form = $this->formFromRequest($request, $engine);

        if ($form['from'] === '' || $form['to'] === '') {
            $_SESSION['_admin_mail_error'] = 'L\'expéditeur et le destinataire sont obligatoires.';
            Response::redirect(route('admin.mail'))->send();
            exit;
        }

        if ($engine === 'brevo') {
            $brevo = $form['brevo'];
            if ($brevo['smtp_host'] === '' || $brevo['smtp_user'] === '' || $brevo['smtp_password'] === '') {
                $_SESSION['_admin_mail_error'] = 'Pour Brevo, le serveur SMTP, l\'identifiant et le mot de passe sont requis.';
                Response::redirect(route('admin.mail'))->send();
                exit;
            }
        }

        if ($this->mailConfig->save($form)) {
            $_SESSION['_admin_mail_success'] = 'Configuration d\'envoi enregistrée.';
        } else {
            $_SESSION['_admin_mail_error'] = 'Erreur lors de l\'enregistrement de la configuration.';
        }

        Response::redirect(route('admin.mail'))->send();
        exit;
    }

    public function testMail(Request $request): string
    {
        $this->auth->requireAuth();

        if (!$this->validCsrf($request)) {
            $_SESSION['_admin_mail_error'] = 'Session expirée. Merci de recharger la page et de réessayer.';
            Response::redirect(route('admin.mail'))->send();
            exit;
        }

        $engine = $request->body('engine', 'mail');
        if (!in_array($engine, ['mail', 'brevo'], true)) {
            $engine = 'mail';
        }

        $form = $this->formFromRequest($request, $engine);
        $subject = 'Test d\'envoi Cours-Réseaux';
        $body = "Ceci est un e-mail de test envoyé depuis le panneau d'administration de cours-reseaux.fr.\n\n";
        $body .= 'Date : ' . date('d/m/Y H:i:s');

        if ($engine === 'brevo') {
            $brevo = $form['brevo'];
            $transport = new SmtpTransport(
                $brevo['smtp_host'],
                $brevo['smtp_port'],
                $brevo['smtp_user'],
                $brevo['smtp_password'],
                $brevo['smtp_security'],
            );
            $ok = $transport->send($form['to'], $form['from'], $form['from_name'], $subject, $body);
            $message = $transport->lastError;
        } else {
            $mailer = new Mailer($form['from'], $form['to'], $form['from_name']);
            $headerString = implode("\r\n", [
                "From: {$form['from_name']} <{$form['from']}>",
                'Return-Path: ' . $form['from'],
                'MIME-Version: 1.0',
                'Content-Type: text/plain; charset=UTF-8',
                'Content-Transfer-Encoding: 8bit',
            ]);
            $ok = $mailer->deliver($form['to'], $subject, $body, $headerString, $form['from']);
            $message = $mailer->lastError;
        }

        return $this->renderMailPage(
            $form,
            testSuccess: $ok ? 'E-mail de test envoyé avec succès.' : null,
            testError: $ok ? null : 'Échec de l\'envoi : ' . $message,
        );
    }

    protected function formFromRequest(Request $request, string $engine): array
    {
        $security = $request->body('smtp_security', 'tls');
        if (!in_array($security, ['tls', 'ssl', 'none'], true)) {
            $security = 'tls';
        }

        $port = (int) $request->body('smtp_port', 587);
        if ($port < 1 || $port > 65535) {
            $port = 587;
        }

        $brevo = [
            'smtp_host' => trim($request->body('smtp_host', 'smtp-relay.brevo.com')),
            'smtp_port' => $port,
            'smtp_user' => trim($request->body('smtp_user', '')),
            'smtp_password' => (string) $request->body('smtp_password', ''),
            'smtp_security' => $security,
        ];

        return [
            'engine' => $engine,
            'from' => trim($request->body('from', 'contact@cours-reseaux.fr')),
            'to' => trim($request->body('to', 'contact@cours-reseaux.fr')),
            'from_name' => trim($request->body('from_name', 'Cours-Reseaux')),
            'log_enabled' => (bool) $request->body('log_enabled'),
            'brevo' => $brevo,
        ];
    }

    protected function renderMailPage(
        array $form,
        ?string $success = null,
        ?string $testSuccess = null,
        ?string $error = null,
        ?string $testError = null,
    ): string {
        return $this->view('admin/mail', [
            'title' => 'Envoi des mails — Cours-Réseaux',
            'year' => \date('Y'),
            'form' => $form,
            'success' => $success,
            'test_success' => $testSuccess,
            'error' => $error,
            'test_error' => $testError,
        ], 'layouts/admin');
    }

    protected function isBrevoConfigured(array $config): bool
    {
        $brevo = $config['brevo'] ?? [];
        return (string) ($brevo['smtp_user'] ?? '') !== ''
            && (string) ($brevo['smtp_password'] ?? '') !== '';
    }

    protected function validCsrf(Request $request): bool
    {
        $token = (string) $request->body('_csrf');
        $session = (string) ($_SESSION['_csrf_token'] ?? '');
        return $token !== '' && $session !== '' && \hash_equals($session, $token);
    }
}