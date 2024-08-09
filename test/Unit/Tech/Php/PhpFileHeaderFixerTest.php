<?php declare(strict_types=1);
/**
 * This file is part of njcom/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Test\Unit\Tech\Php;

use Morpho\Base\Err;
use Morpho\Base\Ok;
use Morpho\Base\Result;
use Morpho\Tech\Php\PhpFileHeaderFixer;
use Morpho\Testing\TestCase;

use PHPUnit\Framework\Attributes\DataProvider;

use const Morpho\Tech\Php\LICENSE_COMMENT;

class PhpFileHeaderFixerTest extends TestCase {
    private PhpFileHeaderFixer $fixer;

    public function testInterface() {
        $this->assertIsCallable($this->fixer);
    }

    public static function dataCheckAndFix_EmptyFile() {
        (yield ['']);
        (yield ['<?php']);
        (yield ["#!/usr/bin/env php"]);
        (yield ["#!/usr/bin/env php\n<?php"]);
        (yield ["#!/usr/bin/env php\n<?php\n"]);
    }

    #[DataProvider('dataCheckAndFix_EmptyFile')]
    public function testCheckAndFix_EmptyFile(string $text) {
        $tmpFilePath = $this->createTmpFile();
        file_put_contents($tmpFilePath, $text);

        $context = ['baseDirPath' => dirname($tmpFilePath), 'filePath' => $tmpFilePath, 'ns' => 'Some'];
        $checkResult = $this->fixer->check($context);
        $resultContext = $checkResult->val();
        $this->assertInstanceOf(Err::class, $checkResult);
        $this->checkContext(
            array_merge(
                $context,
                [
                    'hasStmts'             => false,
                    'hasDeclare'           => false,
                    'hasValidDeclare'      => false,
                    'hasLicenseComment'    => false,
                    'nsCheckResult'        => new Err(['expected' => 'Some', 'actual' => null]),
                    'classTypeCheckResult' => new Ok(['expected' => null, 'actual' => null]),
                ]
            ),
            $resultContext
        );
        $fixResult = $this->fixer->fix($resultContext);
        $this->assertEquals(new Err("The file '{$context['filePath']}' does not have PHP statements"), $fixResult);
    }

    public function testCheckAndFix_DeclareAndNamespace_NoLicense() {
        $filePath = $this->getTestDirPath() . '/Foo.php';
        $context = ['filePath' => $filePath, 'baseDirPath' => dirname($filePath), 'ns' => self::class];
        $checkResult = $this->fixer->check($context);
        $this->assertInstanceOf(Err::class, $checkResult);
        $resultContext = $checkResult->val();
        $this->checkContext(
            array_merge(
                $context,
                [
                    'hasStmts'             => true,
                    'hasDeclare'           => true,
                    'hasValidDeclare'      => true,
                    'hasLicenseComment'    => false,
                    'nsCheckResult'        => new Ok(['expected' => $context['ns'], 'actual' => $context['ns']]),
                    'classTypeCheckResult' => new Ok(['expected' => 'Foo', 'actual' => 'Foo']),
                ]
            ),
            $resultContext
        );
        $fixResult = $this->fixer->fix($resultContext);
        $this->checkResultOfFix($fixResult);
    }

    public function testCheckAndFix_DeclareAndLicense_NoNs() {
        $filePath = $this->getTestDirPath() . '/bar.php';
        $context = ['filePath' => $filePath, 'baseDirPath' => dirname($filePath), 'ns' => self::class];

        $checkResult = $this->fixer->check($context);

        $this->assertInstanceOf(Err::class, $checkResult);
        $resultContext = $checkResult->val();
        $this->checkContext(
            array_merge(
                $context,
                [
                    'hasStmts'             => true,
                    'hasDeclare'           => true,
                    'hasValidDeclare'      => true,
                    'hasLicenseComment'    => true,
                    'nsCheckResult'        => new Err(['expected' => $context['ns'], 'actual' => null]),
                    'classTypeCheckResult' => new Ok(['expected' => null, 'actual' => null]),
                ]
            ),
            $resultContext
        );

        $fixResult = $this->fixer->fix($resultContext);
        $this->checkResultOfFix($fixResult);
    }

    public function testCheck_MultipleDocComments() {
        $filePath = $this->getTestDirPath() . '/multiple-doc-comments.php';
        $context = ['filePath' => $filePath, 'baseDirPath' => dirname($filePath), 'ns' => self::class];
        $checkResult = $this->fixer->check($context);
        $this->assertInstanceOf(Ok::class, $checkResult);
        $resultContext = $checkResult->val();
        $this->checkContext(
            array_merge(
                $context,
                [
                    'hasStmts'             => true,
                    'hasDeclare'           => true,
                    'hasValidDeclare'      => true,
                    'hasLicenseComment'    => true,
                    'nsCheckResult'        => new Ok(['expected' => $context['ns'], 'actual' => $context['ns']]),
                    'classTypeCheckResult' => new Ok(['expected' => null, 'actual' => null]),
                ]
            ),
            $resultContext
        );
    }

    public function testCheckAndFix_LicenseCommentInInvalidPlace() {
        $filePath = $this->getTestDirPath() . '/LicenseCommentInInvalidPlace.php';
        $context = ['filePath' => $filePath, 'baseDirPath' => dirname($filePath), 'ns' => self::class];

        $checkResult = $this->fixer->check($context);

        $this->assertInstanceOf(Err::class, $checkResult);
        $resultContext = $checkResult->val();
        $this->checkContext(
            array_merge(
                $context,
                [
                    'hasStmts'             => true,
                    'hasDeclare'           => true,
                    'hasValidDeclare'      => true,
                    'hasLicenseComment'    => false,
                    'nsCheckResult'        => new Ok(['expected' => $context['ns'], 'actual' => $context['ns']]),
                    'classTypeCheckResult' => new Ok(
                        ['expected' => 'LicenseCommentInInvalidPlace', 'actual' => 'LicenseCommentInInvalidPlace']
                    ),
                ]
            ),
            $resultContext
        );

        $fixResult = $this->fixer->fix($resultContext);

        $this->checkResultOfFix($fixResult);
    }

    public function testCheckAndFix_LicenseCommentInInvalidPlace1() {
        $filePath = $this->getTestDirPath() . '/AppTest.php';
        $context = ['filePath' => $filePath, 'baseDirPath' => dirname($filePath), 'ns' => self::class];

        $checkResult = $this->fixer->check($context);

        $this->assertInstanceOf(Err::class, $checkResult);
        $resultContext = $checkResult->val();

        $this->checkContext(
            array_merge(
                $context,
                [
                    'hasStmts'             => true,
                    'hasDeclare'           => true,
                    'hasValidDeclare'      => true,
                    'nsCheckResult'        => new Ok(['expected' => $context['ns'], 'actual' => $context['ns']]),
                    'classTypeCheckResult' => new Ok(['expected' => 'AppTest', 'actual' => 'AppTest']),
                    'hasLicenseComment'    => false,
                ],
            ),
            $resultContext,
        );

        $fixResult = $this->fixer->fix($resultContext);

        $this->checkResultOfFix($fixResult);
    }

    public function testCheckAndFix_OtherDocCommentAfterDeclare() {
        $filePath = $this->getTestDirPath() . '/Rand.php';
        $context = ['filePath' => $filePath, 'baseDirPath' => dirname($filePath), 'ns' => self::class];
        $checkResult = $this->fixer->check($context);
        $this->assertInstanceOf(Err::class, $checkResult);
        $resultContext = $checkResult->val();
        $this->checkContext(
            array_merge(
                $context,
                [
                    'hasStmts'             => true,
                    'hasDeclare'           => true,
                    'hasValidDeclare'      => true,
                    'hasLicenseComment'    => false,
                    'nsCheckResult'        => new Ok(['expected' => $context['ns'], 'actual' => $context['ns']]),
                    'classTypeCheckResult' => new Ok(['expected' => 'Rand', 'actual' => 'Rand']),
                ]
            ),
            $resultContext
        );
        $fixResult = $this->fixer->fix($resultContext);

        $this->assertInstanceOf(Ok::class, $fixResult);

        $resultContext = $fixResult->val();
        $this->assertMatchesRegularExpression(
            $this->fileHeaderRe(self::class, LICENSE_COMMENT . "\n" . <<<'OUT'
/**
 * Pseudorandom number generator (PRNG)
 */
OUT
            ),
            $resultContext['text']
        );
        $this->assertSame(1, substr_count($resultContext['text'], LICENSE_COMMENT));
    }

    public function testCheckAndFix_MultipleNamespacesWithGlobal() {
        $filePath = $this->getTestDirPath() . '/autoload.php';
        $context = ['filePath' => $filePath, 'baseDirPath' => dirname($filePath), 'ns' => self::class];
        $checkResult = $this->fixer->check($context);
        $this->assertInstanceOf(Err::class, $checkResult);
        $resultContext = $checkResult->val();
        $this->checkContext(
            array_merge(
                $context,
                [
                    'hasStmts'             => true,
                    'hasDeclare'           => true,
                    'hasValidDeclare'      => true,
                    'hasLicenseComment'    => false,
                    'nsCheckResult'        => new Err(['expected' => $context['ns'], 'actual' => null]),
                    'classTypeCheckResult' => new Ok(['expected' => null, 'actual' => null]),
                ]
            ),
            $resultContext
        );
        $fixResult = $this->fixer->fix($resultContext);
        $this->checkResultOfFix($fixResult, '');
    }

    public function testCheckAndFix_FileWithoutDeclareStmt() {
        $filePath = $this->getTestDirPath() . '/NoDeclare.php';
        $context = ['filePath' => $filePath, 'baseDirPath' => dirname($filePath), 'ns' => self::class];
        $checkResult = $this->fixer->check($context);
        $this->assertInstanceOf(Err::class, $checkResult);
        $resultContext = $checkResult->val();
        $this->checkContext(
            array_merge(
                $context,
                [
                    'hasStmts'             => true,
                    'hasDeclare'           => false,
                    'hasValidDeclare'      => false,
                    'hasLicenseComment'    => false,
                    'nsCheckResult'        => new Ok(['expected' => $context['ns'], 'actual' => $context['ns']]),
                    'classTypeCheckResult' => new Ok(['expected' => 'NoDeclare', 'actual' => 'NoDeclare']),
                ]
            ),
            $resultContext
        );
        $fixResult = $this->fixer->fix($resultContext);
        $this->checkResultOfFix($fixResult, '');
    }

    public function testLicenseCommentAccessors() {
        $fixer = new PhpFileHeaderFixer();
        $this->checkAccessors([$fixer, 'licenseComment'], null, '/* foo */', $fixer);
    }

    public function testCheckAndFix_ValidFile_NullLicenseComment() {
        $fixer = new PhpFileHeaderFixer(null);

        $filePath = $this->getTestDirPath() . '/Foo.php';
        $context = ['filePath' => $filePath, 'baseDirPath' => dirname($filePath), 'ns' => self::class];

        $checkResult = $fixer->check($context);

        $this->assertInstanceOf(Ok::class, $checkResult);
        $resultContext = $checkResult->val();
        $this->assertTrue(!isset($resultContext['hasLicenseComment']));
        $this->checkCommonContextVals(
            array_merge(
                $context,
                [
                    'hasStmts'             => true,
                    'hasDeclare'           => true,
                    'hasValidDeclare'      => true,
                    'nsCheckResult'        => new Ok(['expected' => $context['ns'], 'actual' => $context['ns']]),
                    'classTypeCheckResult' => new Ok(['expected' => 'Foo', 'actual' => 'Foo']),
                ]
            ),
            $resultContext
        );

        $fixResult = $this->fixer->fix($resultContext);

        $this->assertInstanceOf(Ok::class, $fixResult);
        $this->assertTrue(!isset($fixResult->val()['text']));
    }

    public function testCheckAndFix_FileWithShebangWithoutDeclareWithInvalidNs() {
        $filePath = $this->getTestDirPath() . '/shebang-without-declare-with-invalid-ns.php';
        $context = ['filePath' => $filePath, 'baseDirPath' => dirname($filePath), 'ns' => self::class];

        $checkResult = $this->fixer->check($context);

        $this->assertInstanceOf(Err::class, $checkResult);
        $resultContext = $checkResult->val();
        $this->checkContext(
            array_merge(
                $context,
                [
                    'hasStmts'             => true,
                    'hasDeclare'           => false,
                    'hasValidDeclare'      => false,
                    'hasLicenseComment'    => false,
                    'nsCheckResult'        => new Err(['expected' => $context['ns'], 'actual' => 'Morpho\\Infra']),
                    'classTypeCheckResult' => new Ok(['expected' => null, 'actual' => null]),
                ]
            ),
            $resultContext
        );

        $fixResult = $this->fixer->fix($resultContext);

        $this->checkResultOfFix($fixResult, shebang: true);
    }

    public function testCheckAndFix_FileWithShebangWithDeclareWithoutNsWithNullLicense() {
        $filePath = $this->getTestDirPath() . '/shebang-with-declare-without-ns-null-license.php';
        $context = ['filePath' => $filePath, 'baseDirPath' => dirname($filePath), 'ns' => self::class];

        $fixer = new PhpFileHeaderFixer(null);

        $checkResult = $fixer->check($context);

        $this->assertInstanceOf(Err::class, $checkResult);
        $resultContext = $checkResult->val();

        $this->checkCommonContextVals(
            array_merge(
                $context,
                [
                    'hasStmts'             => true,
                    'hasDeclare'           => true,
                    'hasValidDeclare'      => true,
                    'nsCheckResult'        => new Err(['expected' => $context['ns'], 'actual' => null]),
                    'classTypeCheckResult' => new Ok(['expected' => null, 'actual' => null]),
                ]
            ),
            $resultContext
        );

        $fixResult = $fixer->fix($resultContext);

        $this->assertInstanceOf(Ok::class, $fixResult);
        $resultContext = $fixResult->val();
        $this->assertMatchesRegularExpression($this->fileHeaderRe(self::class, null, true), $resultContext['text']);
    }

    public function testCheck_InvalidNs_NsDirWithHyphen() {
        $filePath = $this->getTestDirPath() . '/foo-bar/Some.php';
        $context = [
            'filePath'    => $filePath,
            'baseDirPath' => dirname(dirname($filePath)),
            'ns'          => self::class,
        ];

        $checkResult = $this->fixer->check($context);

        $this->assertInstanceOf(Err::class, $checkResult);
        $resultContext = $checkResult->val();
        $this->checkContext(
            array_merge(
                $context,
                [
                    'hasStmts'             => true,
                    'hasDeclare'           => true,
                    'hasValidDeclare'      => true,
                    'hasLicenseComment'    => true,
                    'nsCheckResult'        => new Err(
                        ['expected' => $context['ns'] . '\\FooBar', 'actual' => self::class . '\\SomeInvalidNs']
                    ),
                    'classTypeCheckResult' => new Ok(['expected' => 'Some', 'actual' => 'Some']),
                ]
            ),
            $resultContext
        );
    }

    public function testFix_Enum() {
        $filePath = $this->getTestDirPath() . '/Enum.php';
        $context = [
            'filePath'    => $filePath,
            'baseDirPath' => dirname($filePath),
            'ns'          => self::class,
        ];
        $checkResult = $this->fixer->check($context);

        $this->assertInstanceOf(Err::class, $checkResult);
        $resultContext = $checkResult->val();
        $this->checkContext(
            array_merge(
                $context,
                [
                    'hasStmts'             => true,
                    'hasDeclare'           => true,
                    'hasValidDeclare'      => true,
                    'hasLicenseComment'    => false,
                    'nsCheckResult'        => new Ok(
                        [
                            'expected' => self::class,
                            'actual'   => self::class,
                        ],
                    ),
                    'classTypeCheckResult' => new Err(
                        [
                            'expected' => 'Enum',
                            'actual' => 'InvalidEnumName'
                        ]
                    ),
                ]
            ),
            $resultContext
        );
    }

    protected function setUp(): void {
        parent::setUp();
        $this->fixer = new PhpFileHeaderFixer(LICENSE_COMMENT);
    }

    private function checkContext(array $expectedContext, array $actualContext): void {
        $this->checkCommonContextVals($expectedContext, $actualContext);
        $this->assertSame($expectedContext['hasLicenseComment'], $actualContext['hasLicenseComment']);
    }

    private function checkCommonContextVals(array $expectedContext, array $actualContext): void {
        $this->assertSame($expectedContext['filePath'], $actualContext['filePath']);
        $this->assertSame($expectedContext['ns'], $actualContext['ns']);
        $this->assertSame($expectedContext['baseDirPath'], $actualContext['baseDirPath']);
        $this->assertSame($expectedContext['hasStmts'], $actualContext['hasStmts']);
        $this->assertSame($expectedContext['hasDeclare'], $actualContext['hasDeclare']);
        $this->assertSame($expectedContext['hasValidDeclare'], $actualContext['hasValidDeclare']);
        $this->assertEquals($expectedContext['nsCheckResult'], $actualContext['nsCheckResult']);
        $this->assertEquals($expectedContext['classTypeCheckResult'], $actualContext['classTypeCheckResult']);
    }

    private function checkResultOfFix(Result $fixResult, string $expectedNs = null, bool $shebang = false): void {
        $this->assertInstanceOf(Ok::class, $fixResult);
        $resultContext = $fixResult->val();
        $this->assertMatchesRegularExpression(
            $this->fileHeaderRe($expectedNs ?? self::class, LICENSE_COMMENT, $shebang),
            $resultContext['text']
        );
        $this->assertSame(1, substr_count($resultContext['text'], LICENSE_COMMENT));
    }

    private function fileHeaderRe(string $expectedNs, ?string $licenseComment, bool $shebang = false): string {
        return '~^'
            . ($shebang ? '#!/usr/bin/env\\s+php\\n' : '')
            . '\\<\\?php declare\\(strict_types=1\\);\\s+'
            . (null !== $licenseComment ? preg_quote($licenseComment, '~') . "\\s+" : '')
            . 'namespace ' . preg_quote($expectedNs, '~')
            . '~si';
    }
}
