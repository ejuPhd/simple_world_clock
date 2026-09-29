# Adding README.md and Tutorial Files

Let's add a polished README and a tutorial document. Both are simple Markdown files that GitHub renders nicely.

You asked for both `Tutorial.txt` and `.md` — I'll give you the **Markdown** version (renders beautifully on GitHub), and if you still want a plain `.txt` version afterward, I'll produce that too. Let's start with the two files that matter most.

---

## Step 1 — Create `README.md`

In your project folder, create `README.md` with this content:

```markdown
# Simple World Clock

A PHP-powered world clock showing four customizable timezones with
dynamic day/night mood themes and up to six named alarms.

Built as a learning project to explore modern PHP: JSON persistence,
form handling, `DateTime` arithmetic, and clean separation of
configuration, logic, and display.

## Features

- 🌍 **Four timezones** with live ticking (Jeddah as the main clock)
- 🎨 **Day / dawn / dusk / night mood theming** driven by hour of day
- ⏰ **Up to six alarms** with enable/disable toggle
- ⚙️ **Full CRUD** via `settings.php` — reorder clocks, edit names, add/edit/delete alarms
- 💾 **JSON persistence** in `data/clock.json`
- 🕐 **12-hour / 24-hour display toggle**
- 🔒 **XSS-safe output** via `htmlspecialchars()`

## Requirements

- PHP 8.0 or newer
- Any web server (Apache, Nginx) — or PHP's built-in dev server

## Installation

1. **Clone the repository:**
   ```bash
   git clone https://github.com/ejuPhd/simple_world_clock.git
   cd simple_world_clock
   ```

2. **Make the data folder writable:**
   ```bash
   chmod 755 data
   ```

3. **Start PHP's built-in server:**
   ```bash
   php -S localhost:8000
   ```

4. **Open the app** at http://localhost:8000

On first load, `data/clock.json` is created automatically with the
default timezones and alarms. You can then visit
http://localhost:8000/settings.php to customize everything.

## Project Structure

```
simple_world_clock/
├── index.php        Main clock display
├── settings.php     Configuration page (reorder clocks, manage alarms)
├── config.php       JSON loader/saver + defaults
├── helpers.php      Reusable functions (time formatting, mood, escaping)
├── style.css        Themes and layout
├── data/
│   └── clock.json   User data (auto-created, gitignored)
└── README.md
```

## How It Works

- **`config.php`** holds the path to the JSON file, seeds defaults on
  first run, and provides `loadData()` / `saveData()` / `nextAlarmId()`.
- **`helpers.php`** contains pure functions: `timeInZone()`,
  `hourInZone()`, `moodForHour()`, `findRingingAlarms()`, `e()`,
  `isValidTime()`, `formatTime()`.
- **`index.php`** loads the data, decides the page's mood from the
  first timezone's hour, and renders the cards. A small inline
  `<script>` ticks the clocks every second.
- **`settings.php`** handles all POST actions (move, edit, add,
  toggle, delete), validates input, and writes back to JSON.

## Design Notes

- **Store canonical, format for display.** All times are stored as
  `HH:MM` in 24-hour format. The 12/24 preference only affects how
  they're rendered.
- **The first timezone is "main."** Its hour drives the whole page's
  mood theme. Reorder in settings to change which city is main.
- **Escaping is mandatory.** Every echoed value passes through `e()`
  to prevent cross-site scripting.

## Roadmap

- [ ] JavaScript respects the 12/24-hour toggle (currently PHP-only)
- [ ] "Next alarm in Xh Ym" countdown
- [ ] Days-of-week per alarm (Mon–Fri only)
- [ ] CSRF tokens on all forms
- [ ] Optional login for multi-user support

## License

MIT

## Author

**Dr. Earnest Ujaama**
Another app by Dr. Earnest Ujaama for appsbyeu
```

---

## Step 2 — Create `TUTORIAL.md`

Create `TUTORIAL.md` with this content. It's aimed at someone who wants to *learn* from your code, which is a great gift to the PHP community.

```markdown
# Tutorial: Building a World Clock in PHP

This tutorial walks through how the Simple World Clock was built, and
the concepts each file teaches. If you're learning PHP, follow along
in order.

## What You'll Learn

1. Separating configuration, logic, and display
2. Working with `DateTime` and `DateTimeZone`
3. Reading and writing JSON files for persistence
4. Handling HTML forms with `$_POST`
5. Validating user input safely
6. Escaping output to prevent XSS
7. Writing defensive PHP with `??` and type declarations
8. A taste of CSS theming with custom properties

---

## 1. The Big Picture

A common beginner mistake is putting everything in one file. We split
the app into four PHP files:

| File | Responsibility |
|------|----------------|
| `config.php` | Data storage — load, save, defaults |
| `helpers.php` | Pure functions — no side effects |
| `index.php` | Display the clock |
| `settings.php` | Edit everything |

Rule of thumb: **if a file does more than one job, split it.**

---

## 2. Timezones in PHP

PHP ships with a full timezone database. Every timezone has an
identifier like `Asia/Riyadh` or `America/Denver`. To get the
current time in one:

```php
$tz  = new DateTimeZone('Asia/Riyadh');
$now = new DateTime('now', $tz);
echo $now->format('H:i:s');  // 14:07:33
```

The format codes you'll need most:

| Code | Meaning | Example |
|------|---------|---------|
| `H` | 24-hour, leading zero | `07`, `14` |
| `G` | 24-hour, no leading zero | `7`, `14` |
| `h` | 12-hour, leading zero | `07`, `02` |
| `g` | 12-hour, no leading zero | `7`, `2` |
| `i` | Minutes | `07` |
| `s` | Seconds | `33` |
| `A` | Uppercase AM/PM | `AM`, `PM` |

**Format for display, store canonically.** We store alarms as `HH:MM`
(24-hour). Only when rendering do we swap in `g:i:s A` for users who
prefer 12-hour.

---

## 3. Persistence with JSON

Instead of a database, we use a JSON file for storage. That's plenty
for a project this size and easy to inspect by hand.

**Reading:**

```php
$json = file_get_contents('data/clock.json');
$data = json_decode($json, true);   // true = array, not object
```

**Writing:**

```php
$json = json_encode($data, JSON_PRETTY_PRINT);
file_put_contents('data/clock.json', $json);
```

**Two important details:**

1. **`json_decode(..., true)`** — the second argument forces PHP to
   return associative arrays instead of `stdClass` objects. Easier
   to work with.

2. **`JSON_PRETTY_PRINT`** — makes the file human-readable. Great for
   learning; you can open the JSON and see exactly what changed.

**Always wrap reads in existence checks:**

```php
if (!file_exists(DATA_FILE)) {
    $data = defaultData();
    saveData($data);
}
```

This is what makes the app "just work" on first run — no setup step.

---

## 4. Handling Forms

Every form in `settings.php` posts to itself. We use a hidden field
called `action` to tell PHP which form was submitted:

```html
<input type="hidden" name="action" value="add_alarm">
```

And in PHP:

```php
$action = $_POST['action'] ?? '';
if ($action === 'add_alarm') { /* ... */ }
```

This "action dispatcher" pattern keeps everything in one file
without becoming a mess. It's the same idea used in every PHP
framework (Laravel, Symfony, Slim).

---

## 5. Validating Input

**Never trust `$_POST`.** Everything that comes in is a string
controlled by the user. That means:

- **Timing** — must match `HH:MM`. We check with a regex:
  ```php
  function isValidTime(string $time): bool {
      return (bool) preg_match('/^\d{2}:\d{2}$/', $time);
  }
  ```

- **Timezones** — must be real. PHP will throw if you construct
  `new DateTimeZone('Fake/Zone')`, so we wrap it:
  ```php
  try {
      new DateTimeZone($tzId);
      // ... valid, accept it
  } catch (Exception $e) {
      // ... invalid, reject
  }
  ```

- **Text** — trim whitespace, then check it's not empty:
  ```php
  $name = trim($_POST['name'] ?? '');
  if ($name === '') { /* reject */ }
  ```

---

## 6. Escaping Output (XSS Prevention)

Never print user input raw. If someone names an alarm
`<script>alert('hi')</script>`, and you `echo` it directly, the
browser runs it.

Instead:

```php
function e(string $text): string {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}
```

Then every output goes through it:

```php
<h2><?= e($tz['name']) ?></h2>
```

**Rule:** if a value came from a file or user input, escape it before
printing. No exceptions.

---

## 7. Defensive PHP

Modern PHP has two features that make code much safer:

### Null coalescing (`??`)

Returns the right side if the left is null *or* missing:

```php
$enabled = $alarm['enabled'] ?? false;
```

Without it, PHP warns about "undefined array key." With it, you get
a sensible default.

**Warning:** precedence matters. `!$x ?? false` binds `!` tighter
than `??`, so it reads `(!$x) ?? false` — which warns when `$x` is
missing. You want `!($x ?? false)`.

### Strict types

Put this at the very top of every file:

```php
<?php
declare(strict_types=1);
```

It must be the **first statement** — no comments or HTML above it.
With strict types on, PHP won't silently convert `"5"` to `5`.
Bugs surface immediately instead of hiding.

---

## 8. Themable CSS with Custom Properties

The clock changes color based on the hour. We do that with CSS
custom properties (variables) redefined per mood:

```css
body.mood-day {
    --bg: #cfe9ff;
    --text: #10243a;
    --card: #ffffff;
    --accent: #3a7bd5;
}
```

Then everything downstream just uses `var(--bg)` etc. PHP sets the
class on `<body>`:

```php
<body class="mood-<?= e($mood) ?>">
```

Changing the theme is a one-line change in PHP, and CSS handles the
rest automatically.

---

## 9. The Live-Ticking Trick

PHP runs on the server **before** the page reaches the browser. By
the time the user sees the page, PHP is done. It can't update the
clock every second.

So we use JavaScript for the ticking. PHP renders the initial
values; JavaScript reads each card's `data-timezone` attribute and
updates the display:

```js
document.querySelectorAll('[data-clock]').forEach(el => {
    const tz = el.getAttribute('data-timezone');
    el.textContent = timeInZone(tz);
});
setInterval(tick, 1000);
```

**The important lesson:** PHP and JavaScript solve different problems.
PHP generates pages. JS makes them interactive. Use each for what
it's good at.

---

## 10. What to Explore Next

Try these on your own:

1. **Add "next alarm in X hours"** — requires `DateTime::diff()`.
2. **Let users pick days of week** for each alarm.
3. **Add CSRF tokens** to every form. Search "PHP CSRF token" and
   implement the standard pattern.
4. **Replace the JSON file with SQLite.** You'll learn PDO.
5. **Deploy to a real host** — try Fly.io, Railway, or a $5 VPS.

The best learning comes from breaking things and fixing them. Back
up `data/clock.json` before experiments so you can always restore.

---

## Credits

Built by **Dr. Earnest Ujaama** as a PHP learning project.
Another app by Dr. Earnest Ujaama for appsbyeu.
```

---

## Step 3 — Commit and push both files

Run these in order, from your project folder:

```bash
git add README.md TUTORIAL.md
git status
```

Verify both are listed as new files. Then:

```bash
git commit -m "Add README and tutorial documentation"
git push
```

No `-u origin main` needed anymore — remember, we set the upstream in the first push.

---

## Step 4 — Verify on GitHub

Open https://github.com/ejuPhd/simple_world_clock

You should see:

- The **README** rendered nicely on the repo home page
- A new **TUTORIAL.md** file in the file listing (GitHub will show the `.md` — click it to see it rendered)

---

