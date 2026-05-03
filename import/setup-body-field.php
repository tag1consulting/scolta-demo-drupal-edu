<?php
/**
 * Adds the body field to all custom content types and re-imports body data.
 */
use Drupal\field\Entity\FieldConfig;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\Entity\EntityFormDisplay;

$types_needing_body = [
  'program'          => 'Program Description',
  'course'           => 'Course Description',
  'faculty'          => 'Biography',
  'research_project' => 'Project Description',
  'resource_article' => 'Article Body',
  'lecture_page'     => 'Lecture Content',
  'news'             => 'News Body',
];

foreach ($types_needing_body as $type => $label) {
  // Check if FieldConfig already exists.
  if (FieldConfig::loadByName('node', $type, 'body')) {
    echo "Body field already exists on: $type\n";
    continue;
  }

  // Create the FieldConfig (field instance) for this content type.
  FieldConfig::create([
    'field_name'   => 'body',
    'entity_type'  => 'node',
    'bundle'       => $type,
    'label'        => $label,
    'settings'     => ['display_summary' => FALSE],
    'required'     => FALSE,
  ])->save();

  // Add to default form display.
  $form_display = EntityFormDisplay::load("node.$type.default");
  if (!$form_display) {
    $form_display = EntityFormDisplay::create([
      'targetEntityType' => 'node',
      'bundle'           => $type,
      'mode'             => 'default',
      'status'           => TRUE,
    ]);
  }
  $form_display->setComponent('body', ['type' => 'text_textarea_with_summary'])->save();

  // Add to default view display.
  $view_display = EntityViewDisplay::load("node.$type.default");
  if (!$view_display) {
    $view_display = EntityViewDisplay::create([
      'targetEntityType' => 'node',
      'bundle'           => $type,
      'mode'             => 'default',
      'status'           => TRUE,
    ]);
  }
  $view_display->setComponent('body', [
    'type'     => 'text_default',
    'label'    => 'hidden',
    'settings' => [],
  ])->save();

  echo "Added body field to: $type\n";
}

echo "\nBody fields configured.\n";
echo "Now re-run: ddev exec drush php:script import/import-content.php\n";
echo "(existing nodes will be skipped; body data will NOT auto-populate for existing nodes)\n";
echo "To re-populate body data, run the re-body script next.\n";
