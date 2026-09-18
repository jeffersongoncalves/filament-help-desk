<?php

use Illuminate\Database\Eloquent\Collection;
use JeffersonGoncalves\FilamentHelpDesk\Providers\CoreKnowledgeBaseProvider;
use JeffersonGoncalves\HelpDesk\Services\KnowledgeBaseService;
use Mockery\MockInterface;

it('delegates search to the core KnowledgeBaseService, passing query and department through', function () {
    $expected = new Collection(['an article']);

    /** @var KnowledgeBaseService&MockInterface $service */
    $service = Mockery::mock(KnowledgeBaseService::class);
    $service->shouldReceive('search')
        ->once()
        ->with(term: 'reset my password', departmentId: 3)
        ->andReturn($expected);

    $provider = new CoreKnowledgeBaseProvider($service);

    expect($provider->search('reset my password', 3))->toBe($expected);
});

it('passes a null department through unchanged', function () {
    /** @var KnowledgeBaseService&MockInterface $service */
    $service = Mockery::mock(KnowledgeBaseService::class);
    $service->shouldReceive('search')
        ->once()
        ->with(term: 'anything', departmentId: null)
        ->andReturn(new Collection([]));

    $provider = new CoreKnowledgeBaseProvider($service);

    $provider->search('anything');
});
