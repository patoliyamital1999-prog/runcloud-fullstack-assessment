<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Voting System</title>
  <link rel="stylesheet" href="{{ asset('vendors/typicons/typicons.css') }}">
  <link rel="stylesheet" href="{{ asset('vendors/css/vendor.bundle.base.css') }}">
  <link rel="stylesheet" href="{{ asset('css/vertical-layout-light/style.css') }}">
</head>
<style>
.site-header {
  background: linear-gradient(135deg, #0d9488 0%, #14b8a6 50%, #0f766e 100%);
  padding: 0 32px;
  min-height: 64px;
  display: flex;
  align-items: center;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.12);
}

.site-header-inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  max-width: 1400px;
  margin: 0 auto;
}

.logo-title {
  margin: 0;
  font-size: 24px;
  font-weight: 800;
  font-family: 'Segoe UI', system-ui, sans-serif;
  letter-spacing: -0.5px;
}

.logo-title a {
  color: #ffffff;
  text-decoration: none;
  display: flex;
  align-items: center;
  gap: 2px;
}

.logo-title .logo-accent {
  color: #fef08a;
}

.header-nav {
  display: flex;
  align-items: center;
  gap: 8px;
  list-style: none;
  margin: 0;
  padding: 0;
}

.header-nav .nav-link {
  color: #ffffff;
  font-size: 15px;
  font-weight: 600;
  text-decoration: none;
  padding: 8px 16px;
  border-radius: 8px;
  transition: background 0.2s ease, color 0.2s ease;
}

.header-nav .nav-link:hover {
  background: rgba(255, 255, 255, 0.15);
  color: #ffffff;
  text-decoration: none;
}

.header-nav .nav-divider {
  width: 1px;
  height: 28px;
  background: rgba(255, 255, 255, 0.35);
  margin: 0 8px;
}

.user-profile-header {
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 12px;
  padding: 6px 14px 6px 6px;
  background: rgba(255, 255, 255, 0.12);
  border-radius: 50px;
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.user-avatar {
  width: 38px;
  height: 38px;
  min-width: 38px;
  border-radius: 50%;
  background: linear-gradient(135deg, #2563eb, #1d4ed8);
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 16px;
  text-transform: uppercase;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
}

.user-name {
  color: #ffffff;
  font-size: 15px;
  font-weight: 600;
  max-width: 140px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  line-height: 1.2;
}

.nav-link-logout {
  color: rgba(255, 255, 255, 0.9);
  font-size: 14px;
  font-weight: 600;
}

.nav-link-logout:hover {
  color: #fecaca;
  background: rgba(239, 68, 68, 0.25);
}

@media (max-width: 768px) {
  .site-header {
    padding: 0 16px;
  }

  .logo-title {
    font-size: 18px;
  }

  .user-name {
    max-width: 80px;
    font-size: 13px;
  }

  .header-nav .nav-link {
    padding: 6px 10px;
    font-size: 14px;
  }
}
</style>
<body>
  <header class="site-header">
    <div class="site-header-inner">
      <h4 class="logo-title">
        <a href="{{ route('posts.index') }}">
          <span class="logo-accent">V</span>oting System
        </a>
      </h4>

      <ul class="header-nav">
        <li>
          <a href="{{ route('posts.index') }}" class="nav-link">Posts</a>
        </li>

        @guest
          <li>
            <a href="{{ route('login') }}" class="nav-link">Login</a>
          </li>
          <li>
            <a href="{{ route('register') }}" class="nav-link">Register</a>
          </li>
        @else
          <li><span class="nav-divider"></span></li>
          <li>
            <div class="user-profile-header">
              <div class="user-avatar" title="{{ auth()->user()->name }}">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
              </div>
              <span class="user-name">{{ auth()->user()->name }}</span>
            </div>
          </li>
          <li>
            <a class="nav-link nav-link-logout"
               href="{{ route('logout') }}"
               onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
              Logout
            </a>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
              @csrf
            </form>
          </li>
        @endguest
      </ul>
    </div>
  </header>
  <div class="container-scroller">
