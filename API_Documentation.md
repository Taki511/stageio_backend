# Stage.io API Documentation

## Base URL
```
http://127.0.0.1:8000/api
```

## Authentication
All protected routes require a Bearer token in the Authorization header:
```
Authorization: Bearer {your_token_here}
```

---

## Table of Contents
1. [Authentication](#authentication-endpoints)
2. [Password Reset](#password-reset)
3. [Student CV](#student-cv-endpoints)
4. [Company Profile](#company-profile-endpoints)
5. [Internship Offers (Public)](#internship-offers-public)
6. [Internship Offers (Recruiter)](#internship-offers-recruiter)
7. [Applications (Student)](#applications-student)
8. [Applications (Recruiter)](#applications-recruiter)
9. [Admin Validation](#admin-validation)
10. [Admin Agreements](#admin-agreements)
11. [Auto-Actions Status](#auto-actions-status-all-users)
12. [Role-Based Dashboards](#role-based-dashboards)

---

## Authentication Endpoints

### Register Student
```http
POST /register
Content-Type: application/json

{
    "email": "ahmed@example.com",
    "password": "password123",
    "first_name": "ahmed",
    "last_name": "khaled",
    "university_email": "ahmed@univ-constantine2.com"
}
```

### Login
```http
POST /login
Content-Type: application/json

{
    "email": "ahmed@example.com",
    "password": "password123"
}
```

### Logout
```http
POST /logout
Authorization: Bearer {token}
```

### Logout All Devices
```http
POST /logout-all
Authorization: Bearer {token}
```

### Get Current User
```http
GET /me
Authorization: Bearer {token}
```

**Response:**
```json
{
    "user": {
        "id": 1,
        "name": "ahmed khaled",
        "email": "ahmed@example.com",
        "role": "student",
        "student_profile": {
            "id": 1,
            "first_name": "ahmed",
            "last_name": "khaled",
            "university_email": "ahmed@univ-constantine2.com"
        },
        "recruiter_profile": null,
        "admin_profile": null
    }
}
```

---

## Password Reset

### Forgot Password
Request a password reset link via email.
```http
POST /forgot-password
Content-Type: application/json

{
    "email": "ahmed@example.com"
}
```

**Response:**
```json
{
    "message": "Password reset link has been sent to your email."
}
```

**Note:** The email contains a reset URL with token. Extract the token from:
```
http://localhost/api/reset-password?token=TOKEN_HERE&email=ahmed@example.com
```

### Reset Password
Reset password using the token from email.
```http
POST /reset-password
Content-Type: application/json

{
    "email": "ahmed@example.com",
    "token": "random_token_from_email",
    "password": "newpassword123",
    "password_confirmation": "newpassword123"
}
```

**Response:**
```json
{
    "message": "Password has been reset successfully."
}
```

---

## Student CV Endpoints

### Create CV
```http
POST /my-cv
Authorization: Bearer {student_token}
Content-Type: application/json

{
    "first_name": "ahmed",
    "last_name": "khaled",
    "age": 22,
    "full_address": "123 Main Street, Algiers, Algeria",
    "phone_number": "+213 555 123 456",
    "academic_level": "3rd Year Computer Science",
    "email": "ahmed.khaled@example.com",
    "university_email": "ahmed.khaled@univ-constantine2.com",
    "github_link": "https://github.com/ahmedkhaled",
    "linkedin_link": "https://linkedin.com/in/ahmedkhaled",
    "portfolio_link": "https://ahmedkhaled.portfolio.com",
    "motivation_letter": "I am passionate about software development...",
    "personal_info": "Additional personal information...",
    "personal_photo": "https://example.com/photos/ahmed.jpg"
}
```

**Required Fields:**
- `first_name`
- `last_name`
- `age` (16-100)
- `full_address`
- `phone_number`
- `academic_level`
- `email`
- `university_email`

**Optional Fields:**
- `github_link`
- `linkedin_link`
- `portfolio_link`
- `motivation_letter`
- `personal_info`
- `personal_photo` (URL to photo)

### View My CV
```http
GET /my-cv
Authorization: Bearer {student_token}
```

**Response (Success):**
```json
{
    "data": {
        "id": 1,
        "student_id": 1,
        "first_name": "ahmed",
        "last_name": "khaled",
        "age": 22,
        "full_address": "123 Main Street, Algiers, Algeria",
        "phone_number": "+213 555 123 456",
        "academic_level": "3rd Year Computer Science",
        "email": "ahmed.khaled@example.com",
        "university_email": "ahmed.khaled@univ-constantine2.com",
        "github_link": "https://github.com/ahmedkhaled",
        "linkedin_link": "https://linkedin.com/in/ahmedkhaled",
        "portfolio_link": "https://ahmedkhaled.portfolio.com",
        "motivation_letter": "I am passionate about software development...",
        "personal_info": "Additional personal information...",
        "personal_photo": "https://example.com/photos/ahmed.jpg",
        "created_at": "2025-03-17T10:00:00.000000Z",
        "updated_at": "2025-03-17T10:00:00.000000Z"
    }
}
```

**Response (Not Found):**
```json
{
    "message": "CV not found. Please create one.",
    "data": null
}
```

### Update CV
```http
PUT /my-cv
Authorization: Bearer {student_token}
Content-Type: application/json

{
    "first_name": "ahmed Updated",
    "last_name": "khaled",
    "age": 23,
    "full_address": "456 New Street, Algiers, Algeria",
    "phone_number": "+213 555 999 888",
    "academic_level": "4th Year Computer Science",
    "email": "ahmed.khaled@example.com",
    "university_email": "ahmed.khaled@univ-constantine2.com",
    "github_link": "https://github.com/ahmedkhaled",
    "linkedin_link": "https://linkedin.com/in/ahmedkhaled",
    "portfolio_link": "https://ahmedkhaled.portfolio.com",
    "motivation_letter": "Updated motivation letter...",
    "personal_info": "Updated personal information...",
    "personal_photo": "https://example.com/photos/ahmed_updated.jpg"
}
```

### Delete CV
```http
DELETE /my-cv
Authorization: Bearer {student_token}
```

---

## Company Profile Endpoints

### Create Company Profile
```http
POST /company-profile
Authorization: Bearer {recruiter_token}
Content-Type: application/json

{
    "name": "Tech Solutions Algeria",
    "description": "A leading tech company...",
    "wilaya": "Algiers",
    "address": "123 Tech Street, Hydra",
    "logo": "https://example.com/logo.png"
}
```

### View My Company Profile
```http
GET /company-profile
Authorization: Bearer {recruiter_token}
```

**Response (Success):**
```json
{
    "data": {
        "id": 1,
        "recruiter_id": 2,
        "name": "Tech Solutions Algeria",
        "description": "A leading tech company...",
        "wilaya": "Algiers",
        "address": "123 Tech Street, Hydra",
        "logo": "https://example.com/logo.png",
        "created_at": "2025-03-17T10:00:00.000000Z",
        "updated_at": "2025-03-17T10:00:00.000000Z",
        "recruiter": {
            "id": 2,
            "name": "recruiter name",
            "email": "recruiter@stageio.com"
        }
    }
}
```

**Response (Not Found):**
```json
{
    "message": "Company profile not found. Please create one.",
    "data": null
}
```

### Update Company Profile
```http
PUT /company-profile
Authorization: Bearer {recruiter_token}
Content-Type: application/json

{
    "name": "Updated Company Name",
    "description": "Updated description..."
}
```

### Delete Company Profile
```http
DELETE /company-profile
Authorization: Bearer {recruiter_token}
```

---

## Internship Offers (Public)

### List All Offers (Open by Default)
```http
GET /internship-offers
```

**Response:**
```json
{
    "data": [
        {
            "id": 1,
            "company_profile_id": 1,
            "title": "Full Stack Developer Intern",
            "description": "We are looking for a talented intern...",
            "wilaya": "Algiers",
            "start_date": "2025-04-01",
            "internship_type": "full_time",
            "duration": 12,
            "status": "open",
            "max_students": 3,
            "deadline": "2025-03-30",
            "created_at": "2025-03-17T10:00:00.000000Z",
            "updated_at": "2025-03-17T10:00:00.000000Z",
            "company_profile": {
                "id": 1,
                "name": "Tech Solutions Algeria",
                "wilaya": "Algiers"
            },
            "skills": [
                { "id": 1, "name": "React" },
                { "id": 2, "name": "Laravel" }
            ]
        }
    ],
    "meta": {
        "current_page": 1,
        "last_page": 1,
        "per_page": 10,
        "total": 1
    }
}
```

**Query Parameters:**
- `status=open` - Show only open offers (default)
- `status=closed` - Show only closed offers
- `status=all` - Show all offers

**Auto-Close Conditions:**
- Offer closes when `accepted_students >= max_students`
- Offer closes when `current_date > deadline`
- Offer can reopen if accepted application is cancelled (spots become available)

### Search Offers by Text
```http
GET /internship-offers-search?q=react developer
```

**Response:**
```json
{
    "search_term": "react developer",
    "results_count": 5,
    "data": [
        {
            "id": 1,
            "company_profile_id": 1,
            "title": "React Developer Intern",
            "description": "Looking for a React developer intern...",
            "wilaya": "Algiers",
            "start_date": "2025-04-01",
            "internship_type": "full_time",
            "duration": 12,
            "status": "open",
            "max_students": 3,
            "deadline": "2025-03-30",
            "company_profile": {
                "id": 1,
                "name": "Tech Solutions Algeria"
            },
            "skills": [
                { "id": 1, "name": "React" }
            ]
        }
    ],
    "meta": {
        "current_page": 1,
        "last_page": 1,
        "per_page": 10,
        "total": 5
    }
}
```

### Filter Offers (Dropdown)
```http
GET /internship-offers-filter?wilaya=Algiers&type=full_time&skill_id=1
```

**Response:**
```json
{
    "filters_applied": {
        "wilaya": "Algiers",
        "type": "full_time",
        "skill_id": 1,
        "company_id": null,
        "min_duration": null,
        "max_duration": null
    },
    "results_count": 3,
    "data": [
        {
            "id": 1,
            "company_profile_id": 1,
            "title": "Full Stack Developer Intern",
            "description": "We are looking for a talented intern...",
            "wilaya": "Algiers",
            "start_date": "2025-04-01",
            "internship_type": "full_time",
            "duration": 12,
            "status": "open",
            "max_students": 3,
            "deadline": "2025-03-30",
            "company_profile": {
                "id": 1,
                "name": "Tech Solutions Algeria"
            },
            "skills": [
                { "id": 1, "name": "React" }
            ]
        }
    ],
    "meta": {
        "current_page": 1,
        "last_page": 1,
        "per_page": 10,
        "total": 3
    }
}
```

### Get Filter Options (for Dropdowns)
```http
GET /internship-offers-filter-options
```

**Response:**
```json
{
    "wilayas": ["Algiers", "Oran", "Constantine"],
    "types": [
        { "value": "full_time", "label": "Full Time" },
        { "value": "part_time", "label": "Part Time" },
        { "value": "remote", "label": "Remote" }
    ],
    "skills": [
        { "id": 1, "name": "React" },
        { "id": 2, "name": "Laravel" },
        { "id": 3, "name": "MySQL" }
    ],
    "companies": [
        { "id": 1, "name": "Tech Solutions Algeria" },
        { "id": 2, "name": "Digital Agency Oran" }
    ]
}
```

### View Single Offer
```http
GET /internship-offers/{id}
```

**Response:**
```json
{
    "data": {
        "id": 1,
        "company_profile_id": 1,
        "title": "Full Stack Developer Intern",
        "description": "We are looking for a talented intern...",
        "wilaya": "Algiers",
        "start_date": "2025-04-01",
        "internship_type": "full_time",
        "duration": 12,
        "status": "open",
        "max_students": 3,
        "deadline": "2025-03-30",
        "created_at": "2025-03-17T10:00:00.000000Z",
        "updated_at": "2025-03-17T10:00:00.000000Z",
        "company_profile": {
            "id": 1,
            "name": "Tech Solutions Algeria",
            "description": "A leading tech company...",
            "wilaya": "Algiers",
            "address": "123 Tech Street, Hydra",
            "logo": "https://example.com/logo.png"
        },
        "skills": [
            { "id": 1, "name": "React" },
            { "id": 2, "name": "Laravel" },
            { "id": 3, "name": "MySQL" }
        ]
    },
    "accepted_students": 2,
    "available_spots": 1,
    "deadline_passed": false
}
```

### List All Skills
```http
GET /internship-offers/skills/list
```

**Response:**
```json
{
    "data": ["React", "Laravel", "MySQL", "Vue.js", "Node.js"]
}
```

---

## Internship Offers (Recruiter)

### Create Offer
```http
POST /internship-offers
Authorization: Bearer {recruiter_token}
Content-Type: application/json

{
    "title": "Full Stack Developer Intern",
    "description": "We are looking for a talented intern...",
    "wilaya": "Algiers",
    "start_date": "2025-04-01",
    "internship_type": "full_time",
    "duration": 12,
    "max_students": 3,
    "deadline": "2025-03-30",
    "skills": ["React", "Laravel", "MySQL"]
}
```

**Validation Rules:**
- `start_date` - Must be after today
- `deadline` - Must be after today AND before start_date
- `duration` - Duration in weeks (min: 1)

**New Fields:**
- `max_students` - Maximum number of students to accept (default: 1)
- `deadline` - Application deadline date (offer closes after this date)

**Offer Status:**
- `open` - Accepting applications (default)
- `closed` - Not accepting applications (auto-set when max_students reached or deadline passed)

### Update Offer
```http
PUT /internship-offers/{id}
Authorization: Bearer {recruiter_token}
Content-Type: application/json

{
    "title": "Updated Title",
    "description": "Updated description...",
    "start_date": "2025-05-01",
    "deadline": "2025-04-15"
}
```

**Note:** Same validation rules apply - start_date must be after today, deadline must be before start_date.

### Delete Offer
```http
DELETE /internship-offers/{id}
Authorization: Bearer {recruiter_token}
```

### View My Offers
```http
GET /my-internship-offers
Authorization: Bearer {recruiter_token}
```

**Response:**
```json
{
    "data": [
        {
            "id": 1,
            "company_profile_id": 1,
            "title": "Full Stack Developer Intern",
            "description": "We are looking for a talented intern...",
            "wilaya": "Algiers",
            "start_date": "2025-04-01",
            "internship_type": "full_time",
            "duration": 12,
            "status": "open",
            "max_students": 3,
            "deadline": "2025-03-30",
            "skills": [
                { "id": 1, "name": "React" },
                { "id": 2, "name": "Laravel" }
            ],
            "applications": [
                {
                    "id": 1,
                    "student_id": 1,
                    "status": "pending",
                    "application_date": "2025-03-17"
                }
            ]
        }
    ],
    "meta": {
        "current_page": 1,
        "last_page": 1,
        "per_page": 10,
        "total": 1
    }
}
```

---

## Applications (Student)

### Apply to Offer (One Click)
```http
POST /internship-offers/{offerId}/apply
Authorization: Bearer {student_token}
```

**Requirements:**
- Student must have a CV
- Offer must be open
- Cannot apply if student has an active (non-completed) internship
- Can apply again after internship is completed

**Error - Has Active Internship:**
```json
{
    "message": "You have an active internship. You can only apply after your current internship is completed."
}
```

**Error - Offer Closed:**
```json
{
    "message": "This internship offer is closed.",
    "reason": "Maximum number of students reached",
    "status": "closed"
}
```

### View My Applications
```http
GET /my-applications
Authorization: Bearer {student_token}
```

**Response:**
```json
{
    "data": [
        {
            "id": 1,
            "student_id": 1,
            "internship_offer_id": 1,
            "student_cv_id": 1,
            "application_date": "2025-03-17",
            "status": "pending",
            "accepted_at": null,
            "is_confirmed": false,
            "confirmed_at": null,
            "cover_letter": null,
            "created_at": "2025-03-17T10:00:00.000000Z",
            "updated_at": "2025-03-17T10:00:00.000000Z",
            "internship_offer": {
                "id": 1,
                "title": "Full Stack Developer Intern",
                "company_profile": {
                    "id": 1,
                    "name": "Tech Solutions Algeria"
                }
            },
            "student_cv": {
                "id": 1,
                "first_name": "ahmed",
                "last_name": "khaled",
                "email": "ahmed.khaled@example.com"
            }
        }
    ],
    "meta": {
        "current_page": 1,
        "last_page": 1,
        "per_page": 10,
        "total": 1
    }
}
```

### View Single Application
```http
GET /applications/{id}
Authorization: Bearer {student_token}
```

**Response:**
```json
{
    "data": {
        "id": 1,
        "student_id": 1,
        "internship_offer_id": 1,
        "student_cv_id": 1,
        "application_date": "2025-03-17",
        "status": "pending",
        "accepted_at": null,
        "is_confirmed": false,
        "confirmed_at": null,
        "cover_letter": null,
        "created_at": "2025-03-17T10:00:00.000000Z",
        "updated_at": "2025-03-17T10:00:00.000000Z",
        "internship_offer": {
            "id": 1,
            "title": "Full Stack Developer Intern",
            "description": "We are looking for a talented intern...",
            "wilaya": "Algiers",
            "company_profile": {
                "id": 1,
                "name": "Tech Solutions Algeria",
                "wilaya": "Algiers",
                "address": "123 Tech Street, Hydra"
            }
        },
        "student_cv": {
            "id": 1,
            "first_name": "ahmed",
            "last_name": "khaled",
            "email": "ahmed.khaled@example.com",
            "phone_number": "+213 555 123 456",
            "academic_level": "3rd Year Computer Science"
        }
    }
}
```

### Confirm Accepted Application
Student confirms one accepted application. All other accepted applications will be auto-cancelled.
```http
POST /applications/{id}/confirm
Authorization: Bearer {student_token}
```

**Requirements:**
- Application must be in `ACCEPTED` status
- Student can only confirm their own applications
- Once confirmed, other accepted applications are auto-cancelled
- Confirmed application becomes pending admin validation

**Success Response:**
```json
{
    "message": "Application confirmed successfully!",
    "data": { ... },
    "other_applications_cancelled": 2,
    "next_step": "Your application is now pending admin validation."
}
```

**Error - Not Accepted:**
```json
{
    "message": "Application must be accepted by the recruiter before confirmation.",
    "current_status": "pending"
}
```

### Cancel My Application
Student can cancel their application at any stage (before validation).
```http
DELETE /applications/{id}/cancel
Authorization: Bearer {student_token}
```

**Requirements:**
- Student can only cancel their own applications
- Cannot cancel if internship has already been created (validated by admin)

**Success Response:**
```json
{
    "message": "Application cancelled successfully!"
}
```

**Error - Internship Already Created:**
```json
{
    "message": "Cannot cancel application. Internship has already been created."
}
```

### Check Daily Application Limit
```http
GET /applications-daily-status
Authorization: Bearer {student_token}
```

**Response:**
```json
{
    "daily_limit": 10,
    "applied_today": 5,
    "remaining_today": 5,
    "reset_at": "2025-03-18 00:00:00",
    "can_apply": true,
    "has_active_internship": false,
    "active_internship": null,
    "has_completed_internship": true,
    "completed_internship": { ... }
}
```

---

## Applications (Recruiter)

### View Applications for My Offer
```http
GET /internship-offers/{offerId}/applications
Authorization: Bearer {recruiter_token}
```

**Response:**
```json
{
    "data": [
        {
            "id": 1,
            "student_id": 1,
            "internship_offer_id": 1,
            "student_cv_id": 1,
            "application_date": "2025-03-17",
            "status": "pending",
            "accepted_at": null,
            "is_confirmed": false,
            "confirmed_at": null,
            "cover_letter": null,
            "created_at": "2025-03-17T10:00:00.000000Z",
            "updated_at": "2025-03-17T10:00:00.000000Z",
            "student": {
                "id": 1,
                "name": "ahmed khaled",
                "email": "ahmed@example.com",
                "student_profile": {
                    "id": 1,
                    "first_name": "ahmed",
                    "last_name": "khaled",
                    "university_email": "ahmed@univ-constantine2.com"
                }
            },
            "student_cv": {
                "id": 1,
                "first_name": "ahmed",
                "last_name": "khaled",
                "email": "ahmed.khaled@example.com",
                "phone_number": "+213 555 123 456",
                "academic_level": "3rd Year Computer Science"
            }
        }
    ],
    "meta": {
        "current_page": 1,
        "last_page": 1,
        "per_page": 10,
        "total": 1
    }
}
```

### Accept Application
```http
POST /applications/{id}/accept
Authorization: Bearer {recruiter_token}
```

**Note:** Sends email notification to student.

### Refuse Application
```http
POST /applications/{id}/refuse
Authorization: Bearer {recruiter_token}
```

**Note:** Sends email notification to student.

---

## Admin Validation

### View Pending Validations (Same University Only)
```http
GET /admin/pending-validations
Authorization: Bearer {admin_token}
```

**Response:**
```json
{
    "university_domain": "univ-constantine2.com",
    "data": [
        {
            "id": 1,
            "student_id": 1,
            "internship_offer_id": 1,
            "student_cv_id": 1,
            "application_date": "2025-03-17",
            "status": "accepted",
            "accepted_at": "2025-03-17T11:00:00.000000Z",
            "is_confirmed": true,
            "confirmed_at": "2025-03-17T12:00:00.000000Z",
            "cover_letter": null,
            "created_at": "2025-03-17T10:00:00.000000Z",
            "updated_at": "2025-03-17T12:00:00.000000Z",
            "student": {
                "id": 1,
                "name": "ahmed khaled",
                "email": "ahmed@example.com",
                "student_profile": {
                    "id": 1,
                    "first_name": "ahmed",
                    "last_name": "khaled",
                    "university_email": "ahmed@univ-constantine2.com"
                }
            },
            "internship_offer": {
                "id": 1,
                "title": "Full Stack Developer Intern",
                "company_profile": {
                    "id": 1,
                    "name": "Tech Solutions Algeria"
                }
            }
        }
    ],
    "meta": {
        "current_page": 1,
        "last_page": 1,
        "per_page": 10,
        "total": 1
    }
}
```

### Validate Application (One Click)
```http
POST /admin/applications/{applicationId}/validate
Authorization: Bearer {admin_token}
```

**Note:** Creates internship with:
- `start_date` from offer
- `end_date` = start_date + duration weeks
- `status` = ongoing

### View All Internships (Same University Only)
```http
GET /admin/internships
Authorization: Bearer {admin_token}
```

**Response:**
```json
{
    "university_domain": "univ-constantine2.com",
    "data": [
        {
            "id": 1,
            "application_id": 1,
            "start_date": "2025-04-01",
            "end_date": "2025-06-24",
            "status": "ongoing",
            "created_at": "2025-03-17T10:00:00.000000Z",
            "updated_at": "2025-03-17T10:00:00.000000Z",
            "application": {
                "id": 1,
                "student_id": 1,
                "internship_offer_id": 1,
                "student": {
                    "id": 1,
                    "name": "ahmed khaled",
                    "email": "ahmed@example.com",
                    "student_profile": {
                        "id": 1,
                        "first_name": "ahmed",
                        "last_name": "khaled",
                        "university_email": "ahmed@univ-constantine2.com"
                    }
                },
                "internship_offer": {
                    "id": 1,
                    "title": "Full Stack Developer Intern",
                    "company_profile": {
                        "id": 1,
                        "name": "Tech Solutions Algeria"
                    }
                }
            },
            "agreement": {
                "id": 1,
                "internship_id": 1,
                "admin_id": 3,
                "generated_date": "2025-03-17",
                "signature_status": true,
                "pdf_file": "agreements/agreement_1.pdf"
            }
        }
    ],
    "meta": {
        "current_page": 1,
        "last_page": 1,
        "per_page": 10,
        "total": 1
    }
}
```

**Auto-Complete:** Internships automatically become `completed` when end_date passes.

### Complete Internship
```http
POST /admin/internships/{internshipId}/complete
Authorization: Bearer {admin_token}
```

### Reject Application (Admin)
Reject an application that was accepted by recruiter.
```http
POST /admin/applications/{applicationId}/reject
Authorization: Bearer {admin_token}
```

**Requirements:**
- Admin can only reject applications from students of the same university
- Application must be in `ACCEPTED` status
- Cannot reject if internship has already been created

---

## Admin Agreements

### View All Agreements (Same University Only)
```http
GET /admin/agreements
Authorization: Bearer {admin_token}
```

**Response:**
```json
{
    "university_domain": "univ-constantine2.com",
    "data": [
        {
            "id": 1,
            "internship_id": 1,
            "admin_id": 3,
            "generated_date": "2025-03-17",
            "signature_status": true,
            "pdf_file": "agreements/agreement_1.pdf",
            "created_at": "2025-03-17T10:00:00.000000Z",
            "updated_at": "2025-03-17T10:00:00.000000Z",
            "pdf_url": "http://127.0.0.1:8000/storage/agreements/agreement_1.pdf",
            "internship": {
                "id": 1,
                "application_id": 1,
                "start_date": "2025-04-01",
                "end_date": "2025-06-24",
                "status": "ongoing",
                "application": {
                    "id": 1,
                    "student_id": 1,
                    "internship_offer_id": 1,
                    "student": {
                        "id": 1,
                        "name": "ahmed khaled",
                        "email": "ahmed@example.com",
                        "student_profile": {
                            "id": 1,
                            "first_name": "ahmed",
                            "last_name": "khaled",
                            "university_email": "ahmed@univ-constantine2.com"
                        }
                    },
                    "internship_offer": {
                        "id": 1,
                        "title": "Full Stack Developer Intern",
                        "company_profile": {
                            "id": 1,
                            "name": "Tech Solutions Algeria"
                        }
                    }
                }
            },
            "admin": {
                "id": 3,
                "name": "admin name",
                "email": "admin@stageio.com"
            }
        }
    ],
    "meta": {
        "current_page": 1,
        "last_page": 1,
        "per_page": 10,
        "total": 1
    }
}
```

### Generate Agreement (One Click - Creates + Signs + PDF)
```http
POST /admin/internships/{internshipId}/generate-agreement
Authorization: Bearer {admin_token}
```

### Download Agreement PDF
```http
GET /admin/agreements/{agreementId}/download
Authorization: Bearer {admin_token}
```

**Response:** Returns the PDF file as a download (`application/pdf` content type).

### Regenerate PDF
```http
POST /admin/agreements/{agreementId}/regenerate-pdf
Authorization: Bearer {admin_token}
```

### Trigger Auto-Actions Manually
```http
POST /admin/auto-actions/trigger
Authorization: Bearer {admin_token}
```

**Response:**
```json
{
    "message": "Auto-actions completed successfully!",
    "results": {
        "pending_cancelled": 3,
        "unconfirmed_cancelled": 2,
        "confirmed_validated": 1,
        "internships_completed": 5,
        "timestamp": "2025-03-17 15:30:00"
    }
}
```

---

## Role-Based Dashboards

### Student Dashboard
```http
GET /student/dashboard
Authorization: Bearer {student_token}
```

**Response:**
```json
{
    "message": "Welcome to Student Dashboard"
}
```

### Recruiter Dashboard
```http
GET /recruiter/dashboard
Authorization: Bearer {recruiter_token}
```

**Response:**
```json
{
    "message": "Welcome to Recruiter Dashboard"
}
```

### Admin Dashboard
```http
GET /admin/dashboard
Authorization: Bearer {admin_token}
```

**Response:**
```json
{
    "message": "Welcome to Admin Dashboard"
}
```

---

## Auto-Actions Status (All Users)

Check expiry status for applications based on user role.

### Check My Expiry Status
```http
GET /auto-actions/status
Authorization: Bearer {token}
```

**For Student - Response:**
```json
{
    "timezone": "Africa/Algiers",
    "current_time": "2025-03-17 15:30:00",
    "pending_applications": [
        {
            "application_id": 1,
            "offer_title": "Developer Intern",
            "days_waiting": 5,
            "days_until_auto_cancel": 9
        }
    ],
    "accepted_applications": [
        {
            "application_id": 2,
            "offer_title": "Designer Intern",
            "days_waiting": 3,
            "days_until_auto_cancel": 11
        }
    ],
    "confirmed_applications": [
        {
            "application_id": 3,
            "offer_title": "Manager Intern",
            "days_waiting": 2,
            "days_until_auto_validate": 5
        }
    ],
    "rules": {
        "recruiter_response_days": 14,
        "student_confirm_days": 14,
        "admin_validate_days": 7
    }
}
```

**For Recruiter - Response:**
```json
{
    "timezone": "Africa/Algiers",
    "current_time": "2025-03-17 15:30:00",
    "pending_applications": [
        {
            "application_id": 1,
            "student_name": "ahmed khaled",
            "offer_title": "Developer Intern",
            "days_waiting": 12,
            "days_until_auto_cancel": 2
        }
    ],
    "rules": {
        "recruiter_response_days": 14
    }
}
```

**For Admin - Response:**
```json
{
    "timezone": "Africa/Algiers",
    "current_time": "2025-03-17 15:30:00",
    "confirmed_applications": [
        {
            "application_id": 1,
            "student_name": "ahmed khaled",
            "offer_title": "Developer Intern",
            "days_waiting": 6,
            "days_until_auto_validate": 1
        }
    ],
    "rules": {
        "admin_validate_days": 7
    }
}
```

---

## Query Parameters Reference

### Internship Offers List
| Parameter | Type | Description |
|-----------|------|-------------|
| wilaya | string | Filter by location |
| type | string | full_time, part_time, remote |
| skill | string | Filter by skill name |
| search | string | Search in title/description |
| per_page | integer | Items per page (default: 10) |

### Internship Offers Filter
| Parameter | Type | Description |
|-----------|------|-------------|
| wilaya | string | Exact match |
| type | enum | full_time, part_time, remote |
| skill_id | integer | Skill ID |
| company_id | integer | Company ID |
| min_duration | integer | Minimum weeks |
| max_duration | integer | Maximum weeks |
| per_page | integer | Items per page |

---

## Response Status Codes

| Code | Meaning |
|------|---------|
| 200 | Success |
| 201 | Created |
| 400 | Bad Request |
| 401 | Unauthorized |
| 403 | Forbidden |
| 404 | Not Found |
| 422 | Validation Error |
| 429 | Too Many Requests (Rate Limit) |
| 500 | Server Error |

---

## University-Based Access Control

Admins can only access data for students from the same university based on email domain.

**Example:**
- Admin: `admin@univ-constantine2.com`
- Can access: Students with `@univ-constantine2.com` emails
- Cannot access: Students with `@univ-alger.dz`, `@univ-oran.dz` emails

---

## Auto-Actions System

The system automatically performs these actions:

| Action | Trigger | Timeframe |
|--------|---------|-----------|
| Cancel pending applications | Recruiter no response | 14 days |
| Cancel accepted applications | Student not confirmed | 14 days |
| Validate confirmed applications | Admin not validated | 7 days |
| Complete internships | End date passed | Immediate |

**How it works:**
- Runs on every API request via middleware
- Also available via command: `php artisan app:auto-actions`
- Can be scheduled in cron for background processing

---

## Test Credentials

### Student
- Email: `student@example.com`
- Password: `password123`

### Recruiter
- Email: `recruiter@stageio.com`
- Password: `recruiter123`

### Admin (Super Admin)
- Email: `admin@stageio.com`
- Password: `admin123`

---

## Notes

1. **Student Apply**: No body required - automatically uses student's CV
2. **Admin Validate**: No body required - auto-calculates dates from offer
3. **Generate Agreement**: One button creates agreement, marks signed, and generates PDF
4. **File Storage**: PDFs stored in `storage/app/public/agreements/`
5. **Access Public Files**: Via `http://127.0.0.1:8000/storage/{path}`
6. **Internship Auto-Complete**: Internships automatically become `completed` when end_date passes
7. **Apply After Completion**: Students can apply for new internships after their current one is completed
