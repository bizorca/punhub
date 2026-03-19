<?php
declare(strict_types=1);

header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

$punsFile  = __DIR__ . '/puns.json';
$queueFile = __DIR__ . '/queue.json';

// ── Helpers ──────────────────────────────────────────────────────────────────

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

$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'vote') {
        $votedId = (int)($_POST['vote_id'] ?? 0);
        if ($votedId > 0) {
            $puns = loadJson($punsFile);
            foreach ($puns as &$pun) {
                if ($pun['id'] === $votedId) {
                    $pun['votes']++;
                    break;
                }
            }
            unset($pun);
            saveJson($punsFile, $puns);
        }
        header('Location: index.php?voted=1&t=' . time());
        exit;
    }

    if ($action === 'submit') {
        $text = trim($_POST['pun_text'] ?? '');
        if ($text === '') {
            $error = 'Please enter a pun.';
        } elseif (mb_strlen($text) > 300) {
            $error = 'Keep it under 300 characters.';
        } else {
            $queue   = loadJson($queueFile);
            $queue[] = [
                'id'           => nextId($queue),
                'text'         => $text,
                'submitted_at' => date('Y-m-d H:i:s'),
            ];
            saveJson($queueFile, $queue);
            header('Location: index.php?submitted=1&t=' . time());
            exit;
        }
    }
}

// ── Load data ─────────────────────────────────────────────────────────────────

$puns        = loadJson($punsFile);
$showVoted   = isset($_GET['voted']);
$showSuccess = isset($_GET['submitted']);

// Pick two random puns for the vote
$punA = $punB = null;
if (count($puns) >= 2) {
    $keys = array_rand($puns, 2);
    $punA = $puns[$keys[0]];
    $punB = $puns[$keys[1]];
}

// Pick one random pun for the header display
$headerPun = $puns ? $puns[array_rand($puns)]['text'] : 'Why don\'t scientists trust atoms? Because they make up everything.';

// Top 5 leaderboard
$sorted = $puns;
usort($sorted, fn($a, $b) => $b['votes'] <=> $a['votes']);
$topFive = array_slice($sorted, 0, 5);

?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>PunHub</title>
<style>
  * { box-sizing: border-box; }
  body { font-family: sans-serif; margin: 0; padding: 20px; background: #f9f9f9; color: #222; }

  .site-header {
    text-align: center;
    margin-bottom: 30px;
  }
  .site-header h1 {
    font-size: 2.2em;
    margin: 0 0 4px;
    color: #1c87c9;
  }
  .site-header .tagline {
    color: #888;
    font-size: 0.9em;
  }

  .pun-of-day {
    width: min(600px, 100%);
    margin: 0 auto 30px;
    background: #fff;
    border: 3px dashed #1c87c9;
    border-radius: 12px;
    padding: 20px 28px;
    text-align: center;
    font-size: 1.15em;
    font-style: italic;
    color: #333;
  }

  .section {
    width: min(680px, 100%);
    margin: 0 auto 30px;
    background: #fff;
    border: 3px dashed #1c87c9;
    border-radius: 12px;
    padding: 24px 28px;
  }
  .section h2 {
    margin: 0 0 6px;
    font-size: 1.2em;
    color: #1c87c9;
  }
  .section .sub {
    font-size: 0.85em;
    color: #888;
    margin: 0 0 18px;
  }

  /* Voting cards */
  .vote-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
  }
  .vote-form { margin: 0; }
  .vote-card {
    width: 100%;
    background: #f0f8ff;
    border: 2px solid #1c87c9;
    border-radius: 10px;
    padding: 20px 16px;
    font-size: 1em;
    font-style: italic;
    color: #222;
    cursor: pointer;
    text-align: center;
    line-height: 1.5;
    transition: background 0.15s, transform 0.1s;
    min-height: 100px;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .vote-card:hover {
    background: #d0eaf8;
    transform: translateY(-2px);
  }
  .vote-vs {
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    color: #aaa;
    font-size: 1.1em;
  }

  .notice {
    text-align: center;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 0;
    font-size: 0.95em;
  }
  .notice.success { background: #eafaf1; color: #1e8449; border: 1px solid #a9dfbf; }
  .notice.voted   { background: #eaf4fb; color: #1c87c9; border: 1px solid #aed6f1; }

  /* Submission form */
  .submit-form textarea {
    width: 100%;
    padding: 10px 12px;
    font-size: 0.95em;
    border: 2px solid #ccc;
    border-radius: 8px;
    resize: vertical;
    min-height: 80px;
    font-family: inherit;
  }
  .submit-form textarea:focus { outline: none; border-color: #1c87c9; }
  .submit-form button {
    margin-top: 10px;
    padding: 9px 22px;
    background: #1c87c9;
    color: #fff;
    border: none;
    border-radius: 7px;
    font-size: 0.95em;
    cursor: pointer;
  }
  .submit-form button:hover { background: #166da0; }
  .error { color: #c0392b; font-size: 0.9em; margin-top: 6px; }
  .char-count { font-size: 0.8em; color: #aaa; text-align: right; margin-top: 4px; }

  /* Leaderboard */
  .leaderboard ol { padding-left: 20px; margin: 0; }
  .leaderboard li {
    padding: 8px 0;
    border-bottom: 1px solid #f0f0f0;
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    gap: 10px;
  }
  .leaderboard li:last-child { border-bottom: none; }
  .leaderboard .pun-text { font-style: italic; flex: 1; }
  .leaderboard .votes {
    font-size: 0.8em;
    font-weight: bold;
    color: #1c87c9;
    white-space: nowrap;
  }
  .leaderboard .no-votes { color: #aaa; font-size: 0.9em; font-style: italic; }

  @media (max-width: 500px) {
    .vote-grid { grid-template-columns: 1fr; }
    .vote-vs { display: none; }
  }
</style>
</head>
<body>

<!-- Header -->
<div class="site-header">
  <h1>PunHub</h1>
  <p class="tagline">The internet's least professional pun competition.</p>
</div>

<!-- Pun of the moment -->
<div class="pun-of-day">
  &ldquo;<?= htmlspecialchars($headerPun) ?>&rdquo;
</div>

<!-- Voting -->
<div class="section">
  <h2>Which one is funnier?</h2>
  <p class="sub">Click your pick. The crowd decides.</p>

  <?php if ($showVoted): ?>
    <p class="notice voted">Vote counted. Here's the next round.</p>
  <?php endif; ?>

  <?php if ($punA && $punB): ?>
  <div class="vote-grid">
    <form class="vote-form" method="post" action="index.php">
      <input type="hidden" name="action" value="vote">
      <input type="hidden" name="vote_id" value="<?= $punA['id'] ?>">
      <button type="submit" class="vote-card"><?= htmlspecialchars($punA['text']) ?></button>
    </form>

    <div class="vote-vs">vs</div>

    <form class="vote-form" method="post" action="index.php">
      <input type="hidden" name="action" value="vote">
      <input type="hidden" name="vote_id" value="<?= $punB['id'] ?>">
      <button type="submit" class="vote-card"><?= htmlspecialchars($punB['text']) ?></button>
    </form>
  </div>
  <?php else: ?>
    <p style="color:#888; text-align:center;">Not enough puns loaded yet. Check back soon.</p>
  <?php endif; ?>
</div>

<!-- Submit a pun -->
<div class="section">
  <h2>Got a better one?</h2>
  <p class="sub">Submit your pun for review. If it's clean and funny, it goes in the pool.</p>

  <?php if ($showSuccess): ?>
    <p class="notice success">Submitted! It'll show up once it's been reviewed.</p>
  <?php else: ?>
  <form class="submit-form" method="post" action="index.php">
    <input type="hidden" name="action" value="submit">
    <textarea
      name="pun_text"
      placeholder="Type your pun here…"
      maxlength="300"
      oninput="document.getElementById('char-count').textContent = this.value.length + '/300'"
    ><?= htmlspecialchars($_POST['pun_text'] ?? '') ?></textarea>
    <p class="char-count"><span id="char-count">0</span>/300</p>
    <?php if ($error): ?>
      <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <button type="submit">Submit for Review</button>
  </form>
  <?php endif; ?>
</div>

<!-- Leaderboard -->
<div class="section leaderboard">
  <h2>Top 5 Puns</h2>
  <p class="sub">Ranked by votes. Updated in real time.</p>
  <?php if (empty($topFive)): ?>
    <p class="no-votes">No votes yet — be the first!</p>
  <?php else: ?>
  <ol>
    <?php foreach ($topFive as $p): ?>
    <li>
      <span class="pun-text"><?= htmlspecialchars($p['text']) ?></span>
      <span class="votes"><?= $p['votes'] ?> <?= $p['votes'] === 1 ? 'vote' : 'votes' ?></span>
    </li>
    <?php endforeach; ?>
  </ol>
  <?php endif; ?>
</div>

</body>
</html>


<?php
/* ============================================================================
   DEPRECATED: Chain Pun Challenge
   Removed from display because it was being abused with inappropriate content.
   Code preserved below for reference.
   ============================================================================

function loadState(string $stateFile, string $dataFile): array
{
    if (file_exists($stateFile)) {
        $decoded = json_decode(file_get_contents($stateFile), true);
        if (is_array($decoded) && isset($decoded['current_pun'])) {
            return $decoded;
        }
    }
    $lines = file($dataFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $pun   = $lines[array_rand($lines)];
    $state = ['current_pun' => $pun, 'recent_puns' => []];
    saveState($stateFile, $state);
    return $state;
}

function saveState(string $stateFile, array $state): void
{
    file_put_contents($stateFile, json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function lastWord(string $pun): string
{
    $words = explode(' ', trim($pun));
    $word  = end($words);
    return strtolower(trim($word, ".,!?;:'\""));
}

function firstWord(string $pun): string
{
    $words = explode(' ', trim($pun));
    $word  = reset($words);
    return strtolower(trim($word, ".,!?;:'\""));
}

// Chain Pun Challenge HTML section (was inside <body>):
//
// <div class="game-section">
//   <h2>Chain Pun Challenge</h2>
//   <p><strong>Current pun:</strong></p>
//   <div class="current-pun-box">&ldquo;<?php echo htmlspecialchars($currentPun); ?>&rdquo;</div>
//   <p class="required-word">Your pun must start with: &ldquo;<?php echo htmlspecialchars($requiredWord); ?>&rdquo;</p>
//   <form class="pun-form" method="post" action="index.php">
//     <input type="text" name="pun" placeholder="Type your pun here&hellip;" autofocus>
//     <button type="submit">Submit</button>
//   </form>
// </div>

============================================================================ */
?>
