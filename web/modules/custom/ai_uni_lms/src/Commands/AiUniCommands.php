<?php

declare(strict_types=1);

namespace Drupal\ai_uni_lms\Commands;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\scolta\Service\ScoltaContentGatherer;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Tag1\Scolta\Binary\PagefindBinary;
use Tag1\Scolta\Export\ContentExporter;

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
   * Exports nodes and lms_course groups to a single directory, then
   * runs Pagefind once. This avoids the prepareOutputDir() wipe that
   * would destroy node files if group and node exports ran separately.
   */
  #[CLI\Command(name: 'ai-uni:build-search', aliases: ['aubs'])]
  #[CLI\Option(name: 'output-dir', description: 'Export directory for HTML files')]
  public function buildSearch(
    array $options = ['output-dir' => '/var/www/html/pagefind-site'],
  ): void {
    $config = $this->configFactory->get('scolta.settings');
    $outputDir = $options['output-dir'];
    $siteName = $config->get('site_name') ?: 'Meridian AI';

    $exporter = new ContentExporter($outputDir);

    $this->logger()->notice('Preparing output directory...');
    $exporter->prepareOutputDir();

    // Export nodes.
    $this->logger()->notice('Exporting nodes...');
    $nodeCount = 0;
    foreach ($this->contentGatherer->gather('node', '', $siteName) as $item) {
      if ($exporter->export($item)) {
        $nodeCount++;
      }
    }
    $this->logger()->notice("Exported {$nodeCount} node(s).");

    // Export lms_course groups.
    $this->logger()->notice('Exporting LMS courses...');
    $groupCount = 0;
    foreach ($this->contentGatherer->gather('group', 'lms_course', $siteName) as $item) {
      if ($exporter->export($item)) {
        $groupCount++;
      }
    }
    $this->logger()->notice("Exported {$groupCount} LMS course(s).");

    $total = $nodeCount + $groupCount;
    if ($total === 0) {
      $this->logger()->warning('No content exported — skipping Pagefind build.');
      return;
    }

    // Run Pagefind.
    $this->logger()->notice("Running Pagefind on {$total} HTML file(s)...");
    $resolver = new PagefindBinary(
      configuredPath: $config->get('pagefind.binary'),
      projectDir: defined('DRUPAL_ROOT') ? DRUPAL_ROOT : getcwd(),
    );

    $binary = $resolver->resolve();
    if ($binary === NULL) {
      $status = $resolver->status();
      $this->logger()->error($status['message']);
      return;
    }

    $outputPath = $this->resolvePath(
      $config->get('pagefind.output_dir') ?? 'public://scolta-pagefind'
    ) . '/pagefind';

    $cmd = $binary
      . ' --site ' . escapeshellarg($outputDir)
      . ' --output-path ' . escapeshellarg($outputPath)
      . ' 2>&1';
    $output = [];
    $exitCode = NULL;
    exec($cmd, $output, $exitCode);
    foreach ($output as $line) {
      $this->logger()->notice($line);
    }

    if ($exitCode !== 0) {
      $this->logger()->error('Pagefind build failed.');
      return;
    }

    $generation = $this->state->get('scolta.generation', 0);
    $this->state->set('scolta.generation', $generation + 1);
    \Drupal::service('cache_tags.invalidator')->invalidateTags(['scolta_search_index']);

    $this->logger()->success("Search index built: {$total} documents.");
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
