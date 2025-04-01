<?php
/*
Efficiently translates an entire .po file at once using OpenAI API.
Usage: php translate-po.php API-KEY-HERE

The source language is always "en". The destination languages are defined in the script as an array of country codes (e.g., "es", "fr", "de", "it").
*/

if ($argc < 2) {
    die("Usage: php translate-po.php API-KEY-HERE\n");
}

$api_key = $argv[1];
$source_lang = "en";
$target_languages = ["es", "fr", "de", "it"];
//$target_languages = ["es", "fr", "de", "it", "pt", "nl", "ru", "zh_CN", "ja", "ko", "ar", "tr", "hi", "pl", "sv", "da", "fi", "el", "cs", "hu", "th", "he"];

$source_po = "{$source_lang}.po";

function translate_po($source_po, $target_po, $target_lang, $api_key) {
    if (!file_exists($source_po)) {
        die("Source PO file {$source_po} not found.\n");
    }

    // Read the entire source .po file
    $source_content = file_get_contents($source_po);

    // Prepare the API request
    $messages = [
        ['role' => 'system', 'content' => "Translate the following gettext .po file content to {$target_lang}. Ensure the output is in valid .po file format, preserving all metadata, msgid, and msgstr structures. Multi-line msgid and msgstr blocks must be formatted correctly, including max length formatting. Do not add po or code formatting or extra blank lines at the top or bottom of the file, it will be saved just as sent back."],
        ['role' => 'user', 'content' => $source_content]
    ];

    $data = [
        'model' => 'gpt-4o',
        'messages' => $messages,
        'temperature' => 0.3
    ];

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key,
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $response = curl_exec($ch);
    curl_close($ch);

    if (!$response) {
        die("Error: No response from OpenAI API.\n");
    }

    $decoded = json_decode($response, true);

    if (!isset($decoded['choices'][0]['message']['content'])) {
        die("Error: Unexpected API response format. Full response:\n$response\n");
    }

    // Clean up the translated content to remove extra blank lines
    $translated_content = trim($decoded['choices'][0]['message']['content']);

    // Write the cleaned translated content to the target .po file
    file_put_contents($target_po, $translated_content);

    echo "Translated PO file saved as $target_po\n";
}

// Loop through each target language and translate
foreach ($target_languages as $target_lang) {
    $target_po = "{$target_lang}.po";
    translate_po($source_po, $target_po, $target_lang, $api_key);
}
