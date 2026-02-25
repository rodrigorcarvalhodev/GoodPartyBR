<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Testes de infraestrutura para validar que o ambiente Docker
 * está configurado corretamente.
 *
 * Rodar: php artisan test --filter=InfraTest
 */
class InfraTest extends TestCase
{
    // -------------------------------------------------------
    // PHP
    // -------------------------------------------------------

    public function test_php_version_is_8_3(): void
    {
        $this->assertTrue(
            version_compare(PHP_VERSION, '8.3.0', '>='),
            'PHP version must be >= 8.3.0, got: ' . PHP_VERSION
        );
    }

    public function test_required_php_extensions_are_loaded(): void
    {
        $required = [
            'bcmath',
            'ctype',
            'curl',
            'dom',
            'exif',
            'fileinfo',
            'gd',
            'intl',
            'mbstring',
            'openssl',
            'pcntl',
            'pdo',
            'pdo_mysql',
            'sockets',
            'xml',
            'zip',
        ];

        foreach ($required as $ext) {
            $this->assertTrue(
                extension_loaded($ext),
                "PHP extension '{$ext}' is not loaded."
            );
        }
    }

    public function test_swoole_extension_is_loaded(): void
    {
        $this->assertTrue(
            extension_loaded('swoole'),
            'Swoole extension is not loaded.'
        );
    }

    public function test_opcache_is_enabled(): void
    {
        $this->assertTrue(
            extension_loaded('Zend OPcache'),
            'OPcache is not loaded.'
        );
    }

    // -------------------------------------------------------
    // Timezone
    // -------------------------------------------------------

    public function test_php_timezone_is_sao_paulo(): void
    {
        $this->assertEquals(
            'America/Sao_Paulo',
            date_default_timezone_get(),
            'PHP timezone must be America/Sao_Paulo.'
        );
    }

    public function test_system_timezone_is_sao_paulo(): void
    {
        $timezone = trim(file_get_contents('/etc/timezone'));

        $this->assertEquals(
            'America/Sao_Paulo',
            $timezone,
            'System timezone (/etc/timezone) must be America/Sao_Paulo.'
        );
    }

    // -------------------------------------------------------
    // MySQL
    // -------------------------------------------------------

    public function test_mysql_connection_is_successful(): void
    {
        try {
            // Usamos valores fixos ou getenv() para ignorar as configurações de teste do Laravel
            $host = getenv('DB_HOST') ?: 'mysql';
            $database = getenv('DB_DATABASE') ?: 'goodparty';
            $username = getenv('DB_USERNAME') ?: 'goodparty';
            $password = getenv('DB_PASSWORD') ?: 'secret';
            $port = getenv('DB_PORT') ?: 3306;

            // Correção para evitar o ":memory:" do ambiente de teste do Laravel
            if ($database === ':memory:') {
                $database = 'goodparty';
            }

            $dsn = "mysql:host={$host};port={$port};dbname={$database}";
            $pdo = new \PDO($dsn, $username, $password);
            
            $this->assertInstanceOf(\PDO::class, $pdo);
        } catch (\Exception $e) {
            $this->fail('MySQL connection failed: ' . $e->getMessage());
        }
    }

    // -------------------------------------------------------
    // Redis
    // -------------------------------------------------------

    public function test_redis_connection_is_successful(): void
    {
        $host = env('REDIS_HOST', 'redis');
        $port = (int) env('REDIS_PORT', 6379);

        $connection = @fsockopen($host, $port, $errno, $errstr, 3);

        if (is_resource($connection)) {
            fclose($connection);
            $this->assertTrue(true);
        } else {
            $this->fail("Redis connection failed on {$host}:{$port} — {$errstr}");
        }
    }

    public function test_redis_responds_to_ping(): void
    {
        $host = env('REDIS_HOST', 'redis');
        $port = (int) env('REDIS_PORT', 6379);

        $socket = @fsockopen($host, $port, $errno, $errstr, 3);
        $this->assertNotFalse($socket, "Cannot connect to Redis: {$errstr}");

        fwrite($socket, "PING\r\n");
        $response = trim(fgets($socket));
        fclose($socket);

        $this->assertEquals('+PONG', $response, 'Redis did not respond with PONG.');
    }

    // -------------------------------------------------------
    // Octane / Swoole
    // -------------------------------------------------------

    public function test_octane_server_is_running(): void
    {
        $connection = @fsockopen('127.0.0.1', 8000, $errno, $errstr, 2);

        if (is_resource($connection)) {
            fclose($connection);
            $this->assertTrue(true);
        } else {
            $this->fail("Octane/Swoole is not listening on port 8000: {$errstr}");
        }
    }

    // -------------------------------------------------------
    // HTTP (Nginx → Swoole)
    // -------------------------------------------------------

    public function test_http_response_is_200(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
    }

    // -------------------------------------------------------
    // Tools
    // -------------------------------------------------------

    public function test_composer_is_installed(): void
    {
        $output = shell_exec('composer --version 2>&1');

        $this->assertNotNull($output, 'Composer is not installed.');
        $this->assertStringContainsString('Composer', $output);
    }
}
