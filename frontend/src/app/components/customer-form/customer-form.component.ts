import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { CustomerService } from '../../services/customer.service';
import { Customer } from '../../models/customer.model';

@Component({
  selector: 'app-customer-form',
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

  constructor(
    private customerService: CustomerService,
    private route: ActivatedRoute,
    private router: Router
  ) {}

  ngOnInit(): void {
    const id = this.route.snapshot.paramMap.get('id');
    if (id && id !== 'new') {
      this.isEditMode = true;
      this.customerId = +id;
      this.customerService.getById(this.customerId).subscribe((res) => {
        this.customer = res.data;
      });
    }
  }

  onSubmit(): void {
    if (this.isEditMode && this.customerId) {
      this.customerService.update(this.customerId, this.customer).subscribe(() => {
        this.router.navigate(['/customers']);
      });
    } else {
      this.customerService.create(this.customer).subscribe(() => {
        this.router.navigate(['/customers']);
      });
    }
  }
}
