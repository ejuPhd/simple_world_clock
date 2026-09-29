<?php
declare(strict_types=1);

// index.php
// Main display page: reads data from the JSON store and renders
// the live clock + alarm list.

require_once 'config.php';
require_once 'helpers.php';

// Load persisted data.
$data      = loadData();
$timezones = $data['timezones'];
$alarms    = $data['alarms'];
$timeFormat = $data['settings']['timeFormat'] ?? '24';

// ----------------------------------------------------------------
// HANDLE THE 12/24 TOGGLE
// ----------------------------------------------------------------
// The toggle is a tiny form that POSTs back to index.php.
// We flip the setting and save it.
// ----------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_format') {

    // Read current value, flip it, and write it back.
    $current = $data['settings']['timeFormat'] ?? '24';
    $data['settings']['timeFormat'] = $current === '24' ? '12' : '24';

    // Save to disk.
    saveData($data);
}

// The MAIN clock is the FIRST timezone — currently Jeddah.
// Its mood drives the whole page's theme.
$primaryTz   = $timezones[0]['timezone'];
$primaryHour = hourInZone($primaryTz);
$mood        = moodForHour($primaryHour);

// Any alarms ringing right now?
$ringingAlarms = findRingingAlarms($alarms);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>World Clock — Jeddah Main</title>
    <link rel="stylesheet" href="style.css?v=2">
</head>
<body class="mood-<?= e($mood) ?>">

    <header class="site-header">

        <!-- Centered title block -->
        <div class="header-main">
            <h1>🕰 World Clock</h1>
            <p class="subtitle">
                Main: <strong><?= e($timezones[0]['name']) ?></strong>
                — mood: <strong><?= e($mood) ?></strong>
            </p>
        </div>

        <!-- Settings link pinned to the far right -->
        <div class="header-action">
            <!-- Toggle 12/24. The label shows what you'll switch TO. -->
            <form method="post" class="inline">
                <input type="hidden" name="action" value="toggle_format">
                <button type="submit" class="btn btn-icon"
                        title="Switch to <?= $timeFormat === '24' ? '12' : '24' ?>-hour time"
                        aria-label="Toggle 12/24-hour time">
                    <?= $timeFormat === '24' ? '24h' : '12h' ?>
                </button>
            </form>

            <a class="btn btn-icon" href="settings.php" title="Settings" aria-label="Settings">⚙</a>
        </div>

    </header>

    <?php if (!empty($ringingAlarms)): ?>
        <div class="alarm-banner">
            <h2>🔔 Alarm<?= count($ringingAlarms) > 1 ? 's' : '' ?> ringing!</h2>
            <ul>
                <?php foreach ($ringingAlarms as $alarm): ?>
                    <li><?= e($alarm['name']) ?> (<?= e($alarm['time']) ?> <?= e($alarm['timezone']) ?>)</li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <section class="timezones">
        <?php foreach ($timezones as $i => $tz): ?>
            <?php
                $tzHour = hourInZone($tz['timezone']);
                $tzMood = moodForHour($tzHour);
                $clockDate = new DateTime('now', new DateTimeZone($tz['timezone']));
                $tzTime = $clockDate->format($timeFormat === '24' ? 'H:i:s' : 'g:i:s A');
            ?>
            <div class="tz-card mood-<?= e($tzMood) ?><?= $i === 0 ? ' main' : '' ?>"
                 data-timezone="<?= e($tz['timezone']) ?>">
                <h2>
                    <?= e($tz['name']) ?>
                    <?php if ($i === 0): ?><span class="badge">MAIN</span><?php endif; ?>
                </h2>
                <div class="clock" data-clock><?= e($tzTime) ?></div>
                <div class="tz-id"><?= e($tz['timezone']) ?></div>
                <div class="tz-mood"><?= e($tzMood) ?></div>
            </div>
        <?php endforeach; ?>
    </section>

    <section class="alarms">
        <h2>Alarms</h2>
        <ul class="alarm-list">
            <?php foreach ($alarms as $alarm): ?>
                <li class="alarm <?= $alarm['enabled'] ? 'enabled' : 'disabled' ?>">
                    <span class="alarm-name"><?= e($alarm['name']) ?></span>
                    <span class="alarm-time"><?= e($alarm['time']) ?></span>
                    <span class="alarm-tz"><?= e($alarm['timezone']) ?></span>
                    <span class="alarm-status"><?= $alarm['enabled'] ? 'ON' : 'off' ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>

    <!-- The same live-tick JS as before. -->
    <script>
    const clockElements = document.querySelectorAll('[data-clock]');
    function timeInZone(timezone) {
    // Ask the user's format preference: '12' or '24'.
        const use12 = TIME_FORMAT === '12';

        return new Intl.DateTimeFormat('en-US', {
            timeZone: timezone,
            hour:   use12 ? 'numeric' : '2-digit',  // '2' vs '02'
            minute: '2-digit',
            second: '2-digit',
            hour12: use12,                           // flip per preference
        }).format(new Date());
    }
    function tick() {
        clockElements.forEach(function (el) {
            const now = timeInZone(el.getAttribute('data-timezone'));
            if (el.textContent !== now) el.textContent = now;
        });
    }
    tick();
    setInterval(tick, 1000);
    </script>

    <!-- Required footer -->
    <footer class="site-footer">
        <p>Another app by Dr. Earnest Ujaama for appsbyeu</p>
    </footer>

</body>
</html>