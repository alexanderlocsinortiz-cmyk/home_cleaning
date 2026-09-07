# CleanFlow: A Web- and Mobile-Based Home Cleaning Service Management System for Valencia City

## Working Manuscript Draft

> Replace all bracketed fields before submission. Chapter 4 must contain the actual results of the researchers' testing and user evaluation. Do not invent respondent counts, ratings, or statistical results.

---

# CHAPTER 1

## THE PROBLEM AND ITS BACKGROUND

### Introduction

Home cleaning services are commonly coordinated through phone calls, text messages, social media conversations, or manually maintained schedules. Although these channels are accessible, they make it difficult to maintain a single and reliable record of customer information, service requirements, schedules, cleaner assignments, payment status, and service completion. As the number of customers and cleaners increases, manual coordination can lead to double bookings, delayed confirmations, incomplete payment records, unclear responsibilities, and limited visibility into the actual progress of a service.

CleanFlow was developed as a web- and mobile-based home cleaning service management system for customers and cleaning-service personnel in Valencia City. The system provides a public service catalog, client registration, online booking, price calculation, schedule validation, cleaner assignment, payment tracking, service-status updates, before-and-after service proof, customer feedback, reports, and administrative monitoring. It also provides a mobile API for client and staff workflows and an attendance integration for supported fingerprint devices.

The central idea of CleanFlow is to connect the entire service transaction in one record. A booking begins as a customer request, passes through validation and administrative review, proceeds to cleaner assignment and service execution, and ends with completion proof, payment follow-up, rating, dispute handling, and operational reporting. This reduces dependence on disconnected records and gives each authorized user an appropriate view of the booking lifecycle.

### Background of the Study

The cleaning-service process has several coordination points. A customer must select a service, provide the property and location details, choose a schedule, and receive a price estimate. The business must then determine whether the schedule has capacity, whether the request requires manual review, and which cleaner or provider can perform the work. During the service, the business needs to know whether the assigned cleaner has started, whether the service has been completed, and whether supporting evidence exists. Finally, payment, customer feedback, disputes, and cleaner compensation must be recorded.

When these activities are managed separately, the business may have difficulty answering basic operational questions: Which bookings are still pending? Which cleaner is assigned? Is a requested time slot already full? Has the customer paid? Was the service completed with evidence? Is a provider eligible for payout? CleanFlow addresses these questions through role-based portals and a shared booking database.

The system is designed for a local operating context. It includes barangay-based service-area information, Philippine peso pricing, GCash, Maya, and cash payment options, local time-zone handling, and staff availability and attendance information. The system also recognizes that not every request can be automatically accepted. Suspicious or unusually large bookings may be placed in manual review before confirmation.

CleanFlow uses an area-based pricing model. The client enters the total cleanable floor area of the property, including the cleanable areas on all floors. The main service price is calculated from the total square meters multiplied by the selected service rate. The number of rooms and bathrooms or CRs, together with the property type, is collected as supporting property information for understanding the service scope, planning staffing, and reviewing whether the requested area is reasonable. Under the current pricing policy, rooms and bathrooms are not separate charges. Optional add-ons may create additional charges.

### Statement of the Problem

The study aims to develop and evaluate CleanFlow, a web- and mobile-based home cleaning service management system for Valencia City.

Specifically, it seeks to answer the following questions:

1. How can the system provide customers with a convenient method for viewing services, calculating prices, and submitting cleaning-service requests?
2. How can the system prevent or reduce duplicate customer bookings, cleaner schedule conflicts, and bookings that exceed available slot capacity?
3. How can the system support administrators in reviewing, assigning, confirming, monitoring, and closing bookings?
4. How can the system document service execution through before-service and after-service photos and optional completion video?
5. How can the system support payment tracking, cash-payment proof review, digital-payment verification, and provider payout monitoring?
6. How can the system provide mobile access for clients and staff while maintaining role-based access and authenticated communication with the backend?
7. How acceptable is the system to its intended users in terms of functional suitability, usability, reliability, performance efficiency, security, and maintainability?

### Objectives of the Study

#### General Objective

To develop and evaluate CleanFlow, a web- and mobile-based home cleaning service management system that centralizes booking, scheduling, assignment, service execution, payment, and follow-up operations for Valencia City.

#### Specific Objectives

The study aims to:

1. Develop a public service catalog and client portal for registration, profile management, price estimation, and booking submission.
2. Implement booking validation for service availability, schedule conflicts, staffing capacity, service-area limits, and manual-review conditions.
3. Provide an administrative portal for customer, service, staff, booking, payment, attendance, report, and provider management.
4. Provide staff and provider portals for assignment response, schedule viewing, service-status updates, proof uploads, and performance monitoring.
5. Implement before-service and after-service proof storage linked to the booking record.
6. Implement payment records for cash, GCash, and Maya, including PayMongo integration for supported web digital-payment transactions.
7. Provide a mobile application interface connected to the Laravel backend through authenticated mobile API endpoints.
8. Provide booking history, notifications, messages, location tracking, optional live video, ratings, and dispute workflows.
9. Evaluate the system using functional testing and an appropriate user-evaluation instrument based on recognized software-quality characteristics.

### Significance of the Study

#### Customers

Customers may use the system to view service options, receive a transparent price breakdown, request a schedule, monitor booking progress, view service proof, track payment status, provide ratings, and submit disputes when necessary.

#### Cleaning-Service Administrators

Administrators may use the system to manage the service catalog, review booking risks, assign cleaners or providers, monitor active work, verify payments, resolve disputes, review attendance, and generate operational reports.

#### Cleaners and Service Providers

Cleaners and providers may use their respective portals to view assignments, manage schedules, respond to assignments when applicable, upload service evidence, update progress, review performance, and monitor payout information.

#### Future Researchers and Developers

The project may serve as a reference for future systems involving local service marketplaces, appointment scheduling, evidence-based service completion, role-based portals, and mobile-to-web backend integration.

#### Local Service Businesses

Small service businesses may use the study as a model for how a digital platform can centralize customer requests and operational records without requiring separate systems for every stage of the service process.

### Scope and Delimitations

The study covers the design and implementation of CleanFlow for home and selected commercial cleaning services within the configured service areas of Valencia City and its supported coverage locations. The system includes:

- public service and service-area information;
- client registration, email verification, login, profile completion, and password reset;
- service catalog and area-based price calculation based primarily on service type and total cleanable floor area, with property type, room count, bathroom/CR count, and add-ons captured as supporting booking information;
- one-time and web-supported subscription booking workflows;
- booking conflict, capacity, risk, and manual-review checks;
- administrative booking review, cleaner assignment, provider assignment, payment updates, payout tracking, and reporting;
- staff booking management, proof uploads, schedules, notifications, performance records, and location updates;
- provider application, activation, assignment response, service execution, and payout-related records;
- customer ratings, booking messages, disputes, and service-proof access;
- mobile API access for clients and staff;
- PayMongo checkout and webhook handling where configured;
- attendance-device enrollment, signed device requests, and attendance records where the hardware is available.

The study does not claim to provide automatic cleaner dispatch, guaranteed real-time GPS tracking, a full payroll system, inventory management, tax computation, or a replacement for human inspection of unusual cleaning requests. Internet connectivity, external payment services, email delivery, object storage, routing services, and attendance hardware are external dependencies. The mobile workflow is not assumed to have complete feature parity with the web workflow; its implemented booking flow is more limited than the web booking form.

The effectiveness and acceptance of the system will depend on the actual evaluation conducted by the researchers. This manuscript does not claim a measured improvement in business revenue, response time, customer retention, or service quality unless such measurements are collected and reported.

### Definition of Terms

**Booking.** A customer request for a cleaning service at a specified location, date, and time.

**Client.** A customer who requests and receives a cleaning service.

**Cleaner or Staff.** An authorized worker assigned to perform a booking.

**Marketplace Provider.** An approved and activated external cleaning provider that may receive and respond to assignments.

**Manual Review.** An administrative review required before a booking may proceed because of detected risk, unusual size, service-scope limits, or staffing concerns.

**Service Proof.** Before-service or after-service photos and optional completion video stored with a booking.

**Booking Status.** The operational state of a booking. The principal states are pending, confirmed, in progress, completed, and cancelled.

**Payment Status.** The state of the payment record, primarily pending or paid.

**Provider Payout.** The amount and payment status associated with compensation due to an approved marketplace provider.

**Mobile API.** Authenticated application programming interfaces used by the mobile application to communicate with the Laravel backend.

---

# CHAPTER 2

## REVIEW OF RELATED LITERATURE AND SYSTEMS

### Digitalization of Service Operations

Service businesses depend on the coordination of customers, workers, schedules, locations, prices, and service results. A digital service-management system can make these relationships visible by storing operational information in a common database and exposing only the functions appropriate to each user role. For a cleaning business, this is important because the service is performed at a customer location and cannot be fully represented by a simple product order. The system must capture the requested scope, schedule, assigned worker, execution status, payment, and evidence of completion.

CleanFlow applies this principle by treating the booking as the central operational record. Service information, customer information, scheduling data, payment records, notifications, activity logs, proof files, locations, messages, ratings, disputes, and payout information are linked to the booking. This structure supports traceability from request to completion.

### Online Booking and Scheduling Systems

Online booking systems commonly provide a customer-facing form for selecting a service and time. A basic form, however, is not enough for a cleaning operation. The system must verify that the requested schedule is available for the customer and that enough workers can support the request. It must also prevent concurrent requests from creating the same time slot or assigning the same cleaner to overlapping jobs.

CleanFlow addresses these issues through validation, capacity checking, schedule conflict checking, and transaction-level protection. The system also applies a rest period between cleaner assignments. These rules are operational controls rather than merely user-interface features; they must be enforced by the backend because a customer or administrator could bypass browser-side controls.

### Workflow and Role-Based Access Control

Role-based access control separates system privileges according to the user's responsibilities. In CleanFlow, clients manage their own bookings, staff view assigned work, providers access only their assignments, and administrators manage operational records. This separation reduces accidental changes and protects customer and payment information.

The workflow also uses controlled status transitions. A booking normally moves from pending to confirmed, from confirmed to in progress, and from in progress to completed. A booking may be cancelled from an earlier operational stage, while completed and cancelled bookings are not freely reopened. Restricting transitions creates a more reliable audit trail than allowing every user to edit the status arbitrarily.

### Evidence-Based Service Completion

Cleaning services are delivered in a physical environment, so customers and administrators may need evidence that the service was started and completed. CleanFlow requires before-service proof before a staff member starts a booking and after-service proof before the booking is completed. It can also store a completion video. The proof records are associated with the uploader, stage, media type, file path, and booking activity.

This approach does not prove every aspect of service quality by itself. Photos may be incomplete, poorly captured, or unable to show areas that are inaccessible. Therefore, proof should be treated as an accountability and review mechanism, not as a substitute for a service scope, checklist, customer acceptance process, or human dispute resolution.

### Digital Payment and Payment Verification

Digital service platforms often separate the booking record from the payment record so that payment events can be tracked independently from service status. CleanFlow supports cash and digital payment methods. For supported web transactions, PayMongo checkout-session creation returns a payment URL to the client, while the PayMongo webhook updates the payment record after a successful provider event. Cash bookings can receive a client-uploaded receipt that remains pending until reviewed by an administrator.

Separating payment status from booking status is important. A booking may be confirmed while its payment remains pending, or a completed cash service may still require receipt verification. This distinction prevents the operational state of the service from being confused with the financial state of the transaction.

### Mobile Applications and Backend APIs

Mobile applications can give clients and staff access to operational functions while they are away from a desktop computer. The CleanFlow mobile application communicates with the Laravel backend through bearer-token authentication. The client workflow includes service browsing, booking, booking history, cancellation, rescheduling, ratings, and disputes. The staff workflow includes assigned bookings, performance, notifications, starting jobs with before proof, and completing jobs with after proof.

Mobile access also introduces security and consistency requirements. Tokens must be protected, expired tokens must be rejected, and the backend must repeat authorization checks rather than trusting the mobile interface. The backend remains the source of truth for booking status, ownership, proof requirements, and schedule rules.

### Technology Acceptance Model

Davis's Technology Acceptance Model explains that perceived usefulness and perceived ease of use are important determinants of user acceptance of information technology. The model is relevant to CleanFlow because the intended users must believe that the system improves booking and service coordination and that the system is not unnecessarily difficult to use. A system may be technically complete but still fail operationally if clients, staff, or administrators avoid using it.

For this study, perceived usefulness may be reflected in whether users believe the system makes booking, assignment, monitoring, payment tracking, or reporting more efficient. Perceived ease of use may be reflected in whether users can complete their tasks with minimal confusion, training, or unnecessary steps.

### ISO/IEC 25010 Software Quality Model

ISO/IEC 25010 defines a product-quality model applicable to software and information-and-communication-technology products. The model provides a structured basis for evaluating software quality rather than relying only on whether a feature exists. For this study, the evaluation may focus on functional suitability, usability, reliability, performance efficiency, security, compatibility, maintainability, and portability, depending on the requirements of the institution and the available respondents.

CleanFlow's role separation, validation rules, rate limits, audit logs, transaction processing, mobile API, proof storage, and deployment configuration can be evaluated against these quality characteristics. The evaluation must still be conducted with actual test cases and users; the presence of a feature is not proof that users find it usable or that it performs adequately under production load.

### Related Systems

Traditional manual coordination typically uses calls, messages, spreadsheets, or paper records. These methods may be inexpensive to start but usually separate customer requests from assignments, payments, and service evidence. A generic online booking form improves intake but may still lack conflict protection, role-based operations, proof management, and post-service workflows. A marketplace platform may provide provider discovery but may not be designed for the specific cleaning business's service scope, local coverage, payment review, and audit requirements.

CleanFlow combines these functions in a single operational workflow. Its distinguishing design feature is not merely that it accepts online bookings; it connects booking intake, schedule protection, administrative review, assignment, proof, payment, follow-up, and reporting. This is a system capability, not evidence that the business is cheaper, faster, or better than competitors. Those claims would require separate market and performance studies.

### Synthesis of the Reviewed Literature and Systems

The reviewed concepts support five design requirements for CleanFlow. First, service booking must capture enough information for pricing and staffing. Second, schedule and capacity rules must be enforced by the backend. Third, role-based access and controlled transitions must protect the workflow. Fourth, physical service completion benefits from linked proof and audit records. Fifth, acceptance must be evaluated from both software-quality and user-acceptance perspectives.

The gap addressed by the study is the lack of a unified, locally configured cleaning-service workflow that covers both customer-facing booking and internal service operations. CleanFlow attempts to address this gap through a web portal, mobile API, administrative controls, staff/provider workflows, payment records, and service evidence.

### Theoretical Framework

The study uses the Technology Acceptance Model as a user-acceptance lens and the ISO/IEC 25010 software-quality model as a system-evaluation lens. The Technology Acceptance Model helps explain whether intended users see CleanFlow as useful and easy to use. ISO/IEC 25010 helps organize the evaluation of whether CleanFlow functions correctly, protects data, performs its tasks, and can be maintained.

### Conceptual Framework

The study follows an Input-Process-Output framework.

```text
INPUT
Customer, staff, provider, and administrator requirements
Service catalog, pricing rules, schedules, locations, and payment options
Software-quality and user-acceptance criteria
                 |
                 v
PROCESS
Requirements analysis and interface design
Agile development and database implementation
Booking validation, assignment, service execution, and payment workflows
Functional testing and user evaluation
                 |
                 v
OUTPUT
CleanFlow web and mobile system
Centralized booking and operational records
Service proof, payment, reporting, and audit capabilities
Evaluation findings and recommendations
```

### Research Paradigm

The independent variable is the implemented CleanFlow system. The evaluation dimensions are functional suitability, usability, reliability, performance efficiency, security, compatibility, maintainability, and portability, together with perceived usefulness and perceived ease of use where applicable. The dependent outcome is the level of system quality and user acceptance reported by the selected evaluators.

---

# CHAPTER 3

## RESEARCH METHODOLOGY

### Research Design

The study uses a developmental and descriptive-evaluative research design. The developmental component covers the analysis, design, implementation, and refinement of CleanFlow. The descriptive-evaluative component measures the implemented system against selected software-quality and user-acceptance criteria.

The study should report two kinds of evidence. Functional evidence comes from test cases showing whether the system performs required tasks and rejects invalid actions. User-evaluation evidence comes from actual intended users who interact with the system and answer the approved evaluation instrument. These forms of evidence should not be confused: automated tests can show that a rule is implemented, while respondents can show whether the rule and interface are understandable and useful.

### Development Methodology

The project follows an iterative Agile approach. The work is organized into short cycles in which requirements are reviewed, a feature is designed and implemented, the result is tested, and feedback is used for refinement. This approach is suitable because the system has several connected user roles and because booking, payment, proof, and reporting requirements may become clearer during implementation.

The development activities are:

1. **Planning.** Identify the operational problem, intended users, system boundaries, risks, and expected outputs.
2. **Requirements analysis.** Define client, staff, provider, administrator, payment, attendance, and reporting requirements.
3. **Design.** Design the role-based portals, booking lifecycle, database relationships, validation rules, API contracts, and security controls.
4. **Implementation.** Build the Laravel backend, Blade web interfaces, Expo mobile application, database models, file-storage workflows, and external-service integrations.
5. **Testing.** Test valid and invalid booking operations, status transitions, permissions, payment events, file uploads, mobile endpoints, and attendance requests.
6. **Evaluation and refinement.** Collect evaluator feedback, identify defects and usability problems, correct high-priority issues, and document remaining limitations.

### System Architecture

CleanFlow uses a layered web and mobile architecture:

- **Presentation layer.** Laravel Blade pages and the Expo React Native mobile application.
- **Application layer.** Laravel routes, controllers, request validation, middleware, jobs, services, and notifications.
- **Domain and data layer.** Eloquent models for users, services, bookings, payments, proofs, ratings, disputes, locations, attendance, notifications, and audit logs.
- **Infrastructure layer.** PostgreSQL or the configured database, queue worker, file storage, email transport, PayMongo, Daily.co, map services, and attendance devices.

The Laravel backend is the authoritative source for business rules. The browser and mobile client may display estimates and controls, but the backend repeats validation, authorization, schedule checks, and status checks before changing records.

### System Users

The system has four primary application roles:

1. **Client.** Browses services, maintains a profile, creates and manages bookings, views proof, pays or submits payment proof, rates service, and raises disputes.
2. **Staff.** Views assigned bookings, updates service progress, submits before and after proof, views schedules and performance, receives notifications, and may share location.
3. **Provider.** Uses an approved and activated provider account to manage availability, respond to assignments, execute accepted bookings, and monitor payout records.
4. **Administrator.** Manages users, services, bookings, assignments, payments, manual reviews, attendance, reports, disputes, and system settings.

### Functional Requirements

The system shall:

- allow public users to view services and service areas;
- allow clients to register, verify email, log in, recover passwords, and maintain profile data;
- calculate a booking estimate from the selected service, property, size, and add-ons;
- validate service availability, location, age/profile requirements, schedule conflicts, capacity, and service limits;
- create one-time or supported subscription booking records;
- identify selected risk conditions and route affected bookings to manual review;
- allow administrators to assign staff or approved providers and confirm or cancel bookings;
- require assigned staff or accepted providers to submit before proof before starting and after proof before completing a booking;
- record payment status, cash-proof review, digital-payment events, payout status, ratings, disputes, messages, locations, notifications, and activity history;
- provide mobile authentication and role-appropriate client and staff functions;
- provide reports and analytics for operational monitoring.

### Nonfunctional Requirements

The system should provide:

- **Security:** authenticated access, role restrictions, token validation, rate limits, protected downloads, and server-side authorization;
- **Reliability:** atomic booking writes, schedule locks, idempotent payment webhook handling, and audit logs;
- **Usability:** clear forms, status labels, pricing breakdowns, notifications, and role-specific navigation;
- **Performance efficiency:** database-side aggregation for dashboards, bounded external requests, and controlled file-upload limits;
- **Maintainability:** organized controllers, models, jobs, services, migrations, tests, and documented configuration;
- **Portability:** web access through supported browsers and mobile access through the Expo-based application and configured deployment environment.

### Data Gathering Procedures

The researchers should use the following procedures and replace the bracketed details with the actual implementation:

1. Review the existing manual or proposed cleaning-service workflow with the business owner or designated representatives.
2. Identify the tasks and information required by clients, staff, providers, and administrators.
3. Demonstrate the implemented system using representative booking scenarios.
4. Execute the approved functional test cases.
5. Ask selected respondents to perform tasks appropriate to their role.
6. Distribute the approved evaluation questionnaire after the users have interacted with the system.
7. Consolidate the responses, compute the prescribed descriptive statistics, and interpret the results using the school's scale.

### Research Participants

The study may use purposive sampling because the system requires respondents who can evaluate its intended workflows. The proposed groups are clients, cleaning staff, administrators, and, if available, marketplace providers.

| Respondent group | Target number | Inclusion basis |
|---|---:|---|
| Clients | [insert] | Has experience or can perform a customer booking scenario |
| Staff/cleaners | [insert] | Can perform an assigned-service scenario |
| Administrators | [insert] | Can perform booking, assignment, payment, or reporting tasks |
| Providers | [insert] | Can perform provider assignment and service-status tasks, if included |
| **Total** | **[insert]** | |

The final paper must explain the actual sampling method, number of participants, inclusion criteria, consent process, and date and location of the evaluation.

### Research Instrument

The proposed instrument contains two parts. Part I records respondent role and relevant experience. Part II evaluates the system using statements grouped under the selected ISO/IEC 25010 characteristics and Technology Acceptance Model constructs.

Example evaluation areas include:

- functional suitability: required tasks are available and produce correct results;
- usability: screens, labels, instructions, and workflow are understandable;
- reliability: the system behaves consistently and preserves records;
- performance efficiency: pages and actions respond within an acceptable time;
- security: users can access only authorized functions and records;
- maintainability and portability: the system can be updated and accessed in its supported environments;
- perceived usefulness: the system improves the respondent's work or service experience;
- perceived ease of use: the system can be learned and operated without excessive effort.

The questionnaire should be reviewed by the adviser or qualified validators before distribution. The manuscript should state the validation procedure and, if required, the reliability measure such as Cronbach's alpha.

### Data Analysis

For a Likert-scale evaluation, the weighted mean may be computed as:

```text
Weighted Mean = Σ(f × w) / N
```

where `f` is the frequency of each response, `w` is the numerical weight of the response, and `N` is the total number of responses.

Use the institution's approved interpretation scale. If no scale has been prescribed, the researchers may define one before analyzing the results, for example:

| Mean range | Interpretation |
|---:|---|
| 4.21–5.00 | Excellent / Strongly acceptable |
| 3.41–4.20 | Very good / Acceptable |
| 2.61–3.40 | Good / Moderately acceptable |
| 1.81–2.60 | Fair / Needs improvement |
| 1.00–1.80 | Poor / Not acceptable |

Do not use this scale if the school requires a different interpretation. Report the number of respondents, the item-level results, the dimension-level results, and the overall result.

### Ethical and Security Considerations

Respondents should be informed about the purpose of the evaluation, the voluntary nature of participation, and how their responses will be used. No real payment credentials, unnecessary identity documents, or private customer information should be used during demonstration and testing. Test accounts and test files should be used whenever possible.

The system should protect personal information through authentication, role-based authorization, restricted file access, rate limits, secure token handling, and controlled administrative access. Before production use, the operator must configure durable private storage, a real mail transport, secure payment secrets, external-service credentials, and appropriate data-retention policies.

### System Development Flow

```text
User requirement
      ↓
Service catalog and booking form
      ↓
Validation and price calculation
      ↓
Conflict, capacity, risk, and manual-review checks
      ↓
Atomic booking creation
      ↓
Admin assignment and confirmation
      ↓
Staff/provider service execution with proof
      ↓
Completion, payment follow-up, rating, dispute, and payout
      ↓
Reports, analytics, and audit history
```

---

# CHAPTER 4

## PRESENTATION, ANALYSIS, AND INTERPRETATION OF RESULTS

> This chapter is a structured draft based on the implemented system. Replace the evaluation placeholders with actual test outputs and respondent results. The feature descriptions below are implementation observations, not user-survey findings.

### Overview of the Implemented System

CleanFlow was implemented as a Laravel-based web application supported by an Expo mobile application. The web application provides public pages, client pages, staff pages, provider pages, and administrator pages. The mobile application communicates with the backend through authenticated `/api/mobile` endpoints. The database stores the booking and related operational records, while queued jobs handle selected email notifications.

### Public and Client Functions

The public interface presents the cleaning-service catalog, pricing information, frequently asked questions, and service-area coverage. A visitor may proceed to registration or login. Client registration captures identity, contact, date-of-birth, and password information. Client booking access requires authentication, email verification, and completion of required profile information.

The booking form allows the client to select a service, property type, floor area, rooms, bathrooms, add-ons, location, schedule, payment method, and service plan. The system calculates and displays a pricing breakdown. When the booking is submitted, the backend validates the request, checks schedule and capacity conditions, identifies risk conditions, and stores the booking in an atomic transaction.

For pricing, the client should enter the sum of the cleanable floor areas of all floors. For example, a 50-square-meter first floor and a 40-square-meter second floor should be entered as 90 square meters. Rooms and bathrooms or CRs describe the property and help operations plan the work, but they do not currently add separate fees. The current pricing formula is:

```text
Total price = (Total cleanable floor area × service rate) + add-on fees
```

The property type, room count, and bathroom/CR count remain important because they provide context for service scope, staffing, and manual review even when they do not directly change the price.

The resulting booking begins in the pending state. If a digital web payment method is selected and the configured PayMongo integration is available, the system creates a checkout session and redirects the client to the payment provider. PayMongo return verification and webhook handling update the payment record. For cash bookings, payment remains pending until collection or receipt review is completed.

### Booking Validation and Manual Review

The system performs multiple controls before accepting a booking record. It checks whether the service is active, whether the client has a conflicting active booking, whether the requested time has remaining capacity, and whether the selected service and property information are valid. It also checks service-scope limits, staffing requirements, duplicate address-and-schedule patterns, and repeated booking requests.

Requests that trigger configured risk or scope conditions are not silently treated as ordinary bookings. They are saved as pending bookings with manual-review metadata. The administrator must approve or block a booking before changing its operational status or assignment. This preserves human judgment for requests that exceed the assumptions used by automatic pricing or staffing rules.

### Administrator Functions

The administrator dashboard provides operational summaries and analytics. The administrator booking page groups active and completed or cancelled bookings and provides filters for today's work, unassigned work, overdue pending requests, manual review, in-progress work, and declined provider assignments.

Administrators can assign a staff member or an approved marketplace provider, update booking status, update payment information, review cash-payment proof, manage provider payouts, resolve disputes, and review activity logs. The backend checks schedule conflicts before staff assignment and prevents status changes that violate the configured booking lifecycle. A booking that requires staff cannot proceed without an assigned staff member or an accepted provider assignment.

### Staff and Provider Functions

Assigned staff members can view their bookings, schedule, notifications, performance information, and service areas. A staff member can start a confirmed booking only after uploading at least one before-service photo. To complete an in-progress booking, the staff member must upload at least one after-service photo and may upload a completion video.

Approved marketplace providers have a separate account and activation process. A provider can manage availability and payout setup, respond to assignments, view assigned bookings, and update accepted bookings. A provider must accept an assignment before updating service progress. Provider payout and commission records are kept separately from the operational status of the booking.

### Proof, Tracking, Communication, and Follow-Up

Service-proof records store the booking, uploader, service stage, media type, path, and original filename. Activity logs record status changes and proof-related actions. Clients and authorized internal users can access the appropriate proof records through protected routes.

During active work, the system can store booking location updates and location history. It also supports booking messages and an optional Daily.co live-video workflow where configured. These functions are supplementary to the primary booking lifecycle and depend on authorization, network connectivity, and external-service availability.

After completion, the client can view the booking result, rating information, payment status, and proof of service. The client can rate the completed booking or open a dispute. An open dispute can affect provider payout status until the administrator resolves it.

### Attendance and Operations Support

The attendance module supports device registration, enrollment requests, fingerprint consent, signed device requests, device heartbeat, and attendance punches. Attendance data can be used by administrators to monitor staff presence and by booking operations to display staff availability during assignment. The attendance device is an auxiliary subsystem; it does not itself confirm a booking or replace the booking-status workflow.

### Functional Test Results

Complete the following table using executed test cases. The status must be based on actual test evidence.

| Test area | Example test case | Expected result | Actual result | Status |
|---|---|---|---|---|
| Authentication | Client registers with valid information | Account is created and verification is requested | [insert] | [Pass/Fail] |
| Authorization | Client opens another client's booking | Access is denied | [insert] | [Pass/Fail] |
| Pricing | Client changes floor area and add-ons | Total and breakdown update correctly | [insert] | [Pass/Fail] |
| Duplicate booking | Client submits same active schedule twice | Second request is rejected | [insert] | [Pass/Fail] |
| Capacity | Full slot is submitted | Request is rejected | [insert] | [Pass/Fail] |
| Manual review | Oversized or risky booking is submitted | Booking remains pending review | [insert] | [Pass/Fail] |
| Assignment | Admin assigns a conflicting cleaner | Assignment is rejected | [insert] | [Pass/Fail] |
| Start service | Staff starts without before proof | Action is rejected | [insert] | [Pass/Fail] |
| Complete service | Staff completes without after proof | Action is rejected | [insert] | [Pass/Fail] |
| Payment webhook | Same paid event is delivered twice | Payment is not duplicated | [insert] | [Pass/Fail] |
| Mobile API | Expired or invalid token is used | Request is rejected | [insert] | [Pass/Fail] |
| File access | Unauthorized user requests proof file | Access is denied | [insert] | [Pass/Fail] |

### User Evaluation Results

Insert the actual respondent profile and evaluation results below.

#### Respondent Profile

| Category | Frequency | Percentage |
|---|---:|---:|
| Clients | [insert] | [insert] |
| Staff/cleaners | [insert] | [insert] |
| Administrators | [insert] | [insert] |
| Providers | [insert] | [insert] |
| **Total** | **[insert]** | **100%** |

#### Evaluation by Quality Dimension

| Quality dimension | Weighted mean | Interpretation |
|---|---:|---|
| Functional suitability | [insert] | [insert] |
| Usability | [insert] | [insert] |
| Reliability | [insert] | [insert] |
| Performance efficiency | [insert] | [insert] |
| Security | [insert] | [insert] |
| Compatibility/portability | [insert] | [insert] |
| Maintainability | [insert] | [insert] |
| Perceived usefulness | [insert] | [insert] |
| Perceived ease of use | [insert] | [insert] |
| **Overall** | **[insert]** | **[insert]** |

### Interpretation of Results

The functional results should be interpreted by comparing the actual result of each test case with its expected result. Passing a booking-conflict test indicates that the corresponding validation rule worked for the tested scenario; it does not prove that every possible scheduling edge case has been covered. Similarly, passing an authorization test supports the conclusion that the tested route rejected the tested unauthorized request, but broader security testing is still required.

The user-evaluation results should be interpreted by dimension. A high functional-suitability score would indicate that respondents believe the required features are present and correct. A high usability score would indicate that respondents can understand and operate the system. Reliability and performance scores must be supported by actual observed behavior and test conditions. Security scores reflect user perception and should not be presented as a substitute for a professional security audit.

### Implemented Limitations Identified During Evaluation

The following limitations should be disclosed unless they are resolved before submission:

- assignment is primarily administrator-driven rather than automatically dispatched;
- web and mobile workflows do not expose identical booking options;
- mobile booking currently defaults rooms and bathrooms and follows a more limited one-time booking flow;
- PayMongo, email delivery, object storage, mapping, video, and attendance depend on external configuration;
- public route services and network availability may affect map and tracking behavior;
- service proof supports accountability but cannot guarantee that every cleaning task was completed;
- analytics describe recorded system data and do not independently establish business success.

---

# CHAPTER 5

## SUMMARY, CONCLUSIONS, AND RECOMMENDATIONS

### Summary of Findings

The study developed CleanFlow, a web- and mobile-based home cleaning service management system for Valencia City. The system was designed to centralize the customer booking process and the internal service-operation process in one connected workflow.

The implemented system provides public service information, client account management, price calculation, schedule and capacity validation, manual-review controls, administrator booking management, cleaner and provider assignment, payment records, proof-of-service uploads, notifications, booking activity logs, messages, location tracking, ratings, disputes, provider payouts, reports, and mobile API access.

The booking lifecycle begins when a verified client submits a service request. The backend validates the request and creates a pending booking inside a protected transaction. The administrator then reviews the request, handles any manual-review condition, assigns a cleaner or accepted provider, and confirms the booking. The assigned worker starts the service with before-service proof, completes the service with after-service proof, and causes the booking to enter the completed state. Payment verification, rating, disputes, payout, and reporting follow the service execution.

Functional testing should be used to verify the rules and transitions. User evaluation should be used to measure perceived quality and acceptance. The final version of this chapter must include the actual test outcomes, respondent count, evaluation means, and interpretation from the completed study.

### Conclusions

Based on the implemented design, the following conclusions may be drawn, subject to the actual evaluation results:

1. CleanFlow provides a unified workflow for customer booking and cleaning-service operations by linking service, schedule, assignment, payment, proof, feedback, and reporting records.
2. Backend validation, schedule locks, capacity checks, and controlled status transitions provide a technical basis for reducing duplicate bookings and inconsistent operational updates.
3. The administrator portal serves as the central control point for manual review, assignment, confirmation, payment review, dispute handling, and payout monitoring.
4. Mandatory before-service and after-service proof improves traceability of service execution, although proof does not replace a defined service scope or human quality review.
5. The mobile API extends client and staff access to the same backend while preserving server-side authentication and authorization checks.
6. The system's actual acceptability cannot be concluded from implementation alone. It must be established through the reported functional tests and user-evaluation results.

### Recommendations

#### For the Business Operator

1. Define and publish service-scope sheets, inclusions, exclusions, customer acceptance rules, cancellation rules, re-clean policies, damage policies, and overage rules.
2. Establish a daily administrative process for reviewing pending, risky, overdue, unassigned, and provider-declined bookings.
3. Monitor queue-worker health, email delivery, payment-webhook events, object-storage availability, and backup completion.
4. Train staff and providers to capture clear and consented service proof and to update booking status at the correct stage.

#### For System Developers

1. Add automated assignment or dispatch only after reliable staffing, availability, geographic, and escalation rules have been defined.
2. Bring the mobile booking workflow to deliberate feature parity with the web workflow, or clearly document the differences in the product requirements.
3. Add stronger customer-acceptance and checklist records so that photo proof is supported by a structured definition of completed work.
4. Add automated monitoring and alerting for failed queued jobs, payment-webhook failures, storage failures, and external-service outages.
5. Replace or formalize public routing dependencies if location privacy, reliability, or provider terms require it.
6. Conduct penetration testing, file-upload testing, access-control testing, load testing, and disaster-recovery testing before production expansion.

#### For Future Researchers

1. Measure actual changes in booking response time, scheduling errors, payment reconciliation time, and service-resolution time before and after deployment.
2. Repeat user-acceptance evaluation with a larger and more representative respondent population.
3. Study customer retention, provider acceptance rate, cancellation rate, dispute rate, and completion quality using production data.
4. Evaluate the system under higher concurrent booking volume and intermittent mobile connectivity.
5. Compare manual coordination, web-only operation, and web-plus-mobile operation using controlled operational metrics.

### Final Statement

CleanFlow is a practical foundation for digitizing a local cleaning-service operation because it treats the booking as a complete operational lifecycle rather than a one-time online form. Its strongest contribution is the connection between intake, validation, assignment, execution proof, payment follow-up, and reporting. Its success as a deployed business system will depend on disciplined operations, real user adoption, external-service reliability, clear service policies, and evidence from continued measurement.

---

# REFERENCES

Agile Manifesto. (2001). *Principles behind the Agile Manifesto*. https://agilemanifesto.org/principles.html

Davis, F. D. (1989). Perceived usefulness, perceived ease of use, and user acceptance of information technology. *MIS Quarterly, 13*(3), 319–340. https://doi.org/10.2307/249008

International Organization for Standardization. (2023). *ISO/IEC 25010:2023—Systems and software engineering—Systems and software quality requirements and evaluation (SQuaRE)—Product quality model*. https://www.iso.org/standard/78176.html

Laravel. (n.d.). *Laravel documentation*. https://laravel.com/docs

---

## Submission Checklist

- [ ] Replace title-page placeholders and apply the school's formatting rules.
- [ ] Add the researchers, adviser, institution, program, and school year.
- [ ] Confirm the final research questions and objectives with the adviser.
- [ ] Add actual respondents, sampling method, consent procedure, and evaluation dates.
- [ ] Execute and record functional tests.
- [ ] Add validated questionnaire results and the required statistical treatment.
- [ ] Add screenshots, database/architecture diagrams, and appendices if required.
- [ ] Remove every `[insert]` placeholder before submission.
- [ ] Do not claim that the system is effective, secure, faster, or accepted unless the results support the claim.
