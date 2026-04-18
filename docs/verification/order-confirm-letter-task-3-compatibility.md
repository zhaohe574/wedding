# Task 3 Verification — order confirm letter compatibility coverage

## Scope
- Task owner: `worker-3`
- Plan references:
  - `.omx/plans/prd-order-confirm-letter-redesign.md`
  - `.omx/plans/test-spec-order-confirm-letter-redesign.md`
- Verification target:
  - `admin/src/views/order/lists/index.vue`
  - `admin/src/utils/orderConfirmLetterRenderer.ts`
  - `uniapp/src/pages/order_detail/order_detail.vue`
  - `uniapp/src/packages/pages/staff_order_detail/staff_order_detail.vue`
  - `uniapp/src/utils/orderConfirmLetterRenderer.ts`

## Regression-coverage additions
- Reused `admin/tsconfig.order-confirm-letter.json` so the admin renderer utility can be type-checked without pulling in unrelated admin page globals.
- Reused `uniapp/tsconfig.order-confirm-letter.json` so the customer order detail and staff order detail confirm-letter paths are no longer hidden behind the broader active-config exclusions.

## Verification commands
- PASS `./admin/node_modules/.bin/vue-tsc --noEmit -p admin/tsconfig.order-confirm-letter.json`
  - Result: exit `0`
  - Coverage: admin renderer utility stays type-safe in isolation.
- FAIL `./uniapp/node_modules/.bin/vue-tsc --noEmit -p uniapp/tsconfig.order-confirm-letter.json`
  - Result: surfaced three existing staff-order-detail typing issues that are currently hidden by `uniapp/tsconfig.active.json` exclusions:
    - `uniapp/src/packages/pages/staff_order_detail/staff_order_detail.vue(57,59): error TS2322: Type 'unknown' is not assignable to type 'string | number | symbol | undefined'.`
    - `uniapp/src/packages/pages/staff_order_detail/staff_order_detail.vue(719,13): error TS2322: Type '{}' is not assignable to type 'string'.`
    - `uniapp/src/packages/pages/staff_order_detail/staff_order_detail.vue(795,13): error TS2322: Type 'unknown' is not assignable to type 'string'.`
- PASS `cd uniapp && ./node_modules/.bin/eslint src/utils/orderConfirmLetterRenderer.ts src/pages/order_detail/order_detail.vue --format stylish`
  - Result: exit `0`, no lint output.
- PASS `cd uniapp && ./node_modules/.bin/eslint src/packages/pages/staff_order_detail/staff_order_detail.vue --format stylish`
  - Result: exit `0`, no lint output.
- FAIL `cd admin && ESLINT_USE_FLAT_CONFIG=false ./node_modules/.bin/eslint src/utils/orderConfirmLetterRenderer.ts src/views/order/lists/index.vue --format stylish`
  - Result: current admin lint stack is blocked before file analysis by a repo-level config/dependency mismatch:
    - `Error [ERR_PACKAGE_PATH_NOT_EXPORTED]: Package subpath './recommended' is not defined by "exports" in .../@vue/eslint-config-typescript/package.json`

## Source-level compatibility findings
- PASS Admin preview still preserves single-image priority:
  - `admin/src/views/order/lists/index.vue:1849-1854` still returns `full_image_url` first, then falls back to `buildOrderConfirmLetterDataUrl(...)`.
- PASS Customer uniapp preview still preserves single-image delivery:
  - `uniapp/src/pages/order_detail/order_detail.vue:1891-1904` still resolves one `imageUrl` and previews via `uni.previewImage({ urls: [imageUrl], current: imageUrl })`.
- PASS Staff center preview/save/push chain still preserves the current contract:
  - `uniapp/src/packages/pages/staff_order_detail/staff_order_detail.vue:1420-1453` keeps push, preview, and `saveImageToPhotosAlbum(imageUrl)` separated, with save depending on cached `full_image_url`.
- FAIL Version-aware fallback dispatch is still missing in the current branch snapshot:
  - `admin/src/utils/orderConfirmLetterRenderer.ts:103`
  - `uniapp/src/utils/orderConfirmLetterRenderer.ts:121`
  - `admin/src/views/order/lists/index.vue:1854`
  - `uniapp/src/pages/order_detail/order_detail.vue:1892`
  - `uniapp/src/packages/pages/staff_order_detail/staff_order_detail.vue:1433`
  - Finding: the renderer entrypoints still accept `(snapshot, small = false)` and the three call sites still pass only `rendered_snapshot`, so `render_spec_version`-aware v1/v2 dispatch remains an integration requirement rather than a completed compatibility guarantee.

## Compatibility matrix to confirm
1. **Admin preview**
   - Cached image keeps priority via `full_image_url`.
   - Fallback renderer stays wired through `buildOrderConfirmLetterDataUrl(...)`.
2. **Customer uniapp preview**
   - Single-image preview still uses `uni.previewImage({ urls: [imageUrl], current: imageUrl })`.
   - Fallback path still resolves to the renderer output when no cached image exists.
3. **Staff center preview / save / push**
   - Preview still uses the same single-image preview contract.
   - Save flow still depends on cached image availability instead of changing the delivery shape.
   - Push flow remains separate from preview generation.
4. **Version compatibility**
   - Current state: **not yet complete** in this branch snapshot because the dispatcher contract is not wired.
   - Regression implication: the single-image fallback remains intact, but explicit v1/v2 renderer branching still must land before the PRD compatibility criterion is satisfied.

## Notes
- This worker lane is limited to static verification and repeatable coverage artifacts inside the codebase.
- Runtime screenshot capture and live API payload evidence still require an integrated verifier lane or manual client session.
