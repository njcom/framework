<?php declare(strict_types=1);
/**
 * This file is part of morpho-os/framework
 * It is distributed under the 'Apache License Version 2.0' license.
 * See the https://github.com/njcom/framework/blob/main/LICENSE for the full license text.
 */
namespace Morpho\Tech\Php;

/**
 * PHP types.
 */
enum Type: string {
    /*
    PHP types can be used in:
    * Property definition
    * Formal parameter type hint
    * Return type hint
    * Class name, interface name, enum name

    E.g. (using TypeScript's type notation):
        class TClassOrInterface {
            public TProperty $foo;
        }
        function foo(TParam $bar): TReturn {
            ...
        }
    where:
        TAll
            Union of all types (any possible type)

        TClassOrInterfaceOrEnum: ClassName | InterfaceName | EnumName (>= 8.1)
            Any class and interface name.

        TProperty: TScalar | array | object | iterable | self | parent | mixed | TClassOrInterfaceOrEnum | TUnion | TNullable
            Can be used in class definition as property type hint.
            object: contains TClassOrInterfaceOrEnum
            mixed: contains null, but null can't e used as standalone type hint here

        TParam: TProperty | static | callable
            Can be used in function definition as formal parameter type hint.

        TReturn: TParam | void | never (>= 8.1)
            Can be used in function definition as return type hint.

        TScalar: int | float | bool | string
            Scalar type.

        TUnion: TPropery
            Types which can be used in union, e.g. `int | bool`.
            todo: clarify types which can be used in union (`void` can't be used)

        TNullable: ?int | ?float | ?bool | ?string | ?array | ?object | ?TClassOrInterfaceOrEnum
            Some types can be nullable, i.e. null is valid value for the type, e.g. ?string = null

        TSpecial: resource | TScalar
            Special type, used only in PHP documentation or in some functions, e.g. is_scalar9).

        mixed: Exclude<TAll, void>
            Means any type.

        iterable: array | Traversable

        callable:
            'function'
            | [$obj, 'method']
            | ['Class', 'method']
            | self::class . "::method"
            | parent::class . "::method"
            | static::class . "::method"
            | [self::class, "method"]
            | [parent::class, "method"]
            | [static::class, "method"]
            | Closure
            | foo(...)
            | strlen(...)
            | $closure(...);
            | $invokableObject(...);
            | $obj->method(...);
            | $obj->$methodStr(...);
            | ($obj->property)(...);
            | Foo::method(...);
            | $classStr::$methodStr(...);
            | self::{$complex . $expression}(...);
            | 'strlen'(...);
            | [$obj, 'method'](...);
            | [Foo::class, 'method'](...);

            Any value which can be called with `call_user_func($val)`
            https://wiki.php.net/rfc/deprecate_partially_supported_callables
            https://wiki.php.net/rfc/first_class_callable_syntax
    */
    case Int = 'int';         // in TProperty, TParam, TReturn, TScalar, TNullable
    case Float = 'float';       // in TProperty, TParam, TReturn, TScalar, TNullable
    case Bool = 'bool';        // in TProperty, TParam, TReturn, TScalar, TNullable
    case String = 'string';      // in TProperty, TParam, TReturn, TScalar, TNullable
    case Null = 'null';        // in TNullable
    case Array = 'array';       // in TProperty, TParam, TReturn, TUnion, TNullable
    case Resource = 'resource';    // in TSpecial
    case Object = 'object';      // in TProperty, TParam, TReturn, TUnion, TNullable

    // todo vvv:
    case Iterable = 'iterable';    // in TProperty, TParam, TReturn
    case Callable = 'callable';    // in TParam, TNullable, TReturn
    case Self = 'self';        // in TProperty, TParam, TReturn
    case Public = 'parent';      // in TProperty, TParam, TReturn
    case Static = 'static';      // in TParam, TReturn
    case Void = 'void';        // in TReturn
    case Never = 'never';      // in TReturn
    case Mixed = 'mixed';       // in TProperty, TParam, TReturn
    //case CLASS_OR_INTERFACE = 'classin -interface'; // TProperty, TParam, TReturn, TClassOrInterface
    //case UNION = 'union';        // in TProperty, TParam, TReturn
}
