<?php

namespace App\Services;

class IcsGenerator
{
    /**
     * Generate an ICS file from the preview state.
     *
     * @param  array  $previewState  The current preview state with assignments and work_blocks
     * @return string ICS file content
     */
    public function generate(array $previewState): string
    {
        $lines = [];

        // Calendar header
        $lines[] = 'BEGIN:VCALENDAR';
        $lines[] = 'VERSION:2.0';
        $lines[] = 'PRODID:-//Modulus//Interactive Preview//EN';
        $lines[] = 'CALSCALE:GREGORIAN';
        $lines[] = 'METHOD:PUBLISH';
        $lines[] = 'X-WR-CALNAME:Study Plan';
        $lines[] = 'X-WR-CALDESC:Generated study plan from Modulus';

        // Build assignment lookup
        $assignments = [];
        foreach ($previewState['assignments'] ?? [] as $assignment) {
            $assignments[$assignment['id']] = $assignment;
        }

        // Generate events for each work block
        $workBlocks = $previewState['work_blocks'] ?? [];

        foreach ($workBlocks as $index => $block) {
            $assignment = $assignments[$block['assignment_id']] ?? null;
            $lines = array_merge($lines, $this->generateEvent($block, $assignment, $index));
        }

        // Calendar footer
        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", $lines)."\r\n";
    }

    /**
     * Generate a VEVENT for a work block.
     */
    private function generateEvent(array $block, ?array $assignment, int $index): array
    {
        $lines = [];

        $lines[] = 'BEGIN:VEVENT';

        // UID - unique identifier
        $uid = 'studyplan-'.$block['date'].'-'.$index.'@modulus';
        $lines[] = 'UID:'.$uid;

        // Timestamp
        $dtstamp = gmdate('Ymd\THis\Z');
        $lines[] = 'DTSTAMP:'.$dtstamp;

        // Date and time
        $date = str_replace('-', '', $block['date']);
        $startTime = str_replace(':', '', $block['start_time']).'00';
        $endTime = $this->calculateEndTime($block['start_time'], $block['duration_minutes']);

        $lines[] = 'DTSTART:'.$date.'T'.$startTime;
        $lines[] = 'DTEND:'.$date.'T'.$endTime;

        // Summary: [phase label] Assignment Title [Course]
        $summary = $block['label'] ?? '';
        if ($assignment) {
            $summary .= ' '.$assignment['title'];
            if (! empty($assignment['course'])) {
                $summary .= ' ['.$assignment['course'].']';
            }
        }
        $lines[] = 'SUMMARY:'.$this->escapeIcsValue(trim($summary));

        // Description
        $description = 'Scheduled study block';
        if ($assignment && ! empty($assignment['description'])) {
            $description .= "\n\n".$assignment['description'];
        }
        $lines[] = 'DESCRIPTION:'.$this->escapeIcsValue($description);

        // Categories
        if ($assignment && ! empty($assignment['course'])) {
            $lines[] = 'CATEGORIES:'.$this->escapeIcsValue($assignment['course']);
        }

        $lines[] = 'END:VEVENT';

        return $lines;
    }

    /**
     * Calculate end time given start time and duration.
     */
    private function calculateEndTime(string $startTime, int $durationMinutes): string
    {
        $parts = explode(':', $startTime);
        $hours = (int) $parts[0];
        $minutes = (int) $parts[1];

        $totalMinutes = $hours * 60 + $minutes + $durationMinutes;
        $endHours = (int) floor($totalMinutes / 60) % 24;
        $endMinutes = $totalMinutes % 60;

        return sprintf('%02d%02d00', $endHours, $endMinutes);
    }

    /**
     * Escape a value for ICS format.
     */
    private function escapeIcsValue(string $value): string
    {
        // Escape backslashes first
        $value = str_replace('\\', '\\\\', $value);
        // Escape semicolons
        $value = str_replace(';', '\\;', $value);
        // Escape commas
        $value = str_replace(',', '\\,', $value);
        // Convert newlines to \n
        $value = str_replace(["\r\n", "\r", "\n"], '\\n', $value);

        return $value;
    }
}
