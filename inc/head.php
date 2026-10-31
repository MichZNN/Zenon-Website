<?php
require_once __DIR__ . '/tools.php';

$tool        = current_tool();
$page_title  = $tool ? $tool['title'] . ' – ' . SITE_NAME : SITE_NAME;
$page_desc   = $tool ? $tool['intro'] : 'Look up rewards, balances, Sentinels, bridge requests and momentums.';
$page_url    = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/' . ($tool ? current_tool_slug() : '');
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#0E1420">
<title><?= e($page_title) ?></title>
<meta name="description" content="<?= e($page_desc) ?>">
<meta property="og:title" content="<?= e($page_title) ?>">
<meta property="og:description" content="<?= e($page_desc) ?>">
<meta property="og:url" content="<?= e($page_url) ?>">
<meta property="og:locale" content="en_US">
<link rel="icon" type="image/svg+xml" href="img/mark.svg">
<link rel="icon" type="image/png" sizes="96x96" href="img/mark-96.png">
<link rel="apple-touch-icon" sizes="180x180" href="img/mark-180.png">
<meta name="apple-mobile-web-app-title" content="<?= e(SITE_NAME) ?>">
<link rel="manifest" href="img/site.webmanifest">
<link rel="preload" href="fonts/Inter-Variable.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="fonts/BricolageGrotesque-Variable.woff2" as="font" type="font/woff2" crossorigin>
<link href="lib/bootstrap@5.3.6/css/bootstrap.min.css" rel="stylesheet">
<link href="lib/fontawesome@6.7.2/css/all.min.css" rel="stylesheet">
<link href="lib/twbs@1.13.1/icons/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= e(asset('css/site.css')) ?>" rel="stylesheet">
