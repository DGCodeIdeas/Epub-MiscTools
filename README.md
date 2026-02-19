# GlyphShifter (GSP)

A modular, API-driven Progressive Web Application (PWA) for miscellaneous EPUB file manipulation.

## Architecture
- **Framework:** Spine (Custom Micro-Framework)
- **Pattern:** MVC + Service Layer
- **API:** Stateless RESTful API

## Setup
1. `composer install`
2. `cp .env.example .env` (and configure)
3. `php -S localhost:8080 -t public`
