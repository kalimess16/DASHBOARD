<?php

const DASHBOARD_PROTECTED_DB_HOSTS = ['10.64.0.251', '10.64.0.56'];
$dashboard_last_mysql_connect_error = '';

function env_or_default(string $key, string $default): string
{
    $value = getenv($key);
    if ($value === false || $value === '') {
        return $default;
    }
    return $value;
}

function env_int_or_default(string $key, int $default): int
{
    $value = getenv($key);
    if ($value === false || $value === '' || !is_numeric($value)) {
        return $default;
    }
    $port = (int)$value;
    if ($port <= 0 || $port > 65535) {
        return $default;
    }
    return $port;
}

function connect_mysql(string $host, string $user, string $pass, string $db, int $port): ?mysqli
{
    $GLOBALS['dashboard_last_mysql_connect_error'] = '';
    $conn = mysqli_init();
    if ($conn === false) {
        $GLOBALS['dashboard_last_mysql_connect_error'] = 'mysqli_init failed';
        return null;
    }
    @mysqli_options($conn, MYSQLI_OPT_CONNECT_TIMEOUT, 3);
    $maxPacketOpt = defined('MYSQLI_OPT_MAX_ALLOWED_PACKET') ? constant('MYSQLI_OPT_MAX_ALLOWED_PACKET') : null;
    if ($maxPacketOpt !== null) {
        @mysqli_options($conn, (int)$maxPacketOpt, 134217728);
    }
    if (!@mysqli_real_connect($conn, $host, $user, $pass, $db, $port)) {
        $GLOBALS['dashboard_last_mysql_connect_error'] = (string)mysqli_connect_error();
        @mysqli_close($conn);
        return null;
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

function connect_mysql_without_db(string $host, string $user, string $pass, int $port): ?mysqli
{
    $GLOBALS['dashboard_last_mysql_connect_error'] = '';
    $conn = mysqli_init();
    if ($conn === false) {
        $GLOBALS['dashboard_last_mysql_connect_error'] = 'mysqli_init failed';
        return null;
    }
    @mysqli_options($conn, MYSQLI_OPT_CONNECT_TIMEOUT, 3);
    $maxPacketOpt = defined('MYSQLI_OPT_MAX_ALLOWED_PACKET') ? constant('MYSQLI_OPT_MAX_ALLOWED_PACKET') : null;
    if ($maxPacketOpt !== null) {
        @mysqli_options($conn, (int)$maxPacketOpt, 134217728);
    }
    if (!@mysqli_real_connect($conn, $host, $user, $pass, '', $port)) {
        $GLOBALS['dashboard_last_mysql_connect_error'] = (string)mysqli_connect_error();
        @mysqli_close($conn);
        return null;
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

function db_last_mysql_connect_error(): string
{
    return (string)($GLOBALS['dashboard_last_mysql_connect_error'] ?? '');
}

function db_is_protected_host(string $host): bool
{
    $normalized_host = trim(strtolower($host));
    return in_array($normalized_host, DASHBOARD_PROTECTED_DB_HOSTS, true);
}

function db_strip_sql_comments(string $sql): string
{
    $sql = preg_replace('/\/\*.*?\*\//s', ' ', $sql) ?? $sql;
    $sql = preg_replace('/--[^\r\n]*/', ' ', $sql) ?? $sql;
    return $sql;
}

function db_is_write_sql(string $sql): bool
{
    $sql = db_strip_sql_comments($sql);
    $sql = ltrim($sql);
    if ($sql === '') {
        return false;
    }
    $write_pattern = '/\b(INSERT|UPDATE|DELETE|ALTER|DROP|TRUNCATE|REPLACE|MERGE|CREATE|RENAME)\b/i';
    if (preg_match('/^\s*WITH\b/i', $sql) === 1) {
        return preg_match($write_pattern, $sql) === 1;
    }
    return preg_match('/^\s*(INSERT|UPDATE|DELETE|ALTER|DROP|TRUNCATE|REPLACE|MERGE|CREATE|RENAME)\b/i', $sql) === 1;
}

function db_allow_protected_host_writes(string $host): void
{
    $normalized_host = trim(strtolower($host));
    if ($normalized_host === '') {
        return;
    }
    if (!isset($GLOBALS['dashboard_allowed_protected_db_writes']) || !is_array($GLOBALS['dashboard_allowed_protected_db_writes'])) {
        $GLOBALS['dashboard_allowed_protected_db_writes'] = [];
    }
    $GLOBALS['dashboard_allowed_protected_db_writes'][$normalized_host] = true;
}

function db_is_protected_host_write_allowed(string $host): bool
{
    $normalized_host = trim(strtolower($host));
    if ($normalized_host === '') {
        return false;
    }
    return !empty($GLOBALS['dashboard_allowed_protected_db_writes'][$normalized_host]);
}

function db_should_block_sql(string $host, string $sql): bool
{
    return db_is_protected_host($host) && !db_is_protected_host_write_allowed($host) && db_is_write_sql($sql);
}

function db_mysqli_prepare(mysqli $conn, string $sql)
{
    $host = (string)($GLOBALS['mysqlActiveHost'] ?? $GLOBALS['sourceHost'] ?? '');
    if (db_should_block_sql($host, $sql)) {
        error_log('Dashboard DB guard: blocked MySQL write SQL on protected host ' . $host);
        return false;
    }
    return $conn->prepare($sql);
}

function db_mysqli_query(mysqli $conn, string $sql)
{
    $host = (string)($GLOBALS['mysqlActiveHost'] ?? $GLOBALS['sourceHost'] ?? '');
    if (db_should_block_sql($host, $sql)) {
        error_log('Dashboard DB guard: blocked MySQL write SQL on protected host ' . $host);
        return false;
    }
    return $conn->query($sql);
}

function db_oci_parse($conn, string $sql)
{
    $host = (string)($GLOBALS['oracleHost'] ?? '');
    if (db_should_block_sql($host, $sql)) {
        error_log('Dashboard DB guard: blocked Oracle write SQL on protected host ' . $host);
        return false;
    }
    return @oci_parse($conn, $sql);
}

function connect_oracle(string $user, string $pass, string $host, int $port, string $serviceName)
{
    if (!function_exists('oci_connect')) {
        return null;
    }
    $dsn = "(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST={$host})(PORT={$port}))(CONNECT_DATA=(SERVICE_NAME={$serviceName})))";
    $conn = @oci_connect($user, $pass, $dsn, 'AL32UTF8');
    if ($conn === false) {
        return null;
    }
    if (function_exists('oci_set_call_timeout')) {
        @oci_set_call_timeout($conn, 25000); // 8s per call
    }
    return $conn;
}

$sourceHost = env_or_default('DASHBOARD_SOURCE_DB_HOST', '10.64.0.251');
$sourceUser = env_or_default('DASHBOARD_SOURCE_DB_USER', 'root');
$sourcePass = env_or_default('DASHBOARD_SOURCE_DB_PASS', 'root123');
$sourceDbName = env_or_default('DASHBOARD_SOURCE_DB_NAME', 'dngws');
$sourcePort = env_int_or_default('DASHBOARD_SOURCE_DB_PORT', 3306);

$localHost = env_or_default('DASHBOARD_LOCAL_DB_HOST', '127.0.0.1');
$localUser = env_or_default('DASHBOARD_LOCAL_DB_USER', 'root');
$localPass = env_or_default('DASHBOARD_LOCAL_DB_PASS', '');
$localDbName = env_or_default('DASHBOARD_LOCAL_DB_NAME', 'dashboard_my');
$localPort = env_int_or_default('DASHBOARD_LOCAL_DB_PORT', 3307);

$oracleHost = env_or_default('DASHBOARD_ORACLE_HOST', '10.64.0.56');
$oraclePort = env_int_or_default('DASHBOARD_ORACLE_PORT', 1521);
$oracleUser = env_or_default('DASHBOARD_ORACLE_USER', 'INTELLECT');
$oraclePass = env_or_default('DASHBOARD_ORACLE_PASS', 'intellect');
$oracleServiceName = env_or_default('DASHBOARD_ORACLE_SERVICE_NAME', 'IMSREPORT');

function db_is_host_reachable(string $host, int $port, int $timeout = 3): bool
{
    $fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
    if ($fp !== false) {
        fclose($fp);
        return true;
    }
    return false;
}

$mysqlActiveHost = $sourceHost;
$conn = null;
if (db_is_host_reachable($sourceHost, $sourcePort, 2)) {
    $conn = connect_mysql($sourceHost, $sourceUser, $sourcePass, $sourceDbName, $sourcePort);
} else {
    error_log('Dashboard DB warning: source MySQL host unreachable at ' . $sourceHost . ':' . $sourcePort . ' (skipped connect).');
}
if ($conn === null) {
    error_log('Dashboard DB warning: source connect failed, fallback to local DB 127.0.0.1/dashboard_my.');
    if (db_is_host_reachable($localHost, $localPort, 2)) {
        $conn = connect_mysql($localHost, $localUser, $localPass, $localDbName, $localPort);
        $mysqlActiveHost = $localHost;
    }
}

if ($conn === null) {
    die('Khong ket noi duoc DB (source va local deu that bai).');
}

$oracle_conn = null;
if (db_is_host_reachable($oracleHost, $oraclePort, 2)) {
    $oracle_conn = connect_oracle($oracleUser, $oraclePass, $oracleHost, $oraclePort, $oracleServiceName);
} else {
    error_log('Dashboard DB warning: Oracle host unreachable at ' . $oracleHost . ':' . $oraclePort . ' (skipped connect).');
}
if ($oracle_conn === null) {
    error_log('Dashboard DB warning: Oracle connect failed or OCI8 extension missing.');
}
