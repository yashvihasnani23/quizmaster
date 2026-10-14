<?php
// Validates one question from form data.
// Returns a clean array, or null if anything is missing or invalid.
function clean_question($q) {
    if (!is_array($q)) { return null; }

    $get = function ($key) use ($q) {
        return (isset($q[$key]) && is_string($q[$key])) ? trim($q[$key]) : '';
    };

    $text = $get('text');
    if ($text === '' || mb_strlen($text) > 1000) { return null; }

    $clean = ['text' => $text];
    foreach (['a', 'b', 'c', 'd'] as $opt) {
        $val = $get($opt);
        if ($val === '' || mb_strlen($val) > 255) { return null; }
        $clean[$opt] = $val;
    }

    $correct = $get('correct');
    if (!in_array($correct, ['a', 'b', 'c', 'd'], true)) { return null; }
    $clean['correct'] = $correct;

    return $clean;
}