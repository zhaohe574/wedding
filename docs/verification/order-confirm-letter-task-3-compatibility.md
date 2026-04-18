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
- Added `admin/tsconfig.order-confirm-letter.json` so the admin renderer utility can be type-checked without pulling in unrelated admin page globals.
- Added `uniapp/tsconfig.order-confirm-letter.json` so the customer order detail and staff order detail confirm-letter paths are no longer hidden behind the broader active-config exclusions.

## Verification commands
- Pending command capture from this worker pane.

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
   - Admin + uniapp call sites remain ready to pass through `render_spec_version`-aware renderer updates without removing the existing v1 fallback path.

## Notes
- This worker lane is limited to static verification and repeatable coverage artifacts inside the codebase.
- Runtime screenshot capture and live API payload evidence still require an integrated verifier lane or manual client session.
