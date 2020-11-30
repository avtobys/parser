<?php
ignore_user_abort(true);
require 'inc/conf.php';

$threads = intval(shell_exec("ps aux | grep 'php -f " . __DIR__ . "/thread\.php' | wc -l"));

if ($threads < THREADS_MAX) {
    for (
        $i = 0;
        $i < THREADS_MAX - $threads &&
            intval(shell_exec("echo $(nproc) $(cat /proc/loadavg | awk '{print $1}') | awk '$2<$1/100*" . LOAD_AVERAGE_MAX . " {print 1}'"));
        $i++
    ) {
        exec('(php -f ' . __DIR__ . '/thread.php &) > /dev/null 2>&1');
        usleep(500);
    }
}

usleep(500000);

if (intval(shell_exec("ps aux | grep 'php -f " . __DIR__ . "/init\.php' | wc -l")) <= 1)
    exec('(php -f ' . __DIR__ . '/init.php &) > /dev/null 2>&1');