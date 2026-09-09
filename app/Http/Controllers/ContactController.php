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

        return $this->view('contact', [
            'title' => 'Contact — Cours-Réseaux',
            'year' => \date('Y'),
            'error' => $error,
            'success' => $success,
            'old' => $old,
            'form_token' => $formToken,
            'form_time' => $formTime,
        ], 'layouts/contact');
    }

    public function submit(Request $request): void
    {
        unset($_SESSION['_contact_error'], $_SESSION['_contact_success'], $_SESSION['_old']);

        $sessionToken = $_SESSION['_contact_token'] ?? null;
        $sessionTime = (int) ($_SESSION['_contact_time'] ?? 0);
        unset($_SESSION['_contact_token'], $_SESSION['_contact_time']);

        $token = $request->body('_token');
        $time = (int) $request->body('_time', 0);
        $honeypot = $request->body('website');

        $validToken = ($token !== null && \hash_equals((string) $sessionToken, (string) $token));
        $elapsed = \time() - $sessionTime;
        $timingOk = $sessionTime > 0 && $elapsed >= 3;
        $isHuman = $validToken && $timingOk && trim((string) $honeypot) === '';

        if (!$isHuman) {
            $_SESSION['_contact_success'] = true;
            Response::redirect(route('contact'))->send();
            exit;
        }

        $validated = $this->validator->validate(
            [
                'name' => $request->body('name'),
                'email' => $request->body('email'),
                'message' => $request->body('message'),
            ],
            [
                'name' => 'required',
                'email' => 'required|email',
                'message' => 'required',
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
            ];
            Response::redirect(route('contact'))->send();
            exit;
        }

        $name = \trim($request->body('name'));
        $email = \trim($request->body('email'));
        $message = \trim($request->body('message'));

        if ($this->mailer->contact($name, $email, $message)) {
            $_SESSION['_contact_success'] = true;
        } else {
            $_SESSION['_contact_error'] = "L'envoi du message a échoué. Merci de réessayer ultérieurement.";
        }

        Response::redirect(route('contact'))->send();
        exit;
    }
}
