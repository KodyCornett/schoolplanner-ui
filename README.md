# SchoolPlanner UI

A Laravel web UI for **SchoolPlan** (Kotlin), a scheduling engine that turns Canvas assignment due dates into a realistic day-by-day study plan.

This UI allows students to preview the generated plan in an agenda view before exporting it as a calendar file (`.ics`) they can import into Google Calendar, Apple Calendar, or Outlook.

---

## Features
- Import Canvas assignment calendars (`.ics`)
- Optional busy calendar support (work/personal schedule)
- Configure scheduling preferences (horizon, daily caps, weekends, busy weighting)
- Agenda-style plan preview (grouped by day)
- Export generated schedule as `StudyPlan.ics`

---

## Tech Stack
- **Backend:** PHP 8.2+ / Laravel
- **Planner Engine:** Kotlin (SchoolPlan CLI / `.jar`)
- **Output Formats:** iCalendar (`.ics`) + JSON (`plan_events.json`)

---

## How it works
1. Student imports a Canvas calendar feed (`.ics`) or uploads a calendar file.
2. Student selects planning preferences (daily caps, horizon, etc.).
3. The UI runs the SchoolPlan Kotlin engine to generate:
   - `StudyPlan.ics` (calendar import file)
   - `plan_events.json` (structured plan for preview)
4. Student previews the plan in an agenda view.
5. Student downloads `StudyPlan.ics` and imports it into their calendar app.

---

## Project Status
✅ MVP in progress: Import → Generate → Preview → Download

Planned improvements:
- Availability windows (only schedule during allowed hours)
- Finish-before-due buffer rule (avoid last-minute final chunks)
- AI-assisted task refinement and study tools (optional)

---

## Local Setup

### Requirements
- PHP 8.2+
- Composer
- Node.js (optional, for frontend tooling)
- Java 17+ (only required to run the Kotlin planner engine)

### Install
```bash
composer install
cp .env.example .env
php artisan key:generate
