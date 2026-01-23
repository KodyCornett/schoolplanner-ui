<?php

namespace App\Services;

class IcsParser
{
    /**
     * Parse an ICS file and return an array of events.
     *
     * @param string $icsContent Raw ICS file content
     * @return array Array of parsed events
     */
    public function parse(string $icsContent): array
    {
        // Unfold lines (RFC 5545: lines can be folded with CRLF + whitespace)
        $icsContent = $this->unfoldLines($icsContent);

        $events = [];
        $lines = preg_split('/\r\n|\r|\n/', $icsContent);

        $inEvent = false;
        $currentEvent = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === 'BEGIN:VEVENT') {
                $inEvent = true;
                $currentEvent = [];
                continue;
            }

            if ($line === 'END:VEVENT') {
                $inEvent = false;
                if (!empty($currentEvent)) {
                    $events[] = $this->normalizeEvent($currentEvent);
                }
                continue;
            }

            if ($inEvent && str_contains($line, ':')) {
                // Parse property:value, handling parameters like DTSTART;VALUE=DATE:20260121
                $colonPos = strpos($line, ':');
                $propertyPart = substr($line, 0, $colonPos);
                $value = substr($line, $colonPos + 1);

                // Extract property name (before any semicolon)
                $semicolonPos = strpos($propertyPart, ';');
                if ($semicolonPos !== false) {
                    $property = substr($propertyPart, 0, $semicolonPos);
                    $params = substr($propertyPart, $semicolonPos + 1);
                } else {
                    $property = $propertyPart;
                    $params = '';
                }

                $currentEvent[$property] = [
                    'value' => $this->unescapeValue($value),
                    'params' => $params,
                ];
            }
        }

        return $events;
    }

    /**
     * Parse engine output ICS and extract work blocks.
     *
     * @param string $icsContent Engine-generated ICS content
     * @return array Array of work block data
     */
    public function parseEngineOutput(string $icsContent): array
    {
        $events = $this->parse($icsContent);
        $blocks = [];

        foreach ($events as $event) {
            $summary = $event['summary'] ?? '';
            $uid = $event['uid'] ?? '';
            $date = $event['dtstart'] ?? '';

            // Parse SUMMARY: [phase label] Assignment Title [Course Code]
            $parsed = $this->parseSummary($summary);

            $blocks[] = [
                'uid' => $uid,
                'date' => $date,
                'label' => $parsed['label'],
                'assignment_title' => $parsed['title'],
                'course' => $parsed['course'],
                'description' => $event['description'] ?? '',
            ];
        }

        return $blocks;
    }

    /**
     * Parse Canvas ICS and extract assignments.
     *
     * @param string $icsContent Canvas calendar ICS content
     * @return array Array of assignment data
     */
    public function parseCanvasCalendar(string $icsContent): array
    {
        $events = $this->parse($icsContent);
        $assignments = [];

        foreach ($events as $event) {
            $summary = $event['summary'] ?? '';
            $uid = $event['uid'] ?? '';
            $dueDate = $event['dtstart'] ?? $event['dtend'] ?? '';
            $description = $event['description'] ?? '';
            $url = $event['url'] ?? '';

            // Canvas events often have course info in brackets at the end
            $parsed = $this->parseCanvasSummary($summary);

            $assignments[] = [
                'uid' => $uid,
                'title' => $parsed['title'],
                'course' => $parsed['course'],
                'due_date' => $dueDate,
                'description' => $description,
                'url' => $url,
            ];
        }

        return $assignments;
    }

    /**
     * Unfold lines per RFC 5545 (lines folded with CRLF + whitespace).
     */
    private function unfoldLines(string $content): string
    {
        // Replace CRLF followed by space/tab with empty string
        return preg_replace('/\r?\n[ \t]/', '', $content);
    }

    /**
     * Unescape ICS values per RFC 5545.
     */
    private function unescapeValue(string $value): string
    {
        $value = str_replace('\\n', "\n", $value);
        $value = str_replace('\\N', "\n", $value);
        $value = str_replace('\\,', ',', $value);
        $value = str_replace('\\;', ';', $value);
        $value = str_replace('\\\\', '\\', $value);
        return $value;
    }

    /**
     * Normalize event array to consistent keys.
     */
    private function normalizeEvent(array $event): array
    {
        $normalized = [];

        foreach ($event as $property => $data) {
            $key = strtolower($property);
            $value = $data['value'];

            // Parse date values
            if (in_array($key, ['dtstart', 'dtend', 'dtstamp'])) {
                $value = $this->parseDate($value, $data['params']);
            }

            $normalized[$key] = $value;
        }

        return $normalized;
    }

    /**
     * Parse ICS date value to Y-m-d format.
     */
    private function parseDate(string $value, string $params): string
    {
        // All-day events: VALUE=DATE, format YYYYMMDD
        if (str_contains($params, 'VALUE=DATE') || strlen($value) === 8) {
            if (preg_match('/^(\d{4})(\d{2})(\d{2})$/', $value, $m)) {
                return "{$m[1]}-{$m[2]}-{$m[3]}";
            }
        }

        // DateTime format: YYYYMMDDTHHMMSS or YYYYMMDDTHHMMSSZ
        if (preg_match('/^(\d{4})(\d{2})(\d{2})T(\d{2})(\d{2})(\d{2})Z?$/', $value, $m)) {
            return "{$m[1]}-{$m[2]}-{$m[3]}";
        }

        return $value;
    }

    /**
     * Parse engine SUMMARY format: [phase label] Assignment Title [Course Code]
     */
    private function parseSummary(string $summary): array
    {
        $label = '';
        $title = $summary;
        $course = '';

        // Extract [phase label] from start
        if (preg_match('/^\[([^\]]+)\]\s*/', $summary, $m)) {
            $label = '[' . $m[1] . ']';
            $summary = substr($summary, strlen($m[0]));
        }

        // Extract [Course Code] from end
        if (preg_match('/\s*\[([^\]]+)\]$/', $summary, $m)) {
            $course = $m[1];
            $title = trim(substr($summary, 0, -strlen($m[0])));
        } else {
            $title = trim($summary);
        }

        return [
            'label' => $label,
            'title' => $title,
            'course' => $course,
        ];
    }

    /**
     * Parse Canvas SUMMARY format: Assignment Title [Course Code]
     */
    private function parseCanvasSummary(string $summary): array
    {
        $title = $summary;
        $course = '';

        // Extract [Course Code] from end
        if (preg_match('/\s*\[([^\]]+)\]$/', $summary, $m)) {
            $course = $m[1];
            $title = trim(substr($summary, 0, -strlen($m[0])));
        }

        return [
            'title' => $title,
            'course' => $course,
        ];
    }
}