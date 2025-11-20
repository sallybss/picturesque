<?php
class Paths {
    public string $baseUrl;
    public string $uploads;
    public string $images;

    public function __construct() {
        $this->baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
        $this->uploads = $this->baseUrl . '/uploads/';
        $this->images  = $this->baseUrl . '/images/';
    }
}
