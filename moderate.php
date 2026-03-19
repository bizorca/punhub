<?php
declare(strict_types=1);

define('MOD_KEY', '7f2e9a1c8b3d4f5e0c6a2b9d1e3f8a4c');

$providedKey = $_GET['key'] ?? $_POST['key'] ?? '';
if (!hash_equals(MOD_KEY, $providedKey)) {
    http_response_code(403);
    exit('403 Forbidden');
}

// Force OPcache to recompile this file on every request (admin-only page, no perf concern)
if (function_exists('opcache_invalidate')) opcache_invalidate(__FILE__, true);

$punsFile  = __DIR__ . '/puns.json';
$queueFile = __DIR__ . '/queue.json';

// ── Seed data — 100 clean puns, none overlapping the 15 already in puns.json ─
$SEED_QUEUE = [
    ["id"=>16,"text"=>"I stayed up all night wondering where the sun went. Then it dawned on me.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>17,"text"=>"I'm on a seafood diet. Every time I see food, I eat it.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>18,"text"=>"Why don't eggs tell jokes? They'd crack each other up.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>19,"text"=>"I used to be a banker, but I lost interest.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>20,"text"=>"What do you call a belt made of watches? A waist of time.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>21,"text"=>"Why can't you trust stairs? They're always up to something.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>22,"text"=>"I got a job at a bakery because I kneaded dough.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>23,"text"=>"Why did the golfer bring extra pants? In case he got a hole in one.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>24,"text"=>"I'm friends with all electricians. We have good current connections.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>25,"text"=>"What do you call a sleeping dinosaur? A dino-snore.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>26,"text"=>"I couldn't figure out how lightning works, then it struck me.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>27,"text"=>"Why do cows wear bells? Because their horns don't work.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>28,"text"=>"Did you hear about the cheese factory that exploded? There was nothing left but de-brie.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>29,"text"=>"I'm so good at sleeping I can do it with my eyes closed.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>30,"text"=>"I used to hate math, but then I realized decimals have a point.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>31,"text"=>"What do you call a factory that makes okay products? A satisfactory.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>32,"text"=>"I can't believe I got fired from the calendar factory. All I did was take a day off.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>33,"text"=>"I went to a seafood disco last week and pulled a mussel.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>34,"text"=>"What do you call a pony with a cough? A little hoarse.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>35,"text"=>"I know a lot of jokes about retired people, but none of them work.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>36,"text"=>"I threw a boomerang a few years ago. I now live in constant fear.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>37,"text"=>"How do you organize a space party? You planet.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>38,"text"=>"Two fish swim into a concrete wall. One turns to the other and says, \"Dam.\"","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>39,"text"=>"What do you call a bear with no teeth? A gummy bear.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>40,"text"=>"Did you hear about the Italian chef who died? He pasta way.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>41,"text"=>"I've been diagnosed with color-blindness. It came out of the blue.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>42,"text"=>"I'm on a whiskey diet. I've lost three days already.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>43,"text"=>"What did the Buddhist say to the hot dog vendor? Make me one with everything.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>44,"text"=>"The problem with kleptomaniacs is that they always take things literally.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>45,"text"=>"What's orange and sounds like a parrot? A carrot.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>46,"text"=>"My wife is on a tropical fruit diet. It's enough to make a mango crazy.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>47,"text"=>"Did you hear about the mathematician who's afraid of negative numbers? He'll stop at nothing to avoid them.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>48,"text"=>"Why do seagulls fly over the sea? Because if they flew over the bay, they'd be bagels.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>49,"text"=>"What do you call a cow in an earthquake? A milkshake.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>50,"text"=>"I was wondering why the baseball kept getting bigger. Then it hit me.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>51,"text"=>"What do you call a fake noodle? An impasta.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>52,"text"=>"Why did the coffee file a police report? It got mugged.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>53,"text"=>"I'm writing a book about reverse psychology. Please don't buy it.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>54,"text"=>"I used to think I was indecisive, but now I'm not so sure.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>55,"text"=>"What do you call a man with a rubber toe? Roberto.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>56,"text"=>"My therapist says I have a preoccupation with vengeance. We'll see about that.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>57,"text"=>"I hate Russian dolls. They're so full of themselves.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>58,"text"=>"What do you call cheese that isn't yours? Nacho cheese.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>59,"text"=>"Why are elevator jokes so good? They work on so many levels.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>60,"text"=>"I'm not a fan of whiteboards. I find them quite re-markable.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>61,"text"=>"Parallel lines have so much in common. It's a shame they'll never meet.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>62,"text"=>"Why did the astronaut break up with his girlfriend? He needed space.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>63,"text"=>"I tried to sue the airline for losing my luggage. I lost my case.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>64,"text"=>"Why do cows have hooves instead of feet? Because they lactose.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>65,"text"=>"Atheism is a non-prophet organization.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>66,"text"=>"I hate how funerals are always at 9 a.m. I'm not a mourning person.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>67,"text"=>"Why don't skeletons fight each other? They don't have the guts.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>68,"text"=>"What do you call a magic dog? A Labracadabrador.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>69,"text"=>"I'm terrified of elevators. I'm going to take steps to avoid them.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>70,"text"=>"What do you call a dinosaur that crashes their car? Tyrannosaurus wrecks.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>71,"text"=>"I have a fear of speed bumps. I'm slowly getting over it.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>72,"text"=>"What did the janitor say when he jumped out of the closet? \"Supplies!\"","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>73,"text"=>"A photon checks into a hotel. The bellhop asks, \"Can I help with your luggage?\" The photon replies, \"No thanks, I'm traveling light.\"","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>74,"text"=>"What's the difference between a hippo and a Zippo? One is really heavy; the other is a little lighter.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>75,"text"=>"I have a joke about paper. It's tearable.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>76,"text"=>"What's a skeleton's least favorite room? The living room.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>77,"text"=>"Why did the scarecrow win an award? He was outstanding in his field.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>78,"text"=>"What do you call a woman who stands between two goalposts? Annette.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>79,"text"=>"I'm writing a book called \"Falling Down the Stairs.\" It's a step-by-step guide.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>80,"text"=>"What do you call a group of killer whales playing instruments? An orca-stra.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>81,"text"=>"I told my doctor I kept hearing music in my soup. He said it was probably the chicken noodle.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>82,"text"=>"My cross-eyed wife and I just got a divorce. We couldn't see eye to eye.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>83,"text"=>"I used to be a personal trainer. Then I gave my too-weak notice.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>84,"text"=>"I went to the zoo and saw a baguette in a cage. The zookeeper said it was bread in captivity.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>85,"text"=>"A skeleton walks into a bar and orders a beer and a mop.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>86,"text"=>"What do you call a cow that just had a baby? De-calf-inated.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>87,"text"=>"I can't believe I got a job at the sunscreen factory. I just applied.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>88,"text"=>"Did you hear about the fire at the circus? It was in tents.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>89,"text"=>"I asked my dog what two minus two is. He said nothing.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>90,"text"=>"The rotation of Earth really makes my day.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>91,"text"=>"How does a penguin build its house? Igloos it together.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>92,"text"=>"Why don't some fish play piano? Because you can't tuna fish.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>93,"text"=>"I tried to catch some fog earlier. I mist.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>94,"text"=>"Spring is here! I got so excited I wet my plants.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>95,"text"=>"A will is a dead giveaway.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>96,"text"=>"What do you call a can opener that doesn't work? A can't opener.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>97,"text"=>"What does a grape say when you step on it? Nothing, it just lets out a little wine.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>98,"text"=>"My wife told me I have to stop acting like a flamingo. I had to put my foot down.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>99,"text"=>"Why did the melon jump into the lake? It wanted to be a watermelon.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>100,"text"=>"What do you call two monkeys sharing an Amazon account? Prime mates.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>101,"text"=>"I have a joke about infinity, but I don't know where to start.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>102,"text"=>"I tried to write a joke about time travel, but you didn't like it.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>103,"text"=>"A bank robber walks into a library and says, \"Stick 'em up.\" The librarian says, \"Shh.\"","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>104,"text"=>"What did the ocean say to the beach? Nothing, it just waved.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>105,"text"=>"I bought a thesaurus but when I got it home, it was blank. I have no words for how angry I am.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>106,"text"=>"I told my wife she should embrace her mistakes. She gave me a hug.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>107,"text"=>"What do you call a fake stone in Ireland? A sham rock.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>108,"text"=>"I'm writing a novel about clocks. It's about time.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>109,"text"=>"I used to be a train driver but I got sidetracked.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>110,"text"=>"I broke my finger last week. On the other hand, I'm okay.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>111,"text"=>"I told my doctor I feel like a deck of cards. He told me to sit down and he'd deal with me later.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>112,"text"=>"What do you call a pile of cats? A meow-ntain.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>113,"text"=>"I used to be a door-to-door encyclopedia salesman. There's a lot of stories on that front.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>114,"text"=>"I just got a job as a mirror inspector. It's a job I can really see myself doing.","submitted_at"=>"2026-03-19 00:00:00"],
    ["id"=>115,"text"=>"What do you call a snowman with a six-pack? An abdominal snowman.","submitted_at"=>"2026-03-19 00:00:00"],
];

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

    if ($action === 'reset_queue') {
        $existing     = loadJson($punsFile);
        $approvedText = array_map(fn($p) => normText($p['text']), $existing);
        $filtered     = array_values(array_filter($SEED_QUEUE, fn($item) =>
            !in_array(normText($item['text']), $approvedText)
        ));
        $ok  = saveJson($queueFile, $filtered);
        $msg = $ok ? 'Queue reset (' . count($filtered) . ' puns, already-approved excluded).' : 'Save failed — check file permissions.';
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

// Filter queue: skip anything already in the approved pool, persist result to disk
// Normalize aggressively — strip all non-alphanumeric chars so apostrophe/encoding variants still match
function normText(string $s): string {
    return preg_replace('/[^a-z0-9]/i', '', strtolower($s));
}
$approvedNorm = array_map(fn($p) => normText($p['text']), $puns);
$filtered = array_values(array_filter($queue, fn($item) =>
    !in_array(normText($item['text']), $approvedNorm)
));
if (count($filtered) !== count($queue)) {
    saveJson($queueFile, $filtered);
}
$queue = $filtered;

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

<!-- Reset queue -->
<form method="post" action="moderate.php" style="margin-bottom:16px;">
  <input type="hidden" name="key" value="<?= htmlspecialchars(MOD_KEY) ?>">
  <input type="hidden" name="action" value="reset_queue">
  <button type="submit" class="btn" style="background:#888;font-size:0.8em;" onclick="return confirm('Replace the entire queue with the 100 seed puns? This cannot be undone.')">Reset queue to seed data</button>
</form>

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
