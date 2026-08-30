<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>School Online System — Project Proposal</title>
    <style>
        @page { margin: 42px 46px; }
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #111827; line-height: 1.55; }
        .cover { border-bottom: 4px solid #FF9030; padding-bottom: 22px; margin-bottom: 26px; }
        .cover .brand { font-size: 15px; font-weight: 800; letter-spacing: 0.04em; }
        .cover .brand span { color: #FF9030; }
        .cover h1 { font-size: 30px; font-weight: 800; margin: 18px 0 6px; letter-spacing: -0.02em; }
        .cover .sub { color: #6b7280; font-size: 13px; }
        .cover table { margin-top: 18px; }
        .cover td { padding: 2px 12px 2px 0; font-size: 11px; }
        .cover td b { color: #374151; }
        h2 { font-size: 16px; color: #111827; border-bottom: 2px solid #FF9030; padding-bottom: 5px; margin: 26px 0 12px; }
        h3 { font-size: 13px; margin: 14px 0 6px; color: #b45309; }
        p { margin: 0 0 10px; }
        ul { margin: 0 0 12px 18px; padding: 0; }
        li { margin-bottom: 5px; }
        .badge { display: inline-block; background: #fff3e6; color: #e6761a; font-size: 10px; font-weight: 700; padding: 3px 10px; border-radius: 999px; }
        table.data { width: 100%; border-collapse: collapse; margin: 10px 0 16px; }
        table.data th, table.data td { border: 1px solid #d1d5db; padding: 6px 8px; text-align: left; vertical-align: top; }
        table.data th { background: #f3f4f6; font-size: 10px; text-transform: uppercase; letter-spacing: 0.03em; }
        .highlight { background: #fff3e6; border-left: 3px solid #FF9030; padding: 10px 12px; margin: 12px 0; }
        .feature { margin-bottom: 8px; }
        .feature b { color: #b45309; }
        .page-break { page-break-before: always; }
        .foot { margin-top: 30px; border-top: 1px solid #d1d5db; padding-top: 10px; color: #9ca3af; font-size: 9.5px; }
    </style>
</head>
<body>

<div class="cover">
    <div class="brand">School Online <span>System</span> (OAS)</div>
    <h1>Project Proposal</h1>
    <div class="sub">A modern online application &amp; admission management system for secondary schools</div>
    <table>
        <tr><td><b>Prepared for:</b></td><td>Prospective Client / School Management</td></tr>
        <tr><td><b>Prepared by:</b></td><td>School Online System Team</td></tr>
        <tr><td><b>Version:</b></td><td>1.0</td></tr>
        <tr><td><b>Date:</b></td><td>August 2026</td></tr>
    </table>
</div>

<h2>1. Executive Summary</h2>
<p>
    The <b>School Online System (OAS)</b> is a complete, secure web platform that digitalises the entire
    secondary-school admission journey — from online application to selection results. Built for schools
    offering <b>Form 1 to Form 6</b>, the system lets students and parents apply online, verifies results
    against NECTA, and gives the school powerful tools to review, decide and publish selections — all in
    one place.
</p>
<p>
    This proposal outlines the problem the system solves, the solution and its features, the technology
    behind it, the security measures in place, and a clear delivery plan.
</p>

<h2>2. Background &amp; Problem</h2>
<p>Schools and families today face a fragmented, paper-heavy admission process that:</p>
<ul>
    <li>Requires long queues and repeated visits to collect and submit forms.</li>
    <li>Makes it hard to verify applicants' examination results reliably.</li>
    <li>Slows the admission team's review and decision-making.</li>
    <li>Leaves applicants uncertain about the status of their application.</li>
    <li>Creates confusion around application windows and selection dates.</li>
</ul>

<h2>3. Objectives</h2>
<ul>
    <li>Provide a <b>single online application point</b> for all classes (Form 1 – Form 6).</li>
    <li>Automate <b>result verification</b> against NECTA's published results.</li>
    <li>Give admission officers <b>clear dashboards</b> to review, verify and decide applications.</li>
    <li>Let administrators control <b>application windows</b> and <b>publish selections</b> at once.</li>
    <li>Offer applicants <b>transparent status tracking</b> and downloadable forms.</li>
    <li>Keep all data <b>secure, private and backed up</b>.</li>
</ul>

<h2>4. Proposed Solution &amp; Key Features</h2>
<p>
    OAS is delivered as a responsive web application (works on phones, tablets and computers) with two
    main experiences: the <b>public portal</b> for applicants and the <b>staff back-office</b> for the school.
</p>

<h3>For Students &amp; Parents</h3>
<div class="feature"><span class="badge">Apply</span> &nbsp;<b>Online application wizard</b> — a guided, five-step form covering entry level, student details, academic details, guardian details and review.</div>
<div class="feature"><span class="badge">NECTA</span> &nbsp;<b>Automatic result lookup</b> — applicants enter their index number and the system fetches and confirms their published NECTA result (PSLE, FTNA, CSEE).</div>
<div class="feature"><span class="badge">Track</span> &nbsp;<b>Application tracking</b> — a reference number lets applicants follow progress from submission to selection.</div>
<div class="feature"><span class="badge">Download</span> &nbsp;<b>Application form PDF</b> — a printable application form is generated automatically.</div>
<div class="feature"><span class="badge">Account</span> &nbsp;<b>Personal dashboard &amp; profile</b> — review submissions and manage email/password.</div>

<h3>For the School (Admin &amp; Admission Officers)</h3>
<div class="feature"><span class="badge">Review</span> &nbsp;<b>Applications dashboard</b> — view full details (including the fetched NECTA result) before deciding.</div>
<div class="feature"><span class="badge">Roles</span> &nbsp;<b>Admin &amp; Admission Officer roles</b> — officers review and verify; only admins manage windows and publish selections.</div>
<div class="feature"><span class="badge">Workflow</span> &nbsp;<b>Draft-then-publish selections</b> — decisions are recorded in draft, then published to all applicants at once by the admin.</div>
<div class="feature"><span class="badge">Windows</span> &nbsp;<b>Application window management</b> — admins open/close intake windows with dates; the landing page reflects them.</div>
<div class="feature"><span class="badge">Export</span> &nbsp;<b>Bulk export</b> — export applications to Excel or PDF, filtered by status.</div>
<div class="feature"><span class="badge">Content</span> &nbsp;<b>Manage school content</b> — A-Level combinations, available classes, results links, contact info, gallery and the home feature cards.</div>
<div class="feature"><span class="badge">Team</span> &nbsp;<b>Manage admission officers</b> — create, reset passwords and remove officer accounts.</div>

<h2 class="page-break">5. Technical Architecture</h2>
<table class="data">
    <tr><th>Layer</th><th>Technology</th><th>Why</th></tr>
    <tr><td>Frontend</td><td>React (Create React App), responsive CSS</td><td>Fast, modern, mobile-first user interface</td></tr>
    <tr><td>Backend</td><td>Laravel (PHP 8.4) REST API</td><td>Secure, robust, battle-tested framework</td></tr>
    <tr><td>Database</td><td>SQLite (dev) / MySQL (production)</td><td>Portable development, reliable production</td></tr>
    <tr><td>Authentication</td><td>Laravel Sanctum (token-based)</td><td>Secure API access with scoped roles</td></tr>
    <tr><td>Background jobs</td><td>Laravel Queues</td><td>Slow tasks (e.g. NECTA verification) run off the request path</td></tr>
    <tr><td>Caching</td><td>Laravel Cache (24h)</td><td>Repeat result lookups don't hammer NECTA</td></tr>
    <tr><td>Documents</td><td>domPDF / OOXML</td><td>Application-form PDFs and Excel exports</td></tr>
</table>

<h2>6. Security &amp; Compliance</h2>
<ul>
    <li><b>Authentication &amp; authorisation</b> — every API route is guarded; role-based access for applicants, admission officers and admins.</li>
    <li><b>Password security</b> — bcrypt hashing with strong-password enforcement.</li>
    <li><b>Data protection</b> — validated inputs, mass-assignment protection, and no secret leakage.</li>
    <li><b>Rate limiting</b> — login and API endpoints are throttled to prevent abuse.</li>
    <li><b>Security headers</b> — anti-clickjacking, MIME sniffing protection, strict referrer policy, HTTPS enforcement and HSTS in production.</li>
    <li><b>Ownership checks</b> — users can only access their own data; school management is admin-only.</li>
</ul>

<h2>7. Benefits to the School</h2>
<ul>
    <li><b>Time savings</b> — no more manual form collection or duplicate data entry.</li>
    <li><b>Faster, fairer decisions</b> — complete applicant profiles, including verified NECTA results, at the officer's fingertips.</li>
    <li><b>Professional image</b> — a modern, branded admissions portal that reflects the school's standards.</li>
    <li><b>Fewer errors</b> — automatic validation and result verification reduce mistakes.</li>
    <li><b>Better communication</b> — applicants always know where their application stands.</li>
    <li><b>Full control</b> — the school owns and controls its data and content.</li>
</ul>

<h2>8. Project Delivery Plan</h2>
<table class="data">
    <tr><th>Phase</th><th>Activity</th><th>Indicative Duration</th></tr>
    <tr><td>1. Discovery</td><td>Requirements confirmation, branding and school configuration</td><td>1 week</td></tr>
    <tr><td>2. Setup</td><td>Deployment environment, domains, database and security certificates</td><td>1 week</td></tr>
    <tr><td>3. Configuration</td><td>School details, classes, combinations, results links, gallery and home content</td><td>1 week</td></tr>
    <tr><td>4. Training</td><td>Hands-on training for admins and admission officers</td><td>2–3 days</td></tr>
    <tr><td>5. Go-live</td><td>Open the application window and publish to applicants</td><td>Launch day</td></tr>
</table>

<div class="highlight">
    <b>Note:</b> timings assume data and approvals are provided promptly by the school. A custom timeline is agreed during Phase 1.
</div>

<h2>9. Training &amp; Support</h2>
<ul>
    <li>Administrator and officer training (on-site or remote).</li>
    <li>User guides and quick-reference documentation.</li>
    <li>Priority support during the first application cycle.</li>
    <li>Ongoing maintenance and updates are available under a support plan.</li>
</ul>

<h2>10. Contact</h2>
<p>
    For questions or to request a live demonstration, please contact the School Online System team.
    We look forward to partnering with you to bring your admissions online.
</p>

<div class="foot">School Online System (OAS) — Project Proposal · August 2026 · Confidential</div>

</body>
</html>
