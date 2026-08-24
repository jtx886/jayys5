<?php
// 数据库配置 - InfinityFree免费主机请根据实际情况修改
define('DB_HOST', 'localhost');
define('DB_NAME', 'jay_movie');
define('DB_USER', 'root');
define('DB_PASS', '');

// SMTP邮件配置
define('SMTP_HOST', 'smtp.163.com');
define('SMTP_PORT', 465);
define('SMTP_USER', 'jtxnb886@163.com');
define('SMTP_PASS', 'FLLRDtadYAfGXp9Y');
define('SMTP_FROM', 'jtxnb886@163.com');
define('SMTP_FROM_NAME', 'Jay影视');

// TMDB API配置
define('TMDB_API_KEY', 'cb44223c5dee5676ed3a839f42ed27e3');
define('TMDB_ACCESS_TOKEN', 'eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJjYjQ0MjIzYzVkZWU1Njc2ZWQzYTM4M2Y0MmVkMjdlMyIsInN1YiI6IjY0YjE4NDc2OTg2NTQwMDB1ZGVhYjU3YyIsInNjb3BlcyI6WyJhcGlfcmVhZCJdLCJ2ZXJzaW9uIjoxfQ.S321y0Gj0t6bFjRf3eQf7g8h9i0j1k2l3m4n5o6p7q8');
define('TMDB_BASE_URL', 'https://api.themoviedb.org/3');
define('TMDB_IMAGE_BASE', 'https://image.tmdb.org/t/p');

// 网站基础路径
$site_path = dirname($_SERVER['PHP_SELF']);
if ($site_path == '/' || $site_path == '\\') {
    $site_path = '';
}
define('SITE_URL', 'http://' . $_SERVER['HTTP_HOST'] . $site_path);
define('SITE_PATH', dirname(__FILE__));

// 启动session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 时区设置
date_default_timezone_set('Asia/Shanghai');

// 错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);
