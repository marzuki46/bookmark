<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Setup - Knowledge Hub</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    body { font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; background: #0f172a; min-height: 100vh; }
    .setup-card { background: white; border-radius: 20px; padding: 40px; width: 100%; max-width: 440px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }
    .setup-logo {
      width: 72px; height: 72px; border-radius: 18px;
      background: linear-gradient(135deg, #6366f1, #4f46e5);
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 24px; color: white; font-size: 32px; font-weight: 700;
      box-shadow: 0 8px 24px rgba(99,102,241,0.3);
    }
    .form-input {
      width: 100%; padding: 12px 16px; border: 1.5px solid #e2e8f0; border-radius: 10px;
      font-size: 14px; outline: none; transition: all 0.15s; font-family: inherit;
    }
    .form-input:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
    .btn-primary {
      width: 100%; padding: 12px 24px; border: none; border-radius: 10px;
      background: linear-gradient(135deg, #6366f1, #4f46e5); color: white;
      font-size: 15px; font-weight: 600; cursor: pointer; transition: all 0.15s;
      font-family: inherit;
    }
    .btn-primary:hover { background: linear-gradient(135deg, #4f46e5, #4338ca); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(99,102,241,0.3); }
    .btn-primary:active { transform: translateY(0); }
    .step-indicator { display: flex; gap: 8px; justify-content: center; margin-bottom: 32px; }
    .step-dot { width: 8px; height: 8px; border-radius: 50%; background: #e2e8f0; transition: all 0.3s; }
    .step-dot.active { background: #6366f1; width: 24px; border-radius: 4px; }
    .step-dot.done { background: #34d399; }
    .step-content { display: none; }
    .step-content.active { display: block; animation: fadeIn 0.3s ease; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    .pin-input { letter-spacing: 8px; text-align: center; font-size: 24px; font-weight: 600; }
  </style>
</head>
<body>
  <div style="display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px;">
    <div class="setup-card">
      <div class="setup-logo">K</div>
      <h1 style="text-align: center; font-size: 24px; font-weight: 700; color: #0f172a; margin-bottom: 4px;">Welcome to Knowledge Hub</h1>
      <p style="text-align: center; font-size: 14px; color: #64748b; margin-bottom: 8px;">Let's set up your personal knowledge base</p>

      <div class="step-indicator">
        <div class="step-dot active" id="dot1"></div>
        <div class="step-dot" id="dot2"></div>
        <div class="step-dot" id="dot3"></div>
      </div>

      @if ($errors->any())
        <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; padding: 12px 16px; margin-bottom: 20px;">
          @foreach ($errors->all() as $error)
            <p style="font-size: 13px; color: #991b1b;">{{ $error }}</p>
          @endforeach
        </div>
      @endif

      <form method="POST" action="{{ route('setup.store') }}" id="setupForm">
        @csrf

        {{-- Step 1: Account --}}
        <div class="step-content active" id="step1">
          <h2 style="font-size: 16px; font-weight: 600; color: #0f172a; margin-bottom: 16px;">Account Information</h2>
          <div style="margin-bottom: 16px;">
            <label style="display: block; font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 6px;">Name</label>
            <input type="text" name="name" class="form-input" value="{{ old('name') }}" placeholder="Your name" required autofocus>
          </div>
          <div style="margin-bottom: 16px;">
            <label style="display: block; font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 6px;">Email</label>
            <input type="email" name="email" class="form-input" value="{{ old('email') }}" placeholder="you@example.com" required>
          </div>
          <button type="button" class="btn-primary" onclick="goToStep(2)">Continue</button>
        </div>

        {{-- Step 2: Password --}}
        <div class="step-content" id="step2">
          <h2 style="font-size: 16px; font-weight: 600; color: #0f172a; margin-bottom: 16px;">Create Password</h2>
          <div style="margin-bottom: 16px;">
            <label style="display: block; font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 6px;">Password</label>
            <input type="password" name="password" class="form-input" placeholder="Min. 8 characters" required>
          </div>
          <div style="margin-bottom: 20px;">
            <label style="display: block; font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 6px;">Confirm Password</label>
            <input type="password" name="password_confirmation" class="form-input" placeholder="Repeat password" required>
          </div>
          <div style="display: flex; gap: 12px;">
            <button type="button" class="btn-primary" style="background: #f1f5f9; color: #64748b;" onclick="goToStep(1)">Back</button>
            <button type="button" class="btn-primary" onclick="goToStep(3)">Continue</button>
          </div>
        </div>

        {{-- Step 3: PIN --}}
        <div class="step-content" id="step3">
          <h2 style="font-size: 16px; font-weight: 600; color: #0f172a; margin-bottom: 8px;">Secret Vault PIN</h2>
          <p style="font-size: 13px; color: #64748b; margin-bottom: 20px;">4-6 digit PIN to unlock your Secret Vault at <code style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px;">/secrets</code></p>
          <div style="margin-bottom: 20px;">
            <input type="password" name="pin" class="form-input pin-input" placeholder="----" maxlength="6" pattern="[0-9]{4,6}" required inputmode="numeric">
          </div>
          <div style="display: flex; gap: 12px;">
            <button type="button" class="btn-primary" style="background: #f1f5f9; color: #64748b;" onclick="goToStep(2)">Back</button>
            <button type="submit" class="btn-primary">Complete Setup</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <script>
    function goToStep(step) {
      document.querySelectorAll('.step-content').forEach(el => el.classList.remove('active'));
      document.getElementById('step' + step).classList.add('active');
      document.querySelectorAll('.step-dot').forEach((dot, i) => {
        dot.classList.remove('active', 'done');
        if (i + 1 < step) dot.classList.add('done');
        if (i + 1 === step) dot.classList.add('active');
      });
    }
  </script>
</body>
</html>
