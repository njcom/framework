<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/morpho-os/framework/blob/master/LICENSE for the full license text.
 */
namespace Morpho\App\Cli;

const IN_FD = 0;
const OUT_FD = 1;
const ERR_FD = 2;
const STD_PIPES = [
    IN_FD  => ['pipe', 'r'],  // child process will read from STDIN
    OUT_FD => ['pipe', 'w'],  // child process will write to STDOUT
    ERR_FD => ['pipe', 'w'],  // child process will write to STDERR
];

use Morpho\Base\Conf;
use Morpho\Tech\Php\DumpListener;
use Morpho\Tech\Php\ErrorHandler;
use RuntimeException;
use Stringable;
use UnexpectedValueException;

use function count;
use function escapeshellarg;
use function fgets;
use function fwrite;
use function implode;
use function is_int;
use function is_string;
use function Morpho\Base\capture;
use function passthru;
use function pcntl_exec;
use function pcntl_fork;
use function pcntl_waitpid;
use function pcntl_wexitstatus;
use function preg_match;
use function strtolower;
use function substr;
use function trim;

use const Morpho\Base\CODE_WIDTH_1;

/**
 * Useful to call from simple CLI applications, not involving the AppInitializer.
 */
function bootstrap(): void {
    (new Env())->init();
    (new ErrorHandler([new DumpListener()]))->register();
}

function showLine(string|Stringable|iterable|int|float $text = null): void {
    if (null === $text) {
        echo "\n";
    } else {
        if (is_scalar($text) || $text instanceof Stringable) {
            echo (string)$text . "\n";
        } else {
            foreach ($text as $line) {
                echo $line . "\n";
            }
        }
    }
}

function showOk(string|Stringable $prefix = null, string|Stringable $suffix = null): void {
    showLine(
        (null !== $prefix ? $prefix . ' ' : '')
        . 'OK'
        . (null !== $suffix ? ' ' . $suffix : '')
    );
}

/**
 * @param string      $ch Character to output
 * @param int         $n Number of times to output the character
 * @param string|null $prefix
 * @param string|null $suffix
 * @param bool        $stdErr
 * @return void
 */
function showSep(string $ch = '-', int $n = CODE_WIDTH_1, string $prefix = null, string $suffix = null, bool $stdErr = false): void {
    if (null !== $prefix) {
        $n -= mb_strlen($prefix);
    }
    if (null !== $suffix) {
        $n -= mb_strlen($suffix);
    }
    $msg = $prefix . str_repeat($ch, $n) . $suffix;
    if ($stdErr) {
        showErrorLine($msg);
    } else {
        showLine($msg);
    }
}

function showError(string $errMessage): void {
    fwrite(STDERR, $errMessage);
}

function showErrorLine(string $errMessage = null): void {
    showError($errMessage . "\n");
}

/**
 * Like showError() but exits with the non-zero exit code.
 * @param string|null $errMessage
 * @param int|null    $exitCode
 */
function error(string $errMessage = null, int $exitCode = null): never {
    if ($errMessage) {
        showError($errMessage);
    }
    exit(null !== $exitCode && 0 !== $exitCode ? $exitCode : Env::FAILURE_CODE);
}

/**
 * Like showErrorLn() but exits with the non-zero exit code.
 * @param string|null $errMessage
 * @param int|null    $exitCode
 */
function errorLine(string $errMessage = null, int $exitCode = null): never {
    if ($errMessage) {
        showErrorLine($errMessage);
    }
    exit(null !== $exitCode && 0 !== $exitCode ? $exitCode : Env::FAILURE_CODE);
}

/**
 * @param bool $success true => 0, false => 1
 * @return never
 */
function exitBool(bool $success): never {
    exit($success ? Env::SUCCESS_CODE : Env::FAILURE_CODE);
}

function stylize(string $text, $codes): string {
    // @TODO:
    // RGB
    // $fg: 38;05;$codes
    // $bg: 48;05;$codes
    // $codes: $code(';' $code)?
    // $code: int in [1..255]
    // $flags $fg $bg
    // $flags: $bold | $italic | $underline | $inverse | $blink
    // $bold: 01
    // $italic: 03
    // $underline: 04
    // $inverse: 07
    // $blink: 05
    /*
    $flags
    00=none
    01=bold
    04=underscore
    05=blink
    07=reverse
    08=concealed
    */

    // \033 is ASCII-code of the ESC.
    static $escapeSeqPrefix = "\033[";
    static $escapeSeqSuffix = "\033[0m";
    $textStyle = implode(';', (array)$codes) . 'm';
    return $escapeSeqPrefix
        . $textStyle
        . $text
        . $escapeSeqSuffix;
}

function earg(int|string|iterable $args): array {
    if (!is_iterable($args)) {
        if (is_string($args) || is_int($args)) {
            return [escapeshellarg((string)$args)];
        }
        throw new UnexpectedValueException();
    }
    $res = [];
    foreach ($args as $arg) {
        $res[] = escapeshellarg((string)$arg);
    }
    return $res;
}

function arg(int|string|iterable $args): string {
    if ($args === '') {
        return '';
    }
    $suffix = implode(' ', earg($args));
    return $suffix === '' ? '' : ' ' . $suffix;
}

function envVarsStr(array $envVars): string {
    if (!count($envVars)) {
        return '';
    }
    $str = '';
    foreach ($envVars as $name => $value) {
        if (!preg_match('~^[a-z][a-z0-9_]*$~si', (string)$name)) {
            throw new RuntimeException('Invalid variable name');
        }
        $str .= ' ' . $name . '=' . escapeshellarg($value);
    }
    return substr($str, 1);
}

function mkdir(string $args, array $conf = null): ICommandResult {
    return sh('mkdir -p ' . $args);
}

function mv(string $args, array $conf = null): ICommandResult {
    return sh('mv ' . $args, $conf);
}

function cp(string $args, array $conf = null): ICommandResult {
    return sh('cp -r ' . $args, $conf);
}

function rm(string $args, array $conf = null): ICommandResult {
    return sh('rm -rf ' . $args, $conf);
}

function sh(string $command, array $conf = null): ICommandResult {
    /*    if (isset($conf['capture'])) {
            if (!isset($conf['show'])) {
                $conf['show'] = !$conf['capture'];
            }
        }*/
    $showSet = isset($conf['show']);
    $captureSet = isset($conf['capture']);
    $conf = Conf::check(
        [
            'check'   => true,
            // @TODO: tee: buffer and display output
            'show'    => true,
            'capture' => false,
            'envVars' => null,
        ],
        (array)$conf
    );
    if (!$showSet && $conf['capture']) {
        $conf['show'] = false;
    }
    if ($showSet && !$conf['show'] && !$captureSet) { // show === false && !isset($capture)
        $conf['capture'] = true;
    }
    $output = '';
    $exitCode = 1;
    if ($conf['envVars']) {
        $command = envVarsStr($conf['envVars']) . ';' . $command;
    }
    // todo: replace capture() (and ob_*() calls) with an variable
    if ($conf['capture']) {
        $output = capture(
            function () use ($command, &$exitCode) {
                passthru($command, $exitCode);
            }
        );
        if ($conf['show']) {
            // Capture and show
            echo $output;
        }
    } else {
        if ($conf['show']) {
            // Don't capture, show
            passthru($command, $exitCode);
        } else {
            // Don't capture, don't show => we are capturing to avoid displaying the result, but don't save the output.
            capture(
                function () use ($command, &$exitCode) {
                    passthru($command, $exitCode);
                }
            );
        }
    }

    if ($conf['check']) {
        checkExitCode($exitCode);
    }
    // @TODO: Check the `system` function https://github.com/Gabriel439/Haskell-Turtle-Library/blob/master/src/Turtle/Bytes.hs#L319
    // @TODO: To get stderr use 2>&1 at the end.
    return new ShellCommandResult($command, $exitCode, $output, '');
}

function sudo(string $command, array $conf = null): ICommandResult {
    return sh('sudo bash -c "' . str_replace('"', '\\"', $command) . '"', $conf);
}

/**
 * Taken from https://habr.com/ru/post/135200/
 * @param string $cmd
 */
function rawSh(string $cmd, $env = null) {
    $pid = pcntl_fork();
    if ($pid < 0) {
        throw new RuntimeException('fork failed');
    }
    if ($pid == 0) {
        pcntl_exec('/bin/sh', ['-c', $cmd], $env ?? []); // @TODO: pass $_ENV?
        exit(127);
    }
    pcntl_waitpid($pid, $status);
    return pcntl_wexitstatus($status);
}

function checkExitCode(int $exitCode, string $errMessage = null): int {
    if ($exitCode !== 0) {
        throw new RuntimeException(
            "Command returned non-zero exit code: " . (int)$exitCode . (null !== $errMessage ? '. ' . $errMessage : '')
        );
    }
    return $exitCode;
}

/*function checkResult(ICommandResult $result): void {
    if ($result->isError()) {
        errorLine($result->error() . ' Exit code: ' . $result->exitCode());
    }
}*/

function ask(string $question, bool $trim = true): string {
    echo $question;
    $result = fgets(STDIN);
    // \fgets() returns false on Ctrl-D
    if (false === $result) {
        $result = '';
    }
    return $trim ? trim($result) : $result;
}

function askYesNo(string $question): bool {
    echo $question . "? (y/n): ";
    do {
        $answer = strtolower(trim(fgets(STDIN)));
        if ($answer === 'y') {
            return true;
        } elseif ($answer === 'n') {
            return false;
        } else {
            showLine("Invalid choice, please type y or n");
        }
    } while (true);
}

function stdOutIsTerminal(): bool {
    return posix_isatty(STDOUT);
}