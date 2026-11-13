# Fira Link Mobile App: Wireframe Briefs for New and Modified Screens

**File Reference:** Part E of the Product Design & Engineering Architecture Specification  
**Order:** Sequenced in the exact chronological order of the user journey  

---

## 1. App Launch & Registration Phase

---

### S-101: Initial Language Choice
- **Status:** `NEW` (Belongs to Workflow 1, Step 1.3)
- **Goal:** Allow first-time users to select their preferred language (English or Amharic) before encountering any marketing or onboarding content.
- **Primary Action:** Tap "Continue in English" or "በአማርኛ ይቀጥሉ".
- **Entry Points:** `S-002` SplashWrapper (on first app launch).
- **Exits:** `S-003` OnboardingScreen.
- **Who Sees It:** All first-time app users (Guests).
- **Layout (Top to Bottom):**
  - **App Bar:** None. Full-screen immersive scaffold with clean gradient (`#FCFBFE` -> `#F9F7FA`).
  - **Header Section:** Centered brand emblem (`SvgPicture.asset('assets/img/logo.svg')`, width 160).
  - **Title Section:** `Text('Choose your language / ቋንቋ ይምረጡ', style: typography.headlineMedium)`.
  - **Subtitle Section:** `Text('You can switch this anytime from settings', style: typography.bodyMedium, color: mutedText)`.
  - **Selection Cards (Existing Component: `Card` + `ListTile`):**
    - Option A: English (`assets/img/icons/en.png`, Title: "English", Subtitle: "Global Commodity Trade & Learning").
    - Option B: Amharic (`assets/img/icons/am.png`, Title: "አማርኛ", Subtitle: "የኢትዮጵያ ኤክስፖርት ንግድ እና ስልጠና").
  - **Bottom Action (Existing Component: `ElevatedButton`):** Full-width button with text "Continue / ይቀጥሉ".
- **Content & Placeholders:**
  - Label: "Choose your language"
  - Helper: "Select the language you want to use in Fira Link."
  - CTA Button: "Continue"
- **States:** Default (English selected by default); Tap selects with purple border highlight (`Border.all(color: brandPrimary, width: 2.0)`).
- **Validation & Edge Cases:** No network dependency; persists immediately to `SharedPreferences` key `selected_locale`.
- **Backend Dependency:** Missing (purely client-side preference; synced to `/api/user` if logged in).
- **Open Questions for Product Owner:** Should we support Afaan Oromoo in Phase 1 given its major significance in Jimma and Guji coffee producing regions?

---

### S-003: Onboarding Carousel
- **Status:** `MODIFY` (Belongs to Workflow 1, Step 1.4)
- **Goal:** Introduce Fira Link's core pillars (Trade, AI Docs, Coffee Safaris, Summits) while giving users a frictionless path to browse immediately as a guest.
- **Primary Action:** Tap "Explore as Guest" (Primary) or "Sign In / Register" (Secondary).
- **Entry Points:** `S-101` Initial Language Choice.
- **Exits:** `S-007` Home (via Guest), `S-004` Auth (via Sign In).
- **Who Sees It:** All first-time users (Guests).
- **Layout (Top to Bottom):**
  - **Top Bar (Existing Component: `Row`):** Brand emblem on left, "Skip" text button on right.
  - **Carousel View (Existing Component: `PageView` + `_OnboardingContent`):** 4 slides with high-res illustrations, pill category badge, accent titles, and descriptions.
  - **Page Indicator (Existing Component: `_Indicator`):** Animated pill dots.
  - **Bottom Action Stack (Existing Component: `Column`):**
    - Primary Button: `ElevatedButton(text: 'Explore as Guest')`.
    - Secondary Button: `OutlinedButton(text: 'Sign In or Register')`.
- **Content & Placeholders:**
  - Slide 1 Badge: "B2B COMMERCE & EXPORT"
  - Slide 2 Badge: "FIRA AI LEGAL STUDIO"
  - Slide 3 Badge: "COFFEE SAFARIS & AGRO-TOURS"
  - Slide 4 Badge: "GLOBAL SUMMITS & ACADEMY"
  - Primary CTA: "Explore as Guest"
  - Secondary CTA: "Sign In or Register"
- **States:** Default interactive carousel; smooth swiping; instant skip.
- **Validation & Edge Cases:** If user taps "Explore as Guest", set `has_seen_onboarding = true` in local storage so subsequent app launches jump straight to Home.
- **Backend Dependency:** Endpoint exists (pure client presentation).
- **Open Questions for Product Owner:** None.

---

### S-007: Home Screen (Personalized & Guest Adaptive)
- **Status:** `MODIFY` (Belongs to Workflow 1, Step 1.5)
- **Goal:** Serve as the central starting point for trade, market intelligence, tools, and courses, adapting seamlessly between guest visitors and authenticated exporters/buyers.
- **Primary Action:** Tap primary contextual action card ("Continue Learning" or "Open Seller Portal" or "Create Sourcing RFQ").
- **Entry Points:** `S-002` SplashWrapper, `S-003` Onboarding, Shell Tab 0.
- **Exits:** `S-010` Courses, `S-014` Marketplace, `S-017` Toolkit, `S-024` Fira AI, `S-004` Auth.
- **Who Sees It:** All users (Guest, Student, Exporter, Buyer).
- **Layout (Top to Bottom):**
  - **App Bar (Existing Component: `HomeHeader`):** Compact row with user avatar (or guest icon), greeting ("Good morning, [User Name]" or "Welcome to Fira Link"), active workspace badge, and notification bell with unread badge.
  - **Section 1: Contextual Primary Action (Existing Component: `HomePrimaryAction` / `ContinueLearningCard`):**
    - Guest: "Learn coffee export from source to market" + "Explore courses" button.
    - Exporter: "Manage your coffee lots & quotes" + "Open seller portal" button.
    - Buyer: "Find verified Ethiopian coffee" + "Create an RFQ" button.
    - Student: "Continue [Course Title]" + progress bar + "Resume lesson" button.
  - **Section 2: Market Pulse Ticker (Existing Component: `MarketStrip`):** Horizontal scroll of live ECTA regulatory minimum prices (e.g. `Sidama G1 Washed $3.85/lb`, `Yirgacheffe G2 Natural $3.60/lb`).
  - **Section 3: Export Toolkit Bento Grid (Existing Component: `ToolkitBento`):** 4 quick access cards: FOB Calculator, Offer Generator, Readiness Score, Daily Market Candlestick.
  - **Section 4: Search & Topic Filter (Existing Components: `HomeSearchBar`, `TopicChips`):** Search input with chips (`All`, `Sourcing`, `Cupping & quality`, `Export docs`, `Logistics`, `Finance`, `Buyers`).
  - **Section 5: Recommended Training List (Existing Component: `FeaturedCourseCard`):** Horizontal snap list of top-rated courses.
  - **Bottom Dock Clearance:** 140dp clearance for `FiraBottomDock`.
- **Content & Placeholders:**
  - Greeting: "Good morning, [User First Name]"
  - Ticker Label: "ECTA Minimum Price Compliant"
  - Card 1: "[Current Enrolled Course Name]"
- **States:** Loading (`HomeSkeletons`), Error (`_buildErrorState`), Pull-to-refresh, Offline (serves cached market data).
- **Validation & Edge Cases:** If unauthenticated guest taps any transactional action, trigger non-intrusive `VerificationPromptDialog.showAuthRequired`.
- **Backend Dependency:** Endpoint exists (`GET /api/home`).
- **Open Questions for Product Owner:** Should the daily market ticker support push alerts when ECTA announces new minimum prices?

---

### S-004: Auth Screen (Phone-First Registration & Login)
- **Status:** `MODIFY` (Belongs to Workflow 2, Step 2.1)
- **Goal:** Allow users to authenticate or register using their mobile phone number (Ethiopian or international), with email as a secondary backup.
- **Primary Action:** Tap "Send Verification Code" (Register) or "Sign In" (Login).
- **Entry Points:** Profile login button, any gated action prompt.
- **Exits:** `S-005` OtpVerificationScreen, `S-006` ForgotPasswordScreen, `S-007` Home.
- **Who Sees It:** Unauthenticated Guests.
- **Layout (Top to Bottom):**
  - **Header Section (Existing Component: `_HeaderSection`):** Centered SVG logo (`assets/img/logo.svg`).
  - **Card Container (Existing Component: `_LoginCard`):** White/Dark surface container (radius 32 top) with subtle shadow.
  - **Tab Bar (Existing Component: `TabBar`):** 56dp segmented pill (`Login` / `Sign Up`).
  - **Form Fields (Existing Component: `InputDecorationTheme`):**
    - First Name & Last Name (Sign Up only).
    - Phone Number input with country prefix pill (`+251` default, flag icon).
    - Optional Email Address input.
    - Password & Confirm Password with visibility toggle icon.
  - **Terms Checkbox (Existing Component: `CheckboxListTile`):** "I agree to Fira Link B2B Trade & LMS Terms".
  - **Submit Button (Existing Component: `ElevatedButton`):** "Create Account" (Sign Up) or "Sign In" (Login).
- **Content & Placeholders:**
  - Phone Hint: "911 234 567"
  - Email Hint: "name@company.com (optional)"
  - Submit Button: "Create Account"
- **States:** Default form, Submitting (button spinner), Error toast (invalid phone regex, password mismatch).
- **Validation & Edge Cases:** Phone regex validates Ethiopian mobile prefixes (`^(?:\+251|251|0)?([79]\d{8})$`). Password must be >=8 chars.
- **Backend Dependency:** Endpoint exists (`POST /api/auth/register`, `POST /api/auth/login`).
- **Open Questions for Product Owner:** Can international buyers register with non-Ethiopian numbers (+1, +49, +81)? (Yes, regex must accommodate international E.164).

---

### S-005: OTP Verification Screen
- **Status:** `MODIFY` (Belongs to Workflow 2, Step 2.2)
- **Goal:** Verify the user's mobile number via a 6-digit SMS code, activate their account, and navigate them into intent-based onboarding.
- **Primary Action:** Enter 6-digit PIN; triggers auto-submit on 6th digit.
- **Entry Points:** `S-004` AuthScreen.
- **Exits:** `S-102` UserIntentSelection (NEW), `S-004` (Back / Change Phone).
- **Who Sees It:** Unverified users completing registration or phone login.
- **Layout (Top to Bottom):**
  - **App Bar (Existing Component: `AppBar`):** Back arrow with title "Phone Verification".
  - **Header Text (Existing Component: `Column`):**
    - `Text('Enter verification code', style: typography.headlineMedium)`
    - `Text('We sent a 6-digit SMS code to [Masked Phone Number]', style: typography.bodyMedium, color: mutedText)`
    - Action link: "Change phone number" (pops back to S-004).
  - **6-Digit PIN Fields (Existing Component: `Row` of 6 `TextField`s):** Radius 16 containers with auto-advance and backspace handling.
  - **Countdown & Resend (Existing Component: `Row`):**
    - Timer: "Resend code in [60s]"
    - Enabled State: "Resend Code via SMS" text button.
  - **Verify Button (Existing Component: `ElevatedButton`):** "Verify & Continue".
- **Content & Placeholders:**
  - Masked Phone: `+251 91 *** 4567`
  - Timer: `Resend code in 48s`
  - Button: `Verify & Continue`
- **States:** Default, Auto-submitting on 6 digits, Success toast, Invalid/Expired code error toast with shake animation.
- **Validation & Edge Cases:** Code expires after 5 minutes. Resend disabled for 60 seconds after dispatch to prevent SMS gateway flooding.
- **Backend Dependency:** Endpoint exists (`POST /api/auth/register/verify`, `POST /api/auth/register/resend`).
- **Open Questions for Product Owner:** Does SMS Ethiopia gateway support international SMS delivery, or should WhatsApp / Telegram OTP be added for overseas buyers?

---

### S-102: User Intent Selection Screen
- **Status:** `NEW` (Belongs to Workflow 2, Step 2.3)
- **Goal:** Capture the user's immediate commercial or educational intent to customize their experience without locking their profile into an irreversible account type.
- **Primary Action:** Tap one of the 4 intent cards and tap "Continue".
- **Entry Points:** `S-005` OtpVerificationScreen.
- **Exits:** `S-103` BusinessWorkspaceSetup (if Exporter/Buyer), `S-104` IntentWelcome (if Learner/Events).
- **Who Sees It:** Newly registered users right after OTP verification.
- **Layout (Top to Bottom):**
  - **App Bar:** Centered small logo with step indicator ("Step 1 of 2").
  - **Title Section:**
    - `Text('What brings you to Fira Link?', style: typography.headlineLarge)`
    - `Text('Choose your main focus. You can always change or add workspaces later.', style: typography.bodyMedium, color: mutedText)`
  - **Intent Selection Grid (Needs New Component: `IntentChoiceCard` using `Card` + `FiraColors`):**
    - **Card 1 (Exporter / Producer):** Icon `Icons.directions_boat_outlined`, Title: "Export & Sell Commodities", Subtitle: "Publish verified coffee lots, respond to global RFQs, and access export pricing tools."
    - **Card 2 (Buyer / Importer):** Icon `Icons.coffee_outlined`, Title: "Source & Buy Ethiopian Coffee", Subtitle: "Browse verified origins, request tracked samples, and post procurement tenders."
    - **Card 3 (Academy Student):** Icon `HugeIcons.strokeRoundedBook01`, Title: "Learn & Master Export Trade", Subtitle: "Practical video courses, trade masterclasses, and accredited certifications."
    - **Card 4 (Events & Farm Tours):** Icon `HugeIcons.strokeRoundedCalendar01`, Title: "Attend Summits & Origin Tours", Subtitle: "Reserve passes for trade forums and book guided coffee agro-safaris."
  - **Bottom Action:** `ElevatedButton(text: 'Continue')`.
- **Content & Placeholders:**
  - Title: "What brings you to Fira Link?"
  - Button: "Continue"
- **States:** Default (Card 1 selected by default with purple border `brandPrimary` and checkmark); Tap changes selection.
- **Validation & Edge Cases:** User must select exactly one intent. Selection stores intent tag on user session.
- **Backend Dependency:** Endpoint exists (`POST /api/user/profile` or Sanctum user metadata).
- **Open Questions for Product Owner:** None.

---

### S-103: Business Workspace Setup Screen
- **Status:** `NEW` (Belongs to Workflow 2, Step 2.4)
- **Goal:** Enable commercial exporters and international buyers to establish their organization workspace immediately during onboarding.
- **Primary Action:** Tap "Create Workspace" or "Skip for now".
- **Entry Points:** `S-102` Intent Selection (when Exporter or Buyer is chosen).
- **Exits:** `S-104` IntentWelcomeLanding.
- **Who Sees It:** Newly registered users with commercial intent.
- **Layout (Top to Bottom):**
  - **App Bar:** Step indicator ("Step 2 of 2") with "Skip for now" on right.
  - **Title Section:**
    - `Text('Set up your trading workspace', style: typography.headlineLarge)`
    - `Text('Your business workspace houses your verified licenses, team members, lots, and contracts.', style: typography.bodyMedium, color: mutedText)`
  - **Form Fields (Existing Component: `InputDecorationTheme`):**
    - Legal Company Name (`[Company Name]`)
    - Business Entity Type Dropdown: `Licensed Exporter`, `Coffee Cooperative / Union`, `Commercial Farm / Estate`, `Washing Station Operator`, `International Importer / Roaster`, `Trade Broker`.
    - Country of Incorporation Dropdown (Ethiopia default for exporters; USA, Germany, Japan, etc. for buyers).
    - Optional Tax Identification Number (TIN).
  - **Trust Disclaimer Box (Existing Component: `Container` + `FiraColors.brandTint`):**
    - Row with `Icons.shield_outlined`: "Trust is Never for Sale: Official verification is role-specific and verified after setup via your ECTA credentials."
  - **Bottom Action Stack:**
    - `ElevatedButton(text: 'Create Business Workspace')`
    - `TextButton(text: 'I will set this up later')`
- **Content & Placeholders:**
  - Company Hint: "e.g. Sidama Horizon Coffee Union"
  - Button: "Create Business Workspace"
- **States:** Default form, Submitting spinner, Success toast.
- **Validation & Edge Cases:** Company Name must be >=3 chars. If user taps "Skip", create default Personal Space and route to S-104.
- **Backend Dependency:** Endpoint needs change (Requires `POST /api/organizations` per SaaS architecture).
- **Open Questions for Product Owner:** Should TIN number validation format (10 digits) be strictly enforced during initial setup, or left flexible until verification? (Flexible until verification).

---

### S-104: Intent Welcome Landing Screen
- **Status:** `NEW` (Belongs to Workflow 2, Step 2.5)
- **Goal:** Provide a celebratory, personalized landing screen that guides the user directly to their highest-value first action.
- **Primary Action:** Tap the highlighted primary action button.
- **Entry Points:** `S-103` BusinessWorkspaceSetup, `S-102` IntentSelection.
- **Exits:** `S-007` Home, `S-021` Verification (Exporter), `S-019` Create RFQ (Buyer), `S-010` Courses (Learner).
- **Who Sees It:** All newly registered users upon onboarding completion.
- **Layout (Top to Bottom):**
  - **Hero Illustration:** Centered animated graphic with ambient purple glow.
  - **Welcome Text:**
    - `Text('Welcome to Fira Link, [First Name]!', style: typography.headlineLarge)`
    - `Text('Your [Workspace Name] workspace is ready.', style: typography.bodyLarge, color: brandPrimary)`
  - **Recommended Next Step Card (Existing Component: `Card` with radius 24):**
    - Exporter: Title: "Verify Your ECTA License", Description: "Submit your export credentials to unlock publishing unlimited coffee lots and issuing legal offer sheets." + `ElevatedButton(text: 'Start Verification')`.
    - Buyer: Title: "Post Your First Coffee Tender", Description: "Publish an RFQ to receive competitive FOB quotations directly from verified Ethiopian producers." + `ElevatedButton(text: 'Post an RFQ')`.
    - Learner: Title: "Start Your First Practical Course", Description: "Explore foundational training on Ethiopian coffee export regulations and cupping standards." + `ElevatedButton(text: 'Browse Courses')`.
  - **Secondary Action:** `TextButton(text: 'Go to Home Feed')`.
- **Content & Placeholders:**
  - Welcome: "Welcome to Fira Link, [First Name]!"
  - Workspace: "[Company Name or Personal Space]"
- **States:** Default.
- **Validation & Edge Cases:** Tapping primary action navigates directly to target screen; tapping secondary action opens S-007 Home.
- **Backend Dependency:** Endpoint exists (local configuration).
- **Open Questions for Product Owner:** None.

---

## 2. Workspaces & Identity Management

---

### S-035: Profile & Workspace Hub Screen
- **Status:** `MODIFY` (Belongs to Workflow 3, Step 3.1 & Workflow 17, Step 17.1)
- **Goal:** Provide users with unified account management, workspace switching, feature shortcuts, preferences, and security controls.
- **Primary Action:** Switch active workspace or tap menu item.
- **Entry Points:** Shell Tab 5 (Profile).
- **Exits:** `S-105` WorkspaceSwitcher, `S-036` EditProfile, `S-037` Settings, `S-020` SellerPortal, `S-039` PaymentHistory.
- **Who Sees It:** All users (Personal, Exporter, Buyer, Student).
- **Layout (Top to Bottom):**
  - **App Bar:** Centered title "Account & Workspaces".
  - **User Profile Header (Existing Component: `Container` radius 20):**
    - Row: Avatar clip, Name (`[User Full Name]`), Email (`[user@email.com]`), Member status badge (`Verified Member`).
  - **Active Workspace Switcher Pill (Needs New Component: `WorkspacePill`):**
    - Interactive pill directly below user header:
      `Row: [🏢 Sidama Horizon Union (Exporter)] [Tier 1 Verified 🟢] [Switch ▾]`
    - Tapping pill opens S-105 Workspace Switcher Modal.
  - **Menu Group 1: Workspace & Trade Operations (Existing Component: `_MenuItem`):**
    - "Seller Portal" (if exporter workspace active)
    - "Buyer Procurement & RFQs" (if buyer workspace active)
    - "Export Toolkit & Calculations"
    - "Digital Deal Rooms"
  - **Menu Group 2: Learning & Achievements:**
    - "My Enrolled Courses", "Certificates", "Assignments", "Quiz Attempts"
  - **Menu Group 3: Financial & Legal:**
    - "Invoices & Payment History", "Compliance Document Vault"
  - **Menu Group 4: Settings & Security:**
    - "Settings", "Notification Preferences", "Dark Mode Toggle", "Language (English / አማርኛ)"
  - **Bottom Action:** "Log Out" (or "Log In" if guest).
- **Content & Placeholders:**
  - Name: `[User Name]`
  - Workspace: `[Active Workspace Legal Name]`
- **States:** Default, Authenticated vs Guest, Switched workspace state.
- **Validation & Edge Cases:** Tapping workspace switcher instantly re-scopes active entitlements, lots, and deal rooms across the app.
- **Backend Dependency:** Endpoint needs change (`GET /api/user/organizations`).
- **Open Questions for Product Owner:** Should personal learning progress (courses/quizzes) follow the user across all business workspaces? (Yes, personal courses are user-scoped; trade lots and deal rooms are workspace-scoped).

---

### S-105: Workspace Switcher Modal
- **Status:** `NEW` (Belongs to Workflow 3, Step 3.2)
- **Goal:** Allow users managing multiple companies (e.g. coffee exporter and sesame exporter) to toggle active workspace context.
- **Primary Action:** Tap an organization to switch to it.
- **Entry Points:** `S-035` Profile Screen (Workspace Pill tap).
- **Exits:** `S-035` Profile, `S-106` CreateBusinessWorkspace.
- **Who Sees It:** Authenticated users.
- **Layout (Top to Bottom):**
  - **Modal Header (Existing Component: `BottomSheetThemeData`):** Drag handle, title "Select Active Workspace", close icon.
  - **Workspace List (Existing Component: `ListView`):**
    - Item 1: `👤 Personal Space` (Personal courses, saved lots, event tickets).
    - Item 2: `🏢 [Company Name 1]` (Subtitle: "Licensed Exporter • Tier 1 Verified", Checkmark if active).
    - Item 3: `🏢 [Company Name 2]` (Subtitle: "Trade Broker • Pending Review").
  - **Bottom Action:**
    - `OutlinedButton(icon: Icons.add_business, text: 'Add Business Workspace')` -> opens S-106.
- **Content & Placeholders:**
  - Company: `[Organization Legal Name]`
  - Role: `[Owner / Manager / Staff]`
- **States:** Default, Active workspace highlighted with purple background tint (`mint100`).
- **Validation & Edge Cases:** Switching workspace triggers immediate cache refresh and updates `EntitlementsProvider`.
- **Backend Dependency:** Endpoint needs change (`POST /api/user/switch-organization`).
- **Open Questions for Product Owner:** None.

---

### S-106: Create Business Workspace Screen
- **Status:** `NEW` (Belongs to Workflow 3, Step 3.3)
- **Goal:** Register an additional legal trading entity under the user's primary account.
- **Primary Action:** Tap "Create Workspace".
- **Entry Points:** `S-105` Workspace Switcher Modal.
- **Exits:** `S-021` OrganizationVerificationScreen.
- **Who Sees It:** Authenticated users.
- **Layout (Top to Bottom):**
  - **App Bar:** Back arrow, title "Add Business Workspace".
  - **Form Fields (Existing Component: `InputDecorationTheme`):**
    - Legal Entity Name
    - Business Type Dropdown (Exporter, Supplier, Buyer, Broker)
    - Corporate Email & Phone Number
    - Registered Office City / Region
  - **Bottom Action:** `ElevatedButton(text: 'Register Entity & Proceed to Verification')`.
- **Content & Placeholders:**
  - Name Hint: "e.g. Kaffa Green Forest PLC"
  - Button: "Register Entity"
- **States:** Default form, Submitting spinner, Error toast.
- **Validation & Edge Cases:** Checks for duplicate entity names or TIN collisions.
- **Backend Dependency:** Endpoint needs change (`POST /api/organizations`).
- **Open Questions for Product Owner:** Can a user be an Owner of one workspace and a Staff member of another? (Yes, fully supported by SaaS architecture).

---

### S-107: Team Seats & Member Roles Screen
- **Status:** `NEW` (Belongs to Workflow 3, Step 3.4)
- **Goal:** Allow organization Owners and Managers to manage employee access, assign roles, and invite colleagues within their SaaS seat quota.
- **Primary Action:** Tap "Invite Team Member".
- **Entry Points:** `S-020` Seller Portal, `S-035` Profile.
- **Exits:** Invite member modal.
- **Who Sees It:** Organization Owners and Managers.
- **Layout (Top to Bottom):**
  - **App Bar:** Back arrow, title "Team & Permissions".
  - **Seat Quota Card (Existing Component: `Container` radius 16):**
    - Row: `Icons.people_outline`, Text: `[Used Seats] of [Total Seats] Seats Used`, ProgressBar, Upgrade link if at capacity.
  - **Active Members List (Existing Component: `ListView`):**
    - Member item: Avatar, Name (`[Employee Name]`), Email (`[email]`), Role Dropdown (`Owner`, `Manager`, `Staff`), Remove action.
  - **Pending Invitations Section:**
    - List of invited phone numbers / emails awaiting acceptance.
  - **Bottom Action:** `ElevatedButton(icon: Icons.person_add, text: 'Invite Team Member')`.
- **Content & Placeholders:**
  - Quota: `3 of 5 Seats Used`
  - Button: `Invite Team Member`
- **States:** Default, Quota full (disables invite button and shows `QuotaLimitSheet`), Submitting invite.
- **Validation & Edge Cases:** If plan seat limit is reached, trigger `QuotaLimitSheet.show(context, {'feature': 'seats'})`.
- **Backend Dependency:** Endpoint missing (`GET /api/organizations/{id}/members`, `POST .../invite`).
- **Open Questions for Product Owner:** Can staff members be restricted from viewing financial deal room milestones? (Yes, role-based permission).

---

## 3. Exporter Verification & Marketplace Trust Phase

---

### S-020: Seller Portal Screen
- **Status:** `MODIFY` (Belongs to Workflow 4, Step 4.1)
- **Goal:** Provide Ethiopian exporters with a real-time trading command center showing their verification tier, active lot quota, received inquiries, and live lots.
- **Primary Action:** Tap "Add New Lot" or "Upgrade Verification".
- **Entry Points:** `S-014` Marketplace Home (AppBar storefront icon), `S-007` Home Action.
- **Exits:** `S-016` CreateLot, `S-021` Verification, `S-023` Pricing, `S-117` DealRoom.
- **Who Sees It:** Authenticated Exporters and Suppliers.
- **Layout (Top to Bottom):**
  - **App Bar:** Back arrow, title "Exporter Trading Portal", actions: Refresh, Settings.
  - **Section 1: Verification & Compliance Card (Needs New Component: `TierVerificationBadge`):**
    - Container (radius 20, purple gradient): Exporter Name (`[Company Name]`), ECTA License No (`[ECTA/EXP/2026/049]`), Tier Badge (`Tier 1 Legal Verified 🟢`), "Upgrade to Tier 2 (Inspected)" action link.
  - **Section 2: SaaS Entitlement Meters (Existing Component: `_buildMeterCard`):**
    - Row of 3 meter cards: Active Lots (`[3/15 Used]`), RFQ Quotations (`[4/30 Used]`), Deal Rooms (`[1/10 Active]`).
  - **Section 3: Quick Hub Actions:**
    - Chips: `Active Lots`, `Buyer Inquiries ([2])`, `Incoming RFQ Bids ([5])`, `Document Studio`.
  - **Section 4: My Published Lots List (Existing Component: `MarketplaceLotCard`):**
    - Cards with status pills: `Active`, `Under Negotiation`, `Paused`, `Expired`.
  - **Bottom Action:** Floating Action Button `FloatingActionButton.extended(icon: Icons.add, label: 'Add New Lot')`.
- **Content & Placeholders:**
  - Company: `[Company Legal Name]`
  - License: `ECTA/[Year]/[Number]`
  - Quota: `[Used] / [Limit] Lots Active`
- **States:** Default, Loading indicator, Quota warning banner, Unverified state (prompts verification before lot creation).
- **Validation & Edge Cases:** If unverified, tapping "Add New Lot" opens `VerificationPromptDialog` with "Trust is Never for Sale" explanation.
- **Backend Dependency:** Endpoint exists (`GET /api/marketplace/seller/profile`, `GET .../listings`).
- **Open Questions for Product Owner:** None.

---

### S-021: Organization Verification Dossier Screen
- **Status:** `MODIFY` (Belongs to Workflow 4, Step 4.2)
- **Goal:** Enable Ethiopian exporters to submit institutional legal, operational, and financial compliance documents to achieve Tier 1, Tier 2, or Tier 3 verified standing.
- **Primary Action:** Tap "Attach & Vault" on required document slots and submit dossier.
- **Entry Points:** `S-020` Seller Portal, Gated action dialogs.
- **Exits:** `S-108` DocumentScanner, `S-109` VerificationAuditTracker.
- **Who Sees It:** Authenticated commercial entities.
- **Layout (Top to Bottom):**
  - **App Bar:** Back arrow, title "Compliance & Verification".
  - **Role Selector Tabs (Existing Component: `TabBar`):** `Exporter`, `Washing Station / Estate`, `Buyer`, `Broker`.
  - **Tier Explainer Card (Existing Component: `Container` radius 18):**
    - Tier 1: Legal Registration & ECTA Competence (Required to trade).
    - Tier 2: Physical Warehouse & Washing Station Inspection.
    - Tier 3: Financial Bank Vetting & Clean Audit.
  - **Regulatory Inputs (Existing Component: `InputDecorationTheme`):**
    - TIN Number TextField (`[10-digit TIN]`)
    - Export License Number (`[ECTA License]`)
    - Issuing Authority (`ECTA` / `Ministry of Trade`)
    - Expiry Date Picker (`[YYYY-MM-DD]`)
  - **Document Checklist Cards (Needs New Component: `DocumentUploadSlotCard`):**
    - Slot 1: Commercial Registration Certificate (MoTI) -> `[Status: Uploaded 🟢 / Missing ⚠️]`
    - Slot 2: ECTA Coffee Export Competence Certificate -> `[Status: Uploaded 🟢]`
    - Slot 3: TIN & VAT Certificate -> `[Status: Missing ⚠️]`
    - Slot 4: Authorized Signatory Passport / ID -> `[Status: Uploaded 🟢]`
  - **Bottom Action:** `ElevatedButton(text: 'Submit Dossier for Regulatory Audit')`.
- **Content & Placeholders:**
  - TIN Hint: "e.g. 0012345678"
  - License Hint: "e.g. ECTA-EXP-2026-0921"
  - Button: "Submit Dossier for Regulatory Audit"
- **States:** Default form, Submitting spinner, In Review state (locks fields and displays `S-109`), Approved state.
- **Validation & Edge Cases:** **CRITICAL FIX:** Remove hardcoded dummy byte upload (`utf8.encode('PDF_CERTIFICATE_...')`). Invoke real `S-108` camera/picker.
- **Backend Dependency:** Endpoint exists (`POST /api/marketplace/verification/submit`, `POST .../upload-document`).
- **Open Questions for Product Owner:** How many days does manual admin/auditor review take on average? (Typically 24-48 business hours; should show SLA).

---

### S-108: Compliance Document Scanner & Camera Capture
- **Status:** `NEW` (Belongs to Workflow 4, Step 4.3)
- **Goal:** Allow exporters to capture crisp, glare-free camera scans or PDF uploads of their trade licenses and lab certificates with auto-edge detection.
- **Primary Action:** Tap shutter button to capture document or select PDF.
- **Entry Points:** `S-021` Verification Dossier, `S-111` Lab Uploader.
- **Exits:** Returns to caller screen with encrypted file URI.
- **Who Sees It:** Authenticated users submitting compliance files.
- **Layout (Top to Bottom):**
  - **Viewfinder Header:** Back arrow, Flash toggle, Document Type Label ("Scanning: [ECTA Competence Certificate]").
  - **Camera Viewfinder Area:** Live camera preview with high-contrast document boundary guide box.
  - **Guidance Pill:** "Align document edges inside frame. Ensure text is readable."
  - **Bottom Control Bar:**
    - Left: Gallery / PDF File Picker icon (`Icons.picture_as_pdf`).
    - Center: Large circular shutter button (radius 72, white with purple ring).
    - Right: Retake / Flip icon.
- **Content & Placeholders:**
  - Guide: "Place document on a flat, well-lit surface."
  - Button: "Capture Document"
- **States:** Live viewfinder, Processing/Crop preview, Uploading to secure vault spinner.
- **Validation & Edge Cases:** Validates file size (max 10MB) and formats (PDF, JPG, PNG). Rejects blurry images with warning prompt.
- **Backend Dependency:** Endpoint exists (`POST /api/marketplace/verification/upload-document`).
- **Open Questions for Product Owner:** None.

---

### S-109: Verification Status & Audit Tracker Screen
- **Status:** `NEW` (Belongs to Workflow 4, Step 4.4)
- **Goal:** Give exporters transparent visibility into the regulatory auditing process, auditor notes, and annual renewal timelines.
- **Primary Action:** Review status or tap "Resolve Changes Requested".
- **Entry Points:** `S-020` Seller Portal, `S-021` Verification.
- **Exits:** `S-021` (if changes needed), `S-110` Public Verification.
- **Who Sees It:** Exporters with pending, approved, or flagged verification.
- **Layout (Top to Bottom):**
  - **App Bar:** Back arrow, title "Verification Audit Status".
  - **Status Hero Card (Existing Component: `Container` radius 24):**
    - Icon: `Icons.hourglass_top` (Pending) or `Icons.verified` (Approved) or `Icons.error` (Changes Requested).
    - Title: "Under Compliance Review" / "Tier 1 Exporter Verified".
    - Subtitle: "Submitted on [Date] • Estimated completion: 24-48 hrs".
  - **Audit Step Timeline (Needs New Component: `AuditTimelineTile`):**
    - Step 1: Dossier Received & Authenticated (`Completed 🟢`).
    - Step 2: ECTA Regulatory License Cross-Check (`In Progress 🟣`).
    - Step 3: Physical Facility & Warehouse Review (`Upcoming ⚪`).
    - Step 4: Verification Seal Issued (`Upcoming ⚪`).
  - **Auditor Notes Box (if changes requested):**
    - "Note from ECTA Auditor: Please upload a clearer copy of your 2026 Commercial Registration with official stamp."
    - Button: "Re-upload Document".
  - **Bottom Action:** "View Public Verified Badge" (if approved).
- **Content & Placeholders:**
  - Status: `[Pending / Approved / Changes Requested]`
  - Date: `[Submission Date]`
- **States:** Pending, Approved, Changes Requested, Expired.
- **Validation & Edge Cases:** If status is "Changes Requested", highlights exactly which document was rejected and provides 1-tap re-upload.
- **Backend Dependency:** Endpoint exists (`GET /api/marketplace/verification/status`).
- **Open Questions for Product Owner:** None.

---

### S-110: Public "Verify a Seller" Page
- **Status:** `NEW` (Belongs to Workflow 4, Step 4.5 & Workflow 10, Step 10.1)
- **Goal:** Provide an unalterable, publicly shareable verification page where international buyers can authenticate an Ethiopian exporter's legal credentials.
- **Primary Action:** Tap "Download Verification Certificate" or "Share Audit Link".
- **Entry Points:** Scanned from physical QR code, link from Lot detail, `/verify-seller/{slug}`.
- **Exits:** `S-014` Marketplace (View Lots), `S-114` Seller Public Profile.
- **Who Sees It:** All users (Public, International Buyers, Customs Officials).
- **Layout (Top to Bottom):**
  - **Official Verification Header:** Ethiopian coffee seal graphic, "Official Fira Link Compliance Verification".
  - **Verified Exporter Seal:** Animated golden/emerald verified badge.
  - **Company Identity Card:**
    - Legal Entity Name: `[Company Legal Name]`
    - Business Type: `Licensed Coffee Exporter`
    - ECTA License Number: `[ECTA/EXP/2026/049]`
    - Commercial Reg Number: `[MoTI-AA-123456]`
    - Verification Tier: `Tier 1 Verified (Legal & Regulatory Standing)`
    - Valid Period: `[Jan 1, 2026 to Dec 31, 2026]`
    - Status: `Active & Enforced 🟢`
  - **Compliance Audit Proof:**
    - SHA-256 Cryptographic Record Hash: `[3f8a...9c2e]`
    - Inspected Facility Address: `[Addis Ababa / Hawassa Industrial Zone]`
  - **Bottom Actions:**
    - `ElevatedButton(text: 'View Exporter Published Lots')`
    - `OutlinedButton(text: 'Share Verification Report')`
- **Content & Placeholders:**
  - Company: `[Company Legal Name]`
  - Hash: `[Cryptographic Audit Hash]`
- **States:** Verified (Green), Revoked / Suspended (Red banner with warning), Expired (Amber banner).
- **Validation & Edge Cases:** Read-only public page; works completely without user login.
- **Backend Dependency:** Endpoint exists (`GET /api/verify-seller/{slug}`).
- **Open Questions for Product Owner:** Should the ECTA database API be queried in real time, or periodically cached in Laravel? (Cached hourly with live fallback).

---

## 4. Lot Publishing & Evidence Levels Phase

---

### S-016: Create Coffee Lot Screen
- **Status:** `MODIFY` (Belongs to Workflow 5, Step 5.1)
- **Goal:** Enable verified exporters to publish exportable green coffee lots with verified physical specs, evidence levels, and ECTA minimum price compliance.
- **Primary Action:** Tap "Publish Verified Lot".
- **Entry Points:** `S-020` Seller Portal ("Add New Lot" FAB).
- **Exits:** `S-111` EvidenceLevelSelector, `S-112` LotPhotoCapture, `S-020` Seller Portal.
- **Who Sees It:** Verified Exporters with active lot quota.
- **Layout (Top to Bottom):**
  - **App Bar:** Back arrow, title "Publish Export Lot", Draft action.
  - **Section 1: Trust & Evidence Banner (Existing Component: `Container` radius 14):**
    - Selected Evidence Level pill (`[Lab-Backed 🟢]`), "Change Evidence Tier ▾" (opens S-111).
  - **Section 2: Origin & Traceability Form (Existing Component: `InputDecorationTheme`):**
    - Lot Title (`[e.g. Guji Hambela Bedessa Natural G1]`)
    - Origin Region Dropdown (`Sidama`, `Yirgacheffe`, `Guji`, `Limu`, `Harrar`, `Jimma`)
    - Sub-region / Woreda (`[Hambela Wamena]`)
    - Washing Station / Estate (`[Bedessa Wet Mill]`)
    - Variety (`[74110 / 74112 Heirloom]`)
    - Crop Harvest Year Dropdown (`[2025/2026 Harvest]`)
  - **Section 3: Quality Specifications:**
    - Processing Method Dropdown (`Washed`, `Natural`, `Anaerobic`, `Honey`)
    - Grade Dropdown (`Grade 1`, `Grade 2`, `Grade 3`, `Grade 4`)
    - SCA Cupping Score (`[88.5]`)
    - Moisture Content % (`[10.5%]`)
    - Water Activity (`[0.55 aw]`)
  - **Section 4: Volume & Packaging:**
    - Total Quantity Bags (`[320 Bags (1 x 20ft FCL)]`)
    - Bag Weight (`60 kg`)
    - Packaging Type Dropdown (`GrainPro + Jute`, `Vacuum Pack`, `Standard Jute`)
  - **Section 5: Pricing & ECTA Benchmark:**
    - Indicative FOB Price USD/lb (`[$4.20]`)
    - Real-time Benchmark Badge: "ECTA Minimum FOB for this grade: $3.85/lb. Your price is compliant."
  - **Section 6: Photos & Sensory:**
    - Photo upload slots (Green beans, Parchment, Bag tags) -> opens S-112.
    - Tasting Notes chips (`Peach, Jasmine, Citrus, Bergamot`).
  - **Bottom Action:** `ElevatedButton(text: 'Publish Verified Lot')`.
- **Content & Placeholders:**
  - Title Hint: "e.g. Yirgacheffe Chelchele Washed Grade 1"
  - Bags Hint: "320"
  - Price Hint: "4.20"
  - Button: "Publish Verified Lot"
- **States:** Default form, Submitting spinner, Price non-compliant warning (if below ECTA floor), Quota limit reached modal.
- **Validation & Edge Cases:** If FOB price is lower than the official daily ECTA rate, display blocking warning: "Price cannot be lower than ECTA minimum price ($[price]/lb) per Ethiopian trade regulations."
- **Backend Dependency:** Endpoint exists (`POST /api/marketplace/seller/listings`).
- **Open Questions for Product Owner:** Can an exporter save an unfinished lot as a private draft without consuming active lot quota? (Yes, drafts do not count toward quota).

---

### S-111: Evidence Level Selector & Lab Uploader
- **Status:** `NEW` (Belongs to Workflow 5, Step 5.2)
- **Goal:** Guide exporters in categorizing their coffee lot into one of 4 trust evidence levels and enforce uploading certified lab cupping reports for higher tiers.
- **Primary Action:** Tap "Confirm Evidence Level & Attach Certificate".
- **Entry Points:** `S-016` CreateListingScreen.
- **Exits:** Returns to `S-016`.
- **Who Sees It:** Verified Exporters publishing lots.
- **Layout (Top to Bottom):**
  - **Modal Header:** Drag handle, title "Select Lot Evidence Level".
  - **Evidence Tier Cards (Needs New Component: `EvidenceTierCard`):**
    - **Tier 1: Claimed (Bronze):** Description: "Seller self-declared specs. No third-party documentation attached. Standard buyer vetting applies."
    - **Tier 2: Documented (Silver):** Description: "Backed by washing station delivery receipts, origin traceability records, or warehouse weight certificates." (Requires PDF).
    - **Tier 3: Lab-Backed (Gold / Purple Highlight):** Description: "Backed by an accredited Q-Grader cupping score sheet or ECTA cupping certificate detailing SCA sensory attributes." (Requires Q-Grader report PDF).
    - **Tier 4: Inspected (Emerald):** Description: "Verified on-site by a third-party inspection agency (e.g. SGS or Bureau Veritas) with sealed sample retainment." (Requires Inspection cert).
  - **Certificate Attachment Box:**
    - "Attach Accredited Cupping Report / Inspection PDF" -> invokes `S-108`.
  - **Bottom Action:** `ElevatedButton(text: 'Confirm Evidence Level')`.
- **Content & Placeholders:**
  - Tier 3: "Lab-Backed (Recommended for Specialty Coffee)"
  - Button: "Confirm Evidence Level"
- **States:** Interactive radio selection; selecting Tier 2+ disables confirm button until document is attached.
- **Validation & Edge Cases:** Enforces PDF file attachment for Tiers 2, 3, and 4 before allowing confirmation.
- **Backend Dependency:** Endpoint needs change (Add `evidence_level` and `evidence_document_path` columns to `coffee_listings`).
- **Open Questions for Product Owner:** Should lots with higher evidence levels receive higher search ranking priority? (Yes, search algorithm prioritizes Lab-Backed and Inspected lots).

---

### S-112: In-App Lot Photo Capture Screen
- **Status:** `NEW` (Belongs to Workflow 5, Step 5.3)
- **Goal:** Prevent fraudulent listings using downloaded web photos by requiring live camera captures of green coffee beans, parchment, and warehouse bags with origin timestamps.
- **Primary Action:** Capture 3 required photos and tap "Attach to Lot".
- **Entry Points:** `S-016` CreateListingScreen.
- **Exits:** Returns to `S-016`.
- **Who Sees It:** Exporters publishing coffee lots.
- **Layout (Top to Bottom):**
  - **Top Step Counter:** "Photo 1 of 3: Green Coffee Beans (Close-up)".
  - **Camera Viewfinder:** High-resolution live feed with sample lighting guide.
  - **Photo Requirements Checklist:**
    - Photo 1: Green Beans in Hand or Tray (Clean close-up showing screen size & moisture).
    - Photo 2: Stacked Jute Bags at Warehouse with Lot Marking Tags.
    - Photo 3: Moisture Meter Screen or Cupping Lab Sample.
  - **Captured Thumbnails Row:** Horizontal strip of 3 slots displaying captured photos with checkmarks.
  - **Bottom Shutter:** Shutter button and "Confirm All Photos" CTA.
- **Content & Placeholders:**
  - Guide: "Ensure grain markings and bag tags are clearly legible."
  - Button: "Attach Photos to Lot"
- **States:** Live camera, Photo review/retake, All 3 captured state.
- **Validation & Edge Cases:** Requires at least 2 verified live camera photos before lot publishing.
- **Backend Dependency:** Endpoint exists (`POST /api/marketplace/seller/listings` multipart media).
- **Open Questions for Product Owner:** None.

---

### S-015: Coffee Lot Detail Screen (Enhanced with Evidence & Benchmark)
- **Status:** `MODIFY` (Belongs to Workflow 5, Step 5.4 & Workflow 6, Step 6.2)
- **Goal:** Provide international buyers with transparent, verified lot specifications, cupping notes, official ECTA benchmark rates, exporter credentials, and direct action triggers (Sample, RFQ, Inquire).
- **Primary Action:** Tap "Request Coffee Sample" or "Send Inquiry".
- **Entry Points:** `S-014` Marketplace Home, `S-046` Wishlist, Search results.
- **Exits:** `S-118` SampleRequestForm, `S-114` SellerProfile, `S-043` InAppChat.
- **Who Sees It:** All users (Guest, Buyer, Exporter).
- **Layout (Top to Bottom):**
  - **Collapsible App Bar (Existing Component: `SliverAppBar`):**
    - High-res photo gallery carousel (3-5 photos) with back button and Bookmark button.
  - **Section 1: Badges Header:**
    - Row: Origin Pill (`Guji`), Evidence Level Badge (`★ Lab-Backed Tier 3`), SCA Score Badge (`88.75 SCA`), Grade (`Grade 1`).
  - **Section 2: Lot Title & Indicative Pricing:**
    - `Text('[Lot Title]', style: typography.titleLarge)`
    - Indicative FOB: `Text('$4.20 / lb ($9,259 / MT)', style: typography.headlineMedium)`
    - Available Volume: `320 Bags (19.2 MT) • GrainPro + Jute`
  - **Section 3: Regulatory Benchmark Card (Existing Component: `Container` radius 14):**
    - "ECTA Official Benchmark: Daily Minimum FOB: $3.85/lb. This lot is +$0.35 specialty premium over regulatory floor."
  - **Section 4: Sensory Profile & Flavor Notes (Existing Component: `TopicChips`):**
    - Chips: `Peach`, `Jasmine`, `Bergamot`, `Wild Honey`, `Crisp Meyer Lemon`.
    - Physical specs: Moisture: `10.4%`, Water Activity: `0.54 aw`, Screen size: `15/17`.
  - **Section 5: Exporter Trust Card (Needs New Component: `ExporterCompactCard`):**
    - Exporter Logo/Avatar, Company Legal Name (`[Company Name]`), Tier 1 Verified Badge (`🟢`), Completed Shipments count (`14 Shipments`), "View Full Profile & Licenses ➔".
  - **Section 6: Verified Evidence Documents:**
    - File preview tile: "Accredited Q-Grader Cupping Report (PDF, 1.4 MB)" + "View Report" button.
  - **Bottom Fixed Action Bar (Existing Component: `Container` with shadow):**
    - Left: Bookmark icon button.
    - Center: `OutlinedButton(text: 'Request Sample')` -> opens S-118.
    - Right: `ElevatedButton(text: 'Contact Exporter')` -> opens S-043.
- **Content & Placeholders:**
  - Lot: `[Lot Title]`
  - Price: `[$ Price] / lb`
  - Seller: `[Company Legal Name]`
- **States:** Loading indicator, Not found state, Inactive/Expired lot banner.
- **Validation & Edge Cases:** If guest taps "Request Sample", show `VerificationPromptDialog` explaining that coffee sample evaluation requires an activated buyer account.
- **Backend Dependency:** Endpoint exists (`GET /api/marketplace/listings/{id}`).
- **Open Questions for Product Owner:** Should buyers be allowed to download the full Q-grader lab report PDF directly without logging in? (Yes, transparency builds buyer intent).

---

### S-113: Lot Lifecycle Management Screen
- **Status:** `NEW` (Belongs to Workflow 5, Step 5.5)
- **Goal:** Enable exporters to manage their published lots: pause visibility, mark as under negotiation, update bag counts, or renew expiring listings.
- **Primary Action:** Tap "Update Lot Status".
- **Entry Points:** `S-020` Seller Portal (Lot item context menu).
- **Exits:** Returns to `S-020`.
- **Who Sees It:** Exporters who own the lot.
- **Layout (Top to Bottom):**
  - **App Bar:** Back arrow, title "Manage Lot: [Lot Name]".
  - **Current Status Banner:** "Active on Marketplace • Expires in 34 days".
  - **Status Selector (Existing Component: `RadioListTile`):**
    - `Active`: Visible to all global buyers.
    - `Under Negotiation`: Temporarily reserved during active contract negotiation.
    - `Paused`: Hidden from search; does not consume active lot quota.
    - `Sold Out`: Archived as completed trade record.
  - **Inventory Adjustment Form:**
    - Remaining Bags counter (`[Remaining Bags] / [Total Initial Bags]`).
  - **Expiry & Renewal Card:**
    - 90-day auto-expiry policy. "Renew for 90 Days" button.
  - **Bottom Actions:**
    - `ElevatedButton(text: 'Save Lot Status')`
    - `TextButton(text: 'Delete Lot Listing', color: Colors.red)`
- **Content & Placeholders:**
  - Lot: `[Lot Name]`
  - Remaining: `[Count] Bags Available`
- **States:** Default form, Submitting spinner, Success toast.
- **Validation & Edge Cases:** Deleting a lot that is linked to an active open Deal Room is prohibited; system warns user to resolve deal room first.
- **Backend Dependency:** Endpoint needs change (`PUT /api/marketplace/seller/listings/{id}/status`).
- **Open Questions for Product Owner:** None.

---

## 5. Buyer Discovery & Procurement RFQs Phase

---

### S-014: Marketplace Home Screen (Refined Search & Filters)
- **Status:** `MODIFY` (Belongs to Workflow 6, Step 6.1)
- **Goal:** Enable global buyers to browse, search, and filter verified Ethiopian coffee lots with multi-facet filters.
- **Primary Action:** Search keywords or tap lot card to inspect.
- **Entry Points:** Shell Tab 2 (B2B Marketplace).
- **Exits:** `S-015` LotDetail, `S-018` RFQs, `S-020` SellerPortal, `S-022` ExportDocsStudio.
- **Who Sees It:** All users (Guest, Buyer, Exporter).
- **Layout (Top to Bottom):**
  - **App Bar:** Title "Coffee Marketplace", action icons: RFQs list (`receipt_long`), Seller Portal (`storefront`).
  - **Search & Filter Bar (Existing Component: `HomeSearchBar`):**
    - Search text field with filter action icon opening multi-facet filter bottom sheet.
  - **ECTA Compliance Ticker Banner (Existing Component: `Container` radius 14):**
    - "ECTA Minimum Price Compliant: All contracts benchmarked with daily official Ethiopian rates."
  - **Export Docs Studio Promo Pill:** Link to 30 free legal export templates.
  - **Horizontal Origin Filter Pills (Existing Component: `TopicChips`):** `All Origins`, `Sidama`, `Yirgacheffe`, `Guji`, `Limu`, `Harrar`, `Jimma`.
  - **Processing Filter Pills:** `All Processes`, `Washed`, `Natural`, `Anaerobic`, `Honey`.
  - **Listings List (Existing Component: `MarketplaceLotCard`):** Vertical list of lot cards with SCA scores, prices, evidence badges, and bag counts.
  - **Floating Action Button:** `FloatingActionButton.extended(icon: Icons.add_shopping_cart, label: 'Post RFQ')`.
- **Content & Placeholders:**
  - Search Hint: "Search lots, washing stations, origins..."
  - Card 1: "[Lot Title] • [Origin Region]"
- **States:** Loading indicator, Empty search results ("No lots found matching filters"), Pull-to-refresh.
- **Validation & Edge Cases:** Tapping "Post RFQ" checks authentication. If guest, displays `VerificationPromptDialog.showAuthRequired`.
- **Backend Dependency:** Endpoint exists (`GET /api/marketplace/listings`).
- **Open Questions for Product Owner:** None.

---

### S-114: Exporter Public Profile Screen
- **Status:** `NEW` (Belongs to Workflow 6, Step 6.2)
- **Goal:** Provide buyers with an institutional view of an exporter's verified track record, facilities, certifications, and active catalog before committing funds.
- **Primary Action:** Tap "Inquire / Contact Exporter" or "Browse Active Lots".
- **Entry Points:** `S-015` Lot Detail (Seller name tap), Search results.
- **Exits:** `S-015` Lot Detail, `S-043` Chat, `S-110` Public Verification.
- **Who Sees It:** All users (Buyers, Public).
- **Layout (Top to Bottom):**
  - **App Bar:** Back arrow, Share profile icon, Bookmark exporter icon.
  - **Company Hero Header (Existing Component: `Container` radius 24):**
    - Exporter Logo, Legal Company Name (`[Company Legal Name]`), Founded Year, Location (`Addis Ababa & Hawassa, Ethiopia`).
    - Verification Badge (`Tier 1 Verified 🟢`), "View Legal Audit Proof ➔" (opens S-110).
  - **Trade Performance Metrics (Needs New Component: `TradeMetricCounter`):**
    - Row: `14` Completed Export Shipments • `100%` Inspection Pass Rate • `4.9 ★` (18 Buyer Reviews) • `<48h` Sample Dispatch.
  - **Company Overview & Sourcing Regions:**
    - Bio describing washing stations owned, processing capabilities, and export destinations.
    - Sourcing Regions tags: `Guji`, `Yirgacheffe`, `Sidama`.
  - **Accreditations & Certificates Carousel:**
    - ECTA License, Organic USDA, Fairtrade, Rainforest Alliance certificates.
  - **Active Coffee Lots Section (Existing Component: `MarketplaceLotCard`):**
    - Grid/List of all currently available lots from this exporter.
  - **Bottom Action Bar:**
    - `ElevatedButton(icon: Icons.chat_outlined, text: 'Inquire with Exporter')`
- **Content & Placeholders:**
  - Company: `[Company Legal Name]`
  - Bio: `[Company Trade Overview]`
- **States:** Default profile, Loading spinner, No active lots empty state.
- **Validation & Edge Cases:** Verified badges are strictly dynamically computed from backend compliance records.
- **Backend Dependency:** Endpoint exists (`GET /api/marketplace/sellers/{id}`).
- **Open Questions for Product Owner:** None.

---

### S-018: Marketplace RFQs & Tenders Screen
- **Status:** `MODIFY` (Belongs to Workflow 7, Step 7.1)
- **Goal:** Display global coffee procurement tenders posted by international buyers, allowing exporters to bid and buyers to manage their RFQs.
- **Primary Action:** Tap tender card to inspect or quote; tap "Post RFQ" to create.
- **Entry Points:** `S-014` Marketplace Home (AppBar RFQ icon).
- **Exits:** `S-019` CreateRfq, `S-115` RfqDetail, `S-116` ExporterQuoteBuilder.
- **Who Sees It:** All users.
- **Layout (Top to Bottom):**
  - **App Bar:** Back arrow, title "Buyer Procurement Tenders", action: My RFQs.
  - **Filter Segment Bar (Existing Component: `TabBar`):** `Global Open Tenders`, `My Posted RFQs`.
  - **Filter Chips:** `All Origins`, `Destination: Europe`, `Destination: Asia`, `Destination: North America`.
  - **Tender Cards List (Needs New Component: `RfqTenderCard` using `Card` radius 20):**
    - Card: Buyer Organization (`[Tokyo Craft Roasters]`), Origin Required (`Yirgacheffe G1 Washed`), Volume (`320 Bags / 1 FCL`), Target FOB (`$4.50/lb`), Destination (`Yokohama Port, Japan`), Bidding Closes (`In 5 days`).
    - Received Bids Badge: `4 Exporter Quotes Received`.
    - Action button: "Quote Tender" (for exporters) or "Compare Bids" (for owning buyer).
  - **Floating Action Button:** `FloatingActionButton.extended(icon: Icons.add, label: 'Post RFQ')`.
- **Content & Placeholders:**
  - Tender: `[Volume] Bags of [Origin] [Grade]`
  - Buyer: `[Buyer Company / Country]`
- **States:** Loading indicator, Empty list ("No open tenders matching filters"), Refresh.
- **Validation & Edge Cases:** Tapping "Quote Tender" checks exporter verification and quota. If quote limit is reached, trigger `QuotaLimitSheet`.
- **Backend Dependency:** Endpoint exists (`GET /api/marketplace/rfqs`).
- **Open Questions for Product Owner:** None.

---

### S-019: Create Buyer Procurement RFQ Screen
- **Status:** `MODIFY` (Belongs to Workflow 7, Step 7.2)
- **Goal:** Enable international roasters and green coffee buyers to publish structured procurement tenders for verified Ethiopian exporters to bid on.
- **Primary Action:** Tap "Publish Procurement Tender".
- **Entry Points:** `S-018` RFQs Screen, `S-007` Home Action.
- **Exits:** Returns to `S-018`.
- **Who Sees It:** Authenticated Buyers.
- **Layout (Top to Bottom):**
  - **App Bar:** Back arrow, title "Post Coffee Procurement Tender".
  - **Form Fields (Existing Component: `InputDecorationTheme`):**
    - Tender Title (`[e.g. Sourcing 1 FCL Guji Natural Grade 1]`)
    - Required Origin Region Dropdown (`Guji`, `Yirgacheffe`, `Sidama`, etc.)
    - Required Processing (`Washed`, `Natural`, `Any`)
    - Minimum SCA Cupping Score (`[e.g. 87.0]`)
    - Volume Required Bags (`[320 Bags]`)
    - Target FOB Price USD/lb (`[$4.30]`)
    - Delivery Window / Shipment Month Dropdown (`[March - April 2026]`)
    - Destination Port (`[Hamburg Port, Germany]`)
    - Preferred Incoterm Dropdown (`FOB Djibouti`, `CIF Hamburg`)
    - Payment Terms Preference Dropdown (`Irrevocable LC at Sight`, `CAD`, `Advance Deposit`)
    - Quality & Sample Instructions TextArea
  - **Bottom Action:** `ElevatedButton(text: 'Publish Procurement Tender')`.
- **Content & Placeholders:**
  - Title Hint: "e.g. Sourcing 320 Bags Yirgacheffe Washed G1"
  - Port Hint: "e.g. Kobe Port, Japan"
  - Button: "Publish Procurement Tender"
- **States:** Default form, Submitting spinner, Validation error toast.
- **Validation & Edge Cases:** Validates volume >=10 bags and valid target price >$0. Free for international buyers.
- **Backend Dependency:** Endpoint exists (`POST /api/marketplace/rfqs`).
- **Open Questions for Product Owner:** None.

---

### S-115: RFQ Detail & Quotation Comparison Screen
- **Status:** `NEW` (Belongs to Workflow 7, Step 7.3)
- **Goal:** Enable buyers to evaluate competing exporter bids side-by-side, analyze FOB price breakdowns, and accept winning quotations to start Deal Rooms.
- **Primary Action:** Tap "Accept Quotation & Open Deal Room".
- **Entry Points:** `S-018` RFQs Screen (tapping tender card).
- **Exits:** `S-117` DealRoomHub, `S-043` Chat.
- **Who Sees It:** The buyer who created the RFQ (or bidding exporters viewing their bid).
- **Layout (Top to Bottom):**
  - **App Bar:** Back arrow, title "Tender Details & Bids".
  - **Tender Summary Card (Existing Component: `Container` radius 18):**
    - Title, Origin, Volume (`320 Bags`), Destination Port, Target Price, Bidding Status (`Open • Closes in 3 days`).
  - **Quotation Comparison Header:**
    - `Text('Received Exporter Bids ([Count])', style: typography.titleLarge)`
  - **Comparison Cards List (Needs New Component: `QuotationBidCard` using `Card` radius 18):**
    - Exporter Header: Exporter Logo, Legal Name (`[Exporter Name]`), Tier Badge (`Tier 1 Verified 🟢`).
    - Bid Metrics Row: Price USD/lb (`$4.25/lb`), Total FOB Value (`$81,600`), Sample Dispatch Time (`3 Days`), Bid Validity (`14 Days`).
    - Payment Terms: `100% LC at Sight via Commercial Bank of Ethiopia`.
    - Seller Notes & Linked Lot Card (if quoted from existing inventory).
    - Actions Row:
      - `OutlinedButton(text: 'Message Exporter')` -> opens S-043.
      - `ElevatedButton(text: 'Accept Bid')` -> opens S-117 Deal Room confirmation.
  - **Bottom Action:** "Close Tender" (if buyer desires no further bids).
- **Content & Placeholders:**
  - Tender: `[Volume] Bags [Origin]`
  - Bid Price: `[$ Price] / lb`
  - Button: "Accept Bid & Open Deal Room"
- **States:** Default, Empty bids state ("No quotes received yet. Exporters are reviewing your tender."), Bid accepted state.
- **Validation & Edge Cases:** Accepting a quotation automatically marks the RFQ as awarded and prompts creation of a digital Deal Room (`S-117`).
- **Backend Dependency:** Endpoint exists (`GET /api/marketplace/rfqs/{id}/quotes`, `PUT .../quotes/{id}/status`).
- **Open Questions for Product Owner:** Does accepting one bid automatically decline all other competing bids, or keep them as backups? (Declines others with polite automated notification).

---

### S-116: Exporter Quotation Builder Screen
- **Status:** `NEW` (Belongs to Workflow 7, Step 7.4)
- **Goal:** Provide exporters with a professional bid builder that calculates FOB margins, validates against ECTA minimum prices, and submits formal contract offers.
- **Primary Action:** Tap "Submit Official Quotation".
- **Entry Points:** `S-018` RFQs Screen ("Quote Tender" button).
- **Exits:** Returns to `S-018`.
- **Who Sees It:** Verified Exporters quoting on open buyer tenders.
- **Layout (Top to Bottom):**
  - **App Bar:** Back arrow, title "Submit Quotation".
  - **Tender Reference Banner:** Shows Buyer Name, Volume (`320 Bags`), Target Price (`$4.30/lb`), Destination (`Hamburg`).
  - **Quote Form (Existing Component: `InputDecorationTheme`):**
    - Offered FOB Price USD/lb (`[$4.25]`)
    - Real-time ECTA Regulatory Validation: "ECTA floor is $3.85/lb. Your quotation is compliant."
    - Total Gross Contract Value: `$81,600 USD`
    - Volume Offered Bags (`[320 Bags]`)
    - Incoterm Dropdown (`FOB Djibouti`, `CIF Hamburg`)
    - Payment Terms Dropdown (`Irrevocable LC at Sight`, `CAD`, `30% Advance / 70% LC`)
    - Sample Availability Toggle: "Sample available for immediate courier dispatch"
    - Bid Validity Days Dropdown (`7 Days`, `14 Days`, `30 Days`)
    - Exporter Notes & Commercial Terms TextArea
  - **Bottom Action:** `ElevatedButton(text: 'Submit Official Quotation')`.
- **Content & Placeholders:**
  - Price Hint: "4.25"
  - Total: "$81,600 USD"
  - Button: "Submit Official Quotation"
- **States:** Default form, Submitting spinner, Below-ECTA error warning (disables submit).
- **Validation & Edge Cases:** Gated by SaaS entitlement `rfq_responses_per_month`. If quota is exhausted, show `QuotaLimitSheet`.
- **Backend Dependency:** Endpoint exists (`POST /api/marketplace/rfqs/{id}/quote`).
- **Open Questions for Product Owner:** None.

---

## 6. Samples & Deal Room Closing Phase

---

### S-118: Sample Request & Shipping Form Screen
- **Status:** `NEW` (Belongs to Workflow 8, Step 8.1)
- **Goal:** Standardize green coffee sample orders, capturing courier account details (or courier fee payment) and delivery addresses to prevent unfulfilled sample requests.
- **Primary Action:** Tap "Submit Sample Request".
- **Entry Points:** `S-015` CoffeeLotDetail ("Request Sample" button).
- **Exits:** `S-119` SampleTrackingScreen.
- **Who Sees It:** Authenticated Buyers.
- **Layout (Top to Bottom):**
  - **App Bar:** Back arrow, title "Order Coffee Sample".
  - **Sample Item Card (Existing Component: `Container` radius 16):**
    - Thumbnail, Lot Title (`[Guji Hambela Natural G1]`), Exporter Legal Name (`[Exporter Name]`).
  - **Sample Configuration:**
    - Sample Quantity Radio: `250 grams (Standard Cupping)` / `500 grams (Roast Profile & Cupping)`.
  - **Shipping & Courier Options:**
    - Option 1: "Use my DHL / FedEx Account" -> TextField: `Courier Account Number`.
    - Option 2: "Pay Courier Air Freight ($45 USD via Chapa/Card)".
  - **Delivery Destination Address (Existing Component: `InputDecorationTheme`):**
    - Company Name, Street Address, City, Postal Code, Country, Recipient Phone Number.
  - **Cupping Lab Notes:**
    - Optional notes for exporter cupping team.
  - **Anti-Fraud Notice:** "A sealed counter-sample will be retained by the exporter under cryptographic seal."
  - **Bottom Action:** `ElevatedButton(text: 'Submit Sample Request')`.
- **Content & Placeholders:**
  - Address Hint: "Company, Street, City, Country"
  - Account Hint: "e.g. DHL 961234567"
  - Button: "Submit Sample Request"
- **States:** Default form, Submitting spinner, Payment redirect (if paying freight).
- **Validation & Edge Cases:** Requires complete address and valid phone number for courier waybill generation.
- **Backend Dependency:** Endpoint needs change (Expand `POST /api/marketplace/listings/{id}/inquire` to accept structured sample models).
- **Open Questions for Product Owner:** Should exporters be allowed to set sample fees for expensive microlots (>90 SCA)? (Yes, optional sample price).

---

### S-119: Sample Tracking & Chain of Custody Screen
- **Status:** `NEW` (Belongs to Workflow 8, Step 8.2)
- **Goal:** Track the physical journey of green coffee samples from warehouse dispatch to buyer delivery, logging courier waybills and retained counter-sample seal numbers.
- **Primary Action:** Tap "Track Courier Parcel" or "Record Cupping Evaluation".
- **Entry Points:** `S-015` LotDetail, `S-041` Notifications, `S-035` Profile.
- **Exits:** `S-120` SampleCuppingApproval, External Courier Tracking.
- **Who Sees It:** Buyer and Exporter.
- **Layout (Top to Bottom):**
  - **App Bar:** Back arrow, title "Sample Order #[ID]".
  - **Header Status Card (Existing Component: `Container` radius 20):**
    - Status: `In Transit via DHL Express ✈️`
    - Estimated Delivery: `[Date, e.g. Friday, Nov 14]`
    - Waybill Number: `[DHL-7890123456]` with "Copy" and "Track Live" buttons.
  - **Chain of Custody Timeline (Needs New Component: `TimelineStepView`):**
    - Step 1: Sample Requested by Buyer (`Nov 8, 14:00 • Completed 🟢`).
    - Step 2: Parcel Packed & Retained Counter-Sample Sealed (`Nov 9, 10:30 • Completed 🟢`):
      - Retained Seal Number: `SEAL-ETH-2026-9041`
      - Exporter Sample Photo attached.
    - Step 3: Courier Pickup in Addis Ababa (`Nov 10, 16:00 • Completed 🟢`).
    - Step 4: Customs Clearance in Transit (`In Progress 🟣`).
    - Step 5: Delivered to Buyer Cupping Lab (`Upcoming ⚪`).
    - Step 6: Buyer Cupping Approval (`Upcoming ⚪`).
  - **Bottom Action (when delivered):** `ElevatedButton(text: 'Record Cupping Results & Evaluate')` -> opens S-120.
- **Content & Placeholders:**
  - Tracking: `[Courier Name] - [Tracking Number]`
  - Seal: `SEAL-ETH-[Year]-[Number]`
  - Button: "Record Cupping Results"
- **States:** Requested, Dispatched, In Transit, Delivered, Evaluated.
- **Validation & Edge Cases:** If courier marks delivered, app sends push notification to buyer with CTA to cup sample within 7 days.
- **Backend Dependency:** Endpoint missing (`GET /api/marketplace/samples/{id}`, `PUT .../tracking`).
- **Open Questions for Product Owner:** Can we integrate DHL/FedEx tracking APIs directly for automated status webhooks? (Recommended for Phase 2).

---

### S-120: Sample Cupping Lab Approval Screen
- **Status:** `NEW` (Belongs to Workflow 8, Step 8.3)
- **Goal:** Allow international buyers to record official cupping evaluation results, approve or reject the sample, and bind the approved quality seal to future contract negotiations.
- **Primary Action:** Tap "Approve Sample & Initiate Contract".
- **Entry Points:** `S-119` SampleTrackingScreen.
- **Exits:** `S-117` DealRoomHub (if approved), `S-014` Marketplace (if rejected).
- **Who Sees It:** Authenticated Buyers evaluating samples.
- **Layout (Top to Bottom):**
  - **App Bar:** Back arrow, title "Sample Cupping Evaluation".
  - **Lot Reference:** `[Lot Title]` • Exporter: `[Company Legal Name]` • Seal: `SEAL-ETH-2026-9041`.
  - **Cupping Score Input (Existing Component: `InputDecorationTheme`):**
    - Overall Cupping Score: `[88.5]`
    - Attribute Sliders: Fragrance/Aroma, Flavor, Aftertaste, Acidity, Body, Balance, Clean Cup, Uniformity, Sweetness.
  - **Sensory Notes Selector:**
    - Chips: `Floral`, `Jasmine`, `Stone Fruit`, `Blackberry`, `Citric Acidity`.
  - **Decision Radio Cards:**
    - Option A (Green): "Approve Sample: Meets or exceeds our procurement standards. Proceed to export contract."
    - Option B (Red): "Reject Sample: Quality does not meet requirements." (Reason dropdown: Defect cup, Past crop fade, Low acidity).
  - **Bottom Action:**
    - If Approved: `ElevatedButton(backgroundColor: success, text: 'Approve Quality & Start Deal Room')`.
    - If Rejected: `OutlinedButton(text: 'Submit Rejection Feedback')`.
- **Content & Placeholders:**
  - Score: `88.5 SCA`
  - Button: "Approve Quality & Start Deal Room"
- **States:** Default form, Submitting evaluation.
- **Validation & Edge Cases:** Approval locks the sample seal number into the deal room record, guaranteeing legal recourse if bulk delivery differs.
- **Backend Dependency:** Endpoint missing (`POST /api/marketplace/samples/{id}/evaluate`).
- **Open Questions for Product Owner:** None.

---

### S-117: Digital Deal Room Hub Screen
- **Status:** `NEW` (Belongs to Workflow 9, Step 9.1)
- **Goal:** Provide a shared, auditable commercial workspace where buyer and exporter finalize contracts, monitor trade finance payments, track pre-shipment inspections, and release container shipping documents.
- **Primary Action:** Review next active deal milestone and take required action.
- **Entry Points:** `S-120` SampleApproval, `S-115` RFQQuoteAcceptance, `S-035` Profile.
- **Exits:** `S-121` Contract, `S-122` PaymentMilestones, `S-123` Inspection, `S-124` ShippingDocs, `S-126` Disputes.
- **Who Sees It:** Verified Buyer and Verified Exporter participating in the deal.
- **Layout (Top to Bottom):**
  - **App Bar:** Back arrow, title "Deal Room #[Contract ID]", action: Dispute button (`Icons.gavel`).
  - **Deal Summary Card (Existing Component: `Container` radius 20, purple gradient):**
    - Contract Title: `320 Bags Guji Grade 1 Natural`
    - Total Contract Value: `$81,600 USD (FOB Djibouti)`
    - Parties: Exporter `[Company Name]` ⇄ Buyer `[Tokyo Craft Roasters]`
    - Current Active Milestone: `Milestone 2 of 5: Letter of Credit Verification`
  - **Milestone Progress Bar (Existing Component: `LinearProgressIndicator`):** 40% complete.
  - **Deal Milestones Step Cards (Needs New Component: `DealMilestoneCard` using `Card` radius 18):**
    - **Milestone 1: Sales Contract & E-Signatures** (`Completed 🟢` • Signed by both parties) -> opens S-121.
    - **Milestone 2: Payment Milestone / Trade Finance** (`Active 🟣` • Awaiting LC confirmation) -> opens S-122.
    - **Milestone 3: Dry Mill Processing & Pre-Shipment Inspection** (`Locked 🔒`) -> opens S-123.
    - **Milestone 4: Railway Logistics & Bill of Lading Release** (`Locked 🔒`) -> opens S-124.
    - **Milestone 5: Settlement & Final Closeout** (`Locked 🔒`) -> opens S-125.
  - **Deal Activity & Audit Feed:**
    - Live ledger: "Nov 11: Export sales contract countersigned by buyer."
  - **Bottom Action Bar:**
    - `OutlinedButton(icon: Icons.chat, text: 'Deal Chat')` -> opens scoped deal chat.
    - `ElevatedButton(text: 'Complete Active Milestone')`.
- **Content & Placeholders:**
  - Deal: `[Contract Code] - [Volume] Bags [Origin]`
  - Value: `$ [Amount] USD`
- **States:** Active, Milestone pending, In Dispute (freezes funds/releases), Completed & Archived.
- **Validation & Edge Cases:** Open deal rooms NEVER expire or get locked on subscription downgrade (per Invariant Rule 3).
- **Backend Dependency:** Endpoint missing (`GET /api/deal-rooms/{id}`, `POST .../milestones`).
- **Open Questions for Product Owner:** Does Fira Link handle escrow payments directly? (No, per architecture: trade finance is managed via LC / commercial banks; Fira Link validates documentation milestones).

---

### S-121: Export Contract & E-Signature Screen
- **Status:** `NEW` (Belongs to Workflow 9, Step 9.2)
- **Goal:** Render the institutional export sales contract (FOB/CIF) and capture legal e-signatures from both authorized signatories.
- **Primary Action:** Tap "Review & E-Sign Export Contract".
- **Entry Points:** `S-117` DealRoomHub (Milestone 1).
- **Exits:** Returns to `S-117`.
- **Who Sees It:** Authorized representatives of Buyer and Exporter.
- **Layout (Top to Bottom):**
  - **App Bar:** Back arrow, title "Export Sales Contract", Download PDF icon.
  - **Contract Document Viewer:**
    - Scrollable legal contract text: Parties, Commodity specs, Approved Sample Seal Hash (`SEAL-ETH-2026-9041`), Total Bags & MT, Incoterms, Price per lb & total value, Payment terms (LC at Sight), Port of Loading (Djibouti), Governing law (Ethiopian Trade Directives / ECF arbitration).
  - **Signatures Status Card:**
    - Exporter Signatory: `Signed by [Exporter Rep] on [Date] 🟢`
    - Buyer Signatory: `Pending Signature ⚪`
  - **E-Signature Pad Modal (Needs New Component: `SignatureCanvas`):**
    - Canvas drawing box, "Clear", "Adopt Typed Signature", "Sign Legally".
  - **Bottom Action:** `ElevatedButton(text: 'Sign Contract with Legal Consent')`.
- **Content & Placeholders:**
  - Contract: `CONTRACT-FL-ETH-[Year]-[Number]`
  - Button: "Sign Contract with Legal Consent"
- **States:** Default review, Signing mode, Dual-signed success state with cryptographic timestamp.
- **Validation & Edge Cases:** Unlocks Milestone 2 only after both parties have executed valid e-signatures.
- **Backend Dependency:** Endpoint partial (`CoffeeExportService` generates DomPDF contracts; e-signature storage needed).
- **Open Questions for Product Owner:** Does Ethiopian law recognize touch-drawn digital signatures, or is SMS OTP dual-factor authorization required? (SMS OTP dual-factor confirmation recommended).

---

### S-122: Payment Milestones & LC Tracker Screen
- **Status:** `NEW` (Belongs to Workflow 9, Step 9.3)
- **Goal:** Track international trade finance milestones (Letter of Credit, Advance Deposit, CAD) and verify SWIFT MT103 / LC documentary copies.
- **Primary Action:** Upload or confirm SWIFT LC document.
- **Entry Points:** `S-117` DealRoomHub (Milestone 2).
- **Exits:** Returns to `S-117`.
- **Who Sees It:** Buyer and Exporter trade finance officers.
- **Layout (Top to Bottom):**
  - **App Bar:** Back arrow, title "Trade Payment & LC Milestone".
  - **Payment Terms Card:**
    - Payment Method: `Irrevocable Confirmed Letter of Credit at Sight (LC)`
    - Total Due: `$81,600.00 USD`
    - Issuing Bank: `[e.g. Deutsche Bank Frankfurt]`
    - Advising / Beneficiary Bank: `Commercial Bank of Ethiopia (CBE), Addis Ababa`
  - **Milestone Progress Checklist:**
    - Sub-step 1: LC Draft Issued & Reviewed by Exporter (`Completed 🟢`).
    - Sub-step 2: Operative LC Opened & SWIFT MT700 Transmitted (`Active 🟣`).
    - Sub-step 3: CBE Confirms Operative LC Arrival (`Upcoming ⚪`).
  - **Document Attachment Slot:**
    - "Upload SWIFT MT700 / MT103 Copy (PDF)" -> invokes `S-108`.
  - **Confirmation Action:** "Confirm LC Receipt & Unlock Processing".
  - **Anti-Fraud Warning:** "Fira Link staff and systems never ask for payment to private accounts. Always confirm bank SWIFT codes directly with your bank."
  - **Bottom Action:** `ElevatedButton(text: 'Confirm Payment Milestone')`.
- **Content & Placeholders:**
  - Amount: `$81,600.00 USD`
  - Bank: `Commercial Bank of Ethiopia`
- **States:** Awaiting document, Document uploaded, Confirmed by both parties.
- **Validation & Edge Cases:** Both buyer and exporter must confirm LC verification before unlocking pre-shipment inspection.
- **Backend Dependency:** Endpoint missing (`POST /api/deal-rooms/{id}/milestones/payment`).
- **Open Questions for Product Owner:** None.

---

### S-123: Pre-Shipment Inspection & Container Stuffing Screen
- **Status:** `NEW` (Belongs to Workflow 9, Step 9.4)
- **Goal:** Provide documented proof of coffee processing, ECTA pre-export cupping clearance, and container stuffing at the Addis Ababa dry mill.
- **Primary Action:** Upload inspection report and container photos.
- **Entry Points:** `S-117` DealRoomHub (Milestone 3).
- **Exits:** Returns to `S-117`.
- **Who Sees It:** Exporter (uploader) and Buyer (reviewer).
- **Layout (Top to Bottom):**
  - **App Bar:** Back arrow, title "Pre-Shipment & Stuffing".
  - **Processing & Quality Verification:**
    - Milling Station: `[Mill Name, Addis Ababa]`
    - Final Moisture Reading: `10.2%` (Must be between 9.5% and 11.5%)
    - ECTA Export Quality Inspection Certificate: `[PDF attached 🟢]`
  - **Container Stuffing Details:**
    - Container Number: `[MSKU-9876543]`
    - High-Security Seal Number: `[ETH-SEAL-8812]`
    - Bag Count: `320 Bags verified`
  - **Stuffing Photos Gallery:**
    - Photo 1: Dry container floor & kraft paper lining.
    - Photo 2: GrainPro inner bags stacked.
    - Photo 3: Closed container doors with official customs seal.
  - **Bottom Action:** `ElevatedButton(text: 'Approve Inspection & Clear for Dispatch')`.
- **Content & Placeholders:**
  - Container: `[Container Number]`
  - Seal: `[High-Security Seal No]`
- **States:** In progress, Submitted, Buyer approved.
- **Validation & Edge Cases:** Validates moisture reading is within legal export limits before clearing.
- **Backend Dependency:** Endpoint missing (`POST /api/deal-rooms/{id}/milestones/inspection`).
- **Open Questions for Product Owner:** None.

---

### S-124: Shipping Logistics & Document Release Screen
- **Status:** `NEW` (Belongs to Workflow 9, Step 9.5)
- **Goal:** Track inland railway transit (Addis to Djibouti), ocean vessel departure, and upload copies of the Bill of Lading, Phytosanitary, and Origin certificates.
- **Primary Action:** Upload Bill of Lading and track vessel.
- **Entry Points:** `S-117` DealRoomHub (Milestone 4).
- **Exits:** Returns to `S-117`.
- **Who Sees It:** Exporter and Buyer.
- **Layout (Top to Bottom):**
  - **App Bar:** Back arrow, title "Shipping Logistics & Documents".
  - **Transit Pipeline Card:**
    - Inland Rail: Addis Ababa (Indode) -> Port of Djibouti (`Completed 🟢`).
    - Port Handling: Doraleh Container Terminal (`Loaded on Vessel 🟢`).
    - Vessel Name: `[CMA CGM PEGASUS / Voyage 2601]`
    - Port of Destination: `Hamburg Port, Germany (ETA: Dec 12, 2026)`
  - **Vaulted Export Documents (Existing Component: `ExportDocsStudio` integration):**
    - 1. Original Bill of Lading (B/L) [PDF preview]
    - 2. Phytosanitary Certificate (Ministry of Agriculture) [PDF preview]
    - 3. Certificate of Origin (Chamber of Commerce) [PDF preview]
    - 4. Commercial Invoice & Packing List [PDF preview]
    - 5. Weight & Quality Certificate [PDF preview]
  - **Courier Tracking for Original Negotiable Documents:**
    - "Original documents dispatched to buyer's bank via DHL Express [DHL-Waybill-No]".
  - **Bottom Action:** `ElevatedButton(text: 'Confirm Document Dispatch')`.
- **Content & Placeholders:**
  - Vessel: `[Vessel Name]`
  - ETA: `[Estimated Arrival Date]`
- **States:** Documents in transit, Vaulted PDF previews available.
- **Validation & Edge Cases:** All 5 mandatory export documents must be present before completing this milestone.
- **Backend Dependency:** Endpoint partial (`ExportDocsService` handles document storage).
- **Open Questions for Product Owner:** None.

---

### S-125: Trade Settlement & Closeout Screen
- **Status:** `NEW` (Belongs to Workflow 9, Step 9.6)
- **Goal:** Formalize deal completion upon container arrival, capture reciprocal trade reviews, and archive the completed transaction to the immutable audit ledger.
- **Primary Action:** Tap "Confirm Cargo Receipt & Close Deal".
- **Entry Points:** `S-117` DealRoomHub (Milestone 5).
- **Exits:** `S-007` Home, `S-035` Profile.
- **Who Sees It:** Buyer and Exporter.
- **Layout (Top to Bottom):**
  - **Success Celebration Banner:** Animated emerald checkmark, "Deal Completed Successfully!".
  - **Trade Summary Card:**
    - Volume: `320 Bags (19.2 MT) Guji Washed G1`
    - Settlement: `$81,600.00 USD fully released by bank`
    - Lead Time: `38 Days from Contract to Port Arrival`
  - **Reciprocal Trade Review Form:**
    - Star Rating (1 to 5 Stars)
    - Review Categories: Quality Consistency, Communication, Packaging & Labeling, Documentation Accuracy.
    - Public Review Comments TextArea.
  - **Immutable Audit Record Hash:**
    - SHA-256 Contract & Settlement Proof for customs and tax authorities.
  - **Bottom Action:** `ElevatedButton(text: 'Submit Review & Return to Home')`.
- **Content & Placeholders:**
  - Deal: `[Deal ID] - [Volume] MT`
  - Button: "Submit Review & Finish"
- **States:** Default closeout, Review submitted.
- **Validation & Edge Cases:** Automatically increments exporter's "Completed Shipments" count and updates platform trust score.
- **Backend Dependency:** Endpoint missing (`POST /api/deal-rooms/{id}/complete`).
- **Open Questions for Product Owner:** None.

---

### S-126: Trade Dispute Resolution Room Screen
- **Status:** `NEW` (Belongs to Workflow 9, Step 9.7)
- **Goal:** Provide a formal legal dispute channel when quality, weight, or logistics fail, comparing delivered coffee against the sealed retained counter-sample.
- **Primary Action:** Submit dispute claim with evidence.
- **Entry Points:** `S-117` DealRoomHub (Dispute button tap).
- **Exits:** Returns to `S-117`.
- **Who Sees It:** Buyer, Exporter, and Platform Arbitrators.
- **Layout (Top to Bottom):**
  - **App Bar:** Back arrow, title "Trade Dispute Resolution", Warning icon.
  - **Dispute Warning Banner (Existing Component: `Container` radius 14, amber tint):**
    - "Official Arbitration: Submitting a claim freezes milestone payouts and initiates formal evidence examination."
  - **Dispute Category Selector:**
    - `Quality Defect / Cupping Score Discrepancy`
    - `Weight Shortage / Moisture Damage`
    - `Delayed Shipment Exceeding Contract Grace Period`
    - `Non-Delivery / Missing Documents`
  - **Evidence Submission Box:**
    - Claimed Defect Details
    - Attached Independent Surveyor Inspection Report (SGS/Bureau Veritas)
    - Sealed Retained Counter-Sample Comparison Request: `SEAL-ETH-2026-9041`
  - **Arbitration Timeline:**
    - 1. Claim Filed ➔ 2. Exporter Rebuttal (72h) ➔ 3. Retained Sample Cupping at National Coffee Lab ➔ 4. Final Binding Decision.
  - **Bottom Action:** `ElevatedButton(backgroundColor: danger, text: 'File Formal Trade Dispute')`.
- **Content & Placeholders:**
  - Claim: "File Formal Trade Dispute"
- **States:** Dispute filing form, Under Arbitration review, Adjudicated resolution.
- **Validation & Edge Cases:** Once a dispute is filed, milestone transitions in S-117 are locked until the arbitrator issues a ruling.
- **Backend Dependency:** Endpoint missing (`POST /api/deal-rooms/{id}/dispute`).
- **Open Questions for Product Owner:** Does Fira Link charge an arbitration filing fee to prevent frivolous claims? (Standard $250 fee recommended, refundable to prevailing party).

---

## 7. Trust & Safety, Scam Warnings & Reporting Phase

---

### S-128: Report Listing or User Modal
- **Status:** `NEW` (Belongs to Workflow 10, Step 10.2)
- **Goal:** Empower buyers and exporters to flag fraudulent listings, stolen licenses, or scam solicitations immediately.
- **Primary Action:** Tap "Submit Fraud Report".
- **Entry Points:** Context menu on any Lot card, Seller profile, or Chat thread.
- **Exits:** Closes modal.
- **Who Sees It:** All authenticated users.
- **Layout (Top to Bottom):**
  - **Modal Header:** Title "Report Listing or User", Shield alert icon.
  - **Report Reason Radio Group:**
    - `Suspected Fraud / Phishing / Scam`
    - `Unlicensed Broker Posing as Exporter`
    - `Soliciting Off-Platform Cash Wire`
    - `Forged Cupping Certificate or ECTA License`
    - `Misrepresented Origin / Defective Cargo`
  - **Explanation TextArea:** `[Describe specific details]`
  - **Evidence Attachment:** Upload screenshots or document scans.
  - **Bottom Action:** `ElevatedButton(backgroundColor: danger, text: 'Submit Report for Compliance Review')`.
- **Content & Placeholders:**
  - Reason: `[Selected Reason]`
  - Button: "Submit Report"
- **States:** Default form, Submitting spinner, Success confirmation.
- **Validation & Edge Cases:** Dispatches high-priority notification to Fira Link compliance team; auto-flags listing if 3 independent reports are received.
- **Backend Dependency:** Endpoint missing (`POST /api/reports`).
- **Open Questions for Product Owner:** None.

---

### S-129: Off-Platform Scam Warning Sheet
- **Status:** `NEW` (Belongs to Workflow 10, Step 10.3)
- **Goal:** Intercept users attempting to exchange direct cash wire instructions or unauthorized bank accounts in chat, warning them of trade fraud hazards.
- **Primary Action:** Tap "I Understand the Risks" to proceed or "Cancel & Return to Secure Deal Room".
- **Entry Points:** Automatically triggered when regex detects bank SWIFT, IBAN, WhatsApp numbers, or wire terms in chat or message inputs.
- **Exits:** Returns to Chat or dismisses.
- **Who Sees It:** Chat participants.
- **Layout (Top to Bottom):**
  - **Modal Header:** Red alert shield icon, title "Security & Anti-Fraud Notice".
  - **Warning Callout:**
    - "Never send unverified wire transfers or Western Union payments to individual accounts."
    - "Scammers frequently pose as licensed exporters and request deposits outside official Letters of Credit."
    - "Transactions conducted off-platform forfeit all Fira Link dispute protection, sample verification guarantees, and legal arbitration."
  - **Safe Alternative:** "Use Fira Link Digital Deal Rooms for verified Letter of Credit and bank-guaranteed milestones."
  - **Bottom Actions:**
    - `ElevatedButton(text: 'Return to Secure Deal Room (Recommended)')`
    - `TextButton(text: 'I acknowledge the risks and take full liability')`
- **Content & Placeholders:**
  - Header: "Security & Anti-Fraud Notice"
- **States:** Default modal.
- **Validation & Edge Cases:** Logs the acknowledgment event in user audit trail for risk scoring.
- **Backend Dependency:** Client-side regex trigger + audit log endpoint (`POST /api/security/log-warning`).
- **Open Questions for Product Owner:** None.

---

### S-130: Suspended / Expired License State Screen
- **Status:** `NEW` (Belongs to Workflow 10, Step 10.4)
- **Goal:** Inform an exporter whose ECTA export license has expired or been suspended that new listings and quotes are restricted while providing a clear appeal path.
- **Primary Action:** Tap "Renew License with ECTA" or "Submit Appeal".
- **Entry Points:** Attempting to publish lot or quote with expired verification.
- **Exits:** `S-021` Verification Dossier.
- **Who Sees It:** Exporters with expired or suspended compliance status.
- **Layout (Top to Bottom):**
  - **Hero Illustration:** Red warning padlock icon with ambient glow.
  - **Title Section:**
    - `Text('Export License Suspended or Expired', style: typography.headlineLarge)`
    - `Text('Your ECTA competence certificate expired on [Date]. Under Ethiopian trade law, active coffee exports require valid annual renewal.', style: typography.bodyLarge, color: mutedText)`
  - **Impact Notice:**
    - Live listings paused.
    - Incoming quotes disabled.
    - Active open Deal Rooms remain fully operational (Invariant Rule 3).
  - **Action Steps:**
    - Step 1: Renew certificate with Ethiopian Coffee & Tea Authority.
    - Step 2: Upload new certified license to Fira Link.
  - **Bottom Action:** `ElevatedButton(text: 'Upload Renewed License')` -> opens S-021.
- **Content & Placeholders:**
  - Date: `[License Expiry Date]`
  - Button: "Upload Renewed License"
- **States:** Expired vs Suspended.
- **Validation & Edge Cases:** Guarantees that open deals, documents, and chat are NEVER locked out.
- **Backend Dependency:** Endpoint exists (`GET /api/marketplace/verification/status`).
- **Open Questions for Product Owner:** None.

---

## 8. Billing, Payments & Settings Phase

---

### S-132: Unified Checkout & Bank Slip Upload Screen
- **Status:** `NEW` (Belongs to Workflow 15, Step 15.2)
- **Goal:** Provide seamless payment checkout supporting both instant Chapa gateway payments (Telebirr, CBE Birr, Cards) and corporate Ethiopian bank transfers (CBE slip upload).
- **Primary Action:** Tap "Complete Payment" or "Upload Bank Transfer Slip".
- **Entry Points:** `S-023` MarketplacePricing, `S-012` CourseCheckout, `S-028` EventCheckout.
- **Exits:** Payment Success screen, S-039 Payment History.
- **Who Sees It:** Authenticated users making payments.
- **Layout (Top to Bottom):**
  - **App Bar:** Back arrow, title "Secure Checkout", SSL padlock.
  - **Order Summary Card (Existing Component: `Container` radius 18):**
    - Item: `[Plan / Course / Event Name]`
    - Net Total: `[Amount ETB / USD]`
  - **Payment Method Selector (Existing Component: `RadioListTile`):**
    - Option 1: **Chapa Instant Checkout** (Telebirr, CBE Birr, Visa, Mastercard).
    - Option 2: **Offline Commercial Bank of Ethiopia (CBE) Transfer**.
  - **CBE Bank Account Details (if Option 2 selected):**
    - Account Name: `Fira Link Technologies PLC`
    - Account Number: `1000 1234 5678 9012`
    - Bank Branch: `Addis Ababa Main Branch`
    - Transfer Reference Code: `[Unique Pay Code, e.g. FL-PAY-9812]`
  - **Deposit Slip Upload Box:**
    - "Attach Bank Deposit Slip / Mobile Screenshot" -> invokes `S-108`.
  - **Bottom Action:**
    - If Chapa: `ElevatedButton(text: 'Pay with Chapa (Instant)')`.
    - If Offline Bank: `ElevatedButton(text: 'Submit Transfer Slip for Review')`.
- **Content & Placeholders:**
  - Item: `[Item Name]`
  - Total: `[Amount] ETB`
  - Account: `1000 1234 5678 9012`
- **States:** Default selector, Processing payment, Slip uploaded awaiting admin review.
- **Validation & Edge Cases:** Enforces unique transfer reference code on manual slips to prevent duplicate deposit submissions.
- **Backend Dependency:** Endpoint exists (`PaymentService` & `CourseEnrollmentController::chapaVerify`).
- **Open Questions for Product Owner:** How fast are manual bank slips reviewed? (Within 2-4 hours during business days).

---

### S-133: Compliance Document Vault Screen
- **Status:** `NEW` (Belongs to Workflow 17, Step 17.4)
- **Goal:** Provide organization managers with a single secure archive of all legal export certificates, tax clearance forms, and trade agreements with expiry countdowns.
- **Primary Action:** Tap document to preview, share, or replace.
- **Entry Points:** `S-035` Profile Screen ("Compliance Document Vault" menu).
- **Exits:** PDF Viewer, `S-108` DocumentScanner.
- **Who Sees It:** Authenticated Organization Members.
- **Layout (Top to Bottom):**
  - **App Bar:** Back arrow, title "Document Vault", Search icon.
  - **Vault Security Badge:** "AES-256 Encrypted Private Storage • Accessible only by authorized organization members."
  - **Categories TabBar:** `Legal Licenses`, `Tax & TIN`, `ECTA Certs`, `Lab Reports`, `Contracts`.
  - **Documents List (Existing Component: `ListTile` in Card radius 16):**
    - Item: PDF Icon, Document Title (`ECTA Coffee Export License 2026`), Uploaded Date, Expiry Date (`Expires in 62 days 🟢`), Download button, Context menu (Replace, Delete).
  - **Expiring Soon Warning Card (if <30 days):**
    - Amber card: "TIN Tax Clearance expires in 12 days. Tap to upload renewal."
  - **Bottom Action:** `ElevatedButton(icon: Icons.upload_file, text: 'Vault New Document')`.
- **Content & Placeholders:**
  - Doc: `[Document Name]`
  - Expiry: `[Expiry Date]`
- **States:** Loading indicator, Empty vault state, Expiring warnings.
- **Validation & Edge Cases:** All documents are non-public; downloads require signed Sanctum authorization tokens.
- **Backend Dependency:** Endpoint partial (`ExportDocsService::documents`).
- **Open Questions for Product Owner:** None.

---

### S-131: Fira AI Calculation & Tool Result Card
- **Status:** `NEW` (Belongs to Workflow 12, Step 12.2)
- **Goal:** Render rich, interactive calculation summaries and regulatory checklists directly inside AI chat messages.
- **Primary Action:** Tap "Open in Full Tool" or "Export PDF".
- **Entry Points:** Embedded inside `S-024` AiChatScreen message bubbles.
- **Exits:** `S-017A` ProfitCalculator, `S-022A` DocEditor.
- **Who Sees It:** All users chatting with Fira AI.
- **Layout (Inside Chat Message Bubble):**
  - **Card Container (Existing Component: `Container` radius 18, dark/light surface):**
    - Header: AI Tool Badge (`FOB Export Margin Estimate 🤖`), Sourced Badge (`Verified ECTA Daily Rate: $3.85/lb`).
    - Metric Grid:
      - Farmgate Cherry Cost: `120 ETB/kg`
      - Processing & Washing: `25 ETB/kg`
      - Inland Freight to Djibouti: `$3,200 USD`
      - Breakeven FOB: `$3.42 / lb`
      - Projected Net Margin: `+$0.78 / lb (18.5%)`
    - Action Buttons Row:
      - `OutlinedButton(text: 'Export PDF Report')`
      - `ElevatedButton(text: 'Open in FOB Calculator')` -> opens S-017A with pre-filled inputs.
- **Content & Placeholders:**
  - Metric: `[$ Value]`
  - Button: "Open in Full Tool"
- **States:** Interactive embedded widget within chat stream.
- **Validation & Edge Cases:** Always marks AI calculation outputs with an explicit disclaimer: "Estimate based on current tariffs. Verify with freight forwarder."
- **Backend Dependency:** Endpoint exists (`POST /api/fira-ai/send`).
- **Open Questions for Product Owner:** None.

