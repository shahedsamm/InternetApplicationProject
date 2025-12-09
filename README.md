Roles:
1. Citizen 
2. Employee
3. Admin

Tasks:
1. Build Role and Permission Seeder and Service and controller and API [done]
2. Build Media table [done]
3. Build Complaint Table, model (link to media) seeder, service, controller and api 
   - [done] columns (citizen_id (FK), type(enum), section(enum), location (text), description, media (json), serial_number(auto generated), status (enum:(new, pending, done, rejected), default new), notes (text, nullable))
   - [done] Build complaint update history, link to complaint model,
   - [done] Build complaint followup requests table, link to complaint model
   - when updating:
     + [done] columns (citizen_id (FK), type(enum), section(enum), location (text), description, media (json), serial_number(auto generated), status (enum:(new, pending, done, rejected), default new), notes (text, nullable), followup array (title))
     + [done] lock record + show message if record is locked at the moment with status code 401
     + [done] create a complaint update history record (complain id(FK), employee_id(FK), followup_id(FK) (nullable), status, timestamps, notes(nullable), title(what was updated this time)),
     + [done] create followup record if exists
     + send notification to user (email and fcm)
   - show function should return complaint update history and complaint followup request (with)
       + add filter citizen_id if role is citizen (can only see his complaints)
       + add filter section equal employee section if role is employee
   - index function should be paginated + 
     + add filter citizen_id if role is citizen (can only see his complaints)
     + add filter section equal employee section if role is employee
   - statistics function should return the number of each status throughout start_date, end_date filter (default last week)
   - export function should return a csv of the stats + complaints throughout start_date, end_date

4. Seed Employees, user service [done]
   - admin can crud employees
   - admin can not delete his own account
   
5. Build Auth service, 
   - login employee/admin/citizen + send otp to email
     + count no_failed_tries requests and stop after three and set the last_failed_try_date column to now()
     + lock the account and send notification (Email + fcm) to wait 5 minutes and try again
     + schedule job to reset the no_failed_tries column to zero and last_failed_try_date to null
   - register citizen (name, email, email_verified_at)
   - logout
6. call mustVerifyEmail inside login and write the email template and setup env
7. create a verified email middleware and add next to auth:sanctum middleware
8. Test functions using serverless command
9. add request rate limit to all requests and throttle
10. backup database and schedule it, + restore function + store backup history (link, date)
11. notification index
"# InternetApplication1" 
