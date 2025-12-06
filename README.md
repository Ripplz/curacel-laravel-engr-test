# Healthcare Claims Batching System

This Laravel + Vue.js application implements an optimal claims batching system for healthcare providers and insurers, minimizing processing costs while respecting various constraints.

## Overview

The system allows healthcare providers to submit medical claims through a Vue.js frontend. Claims are automatically batched for insurers in a way that minimizes total processing costs, considering factors like time of month, specialty efficiency, priority levels, and monetary values.

## Features

-   **Claim Submission**: Providers can submit claims with multiple items, specialties, and priorities.
-   **Automatic Batching**: Claims are optimally batched to minimize insurer processing costs.
-   **Cost Calculation**: Dynamic cost calculation based on insurer-specific constraints.
-   **Email Notifications**: Insurers receive notifications about new batches.
-   **Queue Support**: Email sending is queued for better performance.
-   **Error Handling**: Comprehensive try-catch blocks prevent application crashes.
-   **Form Validation and Feedback**: Retains form data on validation errors, clears on success, shows appropriate toasts.

## Recent Changes

### Fixes Applied to Claim Submission

The following changes were made to resolve issues with form submission, toasts, and data retention:

-   **Route Migration**: Moved `POST /api/claims` from `routes/api.php` to `routes/web.php` to ensure Inertia.js receives proper redirect responses instead of plain JSON, preventing "plain JSON response" errors.
-   **Controller Updates**: Modified `ClaimController::store()` to return `redirect()->back()->with('success', ...)` on success and `redirect()->back()->with('error', ...)` on server errors. Validation errors are handled automatically by Laravel via redirects.
-   **Vue Component Adjustments**: Updated `SubmitClaim.vue` to handle flash messages in `onMounted` for success and error toasts. Removed redundant toast calls in form callbacks. Form reset occurs only on successful submission via `onSuccess`.
-   **Code Cleanup**: Removed unused imports (`Inertia`, `ClaimItem`), added return types, and ensured proper error handling for web routes.

## Architecture

### Models

-   **Claim**: Represents a submitted claim with items, linked to insurer and batch.
-   **ClaimItem**: Individual items within a claim (name, price, quantity).
-   **Batch**: Groups claims for processing, tracks total cost.
-   **Insurer**: Insurance companies with processing constraints and costs.

### Key Components

-   **ClaimController**: Handles API requests for claim submission.
-   **BatchNotification**: Queued email notification for insurers.
-   **SubmitClaim.vue**: Frontend form for claim submission.

## Setup

1. Install dependencies:

    ```bash
    composer install
    npm install
    ```

2. Set up environment:

    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

3. Configure database in `.env` and run migrations:

    ```bash
    php artisan migrate
    php artisan db:seed
    ```

4. Build assets:

    ```bash
    npm run dev
    ```

5. Start the application:

    ```bash
    php artisan serve
    ```

6. (Optional but recommended) Start the queue worker in the background for email processing:

    ```bash
    # Run in background
    php artisan queue:work --daemon

    # Or run in foreground for development
    php artisan queue:work
    ```

The UI is accessible at the root URL (`/`).

## Running the Application

### Development Mode

1. Start the Laravel server:

    ```bash
    php artisan serve
    ```

2. In another terminal, start the queue worker:

    ```bash
    php artisan queue:work
    ```

3. Build assets (if not using hot reload):
    ```bash
    npm run dev
    ```

### Production Mode

1. Use a web server like Nginx/Apache to serve the application
2. Set up a process manager (like Supervisor) to run queue workers:
    ```ini
    [program:laravel-queue-worker]
    process_name=%(program_name)s_%(process_num)02d
    command=php /path/to/artisan queue:work --sleep=3 --tries=3
    directory=/path/to/project
    autostart=true
    autorestart=true
    user=www-data
    numprocs=2
    redirect_stderr=true
    stdout_logfile=/path/to/logs/queue.log
    ```

### Queue Worker Explanation

The queue worker processes background jobs like email sending:

-   **Foreground mode** (`php artisan queue:work`): Runs in terminal, stops when terminal closes
-   **Daemon mode** (`php artisan queue:work --daemon`): Runs in background, survives terminal closure
-   **Production**: Use process managers for reliability and automatic restarts

Without the queue worker, emails won't be sent, but claims will still be processed and batched.

## API Endpoints

### POST /api/claims

Submits a new claim. This is a web route that returns redirects with flash messages for proper Inertia.js handling.

**Request Body (form data):**

```
insurer_code: INS-A
provider_name: Provider A
encounter_date: 2023-10-01
specialty: cardiology
priority: 3
items[0][name]: Consultation
items[0][unit_price]: 100.0
items[0][quantity]: 1
```

**Response (redirect):**

On success: Redirects back with `success` flash message: "Claim submitted successfully!"

On validation error: Redirects back with validation errors populated.

On server error: Redirects back with `error` flash message.

## Batching Algorithm

The system uses a greedy approach to batch claims:

1. Determine batch date based on insurer's preference (encounter or submission date).
2. Find existing batch for provider + date + insurer.
3. If batch exists and has capacity, add claim; else create new batch.
4. Calculate claim cost using: `base_cost * (1 + time_cost) * specialty_eff * priority_mult + value * value_mult`
5. Update batch total cost.
6. Send queued email notification.

This minimizes costs by preferring existing batches over creating new ones (avoiding base costs).

## Database Transactions

The application uses database transactions in the claim submission process to ensure data integrity. If any part of the claim creation, item creation, or batching fails, all changes are rolled back, preventing partial data states.

**Reason**: Prevents data corruption if errors occur during multi-step operations like creating claims and updating batches.

## Queue Configuration

Emails are sent via Laravel's queue system to improve performance and reliability. The `BatchNotification` mailable implements `ShouldQueue` to send emails asynchronously.

**Reason**: Email sending can be slow and unreliable; queuing ensures the API responds quickly while emails are processed in the background. Prevents timeouts and improves user experience.

Ensure queue worker is running:

```bash
php artisan queue:work
```

## Database Schema Overview

### Insurers Table

-   `name`: Insurer company name
-   `code`: Unique identifier for API submissions
-   `base_cost`: Base processing cost per batch
-   `time_cost_min/max`: Percentage costs that increase linearly from 20% to 50% over the month
-   `specialty_efficiency`: JSON object mapping specialties to efficiency multipliers
-   `priority_cost_multiplier`: Multiplier for claim priority levels
-   `value_cost_multiplier`: Multiplier for claim monetary value
-   `daily_capacity`: Maximum claims per day
-   `min/max_batch_size`: Batch size constraints
-   `date_preference`: 'encounter' or 'submission' for batch date calculation
-   `email`: Contact email for notifications

### Claims Table

-   `insurer_id`: Foreign key to insurers
-   `provider_name`: Healthcare provider submitting the claim
-   `encounter_date`: Date of medical encounter
-   `submission_date`: Date claim was submitted
-   `specialty`: Medical specialty
-   `priority`: Priority level (1-5)
-   `total_value`: Total monetary value
-   `batch_id`: Foreign key to batches (nullable)

### Claim Items Table

-   `claim_id`: Foreign key to claims
-   `name`: Item description
-   `unit_price`: Price per unit
-   `quantity`: Number of units
-   `subtotal`: Calculated total for this item

### Batches Table

-   `provider_name`: Provider for this batch
-   `date`: Batch processing date
-   `insurer_id`: Foreign key to insurers
-   `total_cost`: Calculated total processing cost
-   `status`: 'pending' or 'processed'

## Testing

Run tests with:

```bash
php artisan test
```

Tests cover claim submission and validation.

## Evaluation Criteria

-   **Efficiency**: O(1) batching, low memory usage, scalable.
-   **Adaptability**: Handles varying constraints robustly.
-   **Code Quality**: Clean, tested, documented code.
-   **Innovation**: Effective cost-minimizing batching strategy.

## Extra Notes

-   All models use `$guarded = []` for mass assignment.
-   Error handling with try-catch and logging.
-   Queued emails for performance.
-   Comprehensive validation on API inputs.
-   Form automatically clears after successful claim submission for clean UX.
