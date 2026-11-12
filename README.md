# Service Request & Approval API

A RESTful API built with Laravel 12 for managing employee service requests through a role-based approval workflow. The system supports request creation, manager approvals, Operations assignment, status tracking, comments, activity history, and asynchronous database notifications.

## 1. Technology Stack

- **Backend:** PHP 8.3, Laravel 12
- **Database:** SQLite
- **Authentication:** Laravel Sanctum
- **Notifications:** Laravel Notifications
- **Queue:** Database queue driver
- **Testing:** PHPUnit

## 2. Features

### Authentication
- User registration and login.
- Token-based authentication using Laravel Sanctum.
- Authenticated user profile endpoint.
- Logout and token revocation.
- New registrations are assigned the Employee role by default.

### Role-Based Access Control

The system supports three roles:

| Role | Responsibilities |
|---|---|
| Employee | Create requests, edit draft or returned requests, submit requests, and view their own requests |
| Manager | Review requests submitted by direct reports and approve, reject, or return them |
| Operations | Assign approved requests, start assigned work, and complete requests |

### Request Management
- Create and update service requests.
- View request details.
- Search by title, description, or category.
- Filter by status, priority, and category.
- Sort by creation date, title, priority, or status.
- Paginated request listings.

### Approval Workflow

The supported request statuses are:

`draft → submitted → approved → assigned → in_progress → completed`

Alternative manager decisions:

- `submitted → rejected`
- `submitted → returned`
- `returned → submitted` after employee updates

Rejected and completed requests are final states.

Invalid status transitions are rejected with HTTP 422 responses.

### Comments and Activity History
- Add comments to service requests.
- View request comments.
- Record status changes and other important actions.
- Track the user responsible for each activity, timestamps, and optional comments.

### Queued Notifications
- Database notifications are sent asynchronously when requests change status.
- Employees' managers are notified when requests are submitted.
- Relevant employees and Operations users are notified about subsequent workflow changes.
- Notifications are processed using Laravel's database queue.

## 3. Installation and Setup

### Prerequisites

Install the following:

- PHP 8.3 or later
- Composer
- Git

### Step 1: Clone the repository

```bash
git clone https://github.com/KrishmiH/service-request-api.git
cd service-request-api
```

### Step 2: Install dependencies

```bash
composer install
```

### Step 3: Configure environment

Copy the example environment file:

```bash
cp .env.example .env
```

On Windows PowerShell, you can also use:

```powershell
Copy-Item .env.example .env
```

Generate the application key:

```bash
php artisan key:generate
```

### Step 4: Configure SQLite

Update the database configuration in `.env`:

```env
DB_CONNECTION=sqlite
```

Create the SQLite database file if it does not already exist.

On Windows PowerShell:

```powershell
New-Item database/database.sqlite -ItemType File
```

If the file already exists, do not create another one.

### Step 5: Configure the queue

In `.env`, set:

```env
QUEUE_CONNECTION=database
```

### Step 6: Run migrations and seeders

```bash
php artisan migrate:fresh --seed
```

This creates the database tables and inserts demonstration users and service requests.

### Step 7: Start the Laravel development server

```bash
php artisan serve
```

The API will be available at:

```text
http://127.0.0.1:8000
```

## 4. Demo Login Credentials

The database seeder creates demonstration accounts for all three roles.

| Role | Email | Password |
|---|---|---|
| Manager | manager@example.com | password123 |
| Employee | employee1@example.com | password123 |
| Employee | employee2@example.com | password123 |
| Operations | operations@example.com | password123 |

The seeded employees report directly to the demonstration manager.

**Note:** These credentials are for local development and testing only. Do not use them in production.

## 5. API Endpoints

All endpoints use the `/api` prefix.

### Authentication

| Method | Endpoint | Description | Authentication |
|---|---|---|---|
| POST | `/api/register` | Register an employee | No |
| POST | `/api/login` | Login and receive token | No |
| GET | `/api/me` | Get authenticated user | Yes |
| POST | `/api/logout` | Revoke current token | Yes |

### Service Requests

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/requests` | List accessible requests |
| POST | `/api/requests` | Create a request |
| GET | `/api/requests/{id}` | View request details |
| PUT | `/api/requests/{id}` | Update a request |
| POST | `/api/requests/{id}/submit` | Submit a request |

### Manager Approval

| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/requests/{id}/approve` | Approve a request |
| POST | `/api/requests/{id}/reject` | Reject a request |
| POST | `/api/requests/{id}/return` | Return a request for changes |

### Operations

| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/requests/{id}/assign` | Assign an approved request |
| POST | `/api/requests/{id}/start` | Start assigned work |
| POST | `/api/requests/{id}/complete` | Complete a request |

### Comments

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/requests/{id}/comments` | List request comments |
| POST | `/api/requests/{id}/comments` | Add a comment |

### Request Listing Parameters

The request listing endpoint supports:

| Parameter | Description |
|---|---|
| `search` | Search title, description, or category |
| `status` | Filter by request status |
| `priority` | Filter by priority |
| `category` | Filter by category |
| `sort_by` | Sort by `created_at`, `title`, `priority`, or `status` |
| `sort_direction` | `asc` or `desc` |
| `page` | Select pagination page |

Example:

```http
GET /api/requests?status=submitted&priority=high&sort_by=created_at&sort_direction=desc
```

## 6. Authentication

The API uses Laravel Sanctum bearer tokens.

After logging in, copy the returned token and include it in the Authorization header for protected endpoints.

```http
Authorization: Bearer YOUR_ACCESS_TOKEN
Accept: application/json
```

Example login request:

```http
POST /api/login
Content-Type: application/json
Accept: application/json
```

```json
{
    "email": "employee1@example.com",
    "password": "password123"
}
```

Example create-request body:

```json
{
    "title": "Laptop Repair",
    "description": "My laptop is not starting properly.",
    "category": "IT Support",
    "priority": "high"
}
```

## 7. Queue Processing and Notifications

The application uses Laravel's database queue to process notifications asynchronously.

Start the queue worker in a separate terminal:

```bash
php artisan queue:work
```

The worker processes queued notification jobs and stores the resulting notifications in the `notifications` table.

Keep the worker running while testing notifications.

## 8. Running Automated Tests

Run the complete test suite:

```bash
php artisan test
```

Run the notification dispatch test separately:

```bash
php artisan test --filter=ServiceRequestNotificationTest
```

The notification test verifies that submitting a service request dispatches a queued notification job.

## 9. Database Structure

The main database tables are:

| Table | Purpose |
|---|---|
| `users` | User accounts, roles, and manager relationships |
| `service_requests` | Service request details and current status |
| `comments` | Comments associated with requests |
| `request_activities` | Request activity and workflow history |
| `notifications` | Database notifications |
| `jobs` | Queued background jobs |
| `failed_jobs` | Failed background jobs |

The application uses Eloquent relationships to connect users, requests, comments, and activities.

## 10. Assumptions and Design Decisions

- New users are registered with the Employee role. Users cannot assign themselves Manager or Operations privileges during registration.
- Managers can review requests belonging to their direct reports.
- Employees can edit requests only while they are in draft or returned status.
- Only approved requests can be assigned by Operations.
- Only the assigned Operations user can start or complete a request.
- Rejected and completed requests cannot transition to another status.
- Important workflow actions are recorded in the activity history.
- Database notifications are used instead of email notifications to keep the implementation simple and avoid requiring an external mail service.
- SQLite is used for local development and assessment purposes.

## 11. Possible Future Improvements

- Add automated tests covering all role permissions and workflow transitions.
- Provide an OpenAPI specification and expanded API documentation.
- Add email notifications.
- Add request attachment support.
- Add SLA tracking and overdue request alerts.
- Introduce a production database such as MySQL or PostgreSQL.

## 12. Repository

GitHub: https://github.com/KrishmiH/service-request-api
