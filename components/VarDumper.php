<?php

function pdd($var = 'nothing to print', $do_exit = true)
{
    if (!is_cli() and !headers_sent()) header('Content-Type: text/plain');
    print_r($var);
    if ($do_exit) die;
}

function vdd($var = 'nothing to dump', $do_exit = true)
{
    if (!is_cli() and !headers_sent()) header('Content-Type: text/plain');
    var_dump($var);
    if ($do_exit) die;
}

function is_cli()
{
    return php_sapi_name() === 'cli';
}