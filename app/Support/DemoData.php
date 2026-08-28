<?php
namespace App\Support;

final class DemoData
{
    public static function courses(): array
    {
        return [
            ['title'=>'Pilotage de la performance publique','category'=>'Management public','progress'=>72,'lessons'=>24,'duration'=>'12h 40min','color'=>'indigo','icon'=>'chart','teacher'=>'Dr. Aminata Koné','next'=>'Indicateurs et tableaux de bord'],
            ['title'=>'Gestion budgétaire et comptable','category'=>'Finance publique','progress'=>38,'lessons'=>18,'duration'=>'9h 15min','color'=>'emerald','icon'=>'wallet','teacher'=>'M. Serge Kouassi','next'=>'Le cycle budgétaire'],
            ['title'=>'Leadership & conduite du changement','category'=>'Développement personnel','progress'=>15,'lessons'=>16,'duration'=>'7h 30min','color'=>'orange','icon'=>'spark','teacher'=>'Mme Nadia Traoré','next'=>'Mobiliser son équipe'],
        ];
    }

    public static function modules(): array
    {
        return [
            ['title'=>'Comprendre la performance publique','duration'=>'42 min','done'=>true,'lessons'=>3],
            ['title'=>'Définir les objectifs stratégiques','duration'=>'1h 15 min','done'=>true,'lessons'=>4],
            ['title'=>'Indicateurs et tableaux de bord','duration'=>'1h 40 min','active'=>true,'lessons'=>5],
            ['title'=>'Mesurer, analyser et corriger','duration'=>'1h 05 min','lessons'=>4],
            ['title'=>'Évaluation finale','duration'=>'30 min','lessons'=>1],
        ];
    }
}

