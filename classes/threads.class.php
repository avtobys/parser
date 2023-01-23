<?php

class Threads
{
    public $n;
    public $threads_file;
    public $file;

    public function __construct()
    {
        file_exists(dirname(__DIR__) . '/data') or mkdir(dirname(__DIR__) . '/data', 0755) or exit('data path not created');
        $this->threads_file = dirname(__DIR__) . '/data/threads';
        $this->file = new SplFileObject($this->threads_file, 'a+');
        $this->file->flock(LOCK_EX);
        $this->file->rewind();
        $data = $this->file->fgets();
        $this->n = empty($data) ? 1 : ++$data;
        $this->file->ftruncate(0);
        $this->file->fwrite($this->n);
    }

    public function unlock()
    {
        $this->file->flock(LOCK_UN);
    }

    public function __destruct()
    {
        $this->file->flock(LOCK_UN);
        $this->file = new SplFileObject($this->threads_file, 'a+');
        $this->file->flock(LOCK_EX);
        $this->file->rewind();
        $data = $this->file->fgets();
        $this->n = empty($data) ? 0 : --$data;
        $this->file->ftruncate(0);
        $this->file->fwrite($this->n);
        $this->file->flock(LOCK_UN);
    }

}
