<?php

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\DomCrawler\Crawler;

class CMSMigration
{
    private const PAGE_INFO = [
        [
            'page_id' => 1000,
            'url' => 'https://www.nomura.co.jp/terms/english/other/A02904.html'
        ]
    ];

    private const SELECTOR_MAPPING = [
        'span#term_id[data-value]' => 'h1-title',
        'span.txt.-suppress._fz-xl._fz-l-sm._d-b._ff-sans._fw-n._pl-10' => 'sub-title',
        'i.ico-label.-navy.-terms-category._miw-none' => 'label',
        'p.txt' => 'text'
    ];

    private array $migrationData = [];

    public function run(): void
    {
        echo "Starting CMS Migration...\n\n";

        foreach (self::PAGE_INFO as $pageInfo) {
            $pageId = $pageInfo['page_id'];
            $url = $pageInfo['url'];

            echo "Processing Page ID: {$pageId}\n";
            echo "URL: {$url}\n";

            $html = $this->fetchHtml($url);
            
            if ($html === null) {
                echo "Failed to fetch HTML from {$url}\n\n";
                continue;
            }

            $contents = $this->extractContents($html);

            $this->migrationData[$pageId] = [
                'url' => $url,
                'contents' => $contents
            ];

            echo "Extracted " . count($contents) . " elements\n\n";
        }

        $this->outputResults();
    }

    private function fetchHtml(string $url): ?string
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36\r\n"
            ]
        ]);

        $html = @file_get_contents($url, false, $context);

        if ($html === false) {
            return null;
        }

        return $html;
    }

    private function extractContents(string $html): array
    {
        $crawler = new Crawler($html);
        $contents = [];
        $seqNo = 1;

        try {
            $mainContent = $crawler->filter('main#main[role="main"]');
            
            if ($mainContent->count() === 0) {
                echo "Warning: Could not find <main id=\"main\" role=\"main\"> element. Extracting from entire document.\n";
                $mainContent = $crawler;
            }
        } catch (\Exception $e) {
            echo "Warning: Error filtering main content: {$e->getMessage()}. Extracting from entire document.\n";
            $mainContent = $crawler;
        }

        foreach (self::SELECTOR_MAPPING as $selector => $componentName) {
            try {
                $elements = $mainContent->filter($selector);

                foreach ($elements as $element) {
                    $value = $this->extractElementValue($element);

                    if (!empty($value)) {
                        $contents[$seqNo] = [
                            'block_id' => null,
                            'component_name' => $componentName,
                            'value' => $value
                        ];
                        $seqNo++;
                    }
                }
            } catch (\Exception $e) {
                echo "Warning: Failed to extract elements for selector '{$selector}': {$e->getMessage()}\n";
            }
        }

        return $contents;
    }

    private function extractElementValue(\DOMElement $element): string
    {
        $value = trim($element->textContent);

        if ($element->hasAttribute('data-value')) {
            $dataValue = $element->getAttribute('data-value');
            if (!empty($dataValue)) {
                $value = $dataValue . ' - ' . $value;
            }
        }

        return $value;
    }

    private function outputResults(): void
    {
        echo "=== Migration Results ===\n\n";

        echo "Array Structure:\n";
        print_r($this->migrationData);

        echo "\n\n=== JSON Output ===\n";
        echo json_encode($this->migrationData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        echo "\n";

        $outputFile = __DIR__ . '/migration_output.json';
        file_put_contents($outputFile, json_encode($this->migrationData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        echo "\nResults saved to: {$outputFile}\n";
    }
}

if (php_sapi_name() === 'cli') {
    $migration = new CMSMigration();
    $migration->run();
} else {
    echo "This script must be run from the command line.\n";
    exit(1);
}
