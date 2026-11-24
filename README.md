# AI Builds My Website

A business owner shares what they have — a name, a city, some links, a PDF price
list, a few photos — and this Laravel app turns it into a ready-to-publish
website. It reads the files, fetches the links, searches the web, and uses AI
(Gemini, free tier) to write the site. The owner reviews every fact, resolves
disagreements, edits any section, and publishes to a public URL.

## The golden rule

**Never invent facts. If unsure, ask the user.**

- Every fact on a generated site carries its source (shown in a "Sources &
  verification" footer).
- A fact from one unconfirmed source stays `unverified` and renders as
  **"Not confirmed — ask the owner"**, never as a guess.
- When sources disagree, the owner picks the right value in the review step.
- When the AI is down, the site is still built — from verified facts only,
  with a plain template generator and a banner saying so.

## Demo (no API key needed)

The seeder creates two real Chandigarh businesses with verified-only facts:

1. **Nik Baker's** — bakery & cafe, Sector 35C (multiple branches, so the
   disambiguation step has 4 candidates; two sources give two different phone
   numbers, so there is an open conflict to resolve).
2. **Indian Coffee House** — historic South Indian cafe, Sector 17E (also has
   a phone conflict: `0172 270 2804` vs `0172 270 4504`).

Both already have published sites:

- http://localhost:8000/sites/nik-bakers-chandigarh
- http://localhost:8000/sites/indian-coffee-house-chandigarh

Demo flow (see `docs/DEMO_SCRIPT.md` for the 5-minute script):

1. Open `/` — dashboard with both businesses.
2. Open a business's **Review** page — see facts with confidence + source,
   the open phone conflict, the blocked Instagram/Facebook source, the
   quarantined instruction line in the Nik Baker's price list, and the open
   question for the owner.
3. Resolve the conflict, answer the question, confirm facts → **Generate**.
4. **Edit** a section, **regenerate one section only** (others stay untouched),
   **Publish** → public URL.

## Setup

Requirements: PHP 8.3, Composer, MySQL 8.0, `pdftotext` (optional, for PDFs).

```bash
mysql -uroot -e "CREATE DATABASE ai_builds_my_website CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
cp .env.example .env
php artisan key:generate
composer install
php artisan migrate --seed
php artisan serve   # http://localhost:8000
```

Optional AI (Gemini free tier — get a key at https://aistudio.google.com/app/apikey):

```bash
# in .env
GEMINI_API_KEY=your_key_here
GEMINI_MODEL=gemini-2.0-flash
```

With no key, everything still works: the template generator builds the site
from verified facts and the UI says so. No API keys are in the repo — `.env`
is gitignored and only `.env.example` (empty placeholders) is committed.

## Environment variables

| Var | Default | Meaning |
|---|---|---|
| `DB_CONNECTION` / `DB_HOST` / `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` | mysql / localhost / ai_builds_my_website / root / (empty) | MySQL connection. Tests override this with sqlite `:memory:` via `phpunit.xml`. |
| `GEMINI_API_KEY` | (empty) | Gemini key. Empty = template fallback. |
| `GEMINI_MODEL` | gemini-2.0-flash | Model used by `GeminiService`. |
| `GEMINI_TIMEOUT` | 30 | Seconds before the AI call gives up and falls back. |

## Routes

| Route | What it does |
|---|---|
| `GET /` | Dashboard — businesses + published sites |
| `GET/POST /businesses/create` | Wizard step 1: name, city, category, links, uploads |
| `GET/POST /businesses/{id}/ingest` | Read files, fetch links, web search, extract facts |
| `GET /businesses/{id}/review` | Facts, conflicts, questions, disambiguation, blocked sources |
| `POST /businesses/{id}/resolve-conflict` · `/answer-question` · `/pick-candidate` · `/confirm-facts` · `/manual-source` | Owner decisions |
| `GET/POST /businesses/{id}/generate` | Build the site (Gemini, else template) |
| `GET /sites/{site}/edit` | Edit every section |
| `POST /sites/{site}/sections/{section}` | Save one section |
| `POST /sites/{site}/sections/{section}/regenerate` | Regenerate ONE section only |
| `POST /sites/{site}/publish` | Publish |
| `GET /sites/{site}/preview` | Draft preview |
| `GET /sites/{slug}` | Public site (no login) |

## Tests

```bash
php artisan test
```

9 tests, all green (sqlite `:memory:`): conflict detection, injection
quarantine, blocked source invents nothing, AI-down fallback, single-section
regeneration leaves others untouched, publish makes the site public, and the
seeded pages load.

## Folder map

```
app/Models/        Business, Source, Fact, Conflict, Question, Site, SiteSection, Generation
app/Services/      FileIngestionService, InjectionGuard, WebFetchService (+WebSearchService),
                   FactExtractorService, GeminiService, TemplateSiteGenerator, SiteGeneratorService
app/Http/Controllers/  BusinessController, SiteController
config/ai.php      provider / model / timeout (key comes from .env only)
database/migrations/  one migration: businesses, sources, facts, conflicts,
                   questions, sites, site_sections, generations
database/seeders/  DatabaseSeeder — 2 real businesses, verified-only facts
storage/app/samples/  sample price lists (labelled SAMPLE, with source URLs)
resources/views/   Blade: dashboard, wizard, review, editor, public site (2 themes)
public/css/app.css plain CSS, no build step
tests/Feature/AiWebsiteTest.php
docs/DEMO_SCRIPT.md
DECISIONS.md       key choices, what was cut, what's next, edge-case table
```

## Limitations (honest list)

- No login/auth — anyone with the URL can edit. Add auth before multi-user use.
- No real Google Business API (needs OAuth) — the listing URL is fetched as a
  web page, which Google usually blocks; the owner then pastes the details.
- No OCR — photos are shown in the gallery, their text is not read.
- Web search is DuckDuckGo HTML (no key); if it blocks us, search degrades
  gracefully and the review page says so.
- Public URLs are `/sites/{slug}` on this host — no custom domains, no
  deployment to an external host yet.
- See `DECISIONS.md` for the full list of what was cut and what's next.
