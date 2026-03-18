<?php
declare(strict_types=1);
$showFooter          = $showFooter          ?? true;
$showFooterThemeMenu = $showFooterThemeMenu ?? true;
$footerLinks         = $footerLinks         ?? [];
$jsPathFs = __DIR__ . '/../public/assets/js/app.js';
$jsVer    = is_file($jsPathFs) ? (string) filemtime($jsPathFs) : (string) time();

// Git-Datum gecacht in DB (max. 1x pro Stunde neu gelesen)
$gitDate     = '';
$gitCached   = getConfig('footer_git_date_cache');
$gitCachedTs = (int)getConfig('footer_git_date_ts');
if ($gitCached && (time() - $gitCachedTs) < 3600) {
    $gitDate = $gitCached;
} else {
    $raw = shell_exec('git -C ' . escapeshellarg(__DIR__ . '/..') . ' log -1 --format=%cd --date=format:"%d.%m.%Y" 2>/dev/null');
    $gitDate = $raw ? trim($raw) : date('d.m.Y');
    setConfig('footer_git_date_cache', $gitDate);
    setConfig('footer_git_date_ts', (string)time());
}
?>
<?php if ($showFooter): ?>
  <footer class="app-footer">

    <div class="footer-left">
      <span class="footer-date"><?= htmlspecialchars($gitDate, ENT_QUOTES, 'UTF-8') ?></span>
      <?php foreach ($footerLinks as $link): ?>
        <a href="<?= htmlspecialchars($link['href'], ENT_QUOTES, 'UTF-8') ?>">
          <?= htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8') ?>
        </a>
      <?php endforeach; ?>
    </div>

    <div class="footer-right">
      <?php if ($showFooterThemeMenu): ?>
        <div class="footer-theme">
          <button id="theme-menu-toggle" class="btn" type="button" aria-label="Theme auswählen">
            <span class="icon icon-dark" aria-hidden="true"></span>
            <span id="theme-label">Dark</span>
          </button>
          <div id="theme-menu" class="theme-menu">
            <button class="theme-menu-item" type="button" data-theme="light">
              <span class="icon icon-light" aria-hidden="true"></span>
              Light
            </button>
            <button class="theme-menu-item" type="button" data-theme="grey">
              <span class="icon icon-grey" aria-hidden="true"></span>
              Grey
            </button>
            <button class="theme-menu-item" type="button" data-theme="dark">
              <span class="icon icon-dark" aria-hidden="true"></span>
              Dark
            </button>
            <button class="theme-menu-item" type="button" data-theme="contrast">
              <span class="icon icon-contrast" aria-hidden="true"></span>
              High Contrast
            </button>
          </div>
        </div>
      <?php endif; ?>
    </div>

  </footer>
<?php endif; ?>
</div>
<script src="/assets/js/app.js?v=<?= htmlspecialchars($jsVer, ENT_QUOTES, 'UTF-8') ?>"></script>
</body>
</html>
