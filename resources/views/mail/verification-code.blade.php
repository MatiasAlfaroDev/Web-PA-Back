<x-mail::message>
# Hola {{ $user->first_name }},

Tu código de verificación es:

<x-mail::panel>
**{{ $user->verification_code }}**
</x-mail::panel>

El código expira en 15 minutos.

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
