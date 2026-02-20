<?php
declare(strict_types=1);

$stateFile = __DIR__ . '/game_state.json';
$dataFile  = __DIR__ . '/data.txt';

// ── Helper functions ────────────────────────────────────────────────────────

function loadState(string $stateFile, string $dataFile): array
{
    if (file_exists($stateFile)) {
        $decoded = json_decode(file_get_contents($stateFile), true);
        if (is_array($decoded) && isset($decoded['current_pun'])) {
            return $decoded;
        }
    }
    // First run: pick a random line from data.txt
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

// ── Handle POST submission ──────────────────────────────────────────────────

$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $submitted = trim($_POST['pun'] ?? '');
    $state     = loadState($stateFile, $dataFile);

    if ($submitted === '') {
        $error = 'Please enter a pun.';
    } else {
        $required  = lastWord($state['current_pun']);
        $firstW    = firstWord($submitted);

        if ($firstW !== $required) {
            $error = 'Your pun must start with "' . htmlspecialchars($required) . '" (you started with "' . htmlspecialchars($firstW) . '")';
        } else {
            // Valid — update state
            $recent = $state['recent_puns'];
            $recent[] = $state['current_pun'];
            if (count($recent) > 5) {
                $recent = array_slice($recent, -5);
            }
            $state['recent_puns'] = $recent;
            $state['current_pun'] = $submitted;

            file_put_contents($dataFile, $submitted . "\n", FILE_APPEND | LOCK_EX);
            saveState($stateFile, $state);

            // PRG redirect
            header('Location: index.php?ok=1');
            exit;
        }
    }
}

// ── Load state for display ──────────────────────────────────────────────────

$state       = loadState($stateFile, $dataFile);
$currentPun  = $state['current_pun'];
$recentPuns  = $state['recent_puns'];
$requiredWord = lastWord($currentPun);
$showSuccess  = isset($_GET['ok']);

?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>PunHub</title>
<style>
  body { font-family: sans-serif; margin: 0; padding: 20px; background: #f9f9f9; }
  h1 { font-size: 24pt; margin: 0; }
  h2 { font-size: 16pt; margin-top: 0; }

  .pun-box {
    display: flex;
    width: 50%;
    min-height: 100px;
    margin: 20px auto;
    border-radius: 10px;
    border: 3px dashed #1c87c9;
    align-items: center;
    justify-content: center;
    padding: 20px;
    box-sizing: border-box;
    text-align: center;
  }

  .game-section {
    width: 50%;
    margin: 20px auto;
    background: #fff;
    border-radius: 10px;
    border: 3px dashed #1c87c9;
    padding: 24px;
    box-sizing: border-box;
  }

  .current-pun-box {
    background: #eaf4fb;
    border-radius: 8px;
    border: 2px solid #1c87c9;
    padding: 16px;
    font-style: italic;
    font-size: 1.1em;
    margin-bottom: 12px;
  }

  .required-word {
    font-weight: bold;
    color: #1c87c9;
    font-size: 1.05em;
    margin-bottom: 14px;
  }

  .pun-form input[type="text"] {
    width: 100%;
    padding: 10px;
    font-size: 1em;
    border: 2px solid #ccc;
    border-radius: 6px;
    box-sizing: border-box;
    margin-bottom: 8px;
  }

  .pun-form button {
    padding: 10px 22px;
    font-size: 1em;
    background: #1c87c9;
    color: #fff;
    border: none;
    border-radius: 6px;
    cursor: pointer;
  }

  .pun-form button:hover { background: #166da0; }

  .error   { color: #c0392b; margin-top: 8px; font-weight: bold; }
  .success { color: #27ae60; margin-bottom: 10px; font-weight: bold; }

  .recent-section { margin-top: 22px; }
  .recent-section h3 { border-bottom: 1px solid #ddd; padding-bottom: 6px; }
  .recent-section ol { padding-left: 20px; }
  .recent-section li { margin-bottom: 6px; }
</style>
</head>
<body>

<!-- Random pun of the day -->
<div class="pun-box">
  <h1><?php include __DIR__ . '/randy.php'; ?></h1>
</div>

<!-- Chain Pun Game -->
<div class="game-section">
  <h2>Chain Pun Challenge</h2>

  <?php if ($showSuccess): ?>
    <p class="success">Pun accepted! Keep the chain going.</p>
  <?php endif; ?>

  <p><strong>Current pun:</strong></p>
  <div class="current-pun-box">
    &ldquo;<?php echo htmlspecialchars($currentPun); ?>&rdquo;
  </div>

  <p class="required-word">
    Your pun must start with: &ldquo;<?php echo htmlspecialchars($requiredWord); ?>&rdquo;
  </p>

  <form class="pun-form" method="post" action="index.php">
    <input
      type="text"
      name="pun"
      placeholder="Type your pun here&hellip;"
      value="<?php echo htmlspecialchars($_POST['pun'] ?? ''); ?>"
      autofocus
    >
    <?php if ($error !== ''): ?>
      <p class="error"><?php echo $error; ?></p>
    <?php endif; ?>
    <button type="submit">Submit</button>
  </form>

  <?php if (!empty($recentPuns)): ?>
    <div class="recent-section">
      <h3>Recent puns</h3>
      <ol>
        <?php foreach (array_reverse($recentPuns) as $p): ?>
          <li><?php echo htmlspecialchars($p); ?></li>
        <?php endforeach; ?>
      </ol>
    </div>
  <?php endif; ?>
</div>

</body>
</html>
