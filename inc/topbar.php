<?php require_once __DIR__ . '/tools.php'; $active_slug = current_tool_slug(); ?>
<a class="skip-link" href="#content">Skip to content</a>
<header class="topbar">
  <div class="container topbar-inner">
    <a class="brand" href="index.php">
      <img src="img/mark.svg" alt="" width="28" height="28">
      <span><?= e(SITE_NAME) ?></span>
    </a>

    <p class="ticker" data-prices role="status" title="USD prices">
      <span data-symbol="ZNN">ZNN <b>–</b></span>
      <span data-symbol="QSR">QSR <b>–</b></span>
    </p>

    <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="toolMenu">
      <span class="menu-toggle-icon" aria-hidden="true"></span>
      <span class="menu-toggle-label">Tools</span>
    </button>
  </div>

  <nav class="tool-menu" id="toolMenu" aria-label="Tools" hidden>
    <div class="container tool-menu-inner">
      <?php foreach (TOOL_GROUPS as $group_key => $group_name): ?>
        <div class="tool-menu-group">
          <p class="tool-menu-heading"><?= e($group_name) ?></p>
          <ul>
            <?php foreach (TOOLS as $slug => $item): if ($item['group'] !== $group_key) continue; ?>
              <li>
                <a href="<?= e($slug) ?>.php" data-category="<?= e($item['category']) ?>"<?= $slug === $active_slug ? ' aria-current="page"' : '' ?>>
                  <?= e($item['title']) ?>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endforeach; ?>
    </div>
  </nav>
</header>
