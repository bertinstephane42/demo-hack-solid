<?php

namespace App\Http\Controllers;

class HomeController extends Controller
{
    public function index(): string
    {
        return $this->view('home', [
            'title' => 'Cours-Réseaux — Formation BTS SIO • CPI • Expert Cyber',
            'year' => \date('Y'),
        ]);
    }
}
