<?php

use Sami\Sami;
use Sami\Version\Version;
use Symfony\Component\Finder\Finder;
use Sami\RemoteRepository\GitHubRemoteRepository;
use Sami\Parser\Filter\TrueFilter;

$iterator = Finder::create()
    ->files()
    ->name('*.php')
    ->exclude('Console')
    ->exclude('Exceptions')
    ->exclude('Providers')
    ->exclude('Http/Middleware')
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

class IgnoreFilter extends TrueFilter
{
    public function acceptClass(\Sami\Reflection\ClassReflection $class)
    {
        return empty($class->getTags('ignore')) && parent::acceptClass($class);
    }

    public function acceptMethod(\Sami\Reflection\MethodReflection $method)
    {
        return empty($method->getTags('ignore')) && parent::acceptMethod($method);
    }

    public function acceptProperty(\Sami\Reflection\PropertyReflection $property)
    {
        return empty($property->getTags('ignore')) && parent::acceptProperty($property);
    }
}

$sami['filter'] = function () {
    return new IgnoreFilter();
};

return $sami;
