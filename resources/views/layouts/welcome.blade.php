<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #f5f7fb; }
    .login-card { max-width: 420px; width: 100%; border: none; border-radius: 16px; box-shadow: 0 8px 24px rgba(0,0,0,0.08); }
    .brand { font-weight: 700; letter-spacing: .3px; }
  </style>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const token = document.querySelector('meta[name=\"csrf-token\"]').getAttribute('content');
      const form = document.getElementById('loginForm');
      if (form) {
        form.addEventListener('submit', () => {
          // no-op placeholder for custom JS if needed
        });
      }
    });
  </script>
</head>
<body>
  <div class="card login-card">
    <div class="card-body p-4 p-lg-5">
      <div class="text-center mb-4">
        <div class="brand h4 mb-1">GP-Specialist Admin</div>
        <div class="text-muted">Sign in to your dashboard</div>
      </div>
      @if ($errors->any())
        <div class="alert alert-danger">
          {{ $errors->first() }}
        </div>
      @endif
      <form id="loginForm" method="POST" action="{{ route('admin.login.submit') }}">
        @csrf
        <div class="mb-3">
          <label class="form-label">Email or Mobile</label>
          <input type="text" name="login" class="form-control form-control-lg" placeholder="admin@example.com or 9000000000" value="{{ old('login', 'admin@example.com') }}" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control form-control-lg" placeholder="••••••••" value="password" required>
        </div>
        <div class="d-grid">
          <button type="submit" class="btn btn-primary btn-lg">Sign In</button>
        </div>
      </form>
      <div class="text-center text-muted mt-3" style="font-size: .9rem;">
        Admin: admin@example.com / 9000000000 · password: password
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
