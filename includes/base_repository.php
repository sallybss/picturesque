<?php

abstract class BaseRepository
{
    protected $db;

    public function __construct()
    {
        $this->db = DB::get();
    }
}
