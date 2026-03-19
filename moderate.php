<?php
declare(strict_types=1);

// ── Set your secret key here ──────────────────────────────────────────────────
// Visit: https://punhub.com/moderate.php?key=7f2e9a1c8b3d4f5e0c6a2b9d1e3f8a4c
// Change this to something only you know before deploying.
define('MOD_KEY', '7f2e9a1c8b3d4f5e0c6a2b9d1e3f8a4c');
// ─────────────────────────────────────────────────────────────────────────────

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

function saveJson(string $file, array $data): void {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

function nextId(array $items): int {
    if (empty($items)) return 1;
    return max(array_column($items, 'id')) + 1;
}

// ── Handle actions ────────────────────────────────────────────────────────────

$msg = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = $_POST['action'] ?? '';
    $id     = (int)($_POST['id'] ?? 0);
    $key    = $_POST['key'] ?? '';

    if ($action === 'approve' && $id > 0) {
        $queue = loadJson($queueFile);
        $puns  = loadJson($punsFile);
        foreach ($queue as $i => $item) {
            if ($item['id'] === $id) {
                $puns[] = [
                    'id'    => nextId($puns),
                    'text'  => $item['text'],
                    'votes' => 0,
                ];
                array_splice($queue, $i, 1);
                saveJson($punsFile, $puns);
                saveJson($queueFile, array_values($queue));
                $msg = 'Pun approved and added to the pool.';
                break;
            }
        }
    }

    if ($action === 'delete' && $id > 0) {
        $queue = loadJson($queueFile);
        foreach ($queue as $i => $item) {
            if ($item['id'] === $id) {
                array_splice($queue, $i, 1);
                saveJson($queueFile, array_values($queue));
                $msg = 'Pun deleted.';
                break;
            }
        }
    }

    if ($action === 'delete_approved' && $id > 0) {
        $puns = loadJson($punsFile);
        foreach ($puns as $i => $p) {
            if ($p['id'] === $id) {
                array_splice($puns, $i, 1);
                saveJson($punsFile, array_values($puns));
                $msg = 'Approved pun removed from pool.';
                break;
            }
        }
    }

    header('Location: moderate.php?key=' . urlencode(MOD_KEY) . '&msg=' . urlencode($msg));
    exit;
}

$queue = loadJson($queueFile);
$puns  = loadJson($punsFile);
usort($puns, fn($a, $b) => $b['votes'] <=> $a['votes']);

$msg = $_GET['msg'] ?? '';

?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>PunHub — Moderation</title>
<style>
  * { box-sizing: border-box; }
  body { font-family: sans-serif; margin: 0; padding: 24px; background: #f5f5f5; color: #222; }
  h1 { color: #1c87c9; margin: 0 0 4px; }
  .meta { color: #888; font-size: 0.85em; margin-bottom: 24px; }

  .notice {
    background: #eafaf1; color: #1e8449; border: 1px solid #a9dfbf;
    padding: 10px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 0.95em;
  }

  .section { margin-bottom: 36px; }
  .section h2 { font-size: 1.1em; border-bottom: 2px solid #1c87c9; padding-bottom: 6px; color: #1c87c9; }

  .empty { color: #aaa; font-style: italic; }

  table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,0.06); }
  th { background: #1c87c9; color: #fff; text-align: left; padding: 10px 14px; font-size: 0.85em; font-weight: 600; }
  td { padding: 10px 14px; border-bottom: 1px solid #f0f0f0; font-size: 0.9em; vertical-align: middle; }
  tr:last-child td { border-bottom: none; }
  tr:hover td { background: #f9fdff; }

  .pun-text { font-style: italic; }
  .submitted-at { color: #aaa; font-size: 0.8em; white-space: nowrap; }
  .votes-badge { font-weight: bold; color: #1c87c9; }

  .btn {
    padding: 5px 12px; border: none; border-radius: 5px;
    font-size: 0.82em; cursor: pointer; font-weight: 600;
  }
  .btn-approve { background: #27ae60; color: #fff; margin-right: 4px; }
  .btn-approve:hover { background: #1e8449; }
  .btn-delete  { background: #e74c3c; color: #fff; }
  .btn-delete:hover  { background: #c0392b; }

  form.inline { display: inline; margin: 0; }
</style>
</head>
<body>

<h1>PunHub Moderation</h1>
<p class="meta">Logged in as admin &nbsp;·&nbsp; <a href="index.php">View site</a></p>

<?php if ($msg): ?>
<p class="notice"><?= htmlspecialchars($msg) ?></p>
<?php endif; ?>

<!-- Pending Queue -->
<div class="section">
  <h2>Pending Review (<?= count($queue) ?>)</h2>
  <?php if (empty($queue)): ?>
    <p class="empty">No submissions waiting.</p>
  <?php else: ?>
  <table>
    <thead>
      <tr>
        <th>Pun</th>
        <th>Submitted</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($queue as $item): ?>
      <tr>
        <td class="pun-text"><?= htmlspecialchars($item['text']) ?></td>
        <td class="submitted-at"><?= htmlspecialchars($item['submitted_at']) ?></td>
        <td>
          <form class="inline" method="post" action="moderate.php">
            <input type="hidden" name="key" value="<?= htmlspecialchars(MOD_KEY) ?>">
            <input type="hidden" name="action" value="approve">
            <input type="hidden" name="id" value="<?= $item['id'] ?>">
            <button type="submit" class="btn btn-approve">Approve</button>
          </form>
          <form class="inline" method="post" action="moderate.php">
            <input type="hidden" name="key" value="<?= htmlspecialchars(MOD_KEY) ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= $item['id'] ?>">
            <button type="submit" class="btn btn-delete" onclick="return confirm('Delete this pun?')">Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

<!-- Approved Puns -->
<div class="section">
  <h2>Approved Puns (<?= count($puns) ?>)</h2>
  <?php if (empty($puns)): ?>
    <p class="empty">No approved puns yet.</p>
  <?php else: ?>
  <table>
    <thead>
      <tr>
        <th>Pun</th>
        <th>Votes</th>
        <th>Remove</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($puns as $p): ?>
      <tr>
        <td class="pun-text"><?= htmlspecialchars($p['text']) ?></td>
        <td class="votes-badge"><?= $p['votes'] ?></td>
        <td>
          <form class="inline" method="post" action="moderate.php">
            <input type="hidden" name="key" value="<?= htmlspecialchars(MOD_KEY) ?>">
            <input type="hidden" name="action" value="delete_approved">
            <input type="hidden" name="id" value="<?= $p['id'] ?>">
            <button type="submit" class="btn btn-delete" onclick="return confirm('Remove this pun from the pool?')">Remove</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

</body>
</html>
