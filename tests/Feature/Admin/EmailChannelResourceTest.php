<?php

use JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\EmailChannelResource;
use JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\EmailChannelResource\Pages\CreateEmailChannel;
use JeffersonGoncalves\FilamentHelpDesk\Admin\Resources\EmailChannelResource\Pages\ListEmailChannels;
use JeffersonGoncalves\FilamentHelpDesk\Tests\Factories\UserFactory;
use JeffersonGoncalves\HelpDesk\Exceptions\EmailProcessingException;
use JeffersonGoncalves\HelpDesk\Mail\Drivers\ImapDriver;
use JeffersonGoncalves\HelpDesk\Mail\Drivers\MailgunDriver;
use JeffersonGoncalves\HelpDesk\Mail\Drivers\PostmarkDriver;
use JeffersonGoncalves\HelpDesk\Mail\Drivers\ResendDriver;
use JeffersonGoncalves\HelpDesk\Mail\Drivers\SendGridDriver;
use JeffersonGoncalves\HelpDesk\Models\EmailChannel;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->admin = UserFactory::new()->create();

    filament()->setCurrentPanel(filament()->getPanel('admin'));

    $this->actingAs($this->admin);
});

it('can render the email channel list page', function () {
    livewire(ListEmailChannels::class)
        ->assertSuccessful();
});

it('can render the create email channel page with the test-connection action in the form', function () {
    livewire(CreateEmailChannel::class)
        ->assertSuccessful();
});

it('resolves the driver instance matching the stored driver key', function (?string $driver, string $expected) {
    expect(EmailChannelResource::resolveDriver($driver))->toBeInstanceOf($expected);
})->with([
    'imap' => ['imap', ImapDriver::class],
    'mailgun' => ['mailgun', MailgunDriver::class],
    'sendgrid' => ['sendgrid', SendGridDriver::class],
    'resend' => ['resend', ResendDriver::class],
    'postmark' => ['postmark', PostmarkDriver::class],
    'unknown falls back to imap' => [null, ImapDriver::class],
]);

it('reports success without connecting for webhook-based drivers', function (string $driver) {
    $result = EmailChannelResource::resolveDriver($driver)
        ->testConnection(new EmailChannel(['driver' => $driver, 'settings' => []]));

    expect($result['success'])->toBeTrue()
        ->and($result['message'])->toContain('Webhook-based driver');
})->with(['mailgun', 'sendgrid', 'resend', 'postmark']);

it('surfaces the missing IMAP dependency instead of pretending the connection worked', function () {
    EmailChannelResource::resolveDriver('imap')
        ->testConnection(new EmailChannel(['driver' => 'imap', 'settings' => []]));
})->throws(EmailProcessingException::class);

it('notifies instead of crashing when the test-connection action hits a driver exception', function () {
    livewire(CreateEmailChannel::class)
        ->fillForm([
            'driver' => 'imap',
            'settings' => ['host' => 'imap.example.com', 'username' => 'user', 'password' => 'secret'],
        ])
        ->callFormComponentAction('testConnectionAction', 'testConnection')
        ->assertNotified();
});
