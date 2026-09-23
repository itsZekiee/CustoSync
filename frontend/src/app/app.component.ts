import { Component } from '@angular/core';

@Component({
  selector: 'app-root',
  template: `
    <nav class="navbar navbar-dark bg-dark mb-4">
      <div class="container">
        <a class="navbar-brand" routerLink="/customers">CustoSync</a>
      </div>
    </nav>
    <router-outlet></router-outlet>
  `,
})
export class AppComponent {}
