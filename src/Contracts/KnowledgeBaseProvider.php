<?php

declare(strict_types=1);

namespace JeffersonGoncalves\FilamentHelpDesk\Contracts;

use Illuminate\Support\Collection;

/**
 * Bridge to whatever knowledge base backs article deflection on the ticket
 * creation form. A host application configures its own implementation via
 * `filament-help-desk.knowledge_base.provider` — nothing here depends on a
 * core Knowledge Base table, so this stays usable before one exists and after.
 */
interface KnowledgeBaseProvider
{
    /**
     * Search for articles that might already answer the query, optionally
     * scoped to a department. Each item's shape is up to the implementation;
     * the deflection card reads `title` and `url` off whatever it returns.
     *
     * @return Collection<int, mixed>
     */
    public function search(string $query, ?int $departmentId = null): Collection;
}
