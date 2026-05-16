# Word rejection design

## Goal

Let the admin reject a requested word directly from the request email, and notify the requester that their word was not added to the dictionary. This mirrors the existing approve flow, including its limitation: if no Dictionary row exists for the word, no requester email is sent.

## Context

Today the flow is:

1. `RequestWordAdditionAction` emails the admin (`WordRequestedMail` to freek@spatie.be) with a signed "Add Word" button.
2. The button points at the signed route `dictionary.add-word`, handled by `AddWordController`, which calls `AddWordToDictionaryAction`.
3. `AddWordToDictionaryAction` adds the word, and (recent change) emails the requester via `WordApprovedMail`, then clears `requested_by_user_id`.

The requester is found by reading the Dictionary row's `requested_by_user_id`. `RequestWordAdditionAction` only sets that column on an existing row, so brand new words with no row never persist a requester. The reject flow mirrors this behavior on purpose.

## New components

All new components follow their existing siblings.

### WordRejectedMail

Location `app/Mail/`. Mirrors `WordApprovedMail`.

* Constructor `__construct(public string $word, public string $language)`.
* Subject like `Your word was not added: {word}`.
* Markdown view `emails.word-rejected`.

### emails.word-rejected.blade.php

Mirrors `word-approved.blade.php`. A short message that the requested word was reviewed and was not added to the dictionary.

### RejectWordAdditionAction

Location `app/Domain/Support/Actions/`. Signature `execute(string $word, string $language): void`.

* Look up the Dictionary row by language and word.
* If the row exists and has `requested_by_user_id`, send `WordRejectedMail` to `requestedBy`, then clear `requested_by_user_id` and save. This is an exact mirror of `AddWordToDictionaryAction::notifyRequester`.
* It does not change `is_valid`. A rejected word simply stays not added.
* It does not delete the row.
* If there is no row, or no requester on the row, it sends no email and returns without error.

### RejectWordController

Location `app/Http/Controllers/Api/Dictionary/`, matching where `AddWordController` lives.

* Read `word` and `language` query parameters.
* Guard with `abort_unless` for language in (nl, en) and word length at least 2, identical to `AddWordController`.
* Call `RejectWordAdditionAction`.
* Return the `dictionary.action-confirmed` view with `action` set to `rejected`.

### Route

Add to the existing `signed` middleware group in `routes/web.php`:

```
Route::get('/dictionary/reject-word', RejectWordController::class)->name('dictionary.reject-word');
```

### action-confirmed.blade.php

Add an `@elseif($action === 'rejected')` branch using the red cross styling already used for `invalidated`. Text: "Word Rejected" and "{word} ({language}) has not been added to the dictionary."

### word-requested.blade.php

Add a second `x-mail::button` labelled "Reject Word" below the existing "Add Word" button, pointing at `URL::signedRoute('dictionary.reject-word', ['word' => $word, 'language' => $language])`.

## Testing

Mirror `DictionaryAddWordTest.php`, using `Mail::fake()`.

* Rejecting a requested word sends `WordRejectedMail` to the requester and clears `requested_by_user_id`.
* Rejecting when the row has no requester, or when no row exists, sends no mail but still returns the confirmation page.
* Rejecting does not change `is_valid` and does not delete the row.
* An unsigned request returns 403.
* An invalid language or a word shorter than 2 characters returns 422.
* `WordRequestedMail` renders the signed reject link or button.
