<?php
/**
 * Set up LMS infrastructure for the Feste course.
 *
 * - Creates anonymous + outsider group roles with take-course permission
 * - Creates reading_content (no_answer + body field) activity type
 * - Creates quiz_mc (select_single_feedback) activity type
 */

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

// ── 1. Group roles ────────────────────────────────────────────────────────────

$role_storage = \Drupal::entityTypeManager()->getStorage('group_role');

// Anonymous outsider: can view course pages and take the course.
if (!$role_storage->load('lms_course-anonymous')) {
  $role_storage->create([
    'id'          => 'lms_course-anonymous',
    'label'       => 'Anonymous',
    'weight'      => -5,
    'admin'       => FALSE,
    'scope'       => 'outsider',
    'global_role' => 'anonymous',
    'group_type'  => 'lms_course',
    'permissions' => ['view group', 'take course'],
  ])->save();
  echo "Created group role: lms_course-anonymous\n";
} else {
  // Update existing role.
  $role = $role_storage->load('lms_course-anonymous');
  $role->set('permissions', ['view group', 'take course']);
  $role->save();
  echo "Updated group role: lms_course-anonymous\n";
}

// Outsider (authenticated, non-member): view + take + join.
if (!$role_storage->load('lms_course-outsider')) {
  $role_storage->create([
    'id'          => 'lms_course-outsider',
    'label'       => 'Outsider',
    'weight'      => -6,
    'admin'       => FALSE,
    'scope'       => 'outsider',
    'global_role' => 'authenticated',
    'group_type'  => 'lms_course',
    'permissions' => ['view group', 'take course', 'join group'],
  ])->save();
  echo "Created group role: lms_course-outsider\n";
} else {
  $role = $role_storage->load('lms_course-outsider');
  $perms = $role->getPermissions();
  if (!in_array('take course', $perms)) {
    $perms[] = 'take course';
    $role->set('permissions', $perms);
    $role->save();
  }
  echo "Updated group role: lms_course-outsider\n";
}

echo "\n";

// ── 2. Activity types ─────────────────────────────────────────────────────────

$at_storage  = \Drupal::entityTypeManager()->getStorage('lms_activity_type');
$plugin_mgr  = \Drupal::service('plugin.manager.activity_answer');
$cfg_install = \Drupal::service('plugin.config_installer.activity_answer');
$definitions = $plugin_mgr->getDefinitions();

// reading_content — no_answer plugin, will receive a body field.
if (!$at_storage->load('reading_content')) {
  $at = $at_storage->create([
    'id'                  => 'reading_content',
    'name'                => 'Reading Content',
    'description'         => 'Display-only lesson reading material — no student response required.',
    'pluginId'            => 'no_answer',
    'pluginConfiguration' => [],
    'defaultMaxScore'     => 0,
  ]);
  $at->save();
  echo "Created activity type: reading_content\n";
} else {
  echo "Activity type already exists: reading_content\n";
}

// quiz_mc — select_feedback plugin with single selection (radio buttons).
if (!$at_storage->load('quiz_mc')) {
  $at = $at_storage->create([
    'id'                  => 'quiz_mc',
    'name'                => 'Quiz — Multiple Choice',
    'description'         => 'Single-select multiple-choice question with immediate correct-answer feedback.',
    'pluginId'            => 'select_feedback',
    'pluginConfiguration' => ['selection_type' => 'single'],
    'defaultMaxScore'     => 5,
  ]);
  $at->save();
  // Install per-bundle plugin config (field instances, display).
  if (isset($definitions['select_feedback'])) {
    $cfg_install->install($definitions['select_feedback'], 'quiz_mc');
  }
  echo "Created activity type: quiz_mc\n";
} else {
  echo "Activity type already exists: quiz_mc\n";
}

echo "\n";

// ── 3. Body field on reading_content activity type ────────────────────────────

$field_name = 'body';
$entity_type = 'lms_activity';
$bundle = 'reading_content';

// Field storage.
if (!FieldStorageConfig::loadByName($entity_type, $field_name)) {
  FieldStorageConfig::create([
    'field_name'  => $field_name,
    'entity_type' => $entity_type,
    'type'        => 'text_long',
    'cardinality' => 1,
    'settings'    => [],
  ])->save();
  echo "Created field storage: $entity_type.$field_name\n";
} else {
  echo "Field storage exists: $entity_type.$field_name\n";
}

// Field instance on this bundle.
if (!FieldConfig::loadByName($entity_type, $bundle, $field_name)) {
  FieldConfig::create([
    'field_name'  => $field_name,
    'entity_type' => $entity_type,
    'bundle'      => $bundle,
    'label'       => 'Lesson Content',
    'required'    => FALSE,
    'settings'    => ['allowed_formats' => ['full_html']],
  ])->save();
  echo "Created field config: $entity_type.$bundle.$field_name\n";
} else {
  echo "Field config exists: $entity_type.$bundle.$field_name\n";
}

// Configure view display — show body prominently, hide name label.
$display_repository = \Drupal::service('entity_display.repository');

$view_display = $display_repository->getViewDisplay($entity_type, $bundle, 'default');
$view_display->setComponent('body', [
  'type'     => 'text_default',
  'label'    => 'hidden',
  'weight'   => 0,
  'settings' => [],
]);
$view_display->save();
echo "Updated view display for reading_content\n";

echo "\nLMS setup complete.\n";
