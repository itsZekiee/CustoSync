import { Component, OnInit, OnDestroy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Subscription } from 'rxjs';
import { CustomerService } from '../../services/customer.service';
import { ToastService, Toast } from '../../services/toast.service';
import { Customer } from '../../models/customer.model';

type ModalMode = 'create' | 'edit' | null;

@Component({
  selector: 'app-customer-list',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './customer-list.component.html',
})
export class CustomerListComponent implements OnInit, OnDestroy {
  /* ── State ─────────────────────────────────────────────── */
  customers: Customer[] = [];
  filtered: Customer[] = [];
  searchQuery = '';
  isLoading = true;
  hasError = false;
  toasts: Toast[] = [];

  /* ── Modal ─────────────────────────────────────────────── */
  modalMode: ModalMode = null;
  modalSubmitting = false;
  modalError = '';
  form: Customer = this.emptyForm();

  /* ── Detail panel ──────────────────────────────────────── */
  selectedCustomer: Customer | null = null;
  showPanel = false;

  /* ── Delete confirm ────────────────────────────────────── */
  confirmTarget: Customer | null = null;
  isDeleting = false;

  private toastSub?: Subscription;

  constructor(
    private svc: CustomerService,
    private toast: ToastService,
  ) {}

  ngOnInit(): void {
    this.load();
    this.toastSub = this.toast.toasts$.subscribe(t => {
      this.toasts = [...this.toasts, t];
      setTimeout(() => { this.toasts = this.toasts.filter(x => x.id !== t.id); }, 3000);
    });
  }

  ngOnDestroy(): void { this.toastSub?.unsubscribe(); }

  /* ── Data ──────────────────────────────────────────────── */
  load(): void {
    this.isLoading = true;
    this.hasError = false;
    this.svc.getAll().subscribe({
      next: res => {
        this.customers = res.data ?? [];
        this.applyFilter();
        this.isLoading = false;
      },
      error: () => { this.hasError = true; this.isLoading = false; }
    });
  }

  applyFilter(): void {
    const q = this.searchQuery.toLowerCase().trim();
    this.filtered = q
      ? this.customers.filter(c =>
          c.first_name.toLowerCase().includes(q) ||
          c.last_name.toLowerCase().includes(q) ||
          c.email.toLowerCase().includes(q) ||
          (c.contact_number ?? '').includes(q)
        )
      : [...this.customers];
  }

  clearSearch(): void { this.searchQuery = ''; this.applyFilter(); }

  /* ── Create Modal ─────────────────────────────────────── */
  openCreate(): void {
    this.form = this.emptyForm();
    this.modalError = '';
    this.modalMode = 'create';
  }

  /* ── Edit Modal ───────────────────────────────────────── */
  openEdit(c: Customer, ev?: Event): void {
    ev?.stopPropagation();
    this.form = { ...c };
    this.modalError = '';
    this.modalMode = 'edit';
    this.showPanel = false;
  }

  closeModal(): void { this.modalMode = null; }

  submitForm(): void {
    this.modalSubmitting = true;
    this.modalError = '';
    const action$ = this.modalMode === 'edit' && this.form.id
      ? this.svc.update(this.form.id, this.form)
      : this.svc.create(this.form);

    action$.subscribe({
      next: res => {
        const wasEdit = this.modalMode === 'edit';
        this.modalSubmitting = false;
        this.modalMode = null;
        this.toast.success(wasEdit ? 'Customer updated!' : 'Customer created!');
        // Refresh list and update detail panel if open
        this.svc.getAll().subscribe(r => {
          this.customers = r.data ?? [];
          this.applyFilter();
          if (this.showPanel && this.selectedCustomer?.id === res.data.id) {
            this.selectedCustomer = res.data;
          }
        });
      },
      error: err => {
        this.modalSubmitting = false;
        this.modalError = err?.error?.error ?? 'Something went wrong. Try again.';
      }
    });
  }

  /* ── View Panel ───────────────────────────────────────── */
  openPanel(c: Customer): void {
    this.selectedCustomer = c;
    this.showPanel = true;
  }

  closePanel(): void { this.showPanel = false; }

  /* ── Delete ───────────────────────────────────────────── */
  promptDelete(c: Customer, ev?: Event): void {
    ev?.stopPropagation();
    this.confirmTarget = c;
    this.showPanel = false;
  }

  cancelDelete(): void { this.confirmTarget = null; }

  confirmDelete(): void {
    if (!this.confirmTarget) return;
    this.isDeleting = true;
    const name = `${this.confirmTarget.first_name} ${this.confirmTarget.last_name}`;
    this.svc.delete(this.confirmTarget.id!).subscribe({
      next: () => {
        this.isDeleting = false;
        this.confirmTarget = null;
        this.toast.success(`${name} deleted.`);
        this.load();
      },
      error: () => {
        this.isDeleting = false;
        this.toast.error('Failed to delete customer.');
      }
    });
  }

  /* ── Helpers ──────────────────────────────────────────── */
  emptyForm(): Customer {
    return { first_name: '', last_name: '', email: '', contact_number: '' };
  }

  initials(c: Customer): string {
    return ((c.first_name?.[0] ?? '') + (c.last_name?.[0] ?? '')).toUpperCase();
  }

  avatarGradient(id?: number): string {
    const g = [
      'linear-gradient(135deg,#6366f1,#818cf8)',
      'linear-gradient(135deg,#06b6d4,#38bdf8)',
      'linear-gradient(135deg,#10b981,#34d399)',
      'linear-gradient(135deg,#f59e0b,#fbbf24)',
      'linear-gradient(135deg,#f43f5e,#fb7185)',
      'linear-gradient(135deg,#8b5cf6,#a78bfa)',
      'linear-gradient(135deg,#ec4899,#f472b6)',
    ];
    return g[(id ?? 0) % g.length];
  }

  formatDate(d?: string): string {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
  }

  get totalCustomers() { return this.customers.length; }
  get recentCustomers() {
    const week = Date.now() - 7 * 24 * 60 * 60 * 1000;
    return this.customers.filter(c => c.created_at && new Date(c.created_at).getTime() > week).length;
  }
}
