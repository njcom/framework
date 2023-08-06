<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Compiler;

use ArrayObject;
use Morpho\Base\Pipe;
use Morpho\Compiler\Compiler;
use Morpho\Compiler\ICompiler;

class CompilerTest extends ConfigurablePipeTest {
    public function testCompilerInterface() {
        $compiler = new Compiler($this->mkCompilerConf());
        $this->assertIsCallable($compiler);
        $this->assertInstanceOf(ICompiler::class, $compiler);
        $this->assertInstanceOf(Pipe::class, $compiler);
    }

    private function mkCompilerConf(): array {
        return [
            'frontend' => fn($v) => $v,
            'midend'   => fn($v) => $v,
            'backend'  => fn($v) => $v,
        ];
    }

    public function testCustomStepsViaConstructorConf() {
        $frontend = function ($v) {
            $v['frontend'] = 'frontend ok';
            return $v;
        };
        $midend = function ($v) {
            $v['midend'] = 'midend ok';
            return $v;
        };
        $backend = function ($v) {
            $v['backend'] = 'backend ok';
            $v['target'] = $v['source'];
            return $v;
        };

        $conf = [
            'frontend' => $frontend,
            'midend'   => $midend,
            'backend'  => $backend,
        ];
        $compiler = new Compiler($conf);

        $this->assertSame($frontend, $compiler->frontend());
        $this->assertSame($midend, $compiler->midend());
        $this->assertSame($backend, $compiler->backend());

        $source = '';
        $context = new ArrayObject(
            [
                'source' => $source,
            ]
        );

        $result = $compiler($context);

        $this->assertSame($result, $context);
        $this->assertSame($source, $result['source']);
        $this->assertSame($source, $result['target']); // should not be changed
        $this->assertSame('frontend ok', $context['frontend']);
        $this->assertSame('midend ok', $context['midend']);
        $this->assertSame('backend ok', $context['backend']);
    }

    public static function dataStepsAccessors() {
        yield [
            'frontend',
            'midend',
            'backend',
        ];
    }

    /**
     * @dataProvider dataStepsAccessors
     */
    public function testStepsAccessors(string $method) {
        $compiler = new Compiler($this->mkCompilerConf());
        $oldStep = $compiler->$method();
        $this->assertIsCallable($oldStep);
        $newStep = fn() => null;
        $this->assertSame($compiler, $compiler->{'set' . $method}($newStep));
        $this->assertSame($newStep, $compiler->$method());
        $this->assertNotSame($newStep, $oldStep);
    }

    public function testDefaultSteps() {
        $compiler = new Compiler($this->mkCompilerConf());

        $frontend = $compiler->frontend();
        $this->assertIsCallable($frontend);

        $midend = $compiler->midend();
        $this->assertIsCallable($midend);
        $this->assertNotSame($frontend, $midend);

        $backend = $compiler->backend();
        $this->assertIsCallable($backend);
        $this->assertNotSame($frontend, $backend);
        $this->assertNotSame($midend, $backend);

        $context['source'] = '';
        $this->assertSame($context, $compiler($context));
    }
}