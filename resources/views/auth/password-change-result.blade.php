<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ $success ? 'Success' : 'Failed' }} - Knowledge Hub</title>
  <style>
    body { font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; background: #f1f5f9; color: #1e293b; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
    .card { background: white; border-radius: 16px; padding: 40px; width: 100%; max-width: 400px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
    .icon { width: 64px; height: 64px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 28px; }
    .icon.success { background: #ecfdf5; color: #059669; }
    .icon.error { background: #fef2f2; color: #dc2626; }
    h1 { font-size: 20px; margin-bottom: 8px; }
    p { font-size: 14px; color: #64748b; margin-bottom: 24px; }
    .btn { display: inline-block; padding: 10px 24px; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600; background: #6366f1; color: white; }
    .btn:hover { background: #4f46e5; }
  </style>
</head>
<body>
  <div class="card">
    <div class="icon {{ $success ? 'success' : 'error' }}">
      {{ $success ? '✓' : '✕' }}
    </div>
    <h1>{{ $success ? 'Change Confirmed' : 'Change Failed' }}</h1>
    <p>{{ $message }}</p>
    <a href="{{ route('login') }}" class="btn">Go to Login</a>
  </div>
</body>
</html>
