# Worker-3 Review & Documentation — dynamic-detail-ui

Date: 2026-04-17
Task: 3
Target: `uniapp/src/pages/dynamic_detail/dynamic_detail.vue`
Plan refs:
- `.omx/plans/prd-dynamic-detail-ui.md`
- `.omx/plans/test-spec-dynamic-detail-ui.md`
- `.omx/plans/ralplan-dynamic-detail-ui-short.md`

## Static verification

- PASS — `cd uniapp && npm run type-check:active`
  - Exit: 0
- PASS — `cd uniapp && npx eslint src/pages/dynamic_detail/dynamic_detail.vue`
  - Exit: 0
- PASS — `cd uniapp && npm run validate:all`
  - Exit: 0
- FAIL (environment) — `cd uniapp && npm run build:mp-weixin`
  - Exit: 1
  - Failure: Vite cannot load `vite.config.ts` because the workspace currently contains the Windows esbuild binary (`@esbuild/win32-x64`) while this worker is running on Linux/WSL and needs `@esbuild/linux-x64`.
  - Repro detail: `npx vite build --config vite.config.ts --debug` prints the platform-mismatch stack trace from `node_modules/esbuild/lib/main.js`.

## Review findings

### 1) Content-first hierarchy is implemented
- Hero video/image leads the page before any support or comment surface: `dynamic_detail.vue:9-43`.
- Author/meta/favorite/content are grouped into one lead shell instead of separate competing cards: `dynamic_detail.vue:45-124`.
- Comment surface is clearly downstream of the lead content and support band: `dynamic_detail.vue:184-297`.

### 2) Support band has been unified into one secondary surface
- Stats and collect/share actions now live under one `dynamic-detail__support-band`: `dynamic_detail.vue:126-182`.
- This satisfies the PRD requirement to stop treating actions/stats as two separate blocks.

### 3) Navbar rhythm and warm-brand token continuity are aligned
- Page content uses `var(--wm-space-page-x, 37rpx)` at `dynamic_detail.vue:1254-1256`.
- `BaseNavbar` uses the same page-x token at `BaseNavbar.vue:166-170`.
- The page continues to pull tone/radius/shadow tokens from `dynamic.scss:1-48`.

### 4) Heavy shared card utilities are no longer being inherited implicitly
- Shared utility definitions still exist in `public.scss:32-41`, but the page no longer references `wm-panel-card` / `wm-soft-card`.
- Repo check result: `rg -n "wm-panel-card|wm-soft-card" uniapp/src/pages/dynamic_detail/dynamic_detail.vue` → `NO_UTILITY_CARD_MATCHES`.
- The page now uses explicit local surfaces for hero/lead/support/comments/popup at `dynamic_detail.vue:1258-1884`, which is the intended audit outcome from the plan.

### 5) Location duplication is removed from the template surface
- Location is folded into `authorMetaText`: `dynamic_detail.vue:595-603`.
- Repo check result: `rg -n "dynamic-detail__location|location-row" uniapp/src/pages/dynamic_detail/dynamic_detail.vue` → `NO_STANDALONE_LOCATION_ROW_MATCHES`.

### 6) Protected interaction wiring is still present
- Favorite branch remains in the lead area and calls `handleFavorite`: `dynamic_detail.vue:92-103`, `921-940`.
- Like / collect / comment-entry actions remain wired from the support band: `dynamic_detail.vue:136-180`, `887-919`, `1039-1060`.
- Comment sort / pagination / reply expansion remain intact: `dynamic_detail.vue:194-210`, `800-885`.
- Popup composer, emoji handling, submit, delete, and preview ordering remain intact: `dynamic_detail.vue:300-413`, `955-1235`.
- Gallery preview order still preserves the hero-first offset via `idx + 1`: `dynamic_detail.vue:30-41`, `1200-1209`.

## Verification matrix documentation

### Case A — image detail
- Expected evidence:
  - hero image is the first-screen lead
  - gallery preview order remains hero-first then thumbnails
  - support band is secondary in-frame
  - comments read as tertiary below the fold or lower-emphasis section
- Required capture status: pending runnable UniApp target / screenshot path

### Case B — no-media detail
- Expected evidence:
  - no empty hero placeholder is rendered
  - author/meta + content form the first-screen lead
  - support band remains discoverable below lead content
- Required capture status: pending runnable UniApp target / screenshot path

### Case C — video detail
- Expected evidence:
  - video hero leads the page
  - playback works
  - support band/comments remain visually secondary
- Required capture status: pending runnable UniApp target / screenshot path

### Interaction checklist for live follow-up
- collect dynamic
- share dynamic
- like dynamic
- open/close popup
- emoji open/insert/close
- submit comment
- delete comment
- like comment
- reply comment
- switch sort / load more comments / load more replies
- favorite staff flow when `detail.can_favorite === true`
- image preview order and video playback when applicable

## Reviewer conclusion

No blocking code-quality regressions were found in the current `dynamic_detail.vue` implementation relative to the approved plan. The remaining acceptance gaps are operational rather than structural: (1) build is blocked by the cross-platform esbuild install in this workspace, and (2) screenshot/live-interaction evidence still requires a runnable UniApp target or device/emulator session.
