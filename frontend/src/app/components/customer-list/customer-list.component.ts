import { Component, OnInit } from '@angular/core';
import { CustomerService } from '../../services/customer.service';
import { Customer } from '../../models/customer.model';
import { Router } from '@angular/router';

@Component({
  selector: 'app-customer-list',
  templateUrl: './customer-list.component.html',
})
export class CustomerListComponent implements OnInit {
  customers: Customer[] = [];
  searchQuery = '';

  constructor(
    private customerService: CustomerService,
    private router: Router
  ) {}

  ngOnInit(): void {
    this.loadCustomers();
  }

  loadCustomers(): void {
    const search = this.searchQuery.trim() || undefined;
    this.customerService.getAll(search).subscribe((res) => {
      this.customers = res.data ?? res;
    });
  }

  onSearch(): void {
    this.loadCustomers();
  }

  onDelete(id: number): void {
    if (confirm('Are you sure you want to delete this customer?')) {
      this.customerService.delete(id).subscribe(() => this.loadCustomers());
    }
  }

  onEdit(id: number): void {
    this.router.navigate(['/customers', id, 'edit']);
  }

  onView(id: number): void {
    this.router.navigate(['/customers', id]);
  }
}
