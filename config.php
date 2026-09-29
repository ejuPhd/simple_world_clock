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
        'settings' => [
            'timeFormat' => '24',
        ],
        'timezones' => [
            ['name' => 'Jeddah',  'timezone' => 'Asia/Riyadh'],
            ['name' => 'Denver',  'timezone' => 'America/Denver'],
            ['name' => 'Seattle', 'timezone' => 'America/Los_Angeles'],
            ['name' => 'Doha',    'timezone' => 'Asia/Qatar'],
        ],
        'alarms' => [
            ['id' => 1, 'name' => 'Wake up',      'time' => '07:00', 'timezone' => 'Asia/Riyadh',         'enabled' => true],
            ['id' => 2, 'name' => 'Standup call', 'time' => '09:30', 'timezone' => 'Asia/Qatar',          'enabled' => true],
            ['id' => 3, 'name' => 'Lunch',        'time' => '12:00', 'timezone' => 'Asia/Riyadh',         'enabled' => true],
            ['id' => 4, 'name' => 'Call Apple',   'time' => '18:00', 'timezone' => 'America/Los_Angeles', 'enabled' => true],
            ['id' => 5, 'name' => 'Call Riz',     'time' => '19:00', 'timezone' => 'Asia/Riyadh',         'enabled' => true],
            ['id' => 6, 'name' => 'Dinner',       'time' => '21:00', 'timezone' => 'Asia/Qatar',          'enabled' => false],
        ],
    ];
}

/**
 * Load data from disk. If the file is missing or unreadable,
 * seed it with defaults and write it out.
 */
function loadData(): array
{
    if (!file_exists(DATA_FILE)) {
        $defaults = defaultData();
        saveData($defaults);
        return $defaults;
    }

    $json = file_get_contents(DATA_FILE);
    $data = json_decode($json, true);

    if (!is_array($data)) {
        $defaults = defaultData();
        saveData($defaults);
        return $defaults;
    }

    // Backfill: older data files may lack 'settings'.
    if (!isset($data['settings'])) {
        $data['settings'] = ['timeFormat' => '24'];
        saveData($data);
    }

    return $data;
}

/**
 * Save data to disk as pretty-printed JSON.
 */
function saveData(array $data): void
{
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    file_put_contents(DATA_FILE, $json);
}

/**
 * Find the next available alarm id (max + 1).
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