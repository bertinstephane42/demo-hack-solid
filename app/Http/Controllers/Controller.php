<?php

namespace App\Http\Controllers;

use Core\View;

class Controller
{
    protected View $view;

    public function __construct()
    {
        $this->view = app(View::class);
    }

    protected function view(string $name, array $data = [], ?string $layout = 'layouts/app'): string
    {
        return $this->view->render($name, $data, $layout);
    }
}
