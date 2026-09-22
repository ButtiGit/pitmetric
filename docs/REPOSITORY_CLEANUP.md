# Repository cleanup — Step 7

Step 7 removes proven repository residue without changing PitMetric domain behavior.

## Removed

- Laravel starter `welcome` view and generic example tests.
- Obsolete custom form-picker CSS left behind after the native-first frontend consolidation.
- Unused starter placeholder/Flux icon overrides.
- Packaged brand ZIP committed alongside the real source assets.
- Numbered standalone-game JavaScript fragments that are not loaded by `game_sim.php`.

The canonical standalone-game files remain `public/game_sim.php`, `public/game_assets/game_sim.js`, `game_sim.css` and `game_data_2026.js`.

## Added guardrails

- Root project README and documentation map.
- `.gitignore` protection for packaged brand archives and OS metadata.
- Repository hygiene tests for starter artifacts and duplicate game bundles.
- Frontend regression coverage that rejects the removed custom-picker runtime selectors.

## Intentionally deferred

Dependency/package metadata cleanup is not mixed into this step when it would require lockfile regeneration. Package upgrades, npm audit remediation and Composer metadata changes should be performed as a dedicated dependency-maintenance change with regenerated lockfiles and the full CI/E2E suite.
