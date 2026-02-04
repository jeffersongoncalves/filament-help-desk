<?php

use JeffersonGoncalves\FilamentHelpDesk\Concerns\HasTicketForm;
use JeffersonGoncalves\FilamentHelpDesk\Concerns\HasTicketInfolist;
use JeffersonGoncalves\FilamentHelpDesk\Concerns\HasTicketTable;

// Create anonymous classes that use the traits so we can test the static methods.
function traitFormClass(): object
{
    return new class
    {
        use HasTicketForm;
    };
}

function traitTableClass(): object
{
    return new class
    {
        use HasTicketTable;
    };
}

function traitInfolistClass(): object
{
    return new class
    {
        use HasTicketInfolist;
    };
}

test('HasTicketForm::getTicketFormSchema() returns non-empty array', function () {
    $schema = traitFormClass()::getTicketFormSchema();

    expect($schema)->toBeArray()->not->toBeEmpty();
});

test('HasTicketForm::getTicketFormSchema(isUser: true) returns array', function () {
    $schema = traitFormClass()::getTicketFormSchema(isUser: true);

    expect($schema)->toBeArray()->not->toBeEmpty();
});

test('HasTicketForm::getTicketFormSchema(isUser: true) has fewer fields than default', function () {
    $schemaUser = traitFormClass()::getTicketFormSchema(isUser: true);
    $schemaFull = traitFormClass()::getTicketFormSchema(isUser: false);

    expect(count($schemaUser))->toBeLessThan(count($schemaFull));
});

test('HasTicketForm::getTicketEditFormSchema() returns non-empty array', function () {
    $schema = traitFormClass()::getTicketEditFormSchema();

    expect($schema)->toBeArray()->not->toBeEmpty();
});

test('HasTicketTable::getTicketTableColumns() returns non-empty array', function () {
    $columns = traitTableClass()::getTicketTableColumns();

    expect($columns)->toBeArray()->not->toBeEmpty();
});

test('HasTicketTable::getTicketTableColumns(showUser: false) has fewer columns', function () {
    $columnsWithUser = traitTableClass()::getTicketTableColumns(showUser: true);
    $columnsWithoutUser = traitTableClass()::getTicketTableColumns(showUser: false);

    expect(count($columnsWithoutUser))->toBeLessThan(count($columnsWithUser));
});

test('HasTicketTable::getTicketTableFilters() returns non-empty array', function () {
    $filters = traitTableClass()::getTicketTableFilters();

    expect($filters)->toBeArray()->not->toBeEmpty();
});

test('HasTicketInfolist::getTicketInfolistSchema() returns non-empty array', function () {
    $schema = traitInfolistClass()::getTicketInfolistSchema();

    expect($schema)->toBeArray()->not->toBeEmpty();
});
