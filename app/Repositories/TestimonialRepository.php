<?php
namespace App\Repositories;

use App\Core\Database;
use App\Services\R2Storage;

final class TestimonialRepository
{
    public static function approvedForHome(int $limit = 6): array
    {
        try {
            $limit = max(1, min(12, $limit));
            $rows = Database::connection()->query("SELECT vt.*,u.name,u.avatar,c.title course_title FROM video_testimonials vt JOIN users u ON u.id=vt.user_id LEFT JOIN courses c ON c.id=vt.course_id WHERE vt.status='approved' ORDER BY vt.featured DESC,vt.sort_order ASC,vt.approved_at DESC,vt.id DESC LIMIT ".$limit)->fetchAll();
            $storage = new R2Storage();
            foreach ($rows as &$row) {
                $row['video_url'] = null;
                try { $row['video_url'] = $storage->playbackUrl((string)$row['video_path']); } catch (\Throwable) {}
            }
            unset($row);
            return array_values(array_filter($rows, fn($row)=>!empty($row['video_url'])));
        } catch (\Throwable) {
            return [];
        }
    }
}
