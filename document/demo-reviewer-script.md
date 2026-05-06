# Demo Reviewer Script

## Purpose

Use this script as the official reviewer walkthrough for the Gantian capstone demo. It keeps the demo tied to seeded data, exact routes, visible outcomes, and the proposal scope already implemented in the codebase.

## Reset And Start Commands

Run these commands from the project root before the demo:

```bash
touch database/database.sqlite
php artisan migrate:fresh --seed
npm run build
php artisan serve --host=127.0.0.1 --port=8001
```

Open the app at `http://127.0.0.1:8001`.

## Demo Accounts

All seeded reviewer accounts use password `password`.

| Role | Email | Main demo area |
| --- | --- | --- |
| Admin/Owner | `admin@gantian.test` | `/admin/items`, `/admin/reports`, and staff operations |
| Staff | `staff@gantian.test` | `/staff/bookings` |
| Customer | `customer@gantian.test` | `/dashboard`, `/bookings`, and `/catalog` |
| Customer | `customer2@gantian.test` | `/dashboard`, `/bookings`, and `/catalog` |

## Seeded Scenario Map

Use these seeded records to anchor the walkthrough:

| Account | Seeded examples |
| --- | --- |
| `customer@gantian.test` | `pending_camera`, `active_speaker`, `cancelled_drone` |
| `customer2@gantian.test` | `approved_projector`, `completed_tripod` |

Seeded fines:

| Fine | Expected status |
| --- | --- |
| Fine on `completed_tripod` | paid |
| Fine on `active_speaker` | unpaid |

Seeded booking statuses covered by the demo data:

* `pending`
* `approved`
* `active`
* `completed`
* `cancelled`

## Walkthrough Script

1. Customer dashboard and bookings: sign in as `customer@gantian.test`, open `/dashboard`, then open `/bookings`. Confirm the customer sees only their own seeded bookings, including `pending_camera`, `active_speaker`, and `cancelled_drone` outcomes through the visible booking rows and statuses.
2. Catalog submit: open `/catalog` as `customer@gantian.test`. Choose an available item, use tomorrow as `start_date` and the day after tomorrow as `end_date`, then submit a new booking request. Confirm the request is accepted and appears as a pending customer booking.
3. Duplicate validation: submit the same item and date range again from `/catalog`. Confirm the duplicate request is rejected by validation instead of creating another open booking.
4. Staff bookings: sign out, then sign in as `staff@gantian.test` and open `/staff/bookings`. Review the seeded lifecycle coverage across `pending`, `approved`, `active`, `completed`, and `cancelled`. Confirm pending, approved, and active bookings show the expected operational controls. Demonstrate valid staff actions such as approve, check-out, check-in with inspection, cancel where allowed, and fine handling for active or completed bookings.
5. Admin items and reports: sign out, then sign in as `admin@gantian.test`, open `/admin/items`, then open `/admin/reports`. Confirm admin can view and manage catalog data, then confirm the reports screen summarizes bookings, revenue, and fines from seeded and demo data.
6. Negative access checks: sign in as `customer@gantian.test` and attempt `/staff/bookings` and `/admin/items`. Confirm access is blocked. Sign in as `staff@gantian.test` and attempt `/admin/items` and `/bookings`. Confirm staff cannot access admin-only pages or customer-only booking history.

## Expected Evidence

Screenshots are optional presenter guidance only. They aren't required for automated completion.

Suggested filenames if the presenter wants visual evidence:

* `.sisyphus/evidence/demo-customer-bookings.png`
* `.sisyphus/evidence/demo-customer-catalog.png`
* `.sisyphus/evidence/demo-staff-bookings.png`
* `.sisyphus/evidence/demo-admin-reports.png`

## Out Of Scope During Demo

Don't claim or demonstrate features outside the current proposal scope:

* no payment gateway
* no payment settlement
* no report export
* no customer cancellation workflow
* no new statuses beyond `pending`, `approved`, `active`, `completed`, and `cancelled`
* no schema changes during the demo
