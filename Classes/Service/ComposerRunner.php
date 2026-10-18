<?php

namespace WapplerSystems\ZabbixClient\Service;

/**
 * This file is part of the "zabbix_client" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use TYPO3\CMS\Core\Core\Environment;
use WapplerSystems\ZabbixClient\Exception\ComposerRunException;
use WapplerSystems\ZabbixClient\Utility\Configuration;

/**
 * Runs a composer command in the project root and returns its JSON output.
 *
 * Operations are called through the web server, whose environment differs from a shell:
 * HOME is usually not set (composer refuses to start without HOME or COMPOSER_HOME),
 * PATH is minimal and PHP_BINARY points to php-fpm or php-cgi instead of the CLI.
 * This class compensates for all three, so the command behaves as it does on the shell.
 */
class ComposerRunner
{
    public const DEFAULT_TIMEOUT = 50;

    /**
     * Detected PHP CLI per process; '' = searched without result.
     */
    protected static ?string $phpCli = null;

    /**
     * @param string[] $arguments composer arguments, e.g. ['audit', '--format=json']
     * @throws ComposerRunException
     */
    public function runJson(array $arguments, int $timeout = self::DEFAULT_TIMEOUT): array
    {
        $result = $this->run($arguments, $timeout);

        $data = $this->decodeJson($result['stdout']);
        if ($data === null) {
            throw new ComposerRunException(
                'composer ' . ($arguments[0] ?? '') . ' returned no JSON (exit code ' . $result['exitCode']
                . ', ' . $result['command'] . ')'
                . $this->describeError($result['stderr'] . "\n" . $result['stdout'])
            );
        }

        return $data;
    }

    /**
     * @param string[] $arguments
     * @return array{stdout: string, stderr: string, exitCode: int, command: string}
     * @throws ComposerRunException
     */
    public function run(array $arguments, int $timeout = self::DEFAULT_TIMEOUT): array
    {
        if (!$this->isFunctionAvailable('proc_open')) {
            throw new ComposerRunException('proc_open() is disabled');
        }

        $php = $this->findPhpCli();
        if ($php === null) {
            throw new ComposerRunException('No PHP CLI binary found');
        }
        $composer = $this->findComposer();
        if ($composer === null) {
            throw new ComposerRunException('No composer binary found');
        }

        // display_errors goes to stderr: older composer releases emit deprecation notices
        // on newer PHP versions, and on stdout they would corrupt the JSON document.
        $command = array_merge(
            [$php, '-d', 'display_errors=stderr', '-d', 'memory_limit=-1', $composer],
            $arguments,
            ['--no-interaction', '--no-ansi']
        );

        $process = proc_open(
            $command,
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            Environment::getProjectPath(),
            $this->buildEnvironment()
        );
        if (!is_resource($process)) {
            throw new ComposerRunException('Cannot start composer');
        }

        // Allow the PHP request to outlive the command; exec time is not counted on every
        // platform, but on Windows and some SAPIs it is.
        if ($this->isFunctionAvailable('set_time_limit')) {
            @set_time_limit($timeout + 10);
        }

        fclose($pipes[0]);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $stdout = '';
        $stderr = '';
        $deadline = microtime(true) + $timeout;
        $timedOut = false;

        while (true) {
            $read = [$pipes[1], $pipes[2]];
            $write = null;
            $except = null;
            $remaining = $deadline - microtime(true);
            if ($remaining <= 0) {
                $timedOut = true;
                break;
            }
            if (@stream_select($read, $write, $except, 0, (int)min(200000, $remaining * 1000000)) === false) {
                break;
            }
            foreach ($read as $pipe) {
                $chunk = fread($pipe, 65536);
                if ($pipe === $pipes[1]) {
                    $stdout .= (string)$chunk;
                } else {
                    $stderr .= (string)$chunk;
                }
            }
            if (feof($pipes[1]) && feof($pipes[2])) {
                break;
            }
        }

        if ($timedOut) {
            proc_terminate($process, 9);
        }
        $stdout .= (string)stream_get_contents($pipes[1]);
        $stderr .= (string)stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        if ($timedOut) {
            throw new ComposerRunException('composer ' . ($arguments[0] ?? '') . ' timed out after ' . $timeout . 's');
        }

        return [
            'stdout' => $stdout,
            'stderr' => $stderr,
            'exitCode' => $exitCode,
            'command' => $php . ' ' . $composer,
        ];
    }

    /**
     * Decodes the JSON document from a command output, tolerating leading noise.
     */
    public function decodeJson(string $output): ?array
    {
        $start = strcspn($output, '{[');
        if ($start >= strlen($output)) {
            return null;
        }

        $data = json_decode(substr($output, $start), true);

        return is_array($data) ? $data : null;
    }

    protected function buildEnvironment(): array
    {
        $environment = [];
        foreach (getenv() as $name => $value) {
            $environment[$name] = $value;
        }

        if (($environment['HOME'] ?? '') === '' && ($environment['COMPOSER_HOME'] ?? '') === '') {
            // Prefer the real home of the system user, so the global composer cache and
            // auth.json are shared with the shell. Fall back to a directory in var/.
            $home = $this->getUserHome();
            if ($home !== null) {
                $environment['HOME'] = $home;
            } else {
                $composerHome = Environment::getVarPath() . '/composer';
                if (!is_dir($composerHome)) {
                    @mkdir($composerHome, 0775, true);
                }
                $environment['COMPOSER_HOME'] = $composerHome;
            }
        }

        $path = $environment['PATH'] ?? '';
        $environment['PATH'] = implode(PATH_SEPARATOR, array_filter([
            $path,
            '/usr/local/bin',
            '/usr/bin',
            '/bin',
        ]));
        $environment['COMPOSER_NO_INTERACTION'] = '1';
        $environment['COMPOSER_DISABLE_XDEBUG_WARN'] = '1';

        return $environment;
    }

    protected function getUserHome(): ?string
    {
        if (!function_exists('posix_getpwuid') || !function_exists('posix_geteuid')) {
            return null;
        }
        $user = posix_getpwuid(posix_geteuid());
        $home = is_array($user) ? (string)($user['dir'] ?? '') : '';

        return $home !== '' && is_dir($home) && is_writable($home) ? $home : null;
    }

    public function findPhpCli(): ?string
    {
        $configured = $this->getConfiguredPath('php');
        if ($configured !== null) {
            return $configured;
        }

        if (PHP_SAPI === 'cli' && PHP_BINARY !== '') {
            return PHP_BINARY;
        }

        if (self::$phpCli !== null) {
            return self::$phpCli ?: null;
        }

        // Under php-fpm or php-cgi, PHP_BINARY is not usable for scripts. Look for the CLI of
        // the same PHP version first, so composer runs with the PHP version of the site.
        // The standard directories come first: on managed hosting PHP_BINDIR can be a cgi-bin
        // directory whose "php85" is a CGI wrapper. Every candidate is checked to be a CLI.
        $major = PHP_MAJOR_VERSION;
        $minor = PHP_MINOR_VERSION;
        $names = [
            'php' . $major . $minor,             // e.g. php85 (Hetzner)
            'php' . $major . '.' . $minor,       // e.g. php8.5 (Debian/Ubuntu)
            'php' . $major . $minor . '.bin.cli', // Hetzner managed hosting
            'php',
        ];
        $directories = array_unique(array_filter(['/usr/local/bin', '/usr/bin', PHP_BINDIR, dirname(PHP_BINARY)]));

        foreach ($names as $name) {
            foreach ($directories as $directory) {
                $candidate = $directory . '/' . $name;
                if (@is_file($candidate) && @is_executable($candidate) && $this->isPhpCli($candidate)) {
                    return self::$phpCli = $candidate;
                }
            }
        }

        self::$phpCli = '';

        return null;
    }

    /**
     * php-cgi answers "-r" with a CGI security alert instead of running the code.
     */
    protected function isPhpCli(string $binary): bool
    {
        if (!$this->isFunctionAvailable('proc_open')) {
            return false;
        }
        $process = @proc_open(
            [$binary, '-r', 'echo PHP_SAPI;'],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes
        );
        if (!is_resource($process)) {
            return false;
        }
        fclose($pipes[0]);
        $output = (string)stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        return proc_close($process) === 0 && trim($output) === 'cli';
    }

    public function findComposer(): ?string
    {
        $configured = $this->getConfiguredPath('composer');
        if ($configured !== null) {
            return $configured;
        }

        $candidates = [
            // composer/composer as a project dependency matches the project best
            Environment::getProjectPath() . '/vendor/composer/composer/bin/composer',
        ];
        $home = $this->getUserHome();
        if ($home !== null) {
            $candidates[] = $home . '/bin/composer';
            $candidates[] = $home . '/bin/composer.phar';
            $candidates[] = $home . '/.local/bin/composer';
        }
        $candidates[] = '/usr/local/bin/composer';
        $candidates[] = '/usr/bin/composer';

        foreach ($candidates as $candidate) {
            if (@is_file($candidate) && @is_readable($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @param string $key program key below "program" in the extension configuration
     */
    protected function getConfiguredPath(string $key): ?string
    {
        try {
            $config = Configuration::getExtConfiguration();
        } catch (\Throwable) {
            return null;
        }
        // Dotted keys from ext_conf_template.txt arrive as nested array ("program" => ["php" => ...]).
        $programs = $config['program.'] ?? $config['program'] ?? [];
        $path = trim((string)(is_array($programs) ? ($programs[$key] ?? '') : ''));

        return $path !== '' && @is_file($path) ? $path : null;
    }

    protected function isFunctionAvailable(string $function): bool
    {
        if (!function_exists($function)) {
            return false;
        }
        $disabled = array_map('trim', explode(',', (string)ini_get('disable_functions')));

        return !in_array($function, $disabled, true);
    }

    protected function describeError(string $stderr): string
    {
        $lines = array_filter(array_map('trim', explode("\n", $stderr)), static function (string $line): bool {
            return $line !== '' && !str_starts_with($line, 'Deprecat') && !str_starts_with($line, 'PHP Deprecat');
        });
        if ($lines === []) {
            return '';
        }

        return ': ' . mb_substr(implode(' ', array_slice($lines, 0, 3)), 0, 300);
    }
}
