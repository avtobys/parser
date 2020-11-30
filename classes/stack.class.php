<?php

class Stack
{
    /**
     * @param string $filepath
     * @param int $timeout
     * @return $res
     */
    public function getItem($filepath, $timeout)
    {
        $res = false;
        $file = new SplFileObject($filepath, 'a+b');
        $file->flock(LOCK_EX);
        $file->rewind();
        $data = $file->fgets();
        $arr = json_decode($data, true);
        if ($arr) {
            foreach ($arr as $key => $value) {
                if ($value + $timeout < time()) {
                    $arr[$key] = time();
                    $res = $key;
                    break;
                }
            }
        }
        if ($res) {
            $file->ftruncate(0);
            $file->fwrite(json_encode($arr));
        }
        $file->flock(LOCK_UN);
        if (!$arr) {
            unlink($filepath);
        }
        return $res;
    }

    public function rmItem($filepath, $key)
    {
        $file = new SplFileObject($filepath, 'a+b');
        $file->flock(LOCK_EX);
        $file->rewind();
        $data = $file->fgets();
        $arr = json_decode($data, true);
        if ($arr && isset($arr[$key])) {
            unset($arr[$key]);
        }
        $file->ftruncate(0);
        $file->fwrite(json_encode($arr));
        $file->flock(LOCK_UN);
        if (!$arr) {
            unlink($filepath);
        }
    }

    public function upItem($filepath, $key)
    {
        $file = new SplFileObject($filepath, 'a+b');
        $file->flock(LOCK_EX);
        $file->rewind();
        $data = $file->fgets();
        $arr = json_decode($data, true);
        if ($arr && isset($arr[$key])) {
            $arr[$key] = 0;
        } else if (!$arr) {
            $arr = [];
            $arr[$key] = 0;
        }
        $file->ftruncate(0);
        $file->fwrite(json_encode($arr));
        $file->flock(LOCK_UN);
        if (!$arr) {
            unlink($filepath);
        }
    }

    public function addItem($filepath, $key)
    {
        $file = new SplFileObject($filepath, 'a+b');
        $file->flock(LOCK_EX);
        $file->rewind();
        $data = $file->fgets();
        $arr = json_decode($data, true);
        if ($arr && !isset($arr[$key])) {
            $arr[$key] = 0;
        } else if (!$arr) {
            $arr = [];
            $arr[$key] = 0;
        }
        $file->ftruncate(0);
        $file->fwrite(json_encode($arr));
        $file->flock(LOCK_UN);
    }

    public function addArrItems($filepath, $keys)
    {
        $file = new SplFileObject($filepath, 'a+b');
        $file->flock(LOCK_EX);
        $file->rewind();
        $data = $file->fgets();
        $arr = json_decode($data, true);
        if (!$arr) {
            $arr = [];
        }
        foreach ($keys as $key) {
            if (!isset($arr[$key])) {
                $arr[$key] = 0;
            }
        }
        $file->ftruncate(0);
        $file->fwrite(json_encode($arr));
        $file->flock(LOCK_UN);
    }
}
