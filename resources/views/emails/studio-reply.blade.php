<x-emails.layout locale="it">
    @php($logoSrc = $message->embed(public_path('brand/pitmetric-primary-dark-email.png')))
    <x-emails.outreach locale="it" :message-body="$messageBody" :logo-src="$logoSrc" :is-reply="true" />
</x-emails.layout>
