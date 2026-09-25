import { Component } from '@angular/core';
import { RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-root',
  standalone: true,
  imports: [RouterLink, RouterLinkActive, RouterOutlet, CommonModule],
  template: `
    <div class="app-shell">

      <!-- ── Sidebar ─────────────────────────────────────────────────── -->
      <aside class="sidebar">

        <div class="sidebar-logo">
          <div class="logo-icon">
            <i class="fa-solid fa-users"></i>
          </div>
          <div>
            <div class="logo-text">CustoSync</div>
          </div>
          <span class="logo-badge">CRM</span>
        </div>

        <div class="sidebar-section">
          <div class="sidebar-section-label">Main Menu</div>

          <a class="nav-item" routerLink="/customers" routerLinkActive="active"
             [routerLinkActiveOptions]="{ exact: false }">
            <span class="nav-icon"><i class="fa-solid fa-users"></i></span>
            Customers
          </a>

          <div class="nav-item" style="opacity:0.45;cursor:not-allowed">
            <span class="nav-icon"><i class="fa-solid fa-chart-bar"></i></span>
            Analytics
          </div>

          <div class="nav-item" style="opacity:0.45;cursor:not-allowed">
            <span class="nav-icon"><i class="fa-solid fa-bell"></i></span>
            Notifications
          </div>
        </div>

        <div class="sidebar-section" style="padding-top:0">
          <div class="sidebar-section-label">Settings</div>
          <div class="nav-item" style="opacity:0.45;cursor:not-allowed">
            <span class="nav-icon"><i class="fa-solid fa-gear"></i></span>
            Preferences
          </div>
          <div class="nav-item" style="opacity:0.45;cursor:not-allowed">
            <span class="nav-icon"><i class="fa-solid fa-shield-halved"></i></span>
            Security
          </div>
        </div>

        <div class="sidebar-footer">
          <div class="sidebar-user">
            <div class="user-avatar">A</div>
            <div class="user-info">
              <div class="user-name">Admin User</div>
              <div class="user-role">Administrator</div>
            </div>
          </div>
        </div>
      </aside>

      <!-- ── Main Content ────────────────────────────────────────────── -->
      <main class="main-content">

        <!-- Topbar -->
        <header class="topbar">
          <div class="topbar-breadcrumb">
            <i class="fa-solid fa-house" style="font-size:0.75rem"></i>
            <span class="crumb-sep">/</span>
            <span class="crumb-current">Customers</span>
          </div>
          <div class="topbar-right">
            <div class="topbar-status">
              <span class="status-dot"></span>
              API Connected
            </div>
            <div class="topbar-icon-btn" title="Refresh">
              <i class="fa-solid fa-rotate-right"></i>
            </div>
          </div>
        </header>

        <!-- Router Outlet -->
        <router-outlet></router-outlet>
      </main>
    </div>
  `,
})
export class AppComponent {}
