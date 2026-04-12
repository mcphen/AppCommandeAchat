@component('mail::message')

{{-- Greeting --}}
@if(! empty($greeting))
# {{ $greeting }}
@endif

{{-- Intro Lines --}}
@foreach($introLines as $line)
{{ $line }}

@endforeach

{{-- Action Button --}}
@isset($actionText)
@component('mail::button', ['url' => $actionUrl, 'color' => $level ?? 'primary'])
{{ $actionText }}
@endcomponent
@endisset

{{-- Outro Lines --}}
@foreach($outroLines as $line)
{{ $line }}

@endforeach

@isset($salutation)
{{ $salutation }}
@else
L'équipe {{ config('app.name') }}
@endisset

{{-- Subcopy --}}
@isset($actionText)
@component('mail::subcopy')
Si le bouton "{{ $actionText }}" ne fonctionne pas, copiez ce lien dans votre navigateur : [{{ $actionUrl }}]({{ $actionUrl }})
@endcomponent
@endisset

@endcomponent
