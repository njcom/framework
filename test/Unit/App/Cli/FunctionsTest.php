<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\App\Cli;

use ArrayIterator;
use ArrayObject;
use Morpho\App\Cli\Exception as CliException;
use Morpho\Base\InvalidConfException;
use Morpho\Testing\TestCase;
use RuntimeException;
use Morpho\App\Cli\ICommandResult;

use function basename;
use function escapeshellarg;
use function fclose;
use function file_put_contents;
use function fwrite;
use function md5;
use function Morpho\App\Cli\{arg, envVarsStr, earg, sh, showLine, showOk, showSep, stylize};
use function ob_get_clean;
use function ob_start;
use function proc_close;
use function proc_open;
use function stream_get_contents;

use const Morpho\Base\CODE_WIDTH_1;
use const Morpho\Test\BASE_DIR_PATH;

class FunctionsTest extends TestCase {
    public function testShowLine_NoArgsWritesSingleLine() {
        ob_start();
        showLine();
        $this->assertEquals("\n", ob_get_clean());
    }

    public function testShowLine_SingleArg() {
        ob_start();
        showLine("Printed");
        $this->assertEquals("Printed\n", ob_get_clean());
    }

    public function testShowLine_IterableArg() {
        $val = new ArrayIterator(['foo', 'bar', 'baz']);
        ob_start();
        showLine($val);
        $this->assertEquals("foo\nbar\nbaz\n", ob_get_clean());
    }

    public function testShowOk_WithoutArgs() {
        ob_start();
        showOk();
        $this->assertEquals("OK\n", ob_get_clean());
    }

    public function testShowOk_WithArgs() {
        ob_start();
        showOk("Test");
        $this->assertSame("Test OK\n", ob_get_clean());
    }

    public function testShowSep_DefaultSep() {
        ob_start();
        showSep();
        $output = ob_get_clean();
        $this->assertSame("--------------------------------------------------------------------------------\n", $output);
        $this->assertSame(CODE_WIDTH_1 + 1, strlen($output));
    }

    public function testShowSep_CustomSep() {
        ob_start();
        showSep('#', 3);
        $this->assertSame("###\n", ob_get_clean());
    }

    public function testShowSepWithPrefixAndSuffix() {
        ob_start();
        showSep(prefix: '/*', suffix: '*/');
        $output = ob_get_clean();
        $this->assertSame("/*----------------------------------------------------------------------------*/\n", $output);
        $this->assertSame(CODE_WIDTH_1 + 1, strlen($output));
    }

    public static function dataWriteErrorAndWriteErrorLine(): iterable {
        return [
            ['showError', 'Something went wrong', 'Something went wrong'],
            ['showErrorLine', "Space cow has arrived!\n", 'Space cow has arrived!'],
        ];
    }

    /**
     * @dataProvider dataWriteErrorAndWriteErrorLine
     */
    public function testWriteErrorAndWriteErrorLine($fn, $expectedMessage, $error) {
        $tmpFilePath = $this->createTmpFile();
        $autoloadFilePath = BASE_DIR_PATH. '/vendor/autoload.php';
        file_put_contents(
            $tmpFilePath,
            <<<OUT
<?php
require "$autoloadFilePath";
echo \\Morpho\\App\\Cli\\$fn("$error");
OUT
        );

        $fdSpec = [
            2 => ["pipe", "w"],  // stdout is a pipe that the child will write to
        ];
        $process = proc_open('php ' . escapeshellarg($tmpFilePath), $fdSpec, $pipes);

        $out = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        proc_close($process);

        $this->assertEquals($expectedMessage, $out);
    }

    public function testStylize() {
        $magenta = 35;
        $text = "Hello";
        $this->assertEquals("\033[" . $magenta . "m$text\033[0m", stylize($text, $magenta));
    }

    public function testEarg() {
        $this->assertEquals(
            ["'foo'\\''bar'", "'test/'"],
            earg(["foo'bar", 'test/'])
        );
    }

    public function testArg() {
        $this->assertSame('', arg(''));
        $this->assertSame('', arg([]));
        $this->assertSame(" '1'", arg(1));
        $this->assertEquals(" 'foo'", arg('foo'));
        $this->assertEquals(" 'foo' 'bar'", arg(['foo', 'bar']));
        $gen = function () {
            yield 'foo';
            yield 'bar';
        };
        $this->assertEquals(" 'foo' 'bar'", arg($gen()));
        $this->assertSame(" 'foo' 'bar'", arg(new ArrayObject(['foo', 'bar'])));
        $gen1 = function () {
            yield 1;
            yield 2;
        };
        $this->assertSame(" '1' '2'", arg($gen1()));
    }

    public function testSh_ThrowsExceptionOnInvalidConfParam() {
        $this->expectException(InvalidConfException::class);
        sh('ls', ['some invalid option' => 'value of invalid option']);
    }

    public static function dataSh_CaptureAndShowConfOptions(): iterable {
        yield [false, false];
        yield [false, true];
        yield [true, false];
        yield [true, true];
    }

    /**
     * @dataProvider dataSh_CaptureAndShowConfOptions
     */
    public function testSh_CaptureAndShowConfOptions(bool $capture, bool $show) {
        $cmd = 'ls ' . escapeshellarg(__DIR__);
        ob_start();
        $result = sh($cmd, ['capture' => $capture, 'show' => $show]);
        $this->assertStringContainsString($show ? basename(__FILE__) : '', ob_get_clean());
        $this->assertEquals(0, $result->exitCode());
        $this->assertFalse($result->isError());
        $this->assertStringContainsString($capture ? basename(__FILE__) : '', (string) $result);
    }

    public function testSh_CheckExitConfParam() {
        $exitCode = 134;
        $this->expectException(CliException::class, "Command returned non-zero exit code: $exitCode");
        sh('php -r "exit(' . $exitCode . ');"');
    }

    public function testSh_EnvVarsConfParam() {
        $var = 'v' . md5(__METHOD__);
        $val = 'hello';
        $this->assertSame(
            $val . "\n",
            sh(
                'echo $' . $var,
                ['envVars' => [$var => $val], 'capture' => true, 'show' => false]
            )->stdOut()
        );
    }

    public function testSh_Iterator_MultipleLines() {
        $result = sh('echo foo; echo bar', ['capture' => true, 'show' => false]);
        $this->assertInstanceOf(ICommandResult::class, $result);
        $this->assertCount(2, $result);
        $i = 0;
        foreach ($result as $k => $line) {
            match ($k) {
                0 => $this->assertSame('foo', $line),
                1 => $this->assertSame('bar', $line),
                default => $this->fail(),
            };
            $i++;
        }
        $this->assertSame(2, $i);
    }

    public function testSh_Iterator_EmptyResult() {
        $result = sh('true', ['capture' => true, 'show' => false]);
        $this->assertInstanceOf(ICommandResult::class, $result);
        $this->assertCount(0, $result);
    }

    public function testEnvVarsStr() {
        $this->assertSame("PATH='foo' TEST='foo'\''bar'", envVarsStr(['PATH' => 'foo', 'TEST' => "foo'bar"]));
        $this->assertSame('', envVarsStr([]));
    }

    public function testEnvVarsStr_ThrowsExceptionForInvalidVarName() {
        $this->expectException(RuntimeException::class, 'Invalid variable name');
        envVarsStr(['&']);
    }

    public function testAskYes() {
        $tmpFilePath = $this->createTmpFile();
        $autoloadFilePath = BASE_DIR_PATH . '/vendor/autoload.php';
        $question = "Do you want to play";
        file_put_contents(
            $tmpFilePath,
            <<<OUT
<?php
require "$autoloadFilePath";
echo json_encode(\\Morpho\\App\\Cli\\askYesNo("$question"));
OUT
        );

        $fdSpec = [
            0 => ["pipe", "r"],  // stdin is a pipe that the child will read from
            1 => ["pipe", "w"],  // stdout is a pipe that the child will write to
        ];
        $process = proc_open('php ' . escapeshellarg($tmpFilePath), $fdSpec, $pipes);

        fwrite($pipes[0], "what\ny\n");

        $out = stream_get_contents($pipes[1]);

        foreach ($pipes as $pipe) {
            fclose($pipe);
        }

        proc_close($process);

        $this->assertEquals("$question? (y/n): Invalid choice, please type y or n\ntrue", $out);
    }
}
