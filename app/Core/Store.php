<?php
namespace App\Core;

final class Store
{
    private static function path(): string { return dirname(__DIR__, 2) . '/storage/data.json'; }
    public static function all(): array
    {
        if (!is_file(self::path())) self::write(self::defaults());
        $data = json_decode((string)file_get_contents(self::path()), true);
        return is_array($data) ? $data : self::defaults();
    }
    public static function get(string $key): array { return self::all()[$key] ?? []; }
    public static function set(string $key, array $value): void { $data=self::all(); $data[$key]=$value; self::write($data); }
    public static function add(string $key, array $item): array
    {
        $items=self::get($key); $item['id']=$item['id']??bin2hex(random_bytes(4)); $items[]=$item; self::set($key,$items); return $item;
    }
    public static function delete(string $key, string $id): void { self::set($key,array_values(array_filter(self::get($key),fn($i)=>(string)($i['id']??'')!==$id))); }
    private static function write(array $data): void
    {
        $path=self::path(); if(!is_dir(dirname($path))) mkdir(dirname($path),0775,true);
        $tmp=$path.'.tmp'; file_put_contents($tmp,json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE),LOCK_EX); rename($tmp,$path);
    }
    private static function defaults(): array
    {
        return ['courses'=>[
            ['id'=>'c1','slug'=>'sous-gerant-station-service','title'=>'Le Métier de Sous-Gérant en Station-Service','sector'=>'Aval pétrolier','mode'=>'Présentiel','duration'=>'4 jours','price'=>75000,'tone'=>'navy','status'=>'published'],
            ['id'=>'c2','slug'=>'depotage-carburant','title'=>'Le Dépotage du Carburant en Station-Service','sector'=>'Aval pétrolier','mode'=>'En ligne','duration'=>'8 heures','price'=>45000,'tone'=>'gold','status'=>'published'],
            ['id'=>'c3','slug'=>'graissage-lubrifiants','title'=>'Graissage et Vente des Lubrifiants','sector'=>'Aval pétrolier','mode'=>'Mixte','duration'=>'3 jours','price'=>50000,'tone'=>'green','status'=>'published']],
            'products'=>[
            ['id'=>'p1','slug'=>'pompe-carburant','title'=>'Pompe à carburant','stock'=>'En stock','price'=>0,'tone'=>'navy'],
            ['id'=>'p2','slug'=>'pistolet-distribution','title'=>'Pistolet de distribution','stock'=>'En stock','price'=>85000,'tone'=>'gold'],
            ['id'=>'p3','slug'=>'sabre-de-jauge','title'=>'Sabre de jauge','stock'=>'Sur commande','price'=>45000,'tone'=>'steel'],
            ['id'=>'p4','slug'=>'pate-kolor-kut','title'=>'Pâte Kolor Kut','stock'=>'En stock','price'=>18000,'tone'=>'coral']],
            'orders'=>[],'users'=>[['id'=>'u1','name'=>'Aïcha D.','email'=>'aicha@ifmap.ci','role'=>'learner','status'=>'active']]];
    }
}
