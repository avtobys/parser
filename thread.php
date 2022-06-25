<?php

set_time_limit(1800);
ignore_user_abort(true);
libxml_use_internal_errors(true);
require __DIR__ . '/inc/conf.php';
spl_autoload_register(function ($class) {
    require __DIR__ . '/classes/' . strtolower($class) . '.class.php';
});

$T = new Threads();
$proxy = new Proxy();
$parser  = new Parser();
$stack   = new Stack();

$T->unlock();

try {
    $dbh = new PDO('mysql:dbname=' . DB['name'] . ';host=' . DB['host'] . ';port=' . DB['port'] . ';charset=utf8mb4', DB['user'], DB['pass']);
} catch (PDOException $e) {
    Logger::err($e->getMessage());
}


Logger::log('Потоков: ' . $T->n);
Logger::log(json_encode($proxy->getProxy()));
Logger::err('error');

// sleep(rand(2, 30));
