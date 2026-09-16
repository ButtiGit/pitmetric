# Technical Setup domain

PitMetric intentionally separates two concepts:

- **ConfigurationVersion**: the physical/component build installed on a vehicle.
- **TechnicalSetup**: the mutable adjustment profile used to tune that vehicle.
- **SetupSnapshot**: the immutable copy of a TechnicalSetup captured when a Session is recorded.

A session always receives one SetupSnapshot. If an explicit TechnicalSetup is selected, its name and values are copied. If no setup is selected, PitMetric uses the most recently updated active setup for that vehicle. If none exists, the session still receives an empty `Unspecified setup` snapshot.

Editing or archiving a TechnicalSetup never changes previous SetupSnapshots. This guarantees that historical sessions retain the exact technical context that was recorded for the run.
