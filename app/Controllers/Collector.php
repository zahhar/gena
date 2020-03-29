<?php namespace App\Controllers;

use App\Libraries\Ggamaur;
use App\Libraries\Volketswiler;
use App\Libraries\Meteoswiss;

class Collector extends BaseController
{
	public function ggamaur($n = 10)
	{
        $n = (int)$n;

        if ($n > 0 && $n <= 100) {
            $g = new Ggamaur();
            $tickets = $g->getTickets($n);
            echo '<pre>';
            print_r($tickets);
            echo '</pre>';

        } else {
            die("Out of range, should be [1..100]");
        }
	}

    public function volketswiler()
    {
        $v = new Volketswiler();

        $news = $v->getTopNews();
        echo '<pre>';
        print_r($news);
        echo '</pre>';
    }

    public function meteoswiss($zip)
    {
        $m = new Meteoswiss();

        $zip = (int)$zip;

        if($zip > 0) {
            $warnings = $m->getWarnings($zip);
            echo '<pre>';
            print_r($warnings);
            echo '</pre>';            
        } else {
            die("ZIP expected in format /dddd ");
        }
    }
}