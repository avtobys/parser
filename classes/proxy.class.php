<?php

class Proxy
{
    public $proxy;
    public $type;
    public $time;

    public function __construct()
    {
        file_exists(dirname(__DIR__) . '/data') or mkdir(dirname(__DIR__) . '/data', 0755) or exit('data path not created');
        if (TOR == false) {
            $this->updateProxyList();
        }
    }

    private function saveProxyListFromAPI($type)
    {
        $data = shell_exec("curl -H 'Host: youapi.com' http://192.168.1.100/proxy/data/$type");
        if (!$data || !preg_match('#^[\d]{1,3}\.[\d]{1,3}\.[\d]{1,3}\.[\d]{1,3}#', $data)) {
            $log = new Logger();
            $log->err(__METHOD__ . ' ' . $data);
            touch(dirname(__DIR__) . '/data/' . $type);
            return false;
        }
        $file = new SplFileObject(dirname(__DIR__) . '/data/' . $type, 'wb');
        $file->flock(LOCK_EX);
        $file->fwrite($data);
        $file->flock(LOCK_UN);
        return true;
    }

    private function updateProxyList()
    {
        $types = ['socks5' => 0, 'socks4' => 0, 'https' => 0];
        array_walk($types, function (&$mtime, $type) {
            $mtime = file_exists(dirname(__DIR__) . '/data/' . $type) ? filemtime(dirname(__DIR__) . '/data/' . $type) : 0;
        });
        asort($types, SORT_NUMERIC);
        $type = key($types);
        $last_mtime = end($types);
        if (filemtime(dirname(__DIR__) . '/data/' . $type) + PROXY_UPDATE_PERIOD + mt_rand(0, 10) < time() && $last_mtime + 30 < time()) {
            if (!$this->saveProxyListFromAPI($type)) {
                $log = new Logger();
                $log->err(__METHOD__);
            }
        }
    }

    private function getRandProxyFromList()
    {
        $types = ['socks5', 'socks4', 'https'];
        shuffle($types);
        $types = array_filter($types, function ($type) {
            if (file_exists(dirname(__DIR__) . '/data/' . $type) && filesize(dirname(__DIR__) . '/data/' . $type)) {
                return true;
            }
            return false;
        });
        if (!$types) {
            return false;
        }
        $type = array_shift($types);
        $mtime = filemtime(dirname(__DIR__) . '/data/' . $type);
        $file = new SplFileObject(dirname(__DIR__) . '/data/' . $type, 'a+b');
        $file->flock(LOCK_EX);
        $file->rewind();
        $proxy = trim($file->fgets());
        $data = trim($file->fread($file->getSize()));
        $file->ftruncate(0);
        if ($data) {
            $file->fwrite($data);
        }
        $file->flock(LOCK_UN);
        touch(dirname(__DIR__) . '/data/' . $type, $mtime);
        if (!$proxy) {
            return false;
        }
        return [$proxy, $type];
    }

    private function setProxy()
    {
        if (TOR) {
            $file = new SplFileObject(dirname(__DIR__) . '/data/tor', 'a+b');
            $file->flock(LOCK_EX);
            $file->rewind();
            $data = $file->fgets();
            $ports = empty($data) ? range(TOR_RANGE[0], TOR_RANGE[1]) : json_decode($data, true);
            $port = array_shift($ports);
            $ports[] = $port;
            $file->ftruncate(0);
            $file->fwrite(json_encode($ports));
            $file->flock(LOCK_UN);
            $this->proxy = '127.0.0.1:' . $port;
            $this->type = 'socks5';
            $this->time = time();
        } else {
            if (file_exists(dirname(__DIR__) . '/data/proxy.json') && ((filesize(dirname(__DIR__) . '/data/proxy.json') > (THREADS_MAX * 50) && mt_rand(0, 4)) || filesize(dirname(__DIR__) . '/data/proxy.json') > 10240)) {
                $file = new SplFileObject(dirname(__DIR__) . '/data/proxy.json', 'a+b');
                $file->flock(LOCK_EX);
                $file->rewind();
                $data = $file->fgets();
                $arr = json_decode($data, true);
                if ($arr) {
                    $proxy = array_shift($arr);
                }
                $file->ftruncate(0);
                if ($arr) {
                    $file->fwrite(json_encode($arr));
                }
                $file->flock(LOCK_UN);
            } elseif (!($proxy = $this->getRandProxyFromList())) {
                $log = new Logger();
                $log->err(__METHOD__);
                exit;
            }
            $this->proxy = $proxy[0];
            $this->type = $proxy[1];
            $this->time = time();
        }
    }

    /**
     * function getProxy
     * @return Array (proxy = IP:PORT, type = socks4 / socks5 / https)
     */

    public function getProxy()
    {
        $this->setProxy();
        return [$this->proxy, $this->type];
    }

    public function saveProxy()
    {
        if (TOR) {
            return false;
        }
        if (time() - $this->time > PROXY_GOOD_TIME) {
            return false;
        }
        $file = new SplFileObject(dirname(__DIR__) . '/data/proxy.json', 'a+b');
        $file->flock(LOCK_EX);
        $file->rewind();
        $data = $file->fgets();
        if ($data) {
            $arr = json_decode($data, true);
            if ($arr) {
                if (!in_array([$this->proxy, $this->type], $arr)) {
                    $arr[] = [$this->proxy, $this->type];
                    $file->ftruncate(0);
                    $file->fwrite(json_encode($arr));
                }
            } else {
                $file->ftruncate(0);
                $file->fwrite(json_encode([[$this->proxy, $this->type]]));
            }
        } else {
            $file->ftruncate(0);
            $file->fwrite(json_encode([[$this->proxy, $this->type]]));
        }
        $file->flock(LOCK_UN);
    }
}
