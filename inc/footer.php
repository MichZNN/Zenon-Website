<?php require_once __DIR__ . '/tools.php'; ?>
<footer class="site-footer">
  <div class="container site-footer-inner">
    <a class="brand brand-small" href="/">
      <img src="img/mark.svg" alt="" width="20" height="20">
      <span><?= e(SITE_NAME) ?></span>
    </a>
    <div class="site-footer-meta">
      <p>Data from our own Zenon node, with <a href="https://zenonhub.io">Zenon Hub</a> as backup. Prices from DexScreener, refreshed every ten minutes.</p>
      <p class="footer-momentum" data-momentum>Momentum height <b class="skeleton skeleton-price" aria-label="Loading"></b></p>
    </div>
  </div>
</footer>
<script src="<?= e(asset('js/site.js')) ?>"></script>
