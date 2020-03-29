<?php namespace App\Libraries;

use Goutte\Client as gClient;
use CodeIgniter\I18n\Time;

class Ggamaur
{
    const BASE_URL = 'https://www.gga-maur.ch/iframe/Support_Ereignisuebersicht.php';

    public function getTickets(int $amount = 10, int $offset = 0): array
    {
        $tickets = [];
        $gclient = new gClient();

        $page = $gclient->request('GET', self::BASE_URL.'?startline='.$offset.'&numlines='.$amount);
        
        $page->filter('a')->each(
            function ($node) use (&$tickets) {
                $data = $node->extract(['onclick']);
                    
                if(preg_match('/^DisplayTicket\((\d{4,6})\,\d{1,3}\,\d{1,3}\)$/ix', $data[0], $ticketId)) {
                    $tickets[] = $this->parseTicket($ticketId[1]);
                }
            });

        return $tickets;
    }

    // PRIVATE FUNCTIONS --------------------------------------------------------------------

    private function parseTicket(int $id): array
    {
        $ticket = ['ref' => null, 'title' => null, 'status' => null, 'location' => null, 'zip' => null, 'summary' => null, 'timespan' => null, 'from' => null, 'till' => null, 'url' => null];

        $ticket['ref'] = $id;
        $ticket['url'] = self::BASE_URL.'?ticket='.$id;

        $gclient = new gClient();

        $page = $gclient->request('GET', $ticket['url']);

        $page->filter('body > span.Lauftext01 > table')->eq(1)->children('tr > td')->each(
            function ($node, $i) use (&$ticket) {
                $content = $node->html();
                if ($node->attr('bgcolor')) {
                    $ticket['title'] = $node->text();
                } else {
                    $matches = [];
                    if(preg_match('/^<b>Status:<\/b>(.+)$/mixsu', $content, $matches)) {
                        $ticket['status'] = trim($matches[1]);
                    } elseif (preg_match('/^<b>Datum:<\/b>(.+)$/mixsu', $content, $matches)) {
                        $ticket['timespan'] = trim($matches[1]);
                        list($ticket['from'], $ticket['till']) = $this->parseTimespan($ticket['timespan']);
                    } elseif (preg_match('/^<b>Betrifft:<\/b>(.+)$/mixsu', $content, $matches)) {
                        $ticket['location'] = trim($matches[1]);
                        $ticket['zip'] = $this->parseLocation($ticket['location']);
                    } elseif(trim($node->text())) {
                        $ticket['summary'] = str_replace(["\n", "\r"], '', $content);
                    }
                }
                
        });
        return $ticket;
    }

    private function parseTimespan(string $timespan): array 
    {
        $from = $till = null;

        //02.04.2020 (02:00 - 03:00)
        $p0 = '(\d{2}.\d{2}.\d{4})\s+\((\d{2}:\d{2})\s+-\s+(\d{2}:\d{2})\)';

        //10.03.2020 (18:00) - 11.03.2020 (10:00)
        $p1 = '(\d{2}.\d{2}.\d{4})\s+\((\d{2}:\d{2})\)\s+-\s+(\d{2}.\d{2}.\d{4})\s+\((\d{2}:\d{2})\)';

        //03.11.2019 (03:00) -   ( )
        $p2 = '(\d{2}.\d{2}.\d{4})\s+\((\d{2}:\d{2})\)';

        if (preg_match("/$p0/mixsu", $timespan, $matches)) {
            $from = Time::createFromFormat('d.m.Y H:i', $matches[1].' '.$matches[2])->toDateTimeString();
            $till = Time::createFromFormat('d.m.Y H:i', $matches[1].' '.$matches[3])->toDateTimeString();      
        } elseif (preg_match("/$p1/mixsu", $timespan, $matches)) {
            $from = Time::createFromFormat('d.m.Y H:i', $matches[1].' '.$matches[2])->toDateTimeString();
            $till = Time::createFromFormat('d.m.Y H:i', $matches[3].' '.$matches[4])->toDateTimeString();      
        } elseif (preg_match("/$p2/mixsu", $timespan, $matches)) {
            $from = Time::createFromFormat('d.m.Y H:i', $matches[1].' '.$matches[2])->toDateTimeString();
        }

        return [$from, $till]; 
    }

    private function parseLocation(string $location): ?string 
    {
        $zip = null;

        $places = [
            '8603' => 'schwerzenbach',
            '8126' => 'zumikon', 
            '8700' => 'küsnacht',
            '8123' => 'ebmatingen',
            '8706' => 'meilen',
            '8704' => 'herrliberg',
            '8117' => 'fällanden',
            '8127' => 'forch',
            '8617' => 'mönchaltorf',
            '8132' => 'egg',
            '8133' => 'esslingen',
            '8605' => 'greifensee',
            '8122' => 'binz',
            '8610' => 'uster',
            '8124' => 'maur',
        ];

        $location = strtolower($location);
        $location = str_replace([':', ',', '.', '-', ';'], ' ', $location);

        $matches = array_keys(array_intersect($places, explode(' ', $location)));

        $zip = implode(',', $matches);

        return $zip;
    }
}