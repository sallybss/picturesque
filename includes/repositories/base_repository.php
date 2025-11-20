<?php
require_once __DIR__ . '/../core/db_class.php';

abstract class BaseRepository
{
    protected $db;

    public function __construct()
    {
        $this->db = DB::get();
    }
}
