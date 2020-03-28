<?php

namespace base;

use base\Helpers as H;
use components\CJSON;

class Web
{
    public $model;

    public function __construct()
    {
        if (H::isCli()) throw new \Exception("Can not run this class in command line");
    }

    public function run($model)
    {
        $action = H::getVal($_GET, 'action');
        $start = H::getVal($_GET, 'start');
        $end = H::getVal($_GET, 'end');

        if (empty($action) and !empty($title)) {
            echo '<h2>GoGo<sup><em>search</em></sup>Anime</h2>';
            echo '<form>
                <label for="title">Anime title:</label>
                <input type="text" id="title" name="title"><br><br>
                <label for="start">From Episode:</label>
                <input type="number" id="start" name="start"><br><br>
                <label for="end">To episode:</label>
                <input type="number" id="end" name="end"><br><br>
                <input type="submit" value="Submit">
                </form>';
            exit;
        }

        $title = urldecode(H::getVal($_GET, 'title'));
        $videourl = urldecode(H::getVal($_GET, 'url'));
        $filename = urldecode(H::getVal($_GET, 'filename'));

        $model->appBaseUrl = strtok((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]", '?');
        $model->rawTitle = $title;
        $model->episodeStart = !empty($start) ? $start : 0;
        $model->episodeEnd = !empty($end) ? $end : 2;

        if ($action == 'download' and !empty($videourl)) {
            if (!$filename) {
                pdd('Invalid video file name!');
            }
            $this->downloadVideo($videourl, $filename);
        }

        $this->model = $model;
        $this->model->getAnimeData();

        $this->output();
    }

    private function downloadVideo($url, $filename)
    {
        header('Content-Type: application/octet-stream');
        header("Content-Transfer-Encoding: Binary");
        header("Content-disposition: attachment; filename=\"" . $filename . "\"");
        readfile($url);
        exit;
    }

    public function output()
    {
        echo "<h4><a href='{$this->model->appBaseUrl}'>Reset Search</a></h4>";
        echo "<h2><a target='_blank' href='{$this->model->animeUrl}'>{$this->model->rawTitle}</a></h2>";
        echo "<img style='height: 280px;' src='{$this->model->animeImageUrl}'>";
        foreach ($this->model->episodeList as $ep => $data) {
            echo "<h3><a target='_blank' href='{$data['url']}'>{$ep}</a></h3>";
            foreach (H::getVal($data, 'stream_link') as $stream => $url) {
                $getStream = $this->model->getStream($url);
                preg_match_all('/\{file\:(?:[^{}]|(?R))*\}/x', preg_replace('/\s+/', '', $getStream->html()), $matches);
                foreach ($matches[0] as $mat) {
                    $mat = (new CJSON)->decode($mat);
                    $video_url = H::getVal($mat, 'file');
                    if ($video_url and strpos($video_url, 'redirector.googlevideo')) {
                        echo "Source : {$stream} ";
                        $data = array(
                            'action' => 'download',
                            'url' => $video_url,
                            'filename' => $this->model->cleanTitle . ' ' . $ep . '.' . H::getVal($mat, 'type'),
                        );

                        $download_url = $this->model->appBaseUrl .  '?' . http_build_query($data);
                        echo "[<a target='_blank' href='{$download_url}'>Download</a>]</br>";
                        break;
                    } elseif (!strpos($video_url, 'redirector.googlevideo')) {
                        continue;
                    } else {
                        echo "[Download link not found]</br>";
                    }
                }
            }
        }
    }
}
