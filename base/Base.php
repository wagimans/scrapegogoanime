<?php

namespace base;

use base\Helpers as H;
use base\Web;
use base\Shell;

class Base
{
    public $isCli;
    public $runner;

    public function scrape()
    {
        $this->isCli = H::isCli();

        if (!$this->isCli) {
            $this->runner = new Web;
        } else {
            $this->runner = new Shell;
        }
        $this->runner->run();
    }
}
