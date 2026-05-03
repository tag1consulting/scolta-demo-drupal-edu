<?php
use Drupal\taxonomy\Entity\Term;

$add_terms = [
  'news_category' => ['Faculty News', 'Student News', 'Partnerships', 'Institutional', 'Academics'],
];

foreach ($add_terms as $vocab => $names) {
  foreach ($names as $name) {
    $existing = \Drupal::entityTypeManager()->getStorage('taxonomy_term')
      ->loadByProperties(['vid' => $vocab, 'name' => $name]);
    if (!$existing) {
      Term::create(['vid' => $vocab, 'name' => $name])->save();
      echo "Created: $name\n";
    }
    else {
      echo "Exists:  $name\n";
    }
  }
}
