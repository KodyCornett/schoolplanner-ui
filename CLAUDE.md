# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

The School Planner system automatically generates study schedules from academic calendar data. It consists of two major parts:

1. **Kotlin Engine (SchoolCalendarSync / SchoolPlan)**
   - Parses Canvas `.ics` feeds and optional busy-time calendars
   - Applies scheduling constraints (daily caps, horizon, weights, priorities)
   - Outputs: `StudyPlan.ics` and `plan_events.json`

2. **Laravel UI (schoolplanner-ui)** ← This repository
   - Consumes engine output
   - Provides an **interactive preview** where users can review, adjust, lock, or exclude events
   - Final confirmation triggers `.ics` generation

This repository focuses on the **UI + integration layer**, not the scheduling algorithm itself.

## Development Environment

- **Windows machine** - All code and commands should be Windows-compatible
- File paths use backslashes; be mindful of path separators in code
- The Kotlin engine requires `SystemRoot` environment variable for Java socket initialization

## Core Principles

- **Do not rewrite or redesign the Kotlin scheduling logic**
- The UI should treat the engine output as authoritative unless explicitly overridden by user interaction
- Changes should be incremental and non-destructive
- Prefer clarity and predictability over clever abstractions

## Data Contracts

The UI depends on these stable outputs from the Kotlin engine:
- `plan_events.json`
- `StudyPlan.ics`

**Never change field names, date formats, or semantics** in these files without explicit instruction.

## Common Commands

### Development Server
```bash
composer dev    # Starts Laravel server, queue worker, Pail logs, and Vite concurrently
```

### Build
```bash
npm run build   # Production build via Vite
```

### Testing
```bash
composer test                              # Run all tests (clears config first)
php artisan test                           # Run all tests directly
php artisan test --filter=IcsParserTest    # Run a specific test class
php artisan test tests/Unit/IcsParserTest.php  # Run a specific test file
```

### Code Formatting
```bash
./vendor/bin/pint    # Laravel Pint (PSR-12 style)
```

### Setup
```bash
composer setup   # Full setup: install deps, create .env, key:generate, migrate, npm install/build
```

## Architecture

### User Flow
1. **Import** (`/plan/import`) - User uploads Canvas `.ics` or provides URL, optionally busy calendar, and sets preferences
2. **Generate** (`/plan/generate`) - Runs SchoolPlan Kotlin engine via JAR to create study plan
3. **Preview** (`/plan/preview`) - Interactive calendar UI for editing work blocks
4. **Download** (`/plan/download`) - Export final `StudyPlan.ics`

### Core Services (`app/Services/`)

- **IcsParser** - Parses ICS files (RFC 5545), handles line unfolding, extracts Canvas assignments and engine work blocks
- **PlanEventsBuilder** - Builds preview state by correlating Canvas assignments with engine-generated work blocks
- **EffortRedistributor** - Handles effort rebalancing when blocks are edited/deleted; maintains total effort by adjusting non-anchored blocks
- **IcsGenerator** - Generates downloadable ICS from the preview state

### Preview State Structure
The preview system uses a JSON state structure with:
- `assignments[]` - Canvas assignments with due dates and total effort
- `work_blocks[]` - Scheduled study blocks with `is_anchored` flag (user-edited blocks become anchored)
- `busy_times[]` - Optional busy time blocks from imported calendar

**JSON is the source of truth for preview state.**

### Frontend (`resources/js/preview/`)
- **calendar.js** - Main preview UI, renders calendar grid with drag/resize for work blocks
- **api.js** - Fetch wrapper for preview API endpoints (CRUD operations on blocks/assignments)

### Engine Integration
The Kotlin engine JAR is invoked via `Process::run()`. Configuration:
- `config/schoolplan.php` defines `jar_path` and `java_bin`
- Engine reads a per-run `local.properties` file with settings
- Canvas ICS served via secondary file server on port 8001 (avoids deadlock with artisan serve)

### Session-Based State
Plan runs are stored in `session('plan.run')` with:
- Run ID (UUID)
- File paths (canvas, busy, output ICS)
- Settings (horizon, soft_cap, hard_cap, skip_weekends, busy_weight)
- Preview state (assignments + work_blocks for interactive editing)

## Preview System Rules

- The preview must be editable, reversible (users can undo or reset), and non-final until confirmed
- No `.ics` file should be generated until the user confirms the preview
- The preview is a **simulation**, not a re-run of the engine

## Coding Guidelines

- Use existing Laravel conventions
- Avoid introducing unnecessary dependencies
- Prefer readable, explicit code
- No magic numbers — configuration should live in config files
- All changes should be compatible with IntelliJ Ultimate + npm tooling

## Git & Workflow

- Make small, focused changes
- Commit messages should be professional and descriptive
- Do not squash unrelated changes into one commit
- Never commit generated `.ics` files

## What NOT To Do

- Do not invent new scheduling rules
- Do not auto-adjust user data silently
- Do not assume timezones — always respect calendar metadata
- Do not generate fake test data unless explicitly asked

## Assumptions

- Users are students using Canvas calendars
- Time conflicts matter
- Users want control before final export

## If Something Is Ambiguous

Ask for clarification. Prefer asking over guessing when behavior affects schedules or deadlines.