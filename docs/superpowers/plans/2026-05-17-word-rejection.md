# Word Rejection Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let the admin reject a requested word from the request email and notify the requester their word was not added.

**Architecture:** Mirror the existing approve flow. A signed `dictionary.reject-word` route hits `RejectWordController`, which calls `RejectWordAdditionAction`. The action finds the requester via the Dictionary row's `requested_by_user_id` (exactly like `AddWordToDictionaryAction::notifyRequester`), sends `WordRejectedMail`, and clears the pending request. The admin `WordRequestedMail` gets a second "Reject Word" button.

**Tech Stack:** Laravel 12, Pest 4, Blade markdown mailables, signed URLs.

---

## File Structure

- Create `app/Mail/WordRejectedMail.php` — mailable sent to the requester. Mirrors `WordApprovedMail`.
- Create `resources/views/emails/word-rejected.blade.php` — mail body. Mirrors `word-approved.blade.php`.
- Create `app/Domain/Support/Actions/RejectWordAdditionAction.php` — domain logic: notify requester, clear pending flag.
- Create `app/Http/Controllers/Api/Dictionary/RejectWordController.php` — signed-route entry point. Mirrors `AddWordController`.
- Modify `routes/web.php` — register the signed reject route.
- Modify `resources/views/dictionary/action-confirmed.blade.php` — add a `rejected` branch.
- Modify `resources/views/emails/word-requested.blade.php` — add the "Reject Word" button.
- Create `tests/Feature/DictionaryRejectWordTest.php` — feature tests for the whole flow.

Note: another agent works on `main`. Stay on the `word-rejection` branch. Stage only the files listed in each commit step. Never run `git reset`, `git stash`, or `git add -A`.

---

## Task 1: RejectWordAdditionAction (with mail + view)

**Files:**
- Create: `app/Mail/WordRejectedMail.php`
- Create: `resources/views/emails/word-rejected.blade.php`
- Create: `app/Domain/Support/Actions/RejectWordAdditionAction.php`
- Test: `tests/Feature/DictionaryRejectWordTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/DictionaryRejectWordTest.php`:

```php
<?php

use App\Domain\Support\Actions\RejectWordAdditionAction;
use App\Domain\Support\Models\Dictionary;
use App\Domain\User\Models\User;
use App\Mail\WordRejectedMail;
use Illuminate\Support\Facades\Mail;

it('emails the requester and clears the pending request when rejected', function (): void {
    Mail::fake();

    $requester = User::factory()->create();

    Dictionary::create([
        'language' => 'nl',
        'word' => 'TESTWOORD',
        'is_valid' => false,
        'requested_by_user_id' => $requester->id,
    ]);

    app(RejectWordAdditionAction::class)->execute('TESTWOORD', 'nl');

    Mail::assertSent(WordRejectedMail::class, function (WordRejectedMail $mail) use ($requester) {
        return $mail->hasTo($requester->email)
            && $mail->word === 'TESTWOORD'
            && $mail->language === 'nl';
    });

    $dictionary = Dictionary::where('language', 'nl')->where('word', 'TESTWOORD')->first();

    expect($dictionary->requested_by_user_id)->toBeNull();
    expect($dictionary->is_valid)->toBeFalse();
    expect(Dictionary::where('word', 'TESTWOORD')->count())->toBe(1);
});

it('does not send mail when the row has no requester', function (): void {
    Mail::fake();

    Dictionary::create([
        'language' => 'nl',
        'word' => 'GEENREQUEST',
        'is_valid' => false,
    ]);

    app(RejectWordAdditionAction::class)->execute('GEENREQUEST', 'nl');

    Mail::assertNotSent(WordRejectedMail::class);
});

it('does nothing and does not error when no row exists', function (): void {
    Mail::fake();

    app(RejectWordAdditionAction::class)->execute('ONBEKEND', 'nl');

    Mail::assertNotSent(WordRejectedMail::class);
    expect(Dictionary::where('word', 'ONBEKEND')->exists())->toBeFalse();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=DictionaryRejectWordTest`
Expected: FAIL with `Class "App\Domain\Support\Actions\RejectWordAdditionAction" not found`.

- [ ] **Step 3: Create the mailable**

Create `app/Mail/WordRejectedMail.php`:

```php
<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WordRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $word,
        public string $language,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Your word was not added: {$this->word}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.word-rejected',
        );
    }
}
```

- [ ] **Step 4: Create the mail view**

Create `resources/views/emails/word-rejected.blade.php`:

```blade
<x-mail::message>
# Word Not Added

Your requested word **{{ $word }}** ({{ $language }}) was reviewed and has not been added to the dictionary.

Thanks for helping improve the dictionary.

</x-mail::message>
```

- [ ] **Step 5: Create the action**

Create `app/Domain/Support/Actions/RejectWordAdditionAction.php`:

```php
<?php

namespace App\Domain\Support\Actions;

use App\Domain\Support\Models\Dictionary;
use App\Mail\WordRejectedMail;
use Illuminate\Support\Facades\Mail;

class RejectWordAdditionAction
{
    public function execute(string $word, string $language): void
    {
        $word = mb_strtoupper(trim($word));

        $dictionary = Dictionary::query()
            ->where('language', $language)
            ->where('word', $word)
            ->first();

        if (! $dictionary) {
            return;
        }

        if (! $dictionary->requested_by_user_id) {
            return;
        }

        $requester = $dictionary->requestedBy;

        if (! $requester) {
            return;
        }

        Mail::to($requester->email)->send(new WordRejectedMail($word, $language));

        $dictionary->requested_by_user_id = null;
        $dictionary->save();
    }
}
```

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test --compact --filter=DictionaryRejectWordTest`
Expected: PASS (3 passed).

- [ ] **Step 7: Format and commit**

```bash
vendor/bin/pint app/Mail/WordRejectedMail.php app/Domain/Support/Actions/RejectWordAdditionAction.php tests/Feature/DictionaryRejectWordTest.php
git add app/Mail/WordRejectedMail.php resources/views/emails/word-rejected.blade.php app/Domain/Support/Actions/RejectWordAdditionAction.php tests/Feature/DictionaryRejectWordTest.php
git commit -m "Add RejectWordAdditionAction and WordRejectedMail"
```

---

## Task 2: Reject route and controller

**Files:**
- Create: `app/Http/Controllers/Api/Dictionary/RejectWordController.php`
- Modify: `routes/web.php` (add import after line 3; add route inside the existing `Route::middleware('signed')->group` block)
- Test: `tests/Feature/DictionaryRejectWordTest.php`

- [ ] **Step 1: Add the failing tests**

Append these tests to `tests/Feature/DictionaryRejectWordTest.php` (add `use Illuminate\Support\Facades\URL;` to the existing `use` block at the top of the file):

```php
it('can reject a word via signed url', function (): void {
    Mail::fake();

    $requester = User::factory()->create();

    Dictionary::create([
        'language' => 'nl',
        'word' => 'TESTWOORD',
        'is_valid' => false,
        'requested_by_user_id' => $requester->id,
    ]);

    $url = URL::signedRoute('dictionary.reject-word', [
        'word' => 'TESTWOORD',
        'language' => 'nl',
    ]);

    $response = $this->get($url);

    $response->assertOk();
    $response->assertViewIs('dictionary.action-confirmed');
    $response->assertViewHas('action', 'rejected');
    $response->assertViewHas('word', 'TESTWOORD');
    $response->assertViewHas('language', 'nl');

    Mail::assertSent(WordRejectedMail::class, fn (WordRejectedMail $mail) => $mail->hasTo($requester->email));
});

it('cannot reject a word without a valid signature', function (): void {
    $response = $this->get('/dictionary/reject-word?word=TESTWOORD&language=nl');

    $response->assertForbidden();
});

it('rejects an invalid language with 422', function (): void {
    $url = URL::signedRoute('dictionary.reject-word', [
        'word' => 'TESTWOORD',
        'language' => 'de',
    ]);

    $this->get($url)->assertStatus(422);
});

it('rejects a word shorter than two characters with 422', function (): void {
    $url = URL::signedRoute('dictionary.reject-word', [
        'word' => 'A',
        'language' => 'nl',
    ]);

    $this->get($url)->assertStatus(422);
});
```

- [ ] **Step 2: Run tests to verify the new ones fail**

Run: `php artisan test --compact --filter=DictionaryRejectWordTest`
Expected: the 3 Task 1 tests still PASS; the 4 new tests FAIL with `Route [dictionary.reject-word] not defined`.

- [ ] **Step 3: Create the controller**

Create `app/Http/Controllers/Api/Dictionary/RejectWordController.php`:

```php
<?php

namespace App\Http\Controllers\Api\Dictionary;

use App\Domain\Support\Actions\RejectWordAdditionAction;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RejectWordController
{
    public function __invoke(Request $request): View
    {
        $word = mb_strtoupper(trim($request->query('word', '')));
        $language = $request->query('language', '');

        abort_unless(in_array($language, ['nl', 'en']), 422);
        abort_unless(mb_strlen($word) >= 2, 422);

        app(RejectWordAdditionAction::class)->execute($word, $language);

        return view('dictionary.action-confirmed', [
            'action' => 'rejected',
            'word' => $word,
            'language' => $language,
        ]);
    }
}
```

- [ ] **Step 4: Register the route**

In `routes/web.php`, add this import directly after the existing line `use App\Http\Controllers\Api\Dictionary\AddWordController;`:

```php
use App\Http\Controllers\Api\Dictionary\RejectWordController;
```

Then, inside the existing `Route::middleware('signed')->group(function () { ... });` block, directly after the line `Route::get('/dictionary/add-word', AddWordController::class)->name('dictionary.add-word');`, add:

```php
Route::get('/dictionary/reject-word', RejectWordController::class)->name('dictionary.reject-word');
```

- [ ] **Step 5: Run tests to verify all pass**

Run: `php artisan test --compact --filter=DictionaryRejectWordTest`
Expected: PASS (7 passed).

- [ ] **Step 6: Format and commit**

```bash
vendor/bin/pint app/Http/Controllers/Api/Dictionary/RejectWordController.php routes/web.php tests/Feature/DictionaryRejectWordTest.php
git add app/Http/Controllers/Api/Dictionary/RejectWordController.php routes/web.php tests/Feature/DictionaryRejectWordTest.php
git commit -m "Add signed reject-word route and controller"
```

---

## Task 3: Confirmation page and admin mail button

**Files:**
- Modify: `resources/views/dictionary/action-confirmed.blade.php` (add an `@elseif($action === 'rejected')` branch before the existing `@else`)
- Modify: `resources/views/emails/word-requested.blade.php` (add a second button after the "Add Word" button)
- Test: `tests/Feature/DictionaryRejectWordTest.php`

- [ ] **Step 1: Add the failing tests**

Append to `tests/Feature/DictionaryRejectWordTest.php` (add `use App\Mail\WordRequestedMail;` to the top `use` block):

```php
it('shows the rejected confirmation page', function (): void {
    Mail::fake();

    $url = URL::signedRoute('dictionary.reject-word', [
        'word' => 'ONBEKEND',
        'language' => 'nl',
    ]);

    $response = $this->get($url);

    $response->assertOk();
    $response->assertSee('Word Rejected');
    $response->assertSee('ONBEKEND');
});

it('renders a reject button in the admin request mail', function (): void {
    $requester = User::factory()->create();

    $rendered = (new WordRequestedMail('TESTWOORD', 'nl', $requester))->render();

    expect($rendered)->toContain('/dictionary/reject-word');
    expect($rendered)->toContain('Reject Word');
});
```

- [ ] **Step 2: Run tests to verify the new ones fail**

Run: `php artisan test --compact --filter=DictionaryRejectWordTest`
Expected: `shows the rejected confirmation page` FAILS (does not see "Word Rejected", currently falls into the dismissed `@else` branch). `renders a reject button in the admin request mail` FAILS (no `/dictionary/reject-word` in output).

- [ ] **Step 3: Add the rejected branch to the confirmation view**

In `resources/views/dictionary/action-confirmed.blade.php`, find the existing `@else` that renders "Report Dismissed". Directly **before** that `@else` line, insert:

```blade
            @elseif($action === 'rejected')
                <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-6" style="background: rgba(239, 68, 68, 0.15);">
                    <svg class="w-8 h-8" style="color: #ef4444;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </div>

                <h1 class="text-2xl font-bold mb-3">Word Rejected</h1>

                <p style="color: var(--color-text-secondary); font-size: 1.125rem;">
                    <span class="font-semibold" style="color: var(--color-text-primary);">{{ $word }}</span>
                    ({{ $language }}) has not been added to the dictionary.
                </p>
```

- [ ] **Step 4: Add the reject button to the admin request mail**

In `resources/views/emails/word-requested.blade.php`, directly after the existing `</x-mail::button>` line (the "Add Word" button) and before the closing `</x-mail::message>`, insert:

```blade
<x-mail::button :url="URL::signedRoute('dictionary.reject-word', ['word' => $word, 'language' => $language])" color="error">
Reject Word
</x-mail::button>
```

- [ ] **Step 5: Run tests to verify all pass**

Run: `php artisan test --compact --filter=DictionaryRejectWordTest`
Expected: PASS (9 passed).

- [ ] **Step 6: Format and commit**

```bash
vendor/bin/pint tests/Feature/DictionaryRejectWordTest.php
git add resources/views/dictionary/action-confirmed.blade.php resources/views/emails/word-requested.blade.php tests/Feature/DictionaryRejectWordTest.php
git commit -m "Add rejected confirmation page and admin reject button"
```

---

## Task 4: Full suite regression check

**Files:** none (verification only)

- [ ] **Step 1: Run the full test suite**

Run: `php artisan test --compact`
Expected: all tests pass, 0 failed (skipped tests are pre-existing and acceptable). If anything fails, fix it before proceeding using superpowers:systematic-debugging.

- [ ] **Step 2: Confirm Pint is clean on touched files**

Run: `vendor/bin/pint --test app/Mail/WordRejectedMail.php app/Domain/Support/Actions/RejectWordAdditionAction.php app/Http/Controllers/Api/Dictionary/RejectWordController.php routes/web.php tests/Feature/DictionaryRejectWordTest.php`
Expected: PASS with no style issues. (This is the only allowed use of `--test`; do not commit from this step.)

---

## Self-Review Notes

- Spec coverage: `WordRejectedMail` (Task 1), `word-rejected.blade.php` (Task 1), `RejectWordAdditionAction` with the no-row / no-requester / no is_valid change / no delete behaviors (Task 1 tests), `RejectWordController` with nl/en + length guards (Task 2), signed route (Task 2), `action-confirmed` rejected branch (Task 3), `word-requested` reject button (Task 3), unsigned 403 and 422 cases (Task 2), admin mail renders reject link (Task 3). All spec requirements mapped.
- Type consistency: `RejectWordAdditionAction::execute(string $word, string $language): void` is used identically in Task 1 (direct call) and Task 2 (`app(RejectWordAdditionAction::class)->execute(...)` inside the controller). `WordRejectedMail(public string $word, public string $language)` constructor matches every usage.
- `color="error"` on `x-mail::button` is a standard Laravel mail component color; if the project's mail theme rejects it, drop the attribute (the button still renders and the test only checks the URL and label).
