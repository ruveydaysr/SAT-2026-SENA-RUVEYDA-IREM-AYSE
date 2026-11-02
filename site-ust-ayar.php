<?php
// Bütün kullanıcı sayfalarında ortak dil tercihi.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET'
    && is_string($_GET['lang'] ?? null)
    && in_array($_GET['lang'], ['tr', 'en'], true)) {
    $_SESSION['dil'] = $_GET['lang'];
    $arisParametreler = $_GET;
    unset($arisParametreler['lang']);
    $arisHedef = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
    if ($arisParametreler) {
        $arisHedef .= '?' . http_build_query($arisParametreler, '', '&', PHP_QUERY_RFC3986);
    }
    header('Location: ' . $arisHedef, true, 303);
    exit();
}
function aris_ust_h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
function aris_ust_t($tr, $en)
{
    return ($_SESSION['dil'] ?? 'tr') === 'en' ? $en : $tr;
}
function aris_ust_dil_linki($lang)
{
    $params = $_GET;
    $params['lang'] = $lang;
    return basename($_SERVER['SCRIPT_NAME'] ?? 'index.php') . '?'
        . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
}
