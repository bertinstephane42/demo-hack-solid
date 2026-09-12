<?php

namespace App\Http\Controllers;

use Core\Request;
use Core\Response;
use App\Services\Validator;
use App\Services\Mailer;

class ContactController extends Controller
{
    protected Validator $validator;
    protected Mailer $mailer;

    public function __construct(Validator $validator, Mailer $mailer)
    {
        parent::__construct();
        $this->validator = $validator;
        $this->mailer = $mailer;
    }

    public function show(): string
    {
        $error = $_SESSION['_contact_error'] ?? null;
        $success = $_SESSION['_contact_success'] ?? null;
        $old = $_SESSION['_old'] ?? [];

        unset($_SESSION['_contact_error'], $_SESSION['_contact_success'], $_SESSION['_old']);

        $formToken = \bin2hex(\random_bytes(16));
        $formTime = \time();
        $_SESSION['_contact_token'] = $formToken;
        $_SESSION['_contact_time'] = $formTime;

        $captchaA = \random_int(1, 9);
        $captchaB = \random_int(1, 9);
        $lastA = $_SESSION['_contact_captcha_op_a'] ?? null;
        $lastB = $_SESSION['_contact_captcha_op_b'] ?? null;
        while ($captchaA === $lastA && $captchaB === $lastB) {
            $captchaA = \random_int(1, 9);
            $captchaB = \random_int(1, 9);
        }
        $_SESSION['_contact_captcha_op_a'] = $captchaA;
        $_SESSION['_contact_captcha_op_b'] = $captchaB;
        $_SESSION['_contact_captcha'] = $captchaA + $captchaB;

        return $this->view('contact', [
            'title' => 'Contact — Cours-Réseaux',
            'year' => \date('Y'),
            'error' => $error,
            'success' => $success,
            'old' => $old,
            'form_token' => $formToken,
            'form_time' => $formTime,
            'captcha_a' => $captchaA,
            'captcha_b' => $captchaB,
        ], 'layouts/contact');
    }

    public function submit(Request $request): void
    {
        unset($_SESSION['_contact_error'], $_SESSION['_contact_success'], $_SESSION['_old']);

        $sessionToken = $_SESSION['_contact_token'] ?? null;
        $sessionTime = (int) ($_SESSION['_contact_time'] ?? 0);
        $sessionCaptcha = $_SESSION['_contact_captcha'] ?? null;
        unset($_SESSION['_contact_token'], $_SESSION['_contact_time'], $_SESSION['_contact_captcha']);

        $token = $request->body('_token');
        $time = (int) $request->body('_time', 0);
        $honeypot = $request->body('website');
        $captchaAnswer = $request->body('captcha');

        $validToken = ($token !== null && \hash_equals((string) $sessionToken, (string) $token));
        $elapsed = \time() - $sessionTime;
        $timingOk = $sessionTime > 0 && $elapsed >= 3;
        $validCaptcha = ($sessionCaptcha !== null && (int) $captchaAnswer === (int) $sessionCaptcha);
        $isBot = trim((string) $honeypot) !== '';
        $isHuman = $validToken && $timingOk && !$isBot;

        $this->logContact(sprintf(
            'gate token=%s elapsed=%ds honeypot=%s captcha=%s human=%s',
            $validToken ? 'ok' : 'fail',
            $elapsed,
            $isBot ? 'filled' : 'empty',
            $validCaptcha ? 'ok' : 'fail',
            $isHuman ? 'yes' : 'no'
        ));

        if (!$isHuman) {
            if ($isBot) {
                $_SESSION['_contact_success'] = true;
            } else {
                $_SESSION['_contact_error'] = 'Votre session a expiré. Merci de recharger la page et de réessayer.';
            }
            Response::redirect(route('contact'))->send();
            exit;
        }

        if (!$validCaptcha) {
            $_SESSION['_contact_error'] = 'Le résultat du calcul est incorrect. Votre message n\'a pas été envoyé, merci de réessayer.';
            Response::redirect(route('contact'))->send();
            exit;
        }

        $copyRequested = (bool) $request->body('copy');
        $copyEmail = \trim((string) $request->body('copy_email'));

        $validated = $this->validator->validate(
            [
                'name' => $request->body('name'),
                'email' => $request->body('email'),
                'message' => $request->body('message'),
            ],
            [
                'name' => 'required|min:6|max:120',
                'email' => 'required|email|max:254',
                'message' => 'required|min:7|max:5000',
            ]
        );

        if (!$validated) {
            $_SESSION['_contact_error'] = $this->validator->getError('name')
                ?? $this->validator->getError('email')
                ?? $this->validator->getError('message')
                ?? 'Les informations fournies ne sont pas valides.';
            $_SESSION['_old'] = [
                'name' => $request->body('name'),
                'email' => $request->body('email'),
                'copy' => $copyRequested,
                'copy_email' => $copyEmail,
            ];
            Response::redirect(route('contact'))->send();
            exit;
        }

        if ($copyRequested) {
            $copyValidated = $this->validator->validate(
                ['copy_email' => $copyEmail],
                ['copy_email' => 'required|email|max:254']
            );
            if (!$copyValidated) {
                $_SESSION['_contact_error'] = 'Pour recevoir une copie, renseignez une adresse mail valide.';
                $_SESSION['_old'] = [
                    'name' => $request->body('name'),
                    'email' => $request->body('email'),
                    'copy' => true,
                    'copy_email' => $copyEmail,
                ];
                Response::redirect(route('contact'))->send();
                exit;
            }
        }

        $name = \trim($request->body('name'));
        $email = \trim($request->body('email'));
        $message = \trim($request->body('message'));

        $copyRelation = null;
        if ($copyRequested) {
            $copyRelation = \strcasecmp($copyEmail, $email) === 0 ? 'same' : 'different';
        }

        $sent = $this->mailer->contact($name, $email, $message, $copyRequested ? $copyEmail : null);
        $this->logContact('mail result=' . var_export($sent, true)
            . ' error=' . $this->mailer->lastError
            . ' to=' . config('mail.to', 'contact@cours-reseaux.fr')
            . ' from=' . config('mail.from', 'contact@cours-reseaux.fr')
            . ' copy=' . ($copyRequested ? 'yes' : 'no')
            . ($copyRequested ? ' copy_relation=' . $copyRelation . ' copy_email=' . $copyEmail : ''));

        // Sauvegarde systématique (même en cas d'échec d'envoi) : la demande
        // n'est jamais perdue (storage/logs/contact.log, inaccessible via HTTP).
        backup_contact_request([
            'type' => 'contact',
            'name' => $name,
            'email' => $email,
            'message' => $message,
            'copy' => $copyRequested,
            'copy_email' => $copyRequested ? $copyEmail : null,
            'mail' => $sent ? 'sent' : 'failed',
            'error' => $sent ? '' : $this->mailer->lastError,
        ]);

        if ($sent) {
            $_SESSION['_contact_success'] = true;
        } else {
            $_SESSION['_contact_error'] = "L'envoi du message a échoué. Merci de réessayer ultérieurement.";
        }

        Response::redirect(route('contact'))->send();
        exit;
    }

    protected function logContact(string $message): void
    {
        if (!(bool) config('mail.log_enabled', false)) {
            return;
        }

        $message = str_replace(["\r", "\n"], ' ', $message);
        $dir = __DIR__ . '/../../../storage/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents($dir . '/contact.log', date('Y-m-d H:i:s') . ' ' . $message . "\n", FILE_APPEND | LOCK_EX);
    }
}
