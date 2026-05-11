<?php

declare(strict_types=1);

namespace Drupal\ai_uni_lms\Commands;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\scolta\Progress\DrushProgressReporter;
use Drupal\scolta\Service\ScoltaContentGatherer;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Tag1\Scolta\Config\MemoryBudgetConfig;
use Tag1\Scolta\Index\BuildIntentFactory;
use Tag1\Scolta\Index\IndexBuildOrchestrator;

/**
 * Custom Drush commands for the AI Uni LMS demo.
 */
class AiUniCommands extends DrushCommands {

  public function __construct(
    private readonly ScoltaContentGatherer $contentGatherer,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly StreamWrapperManagerInterface $streamWrapperManager,
    private readonly StateInterface $state,
  ) {
    parent::__construct();
  }

  /**
   * Build the Scolta search index for all content types in one pass.
   *
   * Uses the PHP indexer to combine nodes and lms_course groups into a single
   * index without exporting HTML files or invoking the Pagefind binary.
   */
  #[CLI\Command(name: 'ai-uni:build-search', aliases: ['aubs'])]
  public function buildSearch(): void {
    $config = $this->configFactory->get('scolta.settings');
    $siteName = $config->get('site_name') ?: 'Meridian AI';
    $language = $config->get('ai_languages')[0] ?? 'en';

    $resolvedOutputDir = $this->resolvePath(
      $config->get('pagefind.output_dir') ?? 'public://scolta-pagefind'
    );
    $resolvedStateDir = $this->resolvePath(
      $config->get('pagefind.build_dir') ?? 'private://scolta-build'
    );

    if (!is_dir($resolvedStateDir) && !mkdir($resolvedStateDir, 0755, TRUE)) {
      $this->logger()->error('Failed to create state directory: {dir}', ['dir' => $resolvedStateDir]);
      return;
    }
    if (!is_dir($resolvedOutputDir) && !mkdir($resolvedOutputDir, 0755, TRUE)) {
      $this->logger()->error('Failed to create output directory: {dir}', ['dir' => $resolvedOutputDir]);
      return;
    }

    $nodeCount = $this->contentGatherer->gatherCount('node', '');
    $groupCount = $this->contentGatherer->gatherCount('group', 'lms_course');
    $totalCount = $nodeCount + $groupCount;

    if ($totalCount === 0) {
      $this->logger()->warning('No content found to index.');
      return;
    }
    $this->logger()->notice('Indexing {count} entities ({nodes} nodes + {groups} courses)...', [
      'count' => $totalCount,
      'nodes' => $nodeCount,
      'groups' => $groupCount,
    ]);

    $budget = MemoryBudgetConfig::fromCliAndConfig(
      NULL,
      NULL,
      fn() => [
        'profile' => $config->get('memory_budget.profile') ?? 'conservative',
        'chunk_size' => $config->get('memory_budget.chunk_size'),
      ],
    );

    $intent = BuildIntentFactory::fromFlags(FALSE, FALSE, $totalCount, $budget);
    $reporter = new DrushProgressReporter($this->output());
    $orchestrator = new IndexBuildOrchestrator($resolvedStateDir, $resolvedOutputDir, NULL, $language);

    $report = $orchestrator->build($intent, $this->combinedContent($siteName), $this->logger(), $reporter);

    if ($report->success) {
      $generation = $this->state->get('scolta.generation', 0);
      $this->state->set('scolta.generation', $generation + 1);
      \Drupal::service('cache_tags.invalidator')->invalidateTags(['scolta_search_index']);
      $this->logger()->success('Index built: {pages} pages in {time}s ({mem} peak RAM).', [
        'pages' => $report->pagesProcessed,
        'time' => $report->durationSeconds,
        'mem' => $report->peakMemoryMb(),
      ]);
    }
    else {
      $this->logger()->error('PHP indexer failed: {error}', ['error' => $report->error ?? 'unknown']);
    }
  }

  private function combinedContent(string $siteName): \Generator {
    yield from $this->contentGatherer->gather('node', '', $siteName);
    yield from $this->contentGatherer->gather('group', 'lms_course', $siteName);
  }

  private function resolvePath(string $uri): string {
    if (!str_contains($uri, '://')) {
      return $uri;
    }
    try {
      return $this->streamWrapperManager->getViaUri($uri)->realpath() ?: $uri;
    }
    catch (\Exception) {
      return $uri;
    }
  }

}
