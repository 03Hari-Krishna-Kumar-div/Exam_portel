# 📘 Exam Portal — Simple User Guide

> **For:** Teachers, Administrators, and Staff  
> **Purpose:** Explains every button, screen, and feature in plain English  
> **Last updated:** July 2026

---

## 🎯 What Is This?

This is an online exam platform. Think of it as a digital classroom where:

- **Admins** (you) create tests, manage students, and view results
- **Students** log in, take tests, and see their scores

Everything lives inside your web browser — no software to install.

---

## 🔑 Logging In

**URL:** Ask your technical team for the link.

You'll see a login screen with two fields:

| Field | What to enter |
|-------|---------------|
| **Email** | Your work email address |
| **Password** | The password given to you |

There's also a **dropdown** to choose your role:
- **Admin** → if you're a teacher or staff member
- **Student** → if you're taking tests

Click **"Sign In"** and you're in.

> 💡 **Forgot your password?** Ask your system administrator to reset it.

---

# 🖥️ ADMIN DASHBOARD — What You See First

After logging in as an admin, this is your home screen. It gives you a birds-eye view of everything happening.

## Top Bar (Header)

```
[☰]  [Search... Ctrl+K]     [Create Assessment]  [🌙]  [🔔]  [👤 Admin Name]
```

| Button | What it does |
|--------|-------------|
| **☰ (hamburger menu)** | Collapses or expands the left sidebar to give you more screen space |
| **Search bar** | Type anything — student names, test titles, courses — and it quickly finds results. Press `Ctrl+K` on your keyboard to jump to it anytime |
| **Create Assessment** | Takes you directly to the test creation page |
| **🌙/☀️ (moon/sun)** | Switches between **Dark Mode** (easier on the eyes at night) and **Light Mode** (bright daytime view) |
| **🔔 (bell)** | Shows your notifications |
| **👤 Your name** | Your profile menu — change settings or sign out |

## Statistics Cards (The 4 Big Numbers)

These 4 boxes at the top show your system at a glance:

| Card | What it tells you |
|------|-------------------|
| **👥 Total Students** | How many students are registered in the system |
| **📝 Total Assessments** | How many tests have been created (includes drafts, active, and completed) |
| **📊 Active Assessments** | Tests that are currently running — students can take them right now |
| **⚠️ Pending Actions** | Things that need your attention (like verifying new student accounts) |

Each card has a small arrow → that you can click to see more details.

## Charts Section

### 📈 Average Performance (The Line Graph)

This is a chart that shows how students are performing over time. 

- The **line** goes up when scores improve and down when they drop
- The **green dot** at the end shows the current average score
- Hover your mouse over the chart to see exact numbers for each point in time

> **What to look for:** If the line is trending upward, students are improving. If it's dropping, you may want to review the teaching material.

### 🎯 Assessment Distribution (The Coloured Ring)

This circle shows the **status of all your tests**:

| Colour | What it means |
|--------|---------------|
| **Blue** 🟦 | Active tests — students can take these |
| **Amber** 🟧 | Upcoming tests — scheduled but not yet started |
| **Green** 🟩 | Completed tests — already finished |

The **big number in the centre** shows the dominant category. For example, if most of your tests are Active, it'll show "71% Active".

The **legend on the right** breaks down the exact count for each status.

### 🔄 Submission Overview (Another Coloured Ring)

This circle shows the **progress of student submissions**:

| Colour | What it means |
|--------|---------------|
| **Cyan** 🔷 | In Progress — students are currently taking the test |
| **Amber** 🟧 | Submitted — students have finished but not yet graded |
| **Green** 🟩 | Evaluated — grading is complete and results are available |

## Quick Actions (The 6 Shortcut Cards)

These are one-click shortcuts to your most common tasks:

| Icon | Action |
|------|--------|
| **➕ Create Assessment** | Start making a new test |
| **⏳ Pending Verifications** | Approve new student accounts that need confirmation |
| **👥 Manage Students** | View and edit student information |
| **📚 Manage Batches** | Organise students into groups/batches |
| **📖 Manage Courses** | Add or edit courses |
| **📊 View Reports** | See detailed performance reports |

## Recent Activity

### 📋 Recent Assessments

A table showing the most recently created or modified tests:
- **Name** — the test title
- **Status** — whether it's Active, Draft, Paused, or Completed
- **Batch** — which student group it's assigned to
- **Questions** — how many questions in the test
- **Actions** — buttons to edit, view, or manage the assessment

### 👤 Recent Students

A list of students who recently joined or interacted with the platform:
- **Name** and **Email**
- **Batch** they belong to
- **Status** — Active or Pending
- **Actions** — buttons to view their profile or edit details

### 🔴 Live Activity Feed

A real-time stream showing what's happening right now:
- *"John started Test 3"*
- *"Sarah submitted Chemistry Quiz"*
- *"Admin paused Midterm Exam"*

This auto-refreshes, so you can monitor activity without refreshing the page.

### ✅ System Status

4 indicators showing if everything is working properly:

| Indicator | What it checks |
|-----------|----------------|
| **Database** | Are student records accessible? |
| **Mail Server** | Can the system send emails (verifications, notifications)? |
| **Analysis API** | Is the grading/analysis engine running? |
| **Storage** | Can files and data be saved? |

Each shows a green check ✅ (healthy) or warning ⚠️ (needs attention).

---

# 📂 LEFT SIDEBAR — Navigation Menu

The sidebar on the left is your main menu. Click any item to go to that section.

## Overview

| Menu Item | What you'll find there |
|-----------|------------------------|
| **📊 Dashboard** | Your home screen (described above) |

## Institution Management

| Menu Item | What you'll find there |
|-----------|------------------------|
| **🏛️ Colleges** | Add and manage colleges/institutions. You can add new colleges, edit names, or remove them. |
| **📖 Courses** | Create courses (e.g., "Mathematics 101", "Physics Lab"). Each course belongs to a college. |
| **📚 Batches** | Group students into batches (e.g., "Batch 2025-A", "Morning Section"). This is how you assign tests to specific groups. |

> **Example workflow:** Create a College → Add Courses to it → Create Batches under those courses → Assign Students to batches → Create Tests for those batches.

## Student Management

| Menu Item | What you'll find there |
|-----------|------------------------|
| **👥 Students** | View all registered students, add new ones, edit their info, or remove them. |
| **⏳ Pending Verifications** | New students who registered but need your approval before they can log in. Click to verify them. |
| **🔗 Guest Access** | Set up temporary guest access for students who don't have accounts (e.g., demo tests). |

## Assessment Studio

| Menu Item | What you'll find there |
|-----------|------------------------|
| **➕ Create Assessment** | The test builder — add questions, set duration, choose batch, and publish. |
| **📄 Draft Assessments** | Tests you've started but haven't published yet. Come back later to finish them. |
| **❓ Question Library** | A bank of all your saved questions. Reuse questions across multiple tests instead of typing them again. |

## Assessment Management

| Menu Item | What you'll find there |
|-----------|------------------------|
| **📋 All Assessments** | A complete list of every test. From here you can Edit, Pause, Resume, or End tests. |
| **🔴 Live Monitor** | Watch ongoing tests in real-time — see who's currently taking the test, who has submitted, and track suspicious activity. |
| **✅ Grading** | For tests that need manual grading (like essay questions). Review and assign scores here. |

## Reports & Analytics

| Menu Item | What you'll find there |
|-----------|------------------------|
| **📊 Reports** | Detailed performance reports — average scores, pass rates, per-student breakdowns. |
| **🔄 Tab Activity** | See which students switched tabs during online tests (potential cheating indicator). |
| **⚠️ Failed Logins** | A log of unsuccessful login attempts — useful for spotting security issues. |

## System

| Menu Item | What you'll find there |
|-----------|------------------------|
| **⚙️ Settings** | System-wide settings like email configuration, test defaults, and security options. |
| **📜 Activity Logs** | A detailed record of all actions taken in the system (who did what and when). |

## Support

| Menu Item | What you'll find there |
|-----------|------------------------|
| **❓ Help & Documentation** | Built-in help resources and links to this manual. |

## Bottom of Sidebar

| Button | What it does |
|--------|-------------|
| **🚪 Sign Out** | Logs you out of the system |

---

# 🧑‍🎓 STUDENT EXPERIENCE

This is what students see when they log in.

## Student Dashboard

- **Welcome greeting** with the student's name and today's date
- **Statistics cards:** How many tests they have total, completed, and pending
- **My Tests:** A list of assigned tests with buttons to Start or View Results
- **Info card:** Helpful tips about the platform

## Student Sidebar

| Menu Item | What it does |
|-----------|-------------|
| **📊 Dashboard** | Home screen with test overview |
| **📝 My Tests** | View all assigned tests |
| **📈 Results** | See scores and marks for completed tests |
| **📉 Analytics** | Charts showing performance trends over time |
| **👤 Profile** | View and edit personal information |
| **🌙 Dark/Light Mode** | Switch between dark and light themes |
| **🚪 Sign Out** | Log out |

## Taking a Test

1. Click **"Start Test"** on any active test
2. A full-screen test interface opens with a **timer** counting down
3. Answer questions by clicking on your choice
4. The **question navigator** (dots on the side) shows which questions you've answered
5. Click **"Submit"** when finished
6. **⚠️ Important:** The system detects if you switch tabs — excessive tab switching may be flagged

---

# 🛠️ COMMON TASKS — Step by Step

## Creating a New Test

1. Click **"Create Assessment"** in the top bar, OR go to **Assessment Studio → Create Assessment** in the sidebar
2. Fill in the details:
   - **Title** — Give your test a name (e.g., "Midterm Physics")
   - **Duration** — How many minutes students have to complete it
   - **Batch** — Which student group should take this test
3. **Add questions:**
   - Write new questions, or
   - Pick questions from your **Question Library**
4. Choose question types (multiple choice, true/false, etc.)
5. Click **"Save as Draft"** to finish later, or **"Publish"** to make it available to students

## Verifying a New Student

1. Go to **Student Management → Pending Verifications** in the sidebar
2. You'll see a list of students waiting for approval
3. Click **"Verify"** next to their name to approve them
4. They'll receive an email confirming their account is active

## Viewing Test Results

1. Go to **Assessment Management → All Assessments**
2. Find the test you want to review
3. Click the **"Results"** button to see:
   - Each student's score
   - Class average
   - Question-by-question breakdown

## Pausing or Ending a Test

1. Go to **Assessment Management → All Assessments**
2. Find the active test
3. Click:
   - **"Pause"** to temporarily stop the test (students can resume later)
   - **"End"** to permanently stop it (no more submissions allowed)

---

# 🔤 ICON CHEAT SHEET

Here's what the icons mean throughout the system:

| Icon | Meaning |
|------|---------|
| 🏛️ | Colleges |
| 📖 | Courses |
| 📚 | Batches |
| 👥 | Students |
| ⏳ | Pending / Waiting |
| 🔗 | Guest / External |
| ➕ | Create / Add |
| 📄 | Document / Draft |
| ❓ | Questions |
| 📋 | List / All Items |
| 🔴 | Live / Active Now |
| ✅ | Grading / Check |
| 📊 | Reports / Charts |
| 🔄 | Tab Activity |
| ⚠️ | Warnings / Issues |
| ⚙️ | Settings |
| 📜 | History / Logs |
| 🚪 | Sign Out |
| 🌙 | Dark Mode |
| ☀️ | Light Mode |
| 🔔 | Notifications |
| 🔍 | Search |
| ✏️ | Edit |
| 🗑️ | Delete / Trash |
| ▶️ | Play / Start |
| ⏸️ | Pause |
| ⏹️ | Stop / End |
| 🔄 | Refresh |

---

# ❓ FREQUENTLY ASKED QUESTIONS

**Q: A student says they can't log in. What do I check?**
> First, go to **Pending Verifications** and approve their account. If they're already verified, check that their email and password are correct.

**Q: I need to give a student extra time on a test.**
> Go to **All Assessments**, find the test, click **"Edit"**, and adjust the duration. Or contact your technical team to extend time for specific students.

**Q: How do I see if students cheated?**
> Go to **Reports → Tab Activity**. This shows which students switched tabs during the test and how many times.

**Q: Can I reuse questions from a previous test?**
> Yes! Add your questions to the **Question Library** first. Then when creating a new test, you can import them.

**Q: What's the difference between Pause and End?**
> **Pause** is temporary — students can resume where they left off. **End** is permanent — no one can submit after you end a test.

**Q: I accidentally created a test with wrong settings.**
> Don't worry. Save it as **Draft** (not Published). Drafts can be edited freely. If it's already published, you can **Pause** it, edit the settings, then resume.

**Q: How do I switch between Dark and Light mode?**
> Click the **🌙/☀️ icon** in the top bar (admin) or sidebar (student). Your preference will be remembered next time you log in.

**Q: What does the search bar search for?**
> It searches through all the navigation items — type "student" to find the Students page, "report" to find Reports, etc.

---

# 📱 USING ON MOBILE

The system works on phones and tablets too.

- **On phones:** The sidebar is hidden by default. Tap the **☰ icon** (top left) to open it
- **On tablets:** The sidebar shrinks to show only icons
- **Graphs and charts** automatically resize to fit your screen
- Turn your phone **sideways (landscape)** for a wider view of tables and data

---

> 💡 **Need more help?** Contact your system administrator or the person who set up your Exam Portal. They can help with technical issues and account setup.
