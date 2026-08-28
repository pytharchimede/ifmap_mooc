<?php
namespace App\Controllers;

use App\Core\View;
use App\Support\DemoData;

final class DashboardController
{
    public function index(): void { View::render('dashboard', ['title'=>'Tableau de bord','active'=>'dashboard','courses'=>DemoData::courses()]); }
    public function catalog(): void { View::render('catalog', ['title'=>'Catalogue des cours','active'=>'catalog','courses'=>DemoData::courses()]); }
    public function course(): void { View::render('course', ['title'=>'Pilotage de la performance publique','active'=>'courses','modules'=>DemoData::modules()], 'course'); }
}

