<?php

namespace base;

use base\Helpers as H;
use components\CJSON;
use Goutte\Client;
use Symfony\Component\HttpClient\HttpClient;

class Core
{
    public $client;
    public $appBaseUrl;
    public $idmLocation = 'C:\Program Files (x86)\Internet Download Manager\IDMan.exe';
    public $baseUrl = 'https://www4.gogoanimehub.tv';
    public $rawTitle;
    public $cleanTitle;
    public $animeUrl;
    public $animeImageUrl;
    public $episodeListUrl;
    public $episodeList;
    public $streamList;
    public $streamListUrl;
    public $animeId;
    public $episodeStart;
    public $episodeEnd;

    public function setup()
    {
        $this->client = new Client(HttpClient::create(['timeout' => 60]));

        // TODO : fix url parsing from title
        $this->cleanTitle = strtolower(
            str_replace(' ', '-', preg_replace(
                "/[^A-Za-z0-9]/",
                '-',
                $this->rawTitle)
            )
        );
        $this->cleanTitle = trim(preg_replace('/-+/', '-', $this->cleanTitle), '-');

        $this->animeUrl = $this->baseUrl . '/category/' . $this->cleanTitle;

        $getAnime = $this->client->request('GET', $this->animeUrl);
        if ($this->client->getResponse()->getStatusCode() != '200') {
            echo "Invalid anime link [{$this->animeUrl}]";
            exit;
        }
        $this->animeId = H::extractNode($getAnime, 'input#movie_id', ['value']);
        $this->animeImageUrl = H::extractNode($getAnime, 'div.anime_info_body_bg > img', ['src']);
        $this->episodeStart = $this->episodeStart ? $this->episodeStart : $getAnime->filter('#episode_page a.active')->attr('ep_start');
        $this->episodeEnd = $this->episodeEnd ? $this->episodeEnd : $getAnime->filter('#episode_page a.active')->attr('ep_end');
        $this->episodeListUrl = $this->baseUrl . "/load-list-episode?ep_start={$this->episodeStart}&ep_end={$this->episodeEnd}&id={$this->animeId}";
    }

    public function getAnimeData()
    {
        $this->setup();

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
    }

    public function getAnimeDataShell()
    {
        $this->setup();

        $getEps = $this->client->request('GET', $this->episodeListUrl);
        $getEps->filter('a')->each(function ($node) {
            $epUrl = H::extractNode($node, 0, ['href']);
            if (strpos($epUrl, $this->cleanTitle)) {
                $epName = str_replace('EP ', '', $node->filter('div.name')->text());
                $epUrl = $this->baseUrl . str_replace(' ', '', $epUrl);
                $this->episodeList[$epName] = $epUrl;
            }
        });
        if (empty($this->episodeList)) {
            echo "Empty episode list [{$this->episodeListUrl}]";
            exit;
        }
        ksort($this->episodeList, SORT_NATURAL);

        return $this->episodeList;
    }

    public function getEpisodeDataShell($url)
    {
        $getLinks = $this->client->request('GET', $url);
        $nodes = $getLinks->filter('a[data-video]');
        $i = 1;
        foreach ($nodes as $node) {
            $provider = strtolower($node->firstChild->nodeValue);
            $this->streamListUrl[$i] = $node->getAttribute('data-video');
            $this->streamList[$i] = "[$provider] " . $node->getAttribute('data-video');
            $i++;
        }

        return $this->streamList;
    }

    public function getEpisodeData($url, $k)
    {
        $getLinks = $this->client->request('GET', $url);
        $getLinks->filter('a[data-video]')->each(function ($node) use ($k) {
            $provider = strtolower($node->getNode(0)->firstChild->nodeValue);
            $this->episodeList[$k]['stream_link'][$provider] = H::extractNode($node, 0, ['data-video']);
        });
    }

    public function getStreamDataShell($url, $episode)
    {
        $getStream = $this->getStream($url);
        preg_match_all('/\{file\:(?:[^{}]|(?R))*\}/x', preg_replace('/\s+/', '', $getStream->html()), $matches);

        $downloaded = false;
        foreach ($matches[0] as $mat) {
            $mat = (new CJSON)->decode($mat);
            $video_url = H::getVal($mat, 'file', '#');
            if ($video_url and strpos($video_url, 'redirector.googlevideo')) {
                $filename = $this->cleanTitle . '-' . $episode . '.' . H::getVal($mat, 'type');
                exec('"'. $this->idmLocation .'" /d "'. $video_url .'" /n /f ' . $filename);
                $downloaded = true;
                break;
            }
        }

        return $downloaded;
    }

    public function getStream($url)
    {
        return $this->client->request('GET', $url);
    }
}
