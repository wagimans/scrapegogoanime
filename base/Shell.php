<?php

namespace base;

use Ahc\Cli\IO\Interactor;
use base\Helpers as H;

class Shell
{
    public $cli;
    public $model;

    public function __construct()
    {
        $this->cli = new Interactor;

        if (!H::isCli()) throw new \Exception("Can only run this class in command line");
    }

    public function run($model)
    {
        $model->rawTitle = $this->cli->prompt('Anime title', 'NATSUNAGU!', null, 1);
        if (empty($model->rawTitle)) {
            $this->cli->redBold("Anime title is required!"); exit;
        }

        $model->episodeStart = $this->cli->prompt('From episode', '0', function ($value) {
            if (!is_numeric($value)) {
                throw new \InvalidArgumentException('Integer value expected!');
            }
            return $value;
        });

        $model->episodeEnd = $this->cli->prompt('To episode', '2', function ($value) {
            if (!is_numeric($value)) {
                throw new \InvalidArgumentException('Integer value expected!');
            }
            return $value;
        });

        $this->cli->comment('Getting data . . . ' . PHP_EOL, true);

        $this->model = $model;
        $this->model->getAnimeDataShell();

        $episode = $this->cli->choice('Select an episode', $this->model->episodeList);
        $selectedEp = H::getVal($this->model->episodeList, $episode, false);
        if (!$selectedEp) {
            $this->cli->redBold("Invalid episode selected!"); exit;
        }

        $this->cli->comment('Getting data . . . ' . PHP_EOL, true);

        $this->model->getEpisodeDataShell($selectedEp);

        $stream = $this->cli->choice('Select download stream', $this->model->streamList);
        $selectedstream = H::getVal($this->model->streamList, $stream, false);
        if (!$selectedstream) {
            $this->cli->redBold("Invalid episode selected!");
            exit;
        }
        $selectedstreamUrl = H::getVal($this->model->streamListUrl, $stream, false);

        $confirm = $this->cli->confirm('sure?', 'y');
        if (!$confirm) {
            $this->cli->yellowBold("Bye!"); exit;
        }

        $this->cli->comment("Downloading video from {$selectedstream} . . . " . PHP_EOL, true);

        $this->model->getStreamDataShell($selectedstreamUrl, $episode);
    }
}
