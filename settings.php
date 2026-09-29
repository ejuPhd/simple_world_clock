<?php
declare(strict_types=1);

// settings.php
// Lets the user edit everything: reorder clocks, rename them,
// add/edit/delete alarms. All changes are saved to the JSON file.

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
// $_SERVER['REQUEST_METHOD'] tells us GET vs POST.
// We only act when the form is submitted via POST.
// ----------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Read the "action" field so we know which form was submitted.
    // ?? '' protects against a missing key.
    $action = $_POST['action'] ?? '';

    // ------------------------------------------------------------
    // MOVE A TIMEZONE UP OR DOWN (reordering)
    // ------------------------------------------------------------
    if ($action === 'move_timezone') {
        $index  = (int) ($_POST['index'] ?? -1);
        $dir    = $_POST['direction'] ?? '';
        $target = $dir === 'up' ? $index - 1 : $index + 1;

        // Only swap if both indices are within bounds.
        if ($index >= 0 && $target >= 0 && $target < count($timezones)) {
            // Swap via a temp variable.
            $tmp                  = $timezones[$index];
            $timezones[$index]    = $timezones[$target];
            $timezones[$target]   = $tmp;
            $notice = 'Clock order updated.';
        }
    }

    // ------------------------------------------------------------
    // EDIT A TIMEZONE (rename or change its identifier)
    // ------------------------------------------------------------
    if ($action === 'edit_timezone') {
        $index = (int) ($_POST['index'] ?? -1);
        $name  = trim($_POST['name']     ?? '');
        $tzId  = trim($_POST['timezone'] ?? '');

        if ($index >= 0 && $index < count($timezones) && $name !== '' && $tzId !== '') {
            // Validate the timezone identifier: PHP will throw if
            // it isn't recognized. We catch that and reject it.
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
    // ADD A NEW ALARM
    // ------------------------------------------------------------
    if ($action === 'add_alarm') {
        $name = trim($_POST['name']     ?? '');
        $time = trim($_POST['time']     ?? '');
        $tz   = trim($_POST['timezone'] ?? '');

        // Validate: name non-empty, time matches HH:MM.
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
            // Find the alarm by id and update it in place.
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
                // The ! flips true→false and false→true.
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

        // array_filter returns a new array containing only entries
        // where the callback returns true. We use it to remove the
        // matching alarm.
        $alarms = array_values(array_filter(
            $alarms,
            fn($a) => (int) $a['id'] !== $id
        ));
        $notice = 'Alarm deleted.';
    }

    // ------------------------------------------------------------
    // PERSIST THE CHANGES
    // ------------------------------------------------------------
    // Whatever we changed, write the whole thing back to disk.
    saveData(['timezones' => $timezones, 'alarms' => $alarms]);

    // Reload so the form below reflects the change immediately.
    $data      = loadData();
    $timezones = $data['timezones'];
    $alarms    = $data['alarms'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings — World Clock</title>
    <link rel="stylesheet" href="style.css?v=2">
</head>
<body class="mood-day">

   <header class="site-header">

        <!-- Centered title block -->
        <div class="header-main">
            <h1>⚙ Settings</h1>
            <?php if ($notice !== ''): ?>
                <p class="notice"><?= e($notice) ?></p>
            <?php endif; ?>
        </div>

        <!-- Back link pinned to the far right -->
        <div class="header-action">
            <a class="btn btn-icon" href="index.php" title="Back to clock" aria-label="Back">←</a>
        </div>

    </header>

    <!-- ======================================================
         SECTION 1 — REORDER / EDIT CLOCKS
         ====================================================== -->
    <section class="settings-section">
        <h2>Clocks (order matters — the first one is the main clock)</h2>

        <table class="settings-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Timezone</th>
                    <th>Order</th>
                    <th>Edit</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($timezones as $i => $tz): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= e($tz['name']) ?></td>
                    <td><code><?= e($tz['timezone']) ?></code></td>
                    <td>
                        <!-- Move up: disabled on the first row -->
                        <form method="post" class="inline">
                            <input type="hidden" name="action"    value="move_timezone">
                            <input type="hidden" name="index"     value="<?= $i ?>">
                            <input type="hidden" name="direction" value="up">
                            <button <?= $i === 0 ? 'disabled' : '' ?>>↑</button>
                        </form>

                        <!-- Move down: disabled on the last row -->
                        <form method="post" class="inline">
                            <input type="hidden" name="action"    value="move_timezone">
                            <input type="hidden" name="index"     value="<?= $i ?>">
                            <input type="hidden" name="direction" value="down">
                            <button <?= $i === count($timezones) - 1 ? 'disabled' : '' ?>>↓</button>
                        </form>
                    </td>
                    <td>
                        <!-- Edit form: posts name + timezone for this row -->
                        <form method="post" class="inline edit-form">
                            <input type="hidden" name="action" value="edit_timezone">
                            <input type="hidden" name="index"  value="<?= $i ?>">
                            <input type="text"   name="name"     value="<?= e($tz['name']) ?>"     placeholder="Name">
                            <input type="text"   name="timezone" value="<?= e($tz['timezone']) ?>" placeholder="Area/City">
                            <button>Save</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <!-- ======================================================
         SECTION 2 — ADD ALARM
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
         SECTION 3 — EDIT / TOGGLE / DELETE ALARMS
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
                        <!-- Edit-in-place form spans the first 3 cells -->
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
                        <td>
                                <?= $alarm['enabled'] ? 'ON' : 'off' ?>
                        </td>
                        <td class="actions">
                                <button form="upd-<?= (int) $alarm['id'] ?>">Save</button>
                            </form>
                            <!-- Separate small forms for toggle/delete -->
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