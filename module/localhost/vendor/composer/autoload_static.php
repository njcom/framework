<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit4d9f97f4e7d76bb3291edc277cf7f43a
{
    public static $prefixLengthsPsr4 = array (
        'M' => 
        array (
            'Morpho\\Site\\Localhost\\' => 22,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Morpho\\Site\\Localhost\\' => 
        array (
            0 => __DIR__ . '/../..' . '/lib',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit4d9f97f4e7d76bb3291edc277cf7f43a::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit4d9f97f4e7d76bb3291edc277cf7f43a::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit4d9f97f4e7d76bb3291edc277cf7f43a::$classMap;

        }, null, ClassLoader::class);
    }
}
