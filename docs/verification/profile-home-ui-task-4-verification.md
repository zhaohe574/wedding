# Task 4 Verification — profile home / personal-center refactor

## Scope
- Spec: `.omx/specs/deep-interview-profile-home-ui.md`
- Target pages:
  - `uniapp/src/pages/index/index.vue`
  - `uniapp/src/pages/user/user.vue`
- Target widgets:
  - `uniapp/src/components/widgets/user-info/user-info.vue`
  - `uniapp/src/components/widgets/quick-entry/quick-entry.vue`
  - `uniapp/src/components/widgets/wedding-countdown/wedding-countdown.vue`
- Verification worker: `worker-3`

## Static checks
- PASS `cd uniapp && npm run type-check:active` → exit 0
- PASS targeted ESLint on the five target files → exit 0
- PASS `cd uniapp && npm run validate:config` → all config files passed
- PASS LSP diagnostics on each target file → 0 errors in all five files
- FAIL `cd uniapp && npm run build:h5` → environment-only failure before app compilation completed

### Build failure root cause (environment-only)
`vite build --debug` reports a platform-mismatched esbuild binary in `uniapp/node_modules`: the installed package is `@esbuild/win32-x64`, while this worker runs on Linux and needs `@esbuild/linux-x64`. This is an environment/runtime dependency issue, not a source-level error in the target Vue files.

## Acceptance checklist against spec
1. **Homepage hero remains protected** — PASS
   - The hero swiper, slogan overlay, and dots remain the dominant first-screen structure in `uniapp/src/pages/index/index.vue:6-55`.
2. **Homepage body feels more restrained below the hero** — FAIL
   - The CTA still contains explanatory helper copy in `uniapp/src/pages/index/index.vue:58-65`.
   - The team section still carries explanatory subtitle text in `uniapp/src/pages/index/index.vue:68-79`.
3. **Personal center reads as a lighter account hub instead of stacked widgets** — PARTIAL
   - `uniapp/src/pages/user/user.vue:6-42` does separate a fixed skeleton from the widget zone, but the experience still depends on multiple independent widget cards.
4. **Widget internals reduce title/subtitle/shell burden** — FAIL
   - `user-info` still uses a profile subtitle plus a large frosted card shell in `uniapp/src/components/widgets/user-info/user-info.vue:13-23` and `:121-183`.
   - `quick-entry` still renders a title row, subtitle, and card-in-card grid in `uniapp/src/components/widgets/quick-entry/quick-entry.vue:3-24` and `:86-167`.
   - `wedding-countdown` still presents descriptive copy inside a prominent gradient card in `uniapp/src/components/widgets/wedding-countdown/wedding-countdown.vue:3-9` and `:131-168`.
5. **Series direction matches the earlier “more premium / more restrained” dynamic-list refactor** — NOT YET EVIDENCED
   - Current source still leans on explanatory copy and strong container treatments, so this direction is not yet proven by code inspection.
6. **Core entry functionality remains intact** — PASS
   - Homepage still exposes schedule-query and staff-list entry points in `uniapp/src/pages/index/index.vue:58-79`.
   - User profile tap/login handling remains in `uniapp/src/components/widgets/user-info/user-info.vue:79-85`.
   - Quick-entry routing/login gating remains in `uniapp/src/components/widgets/quick-entry/quick-entry.vue:57-77`.
   - Countdown still guards on login + wedding info and updates reactively in `uniapp/src/components/widgets/wedding-countdown/wedding-countdown.vue:90-124`.

## Verification conclusion
- Source-level safety checks pass.
- The requested home/profile refinement is **not fully satisfied yet** based on the current source snapshot.
- The only build blocker observed in this worker pane is the cross-platform esbuild installation inside `uniapp/node_modules`.

## Environment-only gaps
- No running uniapp client was available in this worker pane, so no screenshot or tap-through proof was captured.
- `build:h5` cannot complete until dependencies are reinstalled for Linux (`npm ci` or equivalent in `uniapp/`).
- No dedicated automated UI tests exist for these two pages/widgets in the repository, so acceptance remains source-inspection-based until a runtime verifier lane captures visuals/interactions.
