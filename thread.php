<?php

set_time_limit(1800);
ignore_user_abort(true);
libxml_use_internal_errors(true);
require __DIR__ . '/inc/conf.php';
spl_autoload_register(function ($class) {
    require __DIR__ . '/classes/' . strtolower($class) . '.class.php';
});

$T = new Threads();
$log = new Logger();
$proxy = new Proxy();
$parser  = new Parser();
$stack   = new Stack();

$T->unlock();


$db = new DB();
$log->log('Потоков: ' . $T->n);
$log->log(json_encode($proxy->getProxy()));
// $log->err('error');

// sleep(rand(2, 30));
