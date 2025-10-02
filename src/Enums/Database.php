<?php

declare(strict_types=1);

namespace Techieni3\StacktifyCli\Enums;

use Techieni3\StacktifyCli\Contracts\PromptSelectableEnum;
use Techieni3\StacktifyCli\Traits\BuildsPromptOptions;

enum Database: string implements PromptSelectableEnum
{
    use BuildsPromptOptions;

    case SQLite = 'sqlite';
    case MySQL = 'mysql';
    case MariaDB = 'mariadb';
    case PostgreSQL = 'pgsql';
    case SQLServer = 'sqlsrv';

    public static function default(): ?string
    {
        return self::SQLite->value;
    }

    public function label(): string
    {
        return match ($this) {
            self::SQLite => 'SQLite',
            self::MySQL => 'MySQL',
            self::MariaDB => 'MariaDB',
            self::PostgreSQL => 'PostgreSQL',
            self::SQLServer => 'SQL Server',
        };
    }

    public function isFileBased(): bool
    {
        return $this === self::SQLite;
    }

    public function defaultPort(): ?int
    {
        return match ($this) {
            self::MySQL, self::MariaDB => 3306,
            self::PostgreSQL => 5432,
            self::SQLServer => 1433,
            self::SQLite => null,
        };
    }

    public function driver(): string
    {
        return $this->value;
    }
}
