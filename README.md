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