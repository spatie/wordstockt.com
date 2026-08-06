<?php

namespace App\Jobs;

use App\Domain\Support\Actions\GenerateWordRecommendationAction;
use App\Domain\Support\Data\WordRecommendationData;
use App\Domain\Support\Enums\DictionaryLanguage;
use App\Domain\User\Models\User;
use App\Mail\WordRequestedMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendWordRequestedMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    /** @var array<int, int> */
    public array $backoff = [10, 30];

    public function __construct(
        public string $word,
        public DictionaryLanguage $language,
        public User $requester,
    ) {}

    public function handle(GenerateWordRecommendationAction $generateWordRecommendation): void
    {
        $this->sendMail($generateWordRecommendation->execute($this->word, $this->language));
    }

    public function failed(?Throwable $exception): void
    {
        $this->sendMail(recommendation: null);
    }

    private function sendMail(?WordRecommendationData $recommendation): void
    {
        Mail::to('freek@spatie.be')->send(new WordRequestedMail(
            word: $this->word,
            language: $this->language->value,
            requester: $this->requester,
            recommendation: $recommendation,
        ));
    }
}
