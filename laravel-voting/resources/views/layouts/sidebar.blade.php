<nav class="sidebar sidebar-offcanvas" id="sidebar">
        <ul class="nav">
          @php
            $user = auth()->user();
          @endphp
          <li class="nav-item">
            @if ($user && (string) $user->role === 'admin')
              <a class="nav-link" href="{{ route('dashboard') }}">
                <i class="typcn typcn-device-desktop menu-icon"></i>
                <span class="menu-title">Admin Panel</span>
              </a>
            @else
              <a class="nav-link" href="{{ route('dashboard') }}">
              <i class="typcn typcn-device-desktop menu-icon"></i>
              <span class="menu-title">Dashboard</span>
            </a>
            @endif
          </li>
          
          <li class="nav-item">
            <a class="nav-link" href="{{ route('logout') }}">
              <i class="typcn typcn-mortar-board menu-icon"></i>
              <span class="menu-title">Logout</span>
            </a>
          </li>
        </ul>
      </nav>