<?php
return function(PDO $db): void {
    $db->exec("CREATE TABLE IF NOT EXISTS seo_pages (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        path VARCHAR(255) NOT NULL UNIQUE,
        title VARCHAR(190) NULL,
        description VARCHAR(320) NULL,
        keywords VARCHAR(500) NULL,
        canonical_url VARCHAR(500) NULL,
        robots_index TINYINT(1) NOT NULL DEFAULT 1,
        robots_follow TINYINT(1) NOT NULL DEFAULT 1,
        og_title VARCHAR(190) NULL,
        og_description VARCHAR(320) NULL,
        og_image VARCHAR(500) NULL,
        twitter_card VARCHAR(40) NOT NULL DEFAULT 'summary_large_image',
        schema_json LONGTEXT NULL,
        sitemap_priority DECIMAL(2,1) NOT NULL DEFAULT 0.5,
        sitemap_changefreq VARCHAR(20) NOT NULL DEFAULT 'weekly',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_seo_indexable (robots_index)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS visitor_sessions (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        visitor_uuid CHAR(32) NOT NULL,
        session_uuid CHAR(32) NOT NULL UNIQUE,
        user_id BIGINT UNSIGNED NULL,
        is_returning TINYINT(1) NOT NULL DEFAULT 0,
        first_seen DATETIME NOT NULL,
        last_seen DATETIME NOT NULL,
        landing_path VARCHAR(500) NOT NULL,
        referrer VARCHAR(1000) NULL,
        utm_source VARCHAR(190) NULL,
        utm_medium VARCHAR(190) NULL,
        utm_campaign VARCHAR(190) NULL,
        utm_term VARCHAR(190) NULL,
        utm_content VARCHAR(190) NULL,
        ip_address VARCHAR(45) NULL,
        remote_port INT UNSIGNED NULL,
        country_code CHAR(2) NULL,
        country_name VARCHAR(120) NULL,
        region_name VARCHAR(190) NULL,
        city VARCHAR(190) NULL,
        latitude DECIMAL(10,7) NULL,
        longitude DECIMAL(10,7) NULL,
        timezone VARCHAR(100) NULL,
        device_type VARCHAR(40) NULL,
        browser VARCHAR(100) NULL,
        os VARCHAR(100) NULL,
        user_agent TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_visitor_uuid (visitor_uuid),
        INDEX idx_session_dates (first_seen,last_seen),
        INDEX idx_country (country_code),
        INDEX idx_device (device_type),
        CONSTRAINT fk_visitor_sessions_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS visitor_pageviews (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        visitor_uuid CHAR(32) NOT NULL,
        session_uuid CHAR(32) NOT NULL,
        user_id BIGINT UNSIGNED NULL,
        path VARCHAR(500) NOT NULL,
        query_string VARCHAR(1000) NULL,
        referrer VARCHAR(1000) NULL,
        page_title VARCHAR(255) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_pageview_session (session_uuid),
        INDEX idx_pageview_visitor (visitor_uuid),
        INDEX idx_pageview_date (created_at),
        INDEX idx_pageview_path (path(190)),
        CONSTRAINT fk_visitor_pageviews_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS visitor_geo_cache (
        ip_address VARCHAR(45) PRIMARY KEY,
        country_code CHAR(2) NULL,
        country_name VARCHAR(120) NULL,
        region_name VARCHAR(190) NULL,
        city VARCHAR(190) NULL,
        latitude DECIMAL(10,7) NULL,
        longitude DECIMAL(10,7) NULL,
        timezone VARCHAR(100) NULL,
        resolved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
};
