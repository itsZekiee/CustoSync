import { Injectable } from '@angular/core';
import { Subject } from 'rxjs';

export interface Toast {
  id: number;
  type: 'success' | 'error';
  message: string;
}

@Injectable({ providedIn: 'root' })
export class ToastService {
  private _toasts$ = new Subject<Toast>();
  toasts$ = this._toasts$.asObservable();
  private nextId = 0;

  show(type: 'success' | 'error', message: string): void {
    this._toasts$.next({ id: this.nextId++, type, message });
  }

  success(message: string): void { this.show('success', message); }
  error(message: string): void   { this.show('error', message); }
}
