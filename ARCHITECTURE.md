# Healthcare Claims Batching System - Detailed Architecture

## Overview

This document provides a comprehensive explanation of the system's architecture, focusing on database transactions, queue usage, and database schema design.

## Database Transactions

### Implementation

The claim submission process uses Laravel's database transactions to ensure atomicity:

```php
DB::transaction(function () use ($request, $insurer, $totalValue, $itemsData) {
    // Create claim
    $claim = Claim::create([...]);

    // Create items
    foreach ($itemsData as $itemData) {
        $claim->items()->create($itemData);
    }

    // Batch the claim
    $this->batchClaim($claim, $insurer);
});
```

### Why Transactions?

1. **Data Integrity**: If any step fails (e.g., batch creation), all changes are rolled back
2. **Consistency**: Prevents partial states where a claim exists but isn't batched
3. **Atomicity**: All-or-nothing operations for complex multi-table inserts
4. **Error Recovery**: Automatic rollback on exceptions maintains database consistency

### Benefits

-   Prevents orphaned records
-   Maintains referential integrity
-   Simplifies error handling
-   Ensures business logic consistency

## Queue System

### Implementation

Email notifications use Laravel's queue system:

```php
class BatchNotification extends Mailable implements ShouldQueue
{
    // Queued email implementation
}
```

### Why Queues?

1. **Performance**: API responds immediately without waiting for email sending
2. **Reliability**: Failed emails can be retried automatically
3. **Scalability**: Email processing can be distributed across workers
4. **User Experience**: No delays in claim submission response

### Configuration

-   Uses database queue driver by default
-   Requires `php artisan queue:work` to process jobs
-   Emails are sent asynchronously in background

### Benefits

-   Faster API responses
-   Better fault tolerance
-   Improved throughput
-   Separation of concerns

## Database Schema Design

### Insurers Table

Stores insurance company configurations and cost parameters.

| Column                   | Type      | Description                                    |
| ------------------------ | --------- | ---------------------------------------------- |
| id                       | bigint    | Primary key                                    |
| name                     | varchar   | Company name                                   |
| code                     | varchar   | Unique API identifier                          |
| base_cost                | decimal   | Base processing cost per batch                 |
| time_cost_min            | decimal   | Minimum time-based cost percentage (0.2 = 20%) |
| time_cost_max            | decimal   | Maximum time-based cost percentage (0.5 = 50%) |
| specialty_efficiency     | json      | Specialty efficiency multipliers               |
| priority_cost_multiplier | decimal   | Priority level multiplier                      |
| value_cost_multiplier    | decimal   | Monetary value multiplier                      |
| daily_capacity           | int       | Maximum claims per day                         |
| min_batch_size           | int       | Minimum claims per batch                       |
| max_batch_size           | int       | Maximum claims per batch                       |
| date_preference          | enum      | 'encounter' or 'submission' for batching       |
| email                    | varchar   | Notification email address                     |
| timestamps               | timestamp | Created/updated timestamps                     |

### Claims Table

Represents submitted medical claims.

| Column          | Type      | Description                       |
| --------------- | --------- | --------------------------------- |
| id              | bigint    | Primary key                       |
| insurer_id      | bigint    | Foreign key to insurers           |
| provider_name   | varchar   | Healthcare provider name          |
| encounter_date  | date      | Date of medical encounter         |
| submission_date | date      | Date claim was submitted          |
| specialty       | varchar   | Medical specialty                 |
| priority        | int       | Priority level (1-5)              |
| total_value     | decimal   | Total claim value                 |
| batch_id        | bigint    | Foreign key to batches (nullable) |
| timestamps      | timestamp | Created/updated timestamps        |

### Claim Items Table

Detailed line items within claims.

| Column     | Type      | Description                               |
| ---------- | --------- | ----------------------------------------- |
| id         | bigint    | Primary key                               |
| claim_id   | bigint    | Foreign key to claims                     |
| name       | varchar   | Item description                          |
| unit_price | decimal   | Price per unit                            |
| quantity   | int       | Number of units                           |
| subtotal   | decimal   | Calculated total (unit_price \* quantity) |
| timestamps | timestamp | Created/updated timestamps                |

### Batches Table

Groups claims for processing.

| Column        | Type      | Description                |
| ------------- | --------- | -------------------------- |
| id            | bigint    | Primary key                |
| provider_name | varchar   | Provider for this batch    |
| date          | date      | Processing date            |
| insurer_id    | bigint    | Foreign key to insurers    |
| total_cost    | decimal   | Calculated processing cost |
| status        | enum      | 'pending' or 'processed'   |
| timestamps    | timestamp | Created/updated timestamps |

## Relationships

### One-to-Many

-   Insurer → Claims: One insurer processes many claims
-   Insurer → Batches: One insurer receives many batches
-   Claim → ClaimItems: One claim has many items
-   Batch → Claims: One batch contains many claims

### Many-to-One

-   Claims → Insurer: Many claims belong to one insurer
-   Batches → Insurer: Many batches belong to one insurer
-   ClaimItems → Claim: Many items belong to one claim
-   Claims → Batch: Many claims belong to one batch

## Cost Calculation Logic

### Time-Based Costs

Costs increase linearly over the month:

```
time_cost = min + (max - min) * (day_of_month - 1) / 29
```

### Specialty Efficiency

Multipliers for different medical specialties:

```json
{
    "cardiology": 0.8,
    "orthopedics": 1.2,
    "general": 1.0
}
```

### Total Claim Cost Formula

```
cost = base_cost * (1 + time_cost) * specialty_efficiency * priority_multiplier^priority + total_value * value_multiplier
```

### Batch Cost

```
batch_total_cost = sum of all claim costs in batch
```

## Indexing Strategy

### Primary Keys

-   All tables have auto-incrementing bigint primary keys

### Foreign Keys

-   insurer_id, claim_id, batch_id have foreign key constraints
-   Cascading deletes on claim_items when claims are deleted

### Unique Constraints

-   Batches: unique(provider_name, date, insurer_id) prevents duplicate batches

### Performance Indexes

-   Foreign key columns are automatically indexed
-   Consider composite indexes on frequently queried combinations

## Data Flow

1. **Claim Submission**:

    - Validate input
    - Start transaction
    - Create claim record
    - Create claim items
    - Determine batch
    - Update/create batch
    - Send queued email
    - Commit transaction

2. **Batching Logic**:

    - Calculate batch date
    - Find existing batch
    - Check capacity
    - Calculate costs
    - Update batch totals

3. **Email Notification**:
    - Queue job
    - Process asynchronously
    - Send batch details to insurer

## Error Handling

### Try-Catch Blocks

-   Wrap entire submission process
-   Log errors for debugging
-   Return user-friendly error messages
-   Prevent application crashes

### Validation

-   Comprehensive input validation
-   Business rule enforcement
-   Type checking and constraints

### Queue Failures

-   Automatic retries for failed jobs
-   Dead letter queues for persistent failures
-   Monitoring and alerting

## Scalability Considerations

### Database

-   Proper indexing on foreign keys
-   Connection pooling
-   Query optimization

### Queues

-   Multiple workers for high volume
-   Priority queues for urgent notifications
-   Monitoring queue health

### Application

-   Caching for frequently accessed data
-   Horizontal scaling capabilities
-   Load balancing support
