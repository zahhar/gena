<?php namespace App\Libraries;

use CodeIgniter\I18n\Time;

class Meteoswiss
{
    const BASE_URL = 'https://app-prod-ws.meteoswiss-app.ch/v1/plzDetail';

    public function getWarnings(int $zip): ?array
    {
        try {
            $data = file_get_contents(self::BASE_URL.'?plz='.$zip.'00');
        } catch(\Exception $e) {
            die("Wrong ZIP code provided: $zip");
        }

        return json_decode($data, true);
    }

}