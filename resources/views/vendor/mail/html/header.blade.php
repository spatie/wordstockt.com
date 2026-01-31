@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block; text-decoration: none;">
<!--[if mso]>
<table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center">
<tr>
<![endif]-->
<table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin: 0 auto;">
<tr>
@php
$tiles = [
    ['letter' => 'W', 'accent' => true],
    ['letter' => 'O', 'accent' => false],
    ['letter' => 'R', 'accent' => false],
    ['letter' => 'D', 'accent' => false],
    ['letter' => 'S', 'accent' => true],
    ['letter' => 'T', 'accent' => false],
    ['letter' => 'O', 'accent' => false],
    ['letter' => 'C', 'accent' => false],
    ['letter' => 'K', 'accent' => false],
    ['letter' => 'T', 'accent' => false],
];
@endphp
@foreach($tiles as $tile)
<td style="padding: 0 1px;">
<div style="width: 28px; height: 28px; border-radius: 3px; display: inline-block; text-align: center; line-height: 28px; font-size: 16px; font-weight: bold; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; {{ $tile['accent'] ? 'background-color: #4A90D9; color: #ffffff;' : 'background-color: #E8E4DC; color: #1A1A1A;' }}">{{ $tile['letter'] }}</div>
</td>
@endforeach
</tr>
</table>
<!--[if mso]>
</tr>
</table>
<![endif]-->
</a>
</td>
</tr>
