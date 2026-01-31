<?php

namespace App\Http\Controllers\Web;

use App\Domain\User\Models\GameInviteLink;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InviteLinkRedirectController
{
    public function __invoke(Request $request, string $code): View|RedirectResponse
    {
        $link = GameInviteLink::with('inviter')->where('code', $code)->first();

        if ($this->isInvalidLink($link)) {
            return redirect('/')->with('error', 'Invalid or expired invite link.');
        }

        $userAgent = $request->userAgent() ?? '';
        $appStoreUrl = $this->getAppStoreUrl($userAgent);

        return view('invite', [
            'inviterName' => $link->inviter->username ?? 'A friend',
            'appStoreUrl' => $appStoreUrl,
            'iosUrl' => config('app.ios_app_store_url'),
            'androidUrl' => config('app.android_play_store_url'),
            'autoRedirect' => ! $this->isCrawler($userAgent),
        ]);
    }

    private function getAppStoreUrl(string $userAgent): string
    {
        if ($this->isIos($userAgent)) {
            return config('app.ios_app_store_url');
        }

        if ($this->isAndroid($userAgent)) {
            return config('app.android_play_store_url');
        }

        return config('app.ios_app_store_url');
    }

    private function isInvalidLink(?GameInviteLink $link): bool
    {
        if (! $link) {
            return true;
        }

        return $link->isUsed();
    }

    private function isIos(string $userAgent): bool
    {
        $iosIdentifiers = ['iPhone', 'iPad', 'iPod'];

        foreach ($iosIdentifiers as $identifier) {
            if (str_contains($userAgent, $identifier)) {
                return true;
            }
        }

        return false;
    }

    private function isAndroid(string $userAgent): bool
    {
        return str_contains($userAgent, 'Android');
    }

    private function isCrawler(string $userAgent): bool
    {
        $crawlers = [
            'facebookexternalhit',
            'Twitterbot',
            'LinkedInBot',
            'WhatsApp',
            'Slackbot',
            'TelegramBot',
            'Discordbot',
            'Googlebot',
            'bingbot',
        ];

        return array_any($crawlers, fn ($crawler): bool => stripos($userAgent, (string) $crawler) !== false);
    }
}
