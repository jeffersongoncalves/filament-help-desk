<?php

declare(strict_types=1);

namespace JeffersonGoncalves\FilamentHelpDesk\Tests\Fakes;

use Illuminate\Support\Collection;
use JeffersonGoncalves\FilamentHelpDesk\Contracts\KnowledgeBaseProvider;

class FakeKnowledgeBaseProvider implements KnowledgeBaseProvider
{
    /** @var array<int, array{title: string, url?: string}> */
    public static array $results = [];

    public static ?string $lastQuery = null;

    public static ?int $lastDepartmentId = null;

    public function search(string $query, ?int $departmentId = null): Collection
    {
        static::$lastQuery = $query;
        static::$lastDepartmentId = $departmentId;

        return collect(static::$results);
    }
}
