<?php


function nowDate()
{
    return date('Y-m-d H:i:s');
}

function mongoObjectId($_id = '')
{
    return new \MongoDB\BSON\ObjectId($_id);
}


/**
 * 使用curl进行GET请求 （支持https）
 * @param $url
 * @param int $timeout
 * @param array $header
 * @return bool|mixed
 */
function curlGet($url, $timeout = 10, $header = array(), $cookie = '')
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    if (!empty($header)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    }
    $https = substr($url, 0, 8) == "https://" ? true : false;
    if ($https) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    }
    if (!empty($cookie)) {
        curl_setopt($ch, CURLOPT_COOKIE, $cookie);
    }
    $res = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    // if ($res !== false && $status >= 400) {
    // $res = false;
    // }
    curl_close($ch);
    return $res;
}

/**
 * 使用curl进行POST请求 （支持https）
 * json头 :
 * $header = array(
 * 'Content-Type: application/json; charset=utf-8',
 * 'Content-Length: ' . strlen($json)
 * );
 * urlencoded头
 * $header = array('Content-Type: application/x-www-form-urlencoded; charset=utf-8',);
 * @param $url
 * @param array $data
 * @param int $timeout
 * @param array $header
 * @param string $cookie
 * @return bool|mixed
 */
function curlPost($url, $data = array(), $timeout = 10, $header = array(), $cookie = "")
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
//    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    if (!empty($header)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    }
    $https = substr($url, 0, 8) == "https://" ? true : false;
    if ($https) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    }
    if (!empty($cookie)) {
        curl_setopt($ch, CURLOPT_COOKIE, $cookie);
    }
    $res = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $res;
}