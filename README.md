# Meridian AI — Scolta EDU Demo

A Drupal 11 demonstration site for the [Scolta](https://github.com/tag1consulting/scolta-drupal) AI-powered search platform, built to showcase Scolta's capabilities for educational institution (EDU) prospects.

**Meridian AI is a fictional institution created by Tag1 Consulting to demonstrate Scolta.**

---

## Quick Start

### Requirements

- [DDEV](https://ddev.readthedocs.io/) v1.23+
- Docker Desktop (or equivalent container runtime)
- An Anthropic API key (for AI overviews and query expansion)

### Setup

```bash
git clone https://github.com/tag1consulting/scolta-demo-edu-ai.git
cd scolta-demo-edu-ai
```

Create `.ddev/config.local.yaml` with your Anthropic API key (this file is gitignored and never committed):

```yaml
web_environment:
  - SCOLTA_API_KEY=sk-ant-...your-key-here...
```

Then start DDEV:

```bash
ddev start
```

The post-start hook handles everything automatically: `composer install`, database import, config sync, cache rebuild, and search index build. The site will be available at https://drupal-edu.ddev.site once the hook completes (typically 2–3 minutes on first start while the container image builds).

Without an API key the site loads and search works, but AI overviews and query expansion are silently disabled.

---

## Bootstrap Procedure

Use this procedure to generate `db/dump.sql.gz` from scratch on a fresh Drupal install.

```bash
ddev start
ddev exec composer install

# Install Drupal
ddev exec drush site:install --existing-config -y \
  --account-name=admin --account-pass=admin \
  --site-name="Meridian AI"

# Run content type and taxonomy setup
ddev exec drush php:script import/setup-content-types.php

# Import all content (runs all content files in order)
ddev exec drush php:script import/import-content.php

# Configure blocks, menus, and theme
ddev exec drush php:script import/setup-blocks-menus.php

# Set your Scolta API key (or use .ddev/config.local.yaml — see Quick Start)
# ddev exec drush cset scolta.settings api_key YOUR_KEY_HERE -y

# Build the Scolta search index (nodes + LMS course in one pass)
ddev exec drush ai-uni:build-search

# Export config
ddev exec drush config:export -y

# Dump the database
mkdir -p db
ddev exec bash -c 'mysqldump -u db -pdb db | gzip > /var/www/html/db/dump.sql.gz'
```

After bootstrap, the DDEV post-start hook will automatically restore the site on subsequent `ddev start` runs.

---

## Content Summary

| Type | Count |
|------|-------|
| Programs | 12 (MS and Certificate, one per school) |
| Courses | ~65 (across 6 schools) |
| Faculty | 22 profiles |
| Research Projects | 18 (active, recruiting, completed) |
| Resource Articles | ~55 (in 4 batches) |
| Lecture Pages | 7 (with working code examples) |
| News Items | 11 |
| Institutional Pages | 8 |
| **Total** | **~200 nodes** |
| **LMS Course** | 1 interactive course — 15 lessons, 63 quizzes (Group entity, separate from nodes) |

### The Six Schools

| Code | Name | Focus |
|------|------|-------|
| SLR | School of Language and Reasoning | LLMs, agents, alignment, RLHF/DPO |
| SPS | School of Perception and Synthesis | Computer vision, diffusion models, multimodal AI |
| SDC | School of Decision and Control | Reinforcement learning, robotics, world models |
| SFM | School of Foundations and Mathematics | Optimization, learning theory, information theory |
| SSG | School of Societal and Governance AI | Ethics, fairness, safety, governance, policy |
| SAI | School of Applied AI and Infrastructure | MLOps, search, IR, AI engineering, deployment |

---

## Repository Structure

```
ai-uni/
├── .ddev/
│   ├── config.yaml              # DDEV config (drupal-edu, PHP 8.4, MariaDB 10.11)
│   ├── config.local.yaml        # Gitignored — put SCOLTA_API_KEY here
│   └── web-build/Dockerfile     # Installs Pagefind 1.5.2 into the container
├── config/
│   └── sync/                    # Drupal config export (all settings, blocks, views)
├── db/
│   └── dump.sql.gz              # DB snapshot — committed; restored by post-start hook
├── import/
│   ├── setup-content-types.php  # Creates content types, taxonomies, pathauto patterns
│   ├── setup-blocks-menus.php   # Theme, blocks, menus, site config
│   ├── import-content.php       # Content import runner
│   ├── lms-create-course.php    # Creates the LMS group, lessons, and quiz activities
│   ├── lms-lesson-data-*.php    # Lesson content and quiz data (lessons 1–15)
│   ├── lms-update-feedback.php  # Populates quiz feedback_if_correct/wrong fields
│   ├── lms-shuffle-answers.php  # Shuffles answer order so correct is not always first
│   └── content-*.yaml           # Node content (programs, courses, faculty, etc.)
├── web/
│   ├── modules/custom/ai_uni_lms/
│   │   ├── ai_uni_lms.module    # hook_lms_course_link (anonymous access) + theme hook
│   │   ├── ai_uni_lms.services.yml
│   │   ├── drush.services.yml
│   │   ├── src/Commands/        # ai-uni:build-search (nodes + LMS in one Pagefind pass)
│   │   ├── src/Plugin/Block/    # LmsCourseListBlock — course card on /learn
│   │   ├── src/Service/         # ScoltaContentGathererDecorator (non-node entity support)
│   │   └── templates/           # Twig template for the course list block
│   └── themes/custom/meridian_theme/
│       ├── meridian_theme.info.yml
│       ├── meridian_theme.libraries.yml
│       └── css/meridian.css
├── composer.json
├── composer.lock
├── README.md
├── SOURCES.md
└── SHOWCASE_QUERIES.md
```

---

## Theme

`meridian_theme` is a sub-theme of Olivero using a navy/amber color palette:

- **Navy** `#1a2744` — primary brand color (header, nav, buttons)
- **Amber** `#d4a843` — accent color (links, badges, borders)
- **Slate** `#475569` — body text

The theme uses Inter (sans-serif) and Source Serif 4 (headings) from Google Fonts.

---

## Scolta Configuration

See `config/sync/scolta.settings.yml` for the full configuration. Key settings:

- `ai_model: claude-sonnet-4-6`
- `title_match_boost: 2.5`
- `expand_primary_weight: 0.75`
- Rich `site_description` covering all 6 schools and content types for query expansion context

---

## Showcase Queries

See `SHOWCASE_QUERIES.md` for 30+ example queries with expected behavior, organized by complexity and content type.

---

## About Meridian AI

Meridian AI is a fictional institution. The impossible geography (coastal redwoods meeting desert mesa, overlooking a freshwater sea with lake-effect snow) is a deliberate signal that this is not a real university. See `/about/demo` on the running site for full disclosure.

Every page of the site includes a footer disclaimer: *"Meridian AI is a fictional institution created by Tag1 Consulting to demonstrate Scolta."*

All technical content on the site is written to be accurate and educationally useful. The code examples in lecture pages are functional.

---

## License

Demo content and configuration: MIT License.
Scolta packages: see individual package licenses.
