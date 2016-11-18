<?php

use Sami\Sami;
use Sami\Version\Version;
use Symfony\Component\Finder\Finder;
use Sami\RemoteRepository\GitHubRemoteRepository;
use Sami\Parser\Filter\TrueFilter;

$iterator = Finder::create()
    ->files()
    ->name('*.php')
    ->exclude('resources')
    ->exclude('tests')
    ->exclude('app/Console')
    ->exclude('app/Exceptions')
    ->exclude('app/Providers')
    ->exclude('app/Http/Middleware')
    ->in($dir = __DIR__.'/app');

$version = new Version('develop', 'develop branch');

$sami = new Sami($iterator, array(
    'title' => 'Chronos API Documentation',
    'build_dir' => __DIR__.'/public/docs',
    'cache_dir' => __DIR__.'/.sami',
    'version' => $version,
    'default_opened_level' => 2,
    'remote_repository' => new GitHubRemoteRepository('Shmeve/soen343-emu', dirname($dir)),
));

$sami['filter'] = function () {
    return new TrueFilter();
};

return $sami;
