<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\Tech\Php;

use Morpho\Base\Err;
use Morpho\Base\IFn;
use Morpho\Base\Ok;
use Morpho\Base\Result;

use function Morpho\App\Cli\errorLn;
use function Morpho\Base\indent;
use function Morpho\Base\q;
use function Morpho\Base\showLn;
use function Morpho\Base\showOk;

class CliPhpFileHeaderFixer implements IFn {
    public function __invoke(mixed $conf): mixed {
        $result = null;
        foreach ($conf['files'] as [$filePaths, $context]) {
            $context['dryRun'] = $conf['dryRun'];
            $result = $this->fixFiles([$conf['constructArgs'] ?? null], $filePaths, $context, $result);
        }
        $this->showResult($result);
        return $result;
    }

    public function fixFiles(array $constructArgs, iterable $files, array $context, Result $prevResult = null): Result {
        $fixer = new PhpFileHeaderFixer(...$constructArgs);
        if ($prevResult) {
            $stats = $prevResult->val();
            $ok = $prevResult->isOk();
        } else {
            $stats = ['processed' => ['num' => 0], 'fixed' => ['num' => 0, 'filePaths' => []]];
            $ok = true;
        }
        foreach ($files as $filePath) {
            showLn("Processing file " . q($filePath) . '...');
            $result = $fixer->__invoke(array_merge($context, ['filePath' => $filePath]));
            if (!$result->isOk()) {
                errorLn("Unable to fix the file " . q($filePath) . "\n" . print_r($result, true));
            }
            if (isset($result->val()['text'])) {
                showLn(indent($result->val()['text']));
                $stats['fixed']['num']++;
                $stats['fixed']['filePaths'][] = $filePath;
            }
            $stats['processed']['num']++;
            showOk();
            $ok = $result->isOk() && $ok;
        }
        return $ok ? new Ok($stats) : new Err($stats);
    }

    public function showResult(Result $result): void {
        showLn("\nNumber of processed files: " . $result->val()['processed']['num']);
        showLn("Number of fixed files: " . $result->val()['fixed']['num']);
        showLn(
            "List of fixed files: " . ($result->val()['fixed']['num'] > 0 ? "\n" . indent(
                    implode("\n", $result->val()['fixed']['filePaths']),
                    4
                ) : '-')
        );
    }
}
