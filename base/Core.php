<?php

namespace base;

use base\Helpers as H;
use components\CJSON;
use Goutte\Client;
use Symfony\Component\HttpClient\HttpClient;

class Core
{
    public $client;
    public $baseUrl;
    public $rawTitle;
    public $cleanTitle;
    public $animeUrl;
    public $animeImageUrl;
    public $episodeListUrl;
    public $episodeList;
    public $animeId;
    public $episodeStart;
    public $episodeEnd;

    public function __construct()
    {
        $this->baseUrl = 'https://www4.gogoanimehub.tv';
        $this->rawTitle = 'NATSUNAGU!';
        $this->episodeStart = 1;
        $this->episodeEnd = 1;
        $this->client = new Client(HttpClient::create(['timeout' => 60]));
    }

    public function scrape()
    {
        $this->cleanTitle = strtolower(
            preg_replace(
                "/[^A-Za-z0-9\-]/",
                '',
                str_replace(' ', '-', $this->rawTitle)
            )
        );

        $this->animeUrl = $this->baseUrl . '/category/' . $this->cleanTitle;

        $getAnime = $this->client->request('GET', $this->animeUrl);
        $this->animeId = H::extractNode($getAnime, 'input#movie_id', ['value']);
        $this->animeImageUrl = H::extractNode($getAnime, 'div.anime_info_body_bg > img', ['src']);
        $this->episodeStart = $this->episodeStart ? $this->episodeStart : $getAnime->filter('#episode_page a.active')->attr('ep_start');
        $this->episodeEnd = $this->episodeEnd ? $this->episodeEnd : $getAnime->filter('#episode_page a.active')->attr('ep_end');
        $this->episodeListUrl = $this->baseUrl . "/load-list-episode?ep_start={$this->episodeStart}&ep_end={$this->episodeEnd}&id={$this->animeId}";

        $getEps = $this->client->request('GET', $this->episodeListUrl);
        $getEps->filter('a')->each(function ($node) {
            $epUrl = H::extractNode($node, 0, ['href']);
            if (strpos($epUrl, $this->cleanTitle)) {
                $epName = $node->filter('div.name')->text();
                $epUrl = $this->baseUrl . str_replace(' ', '', $epUrl);
                $this->episodeList[$epName]['url'] = $epUrl;
            }
        });
        ksort($this->episodeList, SORT_NATURAL);

        foreach ($this->episodeList as $k => $v) {
            if (strpos($v['url'], $this->cleanTitle)) {
                $this->getEpisodeData($v['url'], $k);
            }
        }

        $this->displayResult();
    }

    private function getEpisodeData($url, $k)
    {
        $getLinks = $this->client->request('GET', $url);
        $getLinks->filter('a[data-video]')->each(function ($node) use ($k) {
            $provider = strtolower($node->getNode(0)->firstChild->nodeValue);
            $this->episodeList[$k]['stream_link'][$provider] = H::extractNode($node, 0, ['data-video']);
        });
    }

    public function displayResult()
    {
        echo "<h2><a target='_blank' href='{$this->animeUrl}'>{$this->rawTitle}</a></h2>";
        echo "<img style='height: 280px;' src='{$this->animeImageUrl}'>";
        foreach ($this->episodeList as $ep => $data) {
            echo "<h3><a target='_blank' href='{$data['url']}'>{$ep}</a></h3>";
            foreach (H::getVal($data, 'stream_link') as $stream => $link) {
                $getStream = $this->client->request('GET', $link);
                preg_match_all('/\{file\:(?:[^{}]|(?R))*\}/x', preg_replace('/\s+/', '', $getStream->html()), $matches);
                foreach ($matches[0] as $mat) {
                    $mat = (new CJSON)->decode($mat);
                    if (($video_url = H::getVal($mat, 'file')) and strpos($video_url, 'videoplayback')) {
                        echo "<a target='_blank' href='{$video_url}'>Download from {$stream}</a></br>";
                        break;
                    }
                }
            }
        }
    }
}
