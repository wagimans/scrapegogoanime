<?php

namespace base;

use Ahc\Cli\IO\Interactor;
use base\Core;
use base\Helpers as H;

class Shell
{
    public $cli;
    public $core;
    public $downloadEp;

    public function __construct()
    {
        if (!H::isCli()) throw new \Exception("Can only run this class in command line");

        $this->cli = new Interactor;
        $this->core = new Core;

        $argv = $_SERVER['argv'];
        $this->core->rawTitle = isset($argv[1]) ? (trim($argv[1])) : null;
        $this->downloadEp = isset($argv[2]) ? (trim($argv[2])) : null;
    }

    public function run()
    {
        $core = $this->core;

        if ($core->rawTitle) {
            $this->cli->comment("Anime title : {$core->rawTitle}" , true);
        } else {
            $core->rawTitle = $this->cli->prompt('Anime title', 'FULLMETAL ALCHEMIST', null, 1);
            if (empty($core->rawTitle)) {
                $this->cli->redBold("Anime title is required!"); exit;
            }
        }

        if (is_null($this->downloadEp)) {
            $core->episodeStart = $this->cli->prompt('From episode', '0', function ($value) {
                if (!is_numeric($value)) {
                    throw new \InvalidArgumentException('Integer value expected!');
                }
                return $value;
            });

            $core->episodeEnd = $this->cli->prompt('To episode', '99', function ($value) {
                if (!is_numeric($value)) {
                    throw new \InvalidArgumentException('Integer value expected!');
                }
                return $value;
            });

            $this->cli->comment('Getting data . . . ' . PHP_EOL, true);

            $core->getAnimeDataShell();

            $episode = $this->cli->choice('Select an episode', $core->episodeList);
            $selectedEp = H::getVal($core->episodeList, $episode, false);
            if (!$selectedEp) {
                $this->cli->redBold("Invalid episode selected!");
                exit;
            }

            $this->cli->comment('Getting data . . . ' . PHP_EOL, true);

            $core->getEpisodeDataShell($selectedEp);

            $this->cli->greenBold("{$selectedEp} selected", true);
            $stream = $this->cli->choice('Select download stream', $core->streamList, '1');
            $selectedstream = H::getVal($core->streamList, $stream, false);
            if (!$selectedstream) {
                $this->cli->redBold("Invalid episode selected!");
                exit;
            }
            $selectedStreamUrl = H::getVal($core->streamListUrl, $stream, false);

            $this->cli->cyanBold(PHP_EOL . "Downloading video from {$selectedStreamUrl} . . . " . PHP_EOL, true);
        }

        $this->cli->comment("Selected episode : {$this->downloadEp}", true);
        $episodeList = $core->getAnimeDataShell();

        $downloadEps = explode('-', $this->downloadEp);
        $endDownloadEps = count($downloadEps) == 1 ? $downloadEps[0] : $downloadEps[1];
        for ($i = $downloadEps[0]; $i <= $endDownloadEps; $i++) {
            $selectedEpurl = H::getVal($episodeList, $i, false);
            $streamList = $core->getEpisodeDataShell($selectedEpurl);
            if (empty($streamList)) {
                $this->cli->redBold("No stream provider detected!", true);
            }
            $selectedstreamUrl = H::getVal($core->streamListUrl, 1, false);

            $this->cli->cyanBold("Downloading episode {$i}", true);
            $downloaded = $core->getStreamDataShell($selectedstreamUrl, $i);
            if (!$downloaded) {
                $this->cli->redBold("Unable to download video!", true);
            }
            sleep(1);
        }

        $this->cli->greenBold("All done, bye!", true);
        exit;
    }
}
