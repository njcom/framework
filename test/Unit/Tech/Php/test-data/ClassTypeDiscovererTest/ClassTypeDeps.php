<?php
namespace Morpho\Test\Unit\Tech\Php\ClassTypeDiscovererTest;

trait TFourth {
    use TraitUsesTrait;
}

function test(int $a, FunctionDefinitionHasParameterWithType $b): FunctionDefinitionHasReturnType {
}

interface IThird extends ExtendsInterfaceA, ExtendsInterfaceB {
}

enum Some {

}

class DefinedInTheSameFile {}

class Service extends DefinedInTheSameFile {
    private $logger;

    public function __construct(
        Logger $logger = new NullLogger(), // [New in initializer](https://www.php.net/releases/8.1/en.php#new_in_initializers)
    ) {
        $this->logger = $logger;
    }
}

class First extends ClassExtends implements ClassImplementsA, ClassImplementsB {
    use ClassUsesTrait;
    
    public ClassHasPublicProperty $pub;
    protected ClassHasProtectedProperty $prot;
    private ClassHasPrivateProperty $priv;

    const PING_PONG = 123;
    private static $pingPong;

    public function __construct() {
        new InstantiatesNewObject();

        CallsMethodStatically::some();

        if (ReadsStaticProperty::$foo) {
        }
        WritesStaticProperty::$foo = 'bar';

        try {
        } catch (CatchesExceptionA $e) {
        } catch (CatchesExceptionB $e) {
        }

        $v = '123';
        if ($v instanceof AppliesInstanceOfOperator) {
        }

        if (ReadsClassConstant::SOME) {
        }

        new class extends AnonymousClassExtends implements AnonymousClassImplementsA, AnonymousClassImplementsB {
        };

        function (
            $a,
            AnonymousFunctionDefinitionHasParameterWithType $b
        ): AnonymousFunctionDefinitionHasReturnType {
        }

        ;
    }

    public function skipMe() {
        // The next statements should not be included in result.
        self::doSomething();
        self::PING_PONG;
        self::$pingPong = '456';

        static::doSomething();
        static::PING_PONG;

        static::$pingPong = '456';

        $t = new self();
        $t::PING_PONG;

        $t::doSomething();

        $t = new static();
        $t::PING_PONG;
    }

    public static function doSomething() {
    }

    public function foo(
        int $a,
        MethodDefinitionHasParameterWithType $b,
        self $c,
        $d = self::PING_PONG
    ): MethodDefinitionHasReturnType {
    }

    public function bar(): string {
        return '123';
    }
}

class Second {
    public function __construct(string $a, ConstructorDefinitionHasParameterWithType $b) {
    }
}
