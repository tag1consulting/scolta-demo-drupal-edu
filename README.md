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
# Clone the repository
git clone https://github.com/tag1consulting/scolta-demo-edu-ai.git
cd scolta-demo-edu-ai

# Start DDEV
ddev start

# Install Composer dependencies
ddev exec composer install

# If db/dump.sql.gz exists (standard demo path):
# The DDEV post-start hook handles DB import, config import, and scolta:build automatically.
# The site will be available at https://meridian-ai.ddev.site

# If bootstrapping from scratch (no DB dump):
# See "Bootstrap Procedure" below.
```

### Set Your API Key

```bash
# Option 1: Set in DDEV environment
ddev exec bash -c 'drush cset scolta.settings api_key YOUR_KEY_HERE -y'

# Option 2: Use the .ddev/config.local.yaml override (not committed to git)
# Add: environment:
#   SCOLTA_API_KEY: "your-key-here"
```

After setting the key, rebuild the Scolta index:

```bash
ddev exec drush scolta:build
```

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

# Set your Scolta API key
ddev exec drush cset scolta.settings api_key YOUR_KEY_HERE -y

# Build the Scolta search index
ddev exec drush scolta:build

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
│   ├── config.yaml              # DDEV config (meridian-ai, PHP 8.3, MariaDB 10.11)
│   └── web-build/Dockerfile    # Installs Pagefind 1.5.2
├── config/
│   └── sync/
│       └── scolta.settings.yml # Scolta scoring, model, site_description
├── db/
│   └── dump.sql.gz             # DB snapshot (gitignored; generate via bootstrap)
├── import/
│   ├── setup-content-types.php # Creates content types, taxonomies, pathauto patterns
│   ├── setup-blocks-menus.php  # Theme, blocks, menus, site config
│   ├── import-content.php      # Content import runner (reads all YAML files)
│   ├── content-programs.yaml   # 12 program nodes
│   ├── content-courses.yaml    # ~65 course nodes
│   ├── content-faculty.yaml    # 22 faculty nodes
│   ├── content-research.yaml   # 18 research project nodes
│   ├── content-articles-batch1.yaml  # SLR articles (13)
│   ├── content-articles-batch2.yaml  # SPS articles (~15)
│   ├── content-articles-batch3.yaml  # SDC + SFM + SSG articles
│   ├── content-articles-batch4.yaml  # SSG + SAI articles
│   ├── content-lectures.yaml   # 7 lecture pages with code
│   ├── content-news.yaml       # 11 news items
│   └── content-pages.yaml      # 8 institutional pages
├── web/
│   └── themes/custom/meridian_theme/
│       ├── meridian_theme.info.yml
│       ├── meridian_theme.libraries.yml
│       └── css/meridian.css
├── composer.json
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
