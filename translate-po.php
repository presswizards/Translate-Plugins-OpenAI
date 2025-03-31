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

function translate_bulk($texts, $target_lang, $api_key) {
    if (empty($texts)) return [];

    $messages = [
        ['role' => 'system', 'content' => "Translate the following texts to {$target_lang}, keeping them in the same order and preserving context for software localization. Return the response as a JSON array."],
        ['role' => 'user', 'content' => json_encode($texts)]
    ];

    $data = [
        'model' => 'gpt-4o-mini',
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

    $decoded = json_decode($response, true);

    // Ensure the response is properly decoded
    $translated_texts = json_decode($decoded['choices'][0]['message']['content'] ?? '[]', true);

    if (!is_array($translated_texts) || count($translated_texts) !== count($texts)) {
        echo "Translation response invalid. Response received:\n" . $decoded['choices'][0]['message']['content'] . "\n";
        return array_fill(0, count($texts), '');
    }

    return $translated_texts;
}

function translate_po($source_po, $target_po, $target_lang, $api_key) {
        if (!file_exists($source_po)) {
                die("Source PO file {$source_po} not found.\n");
        }

        $lines = file($source_po);
        $msgids = [];
        $msgid_map = [];
        $processed_lines = [];

        // Collect all msgid entries for batch translation
        foreach ($lines as $line) {
                if (strpos($line, 'msgid "') === 0) {
                        $msgid = trim(substr($line, 7), "\"\n");
                        $msgids[] = $msgid;
                        $msgid_map[$msgid] = ''; // Initialize map
                }
        }

        // Get translations in bulk
        $translations = translate_bulk($msgids, $target_lang, $api_key);

        // Debug output for mismatch issue
        if (count($translations) !== count($msgids)) {
                echo "Warning: Mismatch between msgid count (".count($msgids).") and translations (".count($translations).").\n";
        }

        // Map translations back to the msgids
        $i = 0;
        foreach ($msgid_map as $key => &$value) {
                $value = trim($translations[$i++] ?? '');

                // If the translation is empty, you can skip or set a placeholder
                if (empty($value)) {
                        $value = "[TRANSLATION MISSING FOR {$key}]";  // Optional placeholder for missing translation
                }
        }

        // Reconstruct PO file with translated msgstr values
        $msgid = '';
        foreach ($lines as $line) {
                if (strpos($line, 'msgid "') === 0) {
                        $msgid = trim(substr($line, 7), "\"\n");
                        $processed_lines[] = $line;
                } elseif (strpos($line, 'msgstr ""') === 0 && $msgid !== '') {
                        $translated_value = isset($msgid_map[$msgid]) ? $msgid_map[$msgid] : '';
                        $processed_lines[] = 'msgstr "' . addslashes($translated_value) . "\"\n\n";
                        $msgid = ''; // Reset msgid after translation
                } else {
                        $processed_lines[] = $line;
                }
        }

        file_put_contents($target_po, implode('', $processed_lines));
        echo "Translated PO file saved as $target_po\n";
}

translate_po($source_po, $target_po, $target_lang, $api_key);
