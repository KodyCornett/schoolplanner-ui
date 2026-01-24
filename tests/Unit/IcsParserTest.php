<?php

namespace Tests\Unit;

use App\Services\IcsParser;
use PHPUnit\Framework\TestCase;

class IcsParserTest extends TestCase
{
    private IcsParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new IcsParser();
    }

    public function test_parses_valid_ics_with_single_event(): void
    {
        $ics = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Test//Test//EN
BEGIN:VEVENT
UID:test-123@example.com
DTSTART:20260125
DTEND:20260125
SUMMARY:Test Event
DESCRIPTION:Test description
END:VEVENT
END:VCALENDAR
ICS;

        $events = $this->parser->parse($ics);

        $this->assertCount(1, $events);
        $this->assertEquals('test-123@example.com', $events[0]['uid']);
        $this->assertEquals('2026-01-25', $events[0]['dtstart']);
        $this->assertEquals('Test Event', $events[0]['summary']);
        $this->assertEquals('Test description', $events[0]['description']);
    }

    public function test_parses_multiple_events(): void
    {
        $ics = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:event-1
DTSTART:20260121
SUMMARY:First Event
END:VEVENT
BEGIN:VEVENT
UID:event-2
DTSTART:20260122
SUMMARY:Second Event
END:VEVENT
BEGIN:VEVENT
UID:event-3
DTSTART:20260123
SUMMARY:Third Event
END:VEVENT
END:VCALENDAR
ICS;

        $events = $this->parser->parse($ics);

        $this->assertCount(3, $events);
        $this->assertEquals('First Event', $events[0]['summary']);
        $this->assertEquals('Second Event', $events[1]['summary']);
        $this->assertEquals('Third Event', $events[2]['summary']);
    }

    public function test_parses_datetime_with_time(): void
    {
        $ics = <<<ICS
BEGIN:VCALENDAR
BEGIN:VEVENT
UID:datetime-test
DTSTART:20260125T093000Z
DTEND:20260125T103000Z
SUMMARY:Timed Event
END:VEVENT
END:VCALENDAR
ICS;

        $events = $this->parser->parse($ics);

        $this->assertCount(1, $events);
        $this->assertEquals('2026-01-25', $events[0]['dtstart']);
        $this->assertEquals('20260125T093000Z', $events[0]['dtstart_raw']);
    }

    public function test_parses_date_with_value_parameter(): void
    {
        $ics = <<<ICS
BEGIN:VCALENDAR
BEGIN:VEVENT
UID:date-value-test
DTSTART;VALUE=DATE:20260125
SUMMARY:All Day Event
END:VEVENT
END:VCALENDAR
ICS;

        $events = $this->parser->parse($ics);

        $this->assertCount(1, $events);
        $this->assertEquals('2026-01-25', $events[0]['dtstart']);
    }

    public function test_handles_escaped_characters(): void
    {
        // Note: In ICS files, \n is used to represent newlines
        // We need to use actual backslash-n in the test string
        $ics = "BEGIN:VCALENDAR\r\n" .
               "BEGIN:VEVENT\r\n" .
               "UID:escape-test\r\n" .
               "DTSTART:20260125\r\n" .
               "SUMMARY:Test with\\, comma and\\; semicolon\r\n" .
               "DESCRIPTION:Line 1\\nLine 2\\nLine 3\r\n" .
               "END:VEVENT\r\n" .
               "END:VCALENDAR\r\n";

        $events = $this->parser->parse($ics);

        $this->assertCount(1, $events);
        $this->assertEquals('Test with, comma and; semicolon', $events[0]['summary']);
        $this->assertStringContainsString("\n", $events[0]['description']);
    }

    public function test_handles_folded_lines(): void
    {
        $ics = "BEGIN:VCALENDAR\r\n" .
               "BEGIN:VEVENT\r\n" .
               "UID:fold-test\r\n" .
               "DTSTART:20260125\r\n" .
               "SUMMARY:This is a very long summary that has been folded across\r\n" .
               " multiple lines according to RFC 5545 rules\r\n" .
               "END:VEVENT\r\n" .
               "END:VCALENDAR\r\n";

        $events = $this->parser->parse($ics);

        $this->assertCount(1, $events);
        $this->assertStringContainsString('folded across', $events[0]['summary']);
        $this->assertStringContainsString('multiple lines', $events[0]['summary']);
    }

    public function test_returns_empty_array_for_empty_input(): void
    {
        $events = $this->parser->parse('');
        $this->assertIsArray($events);
        $this->assertCount(0, $events);
    }

    public function test_returns_empty_array_for_ics_without_events(): void
    {
        $ics = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Test//Test//EN
END:VCALENDAR
ICS;

        $events = $this->parser->parse($ics);
        $this->assertIsArray($events);
        $this->assertCount(0, $events);
    }

    public function test_parse_canvas_calendar_extracts_assignments(): void
    {
        $ics = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:canvas-1234
DTSTART;VALUE=DATE:20260126
SUMMARY:Android CRUD Client [CIS218 01 27532]
DESCRIPTION:Build a CRUD client using Android
URL:https://canvas.example.edu/courses/123/assignments/456
END:VEVENT
END:VCALENDAR
ICS;

        $assignments = $this->parser->parseCanvasCalendar($ics);

        $this->assertCount(1, $assignments);
        $this->assertEquals('Android CRUD Client', $assignments[0]['title']);
        $this->assertEquals('CIS218 01 27532', $assignments[0]['course']);
        $this->assertEquals('2026-01-26', $assignments[0]['due_date']);
        $this->assertEquals('https://canvas.example.edu/courses/123/assignments/456', $assignments[0]['url']);
    }

    public function test_parse_engine_output_extracts_work_blocks(): void
    {
        $ics = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:block-001
DTSTART:20260122
SUMMARY:[requirements + setup project] Android CRUD Client [CIS218 01 27532]
END:VEVENT
BEGIN:VEVENT
UID:block-002
DTSTART:20260123
SUMMARY:[implement core logic] Android CRUD Client [CIS218 01 27532]
END:VEVENT
END:VCALENDAR
ICS;

        $blocks = $this->parser->parseEngineOutput($ics);

        $this->assertCount(2, $blocks);

        $this->assertEquals('[requirements + setup project]', $blocks[0]['label']);
        $this->assertEquals('Android CRUD Client', $blocks[0]['assignment_title']);
        $this->assertEquals('CIS218 01 27532', $blocks[0]['course']);
        $this->assertEquals('2026-01-22', $blocks[0]['date']);

        $this->assertEquals('[implement core logic]', $blocks[1]['label']);
        $this->assertEquals('2026-01-23', $blocks[1]['date']);
    }

    public function test_parse_engine_output_handles_missing_label(): void
    {
        $ics = <<<ICS
BEGIN:VCALENDAR
BEGIN:VEVENT
UID:no-label
DTSTART:20260125
SUMMARY:Assignment Without Label [COURSE101]
END:VEVENT
END:VCALENDAR
ICS;

        $blocks = $this->parser->parseEngineOutput($ics);

        $this->assertCount(1, $blocks);
        $this->assertEquals('', $blocks[0]['label']);
        $this->assertEquals('Assignment Without Label', $blocks[0]['assignment_title']);
        $this->assertEquals('COURSE101', $blocks[0]['course']);
    }

    public function test_parse_engine_output_handles_missing_course(): void
    {
        $ics = <<<ICS
BEGIN:VCALENDAR
BEGIN:VEVENT
UID:no-course
DTSTART:20260125
SUMMARY:[phase] Assignment Without Course
END:VEVENT
END:VCALENDAR
ICS;

        $blocks = $this->parser->parseEngineOutput($ics);

        $this->assertCount(1, $blocks);
        $this->assertEquals('[phase]', $blocks[0]['label']);
        $this->assertEquals('Assignment Without Course', $blocks[0]['assignment_title']);
        $this->assertEquals('', $blocks[0]['course']);
    }
}
