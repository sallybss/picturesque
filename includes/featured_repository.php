<?php
require_once __DIR__ . '/db_class.php';

class FeaturedRepository
{
    /** @var mysqli */
    private $db;

    public function __construct()
    {
        $this->db = DB::get(); // same helper as other repos (MySQLi)
    }

    // Monday for any given date (defaults to today)
    private function mondayOfWeek(?DateTime $d = null): string
    {
        $d ??= new DateTime('today');
        $d->modify('monday this week');
        return $d->format('Y-m-d');
    }

    /** Return up to 10 featured pictures for the given week (defaults to this week). */
    public function listForWeek(?DateTime $d = null): array
    {
        $week = $this->mondayOfWeek($d);

        $sql = "
            SELECT
              p.picture_id     AS pic_id,
              p.picture_title  AS pic_title,
              p.picture_url    AS pic_url
            FROM featured_pictures f
            JOIN pictures p ON p.picture_id = f.picture_id
            WHERE f.week_start = ?
            ORDER BY f.id ASC
            LIMIT 10
        ";

        // guard in case table doesn't exist locally yet
        try {
            $st = $this->db->prepare($sql);
        } catch (\mysqli_sql_exception $e) {
            return [];
        }

        $st->bind_param('s', $week);
        $st->execute();
        $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
        $st->close();

        return $rows;
    }

    /** Replace the selection for a given week with up to 10 IDs. */
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
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function isFeaturedThisWeek(int $pictureId): bool
    {
        $w = $this->mondayOfWeek();
        $st = $this->db->prepare("SELECT 1 FROM featured_pictures WHERE week_start = ? AND picture_id = ? LIMIT 1");
        $st->bind_param('si', $w, $pictureId);
        $st->execute();
        $ok = (bool)$st->get_result()->fetch_row();
        $st->close();
        return $ok;
    }

    public function countThisWeek(): int
    {
        $w = $this->mondayOfWeek();
        $st = $this->db->prepare("SELECT COUNT(*) FROM featured_pictures WHERE week_start = ?");
        $st->bind_param('s', $w);
        $st->execute();
        $cnt = (int)$st->get_result()->fetch_row()[0];
        $st->close();
        return $cnt;
    }

    public function addThisWeek(int $pictureId, int $adminProfileId): void
    {
        $w = $this->mondayOfWeek();
        $st = $this->db->prepare("INSERT IGNORE INTO featured_pictures (picture_id, week_start, created_by) VALUES (?,?,?)");
        $st->bind_param('isi', $pictureId, $w, $adminProfileId);
        $st->execute();
        $st->close();
    }

    public function removeThisWeek(int $pictureId): void
    {
        $w = $this->mondayOfWeek();
        $st = $this->db->prepare("DELETE FROM featured_pictures WHERE week_start = ? AND picture_id = ?");
        $st->bind_param('si', $w, $pictureId);
        $st->execute();
        $st->close();
    }
}
