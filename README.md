# CollabTech
 
1. To reword 'View Project' as 'New Project'.✅

2. To add 'New Project' at Dashboard page ✅

3. To enable multi role labels ✅

4. To disable mandatory minimum roles in a team; maksudnya tak perlu ada semua roles baru boleh proceed with the 'Role Assignment'.  ✅

5. To add another extra role 'Technical Support'. ✅

6. To remove roles 'Manager' and 'Senior Executive'. ✅

7. To disable mandatory input for 'Budget'.✅

8. Team lead must be able to submit project ideas (sekarang takleh - atau mungkin semua takleh). ✅

9. Assigned team members must be able to collaborate with the assigned project (sekarang tak reflected kalua login kat accounts lain yang aku assign) ✅


# 22/7/2024
1. To organise the dashboard✅
2. Idea submission to be included in one go at New Project page✅
3. Assigned projects are not reflected at assigned user dashboard✅
4. Navigating to the next process after Idea Submission✅
5. Page flow for Requirement Analysis✅
6. Risk Analysis Flow - popup modal for high risk✅
7. Task list - project list and click to see the task - can see all task for the project
7. Auto create task based on ideas - and can create sub task(optional)
8. Open tab project based on project status✅


# update table
1. TP_ParentCode - task project
2. PRAcceptRisk - project risk
2. PJ_RejectReason - project reject reason

# 29/10/2024

- handle error - add document✅
- redirect back to project view - after finish✅
- idea submission - add next button
- project idea no change to 5 - now no 4✅
- project idea analysis edit - action button add text - Next Action✅
- check idea analysis - all complete before submit analysis✅
- button back put top
- Requirement analysis button - add text Next Action ✅
- submit risk - stuck submit not proceed submit ✅
- accept risk - error db ✅
- project cancel if risk is high/medium, button show on popup confirm (Cancel Project) ✅


# hold for next update
copy task management
   - pecahkan jadi 3 section
      - project design - 7
            - listing from task management - change to project design
      - future development - 8
            - listing from project design
      - project closure - 9
            - listing from future development
            - final product, acceptance, deployment, maintenance

DB change
- TPTaskProject
  - add column
    - TPType - varchar
    - TPComplete - bit

- TPIssue
  - TPIID - auto increment
