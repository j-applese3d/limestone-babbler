#!/bin/php
<?php

$playlist = $argv[1] ?? null;
if ($playlist === null) {
    die("Playlist should be defined: $argv[0] <playlist>\n");
}

$files = glob("$playlist/XC*");
$fIndexed = [];
foreach ($files as $file) {
    $name = explode(' - ', $file)[1];
    // sort by last word (i.e. put babblers together), then the rest of the name...
    $name = strtoupper(strrev($name));
    $fIndexed[$name][] = $file;
}

$seed = [];
for ($l = 'A', $i = 0; $i < 26; $l++, $i++) $seed[$l] = random_int(1, 1000);

uksort($fIndexed, fn($a, $b) => $seed[$a[0]] <=> $seed[$b[0]] ?: $a <=> $b);

echo "Your playlist is:\n\n";
foreach ($fIndexed as $files) foreach ($files as $f) echo " * $f\n";

foreach ($fIndexed as $name => $files) {
    foreach ($files as $file)
        playCall($file);
}
die("Played " . count($fIndexed) . " bird sp calls");

function playCall($file) {
    sleep(2);
    
    $name = explode(' - ', $file)[1];
    $nameClean = escapeshellarg($name);
    
    echo "\n\n~~ $name ~~\n\n";
    shell_exec("notify-send $nameClean");
    shell_exec("say $nameClean");
    
    sleep(1);
    
    $cleanFile = escapeshellarg($file);
    shell_exec("cvlc --play-and-exit $cleanFile");
}

