<?php

/**
 * Configure blocks, navigation, site identity, and Scolta search placement.
 * Run with: ddev drush php:script import/setup-blocks-menus.php
 */

use Drupal\block\Entity\Block;
use Drupal\menu_link_content\Entity\MenuLinkContent;

// ============================================================
// SITE IDENTITY
// ============================================================
\Drupal::configFactory()->getEditable('system.site')
  ->set('name', 'Meridian AI')
  ->set('slogan', 'Meridian Institute of Artificial Intelligence')
  ->save();
echo "Updated site name\n";

// ============================================================
// ENABLE THEME
// ============================================================
\Drupal::service('theme_installer')->install(['meridian_theme']);
\Drupal::configFactory()->getEditable('system.theme')
  ->set('default', 'meridian_theme')
  ->save();
echo "Enabled and set meridian_theme as default\n";

// ============================================================
// BLOCK PLACEMENTS
// ============================================================
$theme = 'meridian_theme';

$blocks = [
  [
    'id'     => "{$theme}_branding",
    'plugin' => 'system_branding_block',
    'region' => 'header',
    'weight' => -10,
    'settings' => [
      'label'            => 'Site branding',
      'label_display'    => '0',
      'use_site_logo'    => FALSE,
      'use_site_name'    => TRUE,
      'use_site_slogan'  => FALSE,
    ],
  ],
  [
    'id'     => "{$theme}_main_navigation",
    'plugin' => 'system_menu_block:main',
    'region' => 'primary_menu',
    'weight' => 0,
    'settings' => [
      'label'            => 'Main navigation',
      'label_display'    => '0',
      'level'            => 1,
      'depth'            => 0,
      'expand_all_items' => FALSE,
    ],
  ],
  [
    'id'     => "{$theme}_scolta_search",
    'plugin' => 'scolta_search',
    'region' => 'content_above',
    'weight' => -20,
    'settings' => [
      'label'         => 'Scolta Search',
      'label_display' => '0',
      'provider'      => 'scolta',
    ],
  ],
  [
    'id'     => "{$theme}_breadcrumbs",
    'plugin' => 'system_breadcrumb_block',
    'region' => 'breadcrumb',
    'weight' => 0,
    'settings' => ['label' => 'Breadcrumbs', 'label_display' => '0'],
  ],
  [
    'id'     => "{$theme}_page_title",
    'plugin' => 'page_title_block',
    'region' => 'content',
    'weight' => -5,
    'settings' => ['label' => 'Page title', 'label_display' => '0'],
  ],
  [
    'id'     => "{$theme}_messages",
    'plugin' => 'system_messages_block',
    'region' => 'highlighted',
    'weight' => -20,
    'settings' => ['label' => 'Messages', 'label_display' => '0'],
  ],
  [
    'id'     => "{$theme}_content",
    'plugin' => 'system_main_block',
    'region' => 'content',
    'weight' => 0,
    'settings' => ['label' => 'Main page content', 'label_display' => '0'],
  ],
];

foreach ($blocks as $block_data) {
  $id = $block_data['id'];
  if (Block::load($id)) {
    echo "Block already exists: $id\n";
    continue;
  }
  Block::create([
    'id'         => $id,
    'plugin'     => $block_data['plugin'],
    'theme'      => $theme,
    'region'     => $block_data['region'],
    'weight'     => $block_data['weight'],
    'settings'   => $block_data['settings'],
    'visibility' => [],
  ])->save();
  echo "Created block: $id\n";
}

// ============================================================
// MAIN MENU ITEMS
// ============================================================
$menu_items = [
  ['title' => 'Programs', 'link' => '/programs', 'weight' => 1],
  ['title' => 'Courses',  'link' => '/courses',  'weight' => 2],
  ['title' => 'Faculty',  'link' => '/faculty',  'weight' => 3],
  ['title' => 'Research', 'link' => '/research', 'weight' => 4],
  ['title' => 'Resources','link' => '/resources','weight' => 5],
  ['title' => 'Learn',    'link' => '/learn',    'weight' => 6],
  ['title' => 'News',     'link' => '/news',     'weight' => 7],
  ['title' => 'About',    'link' => '/about',    'weight' => 8],
];

foreach ($menu_items as $item) {
  $existing = \Drupal::entityTypeManager()->getStorage('menu_link_content')
    ->loadByProperties(['title' => $item['title'], 'menu_name' => 'main']);
  if (empty($existing)) {
    MenuLinkContent::create([
      'title'     => $item['title'],
      'link'      => ['uri' => 'internal:' . $item['link']],
      'menu_name' => 'main',
      'weight'    => $item['weight'],
      'expanded'  => FALSE,
    ])->save();
    echo "Created menu link: {$item['title']}\n";
  }
}

// ============================================================
// CONFIGURE SCOLTA SETTINGS
// ============================================================
\Drupal::configFactory()->getEditable('scolta.settings')
  ->set('site_name', 'Meridian AI')
  ->set('site_description', 'Meridian Institute of Artificial Intelligence — 250+ pages of AI educational content including degree programs in LLM engineering, computer vision, reinforcement learning, AI ethics, mathematical foundations, and applied AI; individual course syllabi; faculty research profiles; resource articles covering transformers, diffusion models, RL, alignment, MLOps, and AI-powered search; lecture pages with code examples; and research project descriptions. Content ranges from introductory explainers to graduate-level technical material.')
  ->set('ai_provider', 'anthropic')
  ->set('ai_model', 'claude-sonnet-4-6')
  ->set('ai_expand_query', TRUE)
  ->set('ai_summarize', TRUE)
  ->set('ai_languages', ['en'])
  ->set('max_follow_ups', 3)
  ->set('scoring', [
    'title_match_boost'         => 2.5,
    'title_all_terms_multiplier'=> 1.4,
    'content_match_boost'       => 0.6,
    'recency_boost_max'         => 0.1,
    'recency_half_life_days'    => 1825,
    'recency_penalty_after_days'=> 18250,
    'recency_max_penalty'       => 0.05,
    'expand_primary_weight'     => 0.75,
    'language'                  => 'en',
    'custom_stop_words'         => [],
    'recency_strategy'          => 'exponential',
    'recency_curve'             => [],
  ])
  ->set('display', [
    'excerpt_length'      => 400,
    'results_per_page'    => 12,
    'max_pagefind_results'=> 60,
    'ai_summary_top_n'    => 10,
    'ai_summary_max_chars'=> 4000,
  ])
  ->set('cache_ttl', 2592000)
  ->set('indexer', 'auto')
  ->set('memory_budget', ['profile' => 'conservative', 'custom_bytes' => null, 'chunk_size' => null])
  ->set('pagefind', [
    'build_dir'   => 'private://scolta-build',
    'output_dir'  => 'public://scolta-pagefind',
    'binary'      => 'pagefind',
    'auto_rebuild'=> TRUE,
    'view_mode'   => 'search_index',
  ])
  ->save();
echo "Configured Scolta settings\n";

// ============================================================
// SCOLTA PERMISSIONS
// ============================================================
$role_storage = \Drupal::entityTypeManager()->getStorage('user_role');
foreach (['anonymous', 'authenticated'] as $rid) {
  $role = $role_storage->load($rid);
  if ($role && !$role->hasPermission('use scolta ai')) {
    $role->grantPermission('use scolta ai');
    $role->save();
    echo "Granted 'use scolta ai' to: $rid\n";
  }
}

echo "\nBlocks, navigation, and settings configured!\n";
echo "Run: ddev exec drush php:script import/fix-pathauto.php\n";
echo "Then: ddev exec drush scolta:build\n";
