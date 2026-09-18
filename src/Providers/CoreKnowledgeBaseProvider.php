<?php

declare(strict_types=1);

namespace JeffersonGoncalves\FilamentHelpDesk\Providers;

use Illuminate\Support\Collection;
use JeffersonGoncalves\FilamentHelpDesk\Contracts\KnowledgeBaseProvider;
use JeffersonGoncalves\HelpDesk\Services\KnowledgeBaseService;

/**
 * Default bridge from the KnowledgeBaseProvider contract to the core
 * package's own Knowledge Base (laravel-help-desk ^1.10). Bound by default
 * — see config('filament-help-desk.knowledge_base') — so an app using the
 * full ecosystem gets deflection working with nothing to configure. An app
 * with its own FAQ/KB overrides `knowledge_base.provider`, unaffected.
 */
class CoreKnowledgeBaseProvider implements KnowledgeBaseProvider
{
    public function __construct(protected KnowledgeBaseService $service) {}

    public function search(string $query, ?int $departmentId = null): Collection
    {
        return $this->service->search(term: $query, departmentId: $departmentId);
    }
}
