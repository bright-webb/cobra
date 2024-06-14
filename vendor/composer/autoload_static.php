<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit250d13e4e8060b4ae02c17c52a1007c5
{
    public static $files = array (
        'ffef30895802e5f527fdf15582be23d6' => __DIR__ . '/../..' . '/app/helper.php',
    );

    public static $prefixLengthsPsr4 = array (
        'C' => 
        array (
            'Cobra\\' => 6,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Cobra\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit250d13e4e8060b4ae02c17c52a1007c5::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit250d13e4e8060b4ae02c17c52a1007c5::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit250d13e4e8060b4ae02c17c52a1007c5::$classMap;

        }, null, ClassLoader::class);
    }
}
