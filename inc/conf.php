<?php

const THREADS_MAX = 0;
const LOG_FILESIZE = 5242880;
const LOAD_AVERAGE_MAX = 80;

const ADMIN_PASS = 'demo';
const MAX_ATTEMPTS_FAILED = 5;

const PROXY_UPDATE_PERIOD = 120;
const PROXY_GOOD_TIME = 15;

const TOR = true;
const TOR_RANGE = [9060, 9560];

const DB = ['host' => '127.0.0.1', 'username' => 'root', 'password' => '', 'db_name' => 'test'];

const GREP = [
    'parser' => [
        'All'                => 'All',

    ],
    'error'  => [
        'All'                 => 'All',
        
    ]
];