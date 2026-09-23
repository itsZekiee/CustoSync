# CustoSync — CRM Customer Management System

A CRUD application for managing customer records, built with **Laravel** (API), **Angular** (frontend), **MySQL** (database), and **Elasticsearch** (search index), orchestrated via **Docker Compose**.

---

## Architecture

| Service      | Technology        | Port  | Description                                      |
|--------------|-------------------|-------|--------------------------------------------------|
| `controller` | Nginx             | 8080  | Reverse proxy / load balancer                    |
| `api`        | Laravel 10 + PHP 8.2 | 9000  | REST API for customer CRUD                       |
| `database`   | MySQL 8           | 3306  | Primary relational data store                    |
| `searcher`   | Elasticsearch 8   | 9200  | Full-text search index for customers             |

## Mono-Repo Decision

This project uses a **mono-repo** layout. Rationale:
- Single `docker-compose.yml` orchestrates everything — no cross-repo coordination.
- Easier to review commit-by-commit (one history, one PR).
- 48-hour constraint favours simplicity over separation.

## Project Structure

```
CustoSync/
├── docker-compose.yml
├── docker/
│   ├── api/
│   │   └── Dockerfile          # PHP 8.2-FPM + Composer + Laravel deps
│   ├── controller/
│   │   ├── Dockerfile          # Nginx
│   │   └── nginx.conf          # Reverse-proxy config
│   └── searcher/
│       └── (uses official ES image, no custom Dockerfile)
├── api/                        # Laravel application
│   ├── app/
│   │   ├── Http/Controllers/   # CustomerController
│   │   ├── Models/             # Customer model
│   │   ├── Observers/          # CustomerObserver (ES sync trigger)
│   │   ├── Services/           # ElasticsearchService
│   │   └── Providers/
│   ├── database/
│   │   ├── migrations/
│   │   ├── factories/
│   │   └── seeders/
│   ├── routes/
│   │   └── api.php
│   └── tests/
│       ├── Unit/
│       └── Feature/
├── frontend/                   # Angular application
│   ├── src/
│   │   ├── app/
│   │   │   ├── components/
│   │   │   │   ├── customer-list/
│   │   │   │   ├── customer-form/
│   │   │   │   └── customer-detail/
│   │   │   ├── services/
│   │   │   │   └── customer.service.ts
│   │   │   ├── models/
│   │   │   │   └── customer.model.ts
│   │   │   ├── app.module.ts
│   │   │   ├── app-routing.module.ts
│   │   │   └── app.component.ts
│   │   └── environments/
│   └── angular.json
└── README.md
```

## API Endpoints

| Method   | Path                    | Purpose                                    |
|----------|-------------------------|--------------------------------------------|
| `GET`    | `/api/customers`        | List customers (supports `?search=` query) |
| `POST`   | `/api/customers`        | Create a new customer                      |
| `GET`    | `/api/customers/{id}`   | View a single customer                     |
| `PUT`    | `/api/customers/{id}`   | Update a customer                          |
| `DELETE` | `/api/customers/{id}`   | Delete a customer                          |

### Search behaviour

`GET /api/customers?search=john` queries Elasticsearch across `first_name`, `last_name`, and `email` fields using a `multi_match` query, then hydrates results from MySQL.

## Elasticsearch Sync Approach

- **Trigger**: A Laravel **Model Observer** (`CustomerObserver`) fires on `created`, `updated`, and `deleted` events.
- **Execution**: The observer calls `ElasticsearchService` which uses Laravel's built-in `Http` client (no Scout) to index/delete documents via the ES REST API.
- **Index**: `customers`
- **Document schema**:
  ```json
  {
    "id": 1,
    "first_name": "Jane",
    "last_name": "Doe",
    "email": "jane@example.com",
    "contact_number": "+1234567890"
  }
  ```

## Prerequisites

- Docker & Docker Compose
- Node.js 16+ (for local Angular dev, optional if using Docker)

## Quick Start

```bash
# Clone and start all services
git clone <repo-url> && cd CustoSync
docker compose up -d --build

# Run migrations
docker compose exec api php artisan migrate

# Create ES index
docker compose exec api php artisan es:create-index

# Frontend (local dev)
cd frontend && npm install && ng serve
```

Open **http://localhost:4200** (Angular) → API proxied through **http://localhost:8080**.

## Running Tests

```bash
# Backend
docker compose exec api php artisan test

# Frontend
cd frontend && ng test
```

## Decisions & Ambiguities

See bottom of this README for open items to resolve before coding.

### Open Decisions
1. **Pagination** — offset-based vs cursor-based for the list endpoint? (Recommend offset for simplicity.)
2. **Validation error format** — Laravel default JSON or a custom envelope?
3. **Auth** — The spec doesn't mention authentication. Assume none for now?
4. **ES failure handling** — Should a failed ES sync block the API response or fail silently with logging?
5. **Angular version** — Spec says 7+; recommend latest LTS (17) unless a specific version is required.
6. **Contact number format** — Free-text string or validated E.164?
