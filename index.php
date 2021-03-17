<?php
error_reporting(0);
header("Cache-Control: no-cache");
header("Expires: Thu, 01 Jan 1970 00:00:01 GMT");

require __DIR__ . '/inc/conf.php';

$attempts = file_exists(__DIR__ . '/logs/bruteforce.log') ? MAX_ATTEMPTS_FAILED - count(array_filter(file(__DIR__ . '/logs/bruteforce.log'), function ($str) {
    list($ip, $t) = explode('|', trim($str));
    if ($ip == $_SERVER['REMOTE_ADDR'] && ($t + 3600) > time()) {
        return true;
    }
    return false;
})) : MAX_ATTEMPTS_FAILED;

if ($attempts <= 0) {
    exit('Bye!');
}

session_start();

if (ADMIN_PASS == '') {
    $_SESSION['login'] = 1;
}

if (!empty($_SESSION['login'])) {
    if (isset($_GET['stat'])) {
        $threads = intval(shell_exec("ps aux | grep 'php -f " . __DIR__ . "/thread\.php' | wc -l"));
        $threads_work = file_exists(__DIR__ . '/data/threads') ? trim(file_get_contents(__DIR__ . '/data/threads')) : 0;
        $uptime = trim(shell_exec('uptime'));
        $uptime_1_min = (float) preg_replace('#.*load average: ([\d\.]+),.*#', '$1', $uptime);
        $nproc = intval(shell_exec('nproc'));
        $start_time = trim(shell_exec('head -n 1 ' . __DIR__ . '/logs/thread.log | awk \'{printf "%s %s %s\n", $1, $2, $3}\''));
        $end_time = trim(shell_exec('tail -n 1 ' . __DIR__ . '/logs/thread.log | awk \'{printf "%s %s %s\n", $1, $2, $3}\''));
        $sum = trim(shell_exec('wc -l ' . __DIR__ . '/logs/thread.log | awk \'{print $1}\''));
        if ($threads < $threads_work) {
            file_put_contents(__DIR__ . '/data/threads', $threads, LOCK_EX);
        }
        echo "let stat = document.getElementById('stat');
    stat.innerHTML = '\
Размер лог файла    : <a href=\"#logsize\">" . (LOG_FILESIZE / 1024 / 1024) . "</a> МБ\\n\
Предельная нагрузка : " . round($uptime_1_min / $nproc * 100) . "% из <a href=\"#la\">" . LOAD_AVERAGE_MAX . "</a>%\\n\
Все потоки          : $threads из <a href=\"#threads\">" . THREADS_MAX . "</a>\\n\
Рабочие потоки      : " . $threads_work . "\\n\
Ожидающие потоки    : " . ($threads - $threads_work) . "\\n\
Расчётный период    : $start_time - $end_time ($sum URL\\'s)\\n\
Скорость(URL/сутки) : " . round(86400 * $sum / (strtotime($end_time) - strtotime($start_time))) . "\\n\
\\n" . $uptime . " CPU(s): $nproc';
    ";

        if ($_GET['parser_grep'] != 'All') {
            $grep = 'grep "' . array_search($_GET['parser_grep'], GREP['parser']) . '" ' . __DIR__ . '/logs/thread.log';
            $data = trim(shell_exec($grep . ' | tail -n ' . abs(intval($_GET['parser_lines']))));
        } else {
            $data = trim(shell_exec('tail -n ' . abs(intval($_GET['parser_lines'])) . ' ' . __DIR__ . '/logs/thread.log'));
        }
        $data = str_replace("'", "&#39;", $data);
        $data = preg_replace('#(http(|s):\/\/[^\s\,\"]+)#', '<a target=\"_blank\" rel=\"noopener noreferrer\" href=\"$1\">$1</a>', $data);
        echo "document.getElementById('parser').innerHTML='" . str_replace("\n", "\\n", $data) . "';";

        if ($_GET['error_grep'] != 'All') {
            $grep = 'grep "' . array_search($_GET['error_grep'], GREP['error']) . '" ' . __DIR__ . '/logs/error.log';
            $data = trim(shell_exec($grep . ' | tail -n ' . abs(intval($_GET['error_lines']))));
        } else {
            $data = trim(shell_exec('tail -n ' . abs(intval($_GET['error_lines'])) . ' ' . __DIR__ . '/logs/error.log'));
        }
        $data = str_replace("'", "&#39;", $data);
        $data = preg_replace('#(http(|s):\/\/[^\s\,\"]+)#', '<a target=\"_blank\" rel=\"noopener noreferrer\" href=\"$1\">$1</a>', $data);
        echo "document.getElementById('error').innerHTML='" . str_replace("\n", "\\n", $data) . "';";

        exit;
    }
    if (isset($_GET['threads'])) {
        $threads = abs(intval($_GET['threads']));
        exec("sed -i -r 's/THREADS_MAX[[:space:]]*=[[:space:]]*[0-9]+/THREADS_MAX = " . $threads . "/' " . __DIR__ . "/inc/conf.php");
        if ($threads && intval(shell_exec("ps aux | grep 'php -f " . __DIR__ . "/init\.php' | wc -l")) < 1) {
            exec('(php -f ' . __DIR__ . '/init.php &) > /dev/null 2>&1');
        }
        echo "location.hash = '';
        setTimeout(getStat, 1000);";
        exit;
    }
    if (isset($_GET['logsize'])) {
        $_GET['logsize'] = $_GET['logsize'] > 10 ? 10 : $_GET['logsize'];
        $logsize = abs(intval($_GET['logsize'] * 1024 * 1024));
        if (empty($logsize)) {
            exec("truncate -s 0 " . __DIR__ . "/logs/*");
            $logsize = LOG_FILESIZE;
        }
        exec("sed -i -r 's/LOG_FILESIZE[[:space:]]*=[[:space:]]*[0-9]+/LOG_FILESIZE = " . $logsize . "/' " . __DIR__ . "/inc/conf.php");
        echo "location.hash = '';
        setTimeout(getStat, 1000);";
        exit;
    }
    if (isset($_GET['la'])) {
        $_GET['la'] = $_GET['la'] > 100 ? 100 : $_GET['la'];
        $la = abs(intval($_GET['la']));
        exec("sed -i -r 's/LOAD_AVERAGE_MAX[[:space:]]*=[[:space:]]*[0-9]+/LOAD_AVERAGE_MAX = " . $la . "/' " . __DIR__ . "/inc/conf.php");
        sleep(1);
        echo "location.hash = '';
        setTimeout(getStat, 1000);";
        exit;
    }
}



if (!empty($_POST['password']) && $attempts > 0) {
    if (empty($_SESSION['csrf']) || $_SESSION['csrf'] != $_POST['csrf']) {
        exit('CSRF token is not valid!');
    }
    if ($_POST['password'] === ADMIN_PASS) {
        $_SESSION['login'] = 1;
        header('Location: .');
    } else {
        file_exists(__DIR__ . '/logs') or mkdir(__DIR__ . '/logs', 0755) or exit('logs path not created');
        $mode = file_exists(__DIR__ . '/logs/bruteforce.log') && filesize(__DIR__ . '/logs/bruteforce.log') < 1048576 ? 'a+' : 'w';
        $file = new SplFileObject(__DIR__ . '/logs/bruteforce.log', $mode);
        $file->flock(LOCK_EX);
        $file->fwrite($_SERVER['REMOTE_ADDR'] . '|' . time() . PHP_EOL);
        $file->flock(LOCK_UN);
        $attempts--;
    }
}

$_SESSION['csrf'] = md5(microtime(true));

?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= basename(__DIR__) ?> <?= $_SERVER['SERVER_ADDR'] ?></title>
    <style>
        *,
        *::before,
        *::after {
            -webkit-box-sizing: border-box;
            box-sizing: border-box;
        }

        .auth,
        .dataChange {
            position: absolute;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            top: 0;
            left: 0;
        }

        .dataChange {
            background: #000000e8;
            display: none;
        }

        .dataChange>div>span {
            color: #fff;
            font-size: 15px;
            margin-right: 5px;
        }

        .dataChange>div>button[type=reset] {
            position: absolute;
            top: 0;
            right: 0;
            border: none;
            background: transparent;
            color: #fff;
            font-size: 30px;
        }

        .auth>form,
        .dataChange>div {
            background-color: #272727;
            padding: 10%;
            border: 1px solid #777;
            position: relative;
            z-index: 2;
        }

        .dataChange input[name=data] {
            max-width: 40px;
        }

        input {
            outline: none;
        }

        input[type=submit],
        button {
            cursor: pointer;
        }

        .auth>form>span {
            position: absolute;
            right: 2px;
            bottom: 2px;
            color: #777;
        }

        .auth>span {
            position: absolute;
            cursor: default;
            font-size: 5px;
        }

        .block-pre {
            border: 1px solid #777;
            overflow: auto;
            margin: 7px;
            position: relative;
        }

        .select-wrap {
            position: absolute;
            right: 0;
            top: 0;
        }

        select {
            width: 55px;
            font-size: 10px;
        }

        pre {
            margin: 0px;
            padding: 2px;
            background-color: #272727;
            color: #fff;
            min-height: 15px;
        }

        a {
            color: #fff;
            text-decoration: none;
            cursor: pointer;
        }

        a:hover {
            text-decoration: underline;
        }

        .sel {
            background: #00ff00;
            color: #000;
        }

        ::-webkit-scrollbar {
            width: 3px;
            height: 5px;
        }

        ::-webkit-scrollbar-button {
            background-color: #333;
        }

        ::-webkit-scrollbar-track {
            background-color: #999;
        }

        ::-webkit-scrollbar-track-piece {
            background-color: #ffffff;
        }

        ::-webkit-scrollbar-thumb {
            height: 50px;
            background-color: #888;
            border-radius: 3px;
        }

        ::-webkit-scrollbar-corner {
            background-color: #999;
        }

        ::-webkit-resizer {
            background-color: #666;
        }
    </style>
</head>

<body style="font-size:12px;line-height:1;background-color:black;margin:0 auto;padding:0;">
    <?php if (empty($_SESSION['login'])) : ?>
        <div class="auth">
            <form action="/<?= basename(__DIR__) ?>/index.php" method="post">
                <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">
                <input type="password" name="password" placeholder="Password" autofocus>
                <input type="submit" value="Sign in">
                <span>Allowed attempts: <?= $attempts ?></span>
            </form>
        </div>
        <script>
            let matrix = function(n, f = 10) {
                n++;
                let auth = document.querySelector(".auth"),
                    l = Math.floor(Math.random() * 99),
                    t = Math.floor(Math.random() * 99);
                for (let i = 0; i < f * n; i++) {
                    let span = document.createElement("span");
                    span.innerHTML = Math.floor(Math.random() * 2);
                    span.style.cssText = "left:" + l + "%;top:" + t + "%;transition: all " + (Math.floor(Math.random() * 15) + 1) + "s ease-out;color:#fff;";
                    auth.appendChild(span);
                }

                setTimeout(() => {
                    document.querySelectorAll(".auth>span:not(.fix)").forEach(el => {
                        el.style.cssText = "left:" + Math.floor(Math.random() * 100) + "%;top:" + Math.floor(Math.random() * 99) + "%;transition: all " + (Math.floor(Math.random() * 15) + 1) + "s ease-out;color:#777;font-size: 10px;";
                        el.classList.add("fix");
                    });
                }, 20);

                if (n > 25) {
                    return setTimeout(() => {
                        matrixRain(Math.floor(Math.random() * 2));
                    }, 10000);
                }

                return setTimeout(() => {
                    matrix(n);
                }, 400 - n * 8);
            }

            let matrixRain = function(v = 0) {
                let fix = document.querySelectorAll(".auth>span.fix");
                if (!fix.length) {
                    return matrix(1, Math.floor(Math.random() * 15) + 1);
                }
                fix.forEach(el => {
                    el.innerHTML = Math.floor(Math.random() * 10);
                    let top = parseInt(el.style.top, 10);
                    let r = Math.floor(Math.random() * 99);
                    if (!Math.floor(Math.random() * parseInt(fix.length / 500, 10))) {
                        if (v == 0) {
                            top = r > top ? r : top;
                            (top > 94 && Math.floor(Math.random() * 2)) && el.parentNode.removeChild(el);
                        } else {
                            top = r < top ? r : top;
                            (top < 5 && Math.floor(Math.random() * 2)) && el.parentNode.removeChild(el);
                        }
                        el.style.color = "#000";
                    }

                    el.style.top = top + "%";

                });
                return setTimeout(() => {
                    matrixRain(v);
                }, parseInt(fix.length / 500, 10));
            }

            matrix(1);
        </script>
</body>

</html>
<?php exit;
    endif; ?>
<div class="block-pre">
    <svg id="control" style="width:24px;height:24px;color: #fff;position: absolute;right: 0;cursor: pointer;" viewBox="0 0 24 24">
        <path id="control_pause" fill="currentColor" d="M14,19H18V5H14M6,19H10V5H6V19Z" />
        <path id="control_play" style="display: none;" fill="currentColor" d="M8,5.14V19.14L19,12.14L8,5.14Z" />
    </svg>
    <pre id="stat"></pre>
</div>
<div class="block-pre">
    <div class="select-wrap">
        <select name="parser_grep">
            <?php
            foreach (GREP['parser'] as $value) {
                echo '<option value="' . $value . '">' . $value . '</option>';
            }
            ?>
        </select>
        <select name="parser_lines">
            <?php
            foreach (range(0, 5000, 10) as $value) {
                echo '<option value="' . $value . '"' . ($value == 30 ? ' selected' : '') . '>' . $value . '</option>';
            }
            ?>
        </select>
    </div>
    <pre id="parser"></pre>
</div>
<div class="block-pre">
    <div class="select-wrap">
        <select name="error_grep">
            <?php
            foreach (GREP['error'] as $value) {
                echo '<option value="' . $value . '">' . $value . '</option>';
            }
            ?>
        </select>
        <select name="error_lines">
            <?php
            foreach (range(0, 1000, 10) as $value) {
                echo '<option value="' . $value . '"' . ($value == 10 ? ' selected' : '') . '>' . $value . '</option>';
            }
            ?>
        </select>
    </div>
    <pre id="error"></pre>
</div>
<div class="dataChange">
    <div>
        <button type="reset" onclick="location.hash='';">&times;</button>
        <span></span>
        <input name="data" size="3">
        <button type="submit">OK</button>
    </div>
</div>
<script>
    let getStat = function() {
        let xhr = new XMLHttpRequest();
        xhr.open("GET", "/<?= basename(__DIR__) ?>/index.php?stat&parser_grep=" + document.querySelector("select[name=parser_grep]").value + "&parser_lines=" + document.querySelector("select[name=parser_lines]").value + "&error_grep=" + document.querySelector("select[name=error_grep]").value + "&error_lines=" + document.querySelector("select[name=error_lines]").value + "&" + Math.random(), true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState == 4) {
                if (xhr.status == 200) {
                    eval(xhr.responseText);
                }
            }
        }
        xhr.send(null);
    }
    getStat();
    let play = setInterval(getStat, 10000);

    let sendChange = function(data) {
        let xhr = new XMLHttpRequest();
        xhr.open("GET", "/<?= basename(__DIR__) ?>/index.php?" + data + "&" + Math.random(), true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState == 4) {
                if (xhr.status == 200) {
                    eval(xhr.responseText);
                }
            }
        }
        xhr.send(null);
    }

    function dataChange() {
        if (!location.hash) {
            document.querySelector(".dataChange").style.display = "none";
            return;
        }

        if (!document.getElementById("stat").innerHTML) {
            return setTimeout(() => {
                dataChange();
            }, 500);
        }

        document.querySelector(".dataChange").style.display = "flex";
        switch (location.hash) {
            case '#threads':
                document.querySelector(".dataChange > div > span").innerHTML = "Количество потоков:";
                document.querySelector(".dataChange > div > input[name=data]").value = document.querySelector("a[href='" + location.hash + "']").innerHTML;
                document.querySelector(".dataChange > div > button[type=submit]").onclick = function() {
                    sendChange("threads=" + document.querySelector(".dataChange > div > input[name=data]").value);
                }
                break;
            case '#logsize':
                document.querySelector(".dataChange > div > span").innerHTML = "Размеры лог файлов:";
                document.querySelector(".dataChange > div > input[name=data]").value = document.querySelector("a[href='" + location.hash + "']").innerHTML;
                document.querySelector(".dataChange > div > button[type=submit]").onclick = function() {
                    sendChange("logsize=" + document.querySelector(".dataChange > div > input[name=data]").value);
                }
                break;
            case '#la':
                document.querySelector(".dataChange > div > span").innerHTML = "Предельная нагрузка:";
                document.querySelector(".dataChange > div > input[name=data]").value = document.querySelector("a[href='" + location.hash + "']").innerHTML;
                document.querySelector(".dataChange > div > button[type=submit]").onclick = function() {
                    sendChange("la=" + document.querySelector(".dataChange > div > input[name=data]").value);
                }
                break;

            default:
                document.querySelector(".dataChange").style.display = "none";
                location.hash = "";
                break;
        }
    }

    window.addEventListener("hashchange", dataChange);
    window.addEventListener("DOMContentLoaded", dataChange);
    document.querySelectorAll('select').forEach(s => {
        s.addEventListener("change", getStat);
    });

    document.getElementById('control').addEventListener("click", e => {
        if (play) {
            play = clearInterval(play);
            document.getElementById('control_pause').style.display = 'none';
            document.getElementById('control_play').style.display = 'inline';
            document.querySelectorAll('pre').forEach(pre => {
                pre.style.backgroundColor = '#000';
            });
        } else {
            getStat();
            play = setInterval(getStat, 10000);
            document.getElementById('control_pause').style.display = 'inline';
            document.getElementById('control_play').style.display = 'none';
            document.querySelectorAll('pre').forEach(pre => {
                pre.style.backgroundColor = '#272727';
            });
        }
    });

    document.querySelector(".dataChange > div > input[name=data]").addEventListener("keydown", function (e) {
        if (e.keyCode == 13) {
            document.querySelector(".dataChange > div > button[type=submit]").click();
        }
    })
</script>
</body>

</html>