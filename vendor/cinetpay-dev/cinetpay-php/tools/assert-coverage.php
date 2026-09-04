<?php

declare(strict_types=1);

if ($argc !== 3) {
    fwrite(STDERR, "Usage: php tools/assert-coverage.php <clover.xml> <minimum-percent>\n");
    exit(2);
}

$coverageFile = $argv[1];
$minimum = (float) $argv[2];

if (! is_file($coverageFile)) {
    fwrite(STDERR, sprintf("Coverage report not found: %s\n", $coverageFile));
    exit(2);
}

$document = new DOMDocument();

if (! $document->load($coverageFile)) {
    fwrite(STDERR, sprintf("Coverage report is invalid XML: %s\n", $coverageFile));
    exit(2);
}

$xpath = new DOMXPath($document);
$metrics = $xpath->query('/coverage/project/metrics')->item(0);

if (! $metrics instanceof DOMElement) {
    fwrite(STDERR, "Coverage report does not contain project metrics.\n");
    exit(2);
}

$classMetrics = $xpath->query('/coverage/project/file/class/metrics');
$coveredClasses = 0;

foreach ($classMetrics as $classMetric) {
    if (! $classMetric instanceof DOMElement) {
        continue;
    }

    $allMethodsCovered = $classMetric->getAttribute('methods') === $classMetric->getAttribute('coveredmethods');
    $allStatementsCovered = $classMetric->getAttribute('statements') === $classMetric->getAttribute('coveredstatements');

    if ($allMethodsCovered && $allStatementsCovered) {
        $coveredClasses++;
    }
}

$categories = [
    'classes' => ['total' => $classMetrics->length, 'covered' => $coveredClasses],
    'methods' => [
        'total' => (int) $metrics->getAttribute('methods'),
        'covered' => (int) $metrics->getAttribute('coveredmethods'),
    ],
    'statements' => [
        'total' => (int) $metrics->getAttribute('statements'),
        'covered' => (int) $metrics->getAttribute('coveredstatements'),
    ],
];

$failed = false;

foreach ($categories as $label => $values) {
    $total = $values['total'];
    $covered = $values['covered'];
    $percentage = $total === 0 ? 100.0 : ($covered / $total) * 100;

    fwrite(STDOUT, sprintf(
        "%s: %.2f%% (%d/%d)\n",
        ucfirst($label),
        $percentage,
        $covered,
        $total,
    ));

    if ($percentage + PHP_FLOAT_EPSILON < $minimum) {
        $failed = true;
    }
}

if ($failed) {
    fwrite(STDERR, sprintf("Coverage is below the required %.2f%% threshold.\n", $minimum));
    exit(1);
}

fwrite(STDOUT, sprintf("Coverage threshold satisfied: %.2f%%.\n", $minimum));
