<?php
declare(strict_types=1);

define('MOD_KEY', 'd4e8b3f1a06c927e5d2b84f1c39a07e6');

$key = $_GET['key'] ?? '';
if (!hash_equals(MOD_KEY, $key)) {
    http_response_code(403);
    exit('403 Forbidden');
}

$queueFile = __DIR__ . '/queue.json';
$punsFile  = __DIR__ . '/puns.json';

function loadJson(string $file): array {
    if (!file_exists($file)) return [];
    return json_decode(file_get_contents($file), true) ?? [];
}

function saveJson(string $file, array $data): void {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

function nextId(array $items): int {
    if (empty($items)) return 1;
    return max(array_column($items, 'id')) + 1;
}

// ── Handle POST ───────────────────────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id     = (int)($_POST['id'] ?? 0);

    if ($id > 0 && in_array($action, ['approve', 'delete'])) {
        $queue = loadJson($queueFile);

        foreach ($queue as &$item) {
            if ((int)$item['id'] === $id) {
                $item['status'] = $action === 'approve' ? 'approved' : 'deleted';
                if ($action === 'approve') {
                    $puns   = loadJson($punsFile);
                    $puns[] = ['id' => nextId($puns), 'text' => $item['text'], 'votes' => 0];
                    saveJson($punsFile, $puns);
                }
                break;
            }
        }
        unset($item);

        saveJson($queueFile, $queue);
    }

    header('Location: moderate.php?key=' . MOD_KEY);
    exit;
}

// ── Load data ─────────────────────────────────────────────────────────────────

$queue    = loadJson($queueFile);
$pending  = array_values(array_filter($queue, fn($item) => ($item['status'] ?? 'pending') === 'pending'));
$next     = $pending[0] ?? null;
$nPending = count($pending);
$nApproved = count(array_filter($queue, fn($item) => ($item['status'] ?? 'pending') === 'approved'));

?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>PunHub — Moderation</title>
<style>
  * { box-sizing: border-box; }
  body { font-family: sans-serif; margin: 0; padding: 32px 24px; background: #f5f5f5; color: #222; max-width: 600px; }
  h1 { color: #1c87c9; margin: 0 0 4px; }
  .meta { color: #888; font-size: 0.85em; margin-bottom: 28px; }

  .card {
    background: #fff;
    border: 2px solid #1c87c9;
    border-radius: 12px;
    padding: 28px;
    margin-bottom: 20px;
  }
  .label { font-size: 0.75em; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #1c87c9; margin-bottom: 12px; }
  .pun-text { font-size: 1.25em; font-style: italic; line-height: 1.5; margin-bottom: 20px; }
  .actions { display: flex; gap: 12px; }
  .btn { flex: 1; padding: 14px; border: none; border-radius: 8px; font-size: 1em; font-weight: 700; cursor: pointer; }
  .approve { background: #27ae60; color: #fff; }
  .approve:hover { background: #1e8449; }
  .delete  { background: #e74c3c; color: #fff; }
  .delete:hover  { background: #c0392b; }

  .empty { background: #fff; border: 2px dashed #ccc; border-radius: 12px; padding: 40px; text-align: center; color: #aaa; }
</style>
</head>
<body>

<h1>PunHub Moderation</h1>
<p class="meta"><?= $nPending ?> pending &nbsp;·&nbsp; <?= $nApproved ?> approved &nbsp;·&nbsp; <a href="index.php">View site</a></p>

<?php if ($next): ?>
<div class="card">
  <div class="label">Next up — <?= $nPending ?> remaining</div>
  <div class="pun-text"><?= htmlspecialchars($next['text']) ?></div>
  <div class="actions">
    <form method="post" action="moderate.php?key=<?= MOD_KEY ?>" style="flex:1;display:flex;">
      <input type="hidden" name="action" value="approve">
      <input type="hidden" name="id" value="<?= (int)$next['id'] ?>">
      <button type="submit" class="btn approve">Approve</button>
    </form>
    <form method="post" action="moderate.php?key=<?= MOD_KEY ?>" style="flex:1;display:flex;">
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="id" value="<?= (int)$next['id'] ?>">
      <button type="submit" class="btn delete">Delete</button>
    </form>
  </div>
</div>
<?php else: ?>
<div class="empty">Queue is empty. Nothing left to review.</div>
<?php endif; ?>

</body>
</html>
