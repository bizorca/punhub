<?php
declare(strict_types=1);

define('MOD_KEY', '7f2e9a1c8b3d4f5e0c6a2b9d1e3f8a4c');

$providedKey = $_GET['key'] ?? $_POST['key'] ?? '';
if (!hash_equals(MOD_KEY, $providedKey)) {
    http_response_code(403);
    exit('403 Forbidden');
}

$punsFile  = __DIR__ . '/puns.json';
$queueFile = __DIR__ . '/queue.json';

function loadJson(string $file): array {
    if (!file_exists($file)) return [];
    return json_decode(file_get_contents($file), true) ?? [];
}

function saveJson(string $file, array $data): bool {
    $result = file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    return $result !== false;
}

function nextId(array $items): int {
    if (empty($items)) return 1;
    return max(array_column($items, 'id')) + 1;
}

// ── Handle actions ────────────────────────────────────────────────────────────

$msg   = '';
$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = $_POST['action'] ?? '';
    $id     = (int)($_POST['id'] ?? 0);

    if ($action === 'approve' && $id > 0) {
        $queue = loadJson($queueFile);
        $puns  = loadJson($punsFile);
        $found = false;
        foreach ($queue as $i => $item) {
            if ((int)$item['id'] === $id) {
                $puns[] = [
                    'id'    => nextId($puns),
                    'text'  => $item['text'],
                    'votes' => 0,
                ];
                array_splice($queue, $i, 1);
                $ok1 = saveJson($punsFile, $puns);
                $ok2 = saveJson($queueFile, array_values($queue));
                $msg = ($ok1 && $ok2) ? 'Approved.' : 'Save failed — check file permissions.';
                $found = true;
                break;
            }
        }
        if (!$found) $msg = 'Item not found (id=' . $id . ').';
    }

    if ($action === 'delete' && $id > 0) {
        $queue = loadJson($queueFile);
        $found = false;
        foreach ($queue as $i => $item) {
            if ((int)$item['id'] === $id) {
                array_splice($queue, $i, 1);
                $ok  = saveJson($queueFile, array_values($queue));
                $msg = $ok ? 'Deleted.' : 'Save failed — check file permissions.';
                $found = true;
                break;
            }
        }
        if (!$found) $msg = 'Item not found (id=' . $id . ').';
    }

    if ($action === 'delete_approved' && $id > 0) {
        $puns  = loadJson($punsFile);
        $found = false;
        foreach ($puns as $i => $p) {
            if ((int)$p['id'] === $id) {
                array_splice($puns, $i, 1);
                $ok  = saveJson($punsFile, array_values($puns));
                $msg = $ok ? 'Removed from pool.' : 'Save failed — check file permissions.';
                $found = true;
                break;
            }
        }
        if (!$found) $msg = 'Item not found (id=' . $id . ').';
    }

    header('Location: moderate.php?key=' . urlencode(MOD_KEY) . '&msg=' . urlencode($msg));
    exit;
}

$queue = loadJson($queueFile);
$puns  = loadJson($punsFile);
usort($puns, fn($a, $b) => $b['votes'] <=> $a['votes']);

$msg      = $_GET['msg'] ?? '';
$next     = $queue[0] ?? null;
$remaining = count($queue);

// Diagnostic info (visible only because page is key-gated)
$diag = [
    'queue_path'     => $queueFile,
    'queue_exists'   => file_exists($queueFile),
    'queue_writable' => is_writable($queueFile),
    'queue_count'    => count($queue),
    'first_id'       => $next['id'] ?? 'n/a',
    'puns_writable'  => is_writable($punsFile) || !file_exists($punsFile),
    'dir_writable'   => is_writable(__DIR__),
];

?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>PunHub — Moderation</title>
<style>
  * { box-sizing: border-box; }
  body { font-family: sans-serif; margin: 0; padding: 24px; background: #f5f5f5; color: #222; max-width: 700px; }
  h1 { color: #1c87c9; margin: 0 0 4px; }
  .meta { color: #888; font-size: 0.85em; margin-bottom: 24px; }

  .notice {
    padding: 10px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 0.95em;
  }
  .notice.ok  { background: #eafaf1; color: #1e8449; border: 1px solid #a9dfbf; }
  .notice.err { background: #fdf2f2; color: #c0392b; border: 1px solid #f5b7b1; }

  /* Review card */
  .review-card {
    background: #fff;
    border: 2px solid #1c87c9;
    border-radius: 12px;
    padding: 28px;
    margin-bottom: 32px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.07);
  }
  .review-label {
    font-size: 0.8em;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #1c87c9;
    margin-bottom: 12px;
  }
  .review-text {
    font-size: 1.3em;
    font-style: italic;
    color: #222;
    line-height: 1.5;
    margin-bottom: 8px;
  }
  .review-meta {
    font-size: 0.8em;
    color: #aaa;
    margin-bottom: 24px;
  }
  .review-actions {
    display: flex;
    gap: 12px;
  }
  .btn-big {
    flex: 1;
    padding: 14px;
    border: none;
    border-radius: 8px;
    font-size: 1.05em;
    font-weight: 700;
    cursor: pointer;
  }
  .btn-big-approve { background: #27ae60; color: #fff; }
  .btn-big-approve:hover { background: #1e8449; }
  .btn-big-delete  { background: #e74c3c; color: #fff; }
  .btn-big-delete:hover  { background: #c0392b; }

  .empty-queue {
    background: #fff;
    border: 2px dashed #ccc;
    border-radius: 12px;
    padding: 40px;
    text-align: center;
    color: #aaa;
    font-size: 1.1em;
    margin-bottom: 32px;
  }

  /* Approved table */
  .section { margin-bottom: 36px; }
  .section h2 { font-size: 1em; border-bottom: 2px solid #ddd; padding-bottom: 6px; color: #555; margin-bottom: 12px; }
  table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,0.06); }
  th { background: #eee; color: #555; text-align: left; padding: 8px 12px; font-size: 0.8em; font-weight: 600; }
  td { padding: 8px 12px; border-bottom: 1px solid #f0f0f0; font-size: 0.85em; vertical-align: middle; }
  tr:last-child td { border-bottom: none; }
  .pun-text { font-style: italic; }
  .votes-badge { font-weight: bold; color: #1c87c9; white-space: nowrap; }
  .btn { padding: 4px 10px; border: none; border-radius: 5px; font-size: 0.8em; cursor: pointer; font-weight: 600; background: #e74c3c; color: #fff; }
  .btn:hover { background: #c0392b; }
  form.inline { display: inline; margin: 0; }
</style>
</head>
<body>

<h1>PunHub Moderation</h1>
<p class="meta"><?= $remaining ?> pending &nbsp;·&nbsp; <?= count($puns) ?> approved &nbsp;·&nbsp; <a href="index.php">View site</a></p>
<p class="meta" style="font-size:0.75em;color:#bbb;">
  queue: <?= $diag['queue_count'] ?> items &nbsp;|&nbsp;
  first id: <?= htmlspecialchars((string)$diag['first_id']) ?> &nbsp;|&nbsp;
  writable: <?= $diag['queue_writable'] ? 'yes' : 'NO' ?> &nbsp;|&nbsp;
  dir: <?= $diag['dir_writable'] ? 'ok' : 'NO' ?>
</p>

<?php if ($msg): ?>
<p class="notice <?= str_contains($msg, 'failed') || str_contains($msg, 'not found') ? 'err' : 'ok' ?>"><?= htmlspecialchars($msg) ?></p>
<?php endif; ?>

<!-- Review card: one pun at a time -->
<?php if ($next): ?>
<div class="review-card">
  <div class="review-label">Next up — <?= $remaining ?> remaining</div>
  <div class="review-text"><?= htmlspecialchars($next['text']) ?></div>
  <div class="review-meta">Submitted <?= htmlspecialchars($next['submitted_at']) ?></div>
  <div class="review-actions">
    <form method="post" action="moderate.php" style="flex:1;display:flex;">
      <input type="hidden" name="key" value="<?= htmlspecialchars(MOD_KEY) ?>">
      <input type="hidden" name="action" value="approve">
      <input type="hidden" name="id" value="<?= (int)$next['id'] ?>">
      <button type="submit" class="btn-big btn-big-approve">Approve</button>
    </form>
    <form method="post" action="moderate.php" style="flex:1;display:flex;">
      <input type="hidden" name="key" value="<?= htmlspecialchars(MOD_KEY) ?>">
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="id" value="<?= (int)$next['id'] ?>">
      <button type="submit" class="btn-big btn-big-delete">Delete</button>
    </form>
  </div>
</div>
<?php else: ?>
<div class="empty-queue">Queue is empty. Nothing left to review.</div>
<?php endif; ?>

<!-- Approved Puns -->
<?php if (!empty($puns)): ?>
<div class="section">
  <h2>Approved Puns (<?= count($puns) ?>)</h2>
  <table>
    <thead>
      <tr><th>Pun</th><th>Votes</th><th></th></tr>
    </thead>
    <tbody>
      <?php foreach ($puns as $p): ?>
      <tr>
        <td class="pun-text"><?= htmlspecialchars($p['text']) ?></td>
        <td class="votes-badge"><?= (int)$p['votes'] ?></td>
        <td>
          <form class="inline" method="post" action="moderate.php">
            <input type="hidden" name="key" value="<?= htmlspecialchars(MOD_KEY) ?>">
            <input type="hidden" name="action" value="delete_approved">
            <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
            <button type="submit" class="btn" onclick="return confirm('Remove this pun?')">Remove</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

</body>
</html>
