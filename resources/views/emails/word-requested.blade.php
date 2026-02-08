<x-mail::message>
# Word Requested

A user has requested a word to be added to the dictionary.

**Word:** {{ $word }}

**Language:** {{ $language }}

**Requested by:** {{ $requester->username }} ({{ $requester->email }})

<x-mail::button :url="URL::signedRoute('dictionary.add-word', ['word' => $word, 'language' => $language])">
Add Word
</x-mail::button>

</x-mail::message>
