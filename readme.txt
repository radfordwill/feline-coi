=== Feline COI ===
Contributors: radfordwill
Tags: cats, pedigree, inbreeding, persian
Requires at least: 6.5
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Private cat pedigrees, individual and proposed-mating COI, and CFA registration document uploads.

== Description ==
A first working plugin for user-owned pedigrees. It uses WordPress accounts and two database tables; no separate website or database service is needed.

* Each signed-in user can maintain up to 500 cats.
* Store registered names, optional registration numbers, sex, sire and dam.
* Calculate an individual cat's pedigree COI or a proposed mating's expected offspring COI.
* Kinship recursion includes all recorded ancestry, repeated ancestors and inbred ancestors.
* Display maximum ancestry depth and first-five-generation ancestor-slot coverage.
* Reject cycles, missing parent IDs, duplicate cat IDs and registration numbers, inconsistent sex roles, and pedigrees deeper than 50 generations.
* Export and import pedigree JSON backups; downloads include current local changes.
* Conflict detection prevents one tab silently overwriting another tab's saved pedigree.
* Attach up to 12 JPG, PNG or PDF documents per user, maximum 2 MB each.
* Documents are kept in the WordPress database and delivered only through owner-scoped authenticated endpoints. They have no public Media Library URLs.
* Optional administrator-only reading with OpenAI. Extracted fields require review before being applied locally, then a separate save.
* WordPress personal-data export and erasure integration; account deletion removes the account's records.

This release is intended for staging evaluation before public use. It is not CFA-affiliated, does not verify registrations with CFA, and does not automatically retrieve ancestry from CFA.

== Installation ==
1. Back up your WordPress site and database.
2. Go to Plugins > Add New Plugin > Upload Plugin.
3. Upload feline-coi-0.1.0.zip and activate Feline COI.
4. Create a WordPress page and insert a Shortcode block containing [feline_coi].
5. View the page while signed in, or use Tools > Feline COI.
6. Exclude the calculator page and /wp-json/feline-coi/v1/ endpoints from page/CDN caching. Private REST requests must reach WordPress.
7. Test with two separate accounts before offering it to users. It works with existing WordPress registration/login settings; it does not enable public signup.

Multisite: activate separately for each site. Network-wide activation and automatic table upgrades are not implemented in this first version.

== Entering a pedigree ==
Add the oldest known ancestors first. Then add their descendants, choosing the existing records for sire and dam. The same individual must always use the same record, even when appearing in several branches. Identical display names alone do not link records.

Registration numbers are optional but should be copied exactly. Nonempty registration numbers must be unique within an account. They are stored as identifiers and are not validated against a CFA database. Use the edit form to correct mistakes.

Click Apply to pedigree to update local working data; click Save pedigree to persist it. Calculations use saved records. Browser reloads discard changes that have not been saved. Export backup can preserve local changes before resolving a save conflict.

To remove a cat used as a parent, first edit its descendants to remove or replace that parent. Removing a cat does not delete its attached documents; those remain downloadable and can be deleted separately.

== CFA document reading ==
Upload a clear JPG/PNG photo or PDF, then use Download to inspect it. Automatic reading is optional and requires a site administrator to configure a separate OpenAI API key and a vision/PDF-capable Responses API model.

Add these constants to wp-config.php, before its final stop-editing line:

define('FCOI_OPENAI_API_KEY', 'YOUR_SERVER_SIDE_OPENAI_API_KEY');
define('FCOI_OPENAI_MODEL', 'YOUR_SUPPORTED_VISION_AND_PDF_MODEL_ID');

Use a model that supports image inputs, PDF file inputs, and strict JSON schema output in the Responses API. No model is chosen automatically. Keep the key out of browser code and never include it in backups shared with others. API usage requires available API access/credits; use your OpenAI project spending controls.

For this release, Read with OpenAI is shown only to administrators. Ordinary users can upload and download their documents but cannot incur document-reading API charges. Reading another user's uploaded document is not allowed, even to an administrator through the calculator interface. Reading is limited to one request per user per approximately two minutes, with an additional retry sometimes needed when an old lock expires.

Clicking Read with OpenAI prompts for confirmation before sending that document to OpenAI. It may contain owner names and addresses. The extraction asks for only the registered cat, its registration number and sex, its direct sire and dam and their registration numbers. Missing or uncertain values should remain blank. Review is essential: transcription can be wrong.

Apply reviewed fields updates the selected cat's local record and creates or links direct-parent records. Matching registration numbers identify an existing cat; matching a unique name requires explicit confirmation. A conflicting name/registration number requires manual resolution. Empty fields preserve existing values. Save pedigree commits the reviewed changes.

Automatic multigeneration pedigree-chart transcription is not implemented. A full pedigree PDF can be attached and consulted, but this reader extracts only the focal cat and direct parents. Enter and link grandparents and older ancestors manually in this version.

A CFA registration certificate is not a substitute for a multigeneration pedigree. A registration number does not by itself give the plugin access to CFA ancestry. The plugin does not verify document authenticity or ownership.

== External services and privacy ==
No external request is made for normal pedigree storage, COI calculation, backup operations or document upload/download.

Only when a configured administrator explicitly chooses Read with OpenAI is the selected document sent to https://api.openai.com/v1/responses. The request sets store=false, but this does not promise zero retention under the provider's data policies. Requests include the document, an extraction prompt and a field schema. The key is used only on the server. The result is shown in the browser and is not separately saved to a document-reading history.

OpenAI documentation: https://developers.openai.com/api/docs/guides/file-inputs
OpenAI data controls: https://developers.openai.com/api/docs/guides/your-data
OpenAI terms: https://openai.com/policies/services-agreement/
OpenAI privacy: https://openai.com/policies/privacy-policy/

The site owner controls database backups and administrator/database access. Private means isolated from other ordinary users through the plugin, not encrypted against the hosting provider or site administrator. Site backups should include both custom tables. Export backup in the calculator includes pedigree JSON only, not uploaded documents. Download documents individually, or use a full site/database backup. WordPress personal-data export includes document content as base64; WordPress erasure tools remove it.

Deactivation and plugin deletion preserve the custom database tables. Remove records through user data erasure before uninstalling if desired, or ask the site administrator to remove the tables after a verified backup. No uninstall-time data deletion is performed.

== Calculation method and limits ==
For kinship phi(a,b):
* Unknown parent contributes zero.
* phi(a,a) = (1 + phi(sire(a), dam(a))) / 2.
* For distinct cats, expand the later cat in ancestry order:
  phi(a,b) = (phi(sire(a),b) + phi(dam(a),b)) / 2.
* Individual F = phi(sire,dam).
* Proposed offspring F = phi(proposed sire, proposed dam).

Unknown founders are assumed unrelated and non-inbred. This is a pedigree estimate relative to that assumed base population. It is not a genomic COI, not a disease probability, and not a declaration that a mating is safe. Incomplete ancestry may understate COI.

Generation coverage counts pedigree slots, including repeated ancestors in multiple positions; it does not count unique individuals. All recorded ancestry contributes to the calculation even though only five coverage rows are displayed. The depth shown is the longest recorded ancestral path; it does not mean every branch is complete to that depth.

The engine is species-independent but the interface is designed for cats. It does not screen for PKD, PRA, blood type, structural health problems, or other breeding risks. Use genetic testing, family history and veterinary advice alongside pedigree information.

References:
https://labgenvet.ca/en/cat-genetics-4-0-evolution-breeds-breeding-strategies-and-inbreeding/
https://labgenvet.ca/en/cat-genetics-4-1-inbreeding-calculator-detailed-instructions-and-interpretation/
https://catalog.cfa.org/pedigrees.php

== Validation ==
See VALIDATION.md for the checks performed and remaining live-site checks.

== Changelog ==
= 0.1.0 =
Initial evaluation release.
