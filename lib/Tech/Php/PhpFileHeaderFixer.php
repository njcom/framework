<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Tech\Php;

use Morpho\Base\Err;
use Morpho\Base\IFn;
use Morpho\Base\Ok;
use Morpho\Base\Result;
use Morpho\Fs\Path;
use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

use function Morpho\Base\classify;
use function Morpho\Base\init;
use function Morpho\Base\last;
use function Morpho\Base\q;

class PhpFileHeaderFixer implements IFn {
    private ?string $licenseComment;

    /**
     * @param null|string $licenseComment If null then license will not be checked and fixed.
     */
    public function __construct(string $licenseComment = null) {
        $this->licenseComment = $licenseComment;
    }

    /**
     * @param null|string $licenseComment If null then license will not be checked and fixed.
     */
    public function setLicenseComment(?string $licenseComment): static {
        $this->licenseComment = $licenseComment;
        return $this;
    }

    /**
     * @param mixed $context
     * @return Result
     *     Ok: if fix was successful.
     *     Err: otherwise
     */
    public function __invoke(mixed $context): Result {
        $result = $this->check($context);
        if (!$result->isOk()) {
            if (!isset($context['shouldFix']) || $context['shouldFix']($result)) {
                /** @noinspection PhpIncompatibleReturnTypeInspection */
                return $this->fix($result->val())->map(
                    function ($context) {
                        if (!$context['dryRun']) {
                            file_put_contents($context['filePath'], $context['text']);
                        }
                        return $context;
                    }
                );
            } else {
                return new Ok($result->val());
            }
        }
        return $result;
    }

    /**
     * @param array $context
     * @return Result Err if the file has to be fixed later, and Ok otherwise.
     */
    public function check(array $context): Result {
        $nsCheckResult = $this->checkNs($context);
        $classTypeCheckResult = $this->checkClassType($context);
        $visitor = new class($this->licenseComment()) extends NodeVisitorAbstract {
            public bool $hasValidDeclare = false;
            public bool $hasDeclare = false;
            public bool $hasLicenseComment = false;
            public bool $hasStmts = false;
            private bool $checkLicenseComment = true;
            private ?string $licenseComment;

            public function __construct(?string $licenseComment) {
                $this->licenseComment = $licenseComment;
            }

            public function enterNode(Node $node): ?int {
                if ($node instanceof Node\Stmt) {
                    if (!$this->hasStmts && isShebangNode($node)) {
                        return null;
                    }
                    $this->hasStmts = true;
                    if ($node instanceof Node\Stmt\Declare_) {
                        $this->hasDeclare = true;
                        if (isset($node->declares[0]) && $node->declares[0] instanceof Node\Stmt\DeclareDeclare && $node->declares[0]->key->name === 'strict_types' && $node->declares[0]->value->value === 1) {
                            $this->hasValidDeclare = true;
                        }
                        return NodeTraverser::DONT_TRAVERSE_CURRENT_AND_CHILDREN;
                    } elseif ($this->checkLicenseComment && !$node instanceof Node\Stmt\Declare_) {
                        if (null == $this->licenseComment) {
                            return null;
                        }
                        // check that the first encountered statement except declare has the license comment
                        $licenseComment = trim($this->licenseComment);
                        foreach ($node->getComments() as $comment) {
                            if ($comment instanceof Comment\Doc && trim($comment->getText()) === $licenseComment) {
                                $this->hasLicenseComment = true;
                                $this->checkLicenseComment = false;
                                return null;
                            }
                        }
                        $this->checkLicenseComment = false; // Doesn't have valid license comment for the first encountered statement.
                    }
                }
                return null;
            }
        };
        visit($this->parse($context), [$visitor]);
        $resultContext = array_merge(
            $context,
            [
                'hasStmts'             => $visitor->hasStmts,
                'hasDeclare'           => $visitor->hasDeclare,
                'hasValidDeclare'      => $visitor->hasValidDeclare,
                'nsCheckResult'        => $nsCheckResult,
                'classTypeCheckResult' => $classTypeCheckResult,
            ]
        );
        if (null !== $this->licenseComment) {
            $resultContext['hasLicenseComment'] = $visitor->hasLicenseComment;
            $isValid = $visitor->hasValidDeclare && $nsCheckResult->isOk() && $classTypeCheckResult->isOk() && $visitor->hasLicenseComment;
        } else {
            $isValid = $visitor->hasValidDeclare && $nsCheckResult->isOk() && $classTypeCheckResult->isOk();
        }
        return $isValid ? new Ok($resultContext) : new Err($resultContext);
    }

    private function checkNs(array $context): Result {
        $relPath = Path::rel($context['baseDirPath'], $context['filePath']);
        $expectedNs = rtrim($context['ns'], '\\');
        $nsSuffix = init(str_replace('/', '\\', $relPath), '\\');
        if ($nsSuffix !== '') {
            $expectedNs .= '\\' . classify($nsSuffix);
        }
        foreach (self::namespaces($context['filePath']) as $ns) {
            // We are checking only the first namespace.
            if ($ns !== $expectedNs) {
                return new Err(['expected' => $expectedNs, 'actual' => $ns]);
            }
            return new Ok(['expected' => $expectedNs, 'actual' => $ns]);
        }
        return new Err(['expected' => $expectedNs, 'actual' => null]);
    }

    /**
     * @param string $filePath
     * @return iterable
     */
    private function namespaces(string $filePath): iterable {
        $rFile = new FileReflection($filePath);
        foreach ($rFile->namespaces() as $rNamespace) {
            (yield $rNamespace->name());
        }
    }

    private function checkClassType(array $context): Result {
        $mustHaveClasses = ctype_upper(ltrim(basename($context['filePath'], '_'))[0]);
        // Must have classes if filename starts with [A-Z]
        $filePath = $context['filePath'];
        $expectedClassName = Path::dropExt(basename($filePath));
        foreach (self::classes($filePath) as $className) {
            $shortClassName = last($className, '\\');
            if ($shortClassName !== $expectedClassName) {
                return new Err(['expected' => $expectedClassName, 'actual' => $shortClassName]);
            }
            // We are checking only the first class.
            return new Ok(['expected' => $expectedClassName, 'actual' => $shortClassName]);
        }
        if ($mustHaveClasses) {
            return new Err(['expected' => $expectedClassName, 'actual' => null]);
        }
        return new Ok(['expected' => null, 'actual' => null]);
    }

    /**
     * @param string $filePath
     * @return iterable
     */
    private function classes(string $filePath): iterable {
        return (new ClassTypeDiscoverer())->classTypesDefinedInFile($filePath);
    }

    public function licenseComment(): ?string {
        return $this->licenseComment;
    }

    private function parse(array $context): array {
        return isset($context['text']) ? parse($context['text']) : parseFile($context['filePath']);
    }

    public function fix(array $context): Result {
        if (!$context['hasStmts']) {
            return new Err("The file " . q($context['filePath']) . ' does not have PHP statements');
        }
        if ($context['hasDeclare']) {
            if (!$context['hasValidDeclare']) {
                return new Err(
                    array_merge(
                        $context,
                        [
                            'reason' => "Unable to fix declare() for the file " . q(
                                    $context['filePath']
                                ) . '. Reason: the file has unknown `declare` statement.',
                        ]
                    )
                );
            }
        } else {
            $context = $this->addDeclare($context);
        }
        if (!$context['classTypeCheckResult']->isOk()) {
            return new Err(
                array_merge(
                    $context,
                    [
                        'reason' => "Unable to fix the file " . q(
                                $context['filePath']
                            ) . '. Reason: the file contains invalid class(es).',
                    ]
                )
            );
        }
        if (!$context['nsCheckResult']->isOk()) {
            $context = $this->fixNs($context);
        }
        $context = $this->fixLicenseComment($context);
        return new Ok($context);
    }

    private function addDeclare(array $context): array {
        $nodes = $this->parse($context);
        $offset = 0;
        if (isset($nodes[0]) && isShebangNode($nodes[0])) {
            $offset++;
        }
        array_splice(
            $nodes,
            $offset,
            0,
            [
                new Node\Stmt\Declare_(
                    [
                        new Node\Stmt\DeclareDeclare(
                            new Node\Identifier('strict_types'), new Node\Scalar\LNumber(1)
                        ),
                    ]
                ),
            ]
        );
        $context['text'] = $this->ppFile($nodes);
        return $context;
    }

    private function ppFile(array $nodes): string {
        $text = ppFile($nodes);
        return preg_replace(
            '~^(#![^\\n\\r]+[\\n\\r]*)?\\<\\?php\\s+declare\\s*\\(\\s*strict_types\\s*=\\s*1\\s*\\)\\s*;~si',
            '\\1<?php declare(strict_types=1);',
            $text
        );
    }

    private function fixNs(array $context): array {
        $fix = $context['nsCheckResult']->val();
        $visitor = new class($fix) extends NodeVisitorAbstract {
            public bool $fixed = false;
            private array $fix;

            public function __construct(array $fix) {
                $this->fix = $fix;
            }

            public function enterNode(Node $node): ?int {
                if (!$this->fixed) {
                    if ($node instanceof Node\Stmt\Declare_) {
                        return NodeTraverser::DONT_TRAVERSE_CURRENT_AND_CHILDREN;
                    }
                    if ($node instanceof Node\Stmt\Namespace_) {
                        if ($node->name) {
                            $node->name->parts = explode('\\', $this->fix['expected']);
                            // fix only if non-global namespace.
                        }
                        $this->fixed = true;
                    }
                }
                return null;
            }
        };
        return $this->change(
            $context,
            [$visitor],
            afterVisit: function ($nodes, $visitors) use ($fix) {
            $visitor = $visitors[0];
            if (!$visitor->fixed) {
                if ($nodes) {
                    $offset = 0;
                    foreach ($nodes as $node) {
                        if ($node instanceof Node\Stmt\Declare_ || isShebangNode($node)) {
                            $offset++;
                        }
                    }
                    array_splice($nodes, $offset, 0, [new Node\Stmt\Namespace_(new Node\Name($fix['expected']))]);
                }
            }
            return $nodes;
        }
        );
    }

    /**
     * @param array $context
     * @param array $visitors
     * @param callable|null $beforeVisit
     * @param callable|null $afterVisit
     * @return array Modified $context.
     */
    private function change(
        array $context,
        array $visitors,
        callable $beforeVisit = null,
        callable $afterVisit = null
    ): array {
        $nodes = $this->parse($context);
        if ($beforeVisit) {
            $nodes = $beforeVisit($nodes, $visitors);
        }
        visit($nodes, $visitors);
        if ($afterVisit) {
            $nodes = $afterVisit($nodes, $visitors);
        }
        $context['text'] = $this->ppFile($nodes);
        return $context;
    }

    private function fixLicenseComment(array $context): array {
        if (!isset($context['hasLicenseComment'])) {
            return $context;
        }
        $visitor = new class($this->licenseComment()) extends NodeVisitorAbstract {
            private bool $licenseCommentRemoved = false;
            private bool $licenseCommentAdded = false;
            private bool $foundFirstStmt = false;

            public function __construct(private string $licenseComment) {
            }

            public function enterNode(Node $node): ?int {
                if ($node instanceof Node\Stmt || $node instanceof Node\Expr) {
                    if (!$this->licenseCommentRemoved) {
                        $this->licenseCommentRemoved = $this->removeLicenseComment($node);
                    }
                    if ($node instanceof Node\Stmt\Declare_) {
                        return NodeTraverser::DONT_TRAVERSE_CURRENT_AND_CHILDREN;
                    }
                    if ($node instanceof Node\Stmt) {
                        if (!$this->licenseCommentAdded) {
                            if (!$this->foundFirstStmt && isShebangNode($node)) {
                                return null;
                            }
                            $comments = $node->getComments();
                            array_unshift($comments, new Comment\Doc($this->licenseComment));
                            $node->setAttribute('comments', $comments);
                            $this->licenseCommentAdded = true;
                        }
                        $this->foundFirstStmt = true;
                    }
                }
                return null;
            }

            private function removeLicenseComment(Node $node): bool {
                $attributes = $node->getAttributes();
                $found = false;
                if (isset($attributes['comments'])) {
                    $licenseComment = trim($this->licenseComment);
                    foreach ($attributes['comments'] as $key => $comment) {
                        if ($comment instanceof Comment\Doc && trim($comment->getText()) === $licenseComment) {
                            unset($attributes['comments'][$key]);
                            $found = true;
                        }
                    }
                    if ($found) {
                        $attributes['comments'] = array_values($attributes['comments']);
                        $node->setAttributes($attributes);
                    }
                }
                return $found;
            }
        };
        return $this->change($context, [$visitor]);
    }
}