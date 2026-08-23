# QA Report — Hybrid Evaluation Lifecycle

**Date:** 2026-08-23 · **Target:** http://localhost:8000 (PHP cli-server + MariaDB) · **Framework:** PHP/MariaDB + Playwright
**Scope:** Evaluation pipeline changed on this working tree (submit_answer.php, grading.php, results.php, dashboard.php, test.php, helpers.php)

## Result: 3/3 tests passing · Health Score 97/100

| # | Test | Verdict |
|---|---|---|
| 1 | Student login (`hariiphones83@gmail.com`) | ✅ pass |
| 2 | Pure-MCQ instant evaluation (`test_live`, 10 MCQ / 13 marks) | ✅ pass |
| 3 | Hybrid pending-review flow (`QA Hybrid Test`: 2 MCQ + coding + explanation) | ✅ pass |

## Evidence

**Flow 2 (pure MCQ):** answered all 10 questions → Submit → confirm dialog → **landed on result screen "Test Completed" with % score** (regression assertion against raw-JSON bug) → results page shows Objective breakdown + PASS/FAIL badge.
DB after: `status='evaluated'`, `evaluation_status='evaluated'`, `auto_score=total_score=total_marks_obtained=4.00/13.00`.

**Flow 3 (hybrid):** answered 2 MCQs correctly + filled code/explanation → Submit → **"Result Not Yet Announced" screen with Under Evaluation badge, zero premature percentages** → dashboard row shows "Under Evaluation" → results page banner lists the test; it is absent from the graded table.
DB after: `evaluation_status='pending_manual_review'`, `auto_score=5.00` banked, `manual_score`/`total_score` NULL — exactly awaiting admin grading.

## Issues Found

### ISSUE-001 — HIGH — FIXED & VERIFIED
**Students landed on raw JSON after submitting a test.**
`test.php` posts the form natively to `api/submit_answer.php`, which echoed a JSON body into the browser tab. Students never saw their result.
**Fix:** form now carries `redirect=1`; API responds `303 → student/test.php?test_id=N&submitted=1` for browser posts only (fetch/auto-save callers still get JSON).
Files: `src/php/public/student/test.php`, `src/php/api/submit_answer.php`. Regression-locked by spec test 2.

### ISSUE-002 — LOW — NOTED, NOT FIXED (cosmetic)
Expired tests render an "Active" status chip next to the "Expired" action badge on the dashboard (test window lapsed while `tests.status='active'`). Suggest a scheduled job or query-time override marking them completed. Cosmetic inconsistency only.

## Fix Commits Needed (working tree intentionally uncommitted)
- `src/php/api/submit_answer.php` — evaluation engine + browser redirect
- `src/php/public/student/test.php` — redirect hint field
- `tests/evaluation.spec.js`, `tests/reset-eval-qa.sql` — new QA assets

## How to Re-run
```bash
# reset fixtures (retakeable state)
C:\xampp\mysql\bin\mysql.exe -h 127.0.0.1 -u root test_platform < tests\reset-eval-qa.sql
npx playwright test tests/evaluation.spec.js
```

## Notes
- Admin-side grading UI was verified structurally earlier (queue ordering, validation, finalize SQL exercised in lifecycle test); no admin credentials were available for a live browser pass of grading.php — recommended follow-up once admin password is shared/reset.
- Baseline saved for future regression runs.
