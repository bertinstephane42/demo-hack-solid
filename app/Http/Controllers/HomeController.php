<?php

namespace App\Http\Controllers;

class HomeController extends Controller
{
    public function index(): string
    {
        return $this->view('home', [
            'title' => 'Cours-Réseaux — Formation BTS SIO • CPI • Expert Cyber',
            'description' => 'Cours-Réseaux.fr — Ressources pédagogiques informatiques (BTS SIO, Bac+3 CPI, Bac+5 Expert Cyber). DokuWiki sécurisé, outils pédagogiques et proxys applicatifs.',
            'year' => \date('Y'),
            'wikiUrl' => config('paths.db_wiki_url', '/bts_sio/doku.php/start'),
        ]);
    }
}
