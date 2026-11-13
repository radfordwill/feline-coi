# Validation for 0.1.0

Completed locally on PHP 8.4 and Node.js:

- PHP syntax checks on plugin and test PHP files.
- 771 pedigree assertions: unrelated founders, full and half siblings, parent/offspring, grandparent/grandchild, first cousins, previously inbred parents, missing ancestry, repeated ancestor slots, record order, graph validation, and 750 comparisons against an independently implemented tabular relationship matrix over random pedigrees.
- 25 WordPress contract assertions using minimal WordPress/database stubs: route permission callbacks, login/capability checks, sanitized saves, stale-write conflicts, server-side COI, account isolation, document owner checks, rejection of HTML uploads, valid saved cat requirement, and unchanged persistence after graph errors.
- JavaScript syntax check.
- DOM interaction tests with jsdom: add/link cats, save requirement, 0% and 25% display, parent deletion protection, document upload, review before applying extracted fields, creating reviewed parent records, preservation of local work on save conflict, and rendering untrusted names as text.

These checks do not establish production readiness. No live WordPress/MySQL installation, actual CFA document, or paid OpenAI API request was available. Browser layout/screenshot testing could not run in this environment; the included Playwright test is provided for a normal development environment. The DOM tests use mocked HTTP responses; the engine tests execute real PHP calculations.

## Run the included checks

From the plugin directory:

```sh
php tests/engine-test.php
php tests/wp-boundary-test.php
node --check assets/app.js
npm install --no-save jsdom playwright
node tests/ui-test.cjs
CHROMIUM_PATH=/path/to/chromium node tests/browser-test.cjs
```

Development dependencies are only for testing; the installed plugin needs no Node.js runtime or npm packages. PHP test harness files are standalone development tools.

## Live staging checks still required

1. Activate on the target WordPress site and verify both custom tables are created with the site's prefix.
2. Check REST nonce enforcement with real WordPress cookies, and verify cross-account privacy using two accounts, including direct document URLs.
3. Add founders, siblings and a kitten; confirm 25% for the sibling mating and 0% for unrelated founders under the documented assumptions.
4. Export, import, save, reload and verify names, registration numbers and parent links.
5. Save from two tabs; confirm stale saves get HTTP 409 and preserve the local work for export.
6. Upload actual JPG, PNG and PDF certificates; test 2 MB and 12-document limits, download integrity and deletion.
7. Configure a compatible API model and key; test clear, blurry, incomplete and unrelated documents, refusals, bad API access and interrupted requests. Verify field review and ancestry matching before saving.
8. Test WordPress personal-data export/erasure and account deletion, plus preservation after deactivation.
9. Check theme, mobile layout, keyboard navigation, caching exclusions and hosting upload/body limits.
10. Check PHP 7.4 compatibility on a real host if supporting that minimum; executable validation here used PHP 8.4.
