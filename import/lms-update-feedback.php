<?php
/**
 * Populate feedback_if_correct / feedback_if_wrong on all quiz_mc activities.
 *
 * The LmsAnswer field type has no per-answer feedback column, so explanations
 * must live in the activity-level feedback_if_correct / feedback_if_wrong
 * fields. Run after lms-create-course.php.
 */

function uuid_for(string $context, int ...$ids): string {
  $key = 'feste-course:' . $context . ':' . implode(':', $ids);
  $hash = sha1($key);
  return sprintf(
    '%08s-%04s-5%03s-%04s-%12s',
    substr($hash, 0, 8),
    substr($hash, 8, 4),
    substr($hash, 13, 3),
    dechex(0x8000 | (hexdec(substr($hash, 16, 4)) & 0x3fff)),
    substr($hash, 20, 12)
  );
}

require_once __DIR__ . '/lms-lesson-data-1-5.php';
require_once __DIR__ . '/lms-lesson-data-6-10.php';
require_once __DIR__ . '/lms-lesson-data-11-15.php';
$lessons = lessons_1_5() + lessons_6_10() + lessons_11_15();

$storage = \Drupal::entityTypeManager()->getStorage('lms_activity');

foreach ($lessons as $lesson_num => $lesson) {
  foreach ($lesson['quiz'] as $q_idx => $q) {
    $q_uuid = uuid_for('quiz', $lesson_num, $q_idx);
    $existing = $storage->loadByProperties(['uuid' => $q_uuid]);
    if (!$existing) {
      echo "  MISSING L{$lesson_num}/Q{$q_idx} uuid={$q_uuid}\n";
      continue;
    }
    $activity = reset($existing);

    // Find the explanation from the correct answer.
    $correct_feedback = '';
    foreach ($q['answers'] as $a) {
      if (!empty($a['correct']) && !empty($a['explanation'])) {
        $correct_feedback = $a['explanation'];
        break;
      }
    }

    $activity->set('feedback_if_correct', $correct_feedback ?: 'Correct!');
    $activity->set('feedback_if_wrong', 'Not quite — try again, or review the lesson content.');
    $activity->save();
    echo "  Updated L{$lesson_num}/Q{$q_idx}: {$activity->id()}\n";
  }
}

echo "\nDone.\n";
