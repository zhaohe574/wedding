# Task 3 Verification — order confirm letter audit matrix

## Scope
- Task owner: `worker-3`
- Lane: QA + hidden-bug audit + evidence only
- Plan references:
  - `.omx/plans/prd-order-confirm-letter-audit.md`
  - `.omx/plans/test-spec-order-confirm-letter-audit.md`
- Evidence source: read-only inspection of current server/admin/uniapp code in this worker worktree

## Verification commands / evidence collection
- PASS `lsp_diagnostics_directory(admin)`
  - Command: `npx tsc --noEmit --pretty false --project .../admin/tsconfig.json`
  - Result: `totalErrors: 0`
- PASS `lsp_diagnostics_directory(uniapp)`
  - Command: `npx tsc --noEmit --pretty false --project .../uniapp/tsconfig.json`
  - Result: `totalErrors: 0`
- PASS `lsp_diagnostics(admin/src/views/order/lists/index.vue)`
  - Result: `diagnosticCount: 0`
- PASS `lsp_diagnostics(admin/src/utils/orderConfirmLetterRenderer.ts)`
  - Result: `diagnosticCount: 0`
- PASS `lsp_diagnostics(uniapp/src/pages/order_detail/order_detail.vue)`
  - Result: `diagnosticCount: 0`
- PASS `lsp_diagnostics(uniapp/src/packages/pages/staff_order_detail/staff_order_detail.vue)`
  - Result: `diagnosticCount: 0`
- PASS `lsp_diagnostics(uniapp/src/utils/orderConfirmLetterRenderer.ts)`
  - Result: `diagnosticCount: 0`
- FAIL local package-binary verification from shell
  - Command: `ls admin/node_modules/.bin/vue-tsc uniapp/node_modules/.bin/vue-tsc admin/node_modules/.bin/eslint uniapp/node_modules/.bin/eslint`
  - Result: all four paths missing; both apps have `package-lock.json` but no checked-out `node_modules/`
- FAIL PHP CLI syntax verification in this pane
  - Command: `command -v php`
  - Result: `no-php`

## Audit matrix against PRD / test spec

### A. Canonical authority
- PASS single order-level current pointer exists in the service contract.
  - Evidence: `server/app/common/service/OrderConfirmLetterService.php:100-166`
  - Notes: generation reuses the current version when `snapshot_hash` matches and writes back one `order.current_confirm_letter_id`.
- FAIL staff generation still shapes the canonical snapshot by operator identity.
  - Evidence:
    - `server/app/api/logic/StaffCenterLogic.php:2201-2214`
    - `server/app/common/service/OrderConfirmLetterService.php:406-442`
    - `server/app/common/service/OrderConfirmLetterService.php:479-520`
  - Finding: staff generation passes `staffId` into `generate(...)`, and qualification/snapshot staff/team resolution filters order items by that `staffId`. That still lets the generator identity influence canonical content, which conflicts with the approved order-level contract.

### B. Version semantics / compatibility
- PASS content-hash reuse is implemented.
  - Evidence: `server/app/common/service/OrderConfirmLetterService.php:97-118,474-476`
  - Notes: hash includes `RENDER_SPEC_VERSION` plus the serialized snapshot, so render-spec changes participate in versioning.
- PASS historical records keep their own `render_spec_version` and are returned as stored.
  - Evidence: `server/app/common/service/OrderConfirmLetterService.php:105-112,317-330,560-572`
- FAIL SQL defaults and URL length remain inconsistent with runtime rules.
  - Evidence:
    - `server/sql/2.0.0.20260201/update.sql:2649-2659`
    - `server/app/common/service/OrderConfirmLetterService.php:20,127-136`
    - `server/app/adminapi/validate/order/OrderValidate.php:51-54`
    - `server/app/api/validate/StaffCenterValidate.php:73-77`
  - Finding: SQL still defaults `render_spec_version` to `v1` and limits cached URLs to `varchar(255)`, while runtime creates new records as `v2` and validators allow `max:500`.
- PARTIAL lazy migration behavior appears safe but is not explicitly codified.
  - Evidence: no batch migration/backfill path found in the inspected service/controller code.
  - Risk: compatibility currently relies on “do nothing unless regenerated,” but the default/schema mismatch above means compatibility is still incomplete.

### C. Asset persistence protocol
- FAIL admin preview is fallback-only and never persists assets.
  - Evidence:
    - `admin/src/api/order.ts:128-129`
    - `admin/src/views/order/lists/index.vue:1808-1856`
  - Finding: `orderConfirmLetterAssets` exists, but the admin page never calls it. Preview falls straight from `full_image_url` to `buildOrderConfirmLetterDataUrl(...)`.
- FAIL staff preview/save flow is also missing render -> upload -> saveAssets -> refresh.
  - Evidence:
    - `uniapp/src/api/staffCenter.ts:139-160`
    - `uniapp/src/packages/pages/staff_order_detail/staff_order_detail.vue:1324-1455`
  - Finding: `staffCenterOrderConfirmLetterSaveAssets` and `...RegenerateAssets` exist, but the page never calls them. Save-to-album only works when `full_image_url` already exists.
- FAIL “preview implies savable asset” is not satisfied.
  - Evidence:
    - `admin/src/views/order/lists/index.vue:1848-1856`
    - `uniapp/src/packages/pages/staff_order_detail/staff_order_detail.vue:1430-1455`
  - Finding: both clients can preview a data URL fallback without establishing a downloadable cached URL.

### D. Push / notification / visibility
- FAIL push gating does not require completed asset persistence.
  - Evidence: `server/app/common/service/OrderConfirmLetterService.php:188-240`
  - Finding: push only validates current-version state, payment, and user binding; it does not require `full_image_url` / `thumb_image_url` to be present.
- FAIL notification target remains bound to `letter_id`, with no stale fallback.
  - Evidence:
    - `server/app/common/service/OrderConfirmLetterService.php:222-229`
    - `uniapp/src/packages/pages/notification/index.vue:142`
    - `uniapp/src/pages/order_detail/order_detail.vue:1805-1828`
  - Finding: the pushed notification target is `confirm_letter + letter_id`. User-side notification open first tries `getOrderConfirmLetterById(letter_id)` and falls back only to `null`, not to current effective version or order detail.
- PASS user visibility is still current-effective-pushed-only.
  - Evidence:
    - `server/app/common/service/OrderConfirmLetterService.php:244-305`
    - `uniapp/src/pages/order_detail/order_detail.vue:1909-1955`
  - Notes: user `current()` / `byId()` reject outdated or unpushed letters; history exposes records but only marks the current pushed record as `can_view = 1`.

### E. Renderer / visual redesign / compatibility
- PASS v2 renderer redesign is present in both admin and uniapp renderers.
  - Evidence:
    - `admin/src/utils/orderConfirmLetterRenderer.ts:290-588`
    - `uniapp/src/utils/orderConfirmLetterRenderer.ts:290-588`
  - Notes: v2 includes the required sectioned structure (`婚礼信息`, `服务团队`, `费用确认`, `备注与联系`) and a version dispatcher via `renderSpecVersion` / `isV2Spec(...)`.
- PASS fallback rendering is spec-aware at the call sites that still use renderer fallback.
  - Evidence:
    - `admin/src/views/order/lists/index.vue:1848-1856`
    - `uniapp/src/packages/pages/staff_order_detail/staff_order_detail.vue:1430-1435`
    - `uniapp/src/pages/order_detail/order_detail.vue:1890-1894`

## Hidden-bug audit summary
| Topic | Status | Evidence | Conclusion |
| --- | --- | --- | --- |
| Order-level canonical contract | FAIL | `StaffCenterLogic.php:2201-2214`, `OrderConfirmLetterService.php:406-442,479-520` | Staff identity still changes canonical snapshot contents. |
| Preview implies savable asset | FAIL | `admin/src/views/order/lists/index.vue:1848-1856`, `staff_order_detail.vue:1430-1455` | Preview works without upload/saveAssets/readback. |
| Push success but user unavailable | FAIL | `OrderConfirmLetterService.php:188-240` | Push can succeed with empty asset URLs. |
| Stale notification fallback | FAIL | `OrderConfirmLetterService.php:222-229`, `notification/index.vue:142`, `order_detail.vue:1805-1828` | Old `letter_id` deep link has no current-letter fallback. |
| SQL / validator / API consistency | FAIL | `update.sql:2656-2659`, validators `max:500` | DB schema still mismatches runtime contract. |
| Historical visibility restriction | PASS | `OrderConfirmLetterService.php:278-305`, `order_detail.vue:1936-1944` | User history remains record-only except current pushed letter. |
| v1/v2 renderer compatibility dispatch | PASS | renderer utils `:290-588` | Dispatcher is present and call sites pass `render_spec_version`. |
| Bulk migration side effects | PASS (no batch path found) | inspected service/controller code | No eager backfill path found in current branch snapshot. |

## Concrete blockers / risks for the leader
1. **Canonical semantics blocker:** staff-generated snapshots still filter by `staffId`; this is the clearest mismatch with the approved order-level contract.
2. **Asset protocol blocker:** both admin and staff UIs expose preview before any upload/saveAssets/refresh round-trip; this blocks the “preview implies savable asset” acceptance criterion.
3. **Notification blocker:** stale notification fallback is not implemented anywhere in the inspected client/server flow.
4. **Schema blocker:** SQL defaults/column lengths still conflict with the runtime `v2` + 500-char validator contract.
5. **Tooling note:** this worktree lacks checked-out frontend `node_modules/`, and PHP CLI is unavailable in the pane, so shell-based lint/PHP syntax verification could not be completed here; TypeScript evidence above came from code-intel diagnostics instead.

## Conclusion
The branch already has meaningful progress on renderer redesign, current-only user visibility, and version-aware rendering. The highest-risk gaps still open are canonical staff filtering, missing asset-persistence wiring/gating, stale notification fallback, and the unresolved SQL/runtime contract mismatch.
