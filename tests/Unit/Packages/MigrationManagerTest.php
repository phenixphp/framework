<?php

declare(strict_types=1);

use Phenix\App;
use Phenix\Database\MigrationManager;
use Phenix\Packages\PackageRegistry;
use Phinx\Config\Config;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

it('prefers published migration files while retaining other package migrations', function (): void {
    $root = base_path('storage/framework/testing/migration-manager-' . uniqid());
    $source = "{$root}/package";
    $destination = "{$root}/application";

    mkdir($source, 0755, true);
    mkdir($destination, 0755, true);

    $name = '20240101000000_create_posts.php';

    file_put_contents("{$source}/{$name}", '<?php');
    file_put_contents("{$destination}/{$name}", '<?php');
    file_put_contents("{$source}/20240101000001_create_tags.php", '<?php');

    App::make(PackageRegistry::class)->addPublishPaths('MigrationProvider', [$source => $destination]);

    $config = new Config(['paths' => ['migrations' => [$source, $destination]], 'environments' => []]);
    $manager = new class($config, new ArrayInput([]), new NullOutput()) extends MigrationManager {
        public function files(): array
        {
            return $this->getMigrationFiles();
        }
    };

    $files = $manager->files();

    expect($files)->toContain("{$destination}/{$name}")
        ->not->toContain("{$source}/{$name}")
        ->toContain("{$source}/20240101000001_create_tags.php");

    unlink("{$source}/{$name}");
    unlink("{$destination}/{$name}");
    unlink("{$source}/20240101000001_create_tags.php");
    rmdir($source);
    rmdir($destination);
    rmdir($root);
});
