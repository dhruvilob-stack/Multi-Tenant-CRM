<!doctype html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: Arial, sans-serif; color:#1f2937; background:#f8fafc; margin:0; padding:20px;">
    <div style="max-width:720px; margin:0 auto; border:1px solid #cbd5e1; border-radius:8px; background:#fff; overflow:hidden;">
        <div style="padding:14px 16px; border-bottom:1px solid #e2e8f0; background:#f1f5f9;">
            <div style="font-size:18px; font-weight:700;">{{ $mailRow->subject }}</div>
            <div style="font-size:12px; color:#64748b; margin-top:4px;">From: {{ $mailRow->sender_email }}</div>
        </div>
        <div style="padding:16px; line-height:1.55;">
            {!! $mailRow->body !!}
        </div>
    </div>
</body>
</html>
