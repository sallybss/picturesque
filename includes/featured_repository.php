<?php
require_once __DIR__ . '/db_class.php';

class FeaturedRepository
{
    /** @var mysqli */
    private $db;

    public function __construct()
    {
        $this->db = DB::get(); 
    }

    private function mondayOfWeek(?DateTime $d = null): string
    {
        $d ??= new DateTime('today');
        $d->modify('monday this week');
        return $d->format('Y-m-d');
    }

    public function listForWeek(?DateTime $d = null): array
    {
        $week = $this->mondayOfWeek($d);

        $sql = "
            SELECT
              p.picture_id   AS pic_id,
              p.picture_title AS pic_title,
              p.picture_url   AS pic_url
            FROM featured_pictures f
            JOIN pictures p ON p.picture_id = f.picture_id
            WHERE f.week_start = ?
            ORDER BY f.id ASC
            LIMIT 10
        ";

        $st = $this->db->prepare($sql);
        $st->bind_param('s', $week);
        $st->execute();
        $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
        $st->close();

        return $rows;
    }

    public function replaceWeekSelection(array $pictureIds, int $adminProfileId, ?DateTime $week = null): void
    {
        $weekStart = $this->mondayOfWeek($week);

        $this->db->begin_transaction();
        try {
            $del = $this->db->prepare("DELETE FROM featured_pictures WHERE week_start = ?");
            $del->bind_param('s', $weekStart);
            $del->execute();
            $del->close();

            $ins = $this->db->prepare("
                INSERT INTO featured_pictures (picture_id, week_start, created_by)
                VALUES (?, ?, ?)
            ");

            $count = 0;
            foreach ($pictureIds as $pid) {
                if (++$count > 10) break;
                $pid = (int)$pid;
                $ins->bind_param('isi', $pid, $weekStart, $adminProfileId);
                $ins->execute();
            }
            $ins->close();

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }
}
