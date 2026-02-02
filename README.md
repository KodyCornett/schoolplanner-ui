# Modulus

Modulus automatically generates study schedules from Canvas assignment calendars. Students import their Canvas calendar feed, configure preferences, preview the generated plan interactively, and export it to Google Calendar, Apple Calendar, or Outlook.

---

## Features

### Core Scheduling
- Import Canvas assignment calendars (`.ics` URL or file upload)
- Optional busy calendar support (work, personal, or other commitments)
- Configure scheduling preferences:
  - Planning horizon (how far ahead to schedule)
  - Daily soft/hard caps (hours per day)
  - Weekend scheduling toggle
  - Busy time weighting

### Interactive Preview
- Drag-and-drop work blocks to reschedule
- Resize blocks to adjust duration
- Lock blocks to prevent redistribution
- Exclude assignments from the plan
- Real-time effort redistribution when editing

### Export
- Download `StudyPlan.ics` for calendar import
- Compatible with Google Calendar, Apple Calendar, Outlook, and other iCalendar apps

### User Accounts
- User registration and authentication
- Profile management
- Session-based plan storage

### Billing (Stripe Integration)
- Free tier with basic features
- Pro subscription via Stripe Checkout
- Customer portal for subscription management

### Public Pages
- Landing page with feature overview
- Pricing page
- Help documentation
- Terms of Service
- Privacy Policy
- Contact page

---

## Tech Stack

| Layer | Technology |
|-------|------------|
| Backend | PHP 8.3 / Laravel 11 |
| Authentication | Laravel Breeze |
| Billing | Laravel Cashier (Stripe) |
| Frontend | Blade, Tailwind CSS, Alpine.js |
| Build | Vite |
| Planner Engine | Kotlin (SchoolPlan JAR) |
| Database | SQLite (default) |
| Queue | Database driver |

---

## How It Works

1. **Sign up** - Create an account or log in
2. **Import** - Provide Canvas calendar URL or upload `.ics` file
3. **Configure** - Set planning preferences (horizon, daily caps, etc.)
4. **Generate** - Kotlin engine creates optimized study schedule
5. **Preview** - Review and adjust the plan interactively
6. **Export** - Download `StudyPlan.ics` and import into your calendar

---

## Architecture

```
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────┐
│  Laravel UI     │────▶│  Kotlin Engine   │────▶│  Preview State  │
│  (This Repo)    │     │  (SchoolPlan)    │     │  (JSON)         │
└─────────────────┘     └──────────────────┘     └─────────────────┘
        │                                                 │
        │                                                 ▼
        │                                        ┌─────────────────┐
        └───────────────────────────────────────▶│  StudyPlan.ics  │
                                                 └─────────────────┘
```

### Key Services

| Service | Purpose |
|---------|---------|
| `IcsParser` | Parses ICS files (RFC 5545), extracts Canvas assignments |
| `PlanEventsBuilder` | Correlates assignments with engine-generated work blocks |
| `EffortRedistributor` | Rebalances effort when blocks are edited/deleted |
| `IcsGenerator` | Generates downloadable ICS from preview state |

---

## Local Development

### Requirements

- PHP 8.3+
- Composer
- Node.js 20+
- Java 17+ (for Kotlin planner engine)

### Quick Setup

```bash
# Clone the repository
git clone https://github.com/YOUR_USERNAME/modulus-ui.git
cd modulus-ui

# Run full setup (installs deps, creates .env, generates key, runs migrations, builds frontend)
composer setup
```

### Manual Setup

```bash
# Install PHP dependencies
composer install

# Install Node dependencies
npm install

# Configure environment
cp .env.example .env
php artisan key:generate

# Create database and run migrations
touch database/database.sqlite
php artisan migrate

# Create storage symlink
php artisan storage:link

# Build frontend assets
npm run build
```

### Development Server

```bash
# Start all services (Laravel server, queue worker, Vite, log viewer)
composer dev
```

This starts:
- Laravel development server on `http://localhost:8000`
- Queue worker for background jobs
- Vite dev server for hot reloading
- Pail for real-time log viewing

### Kotlin Engine Setup

Place the SchoolPlan JAR file at `storage/app/engine/SchoolCalendarSync1-all.jar` and configure in `.env`:

```env
JAVA_BIN=/path/to/java
SCHOOLPLAN_JAR_PATH=/path/to/project/storage/app/engine/SchoolCalendarSync1-all.jar
```

---

## Configuration

### Required Environment Variables

```env
APP_NAME=Modulus
APP_ENV=local
APP_URL=http://localhost

# Database (SQLite is default)
DB_CONNECTION=sqlite

# Queue (database driver for local dev)
QUEUE_CONNECTION=database

# Kotlin Engine
JAVA_BIN=/usr/bin/java
SCHOOLPLAN_JAR_PATH=/path/to/SchoolCalendarSync1-all.jar
```

### Stripe Configuration (Optional)

For billing features, configure Stripe keys:

```env
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
STRIPE_PRO_PRICE_ID=price_...
```

Get keys from the [Stripe Dashboard](https://dashboard.stripe.com/apikeys).

---

## Testing

```bash
# Run all tests
composer test

# Or directly with artisan
php artisan test

# Run specific test class
php artisan test --filter=IcsParserTest

# Run specific test file
php artisan test tests/Unit/IcsParserTest.php
```

---

## Code Formatting

```bash
# Format code with Laravel Pint (PSR-12)
./vendor/bin/pint
```

---

## Deployment

See [DEPLOYMENT.md](DEPLOYMENT.md) for complete deployment instructions.

### Quick Overview

The guide covers deploying to **AWS EC2 Free Tier**:
- Ubuntu 24.04 LTS on t2.micro instance
- Nginx + PHP-FPM
- Supervisor for queue worker and file server
- Memory optimization for 1GB RAM constraint
- Optional domain and SSL setup

**Estimated cost:** Free for 12 months (AWS Free Tier), then ~$11/month

---

## Project Structure

```
├── app/
│   ├── Http/Controllers/
│   │   ├── PlanController.php      # Main planning flow
│   │   ├── BillingController.php   # Stripe integration
│   │   └── PageController.php      # Public pages
│   └── Services/
│       ├── IcsParser.php           # ICS file parsing
│       ├── PlanEventsBuilder.php   # Preview state builder
│       ├── EffortRedistributor.php # Block rebalancing
│       └── IcsGenerator.php        # ICS output generation
├── config/
│   └── schoolplan.php              # Engine configuration
├── resources/
│   ├── js/preview/                 # Preview UI (calendar, drag/drop)
│   └── views/                      # Blade templates
├── routes/
│   └── web.php                     # Application routes
├── storage/app/
│   ├── engine/                     # Kotlin JAR location
│   └── private/                    # User uploads
└── tests/
    ├── Feature/                    # Feature tests
    └── Unit/                       # Unit tests
```

---

## Routes

| Route | Method | Description |
|-------|--------|-------------|
| `/` | GET | Landing page (guests) / Dashboard redirect (auth) |
| `/help` | GET | User documentation |
| `/pricing` | GET | Subscription pricing |
| `/terms` | GET | Terms of Service |
| `/privacy` | GET | Privacy Policy |
| `/contact` | GET | Contact page |
| `/plan/import` | GET/POST | Import Canvas calendar |
| `/plan/generate` | GET | Generate study plan |
| `/plan/preview` | GET | Interactive plan preview |
| `/plan/download` | GET | Download final ICS |
| `/billing/checkout` | POST | Start Stripe checkout |
| `/billing/portal` | GET | Stripe customer portal |

---

## License

Proprietary. All rights reserved.
