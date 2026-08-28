<?php
namespace App\Controllers;

use App\Core\View;

final class SiteController
{
    private function trainingData(): array
    {
        return [
            ['slug'=>'sous-gerant-station-service','title'=>'Le Métier de Sous-Gérant en Station-Service','sector'=>'Aval pétrolier','mode'=>'Présentiel','duration'=>'4 jours','price'=>75000,'tone'=>'navy'],
            ['slug'=>'depotage-carburant','title'=>'Le Dépotage du Carburant en Station-Service','sector'=>'Aval pétrolier','mode'=>'En ligne','duration'=>'8 heures','price'=>45000,'tone'=>'gold'],
            ['slug'=>'graissage-lubrifiants','title'=>'Graissage et Vente des Lubrifiants','sector'=>'Aval pétrolier','mode'=>'Mixte','duration'=>'3 jours','price'=>50000,'tone'=>'green'],
            ['slug'=>'installation-solaire','title'=>'Installation et Maintenance Solaire','sector'=>'Énergie solaire','mode'=>'Présentiel','duration'=>'5 jours','price'=>95000,'tone'=>'sun'],
            ['slug'=>'techniques-vente','title'=>'Techniques de Vente et Relation Client','sector'=>'Commerce & Marketing','mode'=>'En ligne','duration'=>'6 heures','price'=>35000,'tone'=>'coral'],
            ['slug'=>'excel-professionnel','title'=>'Excel Professionnel & Tableaux de Bord','sector'=>'Informatique','mode'=>'Mixte','duration'=>'3 jours','price'=>60000,'tone'=>'blue'],
        ];
    }

    private function products(): array
    {
        return [
            ['slug'=>'pompe-carburant','title'=>'Pompe à carburant','stock'=>'En stock','price'=>null,'tone'=>'navy'],
            ['slug'=>'pistolet-distribution','title'=>'Pistolet de distribution','stock'=>'En stock','price'=>85000,'tone'=>'gold'],
            ['slug'=>'sabre-de-jauge','title'=>'Sabre de jauge','stock'=>'Sur commande','price'=>45000,'tone'=>'steel'],
            ['slug'=>'pate-kolor-kut','title'=>'Pâte Kolor Kut','stock'=>'En stock','price'=>18000,'tone'=>'coral'],
        ];
    }

    public function home(): void { View::render('site/home', ['title'=>'Accueil','active'=>'home'], 'site'); }
    public function trainings(): void { View::render('site/trainings', ['title'=>'Nos formations','active'=>'trainings','trainings'=>$this->trainingData()], 'site'); }
    public function training(): void { View::render('site/training', ['title'=>'Le Métier de Sous-Gérant','active'=>'trainings'], 'site'); }
    public function shop(): void { View::render('site/shop', ['title'=>'Boutique équipements','active'=>'shop','products'=>$this->products()], 'site'); }
    public function product(): void { View::render('site/product', ['title'=>'Sabre de jauge','active'=>'shop'], 'site'); }
    public function cart(): void { View::render('site/cart', ['title'=>'Votre panier','active'=>'cart'], 'site'); }
    public function account(): void { View::render('site/account', ['title'=>'Mon espace IFMAP','active'=>'account'], 'site'); }
    public function addCart(): void
    {
        $type = ($_POST['type'] ?? '') === 'product' ? 'product' : 'training';
        $_SESSION['cart'][$type] = ($type === 'product')
            ? ['name'=>'Sabre de jauge','quantity'=>max(1, (int)($_POST['quantity'] ?? 1)),'price'=>45000]
            : ['name'=>'Le Métier de Sous-Gérant en Station-Service','quantity'=>1,'price'=>75000];
        $base = rtrim(str_replace('/index.php', '', str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php')), '/');
        header('Location: ' . $base . '/panier'); exit;
    }
}
