# PitMetric Database Design

This document defines the intended direction for the core PitMetric domain. It is a design target, not a migration plan for the current iteration.

## Design rules

1. Do not store kilometres as the only usage model.
2. Preserve immutable historical configuration snapshots.
3. Treat the usage ledger as the source of truth.
4. Store measurement values in stable base units.
5. Scope domain data to a workspace.
6. Prefer archive/soft-delete semantics for historical entities.

## Proposed tables

### workspaces

- `id` PK
- `name`
- timestamps

### workspace_user

- `workspace_id` FK -> workspaces
- `user_id` FK -> users
- timestamps
- unique (`workspace_id`, `user_id`)

The MVP can start with one personal workspace per user, but domain tables should be structured so team collaboration can be added later.

### vehicles

- `id` PK
- `workspace_id` FK
- `name`
- `category`
- `manufacturer` nullable
- `model` nullable
- `year` nullable
- `identifier` nullable
- `status`
- `notes` nullable
- timestamps
- soft deletes recommended

Index `workspace_id`, `status`.

### component_types

- `id` PK
- `workspace_id` nullable FK
- `name`
- `category` nullable
- `description` nullable
- timestamps

System types can use `workspace_id = null`; custom types can belong to a workspace.

### components

Represents one physical item.

- `id` PK
- `workspace_id` FK
- `component_type_id` FK
- `name`
- `manufacturer` nullable
- `model` nullable
- `serial_number` nullable
- `purchase_date` nullable
- `purchase_cost_cents` nullable bigint
- `currency` char(3) nullable
- `status`
- `notes` nullable
- timestamps
- soft deletes recommended

Do not add `total_km` or `total_hours` as authoritative fields.

### usage_metric_types

- `id` PK
- `key` unique
- `name`
- `storage_unit`
- `display_unit`
- `kind`
- `precision` unsigned tinyint default 0
- `is_system` boolean
- timestamps

Initial rows:

| key | storage unit | display unit | kind |
|---|---|---|---|
| distance | meter | km | quantity |
| runtime | second | hour | quantity |
| cycles | count | cycles | counter |
| sessions | count | sessions | counter |
| events | count | events | counter |

For distance/runtime/counters prefer integer base units. Avoid floating-point storage.

### component_trackers

- `id` PK
- `component_id` FK
- `usage_metric_type_id` FK
- `warning_threshold` nullable bigint
- `service_limit` nullable bigint
- `is_active` boolean default true
- timestamps
- unique (`component_id`, `usage_metric_type_id`)

Threshold values use the metric's storage unit.

### configurations

Logical identity of a vehicle build.

- `id` PK
- `workspace_id` FK
- `vehicle_id` FK
- `name`
- `description` nullable
- `status`
- timestamps
- soft deletes recommended

### configuration_versions

Immutable snapshot header.

- `id` PK
- `configuration_id` FK
- `version_number` unsigned integer
- `created_by` FK -> users
- `notes` nullable
- `locked_at` nullable timestamp
- timestamps
- unique (`configuration_id`, `version_number`)

A version becomes immutable when referenced by a finalized session or explicitly locked.

### configuration_version_components

- `id` PK
- `configuration_version_id` FK
- `component_id` FK
- `position_or_role` nullable
- `notes` nullable
- timestamps
- unique (`configuration_version_id`, `component_id`)

Consider an additional unique constraint on (`configuration_version_id`, `position_or_role`) only if the product later defines exclusive positions.

### setups

Technical setup identity, separate from hardware configuration.

- `id` PK
- `workspace_id` FK
- `vehicle_id` FK
- `name`
- timestamps

### setup_versions

- `id` PK
- `setup_id` FK
- `version_number`
- `created_by` FK
- `locked_at` nullable
- timestamps
- unique (`setup_id`, `version_number`)

### setup_values

- `id` PK
- `setup_version_id` FK
- `key`
- `value`
- `unit` nullable
- timestamps

Do not hardcode kart-only setup fields into the schema at this stage.

### circuits

- `id` PK
- `workspace_id` nullable FK
- `name`
- `country` nullable
- `notes` nullable
- timestamps

### circuit_layouts

- `id` PK
- `circuit_id` FK
- `name`
- `length_meters` unsigned integer
- `is_active` boolean
- `notes` nullable
- timestamps
- index (`circuit_id`, `is_active`)

### sessions

- `id` PK
- `workspace_id` FK
- `vehicle_id` FK
- `configuration_version_id` FK
- `setup_version_id` nullable FK
- `circuit_layout_id` nullable FK
- `session_type`
- `started_at` nullable
- `completed_laps` nullable unsigned integer
- `duration_seconds` nullable unsigned integer
- `distance_override_meters` nullable unsigned bigint
- `status` (`draft`, `finalized`)
- `finalized_at` nullable
- `created_by` FK
- `notes` nullable
- timestamps

Finalized sessions must not silently change configuration/setup references.

### session_usage_values

Metrics produced by one session.

- `id` PK
- `session_id` FK
- `usage_metric_type_id` FK
- `value` bigint
- `source`
- timestamps
- unique (`session_id`, `usage_metric_type_id`, `source`)

Examples: distance = 50000 m, runtime = 3000 s, sessions = 1.

### usage_batches

Groups propagation caused by one action.

- `id` PK
- `workspace_id` FK
- `source_type`
- `source_id`
- `usage_metric_type_id` FK
- `value` bigint
- `occurred_at`
- `created_by` FK
- timestamps

Use a unique idempotency constraint appropriate to the source, for example (`source_type`, `source_id`, `usage_metric_type_id`).

### component_usage_entries

Append-only ledger.

- `id` PK
- `component_tracker_id` FK
- `usage_batch_id` FK
- `value` bigint
- `occurred_at`
- `notes` nullable
- `created_by` FK
- timestamps

Totals are `SUM(value)` grouped by tracker. Cached totals may be introduced later but are not authoritative.

### tracker_reset_events

- `id` PK
- `component_tracker_id` FK
- `maintenance_record_id` nullable FK
- `reset_at`
- `reason` nullable
- `created_by` FK
- timestamps

`since service` totals are calculated from usage after the latest reset; lifetime totals use the complete ledger.

### component_service_rules

Future table:

- `id` PK
- `component_id` nullable FK
- `component_type_id` nullable FK
- `usage_metric_type_id` FK
- `limit_value` bigint
- `warning_value` nullable bigint
- `description`
- `is_active`
- timestamps

Exactly one of `component_id` and `component_type_id` should be set. Instance rules override type defaults.

### expenses

- `id` PK
- `workspace_id` FK
- `amount_cents` bigint
- `currency` char(3)
- `category`
- `description`
- `occurred_at`
- `related_type` nullable
- `related_id` nullable
- `created_by` FK
- timestamps

Laravel polymorphic relations are acceptable here because expense attachment is secondary data and does not define core historical integrity.

## Delete behaviour

Historical integrity should favour `restrict` or soft-delete rather than cascading deletes for vehicles, components, configurations, setup snapshots and circuits already referenced by sessions.

Safe cascades are appropriate for pure dependent rows whose parent itself cannot be historically removed, such as `configuration_version_components` when deleting an unused draft version.

## Transactions and idempotency

Session finalization must be transactional. The service should lock the session row, confirm `status = draft`, create session usage values and usage batches, propagate component ledger entries, then set `status = finalized` and `finalized_at`.

A unique source/metric constraint on usage batches prevents duplicate propagation if the command is retried.

## Example

Vehicle: Kart #27

Race Build V1:
- Engine #1: distance + runtime
- Chain #3: distance
- Tyres #7: distance + cycles
- Brake Pads #2: distance

Busca Full:
- 1,250 m

Session:
- 40 laps
- 3,000 seconds
- Configuration V1

Generated session metrics:
- distance = 50,000 m
- runtime = 3,000 s
- sessions = 1

Propagation:
- Engine #1: +50,000 m, +3,000 s
- Chain #3: +50,000 m
- Tyres #7: +50,000 m
- Brake Pads #2: +50,000 m

No cycle entry is produced unless the session explicitly produces a cycle metric.

After Engine #1 is replaced, Race Build V2 contains Engine #2. Future sessions point to V2. The old session remains permanently tied to V1 and Engine #1.
