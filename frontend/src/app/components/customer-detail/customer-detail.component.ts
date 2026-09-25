import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { CustomerService } from '../../services/customer.service';
import { ToastService } from '../../services/toast.service';
import { Customer } from '../../models/customer.model';

@Component({
  selector: 'app-customer-detail',
  standalone: true,
  imports: [CommonModule, RouterLink],
  templateUrl: './customer-detail.component.html',
})
export class CustomerDetailComponent implements OnInit {
  customer?: Customer;
  isLoading = true;
  isDeleting = false;

  constructor(
    private customerService: CustomerService,
    private toastService: ToastService,
    private route: ActivatedRoute,
    private router: Router
  ) {}

  ngOnInit(): void {
    const id = +this.route.snapshot.paramMap.get('id')!;
    this.customerService.getById(id).subscribe({
      next: (res) => {
        this.customer = res.data;
        this.isLoading = false;
      },
      error: () => this.router.navigate(['/customers']),
    });
  }

  onEdit(): void {
    this.router.navigate(['/customers', this.customer!.id, 'edit']);
  }

  onDelete(): void {
    const name = `${this.customer!.first_name} ${this.customer!.last_name}`;
    if (confirm(`Delete "${name}"? This action cannot be undone.`)) {
      this.isDeleting = true;
      this.customerService.delete(this.customer!.id!).subscribe({
        next: () => {
          this.toastService.success(`${name} was deleted.`);
          this.router.navigate(['/customers']);
        },
        error: () => {
          this.isDeleting = false;
          this.toastService.error('Failed to delete customer.');
        },
      });
    }
  }

  onBack(): void {
    this.router.navigate(['/customers']);
  }

  getInitials(): string {
    return ((this.customer?.first_name?.[0] ?? '') + (this.customer?.last_name?.[0] ?? '')).toUpperCase();
  }

  getAvatarGradient(): string {
    const palettes = [
      'linear-gradient(135deg,#6366f1,#818cf8)',
      'linear-gradient(135deg,#06b6d4,#67e8f9)',
      'linear-gradient(135deg,#10b981,#6ee7b7)',
      'linear-gradient(135deg,#f59e0b,#fcd34d)',
      'linear-gradient(135deg,#ef4444,#fca5a5)',
      'linear-gradient(135deg,#8b5cf6,#c4b5fd)',
      'linear-gradient(135deg,#ec4899,#f9a8d4)',
    ];
    return palettes[(this.customer?.id ?? 0) % palettes.length];
  }

  formatDate(dateStr?: string): string {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('en-US', {
      year: 'numeric', month: 'long', day: 'numeric',
      hour: '2-digit', minute: '2-digit',
    });
  }
}
