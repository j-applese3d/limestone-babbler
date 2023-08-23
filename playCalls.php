#!/bin/php
<?php

$playlist = $argv[1] ?? null;
if ($playlist === null) {
    die("Playlist should be defined: $argv[0] <playlist>\n");
}

$quizMode = in_array('--quiz', $argv);

array_shift($argv); // remove myself [script] from argument list

$files = array_filter($argv, fn($arg) => !str_starts_with($arg, '--') && is_file($arg));

# glob("$playlist/XC*");
$fIndexed = [];
foreach ($files as $file) {
    $name = explode(' - ', $file)[1];
    $lastWord = explode(' ', $name);
    $lastWord = array_pop($lastWord);

    if ($quizMode) unset($ordering[$lastWord]); // don't put things together...
    $ordering[$lastWord] ??= random_int(0,1000);

    // sort by last word (i.e. put babblers together), then the rest of the name...
    $fIndexed[$ordering[$lastWord] . $name][] = $file;
}

ksort($fIndexed);
#uksort($fIndexed, fn($a, $b) => $seed[$a[0]] <=> $seed[$b[0]] ?: $a <=> $b);

echo "Your playlist is:\n\n";
foreach ($fIndexed as $files) foreach ($files as $f) echo " * $f\n";

foreach ($fIndexed as $name => $files) {
    foreach ($files as $file)
        playCall($file, $quizMode);
}
die("Played " . count($fIndexed) . " bird sp calls");

function firstLetters($name) {
    $pcs = preg_split('/[ -]/', $name);
    $letters = [];
    foreach ($pcs as $pc) $letters[] = $pc[0] ?? '';
    return implode('', $letters);
}
function lastWord($name) {
    $lastWord = explode(' ', $name);
    $lastWord = array_pop($lastWord);
    return $lastWord;
}

function playCall($file, $quizMode) {
    sleep(2);
    
    $name = explode(' - ', $file)[1];
    $nameClean = escapeshellarg($name);
    $cleanFile = escapeshellarg($file);

    $exifComment = shell_exec("exiftool -comment $cleanFile");
    $commentPieces = explode(' // ', $exifComment);
    $alsoSpecies = array_filter($commentPieces, fn($c) => str_starts_with($c, 'also:'));


    $audioDescPretty = "~~ " . ($quizMode ? '[quiz mode]' : $name) . " ~~\n" . implode("\n", $alsoSpecies);
    echo "\n\n$audioDescPretty\n\n";
    shell_exec("notify-send " . escapeshellarg($audioDescPretty));
    if (!$quizMode) {
        shell_exec("say $nameClean");
        sleep(1);
    }
    
    #shell_exec("cvlc --global-key-next n --play-and-exit $cleanFile");
    runCommandWithUserInputAllowed("cvlc --global-key-stop 'Ctrl+M' --play-and-exit $cleanFile");

    if ($quizMode) {
        $guessed = false;
        $hints = [
            "The family/last word is " . lastWord($name),
            "Letters are: " . firstLetters($name),
        ];

        while (!$guessed) {
            echo "\n";
            $guess = readline("What bird was that? ");
            readline_add_history($guess);
            if ($guess === false) continue;
            if ($guess === 'help') {
                echo "\nEnter your guess, or 'hint' (h), or 'give up' (gu)";
                continue;
            }
            if ($guess === 'hint' || $guess === 'h') {
                echo "\n" . (array_shift($hints) ?: "Out of hints...") . "\n";
                continue;
            }
            if ($guess === 'gu' || $guess === 'give up') {
                echo "\nGave up?! :( That was a $name\n";
                break;
            }
            if (levenshtein($guess, $name) < 5) {
                echo "\nYep! It's a         $name\n";
                break;
            }
            echo "\nNope... Try again!\n";
        }
    }
}

function runCommandWithUserInputAllowed($cmd) 
{
    $descriptors = [
        0 => ["pipe", "r"], // Process input
        1 => ["pipe", "w"], // Process output
        2 => ["pipe", "w"]  // Process errors
    ];

    $process = proc_open($cmd, $descriptors, $pipes);
    
    if (is_resource($process)) {
        stream_set_blocking($pipes[1], 0); // Set output stream to non-blocking
    
        while (true) {
            // Check if the process is still running
            $status = proc_get_status($process);
            if (!$status['running']) {
                //echo "not running anymore";
                break;
            }
            #echo "r";
    
            $read = [$pipes[1], STDIN]; // Add output stream to the array for monitoring
    
            // Use stream_select to check for readable streams without blocking
            $write = null;
            $except = null;
            $timeout = 0; // Immediately return
            $numReadyStreams = stream_select($read, $write, $except, $timeout);
    
            if ($numReadyStreams === false) {
                // Error handling
                break;
            } 

            if ($numReadyStreams > 0) {    
                if (in_array(STDIN, $read)) {
                    // read user input
                    // Capture user input from stdin (if available)
                    $userInput = fgets(STDIN);
                    if ($userInput !== false) {
                        // Process user input (e.g., send a command to the process)
                        // You can use fwrite to send the input to the process
                        fwrite($pipes[0], $userInput);
                    }
                }

                if (in_array($pipes[1], $read)) {
                    // Process output from the command
                    $output = fgets($pipes[1]);
    
                    // Process the captured output (you can echo or do whatever you need)
                    echo $output;
                }
            }
        }
    
        // Close the process and streams
        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);
    }
}


