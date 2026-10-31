<?php require_once __DIR__ . '/tools.php'; ?>
<footer class="site-footer">
  <div class="container site-footer-inner">
    <a class="brand brand-small" href="/">
      <img src="img/mark.svg" alt="" width="20" height="20">
      <span><?= e(SITE_NAME) ?></span>
    </a>
    <div class="site-footer-meta">
      <p class="footer-momentum" data-momentum title="Momentum height"><i class="fa-solid fa-cube" aria-hidden="true"></i><span class="visually-hidden">Momentum height</span><b class="skeleton skeleton-price" aria-label="Loading"></b></p>
    </div>
  </div>
</footer>
<script src="<?= e(asset('js/site.js')) ?>"></script>
