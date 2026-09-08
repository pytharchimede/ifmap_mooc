<?php
return function(PDO $db): void {
    $db->exec("CREATE TABLE IF NOT EXISTS home_carousel_slides (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        position INT UNSIGNED NOT NULL DEFAULT 0,
        eyebrow VARCHAR(190) NOT NULL,
        title VARCHAR(255) NOT NULL,
        highlighted_text VARCHAR(190) NULL,
        description TEXT NULL,
        image_path VARCHAR(500) NULL,
        primary_label VARCHAR(120) NULL,
        primary_url VARCHAR(500) NULL,
        secondary_label VARCHAR(120) NULL,
        secondary_url VARCHAR(500) NULL,
        stat_1_value VARCHAR(60) NULL,
        stat_1_label VARCHAR(120) NULL,
        stat_2_value VARCHAR(60) NULL,
        stat_2_label VARCHAR(120) NULL,
        stat_3_value VARCHAR(60) NULL,
        stat_3_label VARCHAR(120) NULL,
        status ENUM('active','inactive') NOT NULL DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_home_carousel_status(status,position)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    if ((int)$db->query("SELECT COUNT(*) FROM home_carousel_slides")->fetchColumn() === 0) {
        $slides = [
            [1,'INSTITUT DE FORMATION AUX MÉTIERS','Se former. Se faire accompagner.','S’équiper.','240 thèmes de formation sur 6 filières, une académie en ligne et des équipements professionnels — réunis sur une seule plateforme.','','Découvrir les formations','/formations','Voir la boutique','/boutique','240+','Thèmes de formation','6','Filières métiers','15 ans','D’expertise terrain'],
            [2,'MENTORAT & COACHING IFMAP','Accélérez votre évolution avec','un mentor qualifié.','Choisissez un accompagnement professionnel, planifiez vos séances et profitez du coaching en direct dans l’écosystème IFMAP.','','Trouver un mentor','/mentorat','Accéder à l’académie','/academie','1:1','Accompagnement','100%','Paiement en ligne','Pro','Experts validés'],
            [3,'ÉQUIPEMENTS PROFESSIONNELS','Formez-vous et équipez','votre activité.','Retrouvez les équipements essentiels des métiers de l’aval pétrolier et des solutions adaptées aux besoins des professionnels.','','Voir les équipements','/boutique','Solutions entreprises','/entreprise','Pro','Équipements métier','CI','Service local','IFMAP','Une plateforme']
        ];
        $st=$db->prepare("INSERT INTO home_carousel_slides(position,eyebrow,title,highlighted_text,description,image_path,primary_label,primary_url,secondary_label,secondary_url,stat_1_value,stat_1_label,stat_2_value,stat_2_label,stat_3_value,stat_3_label) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        foreach($slides as $slide)$st->execute($slide);
    }

    foreach([
        ['home_carousel_enabled','1','homepage'],
        ['home_carousel_autoplay_ms','6500','homepage']
    ] as $s){$st=$db->prepare("INSERT IGNORE INTO settings(`key`,`value`,`group`) VALUES(?,?,?)");$st->execute($s);}
};
