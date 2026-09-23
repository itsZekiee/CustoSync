import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { CustomerService } from '../../services/customer.service';
import { Customer } from '../../models/customer.model';

@Component({
  selector: 'app-customer-detail',
  templateUrl: './customer-detail.component.html',
})
export class CustomerDetailComponent implements OnInit {
  customer?: Customer;

  constructor(
    private customerService: CustomerService,
    private route: ActivatedRoute,
    private router: Router
  ) {}

  ngOnInit(): void {
    const id = +this.route.snapshot.paramMap.get('id')!;
    this.customerService.getById(id).subscribe((res) => {
      this.customer = res.data;
    });
  }

  onEdit(): void {
    this.router.navigate(['/customers', this.customer!.id, 'edit']);
  }

  onBack(): void {
    this.router.navigate(['/customers']);
  }
}
