# Module Quiz System - Workflow & Architecture

## 🔄 Complete User Flow

```
┌─────────────────────────────────────────────────────────────┐
│                    STUDENT JOURNEY                          │
└─────────────────────────────────────────────────────────────┘

1. ENROLL IN COURSE
   ↓
2. START MODULE 1
   ↓
3. WATCH VIDEOS & READ PDFs
   ↓ (Progress tracked automatically)
   ↓
4. REACH 100% MODULE CONTENT COMPLETION
   ↓
5. "MODULE QUIZ" SECTION APPEARS ✨
   ↓
6. CLICK "MODULE QUIZ"
   ↓
7. VIEW QUIZ INFO PAGE
   - Quiz title & description
   - Number of questions
   - Pass threshold
   - Time limit
   - Attempts remaining
   ↓
8. CLICK "START QUIZ"
   ↓
9. QUIZ ATTEMPT CREATED
   - Timer starts (if enabled)
   - Questions loaded
   ↓
10. ANSWER QUESTIONS
    - Navigate between questions
    - Answers auto-saved
    ↓
11. CLICK "SUBMIT QUIZ"
    ↓
12. QUIZ GRADED AUTOMATICALLY
    - Radio/Checkbox: Auto-graded
    - Text: Requires manual grading
    ↓
13. VIEW RESULTS
    ├─ PASSED (Score ≥ Threshold)
    │  ├─ ✅ Congratulations!
    │  ├─ Next module unlocks
    │  └─ Can view correct answers (if enabled)
    │
    └─ FAILED (Score < Threshold)
       ├─ ❌ Score shown
       ├─ Attempts remaining shown
       ├─ Can retry (if attempts left)
       └─ May see correct answers (based on settings)
    ↓
14. IF PASSED: PROCEED TO MODULE 2
    IF FAILED: RETRY OR STUDY MORE
```

---

## 🔧 Admin Workflow

```
┌─────────────────────────────────────────────────────────────┐
│                    ADMIN JOURNEY                            │
└─────────────────────────────────────────────────────────────┘

1. CREATE COURSE & MODULES
   ↓
2. ADD CONTENT TO MODULES
   (Videos, PDFs)
   ↓
3. NAVIGATE TO COURSE DETAILS
   ↓
4. CLICK 📋 CLIPBOARD ICON ON MODULE
   ↓
5. CREATE QUIZ PAGE OPENS
   ↓
6. CONFIGURE QUIZ SETTINGS
   - Title & description
   - Pass threshold
   - Max attempts
   - Time limit
   - Retry delay
   - Show answers setting
   - Required toggle
   ↓
7. ADD QUESTIONS
   ├─ Radio (Single choice)
   ├─ Checkbox (Multiple choice)
   └─ Text (Open-ended)
   ↓
8. SET CORRECT ANSWERS & POINTS
   ↓
9. ADD EXPLANATIONS (Optional)
   ↓
10. SAVE QUIZ
    ↓
11. QUIZ PUBLISHED
    ├─ Badge appears on module
    └─ Students can now take it
    ↓
12. MONITOR ATTEMPTS
    - View all student attempts
    - See scores & pass rates
    - Review individual answers
    ↓
13. EDIT QUIZ (If needed)
    - Update questions
    - Adjust settings
    - Cannot delete questions with answers
```

---

## 🗄️ Database Architecture

```
┌──────────────────┐
│   course_online  │
└────────┬─────────┘
         │
         │ has many
         ↓
┌──────────────────┐
│ course_modules   │◄─────────┐
│ - has_quiz       │          │
│ - quiz_required  │          │
└────────┬─────────┘          │
         │                    │
         │ has one            │ belongs to
         ↓                    │
┌──────────────────┐          │
│     quizzes      │──────────┘
│ - module_id      │
│ - is_module_quiz │
│ - max_attempts   │
│ - pass_threshold │
└────────┬─────────┘
         │
         │ has many
         ↓
┌──────────────────┐
│ quiz_questions   │
│ - type           │
│ - points         │
│ - options        │
│ - correct_answer │
└────────┬─────────┘
         │
         │ has many
         ↓
┌──────────────────┐
│  quiz_attempts   │
│ - user_id        │
│ - attempt_number │
│ - score          │
│ - passed         │
│ - started_at     │
│ - completed_at   │
└────────┬─────────┘
         │
         ├─ has many
         │  ↓
         │ ┌──────────────────┐
         │ │  quiz_answers    │
         │ │ - answer         │
         │ │ - is_correct     │
         │ │ - points_earned  │
         │ └──────────────────┘
         │
         └─ has one
            ↓
         ┌──────────────────────┐
         │ module_quiz_results  │
         │ - passed             │
         │ - score_percentage   │
         │ - points_earned      │
         └──────────────────────┘
```

---

## 🎯 Module Unlock Logic

```
Can User Access Module N?
    ↓
Is Module N = Module 1?
    ├─ YES → ✅ UNLOCKED
    └─ NO → Continue...
        ↓
Is User Assigned to Course?
    ├─ NO → 🔒 LOCKED
    └─ YES → Continue...
        ↓
Is Module N-1 Content 100% Complete?
    ├─ NO → 🔒 LOCKED (Complete previous content)
    └─ YES → Continue...
        ↓
Does Module N-1 Have Required Quiz?
    ├─ NO → ✅ UNLOCKED
    └─ YES → Continue...
        ↓
Has User Passed Module N-1 Quiz?
    ├─ NO → 🔒 LOCKED (Pass previous quiz)
    └─ YES → ✅ UNLOCKED
```

---

## 🔐 Quiz Access Logic

```
Can User Take Quiz?
    ↓
Is Module Unlocked?
    ├─ NO → 🔒 LOCKED (Complete previous module)
    └─ YES → Continue...
        ↓
Is Module Content 100% Complete?
    ├─ NO → 🔒 LOCKED (Complete all content first)
    └─ YES → Continue...
        ↓
Has User Reached Max Attempts?
    ├─ YES → 🔒 LOCKED (No attempts remaining)
    └─ NO → Continue...
        ↓
Is Retry Delay Active?
    ├─ YES → 🔒 LOCKED (Wait X hours)
    └─ NO → Continue...
        ↓
Is Quiz Published?
    ├─ NO → 🔒 LOCKED (Quiz not available)
    └─ YES → ✅ CAN TAKE QUIZ
```

---

## 📊 Grading Process

```
User Submits Quiz
    ↓
For Each Question:
    ↓
Is Question Type = "text"?
    ├─ YES → Skip (Manual grading needed)
    │        Points = 0
    └─ NO → Continue...
        ↓
Is Question Type = "radio"?
    ├─ YES → Compare user answer with correct answer
    │        ├─ Match? → Award points
    │        └─ No match? → 0 points
    └─ NO → Must be "checkbox"...
        ↓
Compare user answers with correct answers
    ├─ All correct selected? → Award points
    └─ Missing or extra? → 0 points
    ↓
Calculate Total Score
    ↓
Total Score ≥ Pass Threshold?
    ├─ YES → PASSED ✅
    │        └─ Create ModuleQuizResult (passed=true)
    └─ NO → FAILED ❌
            └─ Create ModuleQuizResult (passed=false)
    ↓
Show Results to User
```

---

## 🔄 Retry Logic

```
User Wants to Retry Quiz
    ↓
Check Attempts Used < Max Attempts?
    ├─ NO → Show "Max attempts reached"
    └─ YES → Continue...
        ↓
Check Retry Delay
    ↓
Is Retry Delay > 0?
    ├─ NO → Allow immediate retry
    └─ YES → Check last attempt time
        ↓
Has enough time passed?
    ├─ NO → Show "Wait X hours"
    └─ YES → Allow retry
        ↓
Create New Attempt
    ↓
Increment Attempt Number
    ↓
Start Quiz
```

---

## 📱 Frontend Components

```
Admin Components:
├─ Admin/ModuleQuiz/Index.vue
│  └─ Lists all quizzes for a module
├─ Admin/ModuleQuiz/Create.vue
│  └─ Create new quiz with questions
├─ Admin/ModuleQuiz/Edit.vue
│  └─ Edit existing quiz
├─ Admin/ModuleQuiz/Show.vue
│  └─ View quiz details
└─ Admin/ModuleQuiz/Attempts.vue
   └─ View all student attempts

User Components:
├─ User/ModuleQuiz/Show.vue
│  └─ Quiz info before starting
├─ User/ModuleQuiz/Take.vue
│  └─ Quiz taking interface
├─ User/ModuleQuiz/Result.vue
│  └─ Results after submission
└─ User/ModuleQuiz/History.vue
   └─ All attempts history
```

---

## 🔌 API Endpoints

### Admin Routes
```
GET    /admin/course-online/{course}/modules/{module}/quiz
       → View quiz management page

GET    /admin/course-online/{course}/modules/{module}/quiz/create
       → Show create quiz form

POST   /admin/course-online/{course}/modules/{module}/quiz
       → Store new quiz

GET    /admin/course-online/{course}/modules/{module}/quiz/{quiz}
       → Show quiz details

GET    /admin/course-online/{course}/modules/{module}/quiz/{quiz}/edit
       → Show edit quiz form

PUT    /admin/course-online/{course}/modules/{module}/quiz/{quiz}
       → Update quiz

DELETE /admin/course-online/{course}/modules/{module}/quiz/{quiz}
       → Delete quiz

GET    /admin/course-online/{course}/modules/{module}/quiz/{quiz}/attempts
       → View all attempts
```

### User Routes
```
GET    /courses-online/{course}/modules/{module}/quiz
       → View quiz info

POST   /courses-online/{course}/modules/{module}/quiz/start
       → Start new attempt

GET    /courses-online/{course}/modules/{module}/quiz/take/{attempt}
       → Take quiz interface

POST   /courses-online/{course}/modules/{module}/quiz/save-answer/{attempt}
       → Auto-save answer

POST   /courses-online/{course}/modules/{module}/quiz/submit/{attempt}
       → Submit quiz for grading

GET    /courses-online/{course}/modules/{module}/quiz/result/{attempt}
       → View results

GET    /courses-online/{course}/modules/{module}/quiz/history
       → View all attempts
```

---

## 🎨 UI/UX Flow

### Admin UI
```
Course List
    ↓ Click "View Details"
Course Details Page
    ├─ Module 1 [📋 Quiz] [View] [Edit]
    ├─ Module 2 [📋 Quiz] [View] [Edit]
    └─ Module 3 [📋 Quiz] [View] [Edit]
        ↓ Click 📋
Quiz Management
    ├─ Create Quiz (if none exists)
    └─ View/Edit Quiz (if exists)
        ↓
Quiz Details
    ├─ [Edit] button
    ├─ [Attempts] button
    ├─ [Delete] button
    └─ Questions list
```

### User UI
```
My Courses
    ↓ Click course
Course Details
    ├─ Module 1 (Expanded)
    │   ├─ Video 1 ✅
    │   ├─ Video 2 ✅
    │   ├─ PDF 1 ✅
    │   └─ 📝 Module Quiz [Required]
    │       ↓ Click
    ├─ Module 2 (Locked 🔒)
    └─ Module 3 (Locked 🔒)
        ↓
Quiz Info Page
    ├─ Quiz details
    ├─ Attempts: 0/3
    └─ [Start Quiz] button
        ↓
Quiz Taking Page
    ├─ Timer (if enabled)
    ├─ Question navigation
    ├─ Progress bar
    └─ [Submit] button
        ↓
Results Page
    ├─ Score: 8/10 (80%)
    ├─ Status: PASSED ✅
    ├─ Question review
    └─ [Continue] or [Retry]
```

---

## 🔔 Notifications & Feedback

### Success Messages
- ✅ "Quiz created successfully!"
- ✅ "Quiz updated successfully!"
- ✅ "Quiz submitted successfully!"
- ✅ "Congratulations! You passed the quiz!"

### Error Messages
- ❌ "You must complete all module content first"
- ❌ "Maximum attempts reached"
- ❌ "Please wait X hours before retrying"
- ❌ "Cannot delete quiz with existing attempts"

### Info Messages
- ℹ️ "You have X attempts remaining"
- ℹ️ "Time remaining: X minutes"
- ℹ️ "Quiz auto-submitted due to time limit"

---

## 📈 Analytics & Reporting

### Admin Can View:
- Total quiz attempts
- Pass rate percentage
- Average score
- Highest/lowest scores
- Individual student attempts
- Question-level statistics

### User Can View:
- Personal attempt history
- Best score achieved
- Attempts remaining
- Time taken per attempt
- Question-by-question review

---

*This document provides a complete overview of the Module Quiz system architecture and workflows.*
