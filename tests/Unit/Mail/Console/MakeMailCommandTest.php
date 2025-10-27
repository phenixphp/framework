<?php

declare(strict_types=1);

use Phenix\Filesystem\Contracts\File;
use Phenix\Testing\Mock;

it('creates mailable and view successfully', function (): void {
    $mock = Mock::of(File::class)->expect(
        exists: fn (string $path) => false,
        get: function (string $path) {
            if (str_contains($path, 'mailable.stub')) {
                return "<?php\n\nnamespace {namespace};\n\nuse Phenix\Mail\Mailable;\n\nclass {name} extends Mailable\n{\n    public function build(): self\n    {\n        return \$this->view('emails.{view}')\n            ->subject('Subject here');\n    }\n}\n";
            }
            if (str_contains($path, 'mail-view.stub')) {
                return "<!DOCTYPE html>\n<html>\n<head><title>{title}</title></head>\n<body><h1>{title}</h1></body>\n</html>\n";
            }

            return '';
        },
        put: function (string $path, string $content) {
            if (str_contains($path, 'app/Mail')) {
                expect($path)->toContain('app' . DIRECTORY_SEPARATOR . 'Mail' . DIRECTORY_SEPARATOR . 'WelcomeMail.php');
                expect($content)->toContain('namespace App\Mail');
                expect($content)->toContain('class WelcomeMail extends Mailable');
                expect($content)->toContain("->view('emails.welcome_mail')");
            }
            if (str_contains($path, 'resources/views/emails')) {
                expect($path)->toContain('resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'emails' . DIRECTORY_SEPARATOR . 'welcome_mail.php');
                expect($content)->toContain('<title>Welcome Mail</title>');
            }

            return true;
        },
        createDirectory: function (string $path): void {
            // ..
        }
    );

    $this->app->swap(File::class, $mock);

    /** @var \Symfony\Component\Console\Tester\CommandTester $command */
    $command = $this->phenix('make:mail', [
        'name' => 'WelcomeMail',
    ]);

    $command->assertCommandIsSuccessful();

    $display = $command->getDisplay();
    expect($display)->toContain('Mailable [app/Mail/WelcomeMail.php] successfully generated!');
    expect($display)->toContain('View [resources/views/emails/welcome_mail.php] successfully generated!');
});

it('does not create the mailable because it already exists', function (): void {
    $mock = Mock::of(File::class)->expect(
        exists: fn (string $path) => str_contains($path, 'app/Mail'),
    );

    $this->app->swap(File::class, $mock);

    /** @var \Symfony\Component\Console\Tester\CommandTester $command */
    $command = $this->phenix('make:mail', [
        'name' => 'WelcomeMail',
    ]);

    $command->assertCommandIsSuccessful();

    expect($command->getDisplay())->toContain('Mailable already exists!');
});

it('creates mailable with force option when it already exists', function (): void {
    $mock = Mock::of(File::class)->expect(
        exists: fn (string $path) => str_contains($path, 'app/Mail'),
        get: function (string $path) {
            if (str_contains($path, 'mailable.stub')) {
                return "<?php\n\nnamespace {namespace};\n\nuse Phenix\Mail\Mailable;\n\nclass {name} extends Mailable\n{\n    public function build(): self\n    {\n        return \$this->view('emails.{view}')\n            ->subject('Subject here');\n    }\n}\n";
            }
            if (str_contains($path, 'mail-view.stub')) {
                return "<!DOCTYPE html>\n<html>\n<head><title>{title}</title></head>\n<body><h1>{title}</h1></body>\n</html>\n";
            }

            return '';
        },
        put: fn (string $path, string $content) => true,
        createDirectory: function (string $path): void {
            // ..
        }
    );

    $this->app->swap(File::class, $mock);

    /** @var \Symfony\Component\Console\Tester\CommandTester $command */
    $command = $this->phenix('make:mail', [
        'name' => 'WelcomeMail',
        '--force' => true,
    ]);

    $command->assertCommandIsSuccessful();

    $display = $command->getDisplay();
    expect($display)->toContain('Mailable [app/Mail/WelcomeMail.php] successfully generated!');
    expect($display)->toContain('View [resources/views/emails/welcome_mail.php] successfully generated!');
});

it('creates mailable successfully in nested namespace', function (): void {
    $mock = Mock::of(File::class)->expect(
        exists: fn (string $path) => false,
        get: function (string $path) {
            if (str_contains($path, 'mailable.stub')) {
                return "<?php\n\nnamespace {namespace};\n\nuse Phenix\Mail\Mailable;\n\nclass {name} extends Mailable\n{\n    public function build(): self\n    {\n        return \$this->view('emails.{view}')\n            ->subject('Subject here');\n    }\n}\n";
            }
            if (str_contains($path, 'mail-view.stub')) {
                return "<!DOCTYPE html>\n<html>\n<head><title>{title}</title></head>\n<body><h1>{title}</h1></body>\n</html>\n";
            }

            return '';
        },
        put: function (string $path, string $content) {
            if (str_contains($path, 'app/Mail')) {
                expect($path)->toContain('app' . DIRECTORY_SEPARATOR . 'Mail' . DIRECTORY_SEPARATOR . 'Auth' . DIRECTORY_SEPARATOR . 'PasswordReset.php');
                expect($content)->toContain('namespace App\Mail\Auth');
                expect($content)->toContain('class PasswordReset extends Mailable');
                expect($content)->toContain("->view('emails.auth.password_reset')");
            }
            if (str_contains($path, 'resources/views/emails')) {
                expect($path)->toContain('resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'emails' . DIRECTORY_SEPARATOR . 'auth' . DIRECTORY_SEPARATOR . 'password_reset.php');
            }

            return true;
        },
        createDirectory: function (string $path): void {
            // ..
        }
    );

    $this->app->swap(File::class, $mock);

    /** @var \Symfony\Component\Console\Tester\CommandTester $command */
    $command = $this->phenix('make:mail', [
        'name' => 'Auth/PasswordReset',
    ]);

    $command->assertCommandIsSuccessful();

    $display = $command->getDisplay();
    expect($display)->toContain('Mailable [app/Mail/Auth/PasswordReset.php] successfully generated!');
    expect($display)->toContain('View [resources/views/emails/auth/password_reset.php] successfully generated!');
});

it('does not create view when it already exists but creates mailable', function (): void {
    $mock = Mock::of(File::class)->expect(
        exists: function (string $path) {
            return str_contains($path, 'resources/views/emails');
        },
        get: function (string $path) {
            if (str_contains($path, 'mailable.stub')) {
                return "<?php\n\nnamespace {namespace};\n\nuse Phenix\Mail\Mailable;\n\nclass {name} extends Mailable\n{\n    public function build(): self\n    {\n        return \$this->view('emails.{view}')\n            ->subject('Subject here');\n    }\n}\n";
            }

            return '';
        },
        put: function (string $path, string $content) {
            if (str_contains($path, 'app/Mail')) {
                return true;
            }

            return false;
        },
        createDirectory: function (string $path): void {
            // ..
        }
    );

    $this->app->swap(File::class, $mock);

    /** @var \Symfony\Component\Console\Tester\CommandTester $command */
    $command = $this->phenix('make:mail', [
        'name' => 'WelcomeMail',
    ]);

    $command->assertCommandIsSuccessful();

    $display = $command->getDisplay();
    expect($display)->toContain('Mailable [app/Mail/WelcomeMail.php] successfully generated!');
    expect($display)->toContain('View already exists!');
});
