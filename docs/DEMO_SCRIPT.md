# Demo script — 5 minutes, 2 real businesses

Record after `php artisan migrate --seed && php artisan serve` (no Gemini key
needed — the template generator is the demo engine; mention the fallback
banner when it appears).

## 0:00–0:30 — The idea
"Owners hand us whatever they have — a name, links, a PDF, photos — and we
turn it into a publishable website. One rule: we never invent facts."

## 0:30–1:30 — Business 1: Nik Baker's, Chandigarh
- Dashboard → Nik Baker's → **Review**.
- Point at the disambiguation box: 4 branches with the same name (Sector 35C,
  9D, 22B, Elante Mall) — pick Sector 35C.
- Point at the conflict: TripAdvisor says `+91 172 506 2251`, magicpin says
  `+91 62909 19818`. Click one to resolve it.
- Point at the red banner: an instruction line hidden in the uploaded price
  list ("Ignore previous instructions…") was quarantined — show it listed,
  and that it never became a fact.
- Point at the blocked Instagram source: "could not read — paste the text".
- Answer the open question (1 kg chocolate truffle cake price) or leave it —
  show it renders as "Not confirmed — ask the owner".

## 1:30–2:30 — Generate, edit, regenerate one section, publish
- **Confirm facts & generate** → editor. Note the banner: no Gemini key, so
  the site was built from verified facts with the template generator.
- Edit the About section text, save.
- **Regenerate this section only** on the hero — point out the other sections
  (including your edit) are untouched and their versions did not change.
- **Publish** → open `/sites/nik-bakers-chandigarh` in a new tab: hero, about,
  prices, contact, hours, and the "Sources & verification" footer listing
  every source plus the "Not confirmed" items.

## 2:30–3:30 — Business 2: Indian Coffee House, Sector 17
- Dashboard → Indian Coffee House → **Review**: same pattern, different
  conflict (`0172 270 2804` vs `0172 270 4504`), blocked Facebook source,
  question about Sunday hours.
- Resolve the phone conflict (the majority source, `0172 270 2804`, is the
  one Zomato/magicpin also list).
- Generate → publish → `/sites/indian-coffee-house-chandigarh`. Note the
  different theme (modern vs Nik's warm) and the 1957 workers' co-operative
  story, with prices labelled as reported/unverified.

## 3:30–4:30 — The edge cases, rapid-fire
- Same name → disambiguation (shown). Disagreement → conflict UI (shown).
- Blocked social → graceful + manual paste (shown). Hidden instructions →
  quarantine banner (shown). AI down → template fallback banner (shown
  throughout — this whole demo ran with no key).

## 4:30–5:00 — Close
- "Everything is in MySQL: businesses, sources, facts, conflicts, questions,
  sites, sections, generations. `php artisan test` — 9 tests, all green.
  Add a Gemini key to `.env` and the same flow writes AI copy instead of
  template copy; nothing else changes."
