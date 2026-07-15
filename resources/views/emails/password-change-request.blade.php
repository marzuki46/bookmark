<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Confirm {{ ucfirst($type) }} Change</title>
  <style>
    body { font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; background: #f1f5f9; color: #1e293b; margin: 0; padding: 40px 20px; }
    .container { max-width: 480px; margin: 0 auto; }
    .card { background: white; border-radius: 16px; padding: 32px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
    .logo { width: 56px; height: 56px; border-radius: 14px; background: linear-gradient(135deg, #6366f1, #4f46e5); display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; color: white; font-size: 24px; font-weight: 700; }
    h1 { text-align: center; font-size: 20px; margin-bottom: 8px; }
    .subtitle { text-align: center; font-size: 14px; color: #64748b; margin-bottom: 24px; }
    .detail { background: #f8fafc; border-radius: 8px; padding: 16px; margin-bottom: 20px; }
    .detail-row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 13px; }
    .detail-label { color: #64748b; }
    .detail-value { font-weight: 600; color: #0f172a; }
    .btn { display: block; width: 100%; padding: 12px; border-radius: 8px; text-align: center; text-decoration: none; font-size: 14px; font-weight: 600; margin-bottom: 12px; }
    .btn-primary { background: #6366f1; color: white; }
    .btn-primary:hover { background: #4f46e5; }
    .btn-danger { background: transparent; color: #ef4444; border: 1px solid #fecaca; }
    .btn-danger:hover { background: #fef2f2; }
    .warning { background: #fef3c7; border: 1px solid #fcd34d; border-radius: 8px; padding: 12px; font-size: 13px; color: #92400e; text-align: center; margin-top: 16px; }
    .footer { text-align: center; font-size: 12px; color: #94a3b8; margin-top: 24px; }
  </style>
</head>
<body>
  <div class="container">
    <div class="card">
      <div class="logo">K</div>
      <h1>Confirm {{ ucfirst($type) }} Change</h1>
      <p class="subtitle">You requested to change your {{ $type }} on Knowledge Hub</p>

      <div class="detail">
        <div class="detail-row">
          <span class="detail-label">Account:</span>
          <span class="detail-value">{{ $user->email }}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Change Type:</span>
          <span class="detail-value">{{ ucfirst($type) }}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Expires:</span>
          <span class="detail-value">{{ $expiresAt->format('M d, Y H:i') }}</span>
        </div>
      </div>

      <a href="{{ $approveUrl }}" class="btn btn-primary">Yes, Change My {{ ucfirst($type) }}</a>
      <a href="{{ $rejectUrl }}" class="btn btn-danger">No, Cancel This Request</a>

      <div class="warning">
        If you didn't request this change, click "Cancel" and consider changing your password.
      </div>

      <div class="footer">
        This link expires in {{ $expiresAt->diffForHumans() }}.
      </div>
    </div>
  </div>
</body>
</html>
