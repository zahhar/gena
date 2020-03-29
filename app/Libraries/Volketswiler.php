<?php namespace App\Libraries;

use Goutte\Client as gClient;
use CodeIgniter\I18n\Time;

class Volketswiler
{
    const BASE_URL = 'https://volketswilernachrichten.ch';

    public function getTopNews(): array
    {
        $news = [];
        $gclient = new gClient();

        $page = $gclient->request('GET', self::BASE_URL.'/news/');

        $page->filter('.news-list-item .topnews')->each(
            function ($node) use (&$news) {
                $artickle = ['title' => null, 'date' => null, 'url' => null, 'tag' => null, 'summary' => null];

                $artickle['tag'] = $node->filter(".topline")->text();
                $artickle['title'] = $node->filter("h3 > a")->attr('title');

                $href = $node->filter("h3 > a")->attr('href');
                $href = strstr($href, '?', true); //cut params after /?
                $artickle['url'] = self::BASE_URL.$href; 

                $artickle['summary'] = $node->filter(".lead > p")->text();

                $date = $node->filter(".lead > .datetime")->text();
                $artickle['date'] = Time::createFromFormat('d.m.Y', $date)->toDateTimeString();

                $news[] = $artickle;
            });

        return $news;
    }

}