<?php

namespace App\Services;

class Mailer
{
    protected string $from;
    protected string $to;
    protected string $fromName;

    public function __construct(string $from, string $to, string $fromName = '')
    {
        $this->from = $from;
        $this->to = $to;
        $this->fromName = $fromName;
    }

    public function send(string $subject, string $body, array $headers = []): bool
    {
        $defaultHeaders = [];
        $defaultHeaders[] = "From: {$this->fromName} <{$this->from}>";
        $defaultHeaders[] = "MIME-Version: 1.0";
        $defaultHeaders[] = "Content-Type: text/plain; charset=UTF-8";

        $allHeaders = array_merge($defaultHeaders, $headers);
        $headerString = implode("\r\n", $allHeaders);

        return mail($this->to, $subject, $body, $headerString);
    }

    public function contact(string $name, string $email, string $message): bool
    {
        $subject = "Nouveau message depuis Cours-Reseaux.fr";
        $body = "Nouveau message envoyé depuis le site :\n\n";
        $body .= "Nom : {$name}\n";
        $body .= "Email : {$email}\n\n";
        $body .= "Message :\n{$message}\n";

        return $this->send($subject, $body, [
            "Reply-To: {$name} <{$email}>",
        ]);
    }

    public function setFrom(string $from): self
    {
        $this->from = $from;
        return $this;
    }

    public function setTo(string $to): self
    {
        $this->to = $to;
        return $this;
    }

    public function setFromName(string $name): self
    {
        $this->fromName = $name;
        return $this;
    }
}
