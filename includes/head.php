<?php
declare(strict_types=1);
$title           = $title           ?? 'QR Webapp';
$showHeader      = $showHeader      ?? true;
$showThemeToggle = $showThemeToggle ?? true;
$headerTitle     = $headerTitle     ?? $title;
$bodyClass       = $bodyClass       ?? '';
$containerClass  = $containerClass  ?? 'container';
$extraCss        = $extraCss        ?? [];
$globalCssVer    = is_file(__DIR__ . '/../public/assets/css/global.css') ? (string) filemtime(__DIR__ . '/../public/assets/css/global.css') : (string) time();
?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
  <link rel="stylesheet" href="/assets/css/global.css?v=<?= htmlspecialchars($globalCssVer, ENT_QUOTES, 'UTF-8') ?>">
  <?php foreach ($extraCss as $css): ?>
    <?php $ver = is_file(__DIR__ . '/../public/assets/css/' . $css) ? (string) filemtime(__DIR__ . '/../public/assets/css/' . $css) : (string) time(); ?>
    <link rel="stylesheet" href="/assets/css/<?= htmlspecialchars($css, ENT_QUOTES, 'UTF-8') ?>?v=<?= htmlspecialchars($ver, ENT_QUOTES, 'UTF-8') ?>">
  <?php endforeach; ?>
</head>
<body class="<?= htmlspecialchars($bodyClass, ENT_QUOTES, 'UTF-8') ?>">
<div class="<?= htmlspecialchars($containerClass, ENT_QUOTES, 'UTF-8') ?>">
<?php if ($showHeader): ?>
  <header class="app-header">
    <h1 class="app-title"><?= htmlspecialchars($headerTitle, ENT_QUOTES, 'UTF-8') ?></h1>
    <?php if ($showThemeToggle): ?>
      <button id="theme-toggle" class="theme-switch" type="button" aria-label="Theme wechseln">
        <span id="theme-icon" class="icon icon-dark" aria-hidden="true"></span>
      </button>
    <?php endif; ?>
  </header>
<?php endif; ?>
