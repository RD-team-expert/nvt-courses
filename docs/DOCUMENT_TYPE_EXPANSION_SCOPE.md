# Scope: Training File Attachment for Video Content
**Client Request:** Admin can attach a training file (Word / Excel / PowerPoint) to a video. Users download it while watching to practise what the video teaches.  
**Date:** April 4, 2026 | **Updated:** April 6, 2026  
**Status:** Decisions Locked — Ready for Implementation (Workflow Revised)

---

## ⚠️ Workflow Change — Previous Approach Replaced

The original scope described a new `document` content type to replace PDF. **This has been replaced entirely.**

The new requirement is simpler: a training file is an **optional attachment on a video content item**. It is not a new content type — it is an extra file the admin uploads alongside a video so users can practice what the video teaches.

---

## Decisions Made (Final)

| # | Question | Decision |
|---|---|---|
| 1 | New content type or attachment? | **Attachment on video.** No new content type. `content_type` stays `['video', 'pdf']` unchanged. |
| 2 | What file types are allowed? | **Word (.docx, .doc), Excel (.xlsx, .xls), PowerPoint (.pptx, .ppt).** PDF not needed — already its own type. |
| 3 | How does the user interact with the file? | **Download only.** User clicks a download button on the video page. No in-browser rendering needed. |
| 4 | Does the user submit anything back? | **No.** One-way only — admin provides the file, user downloads it. |
| 5 | Does the file affect progress tracking? | **No.** Progress tracking stays on the video only. No changes to progress logic. |

---

## How the System Works Today (Current Flow — Unchanged)

### Admin Side
1. Admin opens **Create Course** → adds module → adds content item → selects `Content Type = Video`.
2. Admin links or uploads the video.
3. No training file concept exists today.

### User Side
1. User opens content via `ContentViewController@show`.
2. Frontend renders the video with `vue-pdf-embed` for PDF or video player for video.
3. Progress tracked as watch time / pages viewed.

---

## How the New Flow Will Work

### Admin Side (new)
1. Admin selects `Content Type = Video` as normal.
2. A new **optional** section appears: **"Training File (optional)"**.
3. Admin uploads a `.docx`, `.doc`, `.xlsx`, `.xls`, `.pptx`, or `.ppt` file.
4. Admin can also enter a display name for the file (e.g. "Exercise Sheet — Week 3").
5. On submit, the file is stored in `course-content/attachments/` and its path + extension + name are saved on the `module_contents` row.

### User Side (new)
1. User opens the video content page as normal — video plays as before.
2. If the video has an attachment, a **"Download Training File"** button appears below the video.
3. User clicks it → file downloads to their computer.
4. User pauses the video, works through the file, comes back.
5. **Progress tracking is unchanged** — only video watch time is tracked.

---

## What Needs to Change — Layer by Layer

### Layer 1 — Database
**One new migration file.**

| What | Change | Risk |
|---|---|---|
| `module_contents` table | Add `attachment_path VARCHAR(500) NULL` | Low — nullable column |
| `module_contents` table | Add `attachment_name VARCHAR(255) NULL` | Low — nullable column |
| `module_contents` table | Add `attachment_extension VARCHAR(10) NULL` | Low — nullable column |

Nothing else changes. No ENUM changes. No progress table changes.

---

### Layer 2 — Backend Models

| File | Change |
|---|---|
| `app/Models/ModuleContent.php` | Add `'attachment_path'`, `'attachment_name'`, `'attachment_extension'` to `$fillable`. Add `getHasAttachmentAttribute()` → `!is_null($this->attachment_path)`. |

No other model changes needed.

---

### Layer 3 — Backend Services

| File | Change |
|---|---|
| `app/Services/FileUploadService.php` | Add `uploadContentAttachment()` method. Accepts Word/Excel/PowerPoint MIME types. Saves to `course-content/attachments/`. Returns `path`, `original_name`, `extension`. |
| `app/Services/ContentView/ContentDataService.php` | In `prepareContentData()`, add three fields to the content array: `attachment_url` (generated from `attachment_path`), `attachment_name`, `attachment_extension`. All three are `null` when no attachment exists. |

**No changes** to `LearningSessionService`, `ContentProgressService`, or `LearningScoreCalculator` — progress tracking is not affected.

---

### Layer 4 — Backend Controllers

| File | Line(s) | Change |
|---|---|---|
| `app/Http/Controllers/Admin/CourseOnlineController.php` | Validation rules | Add optional rules: `'modules.*.content.*.attachment_file' => 'nullable\|file\|mimes:docx,doc,xlsx,xls,pptx,ppt\|max:20480'`, `'modules.*.content.*.attachment_name' => 'nullable\|string\|max:255'` |
| `app/Http/Controllers/Admin/CourseOnlineController.php` | Save block (~276) | After saving video content, check if `attachment_file` was uploaded → call `uploadContentAttachment()` → save `attachment_path`, `attachment_name`, `attachment_extension` on the content. |
| `app/Http/Controllers/Admin/ModuleContentController.php` | Validation | Add `'attachment_file' => 'nullable\|file\|mimes:docx,doc,xlsx,xls,pptx,ppt\|max:20480'`, `'attachment_name' => 'nullable\|string\|max:255'` |
| `app/Http/Controllers/Admin/ModuleContentController.php` | Store/update | Handle optional attachment upload using `uploadContentAttachment()`. |

**No changes** to `CourseProgressController`, `CourseModuleController`, or any user-side controllers.

---

### Layer 5 — Frontend (Admin)

| File | Change |
|---|---|
| `resources/js/pages/Admin/CourseOnline/Create.vue` | 1. Add optional "Training File" section inside the `v-if="content.content_type === 'video'"` block. 2. File input: `accept=".docx,.doc,.xlsx,.xls,.pptx,.ppt"`. 3. Text input for display name. 4. TypeScript interface: add `attachment_file: File \| null`, `attachment_name: string`. 5. Add `handleAttachmentUpload()` function. PDF and video sections completely untouched. |
| `resources/js/pages/Admin/CourseOnline/Edit.vue` | Same changes as `Create.vue`. Also show currently attached file name if one exists, with a "Remove" option. |

No changes to `CourseModule/Show.vue`.

---

### Layer 6 — Frontend (User / Viewer)

| File | Change |
|---|---|
| `resources/js/pages/User/ContentViewer/Show.vue` | Add a "Download Training File" button section. Shown only when `content.attachment_url` is not null. Clicking it triggers a direct file download via `window.open(content.attachment_url, '_blank')` or an `<a download>` tag. No libraries needed. All video/PDF viewer code untouched. |

**No new component needed.** The download button is a small addition to the existing viewer.

**No changes** to `resources/js/pages/User/CourseOnline/Show.vue`.

---

### Layer 7 — Tests & Factories

| File | Change |
|---|---|
| `database/factories/ModuleContentFactory.php` | Add `withAttachment()` factory state: sets `attachment_path`, `attachment_name`, `attachment_extension`. |
| All other test/factory files | **No changes needed.** |

---

## What Was REMOVED vs Original Scope

| Original Plan | New Plan | Reason |
|---|---|---|
| New `document` content type | ❌ Removed | Not needed — file is an attachment, not content |
| `content_type` ENUM change | ❌ Removed | Type stays as `['video', 'pdf']` |
| `docx-preview` npm package | ❌ Removed | No in-browser rendering needed |
| `SheetJS` npm package | ❌ Removed | No in-browser rendering needed |
| `DocumentViewer.vue` component | ❌ Removed | Download button replaces it |
| Progress tracking changes | ❌ Removed | Video progress unchanged |
| `LearningSessionService` changes | ❌ Removed | Not affected |
| `ContentProgressService` changes | ❌ Removed | Not affected |
| `LearningScoreCalculator` changes | ❌ Removed | Not affected |
| `CourseModule` model changes | ❌ Removed | No document count needed |

---

## Implementation Order (Step by Step)

```
Step 1 — Database migration
  └── Add attachment_path, attachment_name, attachment_extension to module_contents

Step 2 — FileUploadService
  └── Add uploadContentAttachment() method (Word/Excel/PowerPoint MIME types)

Step 3 — ModuleContent model
  └── Add attachment fields to $fillable
  └── Add getHasAttachmentAttribute()

Step 4 — ContentDataService
  └── Add attachment_url, attachment_name, attachment_extension to prepareContentData()

Step 5 — Admin controllers (CourseOnlineController + ModuleContentController)
  ├── Add attachment_file validation rules
  └── Handle attachment upload in store/update

Step 6 — Admin frontend (Create.vue + Edit.vue)
  ├── Add optional "Training File" upload section inside video content block
  └── Add handleAttachmentUpload() + TypeScript interface fields

Step 7 — User frontend (ContentViewer/Show.vue)
  └── Add "Download Training File" button (shown when attachment_url exists)

Step 8 — Factory + manual test
  ├── Add withAttachment() factory state
  └── Manual test: upload video + attach .xlsx → open as user → verify download button
```

---

## Estimated Effort (Revised — Much Simpler)

| Step | Area | Effort |
|---|---|---|
| 1 | Database migration | 30 min |
| 2 | `FileUploadService` | 1 hour |
| 3 | `ModuleContent` model | 30 min |
| 4 | `ContentDataService` | 30 min |
| 5 | Admin controllers | 2 hours |
| 6 | Admin frontend | 2–3 hours |
| 7 | User frontend | 1 hour |
| 8 | Factory + manual test | 30 min |
| | **Total** | **~8–9 hours** |

Estimated delivery: **1–2 working days.**

---

## Files Affected (Full List)

```
NEW:
  database/migrations/XXXX_add_attachment_fields_to_module_contents.php

CHANGED:
  app/
    Models/
      ModuleContent.php
    Services/
      FileUploadService.php
      ContentView/
        ContentDataService.php
    Http/Controllers/
      Admin/
        CourseOnlineController.php
        ModuleContentController.php

  database/
    factories/
      ModuleContentFactory.php

  resources/js/pages/
    Admin/
      CourseOnline/Create.vue
      CourseOnline/Edit.vue
    User/
      ContentViewer/Show.vue

UNTOUCHED (confirmed no changes needed):
  app/Models/CourseModule.php
  app/Models/UserContentProgress.php
  app/Services/ContentView/LearningSessionService.php
  app/Services/ContentView/ContentProgressService.php
  app/Services/ContentView/LearningScoreCalculator.php
  app/Http/Controllers/User/ContentViewController.php
  app/Http/Controllers/User/CourseProgressController.php
  app/Http/Controllers/Admin/CourseModuleController.php
  app/Http/Controllers/Api/ProgressController.php
  resources/js/pages/Admin/CourseModule/Show.vue
  resources/js/pages/User/CourseOnline/Show.vue
  All test files
```

---

## What Needs to Change — Layer by Layer

### Layer 1 — Database
**Create one new migration file.**

| What | Change | Risk |
|---|---|---|
| `module_contents.content_type` ENUM | Add `'document'` → `['video', 'pdf', 'document']` | Low — MySQL ALTER TABLE, no existing data changes |
| `module_contents` table | Add column `file_extension VARCHAR(10) NULL` | Low — new nullable column |
| `user_content_progress.content_type` ENUM | Add `'document'` → `['video', 'pdf', 'document']` | Low |

**Columns NOT renamed** (keep `google_drive_pdf_url`, `pdf_page_count`, `pdf_pages_viewed` — they keep working for `pdf` type records, just unused for `document` type).

---

### Layer 2 — Backend Models

| File | Lines | Change |
|---|---|---|
| `app/Models/ModuleContent.php` | ~74 | Add `scopeDocuments()` alongside existing `scopePdfs()`. Keep `scopePdfs()` untouched. |
| `app/Models/ModuleContent.php` | ~95–97 | Add `getIsDocumentAttribute()` → `content_type === 'document'`. Keep `getIsPdfAttribute()`. |
| `app/Models/ModuleContent.php` | ~25 | Add `'file_extension'` to `$fillable`. |
| `app/Models/CourseModule.php` | ~403 | Add `getDocumentCountAttribute()` → `where('content_type', 'document')->count()`. Keep `getPdfCountAttribute()`. |
| `app/Models/UserContentProgress.php` | ~86 | Keep `updatePdfProgress()` untouched. No equivalent needed — documents use time tracking only. |

---

### Layer 3 — Backend Services

| File | What Changes |
|---|---|
| `app/Services/FileUploadService.php` | Add `'document'` key to `$allowedMimeTypes` with Word + Excel MIME types: `application/vnd.openxmlformats-officedocument.wordprocessingml.document` (docx), `application/msword` (doc), `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet` (xlsx), `application/vnd.ms-excel` (xls). Add `uploadCourseDocument()` method (mirrors `uploadCoursePdf()`, saves to `course-content/documents/`, returns `file_extension`). Keep `uploadCoursePdf()` untouched. |
| `app/Services/ContentView/ContentDataService.php` | Add `prepareDocumentData()` method (mirrors `preparePdfData()`). Returns `file_url`, `file_extension`, `google_drive_url`. Keep `preparePdfData()` untouched. |
| `app/Services/ContentView/LearningSessionService.php` | ~473: `getExpectedDuration()` checks `content_type === 'pdf'`. Add an `elseif` for `'document'` that returns a default of `10` minutes (or admin-entered value — see note). |
| `app/Services/ContentView/ContentProgressService.php` | ~122: Add `elseif ($content->content_type === 'document')` block. For documents, `$finalPosition = 1` (completion is binary — either done or not). |
| `app/Services/LearningScoreCalculator.php` | ~218: Query filters `content_type = 'pdf'`. Change to `whereIn('content_type', ['pdf', 'document'])`. |

---

### Layer 4 — Backend Controllers

| File | Line(s) | Change |
|---|---|---|
| `app/Http/Controllers/Admin/CourseOnlineController.php` | ~154 | `'content_type' => 'required|in:video,pdf'` → `'required|in:video,pdf,document'` |
| `app/Http/Controllers/Admin/CourseOnlineController.php` | ~188 | PDF validation block (`if content_type === 'pdf'`) — add `elseif` for `'document'` (no page count required, only file or Drive URL required). |
| `app/Http/Controllers/Admin/CourseOnlineController.php` | ~276 | PDF upload/save block — add `elseif ($contentData['content_type'] === 'document')` that calls `uploadCourseDocument()` and saves `file_extension`. |
| `app/Http/Controllers/Admin/ModuleContentController.php` | 106 | `'required|in:video,pdf'` → `'required|in:video,pdf,document'` |
| `app/Http/Controllers/Admin/ModuleContentController.php` | 116 | `'mimes:pdf'` → `'mimes:pdf,docx,doc,xlsx,xls'` and rename field to `doc_file` or generalise. |
| `app/Http/Controllers/User/CourseProgressController.php` | ~510, ~595, ~691 | All `content_type === 'pdf'` checks — add `|| content_type === 'document'` OR handle them with `in_array($content->content_type, ['pdf', 'document'])`. |
| `app/Http/Controllers/Admin/CourseModuleController.php` | ~38, ~139 | Add `'document_count' => $module->document_count` alongside existing `pdf_count`. |

---

### Layer 5 — Frontend (Admin)

| File | Change |
|---|---|
| `resources/js/pages/Admin/CourseOnline/Create.vue` | 1. Add `<option value="document">Document (Word / Excel)</option>` to content type dropdown. 2. Add new `v-if="content.content_type === 'document'"` section: file input with `accept=".docx,.doc,.xlsx,.xls"`, no page count field. 3. TypeScript interface: add `doc_file: File \| null`, `doc_name: string`. 4. Add `handleDocUpload()` function (mirrors `handlePdfUpload`). 5. Update `validatePdfContent()` — add skip for `document` type (no page count needed). Existing PDF form section untouched. |
| `resources/js/pages/Admin/CourseOnline/Edit.vue` | Same changes as `Create.vue`. |
| `resources/js/pages/Admin/CourseModule/Show.vue` | Add document count stat display. Keep existing PDF count. |

---

### Layer 6 — Frontend (User / Viewer)

**`resources/js/pages/User/ContentViewer/Show.vue`** — this is the biggest change.

#### What stays the same
- All existing PDF rendering (`vue-pdf-embed`, `pdfSource`, `onPdfLoaded`, `isPdfLoaded`, `pdfZoom`, etc.) — completely untouched.
- Session management (`startSession`, `heartbeat`, `endSession`) — reused as-is for documents.

#### What gets added

**Install two new npm packages:**
```
npm install docx-preview
npm install xlsx
```

**New state variables:**
```
const docContainer = ref<HTMLDivElement | null>(null)   // Word render target
const excelHtml = ref('')                               // Excel HTML output
const isDocLoaded = ref(false)
const isDocError = ref(false)
```

**New computed — detect file type from extension:**
```
const isWordFile = computed(() => ['docx','doc'].includes(props.pdf?.file_extension))
const isExcelFile = computed(() => ['xlsx','xls'].includes(props.pdf?.file_extension))
```

**New function — load document:**
```
async function loadDocument(fileUrl) {
    const res = await fetch(fileUrl)
    const buffer = await res.arrayBuffer()
    if (isWordFile.value) {
        await renderAsync(buffer, docContainer.value)   // docx-preview
    } else if (isExcelFile.value) {
        const wb = XLSX.read(buffer, { type: 'array' })
        const sheet = wb.Sheets[wb.SheetNames[0]]
        excelHtml.value = XLSX.utils.sheet_to_html(sheet)  // SheetJS
    }
    isDocLoaded.value = true
    startSession()   // start timer — reuse existing function
}
```

**New template section (alongside existing PDF viewer):**
```vue
<!-- Word viewer -->
<div v-else-if="content.content_type === 'document' && isWordFile"
     ref="docContainer" class="doc-viewer" />

<!-- Excel viewer -->
<div v-else-if="content.content_type === 'document' && isExcelFile"
     v-html="excelHtml" class="sheet-viewer" />

<!-- Mark as Complete button (documents only) -->
<button v-if="content.content_type === 'document' && isDocLoaded && !isCompleted"
        @click="markDocumentComplete">
    Mark as Complete
</button>
```

**`markDocumentComplete` function:**
Calls the existing `endSession()` with `completionPercentage = 100` and then calls the existing progress update endpoint. No new API endpoint needed.

| File | Also needs | Change |
|---|---|---|
| `resources/js/pages/User/CourseOnline/Show.vue` | TypeScript type | `content_type: 'video' \| 'pdf' \| 'document'` |

---

### Layer 7 — Tests & Factories

| File | Change |
|---|---|
| `database/factories/ModuleContentFactory.php` | Add `document()` factory state with `content_type: 'document'`, `file_path`, `file_extension: 'docx'`. |
| `database/factories/UserContentProgressFactory.php` | Add `'document'` to `content_type` faker options. Add `forDocument()` state. |
| `tests/Feature/N1QueryFixTest.php` | Uses `content_type = 'pdf'` — still valid, no change needed. |
| `tests/Unit/Services/ContentView/VideoStreamingServiceTest.php` | No change needed. |

---

## Implementation Order (Step by Step)

Do these in order. Finish and test each step before moving to the next.

```
Step 1 — Database migration
  ├── Add 'document' to content_type ENUM (module_contents + user_content_progress)
  └── Add file_extension column to module_contents

Step 2 — FileUploadService
  ├── Add Word/Excel MIME types to $allowedMimeTypes
  └── Add uploadCourseDocument() method

Step 3 — Admin controllers + validation
  ├── CourseOnlineController: accept 'document' type, handle upload
  └── ModuleContentController: accept new MIME types

Step 4 — ContentDataService
  └── Add prepareDocumentData() returning file_url + file_extension

Step 5 — Models
  ├── ModuleContent: add scopeDocuments(), getIsDocumentAttribute(), file_extension fillable
  └── CourseModule: add getDocumentCountAttribute()

Step 6 — Progress + Session services
  ├── LearningSessionService: add 'document' to getExpectedDuration()
  ├── ContentProgressService: add 'document' completion logic
  └── LearningScoreCalculator: include 'document' in time queries

Step 7 — Admin frontend (Create.vue + Edit.vue)
  ├── Add 'Document' option to content type dropdown
  ├── Add document upload form section (no page count)
  └── Add handleDocUpload() + validation skip for document type

Step 8 — User frontend (ContentViewer/Show.vue + new DocumentViewer component)
  ├── npm install docx-preview xlsx
  ├── Create new component: resources/js/components/DocumentViewer.vue
  │     ├── Accepts props: fileUrl, fileExtension
  │     ├── Handles Word rendering (docx-preview)
  │     ├── Handles Excel rendering (SheetJS)
  │     ├── Shows loading / error states
  │     └── Emits: @loaded, @error
  ├── In ContentViewer/Show.vue: import and use <DocumentViewer> component
  └── Add "Mark as Complete" button + markDocumentComplete() function

Step 9 — Tests + factories
  ├── Add document factory states
  └── Manual test: upload docx → view as user → mark complete
```

---

## Estimated Effort (Updated)

| Step | Area | Effort |
|---|---|---|
| 1 | Database migration | 1–2 hours |
| 2 | `FileUploadService` | 1–2 hours |
| 3 | Admin controllers | 2–3 hours |
| 4 | `ContentDataService` | 1 hour |
| 5 | Models | 1–2 hours |
| 6 | Progress + session services | 2–3 hours |
| 7 | Admin frontend | 3–4 hours |
| 8 | User frontend (viewer) | 4–6 hours |
| 9 | Tests + factories | 1–2 hours |
| | **Total** | **~16–25 hours** |

Estimated delivery: **3–4 working days.**

---

## Files Affected (Full List)

```
NEW:
  database/migrations/XXXX_add_document_type_to_content_tables.php

CHANGED:
  app/
    Models/
      ModuleContent.php
      CourseModule.php
    Services/
      FileUploadService.php
      LearningScoreCalculator.php
      ContentView/
        ContentDataService.php
        ContentProgressService.php
        LearningSessionService.php
    Http/Controllers/
      Admin/
        CourseOnlineController.php
        ModuleContentController.php
        CourseModuleController.php
      User/
        CourseProgressController.php

  database/
    factories/
      ModuleContentFactory.php
      UserContentProgressFactory.php

  resources/js/pages/
    Admin/
      CourseOnline/Create.vue
      CourseOnline/Edit.vue
      CourseModule/Show.vue
    User/
      ContentViewer/Show.vue
      CourseOnline/Show.vue

UNTOUCHED (confirmed no changes needed):
  app/Models/UserContentProgress.php         (updatePdfProgress stays as-is)
  app/Http/Controllers/User/ContentViewController.php
  app/Http/Controllers/Api/ProgressController.php
  tests/Feature/N1QueryFixTest.php
  tests/Unit/Services/ContentView/VideoStreamingServiceTest.php
```
