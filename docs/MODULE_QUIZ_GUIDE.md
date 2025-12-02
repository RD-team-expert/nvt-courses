# Module Quiz System - Complete Guide

## Overview
The Module Quiz System allows administrators to create quizzes for each course module. Students must complete all module content and pass the quiz (if required) before proceeding to the next module.

---

## 📋 Table of Contents
1. [Admin Guide](#admin-guide)
2. [User Guide](#user-guide)
3. [Quiz Settings Explained](#quiz-settings-explained)
4. [Troubleshooting](#troubleshooting)

---

## 🔧 Admin Guide

### Creating a Module Quiz

#### Step 1: Navigate to Course
1. Go to **Admin Dashboard** → **Course Online**
2. Find your course and click the **three dots menu (⋮)**
3. Select **"View Details"**

#### Step 2: Access Quiz Management
On the course details page, you'll see all modules listed. Each module has:
- **📋 Clipboard Icon** - Click this to create/manage quiz
- **View Button** - View module details
- **Edit Button** - Edit module settings

#### Step 3: Create Quiz
1. Click the **📋 Clipboard Icon** next to the module
2. If no quiz exists, you'll see the **Create Quiz** page
3. Fill in the quiz details:

**Basic Information:**
- **Quiz Title** - Name of the quiz (e.g., "Module 1 Assessment")
- **Description** - Optional description of what the quiz covers
- **Status** - Choose:
  - `Draft` - Quiz is hidden from students
  - `Published` - Quiz is visible and can be taken

**Quiz Settings:**
- **Pass Threshold (%)** - Minimum score to pass (e.g., 70%)
- **Max Attempts** - How many times students can retry (1-100)
- **Time Limit (minutes)** - Optional time limit per attempt
- **Retry Delay (hours)** - Hours students must wait between attempts (0 = no delay)
- **Show Correct Answers** - When to show correct answers:
  - `Never` - Never show answers
  - `After Passing` - Show only after student passes
  - `After Max Attempts` - Show after all attempts used
  - `Always` - Always show after submission

**Module Settings:**
- **Required to Proceed** - Toggle ON if students must pass to unlock next module

#### Step 4: Add Questions
Click **"Add Question"** to create questions. You can add up to 50 questions.

**Question Types:**

1. **Single Choice (Radio)**
   - Students select ONE correct answer
   - Add 2-10 options
   - Select ONE correct answer
   - Set points (e.g., 1 point)

2. **Multiple Choice (Checkbox)**
   - Students select MULTIPLE correct answers
   - Add 2-10 options
   - Select ALL correct answers
   - Set points (e.g., 2 points)

3. **Text Answer**
   - Students type their answer
   - Requires manual grading
   - Does NOT contribute to automatic scoring
   - Points are set to 0 automatically

**For Each Question:**
- Enter the question text
- Choose question type
- Add answer options (for radio/checkbox)
- Select correct answer(s)
- Set points (for auto-graded questions)
- Add explanation (optional) - shown to students based on settings

#### Step 5: Save Quiz
1. Review all questions
2. Check the passing score calculation shown at the top
3. Click **"Create Quiz"**
4. You'll be redirected to the course details page

### Managing Existing Quizzes

#### View Quiz Details
1. Go to course details page
2. Click the **📋 Clipboard Icon** on a module with a quiz
3. You'll see the quiz overview with all questions

#### Edit Quiz
1. From quiz details page, click **"Edit"**
2. Modify any settings or questions
3. Click **"Save Changes"**

**Note:** You can add/remove questions, but questions with existing answers cannot be deleted.

#### View Quiz Attempts
1. From quiz details page, click **"Attempts"**
2. See all student attempts with:
   - Student name and email
   - Attempt number
   - Score and percentage
   - Pass/fail status
   - Completion date

#### Delete Quiz
1. From quiz details page, click **"Delete"**
2. Confirm deletion
3. **Warning:** Can only delete quizzes with NO attempts

### Quiz Statistics

View quiz performance metrics:
- Total attempts
- Pass rate
- Average score
- Highest/lowest scores

---

## 👨‍🎓 User Guide

### Taking a Module Quiz

#### Step 1: Complete Module Content
1. Go to **My Courses** → Select your course
2. Click on a module to expand it
3. Complete ALL videos and PDFs in the module
4. Progress bar must reach 100%

#### Step 2: Access Quiz
Once all content is completed:
1. A **"Module Quiz"** section appears at the bottom of the module
2. Click on it to view quiz information
3. You'll see:
   - Quiz title and description
   - Number of questions
   - Total points
   - Pass threshold
   - Time limit (if any)
   - Your attempts used/remaining

#### Step 3: Start Quiz
1. Review the instructions
2. Check your attempts remaining
3. Click **"Start Quiz"** button
4. Quiz timer starts (if time limit is set)

#### Step 4: Answer Questions
- Navigate between questions using **Previous/Next** buttons
- Or click question numbers at the top to jump to any question
- Your answers are auto-saved as you go
- Progress bar shows how many questions you've answered

**Question Types:**
- **Single Choice** - Click ONE option
- **Multiple Choice** - Click ALL correct options
- **Text Answer** - Type your response

**Timer (if enabled):**
- Shows remaining time at the top
- Turns red when less than 1 minute remains
- Quiz auto-submits when time runs out

#### Step 5: Submit Quiz
1. Answer all questions (or as many as you can)
2. Click **"Submit Quiz"**
3. Confirm submission if you have unanswered questions
4. Wait for results

#### Step 6: View Results
After submission, you'll see:
- Your score and percentage
- Pass/fail status
- Time taken
- Question-by-question review
- Correct answers (if enabled by admin)
- Explanations (if provided)

**If You Passed:**
- ✅ Congratulations message
- Next module unlocks (if quiz was required)
- Option to view history

**If You Failed:**
- ❌ Score below passing threshold
- Attempts remaining shown
- Option to retry (if attempts available)
- Correct answers shown (based on admin settings)

### Retrying a Quiz

If you have attempts remaining:
1. From results page, click **"Try Again"**
2. Or go back to the module and click the quiz
3. Click **"Retry Quiz"**
4. Start a new attempt

**Retry Delay:**
If admin set a retry delay, you must wait before retrying. The next available time is shown.

### Viewing Quiz History

1. From quiz info page, click **"View History"**
2. See all your attempts:
   - Attempt number
   - Score and percentage
   - Pass/fail status
   - Duration
   - Date completed
3. Click **"View"** on any attempt to see detailed results

---

## ⚙️ Quiz Settings Explained

### Pass Threshold
- **What it is:** Minimum percentage needed to pass
- **Example:** 70% means student needs 7/10 points to pass
- **Recommendation:** 
  - Easy quizzes: 60-70%
  - Medium quizzes: 70-80%
  - Hard quizzes: 80-90%

### Max Attempts
- **What it is:** Total number of tries allowed
- **Example:** 3 attempts means student can take quiz 3 times
- **Recommendation:**
  - Learning quizzes: 3-5 attempts
  - Assessment quizzes: 1-2 attempts
  - Practice quizzes: Unlimited (set to 100)

### Time Limit
- **What it is:** Minutes allowed per attempt
- **Example:** 30 minutes means quiz auto-submits after 30 min
- **Recommendation:**
  - 2 minutes per question + 5 minute buffer
  - 10 questions = 25 minutes
  - Leave empty for no time limit

### Retry Delay
- **What it is:** Hours to wait between attempts
- **Example:** 24 hours means student waits 1 day before retry
- **Recommendation:**
  - No delay: 0 hours (immediate retry)
  - Short delay: 1-4 hours
  - Long delay: 24-48 hours

### Show Correct Answers
- **Never:** Students never see correct answers (use for final exams)
- **After Passing:** Show only after student passes (encourages learning)
- **After Max Attempts:** Show after all attempts used (prevents gaming)
- **Always:** Show immediately after submission (use for practice)

### Required to Proceed
- **ON:** Students MUST pass quiz to unlock next module
- **OFF:** Quiz is optional, next module unlocks after content completion
- **Recommendation:** ON for important assessments, OFF for practice

---

## 🔍 Troubleshooting

### Admin Issues

**Q: I can't see the quiz button on modules**
- **A:** Make sure you're on the course **"View Details"** page, not the course list
- Look for the 📋 clipboard icon next to each module

**Q: I can't delete a quiz**
- **A:** Quizzes with student attempts cannot be deleted (data integrity)
- You can edit it or set status to "Draft" to hide it

**Q: Students can't see the quiz**
- **A:** Check:
  - Quiz status is "Published" (not Draft)
  - Module has content for students to complete first
  - Students are assigned to the course

**Q: Quiz points don't add up correctly**
- **A:** Text questions don't contribute to automatic scoring
- Only radio and checkbox questions count toward total points

### User Issues

**Q: I can't see the module quiz**
- **A:** You must complete ALL module content first (100% progress)
- Check if quiz exists (admin may not have created one yet)

**Q: Quiz button is grayed out**
- **A:** Possible reasons:
  - Module content not 100% complete
  - Previous module quiz not passed (if required)
  - Maximum attempts reached
  - Retry delay not elapsed

**Q: I ran out of attempts**
- **A:** Contact your instructor/admin
- They can view your attempts and may grant additional tries
- Or they may adjust the max attempts setting

**Q: Timer expired before I finished**
- **A:** Quiz auto-submits when time runs out
- Your answers up to that point are graded
- Plan your time: divide time limit by number of questions

**Q: I can't see correct answers**
- **A:** Admin controls when answers are shown
- May be set to "Never" or "After Passing"
- Complete all attempts or pass the quiz to see answers

**Q: Next module is still locked after passing**
- **A:** Refresh the page
- If still locked, check if previous module quiz was set as "Required"
- Contact admin if issue persists

---

## 📊 Best Practices

### For Admins

1. **Start with Draft:** Create quiz in draft mode, test it, then publish
2. **Clear Questions:** Write clear, unambiguous questions
3. **Balanced Difficulty:** Mix easy, medium, and hard questions
4. **Reasonable Time:** Allow enough time for students to think
5. **Fair Attempts:** Give at least 2-3 attempts for learning
6. **Helpful Explanations:** Add explanations to help students learn
7. **Test First:** Take the quiz yourself before publishing

### For Users

1. **Complete Content First:** Watch all videos, read all materials
2. **Take Notes:** Write down key points while studying
3. **Read Carefully:** Read each question thoroughly
4. **Manage Time:** Keep track of time if there's a limit
5. **Review Before Submit:** Check all answers before submitting
6. **Learn from Mistakes:** Review explanations after each attempt
7. **Don't Rush:** Use all available attempts to improve

---

## 🎯 Quick Reference

### Admin Quick Actions
| Action | Location | Button |
|--------|----------|--------|
| Create Quiz | Course Details → Module | 📋 Clipboard Icon |
| Edit Quiz | Quiz Details | Edit Button |
| View Attempts | Quiz Details | Attempts Button |
| Delete Quiz | Quiz Details | Delete Button |

### User Quick Actions
| Action | Location | Button |
|--------|----------|--------|
| View Quiz Info | Course → Module (after 100% content) | Module Quiz Link |
| Start Quiz | Quiz Info Page | Start Quiz Button |
| Retry Quiz | Quiz Info Page | Retry Quiz Button |
| View History | Quiz Info Page | View History Button |
| View Results | After Submission | Automatic |

---

## 📞 Support

If you encounter issues not covered in this guide:
- **Admins:** Check Laravel logs for errors
- **Users:** Contact your course instructor or system administrator
- **Technical Issues:** Check browser console for JavaScript errors

---

## 🔄 Updates

**Version 1.0** - December 2025
- Initial release of Module Quiz System
- Support for radio, checkbox, and text questions
- Configurable quiz settings per module
- Attempt tracking and history
- Auto-grading for objective questions

---

*Last Updated: December 1, 2025*
