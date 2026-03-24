# Appointment (Custom Drupal Module)

A custom Drupal module that provides an **appointment booking system** with:

- A **6-step booking wizard** (agency → type → adviser → date/time → personal info → confirmation)
- User self-service page **“Mes rendez-vous”** (view + modify/cancel via phone verification)
- Admin dashboard (filters, sorting, **CSV export via Batch API**)
- Agency management as a custom content entity
- Adviser management (agency, specializations, working hours)
- FullCalendar-based date selection and JSON events endpoint

This project targets **Drupal 9/10/11** and follows Drupal best practices (Form API, Entity API, TempStore, access checks, output escaping).

---

## Features

### Booking flow (public)
Route: `/prendre-un-rendez-vous`

Steps:
1. Choose an **agency**
2. Choose an **appointment type** (taxonomy `appointment_type`)
3. Choose an **adviser** (users with role `adviser`, filtered by agency + specializations)
4. Choose **date and time** (FullCalendar + slot generation based on settings + adviser working hours)
5. Enter **personal info** (name/email/phone validation)
6. **Confirmation** → creates an `appointment` entity and emails confirmation (if mail system configured)

### User dashboard
Route: `/mes-rendez-vous`

Shows:
- Appointment ID, date/time (converted to site timezone), agency, adviser, type, status
- Actions **Modifier / Supprimer** (both require phone verification)

### Modify / Cancel (phone verification)
Routes:
- `/modifier-un-rendez-vous` (lookup + phone verification)
- `/modifier-un-rendez-vous/{appointment}` (modify wizard)
- `/annuler-un-rendez-vous/{appointment}` (cancel/delete)

**Important**: even logged-in users must re-verify the phone number for the appointment in the current session.

### Administration
Routes:
- `/admin/structure/appointment` (appointments list with filters + export)
- `/admin/structure/advisers` (advisers list)
- `/admin/structure/agencies` (agencies collection)
- `/admin/config/appointment/settings` (slot duration + default workday hours)

CSV export is implemented with **Batch API** to avoid timeouts and large memory usage.

---

## Data model

### Appointment entity (`appointment`)
Custom content entity with base fields:
- `appointment_date` (stored in **UTC** as `Y-m-d\TH:i:s`)
- `agency` (entity reference to `agency`)
- `appointment_type` (taxonomy term reference to vocabulary `appointment_type`)
- `adviser` (user reference, role filter `adviser`)
- `customer_name`, `customer_email`, `customer_phone`
- `status` (`pending`, `confirmed`, `cancelled`)
- `reference` (auto-generated human reference like `AP-YYYYMMDD-...`)
- `duration_minutes` (used for calendar event end time)
- `notes`

### Agency entity (`agency`)
Custom content entity with:
- name, address, contact info, operating hours

### Adviser (User)
Users with role `adviser`, with fields:
- `field_agency` (reference to `agency`)
- `field_specializations` (terms in `appointment_type`)
- `field_workday_start`, `field_workday_end` (`HH:MM`)

---

## Installation (fresh site / “ready without UI clicks”)

1. Copy the module to:

```
web/modules/custom/appointment
```

2. Enable the module:

```bash
drush en appointment -y
drush cr
```

### What gets provisioned automatically

On install, the module ships and/or creates:
- Default config `appointment.settings`
- Vocabulary `appointment_type`
- User fields for advisers (agency/specializations/working hours)
- Roles:
  - `adviser`
  - `appointment_manager` (granted `administer appointments`)
- Seeds minimal data if empty:
  - a few `appointment_type` terms
  - one default agency (“Agence principale”)
- Ensures adviser fields are visible on the default user edit form/view display

After enabling, you can immediately:
- Create/assign advisers (users with role `adviser`)
- Assign agency + specializations + working hours on user profiles
- Start booking appointments via `/prendre-un-rendez-vous`

---

## Configuration

### Appointment settings
Admin route: `/admin/config/appointment/settings`

Config keys (`appointment.settings`):
- `slot_minutes` (default 60)
- `day_start` (default `09:00`)
- `day_end` (default `17:00`)

Adviser working hours override the defaults when present.

---

## Permissions

Defined permission:
- `administer appointments`: required for admin dashboard, advisers admin, exports, and settings.

Recommended:
- Give this permission to `appointment_manager` role (already shipped).

---

## Calendar (FullCalendar)

The booking/modify forms use **FullCalendar** (loaded via CDN in `appointment.libraries.yml`).

Events JSON endpoint:
- `/appointment/fullcalendar/events/{adviser}?start=...&end=...`

It returns booked slots (non-cancelled) so the calendar can show availability.

---

## Email notifications

Mail keys (via `hook_mail()` in `appointment.module`):
- `booking_confirmation`
- `booking_modified`
- `booking_cancelled`

If you see SMTP “connection refused”, configure Drupal’s mail system for your environment (Mailhog/Mailpit/SMTP provider).

---

## Key routes (quick reference)

- Public booking: `/prendre-un-rendez-vous`
- User list: `/mes-rendez-vous`
- Modify lookup: `/modifier-un-rendez-vous`
- Admin appointments: `/admin/structure/appointment`
- Admin advisers: `/admin/structure/advisers`
- Agencies: `/admin/structure/agencies`
- Settings: `/admin/config/appointment/settings`



