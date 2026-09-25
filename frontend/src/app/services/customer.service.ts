import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { Customer } from '../models/customer.model';

@Injectable({ providedIn: 'root' })
export class CustomerService {
  private base = 'http://localhost/custosync-api/customers.php';

  constructor(private http: HttpClient) {}

  getAll(search?: string): Observable<{ data: Customer[] }> {
    let params = new HttpParams();
    if (search) params = params.set('search', search);
    return this.http.get<{ data: Customer[] }>(this.base, { params });
  }

  getById(id: number): Observable<{ data: Customer }> {
    return this.http.get<{ data: Customer }>(this.base, { params: new HttpParams().set('id', id) });
  }

  create(c: Customer): Observable<{ data: Customer }> {
    return this.http.post<{ data: Customer }>(this.base, c);
  }

  update(id: number, c: Customer): Observable<{ data: Customer }> {
    return this.http.put<{ data: Customer }>(this.base, c, { params: new HttpParams().set('id', id) });
  }

  delete(id: number): Observable<void> {
    return this.http.delete<void>(this.base, { params: new HttpParams().set('id', id) });
  }
}
