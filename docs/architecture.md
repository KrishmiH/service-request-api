# Architecture Note – Service Request & Approval API

## Architecture Overview

The Service Request & Approval API is built using Laravel 12 and follows a layered architecture. The application uses RESTful API endpoints, Laravel Sanctum for authentication, Eloquent ORM for database operations, and role-based authorization through policies.

## Main Components

- **Controllers:** Handle incoming API requests, validate input, authorize actions, and return JSON responses. Controllers are separated according to authentication, service request management, manager approvals, Operations actions, and comments.
- **Models:** Represent users, service requests, comments, and request activities. Eloquent relationships connect these entities.
- **Policies:** Enforce role-based access control and ownership rules. Employees manage their own eligible requests, managers review requests from direct reports, and Operations users handle approved and assigned requests.
- **Workflow Service:** `ServiceRequestWorkflow` centralizes valid status transitions. It uses database transactions to update request statuses and record activity history, preventing invalid transitions and preserving an audit trail.
- **Notifications and Queues:** Status-change notifications implement Laravel's `ShouldQueue` interface and use database notifications. Notifications are dispatched asynchronously and processed by Laravel's queue worker.

## Request Processing Flow

An employee creates a service request, which starts in the `draft` state. The employee submits it for manager review. The manager can approve, reject, or return the request. Once approved, an Operations user assigns it, starts the work, and marks it as completed. Each transition records the responsible user, timestamp, previous status, new status, and optional comment.

## Database and Testing

SQLite is used for local development. Database migrations define relationships, foreign keys, and indexes. Factories and seeders provide test data and demonstration accounts. Feature tests verify request submission, authorization restrictions, invalid transitions, and queued notification dispatch.

The architecture separates HTTP handling, authorization, workflow logic, persistence, and asynchronous notifications, making the application easier to maintain and extend.