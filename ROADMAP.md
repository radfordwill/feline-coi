# Development roadmap

The [master tracker](https://github.com/radfordwill/feline-coi/issues/1) is the entry point for development status. The 0.1.0 source baseline is preserved without functional changes.

## Next priorities

1. **Reliable full-pedigree ingestion — #2.** Extract all documented generations, deduplicate by registration identity, preserve source provenance, and require review before changing the graph. Prefer selectable text when possible; handle scans separately.
2. **Honest interpretation — #3.** Distinguish insufficient ancestry from documented 0%, show completeness and founder assumptions, and explain the difference between ancestry percentages and COI. Add synthetic examples for unrelated matings after inbred ancestors.
3. **Staging and regression validation — #5.** Verify real WordPress/MySQL, owner isolation, nonce enforcement, concurrency, export/erasure, mobile layout and API-reader error handling. Establish CI and supported-runtime evidence.
4. **Realistic document capacity — #4.** Support pedigree PDFs larger than the current 2 MB limit while enforcing bounded account storage and owner-only access.
5. **Incremental imports — #6.** Preview merges and conflicts, make repeat imports idempotent, and offer a recoverable pre-import snapshot.

## Definition of done

An issue is complete only when the implementation is committed, relevant checks and limitations are recorded, and the behavior satisfies its acceptance criteria. A local test, mocked test, live integration test and owner validation are different forms of evidence; record each accurately. Update the version and changelog when behavior changes. Link commits or pull requests to their issue.

## Data policy

Use synthetic fixtures for public regression tests. Actual user documents and personal pedigree databases stay outside the public repository. Do not copy private source documents into issues, examples or build artifacts.
