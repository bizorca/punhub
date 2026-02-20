<?php
declare(strict_types=1);

#### CONFIG

$title   = ''; # (Optional) title to show above the content. Leave blank to disable. HTML allowed. E.g. use <strong>TITLE</strong> for bold.
$file    = 'data.txt'; # The file that contains the data to display randomly
$pre     = ''; # (Optional) Output before all the random items
$post    = ''; # (Optional) Output after all the random items
$infix   = '&nbsp;'; # (Optional) Output between each random item. E.g. use <br /> for a new line.
$show    = 1; # Number of items to show from the data file. Set to 0 to display all rows.
$per_row = 3; # How many items per row (columns). Set to 0 to ignore.

# If you use HTML for $pre, $post, or $infix, escape any single quotes with a backslash.

#### ADVANCED CONFIGS

$companion = ''; # Optional companion data file. Must have the same number of lines as $file (auto-padded if short).
$infill    = 'use item number'; # Fill value when padding the companion file.
                                # Set to 'use item number' to pad with sequential line numbers.

$dpath = ''; # Path to datafiles relative to the calling page. Leave empty if same directory.

#### END CONFIGS

$file  = $dpath . $file;
$disp  = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$count = count($disp) - 1;
$show  = ($show < 1 || $show - 1 >= $count) ? $count : $show - 1;
$rand  = range(0, $count);
shuffle($rand);

$codata = [];
if ($companion !== '') {
    $companionPath = $dpath . $companion;
    $codata        = file($companionPath, FILE_IGNORE_NEW_LINES);
    $ct            = count($codata) - 1;

    if ($ct < $count) {
        $dif = $count - $ct;
        for ($d = 1; $d <= $dif + 1; $d++) {
            $line = ($infill === 'use item number') ? (string)($ct + $d) : $infill;
            file_put_contents($companionPath, $line . "\n", FILE_APPEND);
        }
        $codata = file($companionPath, FILE_IGNORE_NEW_LINES);
    }
}

echo $title;
echo $pre;

for ($i = 0; $i <= $show; $i++) {
    $key    = $rand[$i];
    $suffix = $per_row !== 0 ? $infix : '';
    echo $infix . $disp[$key] . $suffix;
    if ($companion !== '') {
        echo $infix . ($codata[$key] ?? '') . $suffix;
    }
}

echo $post;
?>
