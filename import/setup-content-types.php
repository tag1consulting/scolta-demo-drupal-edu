<?php

/**
 * Setup script: Creates content types, fields, and taxonomies for Meridian AI.
 * Run with: ddev drush php:script import/setup-content-types.php
 */

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\Entity\Term;

// ============================================================
// TAXONOMIES
// ============================================================

$vocabularies = [
  'school' => [
    'label' => 'School',
    'terms' => [
      'School of Language & Reasoning',
      'School of Perception & Synthesis',
      'School of Decision & Control',
      'School of Foundations & Mathematics',
      'School of Society & Governance',
      'School of Applied Intelligence',
    ],
  ],
  'degree_level' => [
    'label' => 'Degree Level',
    'terms' => ["Certificate", "Bachelor's", "Master's", "PhD", "Professional Development"],
  ],
  'course_level' => [
    'label' => 'Course Level',
    'terms' => [
      'Introductory (100-level)',
      'Intermediate (200-level)',
      'Advanced (300-level)',
      'Graduate (400-level)',
      'Seminar (500-level)',
    ],
  ],
  'topic' => [
    'label' => 'Topic',
    'terms' => [
      'Transformers',
      'Reinforcement Learning',
      'Computer Vision',
      'Natural Language Processing',
      'Ethics & Governance',
      'Robotics',
      'Generative AI',
      'Search & Information Retrieval',
      'MLOps & Infrastructure',
      'Mathematics & Foundations',
      'Multimodal AI',
      'Edge AI',
      'AI History',
      'Large Language Models',
      'AI Safety & Alignment',
      'AI Agents',
    ],
  ],
  'news_category' => [
    'label' => 'News Category',
    'terms' => ['Research', 'Campus', 'Admissions', 'Events', 'Industry Partnerships'],
  ],
  'difficulty' => [
    'label' => 'Difficulty',
    'terms' => ['Introductory', 'Intermediate', 'Advanced', 'Expert'],
  ],
];

foreach ($vocabularies as $machine_name => $info) {
  if (!Vocabulary::load($machine_name)) {
    Vocabulary::create([
      'vid' => $machine_name,
      'name' => $info['label'],
    ])->save();
    echo "Created vocabulary: {$info['label']}\n";
  } else {
    echo "Vocabulary already exists: {$info['label']}\n";
  }

  foreach ($info['terms'] as $term_name) {
    $existing = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties(['name' => $term_name, 'vid' => $machine_name]);
    if (empty($existing)) {
      Term::create([
        'vid' => $machine_name,
        'name' => $term_name,
        'langcode' => 'en',
      ])->save();
      echo "  Created term: $term_name\n";
    }
  }
}

// ============================================================
// HELPER FUNCTIONS
// ============================================================

function create_text_field(string $entity_type, string $bundle, string $field_name, string $label, string $type = 'string', bool $translatable = true, int $cardinality = 1): void {
  if (!FieldStorageConfig::loadByName($entity_type, $field_name)) {
    FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => $entity_type,
      'type' => $type,
      'cardinality' => $cardinality,
      'translatable' => $translatable,
    ])->save();
  }
  if (!FieldConfig::loadByName($entity_type, $bundle, $field_name)) {
    FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => $entity_type,
      'bundle' => $bundle,
      'label' => $label,
      'translatable' => $translatable,
    ])->save();
    echo "  Field created: $field_name on $bundle\n";
  }
}

function create_taxonomy_field(string $entity_type, string $bundle, string $field_name, string $label, string $vocabulary, int $cardinality = 1): void {
  if (!FieldStorageConfig::loadByName($entity_type, $field_name)) {
    FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => $entity_type,
      'type' => 'entity_reference',
      'cardinality' => $cardinality,
      'settings' => ['target_type' => 'taxonomy_term'],
      'translatable' => FALSE,
    ])->save();
  }
  if (!FieldConfig::loadByName($entity_type, $bundle, $field_name)) {
    FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => $entity_type,
      'bundle' => $bundle,
      'label' => $label,
      'translatable' => FALSE,
      'settings' => [
        'handler' => 'default:taxonomy_term',
        'handler_settings' => [
          'target_bundles' => [$vocabulary => $vocabulary],
          'auto_create' => FALSE,
        ],
      ],
    ])->save();
    echo "  Field created: $field_name on $bundle\n";
  }
}

function create_node_ref_field(string $entity_type, string $bundle, string $field_name, string $label, array $target_bundles, int $cardinality = -1): void {
  if (!FieldStorageConfig::loadByName($entity_type, $field_name)) {
    FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => $entity_type,
      'type' => 'entity_reference',
      'cardinality' => $cardinality,
      'settings' => ['target_type' => 'node'],
      'translatable' => FALSE,
    ])->save();
  }
  if (!FieldConfig::loadByName($entity_type, $bundle, $field_name)) {
    FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => $entity_type,
      'bundle' => $bundle,
      'label' => $label,
      'translatable' => FALSE,
      'settings' => [
        'handler' => 'default:node',
        'handler_settings' => [
          'target_bundles' => array_combine($target_bundles, $target_bundles),
          'auto_create' => FALSE,
        ],
      ],
    ])->save();
    echo "  Field created: $field_name on $bundle\n";
  }
}

// ============================================================
// CONTENT TYPE: program
// ============================================================
if (!NodeType::load('program')) {
  NodeType::create([
    'type' => 'program',
    'name' => 'Program',
    'description' => 'A degree program or certificate offered by Meridian AI.',
  ])->save();
  echo "Created content type: program\n";
}

create_taxonomy_field('node', 'program', 'field_school', 'School', 'school');
create_taxonomy_field('node', 'program', 'field_degree_level', 'Degree Level', 'degree_level');
create_text_field('node', 'program', 'field_duration', 'Duration', 'string', FALSE);
create_text_field('node', 'program', 'field_format', 'Format', 'string', FALSE);
create_text_field('node', 'program', 'field_prerequisites', 'Prerequisites', 'text_long', TRUE);
create_text_field('node', 'program', 'field_credits', 'Total Credits', 'string', FALSE);

// ============================================================
// CONTENT TYPE: course
// ============================================================
if (!NodeType::load('course')) {
  NodeType::create([
    'type' => 'course',
    'name' => 'Course',
    'description' => 'An individual course within a program.',
  ])->save();
  echo "Created content type: course\n";
}

create_taxonomy_field('node', 'course', 'field_school', 'School', 'school');
create_taxonomy_field('node', 'course', 'field_course_level', 'Course Level', 'course_level');
create_taxonomy_field('node', 'course', 'field_topic', 'Topic', 'topic', -1);
create_text_field('node', 'course', 'field_credits', 'Credits', 'string', FALSE);
create_text_field('node', 'course', 'field_course_number', 'Course Number', 'string', FALSE);
create_text_field('node', 'course', 'field_semester', 'Offered', 'string', FALSE);
create_text_field('node', 'course', 'field_syllabus_summary', 'Syllabus Summary', 'text_long', TRUE);
create_node_ref_field('node', 'course', 'field_program', 'Program', ['program'], 1);

// ============================================================
// CONTENT TYPE: faculty
// ============================================================
if (!NodeType::load('faculty')) {
  NodeType::create([
    'type' => 'faculty',
    'name' => 'Faculty',
    'description' => 'A professor or researcher at Meridian AI.',
  ])->save();
  echo "Created content type: faculty\n";
}

create_taxonomy_field('node', 'faculty', 'field_school', 'School', 'school');
create_text_field('node', 'faculty', 'field_title_role', 'Title / Role', 'string', FALSE);
create_text_field('node', 'faculty', 'field_specialization', 'Specialization', 'string', FALSE);
create_text_field('node', 'faculty', 'field_publications', 'Selected Publications', 'text_long', TRUE);
create_text_field('node', 'faculty', 'field_bio', 'Short Bio', 'text_long', TRUE);

// ============================================================
// CONTENT TYPE: research_project
// ============================================================
if (!NodeType::load('research_project')) {
  NodeType::create([
    'type' => 'research_project',
    'name' => 'Research Project',
    'description' => 'An ongoing or completed research project at Meridian AI.',
  ])->save();
  echo "Created content type: research_project\n";
}

create_taxonomy_field('node', 'research_project', 'field_school', 'School', 'school');
create_node_ref_field('node', 'research_project', 'field_principal_investigator', 'Principal Investigator', ['faculty'], 1);
create_text_field('node', 'research_project', 'field_status', 'Status', 'string', FALSE);
create_text_field('node', 'research_project', 'field_funding', 'Funding', 'string', FALSE);

// ============================================================
// CONTENT TYPE: resource_article
// ============================================================
if (!NodeType::load('resource_article')) {
  NodeType::create([
    'type' => 'resource_article',
    'name' => 'Resource Article',
    'description' => 'Educational article, tutorial, or explainer.',
  ])->save();
  echo "Created content type: resource_article\n";
}

create_taxonomy_field('node', 'resource_article', 'field_topic', 'Topic', 'topic', -1);
create_taxonomy_field('node', 'resource_article', 'field_difficulty', 'Difficulty', 'difficulty');
create_taxonomy_field('node', 'resource_article', 'field_school', 'School', 'school');
create_node_ref_field('node', 'resource_article', 'field_related_program', 'Related Program', ['program'], 1);
create_node_ref_field('node', 'resource_article', 'field_related_articles', 'Related Articles', ['resource_article'], -1);

// ============================================================
// CONTENT TYPE: lecture_page
// ============================================================
if (!NodeType::load('lecture_page')) {
  NodeType::create([
    'type' => 'lecture_page',
    'name' => 'Lecture Page',
    'description' => 'An online lecture or courseware page.',
  ])->save();
  echo "Created content type: lecture_page\n";
}

create_taxonomy_field('node', 'lecture_page', 'field_topic', 'Topic', 'topic', -1);
create_taxonomy_field('node', 'lecture_page', 'field_difficulty', 'Difficulty', 'difficulty');
create_taxonomy_field('node', 'lecture_page', 'field_school', 'School', 'school');
create_text_field('node', 'lecture_page', 'field_course_number', 'Course Number', 'string', FALSE);
create_text_field('node', 'lecture_page', 'field_estimated_time', 'Estimated Time', 'string', FALSE);

// ============================================================
// CONTENT TYPE: news
// ============================================================
if (!NodeType::load('news')) {
  NodeType::create([
    'type' => 'news',
    'name' => 'News',
    'description' => 'Campus announcements, events, and press.',
  ])->save();
  echo "Created content type: news\n";
}

create_taxonomy_field('node', 'news', 'field_news_category', 'Category', 'news_category');
create_text_field('node', 'news', 'field_news_date', 'Date', 'string', FALSE);
create_taxonomy_field('node', 'news', 'field_school', 'School', 'school');

// ============================================================
// CONTENT TYPE: page (already exists in standard profile)
// ============================================================
// The 'page' content type from the standard profile is fine as-is.

// ============================================================
// PATHAUTO PATTERNS
// ============================================================
\Drupal::service('pathauto.generator');

$patterns = [
  'program' => [
    'type' => 'pathauto_pattern',
    'id' => 'program_pattern',
    'label' => 'Program pattern',
    'pattern' => 'programs/[node:title]',
    'selection_criteria' => ['entity_bundle:node' => ['id' => 'entity_bundle:node', 'negate' => FALSE, 'bundles' => ['program']]],
  ],
  'course' => [
    'type' => 'pathauto_pattern',
    'id' => 'course_pattern',
    'label' => 'Course pattern',
    'pattern' => 'courses/[node:title]',
    'selection_criteria' => ['entity_bundle:node' => ['id' => 'entity_bundle:node', 'negate' => FALSE, 'bundles' => ['course']]],
  ],
  'faculty' => [
    'type' => 'pathauto_pattern',
    'id' => 'faculty_pattern',
    'label' => 'Faculty pattern',
    'pattern' => 'faculty/[node:title]',
    'selection_criteria' => ['entity_bundle:node' => ['id' => 'entity_bundle:node', 'negate' => FALSE, 'bundles' => ['faculty']]],
  ],
  'research_project' => [
    'type' => 'pathauto_pattern',
    'id' => 'research_project_pattern',
    'label' => 'Research Project pattern',
    'pattern' => 'research/[node:title]',
    'selection_criteria' => ['entity_bundle:node' => ['id' => 'entity_bundle:node', 'negate' => FALSE, 'bundles' => ['research_project']]],
  ],
  'resource_article' => [
    'type' => 'pathauto_pattern',
    'id' => 'resource_article_pattern',
    'label' => 'Resource Article pattern',
    'pattern' => 'resources/[node:title]',
    'selection_criteria' => ['entity_bundle:node' => ['id' => 'entity_bundle:node', 'negate' => FALSE, 'bundles' => ['resource_article']]],
  ],
  'lecture_page' => [
    'type' => 'pathauto_pattern',
    'id' => 'lecture_page_pattern',
    'label' => 'Lecture Page pattern',
    'pattern' => 'learn/[node:title]',
    'selection_criteria' => ['entity_bundle:node' => ['id' => 'entity_bundle:node', 'negate' => FALSE, 'bundles' => ['lecture_page']]],
  ],
  'news' => [
    'type' => 'pathauto_pattern',
    'id' => 'news_pattern',
    'label' => 'News pattern',
    'pattern' => 'news/[node:title]',
    'selection_criteria' => ['entity_bundle:node' => ['id' => 'entity_bundle:node', 'negate' => FALSE, 'bundles' => ['news']]],
  ],
];

$pathauto_storage = \Drupal::entityTypeManager()->getStorage('pathauto_pattern');
foreach ($patterns as $key => $pattern_data) {
  if (!$pathauto_storage->load($pattern_data['id'])) {
    $pathauto_storage->create($pattern_data)->save();
    echo "Created pathauto pattern: {$pattern_data['id']}\n";
  }
}

echo "\nContent types, taxonomies, and pathauto patterns setup complete!\n";
