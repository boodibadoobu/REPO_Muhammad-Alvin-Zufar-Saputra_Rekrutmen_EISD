# Project Context — EISD Recruitment

## Identity

- Repository name: `REPO_Muhammad Alvin Zufar Saputra_Rekrutmen_EISD`
- Product name: **LaporKita** (working name; may be changed without changing scope)
- Theme: **Platform Pelaporan Permukiman Kumuh & Infrastruktur Rusak**
- SDG alignment: SDG 11 — Sustainable Cities and Communities, especially safer,
  inclusive, resilient, and sustainable settlements.

## Goal

Build a simple, responsive, and functional Laravel website where residents can
report slum-settlement conditions or damaged public infrastructure, officers can
verify and process reports, and administrators can manage master data and users.
Verified reports are also published through a privacy-aware public dashboard so
the community can follow handling progress and see proof when a ticket is closed.

## Fixed Technical Constraints

1. Laravel only for the application framework, following Route -> Controller ->
   Model -> View using Blade.
2. Do not use automatic admin/CRUD packages such as Filament.
3. PostgreSQL on Supabase is the production database. Laravel owns registration,
   login, password hashing, sessions, and role authorization; Supabase is not the
   authentication provider.
4. Local automated tests use an isolated SQLite database and must never connect
   to Supabase.
5. Server-side validation, CSRF protection, authorization, escaped Blade output,
   and visible success/error flash messages are mandatory.
6. Database migrations and UML/class documentation must remain 100% aligned.
7. Leaflet with OpenStreetMap tiles is the map layer. New reports require
   latitude and longitude selected from an interactive map; PostGIS is not
   required for the current radius-based duplicate check.

## Roles and Access

### Warga

- Public registration always creates a `warga` account; role cannot be selected.
- Login/logout and view personal dashboard.
- Create reports with title, description, address/location text, photo, and one
  or more issue categories.
- Select a precise map point by GPS, map click, or draggable marker.
- Pass a native Laravel session CAPTCHA. Potential duplicates require explicit
  confirmation before submission.
- View and track only their own reports.
- Edit or delete their own report only while its status is `diajukan`.

### Petugas

- Created by an administrator or database seeder, never public registration.
- View all submitted reports.
- Verify a report (`diverifikasi`) or reject it (`ditolak`) with an officer note.
- Advance verified reports to `diproses`, then `selesai`.
- Upload a resolution photo when advancing a report to `selesai`.
- Cannot manage users or issue categories.

### Admin

- Full report visibility and the same processing abilities as an officer.
- Manage users and assign `warga`, `petugas`, or `admin` roles.
- Manage issue categories.
- View system-wide dashboard statistics.

### Public visitor

- View, search, and filter reports with status `diverifikasi`, `diproses`, or
  `selesai` without logging in.
- View report details, map location, progress timeline, officer note, and
  resolution evidence without seeing the reporter's identity or account data.

## Main Workflow

`diajukan -> diverifikasi -> diproses -> selesai`

Alternative terminal path: `diajukan -> ditolak`.

Only an administrator or officer may change status. Invalid status transitions
must be rejected server-side. Status changes record the processing officer and an
optional/required note as appropriate.
Moving from `diproses` to `selesai` requires an uploaded resolution photo.

## Database Design

Core tables:

- `users`: id, name, email, password, role, timestamps.
- `reports`: id, nullable unique `demo_key` for repeatable demo seeding,
  reporter `user_id`, nullable `officer_id`, title, description,
  address, nullable latitude/longitude for legacy compatibility, report photo
  path, nullable resolution photo path, status, officer note,
  verified/processed/resolved/rejected timestamps, timestamps.
- `categories`: id, name, slug, description, timestamps.
- `category_report` pivot: category id, report id, timestamps, unique pair.

Required relationships:

- One-to-Many: `users (reporter) -> reports`.
- One-to-Many: `users (officer) -> handled reports`.
- Many-to-Many: `reports <-> categories` through `category_report`.

Laravel framework tables for sessions, cache, and jobs may exist in addition to
the domain tables and must also be represented in the database documentation.

## Required Deliverables

- Complete responsive Laravel/Blade application.
- Login, registration, logout, role middleware/policies, and role-specific UI.
- CRUD and workflow features listed above.
- Secure image upload and public storage setup instructions.
- Interactive GPS/map picker and public report map/detail pages.
- Native session CAPTCHA, report submission rate limit, and duplicate warning
  for active reports sharing a category within 150 meters during the last 30
  days.
- Seeders with categories, sample reports, and demo accounts for every role.
- Feature/model tests covering authentication, validation, authorization,
  relationships, uploads, and valid/invalid status transitions.
- Use Case, Class, Activity, and Sequence diagrams (Mermaid sources plus rendered
  documentation where practical).
- README with local setup, Supabase connection configuration, demo credentials,
  testing, storage linking, and deployment notes.

## Definition of Done

The project is complete only when installation and migrations work, every role
can perform only its authorized actions, the full report workflow is functional,
validation and flash messages are visible, uploads are validated, tests pass,
migrations match the diagrams, and the documentation is sufficient for an EISD
reviewer to run and assess the application.
Public completion evidence, reporter privacy, CAPTCHA, duplicate detection, and
map behavior are part of these completion criteria.
