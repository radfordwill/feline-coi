# Feline COI

WordPress plugin for private cat pedigrees, pedigree-based inbreeding coefficients, and reviewed CFA registration-document imports.

**Current baseline: 0.1.0 evaluation build.** This repository now contains the plugin source and development tracking. It is not a production-validated release and is not affiliated with CFA.

## Development tracking

Start with [the development tracker](https://github.com/radfordwill/feline-coi/issues/1) and [ROADMAP.md](ROADMAP.md). Proposed features and outstanding validation are tracked as issues; they must not be presented as completed features.

| Priority | Work | Status |
| --- | --- | --- |
| P1 | [Complete multigeneration pedigree extraction and review](https://github.com/radfordwill/feline-coi/issues/2) | Planned |
| P1 | [Explain ancestry completeness and qualified 0% results](https://github.com/radfordwill/feline-coi/issues/3) | Planned |
| P1 | [Live WordPress validation and repeatable regression checks](https://github.com/radfordwill/feline-coi/issues/5) | Pending validation |
| P2 | [Larger private pedigree PDF uploads](https://github.com/radfordwill/feline-coi/issues/4) | Planned |
| P2 | [Merge imports while preserving existing records](https://github.com/radfordwill/feline-coi/issues/6) | Planned |

## Available in 0.1.0

- Signed-in users' private pedigrees: up to 500 cats, with registered name, optional registration number, sex, sire and dam.
- Individual and proposed-mating pedigree COI using all recorded ancestor relationships, including repeated and inbred ancestors.
- First-five-generation ancestor-slot coverage and maximum linked ancestry depth.
- JSON pedigree backup import/export and revision-based conflicting-save protection.
- Owner-scoped JPG/PNG/PDF document storage, up to 12 documents per account and 2 MB per document.
- Optional administrator-only OpenAI reading of a focal cat and its direct parents, with review before applying and a separate save.
- WordPress personal-data export/erasure integration.

Full multigeneration extraction, import merging, and a local selectable-PDF-text extraction path are **not implemented**. The document reader does not look up registration records or verify document authenticity with CFA.

## Installation

Requires WordPress 6.5+ and PHP 7.4+ according to the plugin header; the original executable validation used PHP 8.4. Evaluate on staging first.

1. Put these plugin files inside a directory named `feline-coi` under `wp-content/plugins`, or ZIP that directory and use **Plugins > Add New Plugin > Upload Plugin**.
2. Activate **Feline COI**.
3. Add `[feline_coi]` to a WordPress page, or open **Tools > Feline COI** while signed in.
4. Exclude the calculator page and its private REST endpoints from page/CDN caching.

See [readme.txt](readme.txt) for complete usage, API-key configuration, external-service disclosures, storage limits and data-retention behavior. No API key is needed for ordinary COI calculation or pedigree storage.

## Calculation limits

Offspring COI is the kinship between its parents; an ancestor's COI is not passed down by repeatedly halving it. Unknown founders are assumed unrelated and non-inbred. A result of 0% means no common ancestors were identified under the available pedigree and these assumptions. It does not prove unrelatedness across all history, a genomic COI of zero, or absence of inherited disease.

## Validation status

[VALIDATION.md](VALIDATION.md) records historical local results: 771 pedigree assertions, 25 WordPress contract assertions using stubs, JavaScript syntax, and DOM interaction checks. Those results are **not GitHub Actions results**. Real WordPress/MySQL, full browser/mobile rendering, minimum-PHP compatibility, and live OpenAI document-reading validation remain outstanding. Importing this baseline into GitHub did not rerun those tests.

## Public-source privacy

Commit source, documentation and synthetic fixtures only. Do not commit personal certificates or pedigrees, owner/contact information, database exports, uploads, production configuration, credentials or API keys. Report security problems privately through the repository owner rather than posting exploitable details or secrets in a public issue.

## License

[GNU GPL version 2 or later](LICENSE).
