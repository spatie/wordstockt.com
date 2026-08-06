@php($verdict = $recommendation->recommendation)
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="border-left: {{ $verdict->accentColor() }} solid 4px; margin: 21px 0;">
<tr>
<td style="background-color: {{ $verdict->backgroundColor() }}; padding: 16px;">
<p style="margin: 0 0 12px; color: {{ $verdict->accentColor() }}; font-size: 15px; font-weight: 700; line-height: 1.4;">{{ $verdict->icon() }}&nbsp;&nbsp;{{ $verdict->label() }}<span style="font-weight: 400;">&nbsp;&middot;&nbsp;{{ $recommendation->confidence }}% confident</span></p>
@foreach (preg_split('/\R\s*\R/', trim($recommendation->reasoning)) as $paragraph)
<p style="margin: 0 0 {{ $loop->last ? '0' : '12px' }}; color: #1A1A1A; font-size: 14px; line-height: 1.5;">{!! nl2br(e($paragraph)) !!}</p>
@endforeach
</td>
</tr>
</table>
