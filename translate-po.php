<?php
/*
Efficiently translates an entire .po file at once using OpenAI API.
Usage: php translate-po.php en es API-KEY-HERE

For short options page strings (like UI labels, button text, settings names), GPT-4o-mini is good enough. It’s faster and cheaper, and short phrases don’t require deep context handling.

However, if some strings have subtle meanings (e.g., “Post” could mean a blog post or mailing a letter), GPT-4o would be more accurate.
If cost isn’t a concern, GPT-4o ensures better precision. Otherwise, GPT-4o-mini should work fine for basic UI translations. 🚀
*/

if ($argc < 4) {
    die("Usage: php translate-po.php en es API-KEY-HERE\n");
}

$source_lang = $argv[1];
$target_lang = $argv[2];
$api_key = $argv[3];

$source_po = "{$source_lang}.po";
$target_po = "{$target_lang}.po";

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

translate_po($source_po, $target_po, $target_lang, $api_key);
