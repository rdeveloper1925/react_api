<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitea2475f21a776a23f807022b8552f73b
{
    public static $prefixLengthsPsr4 = array (
        'P' => 
        array (
            'Psr\\Log\\' => 8,
            'Pecee\\' => 6,
        ),
        'K' => 
        array (
            'Katzgrau\\KLogger\\' => 17,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Psr\\Log\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/log/Psr/Log',
        ),
        'Pecee\\' => 
        array (
            0 => __DIR__ . '/..' . '/pecee/simple-router/src/Pecee',
        ),
        'Katzgrau\\KLogger\\' => 
        array (
            0 => __DIR__ . '/..' . '/katzgrau/klogger/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'Katzgrau\\KLogger\\Logger' => __DIR__ . '/..' . '/katzgrau/klogger/src/Logger.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitea2475f21a776a23f807022b8552f73b::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitea2475f21a776a23f807022b8552f73b::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitea2475f21a776a23f807022b8552f73b::$classMap;

        }, null, ClassLoader::class);
    }
}
