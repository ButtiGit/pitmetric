# Step 4 — Frontend consolidation

PitMetric has two frontend surfaces and therefore two Vite entrypoints:

- `resources/js/app.js` for authenticated/product UI.
- `resources/js/public.js` for the public website.

Each JavaScript entrypoint owns the CSS and runtime behavior for its surface. Blade layouts load only the relevant entrypoint.

## App surface

The app bundle owns the application design system, native-first form styling, mobile rules, onboarding, demo workspace behavior and workspace form behavior.

Form controls intentionally keep browser-native select/date/time/month/week semantics. `workspace-forms.js` is limited to application behavior such as modal validation rehydration, vehicle/configuration filtering, responsive table metadata and duplicate-submit protection. It must not grow into a replacement-control framework.

The previous custom picker stack (`form-controls`, `form-controls-composite`, popovers and `form-control-guard`) was removed.

## Public surface

The public bundle owns public typography, editorial styling, public-site motion, language selection, cookie preferences and the partner notice.

The previous `public-polish.css` and `public-simplify.css` patch layers were folded into the public stylesheet so the final visual state is defined directly rather than through overrides.

## Rule of thumb

Add code to the surface that actually uses it. Shared domain behavior belongs in the backend or an explicitly shared module; do not make the public site depend on the authenticated app bundle, and do not introduce global DOM observers to replace native form controls.
