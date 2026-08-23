# Graph Report - TEST  (2026-08-21)

## Corpus Check
- 113 files · ~240,446 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 679 nodes · 679 edges · 105 communities (95 shown, 10 thin omitted)
- Extraction: 97% EXTRACTED · 3% INFERRED · 0% AMBIGUOUS · INFERRED: 21 edges (avg confidence: 0.85)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `fda0826d`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- Detailed Test Report — Student Test & Analysis Platform
- app.py
- Non-tech-manual.md
- 📂 LEFT SIDEBAR — Complete Guide to Every Menu Item
- Comprehensive Path Bug Report
- 🐛 Bug Tracker — Student Test Platform
- What You Must Do When Invoked
- 🎯 Adding Questions One by One (Manual)
- helpers.php
- Analytics Card Redesign — Enterprise SaaS
- auth.php
- Test Coverage
- package.json
- UI_SUGGESTIONS.md
- 4. Database Schema
- 🖥️ ADMIN DASHBOARD — What You See First
- 19.1 Admin Flow
- Student Test & Analysis Platform — Logic Document
- 2. Line Chart — Premium Progressive Line
- analytics-grid.spec.js
- graphify reference: extra exports and benchmark
- mailer.php
- 16.1 Fluent 2 — Design Tokens
- Exam Portal — Enterprise Chart & Analytics Redesign Manual
- 18. Setup Guide
- graphify reference: query, path, explain
- 10. Taking a Test (Student View)
- 15. Python Analysis API
- 6. Authentication & User Roles
- 3. Segmented Progress Ring
- 4. Mobile Responsive Layout
- 9. Troubleshooting
- session.php
- college_create.php
- 12. PCI Scoring System
- 13. Tab Switch Detection & Timer
- 17. Email / OTP System
- 6. Animation System
- 7. Accessibility
- graphify reference: add a URL and watch a folder
- graphify reference: commit hook and native CLAUDE.md integration
- graphify reference: incremental update and cluster-only
- 14. CSV Question Import
- 20. Configuration Reference
- 5. Router & URL Structure
- 8. Guest Access & QR Code Flow
- 9. Test Lifecycle
- 5. CSS Architecture & Tokens
- graphify reference: GitHub clone and cross-repo merge
- graphify reference: transcribe video and audio
- graphify.js
- AGENTS.md
- CLAUDE.md
- .claude/CLAUDE.md
- extraction-spec.md
- playwright.config.js
- saveTestSections
- PDO

## God Nodes (most connected - your core abstractions)
1. `Student Test & Analysis Platform — Logic Document` - 23 edges
2. `Detailed Test Report — Student Test & Analysis Platform` - 15 edges
3. `4. Database Schema` - 15 edges
4. `getDB()` - 14 edges
5. `Exam Portal — Enterprise Chart & Analytics Redesign Manual` - 13 edges
6. `What You Must Do When Invoked` - 12 edges
7. `Test Coverage` - 11 edges
8. `/graphify` - 11 edges
9. `Analytics Card Redesign — Enterprise SaaS` - 11 edges
10. `2. Line Chart — Premium Progressive Line` - 10 edges

## Surprising Connections (you probably didn't know these)
- `getStudentTests()` --calls--> `getDB()`  [INFERRED]
  src/php/includes/helpers.php → src/php/config/db.php
- `generateStudentOtp()` --calls--> `sendOtpEmail()`  [INFERRED]
  src/php/includes/auth.php → src/php/includes/mailer.php
- `processGuestToken()` --calls--> `guestLogin()`  [INFERRED]
  src/php/public/guest.php → src/php/includes/auth.php
- `recalculatePciForSubmission()` --calls--> `getDB()`  [INFERRED]
  src/php/includes/helpers.php → src/php/config/db.php
- `logFailedLogin()` --calls--> `getDB()`  [INFERRED]
  src/php/includes/auth.php → src/php/config/db.php

## Import Cycles
- None detected.

## Communities (105 total, 10 thin omitted)

### Community 0 - "Detailed Test Report — Student Test & Analysis Platform"
Cohesion: 0.05
Nodes (43): 10.1 Unfixed (Feature Scope), 10.2 Non-Issues (Verified Working), 10. Known Issues, 11.1 Quality Gates, 11.2 Coverage Matrix, 11.3 Test Credentials, 11. Coverage Summary, 1. Executive Summary (+35 more)

### Community 1 - "app.py"
Cohesion: 0.09
Nodes (36): route, build_bar_chart_data(), build_doughnut_data(), build_histogram_data(), build_radar_data(), build_stacked_bar_data(), Chart data generation utilities for PCI reports. Returns data structures…, Build a Chart.js radar chart for a single student. (+28 more)

### Community 2 - "Non-tech-manual.md"
Cohesion: 0.05
Nodes (36): Adding a Single Student, Bulk Import Students from CSV, 🛠️ COMMON TASKS — Step by Step, Creating a New Test, 📘 Exam Portal — Simple User Guide, ❓ FREQUENTLY ASKED QUESTIONS, Generating a Guest Link or QR Code, 🔗 GUEST LINK & QR CODE — Giving Test Access Without Login (+28 more)

### Community 3 - "📂 LEFT SIDEBAR — Complete Guide to Every Menu Item"
Cohesion: 0.07
Nodes (29): Activity Logs, All Assessments, 📋 Assessment Management, 🎨 Assessment Studio, Batches, Bottom of Sidebar, Colleges, Courses (+21 more)

### Community 4 - "Comprehensive Path Bug Report"
Cohesion: 0.07
Nodes (28): 1. Executive Summary, 2.1 The Duality Problem, 2.2 Why Type C Failed, 2.3 Why `redirect()` Calls Work, 2. Problem Analysis, 3.1 Confirmed 404 Bugs (3) — FIXED, 3.2 Router Safety Net (4) — ADDED, 3.3 Previously Fixed URL Bugs (5) — Already Fixed (+20 more)

### Community 5 - "🐛 Bug Tracker — Student Test Platform"
Cohesion: 0.07
Nodes (26): 🐛 Bug Tracker — Student Test Platform, CRIT-001: SVG iconSprite() dumped inside `<style>` tag, CRIT-002: Assets path resolution broken — CSS doesn't load, 🔴 Critical Bugs, HIGH-001: Hardcoded `/test-platform` asset paths in test.php, HIGH-002: Hardcoded asset path in verify-otp.php, HIGH-003: "My Tests" sidebar link is a dead placeholder, HIGH-004: Massive sidebar HTML duplication across 5 student pages (+18 more)

### Community 6 - "What You Must Do When Invoked"
Cohesion: 0.07
Nodes (26): For /graphify add and --watch, For /graphify query, For the commit hook and native CLAUDE.md integration, For --update and --cluster-only, /graphify, Honesty Rules, Interpreter guard for subcommands, Part A - Structural extraction for code files (+18 more)

### Community 7 - "🎯 Adding Questions One by One (Manual)"
Cohesion: 0.08
Nodes (25): 🎯 Adding Questions One by One (Manual), 📂 Bulk Upload via CSV File (for MCQ Questions Only), Coding Grading (Manual), Coding Question Tips, Explanation Grading (Manual), Explanation Question Tips, For Coding Questions:, For Explanation Questions: (+17 more)

### Community 8 - "helpers.php"
Cohesion: 0.12
Nodes (15): flash(), flashMessage(), getStudentTests(), h(), pythonApiRequest(), recalculatePciForSubmission(), redirect(), renderNotificationItems() (+7 more)

### Community 9 - "Analytics Card Redesign — Enterprise SaaS"
Cohesion: 0.09
Nodes (21): Added, Analytics Card Redesign — Enterprise SaaS, Animation & Micro-interactions, Card Hover, Card Specification, Center Content, Changes Summary, Dark Mode (+13 more)

### Community 10 - "auth.php"
Cohesion: 0.20
Nodes (17): getDB(), PDO, adminLogin(), generateStudentOtp(), getClientIp(), guestLogin(), isAdmin(), isBruteForceLocked() (+9 more)

### Community 11 - "Test Coverage"
Cohesion: 0.11
Nodes (18): 10. Fluent 2 Design System  (8 tests), 1. Database Connectivity & Schema  (16 tests), 2. Column Validation  (17 tests), 3. Admin Dashboard Queries  (6 tests), 4. Admin Management Pages  (10 tests), 5. Reports & Tab Switcher  (2 tests), 6. Student Pages  (4 tests), 7. Authentication  (3 tests) (+10 more)

### Community 12 - "package.json"
Cohesion: 0.11
Nodes (17): author, description, devDependencies, @playwright/test, directories, doc, keywords, license (+9 more)

### Community 13 - "UI_SUGGESTIONS.md"
Cohesion: 0.11
Nodes (16): 10. RECOMMENDED NEXT STEPS, 1. CURRENT LIMITATION: ADMIN_DASHBOARD.md is a Starter Document, 2. CURRENT LIMITATION: admin.png Cannot Be Rendered, 3. ISSUE: Admin Profile onClick Navigates to Logout, 5. ISSUE: stat-card-menu Buttons Have No Dropdown Behavior, 6. ISSUE: Add Student URL Has No Action Parameter, 7. ISSUE: Edit Buttons in Students Table Use Invalid Anchor Links, 8.1 Keyboard Navigation (+8 more)

### Community 14 - "4. Database Schema"
Cohesion: 0.13
Nodes (15): 4.10 `questions` — Test questions, 4.11 `submissions` — Student test attempts, 4.12 `student_answers` — Individual answer records, 4.13 `tab_switch_logs` — Tab visibility change log, 4.14 `pci_records` — Performance Competency Index scores, 4.1 `admins` — Platform administrators, 4.2 `colleges` — Educational institutions, 4.3 `courses` — Programs offered (unique per college) (+7 more)

### Community 15 - "🖥️ ADMIN DASHBOARD — What You See First"
Cohesion: 0.15
Nodes (13): 🖥️ ADMIN DASHBOARD — What You See First, 🎯 Assessment Distribution (The Coloured Ring), 📈 Average Performance (The Line Graph), Charts Section, 🔴 Live Activity Feed, Quick Actions (The 6 Shortcut Cards), Recent Activity, 📋 Recent Assessments (+5 more)

### Community 16 - "19.1 Admin Flow"
Cohesion: 0.17
Nodes (12): 19.1 Admin Flow, 19.2 Student Flow, 19.3 Guest Flow, 19. Usage Manual, Creating & Managing a Test, First Time Setup, Grading, Managing Students (+4 more)

### Community 17 - "Student Test & Analysis Platform — Logic Document"
Cohesion: 0.18
Nodes (10): 11.1 Grading Interface (`admin/grading.php`), 11.2 Save Grades, 11. Grading & Evaluation, 1. Project Overview, 2. System Architecture, 3. Folder Structure, 7. Student Registration & OTP Verification Flow, Appendix: Key Design Decisions (+2 more)

### Community 18 - "2. Line Chart — Premium Progressive Line"
Cohesion: 0.20
Nodes (10): 2.1 Data Generation, 2.2 SVG Architecture, 2.3 Smooth Path Generation, 2.4 Visual Specifications, 2.5 Y-Axis Labels, 2.6 X-Axis Labels, 2.7 Grid Lines, 2.8 Tooltip (JavaScript) (+2 more)

### Community 19 - "analytics-grid.spec.js"
Cohesion: 0.20
Nodes (5): CSS_PATH, FIXTURE_PATH, fs, path, { test, expect }

### Community 20 - "graphify reference: extra exports and benchmark"
Cohesion: 0.22
Nodes (8): graphify reference: extra exports and benchmark, Step 6b - Wiki (only if --wiki flag), Step 7 - Neo4j export (only if --neo4j or --neo4j-push flag), Step 7a - FalkorDB export (only if --falkordb or --falkordb-push flag), Step 7b - SVG export (only if --svg flag), Step 7c - GraphML export (only if --graphml flag), Step 7d - MCP server (only if --mcp flag), Step 8 - Token reduction benchmark (only if total_words > 5000)

### Community 21 - "mailer.php"
Cohesion: 0.44
Nodes (8): sendMail(), sendNativeMail(), sendOtpEmail(), sendSmtpMail(), smtpFirstLine(), smtpIsSuccess(), smtpReadResponse(), smtpSendAndRead()

### Community 22 - "16.1 Fluent 2 — Design Tokens"
Cohesion: 0.25
Nodes (8): 16.1 Fluent 2 — Design Tokens, 16.2 Student Theme (`student.css`), 16.3 Admin Theme (`admin.css`), 16. UI Design System, Color Palette, Radius, Spacing, Typography

### Community 23 - "Exam Portal — Enterprise Chart & Analytics Redesign Manual"
Cohesion: 0.25
Nodes (7): 1. Overview, 8. Design Inspiration, Appendix A: File Reference, Appendix B: Design Tokens Reference, Design Principles, Exam Portal — Enterprise Chart & Analytics Redesign Manual, Table of Contents

### Community 24 - "18. Setup Guide"
Cohesion: 0.29
Nodes (7): 18.1 Prerequisites, 18.2 Database Setup, 18.3 Configuration, 18.4 Running the Server, 18.5 Seed Data, 18.6 Python API (Optional), 18. Setup Guide

### Community 25 - "graphify reference: query, path, explain"
Cohesion: 0.33
Nodes (5): For /graphify explain, For /graphify path, graphify reference: query, path, explain, Step 0 — Constrained query expansion (REQUIRED before traversal), Step 1 — Traversal

### Community 26 - "10. Taking a Test (Student View)"
Cohesion: 0.33
Nodes (6): 10.1 Entry Points, 10.2 Test Interface (`student/test.php`), 10.3 Auto-Save, 10.4 Submit, 10.5 Result Display, 10. Taking a Test (Student View)

### Community 27 - "15. Python Analysis API"
Cohesion: 0.33
Nodes (6): 15.1 Overview, 15.2 Endpoints, 15.3 Running, 15.4 PHP Integration, 15.5 Configuration, 15. Python Analysis API

### Community 28 - "6. Authentication & User Roles"
Cohesion: 0.33
Nodes (6): 6.1 Roles, 6.2 Admin Login, 6.3 Student Login, 6.4 Guest Token Login, 6.5 Session & CSRF, 6. Authentication & User Roles

### Community 29 - "3. Segmented Progress Ring"
Cohesion: 0.33
Nodes (6): 3.1 Architecture, 3.2 Segment Rendering, 3.3 Gradient Colour System, 3.4 Center Content, 3.5 Legend Layout, 3. Segmented Progress Ring

### Community 30 - "4. Mobile Responsive Layout"
Cohesion: 0.33
Nodes (6): 4.1 Breakpoint Strategy, 4.2 Ring Alignment, 4.3 Card Padding, 4.4 Ring Sizing, 4.5 Mobile Layout (Vertical Stack), 4. Mobile Responsive Layout

### Community 31 - "9. Troubleshooting"
Cohesion: 0.33
Nodes (6): 9.1 Chart Not Rendering, 9.2 Line Chart Not Animating, 9.3 Responsive Breakpoint Issues, 9.4 Ring Alignment on Mobile, 9.5 Test Failures, 9. Troubleshooting

### Community 32 - "session.php"
Cohesion: 0.47
Nodes (4): csrfField(), getCsrfToken(), requireCsrf(), validateCsrfToken()

### Community 33 - "college_create.php"
Cohesion: 0.40
Nodes (3): PDO, generateBatchNickName(), generateCollegeCode()

### Community 34 - "12. PCI Scoring System"
Cohesion: 0.40
Nodes (5): 12.1 Formula, 12.2 Generation, 12.3 Performance Bands (Python API), 12.4 Reports (`admin/reports.php`), 12. PCI Scoring System

### Community 35 - "13. Tab Switch Detection & Timer"
Cohesion: 0.40
Nodes (5): 13.1 Detection, 13.2 Logging, 13.3 Admin View, 13.4 Timer, 13. Tab Switch Detection & Timer

### Community 36 - "17. Email / OTP System"
Cohesion: 0.40
Nodes (5): 17.1 Configuration (`config/mail.php`), 17.2 SMTP Sender (`includes/mailer.php`), 17.3 Dev Mode, 17.4 Gmail App Password, 17. Email / OTP System

### Community 37 - "6. Animation System"
Cohesion: 0.40
Nodes (5): 6.1 Line Chart Draw Animation, 6.2 Final Dot Animation, 6.3 Ring Segment Stagger, 6.4 Hover Interactions, 6. Animation System

### Community 38 - "7. Accessibility"
Cohesion: 0.40
Nodes (5): 7.1 Colour Contrast, 7.2 Focus Indicators, 7.3 Animation Respect, 7.4 ARIA Attributes, 7. Accessibility

### Community 39 - "graphify reference: add a URL and watch a folder"
Cohesion: 0.50
Nodes (3): For /graphify add, For --watch, graphify reference: add a URL and watch a folder

### Community 40 - "graphify reference: commit hook and native CLAUDE.md integration"
Cohesion: 0.50
Nodes (3): For git commit hook, For native CLAUDE.md integration, graphify reference: commit hook and native CLAUDE.md integration

### Community 41 - "graphify reference: incremental update and cluster-only"
Cohesion: 0.50
Nodes (3): For --cluster-only, For --update (incremental re-extraction), graphify reference: incremental update and cluster-only

### Community 42 - "14. CSV Question Import"
Cohesion: 0.50
Nodes (4): 14.1 Format, 14.2 Import Logic (in `test_builder.php`), 14.3 Sample File, 14. CSV Question Import

### Community 43 - "20. Configuration Reference"
Cohesion: 0.50
Nodes (4): 20.1 `config/db.php`, 20.2 `config/mail.php`, 20.3 Environment Variables (for `DB_ENV = 'production'`), 20. Configuration Reference

### Community 44 - "5. Router & URL Structure"
Cohesion: 0.50
Nodes (4): 5.1 Dev Server (`php -S localhost:8000 router.php`), 5.2 BASE_URL Detection, 5.3 Asset URLs, 5. Router & URL Structure

### Community 45 - "8. Guest Access & QR Code Flow"
Cohesion: 0.50
Nodes (4): 8.1 Generation, 8.2 QR Code Display, 8.3 Access Flow, 8. Guest Access & QR Code Flow

### Community 46 - "9. Test Lifecycle"
Cohesion: 0.50
Nodes (4): 9.1 Status Transitions, 9.2 Access Control Logic (in `student/test.php`), 9.3 Timer Extension, 9. Test Lifecycle

### Community 47 - "5. CSS Architecture & Tokens"
Cohesion: 0.50
Nodes (4): 5.1 Design Tokens, 5.2 Key Selectors, 5.3 Responsive Overrides, 5. CSS Architecture & Tokens

## Knowledge Gaps
- **386 isolated node(s):** `10.1 Unfixed (Feature Scope)`, `10.2 Non-Issues (Verified Working)`, `11.1 Quality Gates`, `11.2 Coverage Matrix`, `11.3 Test Credentials` (+381 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **10 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `Student Test & Analysis Platform — Logic Document` connect `Student Test & Analysis Platform — Logic Document` to `12. PCI Scoring System`, `13. Tab Switch Detection & Timer`, `17. Email / OTP System`, `14. CSV Question Import`, `20. Configuration Reference`, `5. Router & URL Structure`, `8. Guest Access & QR Code Flow`, `4. Database Schema`, `9. Test Lifecycle`, `19.1 Admin Flow`, `16.1 Fluent 2 — Design Tokens`, `18. Setup Guide`, `10. Taking a Test (Student View)`, `15. Python Analysis API`, `6. Authentication & User Roles`?**
  _High betweenness centrality (0.022) - this node is a cross-community bridge._
- **Why does `📂 LEFT SIDEBAR — Complete Guide to Every Menu Item` connect `📂 LEFT SIDEBAR — Complete Guide to Every Menu Item` to `Non-tech-manual.md`?**
  _High betweenness centrality (0.011) - this node is a cross-community bridge._
- **Why does `📝 WORKING WITH QUESTIONS — In Detail` connect `🎯 Adding Questions One by One (Manual)` to `Non-tech-manual.md`?**
  _High betweenness centrality (0.009) - this node is a cross-community bridge._
- **Are the 12 inferred relationships involving `getDB()` (e.g. with `adminLogin()` and `generateStudentOtp()`) actually correct?**
  _`getDB()` has 12 INFERRED edges - model-reasoned connections that need verification._
- **What connects `10.1 Unfixed (Feature Scope)`, `10.2 Non-Issues (Verified Working)`, `11.1 Quality Gates` to the rest of the system?**
  _386 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Detailed Test Report — Student Test & Analysis Platform` be split into smaller, more focused modules?**
  _Cohesion score 0.045454545454545456 - nodes in this community are weakly interconnected._
- **Should `app.py` be split into smaller, more focused modules?**
  _Cohesion score 0.09446693657219973 - nodes in this community are weakly interconnected._