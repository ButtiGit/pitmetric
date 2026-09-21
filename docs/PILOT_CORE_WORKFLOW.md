# PitMetric Pilot Core Workflow

## Active scope freeze

During the Pilot milestone, PitMetric does not add new macro product areas unless real pilot evidence shows that the existing product cannot solve an important repeated workflow.

Engineering work should first improve the reliability, clarity and speed of this operating loop:

`Vehicle → Components → Configuration → Event / Session → Usage → Maintenance`

Supporting surfaces such as technical setups, circuits, costs, telemetry, team management and reports may be refined when they strengthen this loop, but they are not reasons to expand the product boundary.

## Workflow contract

### 1. Vehicle

An active vehicle is the root of the operational history. New sessions and configuration versions must only use active vehicles in the current workspace.

### 2. Components

A physical component can have only one active installation. Installation and removal history is preserved rather than rewritten.

### 3. Configuration

A configuration version is a snapshot of the physical component set for one vehicle. Creating a new version synchronizes the vehicle's active installations. Once a version is used by a finalized session it is locked and remains historical evidence.

### 4. Event / Session

A recorded session must use a configuration belonging to its vehicle. Before finalization, PitMetric verifies that the selected configuration still matches the components physically installed on that vehicle. If the physical build has changed, a new configuration version is required before the session can be finalized.

When an event session uses a newer configuration version, the event entry is updated to that version so the next trackside action starts from the build actually used.

### 5. Usage

Finalization is the only point where calculated session usage is propagated. Usage batches and component usage entries are idempotent so a session cannot double-count wear when finalization is retried.

The configuration snapshot determines which components receive distance, runtime, cycles and session-count usage.

### 6. Maintenance

Maintenance health is derived from component usage and reset history. A finalized session immediately changes maintenance state when new usage crosses warning or service thresholds. Historical maintenance work remains preserved.

## Reliability invariants

The Pilot core loop is considered broken if any of the following can happen:

- usage is assigned to a component that is not part of the vehicle build being recorded;
- the same finalized session can add usage twice;
- an event entry silently points to an older build after a newer build was actually used;
- history is destroyed to make the current state easier to edit;
- a record from another workspace can be selected or mutated;
- a failed operational action leaves a partial configuration, usage, cost or maintenance write;
- the UI hides the order of the core operating flow.

## Exit criteria for this consolidation phase

Before adding another macro feature, the following should stay true:

1. The complete core flow is covered by automated regression tests.
2. Session finalization rejects stale physical/configuration state before any usage is written.
3. Finalization remains idempotent.
4. Event entries remain aligned with the configuration actually used.
5. Maintenance reacts to propagated usage in the same workflow.
6. The core flow is visible and navigable from its operational screens.
7. CI remains green with Pint, PHPStan and the feature suite.

Any new macro feature proposed before these conditions are stable should be deferred or justified by observed pilot use.
