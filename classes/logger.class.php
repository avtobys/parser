<?php

class Logger
{
    public function __construct()
    {
        file_exists(dirname(__DIR__) . '/logs') or mkdir(dirname(__DIR__) . '/logs', 0755) or exit('logs path not created');
    }

    public function log_write($data, $logfile)
    {
        $logsize = file_exists($logfile) ? filesize($logfile) : 0;
        $file = new SplFileObject($logfile, 'a+b');
        $file->flock(LOCK_EX);
        if ($logsize > LOG_FILESIZE) {
            $file->ftruncate(0);
        }
        $file->fwrite($data);
        $file->flock(LOCK_UN);
    }

    public function log($data)
    {
        $this->log_write(date('M j H:i:s') . ' '.sprintf("%7s", number_format(microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"], 3)).' ' . $data . "\n", dirname(__DIR__) . '/logs/thread.log');
    }

    public function err($data)
    {
        $this->log_write(date('M j H:i:s') . ' '.sprintf("%7s", number_format(microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"], 3)).' ' . $data . "\n", dirname(__DIR__) . '/logs/error.log');
    }
}
