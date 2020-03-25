<?php

set_time_limit(0);

require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . "/components/VarDumper.php";

$base = new base\Core;
$base->scrape();