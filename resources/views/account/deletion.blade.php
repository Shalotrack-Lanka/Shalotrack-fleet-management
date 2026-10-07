<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Account deletion · ShaloTrack</title>
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;background:#f1f5f9;color:#0f172a;min-height:100vh}
        header{background:#021F4A;color:#fff;padding:14px 20px;font-weight:700;font-size:16px}
        header span{color:#FA6908}
        main{max-width:560px;margin:32px auto;padding:0 16px}
        .card{background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:24px;box-shadow:0 1px 3px rgba(0,0,0,.05)}
        h1{font-size:20px;color:#021F4A;margin-bottom:6px}
        .date{font-size:15px;color:#b91c1c;font-weight:600;margin-bottom:14px}
        p{font-size:14px;color:#475569;line-height:1.55;margin-bottom:12px}
        .row{display:flex;flex-wrap:wrap;gap:10px;margin-top:18px}
        button,a.btn{font:inherit;font-size:14px;font-weight:600;border-radius:10px;padding:10px 16px;cursor:pointer;text-decoration:none;display:inline-block;border:1px solid transparent}
        .primary{background:#FA6908;color:#fff}
        .ghost,a.btn.ghost{background:#fff;color:#021F4A;border-color:#cbd5e1}
        button:disabled{opacity:.55;cursor:default}
        #err{display:none;color:#b91c1c;font-size:13px;margin-top:12px}
        form{display:inline}
    </style>
</head>
<body>
<header>Shalo<span>Track</span></header>
<main>
    <div class="card">
        <h1>Your account is scheduled for deletion</h1>
        <div class="date">Permanent deletion on {{ $scheduledFor }} @if($daysLeft > 0)({{ $daysLeft }} {{ $daysLeft === 1 ? 'day' : 'days' }} left)@endif</div>
        <p>Until then your account is locked: you can't view vehicles or receive notifications, and your live-share links and vehicle shares have been ended.</p>
        <p>Changed your mind? Cancel the deletion and your account and data come back. Live-share links and vehicle shares that were ended must be set up again. After that date your data is erased permanently and can't be recovered.</p>
        <p>You can still download a copy of your data before then.</p>

        <div class="row">
            <button id="cancel" class="primary" type="button">Cancel deletion and keep my account</button>
            <a class="btn ghost" href="/api/account/export?format=pdf" id="dl">Download my data (PDF)</a>
            <form method="POST" action="/logout">
                @csrf
                <button class="ghost" type="submit">Sign out</button>
            </form>
        </div>
        <p id="err" role="alert"></p>
    </div>
</main>
<script>
    const CSRF = '{{ csrf_token() }}';
    const err = document.getElementById('err');
    function showError(m){ err.textContent = m || ''; err.style.display = m ? 'block' : 'none'; }

    document.getElementById('cancel').addEventListener('click', async (ev) => {
        const btn = ev.currentTarget;
        btn.disabled = true; showError('');
        try {
            const res = await fetch('/api/account/deletion/cancel', {
                method: 'POST', credentials: 'include',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            });
            if (res.status === 401) { window.location.href = '/login?expired=1'; return; }
            let data = null; try { data = await res.json(); } catch { /* non-JSON */ }
            if (res.ok && data && data.success) { window.location.href = data.redirect || '/dashboard'; return; }
            showError((data && data.message) || 'Could not cancel. Please try again.');
        } catch { showError('Network problem. Please try again.'); }
        btn.disabled = false;
    });
</script>
</body>
</html>