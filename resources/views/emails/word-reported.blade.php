<x-mail::message>
# Word Reported

A word has been reported as invalid.

**Word:** {{ $dictionary->word }}

**Language:** {{ $dictionary->language }}

**Reported by:** {{ $reporter->username }} ({{ $reporter->email }})

<x-mail::button :url="URL::signedRoute('dictionary.invalidate', $dictionary)" color="error">
Mark as Invalid
</x-mail::button>

<x-mail::button :url="URL::signedRoute('dictionary.dismiss', $dictionary)" color="secondary">
Keep Word
</x-mail::button>

</x-mail::message>
