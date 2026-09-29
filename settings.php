<?php
declare(strict_types=1);

// settings.php
// Lets the user edit everything: reorder clocks, rename or REPLACE
// them with different timezones, and add/edit/delete alarms.

require_once 'config.php';
require_once 'helpers.php';

// Load current state.
$data      = loadData();
$timezones = $data['timezones'];
$alarms    = $data['alarms'];

// A place to collect messages to show the user.
$notice = '';

// ----------------------------------------------------------------
// HANDLE FORM SUBMISSIONS
// ----------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Which form was submitted?
    $action = $_POST['action'] ?? '';

    // ------------------------------------------------------------
    // MOVE A TIMEZONE UP OR DOWN
    // ------------------------------------------------------------
    if ($action === 'move_timezone') {
        $index  = (int) ($_POST['index'] ?? -1);
        $dir    = $_POST['direction'] ?? '';
        $target = $dir === 'up' ? $index - 1 : $index + 1;

        if ($index >= 0 && $target >= 0 && $target < count($timezones)) {
            $tmp                = $timezones[$index];
            $timezones[$index]  = $timezones[$target];
            $timezones[$target] = $tmp;
            $notice = 'Clock order updated.';
        }
    }

    // ------------------------------------------------------------
    // EDIT A TIMEZONE (rename or change identifier)
    // ------------------------------------------------------------
    if ($action === 'edit_timezone') {
        $index = (int) ($_POST['index'] ?? -1);
        $name  = trim($_POST['name']     ?? '');
        $tzId  = trim($_POST['timezone'] ?? '');

        if ($index >= 0 && $index < count($timezones) && $name !== '' && $tzId !== '') {
            try {
                new DateTimeZone($tzId);
                $timezones[$index]['name']     = $name;
                $timezones[$index]['timezone'] = $tzId;
                $notice = 'Clock updated.';
            } catch (Exception $ex) {
                $notice = 'Invalid timezone: ' . $tzId;
            }
        }
    }

    // ------------------------------------------------------------
    // REPLACE A TIMEZONE
    // ------------------------------------------------------------
    // This is the NEW action. It takes an existing slot (by index)
    // and swaps in a completely different timezone. The slot keeps
    // its position in the order.
    //
    // We accept:
    //   - index     : which slot to replace
    //   - timezone  : the new identifier (required, must be valid)
    //   - name      : optional label. If blank, we auto-derive
    //                 the city name from the identifier.
    // ------------------------------------------------------------
    if ($action === 'replace_timezone') {
        $index = (int) ($_POST['index'] ?? -1);
        $tzId  = trim($_POST['replace_timezone'] ?? '');
        $name  = trim($_POST['replace_name'] ?? '');

        // Basic presence checks.
        if ($index < 0 || $index >= count($timezones)) {
            $notice = 'Invalid clock slot.';
        } elseif ($tzId === '') {
            $notice = 'Please pick a timezone to replace it with.';
        } else {
            try {
                // Throws if the identifier isn't a real timezone.
                new DateTimeZone($tzId);

                // If the user didn't type a name, derive one from
                // the identifier — 'Europe/Paris' becomes 'Paris'.
                if ($name === '') {
                    $name = cityFromTimezone($tzId);
                }

                // Overwrite the slot's name and timezone, but keep
                // its position in the array untouched.
                $timezones[$index]['name']     = $name;
                $timezones[$index]['timezone'] = $tzId;

                $notice = 'Replaced slot ' . ($index + 1) . ' with ' . $name . '.';
            } catch (Exception $ex) {
                $notice = 'Invalid timezone: ' . $tzId;
            }
        }
    }

    // ------------------------------------------------------------
    // ADD A NEW ALARM
    // ------------------------------------------------------------
    if ($action === 'add_alarm') {
        $name = trim($_POST['name']     ?? '');
        $time = trim($_POST['time']     ?? '');
        $tz   = trim($_POST['timezone'] ?? '');

        if ($name === '' || !isValidTime($time) || $tz === '') {
            $notice = 'Please fill name, a valid HH:MM time, and a timezone.';
        } else {
            $alarms[] = [
                'id'       => nextAlarmId($alarms),
                'name'     => $name,
                'time'     => $time,
                'timezone' => $tz,
                'enabled'  => true,
            ];
            $notice = 'Alarm added.';
        }
    }

    // ------------------------------------------------------------
    // UPDATE AN EXISTING ALARM
    // ------------------------------------------------------------
    if ($action === 'update_alarm') {
        $id   = (int) ($_POST['id'] ?? 0);
        $name = trim($_POST['name']     ?? '');
        $time = trim($_POST['time']     ?? '');
        $tz   = trim($_POST['timezone'] ?? '');

        if ($name === '' || !isValidTime($time) || $tz === '') {
            $notice = 'Please fill name, a valid HH:MM time, and a timezone.';
        } else {
            foreach ($alarms as $i => $a) {
                if ((int) $a['id'] === $id) {
                    $alarms[$i]['name']     = $name;
                    $alarms[$i]['time']     = $time;
                    $alarms[$i]['timezone'] = $tz;
                    break;
                }
            }
            $notice = 'Alarm updated.';
        }
    }

    // ------------------------------------------------------------
    // TOGGLE AN ALARM ON / OFF
    // ------------------------------------------------------------
    if ($action === 'toggle_alarm') {
        $id = (int) ($_POST['id'] ?? 0);
        foreach ($alarms as $i => $a) {
            if ((int) $a['id'] === $id) {
                $alarms[$i]['enabled'] = !$a['enabled'];
                break;
            }
        }
        $notice = 'Alarm toggled.';
    }

    // ------------------------------------------------------------
    // DELETE AN ALARM
    // ------------------------------------------------------------
    if ($action === 'delete_alarm') {
        $id = (int) ($_POST['id'] ?? 0);

        $alarms = array_values(array_filter(
            $alarms,
            fn($a) => (int) $a['id'] !== $id
        ));
        $notice = 'Alarm deleted.';
    }

    // ------------------------------------------------------------
    // PERSIST
    // ------------------------------------------------------------
    saveData(['settings' => $data['settings'], 'timezones' => $timezones, 'alarms' => $alarms]);

    // Reload so the form reflects the change immediately.
    $data      = loadData();
    $timezones = $data['timezones'];
    $alarms    = $data['alarms'];
}

// Fetch the full list of valid timezones ONCE for the replace dropdowns.
// We only need this on this page, so we grab it here.
$tzList = allTimezones();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings — World Clock</title>
    <link rel="stylesheet" href="style.css?v=3">
</head>
<body class="mood-day">

    <header class="site-header">
        <div class="header-main">
            <h1>⚙ Settings</h1>
            <?php if ($notice !== ''): ?>
                <p class="notice"><?= e($notice) ?></p>
            <?php endif; ?>
        </div>

        <div class="header-action">
            <a class="btn btn-icon" href="index.php" title="Back to clock" aria-label="Back">←</a>
        </div>
    </header>

    <!-- ======================================================
         CLOCKS — reorder, edit, replace
         ====================================================== -->
    <section class="settings-section">
        <h2>Clocks</h2>
        <p class="section-hint">
            The first clock is the main clock — its time of day drives the page theme.
            Use ↑ ↓ to reorder. Use <strong>Edit</strong> to rename in place.
            Use <strong>Replace</strong> to swap in a different city entirely.
        </p>

        <?php foreach ($timezones as $i => $tz): ?>
            <div class="tz-editor">

                <!-- Row 1: current values, order controls, edit form -->
                <div class="tz-row-main">
                    <span class="tz-position"><?= $i + 1 ?></span>

                    <div class="tz-current">
                        <strong><?= e($tz['name']) ?></strong>
                        <code><?= e($tz['timezone']) ?></code>
                    </div>

                    <!-- Move up / down -->
                    <form method="post" class="inline">
                        <input type="hidden" name="action"    value="move_timezone">
                        <input type="hidden" name="index"     value="<?= $i ?>">
                        <input type="hidden" name="direction" value="up">
                        <button <?= $i === 0 ? 'disabled' : '' ?> title="Move up">↑</button>
                    </form>
                    <form method="post" class="inline">
                        <input type="hidden" name="action"    value="move_timezone">
                        <input type="hidden" name="index"     value="<?= $i ?>">
                        <input type="hidden" name="direction" value="down">
                        <button <?= $i === count($timezones) - 1 ? 'disabled' : '' ?> title="Move down">↓</button>
                    </form>
                </div>

                <!-- Row 2: Edit (rename this slot) -->
                <form method="post" class="tz-row-form">
                    <input type="hidden" name="action" value="edit_timezone">
                    <input type="hidden" name="index"  value="<?= $i ?>">

                    <label>Rename:</label>
                    <input type="text" name="name" value="<?= e($tz['name']) ?>" placeholder="Name">
                    <input type="text" name="timezone" value="<?= e($tz['timezone']) ?>" placeholder="Area/City">
                    <button>Save</button>
                </form>

                <!-- Row 3: Replace (swap slot for a different timezone) -->
                <form method="post" class="tz-row-form">
                    <input type="hidden" name="action" value="replace_timezone">
                    <input type="hidden" name="index"  value="<?= $i ?>">

                    <label>Replace with:</label>
                    <!-- The dropdown lists every valid PHP timezone.
                         We group by region for readability. -->
                    <select name="replace_timezone" required>
                        <option value="">— pick a timezone —</option>
                        <?php foreach ($tzList as $option): ?>
                            <option value="<?= e($option) ?>"
                                <?= $option === $tz['timezone'] ? 'disabled' : '' ?>>
                                <?= e($option) ?>
                                <?= $option === $tz['timezone'] ? ' (current)' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label>Label (optional):</label>
                    <input type="text" name="replace_name" placeholder="Leave blank to auto-name">
                    <button>Replace</button>
                </form>
            </div>
        <?php endforeach; ?>
    </section>

    <!-- ======================================================
         ADD ALARM
         ====================================================== -->
    <section class="settings-section">
        <h2>Add Alarm</h2>
        <form method="post" class="row-form">
            <input type="hidden" name="action" value="add_alarm">
            <input type="text" name="name" placeholder="Alarm name" required>
            <input type="time" name="time" required>
            <select name="timezone" required>
                <?php foreach ($timezones as $tz): ?>
                    <option value="<?= e($tz['timezone']) ?>">
                        <?= e($tz['name']) ?> (<?= e($tz['timezone']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <button>+ Add alarm</button>
        </form>
    </section>

    <!-- ======================================================
         EDIT / TOGGLE / DELETE ALARMS
         ====================================================== -->
    <section class="settings-section">
        <h2>Alarms</h2>

        <?php if (empty($alarms)): ?>
            <p>No alarms yet.</p>
        <?php else: ?>
            <table class="settings-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Time</th>
                        <th>Timezone</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($alarms as $alarm): ?>
                    <tr class="<?= $alarm['enabled'] ? '' : 'disabled-row' ?>">
                        <td>
                            <form method="post" id="upd-<?= (int) $alarm['id'] ?>" class="row-form">
                                <input type="hidden" name="action" value="update_alarm">
                                <input type="hidden" name="id" value="<?= (int) $alarm['id'] ?>">
                                <input type="text" name="name" value="<?= e($alarm['name']) ?>" required>
                        </td>
                        <td>
                                <input type="time" name="time" value="<?= e($alarm['time']) ?>" required>
                        </td>
                        <td>
                                <select name="timezone" required>
                                    <?php foreach ($timezones as $tz): ?>
                                        <option value="<?= e($tz['timezone']) ?>"
                                            <?= $tz['timezone'] === $alarm['timezone'] ? 'selected' : '' ?>>
                                            <?= e($tz['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                        </td>
                        <td><?= $alarm['enabled'] ? 'ON' : 'off' ?></td>
                        <td class="actions">
                                <button form="upd-<?= (int) $alarm['id'] ?>">Save</button>
                            </form>
                            <form method="post" class="inline">
                                <input type="hidden" name="action" value="toggle_alarm">
                                <input type="hidden" name="id" value="<?= (int) $alarm['id'] ?>">
                                <button><?= $alarm['enabled'] ? 'Disable' : 'Enable' ?></button>
                            </form>
                            <form method="post" class="inline"
                                  onsubmit="return confirm('Delete this alarm?');">
                                <input type="hidden" name="action" value="delete_alarm">
                                <input type="hidden" name="id" value="<?= (int) $alarm['id'] ?>">
                                <button class="danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>

    <footer class="site-footer">
        <p>Another app by Dr. Earnest Ujaama for appsbyeu</p>
    </footer>

</body>
</html>