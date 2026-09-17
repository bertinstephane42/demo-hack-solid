<?php

namespace App\Http\Controllers;

use Core\Request;
use Core\Response;
use App\Services\Auth;
use App\Services\MailConfig;
use App\Services\Mailer;
use App\Services\SmtpTransport;
use App\Services\PasswordReset;
use App\Services\SystemCheck;
use App\Services\DataExporter;

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
            'password' => [
                'title' => 'Mot de passe',
                'description' => 'Modifier le mot de passe administrateur (politique stricte : 20 caractères, minuscule, majuscule, chiffre et symbole).',
                'route' => route('admin.password'),
                'icon' => '&#128274;',
                'status' => 'Hash bcrypt + protection CSRF',
                'status_class' => 'text-bg-success',
            ],
            'user' => [
                'title' => 'Utilisateur',
                'description' => 'Modifier l\'e-mail de connexion admin.',
                'route' => route('admin.user'),
                'icon' => '&#128100;',
                'status' => 'Email de connexion admin',
                'status_class' => 'text-bg-success',
            ],
            'maintenance' => [
                'title' => 'Maintenance',
                'description' => 'Journal de connexion, sauvegarde des données et état du système.',
                'route' => route('admin.system'),
                'icon' => '&#128736;',
                'status' => 'Outils de maintenance',
                'status_class' => 'text-bg-secondary',
                'vertical_tools' => true,
            ],
        ];

        $flash = $_SESSION['_admin_flash'] ?? null;
        unset($_SESSION['_admin_flash']);

        $tmpCount = app(SystemCheck::class)->tmpFiles()['count'];

        return $this->view('admin/dashboard', [
            'title' => 'Tableau de bord — Cours-Réseaux',
            'year' => \date('Y'),
            'admin_email' => $this->auth->user(),
            'modules' => $modules,
            'flash' => $flash,
            'tmp_count' => $tmpCount,
        ], 'layouts/admin');
    }

    public function password(): string
    {
        $this->auth->requireAuth();

        $error = $_SESSION['_admin_password_error'] ?? null;
        $success = $_SESSION['_admin_password_success'] ?? null;
        unset($_SESSION['_admin_password_error'], $_SESSION['_admin_password_success']);

        return $this->view('admin/password', [
            'title' => 'Changer le mot de passe — Cours-Réseaux',
            'year' => \date('Y'),
            'error' => $error,
            'success' => $success,
        ], 'layouts/admin');
    }

    public function doPassword(Request $request): void
    {
        $this->auth->requireAuth();

        if (!$this->validCsrf($request)) {
            $_SESSION['_admin_password_error'] = 'Session expirée. Merci de recharger la page et de réessayer.';
            Response::redirect(route('admin.password'))->send();
            exit;
        }

        $current = (string) $request->body('current_password', '');
        $new = (string) $request->body('new_password', '');
        $confirm = (string) $request->body('confirm_password', '');

        $currentHash = config('admin.password_hash', '');
        if (!password_verify($current, $currentHash)) {
            $_SESSION['_admin_password_error'] = 'Le mot de passe actuel est incorrect.';
            Response::redirect(route('admin.password'))->send();
            exit;
        }

        if ($new !== $confirm) {
            $_SESSION['_admin_password_error'] = 'Les nouveaux mots de passe ne correspondent pas.';
            Response::redirect(route('admin.password'))->send();
            exit;
        }

        if ($new === $current) {
            $_SESSION['_admin_password_error'] = 'Le nouveau mot de passe doit être différent du mot de passe actuel.';
            Response::redirect(route('admin.password'))->send();
            exit;
        }

        $policyError = $this->auth->passwordPolicyError($new);
        if ($policyError !== '') {
            $_SESSION['_admin_password_error'] = $policyError;
            Response::redirect(route('admin.password'))->send();
            exit;
        }

        if ($this->auth->changePassword($new)) {
            $_SESSION['_admin_password_success'] = 'Mot de passe modifié avec succès.';
        } else {
            $_SESSION['_admin_password_error'] = 'Erreur lors de la modification du mot de passe.';
        }

        Response::redirect(route('admin.password'))->send();
        exit;
    }

    public function user(): string
    {
        $this->auth->requireAuth();

        $error = $_SESSION['_admin_user_error'] ?? null;
        $success = $_SESSION['_admin_user_success'] ?? null;
        unset($_SESSION['_admin_user_error'], $_SESSION['_admin_user_success']);

        return $this->view('admin/user', [
            'title' => 'Compte utilisateur — Cours-Réseaux',
            'year' => \date('Y'),
            'admin_email' => $this->auth->user(),
            'error' => $error,
            'success' => $success,
        ], 'layouts/admin');
    }

    public function doUser(Request $request): void
    {
        $this->auth->requireAuth();

        if (!$this->validCsrf($request)) {
            $_SESSION['_admin_user_error'] = 'Session expirée. Merci de recharger la page et de réessayer.';
            Response::redirect(route('admin.user'))->send();
            exit;
        }

        $current = (string) $request->body('current_password', '');
        $newEmail = strtolower(trim((string) $request->body('new_email', '')));
        $confirm = strtolower(trim((string) $request->body('confirm_email', '')));

        $currentHash = config('admin.password_hash', '');
        if (!password_verify($current, $currentHash)) {
            $_SESSION['_admin_user_error'] = 'Le mot de passe actuel est incorrect.';
            Response::redirect(route('admin.user'))->send();
            exit;
        }

        if (filter_var($newEmail, FILTER_VALIDATE_EMAIL) === false) {
            $_SESSION['_admin_user_error'] = 'L\'adresse e-mail saisie n\'est pas valide.';
            Response::redirect(route('admin.user'))->send();
            exit;
        }

        if ($newEmail !== $confirm) {
            $_SESSION['_admin_user_error'] = 'Les nouvelles adresses e-mail ne correspondent pas.';
            Response::redirect(route('admin.user'))->send();
            exit;
        }

        if ($newEmail === strtolower(trim((string) config('admin.admin_email', '')))) {
            $_SESSION['_admin_user_error'] = 'La nouvelle adresse doit être différente de l\'adresse actuelle.';
            Response::redirect(route('admin.user'))->send();
            exit;
        }

        if ($this->auth->changeEmail($newEmail)) {
            $_SESSION['_admin_user_success'] = 'Adresse e-mail de connexion modifiée avec succès.';
        } else {
            $_SESSION['_admin_user_error'] = 'Erreur lors de la modification de l\'adresse e-mail.';
        }

        Response::redirect(route('admin.user'))->send();
        exit;
    }

    /* ----------------------- Mot de passe oublié ----------------------- */

    public function forgot(): string
    {
        if ($this->auth->check()) {
            Response::redirect(route('admin.dashboard'))->send();
            exit;
        }

        $error = $_SESSION['_admin_error'] ?? null;
        $success = $_SESSION['_admin_success'] ?? null;
        unset($_SESSION['_admin_error'], $_SESSION['_admin_success']);

        $reset = app(PasswordReset::class);

        return $this->view('admin/forgot', [
            'title' => 'Mot de passe oublié — Cours-Réseaux',
            'year' => \date('Y'),
            'error' => $error,
            'success' => $success,
            'has_pending' => $reset->hasPending(),
            'seconds_left' => $reset->secondsLeft(),
            'resend_left' => $reset->resendSecondsLeft(),
        ], 'layouts/admin');
    }

    public function doForgot(Request $request): void
    {
        if ($this->auth->check()) {
            Response::redirect(route('admin.dashboard'))->send();
            exit;
        }

        if (!$this->validCsrf($request)) {
            $this->auth->logAttempt('', 'csrf');
            $_SESSION['_admin_error'] = 'Session expirée. Merci de recharger la page et de réessayer.';
            Response::redirect(route('admin.forgot'))->send();
            exit;
        }

        $reset = app(PasswordReset::class);
        $result = $reset->request($this->auth->clientIp());

        if (empty($result['ok'])) {
            $this->auth->logAttempt('', 'reset-request', 'refused');
            $_SESSION['_admin_error'] = $result['error'] ?? 'Impossible d\'envoyer le code.';
            Response::redirect(route('admin.forgot'))->send();
            exit;
        }

        // Le code est envoyé à l'adresse d'expédition configurée, jamais à une
        // adresse saisie : on ne révèle aucune information sur le compte.
        $recipient = (string) config('mail.from', config('mail.to', ''));
        if ($recipient === '' || filter_var($recipient, FILTER_VALIDATE_EMAIL) === false) {
            $reset->clear();
            $this->auth->logAttempt('', 'reset-request', 'no-recipient');
            $_SESSION['_admin_error'] = 'Aucune adresse d\'expédition valide n\'est configurée (Administration > Mails).';
            Response::redirect(route('admin.forgot'))->send();
            exit;
        }

        if (!$this->sendResetCode($recipient, (string) $result['code'])) {
            $reset->clear();
            $this->auth->logAttempt('', 'reset-request', 'send-failed');
            $_SESSION['_admin_error'] = 'L\'envoi du code a échoué. Vérifiez la configuration des mails puis réessayez.';
            Response::redirect(route('admin.forgot'))->send();
            exit;
        }

        $this->auth->logAttempt('', 'reset-request', 'sent');
        $_SESSION['_admin_success'] = 'Un code à ' . PasswordReset::CODE_LENGTH . ' chiffres a été envoyé à '
            . $recipient . '. Il est valable ' . (PasswordReset::CODE_TTL / 60) . ' minutes.';
        Response::redirect(route('admin.reset'))->send();
        exit;
    }

    protected function sendResetCode(string $to, string $code): bool
    {
        $appName = (string) config('app.name', 'Cours-Réseaux');

        $subject = $appName . ' — Réinitialisation du mot de passe';

        $body = "Bonjour,\n\n"
            . "Une réinitialisation du mot de passe de l'administration du site " . $appName . " a été demandée.\n\n"
            . "Votre code de vérification est : " . $code . "\n\n"
            . "Saisissez ce code sur la page de réinitialisation dans les 5 minutes. "
            . "Sans action, la demande expirera automatiquement.\n\n"
            . "Si vous n'êtes pas à l'origine de cette demande, ignorez cet e-mail : "
            . "aucune modification n'a été effectuée et votre mot de passe reste inchangé.\n\n"
            . "— L'équipe " . $appName;

        try {
            return app(Mailer::class)->sendTo($to, $subject, $body);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function reset(): string
    {
        if ($this->auth->check()) {
            Response::redirect(route('admin.dashboard'))->send();
            exit;
        }

        $error = $_SESSION['_admin_error'] ?? null;
        $success = $_SESSION['_admin_success'] ?? null;
        unset($_SESSION['_admin_error'], $_SESSION['_admin_success']);

        $reset = app(PasswordReset::class);

        return $this->view('admin/reset', [
            'title' => 'Nouveau mot de passe — Cours-Réseaux',
            'year' => \date('Y'),
            'error' => $error,
            'success' => $success,
            'has_pending' => $reset->hasPending(),
            'seconds_left' => $reset->secondsLeft(),
            'code_length' => PasswordReset::CODE_LENGTH,
        ], 'layouts/admin');
    }

    public function doReset(Request $request): void
    {
        if ($this->auth->check()) {
            Response::redirect(route('admin.dashboard'))->send();
            exit;
        }

        if (!$this->validCsrf($request)) {
            $this->auth->logAttempt('', 'csrf');
            $_SESSION['_admin_error'] = 'Session expirée. Merci de recharger la page et de réessayer.';
            Response::redirect(route('admin.reset'))->send();
            exit;
        }

        $code = trim((string) $request->body('code', ''));
        $new = (string) $request->body('new_password', '');
        $confirm = (string) $request->body('confirm_password', '');

        if ($new !== $confirm) {
            $_SESSION['_admin_error'] = 'Les deux mots de passe ne correspondent pas.';
            Response::redirect(route('admin.reset'))->send();
            exit;
        }

        $policyError = $this->auth->passwordPolicyError($new);
        if ($policyError !== '') {
            $_SESSION['_admin_error'] = $policyError;
            Response::redirect(route('admin.reset'))->send();
            exit;
        }

        $currentHash = config('admin.password_hash', '');
        if ($currentHash !== '' && password_verify($new, $currentHash)) {
            $_SESSION['_admin_error'] = 'Le nouveau mot de passe doit être différent du mot de passe actuel.';
            Response::redirect(route('admin.reset'))->send();
            exit;
        }

        // Le code n'est vérifié (et consommé) qu'après validation du mot de
        // passe, afin qu'une erreur de saisie ne fasse pas perdre le code.
        $verify = app(PasswordReset::class)->verify($code);

        if (empty($verify['ok'])) {
            $this->auth->logAttempt('', 'reset-fail', (string) ($verify['error'] ?? ''));
            $_SESSION['_admin_error'] = $verify['error'] ?? 'Code invalide.';
            Response::redirect(route('admin.reset'))->send();
            exit;
        }

        if (!$this->auth->changePassword($new)) {
            $this->auth->logAttempt('', 'reset-fail', 'write-error');
            $_SESSION['_admin_error'] = 'Erreur lors de l\'enregistrement du nouveau mot de passe.';
            Response::redirect(route('admin.reset'))->send();
            exit;
        }

        $this->auth->logAttempt('', 'reset-success');
        $_SESSION['_admin_success'] = 'Mot de passe réinitialisé avec succès. Vous pouvez maintenant vous connecter.';
        Response::redirect(route('admin.login'))->send();
        exit;
    }

    /* ----------------------- Journal de connexion ---------------------- */

    public function logs(): string
    {
        $this->auth->requireAuth();

        $success = $_SESSION['_admin_logs_success'] ?? null;
        unset($_SESSION['_admin_logs_success']);

        $entries = $this->auth->readLoginLog(200);
        $countSuccess = 0;
        $countFail = 0;
        foreach ($entries as $entry) {
            if (\in_array($entry['result'], ['success', 'reset-success'], true)) {
                $countSuccess++;
            } elseif (\in_array($entry['result'], ['fail', 'throttled', 'csrf', 'timeout', 'reset-fail'], true)) {
                $countFail++;
            }
        }

        return $this->view('admin/logs', [
            'title' => 'Journal de connexion — Cours-Réseaux',
            'year' => \date('Y'),
            'entries' => $entries,
            'count_success' => $countSuccess,
            'count_fail' => $countFail,
            'log_exists' => is_file($this->auth->loginLogPath()),
            'success' => $success,
        ], 'layouts/admin');
    }

    public function clearLogs(Request $request): void
    {
        $this->auth->requireAuth();

        if (!$this->validCsrf($request)) {
            Response::redirect(route('admin.logs'))->send();
            exit;
        }

        if ($this->auth->clearLoginLog()) {
            $_SESSION['_admin_logs_success'] = 'Journal de connexion vidé.';
        } else {
            $_SESSION['_admin_logs_success'] = 'Le journal n\'a pas pu être vidé.';
        }

        Response::redirect(route('admin.logs'))->send();
        exit;
    }

    /* ---------------------------- Sauvegarde --------------------------- */

    public function export(): string
    {
        $this->auth->requireAuth();

        $summary = app(SystemCheck::class)->summary();

        return $this->view('admin/export', [
            'title' => 'Sauvegarde — Cours-Réseaux',
            'year' => \date('Y'),
            'checks_ok' => $summary['ok'],
            'checks_fail' => $summary['fail'],
            'checks_total' => $summary['total'],
        ], 'layouts/admin');
    }

    public function downloadExport(Request $request): void
    {
        $this->auth->requireAuth();

        if (!$this->validCsrf($request)) {
            Response::redirect(route('admin.export'))->send();
            exit;
        }

        $exporter = app(DataExporter::class);
        $json = $exporter->toJson($exporter->collect());

        $filename = 'cours-reseaux-sauvegarde-' . \date('Y-m-d-His') . '.json';

        Response::download($json, $filename, 'application/json; charset=utf-8')->send();
        exit;
    }

    /* ------------------------------ Système ---------------------------- */

    public function system(): string
    {
        $this->auth->requireAuth();

        $check = app(SystemCheck::class);
        $items = $check->checks();

        $success = $_SESSION['_admin_success'] ?? null;
        $error = $_SESSION['_admin_error'] ?? null;
        unset($_SESSION['_admin_success'], $_SESSION['_admin_error']);

        $tmp = $check->tmpFiles();

        return $this->view('admin/system', [
            'title' => 'Système — Cours-Réseaux',
            'year' => \date('Y'),
            'groups' => $check->grouped($items),
            'summary' => $check->summary($items),
            'tmp_count' => $tmp['count'],
            'tmp_size' => $check->humanBytes($tmp['bytes']),
            'tmp_files' => $tmp['files'],
            'error' => $error,
            'success' => $success,
        ], 'layouts/admin');
    }

    public function purgeTmp(Request $request): void
    {
        $this->auth->requireAuth();

        if (!$this->validCsrf($request)) {
            $_SESSION['_admin_error'] = 'Votre session a expiré. Merci de réessayer.';
            Response::redirect(route('admin.system'))->send();
            exit;
        }

        $check = app(SystemCheck::class);
        $result = $check->purgeTmp();
        $remaining = $check->tmpFiles();

        if ($result['count'] > 0) {
            $message = $result['count'] . ' fichier' . ($result['count'] > 1 ? 's' : '')
                . ' temporaire' . ($result['count'] > 1 ? 's' : '')
                . ' supprimé' . ($result['count'] > 1 ? 's' : '')
                . ' (' . $check->humanBytes($result['bytes']) . ' libérés). Les compteurs de limitation et le quota d\'envoi repartent de zéro.';
            if ($remaining['count'] > 0) {
                $message .= ' ' . $remaining['count'] . ' fichier' . ($remaining['count'] > 1 ? 's' : '')
                    . ' non supprimé' . ($remaining['count'] > 1 ? 's' : '') . ' (droits insuffisants).';
            }
            $_SESSION['_admin_success'] = $message;
        } elseif ($remaining['count'] > 0) {
            $_SESSION['_admin_error'] = 'Impossible de supprimer les fichiers temporaires (droits d\'écriture sur storage/tmp manquants).';
        } else {
            $_SESSION['_admin_success'] = 'Aucun fichier temporaire à supprimer.';
        }

        Response::redirect(route('admin.system'))->send();
        exit;
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
            $safeFromName = $mailer->sanitizeHeader($form['from_name']);
            $headerString = implode("\r\n", [
                "From: {$safeFromName} <{$form['from']}>",
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