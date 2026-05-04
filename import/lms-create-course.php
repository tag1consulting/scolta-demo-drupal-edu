<?php
/**
 * Create the Feste LMS course with all modules, lessons, and activities.
 *
 * Prerequisites: run lms-setup.php first to create activity types and group roles.
 *
 * Structure:
 *   1 Course (Group) → 15 Lessons (LMS Lesson entities)
 *   Each lesson → 1 reading_content activity + 3-5 quiz_mc activities
 *
 * Activity UUIDs are deterministic (based on lesson/activity index) so this
 * script is idempotent — re-running skips already-created entities.
 */

// ── Helpers ───────────────────────────────────────────────────────────────────

function uuid_for(string $context, int ...$ids): string {
  // Deterministic UUID v5 (SHA-1 based) from a namespace + context string.
  $key = 'feste-course:' . $context . ':' . implode(':', $ids);
  $hash = sha1($key);
  return sprintf(
    '%08s-%04s-5%03s-%04s-%12s',
    substr($hash, 0, 8),
    substr($hash, 8, 4),
    substr($hash, 13, 3),
    // Variant bits 10xx
    dechex(0x8000 | (hexdec(substr($hash, 16, 4)) & 0x3fff)),
    substr($hash, 20, 12)
  );
}

$entity_manager = \Drupal::entityTypeManager();
$activity_storage = $entity_manager->getStorage('lms_activity');
$lesson_storage   = $entity_manager->getStorage('lms_lesson');
$group_storage    = $entity_manager->getStorage('group');

// ── Module / lesson catalogue ─────────────────────────────────────────────────

$modules = [
  1 => 'Tokenization',
  2 => 'Tensor Operations',
  3 => 'Model Architecture',
  4 => 'Training Infrastructure',
  5 => 'Experiments and Results',
];

// lesson_number => [title, module_number, reading_content, key_takeaways, quiz[]]
// Reading content and quiz data injected by the content-generation pass.
$lessons = lesson_data();

// ── 1. Create Activities ──────────────────────────────────────────────────────

$activity_ids = [];   // [lesson_number][activity_index] => entity id

foreach ($lessons as $lesson_num => $lesson) {
  // a) Reading content activity
  $r_uuid = uuid_for('reading', $lesson_num);
  $existing = $activity_storage->loadByProperties(['uuid' => $r_uuid]);
  if ($existing) {
    $act = reset($existing);
    echo "  Exists reading activity for L{$lesson_num}: {$act->id()}\n";
  } else {
    $act = $activity_storage->create([
      'uuid'   => $r_uuid,
      'type'   => 'reading_content',
      'name'   => $lesson['title'],
      'body'   => ['value' => $lesson['content_html'], 'format' => 'full_html'],
      'status' => 1,
    ]);
    $act->save();
    echo "  Created reading activity for L{$lesson_num}: {$act->id()}\n";
  }
  $activity_ids[$lesson_num][0] = $act->id();

  // b) Quiz activities
  foreach ($lesson['quiz'] as $q_idx => $q) {
    $q_uuid = uuid_for('quiz', $lesson_num, $q_idx);
    $existing = $activity_storage->loadByProperties(['uuid' => $q_uuid]);
    if ($existing) {
      $qact = reset($existing);
      echo "  Exists quiz activity L{$lesson_num}/Q{$q_idx}: {$qact->id()}\n";
    } else {
      // Build answers array for the select_feedback plugin field.
      $answers = [];
      foreach ($q['answers'] as $a) {
        $answers[] = [
          'answer'  => $a['text'],
          'correct' => $a['correct'] ? 1 : 0,
        ];
      }
      // Shuffle so the correct answer is not always at position 0.
      // Seed is deterministic so re-runs produce the same order.
      mt_srand($lesson_num * 1000 + $q_idx);
      shuffle($answers);
      if ($answers[0]['correct']) {
        $first = array_shift($answers);
        array_push($answers, $first);
      }

      $qact = $activity_storage->create([
        'uuid'     => $q_uuid,
        'type'     => 'quiz_mc',
        'name'     => "L{$lesson_num} Quiz - " . mb_substr($q['question'], 0, 60),
        'question' => $q['question'],
        'answers'  => $answers,
        'status'   => 1,
      ]);
      $qact->save();
      echo "  Created quiz activity L{$lesson_num}/Q{$q_idx}: {$qact->id()}\n";
    }
    $activity_ids[$lesson_num][$q_idx + 1] = $qact->id();
  }
}

echo "\n";

// ── 2. Create Lessons ─────────────────────────────────────────────────────────

$lesson_ids = [];

foreach ($lessons as $lesson_num => $lesson) {
  $l_uuid = uuid_for('lesson', $lesson_num);
  $existing = $lesson_storage->loadByProperties(['uuid' => $l_uuid]);
  if ($existing) {
    $les = reset($existing);
    echo "Exists lesson L{$lesson_num}: {$les->id()}\n";
  } else {
    // Build activities reference list.
    $activities_ref = [];
    foreach ($activity_ids[$lesson_num] as $act_id) {
      $activities_ref[] = ['target_id' => $act_id, 'data' => []];
    }

    $les = $lesson_storage->create([
      'uuid'        => $l_uuid,
      'name'        => "Module {$lesson['module']} – " . $lesson['title'],
      'description' => [
        'value'  => implode("\n", array_map(fn($o) => "<li>$o</li>", $lesson['objectives'])),
        'format' => 'full_html',
      ],
      'activities'         => $activities_ref,
      'backwards_navigation' => TRUE,
      'status'             => 1,
    ]);
    $les->save();
    echo "Created lesson L{$lesson_num}: {$les->id()} — {$les->label()}\n";
  }
  $lesson_ids[$lesson_num] = $les->id();
}

echo "\n";

// ── 3. Create the Course ──────────────────────────────────────────────────────

$course_uuid = uuid_for('course', 1);
$existing_course = $group_storage->loadByProperties(['uuid' => $course_uuid]);

if ($existing_course) {
  $course = reset($existing_course);
  echo "Course already exists: " . $course->id() . " — " . $course->label() . "\n";
} else {
  // Build lessons reference list.
  $lessons_ref = [];
  foreach ($lesson_ids as $lesson_num => $lesson_id) {
    $lessons_ref[] = [
      'target_id'        => $lesson_id,
      'data' => [
        'required_score'    => 60,
        'mandatory'         => FALSE,
        'auto_repeat_failed'=> FALSE,
        'time_limit'        => 0,
      ],
    ];
  }

  $course = $group_storage->create([
    'uuid'            => $course_uuid,
    'type'            => 'lms_course',
    'label'           => 'Building an LLM From Scratch in Rust',
    'status'          => 1,
    'free_navigation' => TRUE,
    'lessons'         => $lessons_ref,
  ]);
  $course->save();
  echo "Created course: " . $course->id() . " — " . $course->label() . "\n";
  echo "Course URL: /group/" . $course->id() . "\n";
  echo "Start URL:  /course/" . $course->id() . "/start\n";
}

echo "\nDone.\n";


// ── Content data ──────────────────────────────────────────────────────────────

/**
 * Returns the full lesson catalogue with content and quiz data.
 * Split into a function so the main script body stays readable.
 */
function lesson_data(): array {
  require_once __DIR__ . '/lms-lesson-data-1-5.php';
  require_once __DIR__ . '/lms-lesson-data-6-10.php';
  require_once __DIR__ . '/lms-lesson-data-11-15.php';
  return lessons_1_5() + lessons_6_10() + lessons_11_15();
}
