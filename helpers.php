<?php
declare(strict_types=1);

// helpers.php
// A place for small, reusable functions. Keeping these separate means
// index.php stays focused on displaying things.

/**
 * Get the current time in a given timezone, formatted nicely.
 *
 * @param string $timezone  A PHP timezone identifier, e.g. "Europe/London"
 * @param string $format    A PHP date() format string
 * @return string           The formatted current time
 */
function timeInZone(string $timezone, string $format = 'H:i:s'): string
{
    // DateTimeZone is a built-in PHP class that represents a timezone.
    // We create one from the identifier string.
    $tz = new DateTimeZone($timezone);

    // DateTime represents a moment in time.
    // Passing 'now' means "the current moment".
    // Passing the timezone means "express it in this zone".
    $now = new DateTime('now', $tz);

    // ->format() converts the moment into a string using $format.
    // 'H' = 24-hour, 'i' = minutes, 's' = seconds.
    return $now->format($format);
}

/**
 * Return the hour (0-23) in a given timezone. Useful for deciding
 * whether it's day, night, dawn, or dusk — which drives our theme.
 */
function hourInZone(string $timezone): int
{
    // Same idea as above, but we cast the hour to an integer.
    $tz  = new DateTimeZone($timezone);
    $now = new DateTime('now', $tz);

    // 'G' = hour without a leading zero. The empty string here would
    // return nothing, which is why this was previously broken.
    return (int) $now->format('G');
}

/**
 * Decide a "mood" string based on the hour of the day.
 * This drives the color theme in CSS.
 *
 * Returns one of: 'night', 'dawn', 'day', 'dusk'.
 */
function moodForHour(int $hour): string
{
    // 0-4   = night  (falls through to the final return)
    // 5-7   = dawn
    // 8-17  = day
    // 18-20 = dusk
    // 21-23 = night  (falls through to the final return)

    if ($hour >= 5 && $hour <= 7) {
        return 'dawn';
    }
    if ($hour >= 8 && $hour <= 17) {
        return 'day';
    }
    if ($hour >= 18 && $hour <= 20) {
        return 'dusk';
    }
    return 'night';
}

/**
 * Given the full list of alarms, find which alarm (if any) is
 * currently ringing.
 *
 * An alarm is "ringing" when its HH:MM in ITS timezone equals
 * the current HH:MM in that same timezone, and it is enabled.
 *
 * Returns an array of ringing alarms (could be more than one).
 */
function findRingingAlarms(array $alarms): array
{
    // This will collect any alarms that match right now.
    $ringing = [];

    // Loop over every alarm.
    foreach ($alarms as $alarm) {

        // Skip disabled alarms immediately.
        if (!($alarm['enabled'] ?? false)) {
            continue;
        }

        // Get the current time in the alarm's timezone in HH:MM format.
        $currentHHMM = timeInZone($alarm['timezone'], 'H:i');

        // If the current HH:MM matches the alarm's HH:MM exactly,
        // it's ringing. We use strict equality (===) so we're not
        // accidentally treating every later minute as a match.
        if ($currentHHMM === $alarm['time']) {
            $ringing[] = $alarm;
        }
    }

    // Return the list (possibly empty).
    return $ringing;
}

/**
 * Escape text before printing it into HTML.
 * ALWAYS do this for anything that could contain user input.
 * It prevents XSS (cross-site scripting) attacks.
 */
function e(string $text): string
{
    // htmlspecialchars converts < > & " ' into safe HTML entities.
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Return true if $time is a valid 24-hour HH:MM string.
 * We use this to reject bad input from the alarm form.
 */
function isValidTime(string $time): bool
{
    // The regex: two digits, a colon, two digits.
    // ^ and $ anchor to the whole string so "12:345" fails.
    return (bool) preg_match('/^\d{2}:\d{2}$/', $time);
}
