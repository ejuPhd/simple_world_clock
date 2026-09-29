<?php
declare(strict_types=1);

// config.php
// Loads and saves the app's data (timezones + alarms) from a JSON file.
// If the file doesn't exist yet, it seeds it with defaults.

// Path to the JSON store. __DIR__ is the directory of THIS file,
// so the path is always correct no matter where the script runs from.
const DATA_FILE = __DIR__ . '/data/clock.json';

/**
 * Return the default data used the first time the app runs.
 * Jeddah is first so it becomes the "main" clock that drives
 * the page mood.
 */
function defaultData(): array
{
    return [
        // NEW: app-wide settings live here.
        'settings' => [
            // '24' or '12' — controls how clocks are formatted.
            'timeFormat' => '24',
        ],
        'timezones' => [
            ['name' => 'Jeddah',  'timezone' => 'Asia/Riyadh'],
            ['name' => 'Denver',  'timezone' => 'America/Denver'],
            ['name' => 'Seattle', 'timezone' => 'America/Los_Angeles'],
            ['name' => 'Doha',    'timezone' => 'Asia/Qatar'],
        ],
        'alarms' => [
            // ... unchanged ...
        ],
    ];
}

/**
 * Load data from disk. If the file is missing or unreadable,
 * seed it with defaults and write it out.
 */
function loadData(): array
{
    // file_exists returns true if the file is on disk.
    if (!file_exists(DATA_FILE)) {
        $defaults = defaultData();
        saveData($defaults);          // create the file
        return $defaults;
    }

    // file_get_contents reads the entire file into a string.
    $json = file_get_contents(DATA_FILE);

    // json_decode turns JSON text back into a PHP array.
    // The `true` argument means "return an associative array"
    // instead of stdClass objects.
    $data = json_decode($json, true);

    // If decoding failed (bad JSON), fall back to defaults.
    if (!is_array($data)) {
        $defaults = defaultData();
        saveData($defaults);
        return $defaults;
    }

    return $data;
}

/**
 * Save data to disk as pretty-printed JSON.
 * JSON_PRETTY_PRINT makes the file human-readable, which is
 * great while learning — you can open it and see what changed.
 */
function saveData(array $data): void
{
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    file_put_contents(DATA_FILE, $json);
}

/**
 * Find the next available alarm id (max + 1).
 * We need this when the user adds a new alarm.
 */
function nextAlarmId(array $alarms): int
{
    $max = 0;
    foreach ($alarms as $a) {
        if (isset($a['id']) && $a['id'] > $max) {
            $max = $a['id'];
        }
    }
    return $max + 1;
}