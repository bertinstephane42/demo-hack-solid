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
        return $this->view('contact', [
            'title' => 'Contact — Cours-Réseaux',
            'year' => \date('Y'),
            'error' => $_SESSION['_contact_error'] ?? null,
            'success' => $_SESSION['_contact_success'] ?? null,
            'old' => $_SESSION['_old'] ?? [],
        ], 'layouts/contact');
    }

    public function submit(Request $request): void
    {
        unset($_SESSION['_contact_error'], $_SESSION['_contact_success'], $_SESSION['_old']);

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
            \header('Location: ' . route('contact'));
            return;
        }

        $name = \trim($request->body('name'));
        $email = \trim($request->body('email'));
        $message = \trim($request->body('message'));

        if ($this->mailer->contact($name, $email, $message)) {
            $_SESSION['_contact_success'] = true;
        } else {
            $_SESSION['_contact_error'] = "L'envoi du message a échoué. Merci de réessayer ultérieurement.";
        }

        \header('Location: ' . route('contact'));
    }
}
