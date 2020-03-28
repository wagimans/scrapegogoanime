<?php

namespace base;

use base\Helpers as H;
use base\Model;
use base\Web;
use base\Shell;

class Core
{
    public $isCli;
    public $model;
    public $runner;

    public function scrape()
    {
        $this->isCli = H::isCli();
        $this->model = new Model;

        if (!$this->isCli) {
            $this->runner = new Web;
        } else {
            $this->runner = new Shell;
        }
        $this->runner->run($this->model);
    }
}
