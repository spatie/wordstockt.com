<?php

use App\Flare\CloudFlareSender;
use Spatie\FlareClient\Senders\DaemonSender;
use Spatie\LaravelFlare\Senders\LaravelHttpSender;

it('uses direct delivery inside managed queue workers', function (string $command) {
    $arguments = $_SERVER['argv'];

    try {
        $_SERVER['argv'] = ['artisan', $command];
        $sender = new CloudFlareSender(['daemon_url' => 'http://127.0.0.1:8787']);

        expect((new ReflectionProperty($sender, 'sender'))->getValue($sender))
            ->toBeInstanceOf(LaravelHttpSender::class);
    } finally {
        $_SERVER['argv'] = $arguments;
    }
})->with(['queue:work', 'queue:listen', 'horizon', 'horizon:work']);

it('uses the local daemon outside managed queue workers', function () {
    $arguments = $_SERVER['argv'];

    try {
        $_SERVER['argv'] = ['artisan', 'schedule:run'];
        $sender = new CloudFlareSender(['daemon_url' => 'http://127.0.0.1:8787']);

        expect((new ReflectionProperty($sender, 'sender'))->getValue($sender))
            ->toBeInstanceOf(DaemonSender::class);
    } finally {
        $_SERVER['argv'] = $arguments;
    }
});
