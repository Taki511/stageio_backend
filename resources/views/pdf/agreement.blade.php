<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Internship Agreement</title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12pt;
            line-height: 1.6;
            color: #333;
            margin: 40px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #2c5282;
            padding-bottom: 20px;
        }
        .header h1 {
            color: #2c5282;
            font-size: 24pt;
            margin: 0;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .section {
            margin: 20px 0;
        }
        .section-title {
            background-color: #2c5282;
            color: white;
            padding: 8px 15px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .info-row {
            margin: 8px 0;
            padding: 5px 0;
            border-bottom: 1px dotted #ccc;
        }
        .info-label {
            font-weight: bold;
            color: #2c5282;
            display: inline-block;
            width: 200px;
        }
        .info-value {
            display: inline;
        }
        .content-box {
            border: 1px solid #ccc;
            padding: 15px;
            margin: 15px 0;
            background-color: #f9f9f9;
        }
        .signatures {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
        }
        .signature-box {
            width: 45%;
            text-align: center;
        }
        .signature-line {
            border-top: 1px solid #333;
            margin-top: 60px;
            padding-top: 10px;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 10pt;
            color: #666;
            border-top: 1px solid #ccc;
            padding-top: 20px;
        }
        .stamp-area {
            border: 2px dashed #999;
            width: 150px;
            height: 150px;
            margin: 20px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            font-size: 10pt;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>INTERNSHIP AGREEMENT</h1>
        <p><strong>Agreement No:</strong> {{ $agreement_number }}</p>
        <p><strong>Generated Date:</strong> {{ $generated_date }}</p>
    </div>

    <div class="section">
        <div class="section-title">1. STUDENT INFORMATION</div>
        <div class="info-row">
            <span class="info-label">Full Name:</span>
            <span class="info-value">{{ $student_name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Email:</span>
            <span class="info-value">{{ $student_email }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">University Email:</span>
            <span class="info-value">{{ $student_university_email }}</span>
        </div>
    </div>

    <div class="section">
        <div class="section-title">2. COMPANY INFORMATION</div>
        <div class="info-row">
            <span class="info-label">Company Name:</span>
            <span class="info-value">{{ $company_name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Address:</span>
            <span class="info-value">{{ $company_address }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Wilaya/State:</span>
            <span class="info-value">{{ $company_wilaya }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Description:</span>
            <span class="info-value">{{ $company_description }}</span>
        </div>
    </div>

    <div class="section">
        <div class="section-title">3. COMPANY REPRESENTATIVE INFORMATION</div>
        <div class="info-row">
            <span class="info-label">Full Name:</span>
            <span class="info-value">{{ $recruiter_name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Email:</span>
            <span class="info-value">{{ $recruiter_email }}</span>
        </div>
    </div>

    <div class="section">
        <div class="section-title">4. INTERNSHIP DETAILS</div>
        <div class="info-row">
            <span class="info-label">Position Title:</span>
            <span class="info-value">{{ $offer_title }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Internship Type:</span>
            <span class="info-value">{{ $internship_type }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Duration:</span>
            <span class="info-value">{{ $duration_weeks }} weeks</span>
        </div>
        <div class="info-row">
            <span class="info-label">Start Date:</span>
            <span class="info-value">{{ $start_date }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">End Date:</span>
            <span class="info-value">{{ $end_date }}</span>
        </div>
        <div class="content-box">
            <strong>Internship Description:</strong><br>
            {{ $offer_description }}
        </div>
    </div>

    <div class="section">
        <div class="section-title">5. ADMINISTRATIVE VALIDATION</div>
        <div class="info-row">
            <span class="info-label">Validated by:</span>
            <span class="info-value">{{ $admin_name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Administrator Email:</span>
            <span class="info-value">{{ $admin_university_email }}</span>
        </div>
        <div class="stamp-area">
            University Stamp
        </div>
    </div>

    <div class="section">
        <div class="section-title">6. SIGNATURES</div>
        <p>The undersigned parties have read and approved the terms of this internship agreement.</p>
        
        <div class="signatures">
            <div class="signature-box">
                <p><strong>For the Student</strong></p>
                <div class="signature-line">
                    {{ $student_name }}<br>
                    Date: _______________
                </div>
            </div>
            <div class="signature-box">
                <p><strong>For the Company</strong></p>
                <div class="signature-line">
                    {{ $recruiter_name }}<br>
                    Date: _______________
                </div>
            </div>
        </div>
        
        <div style="margin-top: 40px; text-align: center;">
            <p><strong>Pour l'université</strong></p>
            <div class="signature-line" style="width: 45%; margin: 0 auto;">
                {{ $admin_name }}<br>
                Date: _______________
            </div>
        </div>
    </div>

    <div class="footer">
        <p>This agreement was generated electronically by the Stage.io system</p>
        <p>Confidential Document - To be kept by all parties</p>
    </div>
</body>
</html>
