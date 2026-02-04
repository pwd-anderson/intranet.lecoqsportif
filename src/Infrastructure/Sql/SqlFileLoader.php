<?php
namespace App\Infrastructure\Sql;

final class SqlFileLoader
{
    public function __construct(
        private string $projectDir
    ) {}

    public function load(string $path): string
    {
        $fullPath = $this->projectDir . '/src/Infrastructure/Sql/' . ltrim($path, '/');

        if (!is_file($fullPath)) {
            throw new \RuntimeException("SQL file not found: $fullPath");
        }

        return file_get_contents($fullPath);
    }
}
