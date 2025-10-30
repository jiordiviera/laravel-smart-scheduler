<?php

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Models\SmartSchedulerRun;
use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Notifications\SchedulerRunFailedNotification;

beforeEach(function () {
    Artisan::command('smart-scheduler:failing-run', function () {
        throw new \RuntimeException('Boom');
    })->purpose('Simulated failing scheduler command');

    config()->set('smart-scheduler.wrapped_command', 'smart-scheduler:failing-run');
});

it('sends notifications when schedule run fails', function () {
    Notification::fake();
    Http::fake([
        'https://hooks.slack.com/services/*' => Http::response('ok'),
        'https://api.telegram.org/*' => Http::response('ok'),
    ]);

    config()->set('smart-scheduler.notifications.enabled', true);
    config()->set('smart-scheduler.notifications.channels', ['mail', 'slack', 'telegram']);
    config()->set('smart-scheduler.notifications.mail.to', 'ops@example.com');
    config()->set('smart-scheduler.notifications.slack.webhook_url', 'https://hooks.slack.com/services/foo/bar');
    config()->set('smart-scheduler.notifications.telegram.bot_token', 'test-token');
    config()->set('smart-scheduler.notifications.telegram.chat_id', '123456');

    $this->artisan('smart-schedule:run')->assertExitCode(Command::FAILURE);

    $run = SmartSchedulerRun::first();

    expect($run)->not->toBeNull();
    expect($run->status)->toBe(SmartSchedulerRun::STATUS_FAILED);
    expect($run->error_message)->toBe('Boom');

    Notification::assertSentOnDemand(SchedulerRunFailedNotification::class, function ($notification, $channels, $notifiable) {
        return in_array('mail', $channels, true);
    });

    Http::assertSentCount(2);

    Http::assertSent(function ($request) {
        $text = $request->data()['text'] ?? '';

        return str_contains($request->url(), 'https://hooks.slack.com/services')
            && str_contains($text, 'Environment: testing')
            && str_contains($text, 'Host:');
    });

    Http::assertSent(function ($request) {
        $text = $request->data()['text'] ?? '';

        return str_contains($request->url(), 'https://api.telegram.org/bot')
            && str_contains($text, 'Environment: testing')
            && str_contains($text, 'Host:');
    });
});

it('does not send notifications when disabled', function () {
    Notification::fake();
    config()->set('smart-scheduler.notifications.enabled', false);

    $this->artisan('smart-schedule:run')->assertExitCode(Command::FAILURE);

    Notification::assertNothingSent();
});
