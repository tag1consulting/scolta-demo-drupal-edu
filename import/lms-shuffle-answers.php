<?php
/**
 * Shuffle answer order on all quiz_mc activities.
 *
 * Uses a deterministic seed (activity ID) so repeated runs produce the same
 * shuffled order. Ensures the correct answer is no longer always at delta 0.
 */

$storage = \Drupal::entityTypeManager()->getStorage('lms_activity');
$ids = $storage->getQuery()
  ->accessCheck(FALSE)
  ->condition('type', 'quiz_mc')
  ->execute();

if (empty($ids)) {
  echo "No quiz_mc activities found.\n";
  return;
}

$moved = 0;
$skipped = 0;

foreach ($storage->loadMultiple($ids) as $activity) {
  $answers = [];
  foreach ($activity->get('answers') as $item) {
    $answers[] = [
      'answer' => $item->get('answer')->getString(),
      'correct' => (int) $item->isCorrect(),
    ];
  }

  if (count($answers) < 2) {
    $skipped++;
    continue;
  }

  // Seed with activity ID for reproducible (idempotent) shuffling.
  mt_srand((int) $activity->id() * 31337);
  shuffle($answers);

  // If the correct answer ended up first again, rotate it one position.
  if ($answers[0]['correct']) {
    $first = array_shift($answers);
    array_push($answers, $first);
  }

  $activity->set('answers', $answers);
  $activity->save();
  $moved++;

  echo sprintf("  Shuffled activity %d: %s\n", $activity->id(), $activity->label());
}

echo "\nDone. Shuffled: {$moved}, Skipped: {$skipped}.\n";
