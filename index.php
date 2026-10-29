<?php require_once __DIR__ . '/inc/tools.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/inc/head.php'; ?>
</head>
<body class="home">
<?php include __DIR__ . '/inc/topbar.php'; ?>

<main id="content" class="container">
  <section class="hero">
    <h1>Rewards, balances, Sentinels and bridge requests, one lookup away.</h1>
    <p>Paste an address or pick a network overview. Results are links you can bookmark and share.</p>
  </section>

  <?php foreach (TOOL_GROUPS as $group_key => $group_name): ?>
    <section class="tool-group" aria-labelledby="group-<?= e($group_key) ?>">
      <h2 id="group-<?= e($group_key) ?>"><?= e($group_name) ?></h2>
      <ul class="tool-grid">
        <?php foreach (TOOLS as $slug => $tool): if ($tool['group'] !== $group_key) continue; ?>
          <li>
            <a class="tool-card" href="/<?= e($slug) ?>" data-category="<?= e($tool['category']) ?>">
              <span class="tool-card-category"><?= e(CATEGORY_NAMES[$tool['category']]) ?></span>
              <span class="tool-card-title"><?= e($tool['title']) ?></span>
              <span class="tool-card-summary"><?= e($tool['summary']) ?></span>
              <span class="tool-card-input"><?= e($tool['input']) ?></span>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </section>
  <?php endforeach; ?>
</main>

<?php include __DIR__ . '/inc/footer.php'; ?>
</body>
</html>
