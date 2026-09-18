<?php

use JeffersonGoncalves\FilamentHelpDesk\Contracts\KnowledgeBaseProvider;
use JeffersonGoncalves\FilamentHelpDesk\FilamentHelpDeskServiceProvider;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Fakes\FakeKnowledgeBaseProvider;
use JeffersonGoncalves\HelpDesk\Models\Ticket;

function rebindKnowledgeBaseProvider(): void
{
    $provider = app()->getProvider(FilamentHelpDeskServiceProvider::class);

    $method = new ReflectionMethod($provider, 'bindKnowledgeBaseProvider');
    $method->setAccessible(true);
    $method->invoke($provider);
}

it('is not bound by default, config disabled', function () {
    expect(app()->bound(KnowledgeBaseProvider::class))->toBeFalse();
});

it('is not bound when enabled but no provider class is configured', function () {
    config()->set('filament-help-desk.knowledge_base.enabled', true);
    config()->set('filament-help-desk.knowledge_base.provider', null);

    rebindKnowledgeBaseProvider();

    expect(app()->bound(KnowledgeBaseProvider::class))->toBeFalse();
});

it('is not bound when the configured class does not implement the interface', function () {
    config()->set('filament-help-desk.knowledge_base.enabled', true);
    config()->set('filament-help-desk.knowledge_base.provider', Ticket::class);

    rebindKnowledgeBaseProvider();

    expect(app()->bound(KnowledgeBaseProvider::class))->toBeFalse();
});

it('resolves the configured provider when enabled with a valid class', function () {
    config()->set('filament-help-desk.knowledge_base.enabled', true);
    config()->set('filament-help-desk.knowledge_base.provider', FakeKnowledgeBaseProvider::class);

    rebindKnowledgeBaseProvider();

    expect(app()->bound(KnowledgeBaseProvider::class))->toBeTrue()
        ->and(app(KnowledgeBaseProvider::class))->toBeInstanceOf(FakeKnowledgeBaseProvider::class);
});
