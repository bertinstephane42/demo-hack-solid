<?php

namespace App\Http\Controllers;

use App\Models\SitemapMenu;

class SitemapController extends Controller
{
    protected SitemapMenu $menu;

    public function __construct()
    {
        parent::__construct();
        $this->menu = new SitemapMenu();
    }

    public function index(): string
    {
        return $this->view('sitemap', [
            'title' => 'Plan du site cours-reseaux.fr',
            'year' => \date('Y'),
            'menu' => $this->menu,
        ]);
    }
}
