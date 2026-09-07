<?php
namespace App\Controllers;

use App\Core\View;

final class PublicPagesController
{
    public function contact(): void
    {
        View::render('site/contact', ['title' => 'Contact', 'active' => 'contact'], 'site');
    }

    public function company(): void
    {
        View::render('site/company', ['title' => 'Solutions entreprises', 'active' => 'company'], 'site');
    }
}
