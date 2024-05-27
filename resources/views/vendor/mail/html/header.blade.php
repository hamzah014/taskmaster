<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if (trim($slot) === 'Parking')
{{--<img src="https://laravel.com/img/notification-logo.png" class="logo" alt="Laravel Logo">--}}
    <h3>PARKING</h3>
@else
{{ $slot }}
@endif
</a>
</td>
</tr>
