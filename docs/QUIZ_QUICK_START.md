# Module Quiz - Quick Start Guide

## 🚀 For Admins (5 Minutes)

### Create Your First Quiz

1. **Navigate:** Admin → Course Online → Select Course → View Details
2. **Click:** 📋 Clipboard icon next to any module
3. **Fill Basic Info:**
   ```
   Title: Module 1 Quiz
   Pass Threshold: 70%
   Max Attempts: 3
   Status: Published
   ✓ Required to Proceed
   ```
4. **Add Questions:** Click "Add Question"
   - Type: Single Choice
   - Question: "What is 2+2?"
   - Options: 3, 4, 5, 6
   - Correct: 4
   - Points: 1
5. **Save:** Click "Create Quiz"

✅ Done! Students can now take the quiz.

---

## 👨‍🎓 For Users (3 Minutes)

### Take Your First Quiz

1. **Complete Content:** Finish all videos/PDFs in the module (100%)
2. **Find Quiz:** "Module Quiz" appears at bottom of module
3. **Click:** Module Quiz → Start Quiz
4. **Answer:** Select answers, navigate with Previous/Next
5. **Submit:** Click "Submit Quiz"
6. **View Results:** See your score and pass/fail status

**If Failed:** Click "Try Again" (if attempts remaining)
**If Passed:** ✅ Next module unlocks!

---

## ⚡ Common Settings

### Recommended Quiz Settings

**Learning Quiz (Practice):**
```
Pass Threshold: 60%
Max Attempts: 5
Time Limit: None
Retry Delay: 0 hours
Show Answers: Always
Required: No
```

**Assessment Quiz (Graded):**
```
Pass Threshold: 75%
Max Attempts: 2
Time Limit: 30 minutes
Retry Delay: 24 hours
Show Answers: After Passing
Required: Yes
```

**Final Exam:**
```
Pass Threshold: 80%
Max Attempts: 1
Time Limit: 60 minutes
Retry Delay: N/A
Show Answers: Never
Required: Yes
```

---

## 🎯 Question Types

| Type | Use For | Auto-Graded | Example |
|------|---------|-------------|---------|
| **Radio** | Single correct answer | ✅ Yes | "What is the capital of France?" |
| **Checkbox** | Multiple correct answers | ✅ Yes | "Select all prime numbers: 2,3,4,5" |
| **Text** | Open-ended responses | ❌ No | "Explain photosynthesis" |

---

## 🔧 Troubleshooting (30 Seconds)

**Can't see quiz button?**
→ Go to Course "View Details" page

**Students can't see quiz?**
→ Check: Status = Published, Content = 100% complete

**Quiz locked for student?**
→ Check: Previous module quiz passed, Attempts remaining

**Can't delete quiz?**
→ Quizzes with attempts can't be deleted (edit instead)

---

## 📱 Direct URLs

**Admin:**
- Create Quiz: `/admin/course-online/{courseId}/modules/{moduleId}/quiz/create`
- View Quiz: `/admin/course-online/{courseId}/modules/{moduleId}/quiz/{quizId}`

**User:**
- Take Quiz: `/courses-online/{courseId}/modules/{moduleId}/quiz`
- View Results: `/courses-online/{courseId}/modules/{moduleId}/quiz/result/{attemptId}`

---

## 💡 Pro Tips

**Admins:**
- Test quiz yourself before publishing
- Add explanations to help students learn
- Use 2 min per question for time limits
- Give at least 2-3 attempts for learning

**Users:**
- Complete all content before starting
- Read questions carefully
- Use all attempts to improve
- Review explanations after each try

---

## 📊 At a Glance

```
Module Content (Videos/PDFs)
         ↓
    100% Complete
         ↓
   Module Quiz Appears
         ↓
    Student Takes Quiz
         ↓
    Pass? → Yes → Next Module Unlocks
         ↓
         No → Retry (if attempts left)
```

---

**Need More Help?** See full guide: `docs/MODULE_QUIZ_GUIDE.md`
