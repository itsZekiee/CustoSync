import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { ActivatedRoute, Router } from '@angular/router';
import { CustomerService } from '../../services/customer.service';
import { ToastService } from '../../services/toast.service';
import { Customer } from '../../models/customer.model';

@Component({
  selector: 'app-customer-form',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterLink],
  templateUrl: './customer-form.component.html',
})
export class CustomerFormComponent implements OnInit {
  customer: Customer = {
    first_name: '',
    last_name: '',
    email: '',
    contact_number: '',
  };
  isEditMode = false;
  customerId?: number;
  isSubmitting = false;
  apiError = '';

  constructor(
    private customerService: CustomerService,
    private toastService: ToastService,
    private route: ActivatedRoute,
    private router: Router
  ) {}

  ngOnInit(): void {
    const id = this.route.snapshot.paramMap.get('id');
    if (id && id !== 'new') {
      this.isEditMode = true;
      this.customerId = +id;
      this.customerService.getById(this.customerId).subscribe({
        next: (res) => (this.customer = res.data),
        error: () => this.router.navigate(['/customers']),
      });
    }
  }

  onSubmit(): void {
    this.isSubmitting = true;
    this.apiError = '';

    const action$ = this.isEditMode && this.customerId
      ? this.customerService.update(this.customerId, this.customer)
      : this.customerService.create(this.customer);

    action$.subscribe({
      next: () => {
        const msg = this.isEditMode ? 'Customer updated successfully!' : 'Customer created successfully!';
        this.toastService.success(msg);
        this.router.navigate(['/customers']);
      },
      error: (err) => {
        this.isSubmitting = false;
        this.apiError = err?.error?.error ?? 'An error occurred. Please try again.';
      },
    });
  }
}
