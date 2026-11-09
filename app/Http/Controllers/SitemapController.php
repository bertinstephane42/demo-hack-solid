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
        $this->logVisit();

        return $this->view('sitemap', [
            'title' => 'Plan du site cours-reseaux.fr',
            'year' => \date('Y'),
            'menu' => $this->menu,
        ], 'layouts/sitemap');
    }

    /**
     * Journalise une consultation du plan du site (KISS : une ligne par visite).
     */
    protected function logVisit(): void
    {
        $ip = \Core\Request::clientIp();
        $ua = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 120);
        $ref = substr((string) ($_SERVER['HTTP_REFERER'] ?? ''), 0, 200);
        $line = date('Y-m-d H:i:s')
            . ' ip=' . $ip
            . ' ua="' . str_replace('"', '', $ua) . '"'
            . ' ref="' . str_replace('"', '', $ref) . "\"\n";

        $path = __DIR__ . '/../../../storage/logs/sitemap.log';
        @file_put_contents($path, $line, FILE_APPEND | LOCK_EX);
    }
}
