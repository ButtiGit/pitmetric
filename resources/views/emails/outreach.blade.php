<x-emails.layout :locale="$locale">
    @php($logoSrc = $message->embed(public_path('brand/pitmetric-primary-dark-email.png')))
    <x-emails.outreach :locale="$locale" :message-body="$messageBody" :note="$note" :unsubscribe-url="$unsubscribeUrl" :logo-src="$logoSrc" />
</x-emails.layout>
