<p>Hello,</p>
<p>You were invited to join the CRM platform.</p>
<p>Role: {{ $invitation->role }}</p>
<p>Invitation link: <a href="{{ $acceptUrl }}">{{ $acceptUrl }}</a></p>
<p>This link expires at: {{ $invitation->expires_at }}</p>
