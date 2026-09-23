import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { Customer } from '../models/customer.model';

@Injectable({
  providedIn: 'root',
})
export class CustomerService {
  private apiUrl = '/api/customers';

  constructor(private http: HttpClient) {}

  getAll(search?: string): Observable<any> {
    let params = new HttpParams();
    if (search) {
      params = params.set('search', search);
    }
    return this.http.get(this.apiUrl, { params });
  }

  getById(id: number): Observable<{ data: Customer }> {
    return this.http.get<{ data: Customer }>(`${this.apiUrl}/${id}`);
  }

  create(customer: Customer): Observable<{ data: Customer }> {
    return this.http.post<{ data: Customer }>(this.apiUrl, customer);
  }

  update(id: number, customer: Customer): Observable<{ data: Customer }> {
    return this.http.put<{ data: Customer }>(`${this.apiUrl}/${id}`, customer);
  }

  delete(id: number): Observable<void> {
    return this.http.delete<void>(`${this.apiUrl}/${id}`);
  }
}
