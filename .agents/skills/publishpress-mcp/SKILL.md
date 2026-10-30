---
name: publishpress-mcp
description: Implement, audit or test PublishPress Cart MCP abilities using native Cart controls, storage formats and permissions.
---

# PublishPress Cart MCP integration

## Native integration

Use `includes/mcp/` and the existing native hook/require-based architecture. Keep PHP 7.4 compatibility and focused files below 500 lines. Use canonical `ppcart_` hooks and helpers. Do not introduce legacy prefixes or inspect vendor copies as implementation references.

Register private `publishpress-cart/` abilities in the shared `publishpress` category. Guard optional API availability and duplicate category/action registration. Hub owns transport, authentication and action selection; Cart owns native operation permissions and validation. Check permissions in both the ability callback and the executing service. An Administrator role never overrides a denied capability.

Free owns common registration, typed input and native field adapters. Pro owns its controls and services, extending neutral Free hooks only when the actual Pro edition and native dependency are active. Use the existing native settings and metabox instances, rather than constructing duplicate admin controllers or simulating form requests/nonces.

## Values and writes

For each exposed control, trace its native choices, default, getter, sanitizer and setter. Require exact JSON types; distinguish booleans, numeric strings, integer zero, null and empty values. Native repeaters can store empty scalar values as null and zero as integer zero. Never freeze dynamic product/page/plan IDs into registration-time enums.

Validate complete patches and all rows before any write. Preserve omitted attributes and unrelated metadata. Ordered replacements remove omitted rows; additional bumps are positional. Carry revisions through previews and saves, reject stale revisions, and check native read-back. Use raw option snapshots to distinguish absent/empty storage from filtered email defaults. Honor readonly, disabled and forced controls.

Use native object, taxonomy, publication and financial capabilities. Validate related products, pages and plans with their own permissions and native plan requirements. Keep secrets, password defaults, generated gateway IDs and bearer links out of output. Preserve required hidden gateway data internally.

Payment plans and coupons must use the native synchronization path, with recoverable errors instead of request termination. Refunds and cancellations must use stored payment identity and native domain functions. Preview makes no network requests. Do not automatically retry uncertain gateway outcomes or claim that local rollback reverses remote effects. Avoid restoring over concurrent native edits.

## Verification

Use the existing Codeception Unit and Integration suites, including `Integration/Mcp`. Exercise registered schemas through the released Adapter `McpTool`, not only direct service callbacks. Test Free and the actual Pro bootstrap, exact storage round trips, malformed mixed patches, stale revisions, permission denials and gateway failure boundaries. Keep tests isolated from production data. Report skipped adapter cases and untested live providers explicitly.

Keep usage documentation focused on current behavior and supported boundaries. Do not modify vendor files, stage changes or commit unless requested.

## Native feature audit

Use the bundled Free `docs/dev/mcp-feature-coverage.md` and `docs/dev/mcp-prompt-examples.md` to map native screens to discovery, reads, writes and explicit boundaries. Compare the actual control provider and native read-back, rather than inferring coverage from action names. Pro custom tax rates use the native formatter and synchronization service; handle uncertain local/remote outcomes without terminating the API request. Product purchase notifications are positional configurations, not an email-sending action.

Default/address grid actions belong to Pro; Free shares the existing native renderer defaults only. Preserve presence-based flags by removing false keys, and preserve unmentioned fields when initializing a partial grid. Download configuration uses editable attachments in native secure roots and never returns source/bearer URLs. Order download access has separate order-bound revisions. Integration configuration is not execution: preserve hidden authentication by saved row identity, validate native provider/trigger choices, and reject privileged user-role rules. Subscription pause/resume shares the native operation with AJAX, uses persisted gateway mode, and retains ownership plus MCP payment/object checks.
