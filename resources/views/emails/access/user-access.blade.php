<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organization Access</title>
</head>
<body style="margin:0;padding:0;background:#f4f7fb;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f7fb;padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:620px;background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #e5e7eb;">
                <tr>
                    <td style="background:linear-gradient(135deg,#0f766e,#0b7285);padding:24px 28px;color:#ffffff;">
                        <h1 style="margin:0;font-size:22px;line-height:1.3;">Your Access Details</h1>
                        <p style="margin:8px 0 0;font-size:14px;opacity:.95;">{{ $organizationName }} workspace access</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:24px 28px;">
                        <p style="margin:0 0 12px;font-size:15px;line-height:1.7;">
                            Hello {{ $userName }}, your {{ $roleLabel }} account is ready.
                        </p>
                        <div style="background:#f8fafc;border:1px solid #e5e7eb;border-radius:10px;padding:14px 16px;margin-bottom:18px;">
                            <p style="margin:0 0 6px;font-size:13px;color:#6b7280;">Organization Login Email</p>
                            <p style="margin:0 0 12px;font-size:15px;font-weight:600;color:#111827;">{{ $loginEmail }}</p>
                            <p style="margin:0 0 6px;font-size:13px;color:#6b7280;">Temporary Password</p>
                            <p style="margin:0;font-size:15px;font-weight:600;color:#111827;">{{ $loginPassword }}</p>
                        </div>
                        <table role="presentation" cellspacing="0" cellpadding="0" style="margin:0 0 18px;">
                            <tr>
                                <td align="center" style="border-radius:8px;background:#0f766e;">
                                    <a href="{{ $loginUrl }}"
                                       style="display:inline-block;padding:12px 20px;color:#ffffff;text-decoration:none;font-size:14px;font-weight:700;border-radius:8px;">
                                        Sign In
                                    </a>
                                </td>
                            </tr>
                        </table>
                        <p style="margin:0 0 6px;font-size:12px;color:#6b7280;">Secure login link:</p>
                        <p style="margin:0 0 16px;word-break:break-all;font-size:12px;color:#0b7285;">
                            <a href="{{ $loginUrl }}" style="color:#0b7285;">{{ $loginUrl }}</a>
                        </p>
                        <p style="margin:0;font-size:12px;color:#6b7280;">If you did not request this, contact support.</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
