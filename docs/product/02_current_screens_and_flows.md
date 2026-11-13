# Fira Link Mobile App: Current Screens Inventory & Workflow Map

**File Reference:** Part B of the Product Design & Engineering Architecture Specification  
**App Version:** 1.0.0+1 (`firalink flutter`)  
**Backend:** Laravel 12 API (`firalink web`)  

---

## 1. Complete Inventory of Existing Screens

Each screen is documented in the logical order a new user encounters them, from launch through tab roots, sub-modules, and tool suites.

| ID | Screen Name | File Path & Widget Class | Entry Points | Exits | Roles | Main Sections (Top to Bottom) | Data Displayed | States Handled | API / Mock Wiring |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **S-001** | Splash Screen | `lib/screens/splash/splash_screen.dart`<br>`SplashScreen` | App initial cold launch | Auto-navigates to S-002 or S-003 after 2600ms timer | All (Guest) | 1. Animated Logo (Scale/Fade)<br>2. App Name & Slogan<br>3. Pulse indicator | App branding & version | Default only | Fully local |
| **S-002** | Splash Wrapper (Router) | `lib/screens/splash/splash_wrapper.dart`<br>`SplashWrapper` | Main root widget | S-003 (Onboarding) if first run, S-007 (Home) if returning or authenticated | All | Transparent router wrapper checking `SharedPreferences` & `AuthProvider` | Auth & onboarding state | Default | Local SharedPreferences |
| **S-003** | Onboarding Carousel | `lib/screens/onboarding/onboarding_screen.dart`<br>`OnboardingScreen` | S-002 (first launch) | S-007 (Home) via "Get Started" or "Skip" | All (Guest) | 1. Top brand emblem & Skip button<br>2. 4-step illustration carousel<br>3. Category badge & title with accent<br>4. Description<br>5. Page indicator & Next/Complete CTA | 4 promotional feature cards (Trade, AI Docs, Safaris, Summits) | Default | Fully local assets |
| **S-004** | Auth Screen (Login / Register) | `lib/screens/auth/auth_screen.dart`<br>`AuthScreen` | Profile (S-035), Gated Actions (RFQ, Sample, Post Lot, Enroll) | S-005 (OTP Verify), S-006 (Forgot Password), S-007 (Home) | Guest | 1. Brand logo header<br>2. Segmented TabBar (`Login` / `Sign Up`)<br>3. Login form (Email, Password, Remember Me, Submit)<br>4. Register form (Names, Email, Phone, Passwords, Submit) | User credentials & input validation | Loading, Error toasts | Real API: `POST /api/auth/login`, `POST /api/auth/register` |
| **S-005** | OTP Verification | `lib/screens/auth/otp_verification_screen.dart`<br>`OtpVerificationScreen` | S-004 (after registration or phone login) | S-007 (Home) on success, S-004 (Back) | Unverified User | 1. Header with back button<br>2. Masked phone number text<br>3. 6-digit PIN input fields with auto-focus<br>4. Resend code countdown timer (60s)<br>5. Verify CTA button | Masked phone `+251 91***45` | Loading, Error toasts | Real API: `POST /api/auth/register/verify`, `POST /api/auth/register/resend` |
| **S-006** | Forgot Password | `lib/screens/auth/forgot_password_screen.dart`<br>`ForgotPasswordScreen` | S-004 ("Forgot Password?" tap) | Reset Password screen or S-004 | Guest | 1. Header<br>2. Phone/Email input<br>3. Send OTP CTA | Reset instructions | Loading, Error toasts | Real API: `POST /api/auth/password/forgot/otp` |
| **S-007** | Home Screen | `lib/screens/home/home_screen.dart`<br>`HomeScreen` | Shell Tab 0, S-003, S-005 | S-010 (Courses), S-014 (Marketplace), S-017 (Toolkit), S-024 (AI Chat) | All | 1. Header (Avatar, greeting, bell)<br>2. Primary Action / Resume Learning card<br>3. Live Class banner<br>4. Market pulse ticker<br>5. Toolkit Bento grid<br>6. Search bar & Topic chips<br>7. Recommended courses horizontal list<br>8. Master bundles | Live prices, recent courses, user progress, upcoming live class | Loading skeletons, Error state, Pull-to-refresh | Real API: `GET /api/home`, `GET /api/courses/recent`, `GET /api/bundles` |
| **S-008** | Home 2 (Alternative Layout) | `lib/screens/home/home2_screen.dart`<br>`Home2Screen` | Route `/home2` (Testing/legacy) | Courses, Categories | All | 1. Legacy search<br>2. Grid categories<br>3. Course carousels | Courses and category listings | Loading skeletons | Real API: `GET /api/home` |
| **S-009** | Main Shell & Dock | `lib/screens/shell/main_shell_screen.dart`<br>`MainShellScreen` | App navigation root | Manages Tabs 0 to 5 & Orb Quick Actions dialog | All | 1. `IndexedStack` holding 6 screens<br>2. Floating `FiraBottomDock` with center Orb | Active tab index & badge counts | Default | Fully local state |
| **S-010** | Courses Screen (Academy Root) | `lib/screens/courses/courses_screen.dart`<br>`CoursesScreen` | Shell Tab 1, Home "See All" | S-011 (Course Detail), Filter sheet | All | 1. Search bar & Filter button<br>2. Category pills<br>3. Vertical Course Cards list with pull-to-refresh | Paginated course list, ratings, prices | Loading skeleton, Empty, Error, Pagination | Real API: `GET /api/courses` |
| **S-011** | Course Detail | `lib/screens/course_detail/course_detail_screen.dart`<br>`CourseDetailScreen` | S-010, Home cards, Wishlist | S-012 (Checkout), S-013 (Player if enrolled) | All | 1. Video trailer / Thumbnail<br>2. Title, instructor, rating, price<br>3. Curriculum expansion list (Sections/Lessons)<br>4. About & Requirements<br>5. Reviews list<br>6. Bottom Enroll/Buy CTA | Course metadata, syllabus, video preview, reviews | Loading skeleton, Error | Real API: `GET /api/courses/{id}` |
| **S-012** | Course Checkout & Payment | `lib/screens/course_detail/checkout_screen.dart`<br>`CheckoutScreen` | S-011 ("Enroll" on paid course) | S-013 (Success), Webview (Chapa) | Authenticated | 1. Order summary card<br>2. Payment method selector (Chapa, Telebirr, CBE Birr, Offline Bank)<br>3. Coupon input field<br>4. Pay CTA button | Course price, coupon discount, net total | Loading, Processing | Real API: `POST /api/courses/{id}/enroll`, Chapa Gateway |
| **S-013** | Course Access (LMS Player) | `lib/screens/course_access/course_access_screen.dart`<br>`CourseAccessScreen` | S-011 (when enrolled), Dashboard | S-030 (Quiz), S-031 (Assignment), S-032 (Discussion) | Enrolled Student | 1. Embedded Video Player (Chewie/YouTube)<br>2. Lesson title & navigation buttons<br>3. TabBar (`Curriculum`, `Resources`, `Discussion`, `Notes`)<br>4. Complete & Next CTA | Lesson video streams, attachments, discussion threads | Loading, Offline caching, Error | Real API: `GET /api/student/courses/{id}/learn` |
| **S-014** | Marketplace Home (Trade Root) | `lib/screens/marketplace/marketplace_home_screen.dart`<br>`MarketplaceHomeScreen` | Shell Tab 2 | S-015 (Lot Detail), S-018 (RFQs), S-020 (Seller Portal), S-022 (Export Docs) | All | 1. AppBar with RFQs & Seller portal icons<br>2. Search bar<br>3. ECTA compliance banner<br>4. Export Docs Studio promo banner<br>5. Quick Hub chips<br>6. Origin chips & Processing method chips<br>7. Coffee Lots list with SCA score badges | Coffee lots, indicative FOB prices, SCA scores, bags, origins | Loading indicator, Empty state, Refresh | Hybrid: `GET /api/marketplace/listings` with fallback to `MarketplaceDemoData` |
| **S-015** | Coffee Lot Detail | `lib/screens/marketplace/coffee_listing_detail_screen.dart`<br>`CoffeeListingDetailScreen` | S-014, Wishlist | Bottom Sheet Sample Request, Inquiry dialog | All | 1. Collapsible image gallery<br>2. Origin, SCA score, Grade, Process pills<br>3. Indicative FOB price & ECTA benchmark box<br>4. Sensory flavor profile & Tasting notes chips<br>5. Verified Exporter credentials box<br>6. Bottom action bar: Bookmark, Sample Request, Inquire | Lot specs, cupping notes, official benchmark, seller profile | Loading, Error, NotFound | Hybrid: `GET /api/marketplace/listings/{id}` with fallback |
| **S-016** | Create Coffee Lot | `lib/screens/marketplace/create_listing_screen.dart`<br>`CreateListingScreen` | S-020 (Seller Portal "Add Lot") | S-020 (on success), S-021 (Verification) | Verified Exporter | 1. Form header<br>2. Lot Title, Origin, Sub-region, Washing Station<br>3. Variety, Processing dropdown, Grade dropdown<br>4. Cupping score, Available bags, FOB Price/lb<br>5. Tasting notes comma-separated input<br>6. Submit lot CTA | Form fields & quota indicators | Loading, Quota reached modal | Real API / Mock fallback: `POST /api/marketplace/seller/listings` |
| **S-017** | Coffee Toolkit Hub | `lib/screens/coffee_toolkit/coffee_toolkit_hub_screen.dart`<br>`CoffeeToolkitHubScreen` | Shell Orb, Home Bento, Profile | S-017A (FOB Calc), S-017B (Offer Gen), S-017C (Readiness), S-017D (Market) | All | 1. Header with overview description<br>2. 4 major tool feature cards with icons & metrics<br>3. Regulatory disclosure banner | Tool directory & saved calculation counts | Default | Local navigation |
| **S-017A**| FOB & Profit Calculator | `lib/screens/coffee_toolkit/coffee_profit_calculator_screen.dart`<br>`CoffeeProfitCalculatorScreen` | S-017, Orb Quick Action | PDF Export, Save calculation | All | 1. Input parameters (Volume bags, Farmgate cherry ETB/kg, Processing cost, Transport to Addis & Djibouti, Port handling, Overhead)<br>2. Real-time breakdown card (Total Cost ETB, Breakeven FOB USD/lb, Profit margin)<br>3. Save & Share | Dynamic calculation results | Interactive calculation | Real API: `POST /api/coffee-toolkit/calculate` |
| **S-017B**| Formal Offer Sheet Generator | `lib/screens/coffee_toolkit/coffee_offer_generator_screen.dart`<br>`CoffeeOfferGeneratorScreen` | S-017, Orb Quick Action | PDF viewer / share | Authenticated | 1. Exporter profile selector<br>2. Lot details & Grade<br>3. Incoterm (FOB Djibouti, CIF)<br>4. Payment terms & Validity period<br>5. "Generate Institutional PDF" CTA | Offer sheet preview & PDF link | Loading, Error | Real API: `POST /api/coffee-toolkit/offers`, `GET .../pdf` |
| **S-017C**| Export Readiness Assessment | `lib/screens/coffee_toolkit/coffee_readiness_screen.dart`<br>`CoffeeReadinessScreen` | S-017 | Recommendations screen | All | 1. 10-question compliance questionnaire (ECTA license, cupping lab, warehouse, capital)<br>2. Calculate score CTA<br>3. Readiness Grade (0-100%) & Action Plan | Institutional grade & gap checklist | Interactive form | Real API: `POST /api/coffee-toolkit/assess` |
| **S-017D**| Daily Market Prices & Chart | `lib/screens/coffee_toolkit/coffee_market_screen.dart`<br>`CoffeeMarketScreen` | S-017, Marketplace Hub chip | Daily Price Detail | All | 1. Date selector<br>2. Daily ECTA minimum prices table by Grade/Origin<br>3. Candlestick trend chart<br>4. AI Market Synthesis summary | Daily minimum prices, ICE Arabica benchmark | Loading, Error | Real API: `GET /api/market-prices` |
| **S-018** | Buyer RFQs & Tenders | `lib/screens/marketplace/marketplace_rfqs_screen.dart`<br>`MarketplaceRfqsScreen` | S-014 (Marketplace AppBar icon) | S-019 (Create RFQ), Quote modal | All | 1. Header & Filter chips (Origin, Destination)<br>2. Active RFQ cards list (Buyer org, Volume, Target price, Delivery term, Expiry)<br>3. "Quote Tender" button per card | Open buyer tenders & specifications | Loading, Empty, Pull-to-refresh | Hybrid: `GET /api/marketplace/rfqs` with fallback |
| **S-019** | Create Buyer RFQ | `lib/screens/marketplace/create_rfq_screen.dart`<br>`CreateRfqScreen` | S-018 ("Post RFQ" FAB), Home Action | S-018 (on success) | Authenticated Buyer | 1. Sourcing requirements (Target origin, Grade, Process)<br>2. Quantity bags, Target FOB price USD/lb<br>3. Destination port & Incoterm<br>4. Special quality requirements<br>5. Submit RFQ CTA | Form inputs | Submitting, Error toasts | Hybrid: `POST /api/marketplace/rfqs` with fallback |
| **S-020** | Seller Portal | `lib/screens/marketplace/seller_portal_screen.dart`<br>`SellerPortalScreen` | S-014 (Storefront icon) | S-016 (Add Lot), S-021 (Verification), S-023 (Pricing) | Authenticated Exporter | 1. Seller profile & verification status card<br>2. Active quota usage meters (Active Lots, RFQ Quotes, Storage)<br>3. My Published Lots list with status pills<br>4. "Add New Lot" CTA | Exporter company info, quota metrics, published lots | Loading, Error, LimitReached | Real API / Mock fallback: `GET /api/marketplace/seller/profile`, `GET .../listings` |
| **S-021** | Organization Verification | `lib/screens/marketplace/organization_verification_screen.dart`<br>`OrganizationVerificationScreen` | S-020, Gated prompts | Document upload dialog | Authenticated | 1. Role selector tabs (`Exporter`, `Washing Station`, `Buyer`, `Broker`)<br>2. Status banner (Unsubmitted, In Review, Approved, Rejected)<br>3. TIN & License inputs<br>4. Required document checklist cards<br>5. Vault upload dialog trigger | Dossier fields, document upload status | Loading, Submitting, Error | Real API / Mock fallback: `GET /api/marketplace/verification/status`, `POST .../submit` |
| **S-022** | Export Docs Studio | `lib/screens/marketplace/export_docs_studio_screen.dart`<br>`ExportDocsStudioScreen` | S-014 banner, Orb Action | S-022A (Doc Editor), PDF Preview | Authenticated Exporter | 1. TabBar (`Templates (30)`, `Shipments`, `Calculator`, `Vault`)<br>2. Category filters<br>3. Template cards (Commercial Invoice, Packing List, Phyto, Origin Certificate)<br>4. Active shipments tracking | 30 document templates, shipment dossiers, vaulted files | Loading, Empty | Real API: `GET /api/export-docs/templates`, `GET .../shipments` |
| **S-022A**| Export Document Editor | `lib/screens/marketplace/export_doc_editor_screen.dart`<br>`ExportDocEditorScreen` | S-022 (Template card tap) | PDF Preview, Finalize document | Authenticated Exporter | 1. Document title & shipment link<br>2. Dynamic form fields based on template schema<br>3. Live total calculations<br>4. "Save Draft", "AI Autofill", "Finalize & Download" CTAs | Template JSON schema, input values | Loading, Saving, Error | Real API: `GET /api/export-docs/templates/{code}`, `POST .../documents` |
| **S-023** | Marketplace Pricing & Plans | `lib/screens/marketplace/marketplace_pricing_screen.dart`<br>`MarketplacePricingScreen` | S-020 (Quota upgrade), S-035 | Chapa Checkout | Authenticated | 1. Plan comparison carousel (`Starter`, `Growth`, `Enterprise`)<br>2. Quota features list (Lots, RFQs, AI Docs, Deal Rooms)<br>3. Trust is Never for Sale disclaimer<br>4. "Subscribe via Chapa" CTA | Plan names, prices in ETB/USD, entitlements | Loading, Error | Real API: `GET /api/marketplace/plans`, `POST .../subscribe` |
| **S-024** | Fira AI Chat Screen | `lib/screens/ai_chat/ai_chat_screen.dart`<br>`AiChatScreen` | Shell Orb, Home FAB, Profile | Document attachment, External links | All | 1. AppBar with clear chat action<br>2. Quick prompt pills (`Calculate profit`, `ECTA rules`, etc.)<br>3. Message bubbles list with Markdown & KaTeX rendering<br>4. Input bar with mic, camera/doc attach, send button | AI chat history, streaming responses | Loading bubble, Empty state, Error | Real API: `GET /api/fira-ai`, `POST /api/fira-ai/send` |
| **S-025** | Fira Document Scanner | `lib/screens/document_scanner/fira_document_scanner_screen.dart`<br>`FiraDocumentScannerScreen` | Shell Orb Action | Analysis result view | Authenticated | 1. Camera viewfinder / Gallery picker<br>2. Document crop boundary<br>3. OCR scanning progress overlay<br>4. Extracted fields review card (TIN, weights, seals) | Scanned image & extracted OCR key-value pairs | Processing, Error | Real API: `POST /api/fira-ai/analyze-document` |
| **S-026** | Events Screen (Tab 3 Root) | `lib/screens/events/events_screen.dart`<br>`EventsScreen` | Shell Tab 3 | S-027 (Event Detail) | All | 1. Search bar & Date filters<br>2. Featured summit banner<br>3. Upcoming Trade Forums list<br>4. Ticket price & Location pills | Event list, schedules, venues, ticket tiers | Loading skeleton, Empty, Error | Real API: `GET /api/events` |
| **S-027** | Event Detail & Booking | `lib/screens/events/event_detail_screen.dart`<br>`EventDetailScreen` | S-026 | S-028 (Ticket Checkout) | All | 1. Event banner image & Countdown<br>2. Title, Date, Venue (Addis Ababa Millennium Hall, etc.)<br>3. Speaker bios & Agenda schedule<br>4. Ticket tier selector (Standard, VIP, Exporter)<br>5. "Book Pass" CTA | Event agenda, speaker profiles, ticket pricing | Loading, Error | Real API: `GET /api/events/{id}` |
| **S-028** | Event / Tour Payment | `lib/screens/events/payment_screen.dart`<br>`PaymentScreen` | S-027, S-029 | S-028A (Confirmation) | Authenticated | 1. Booking summary<br>2. Payment method selector (Chapa, Telebirr, CBE Birr)<br>3. Pay CTA | Net amount ETB, transaction fee | Processing, Error | Real API: Chapa Gateway redirect / verify |
| **S-028A**| Booking Confirmation | `lib/screens/events/booking_confirmation_screen.dart`<br>`BookingConfirmationScreen` | S-028 (on payment success) | S-034 (My Bookings), S-007 | Authenticated | 1. Animated success checkmark<br>2. QR code ticket pass<br>3. Venue directions & Add to Calendar CTA<br>4. "View My Bookings" button | Ticket ID, QR data, attendee name, booking code | Default | Real API / Local data |
| **S-029** | Tours Screen (Tab 4 Root) | `lib/screens/tours/tours_screen.dart`<br>`ToursScreen` | Shell Tab 4 | S-029A (Tour Detail) | All | 1. Search bar & Region filters (Guji, Yirgacheffe, Sidama, Jimma)<br>2. Farm Safari cards with hero imagery<br>3. Duration, Group size, Inclusions pills | Agro-tour catalog, departure dates, pricing | Loading skeleton, Empty, Error | Real API: `GET /api/tours` |
| **S-029A**| Tour Detail & Booking | `lib/screens/tours/tour_detail_screen.dart`<br>`TourDetailScreen` | S-029 | S-028 (Payment) | All | 1. Hero photography carousel<br>2. Washing station & farm itinerary day-by-day<br>3. Cupping masterclass inclusions<br>4. Departure date picker & guest counter<br>5. "Reserve Expedition" CTA | Full itinerary, guide credentials, price | Loading, Error | Real API: `GET /api/tours/{id}` |
| **S-030** | Quiz Attempt Screen | `lib/screens/course_access/quiz_attempt_screen.dart`<br>`QuizAttemptScreen` | S-013 (Lesson Quiz tap) | S-013 (Result summary) | Enrolled Student | 1. Quiz timer countdown<br>2. Question index pills<br>3. Question prompt & multi-choice options<br>4. "Submit Answer" / "Finish Quiz" CTA | Quiz questions, options, passing score | Submitting, Error | Real API: `GET /api/student/quizzes/{id}`, `POST .../submit` |
| **S-031** | Assignment Submit Screen | `lib/screens/course_access/assignment_submit_screen.dart`<br>`AssignmentSubmitScreen` | S-013 (Assignment tap) | S-013 (on success) | Enrolled Student | 1. Assignment brief & rubrics<br>2. File attachment box (PDF, DOCX)<br>3. Text notes input<br>4. "Submit Assignment" CTA | Assignment instructions, deadlines | Uploading, Error | Real API: `POST /api/student/assignments/{id}/submit` |
| **S-032** | Course Discussion Detail | `lib/screens/course_access/course_discussion_detail_screen.dart`<br>`CourseDiscussionDetailScreen` | S-013 (Discussion thread tap) | S-013 | Enrolled Student | 1. Original question & author avatar<br>2. Replies list with like buttons<br>3. Bottom reply input field | Forum thread & replies | Loading, Submitting | Real API: `GET /api/student/discussions/{id}`, `POST .../reply` |
| **S-033** | Student Certificates Screen | `lib/screens/dashboard/certificates_screen.dart`<br>`CertificatesScreen` | S-035 (Profile), Dashboard | S-033A (View Certificate), S-033B (Verify) | Authenticated | 1. List of earned course certificates<br>2. Course title, issue date, credential ID<br>3. "Download PDF", "Share Credential" buttons | Certificate title, verification code, date | Loading, Empty, Error | Real API: `GET /api/student/certificates` |
| **S-033A**| Certificate View Screen | `lib/screens/dashboard/certificate_view_screen.dart`<br>`CertificateViewScreen` | S-033 | PDF Share / Download | Authenticated | 1. High-res certificate canvas preview<br>2. Verification QR code<br>3. Action buttons (Share, Download PDF) | Rendered certificate image/PDF | Loading | Real API: `GET /api/certificates/download/signed/{id}` |
| **S-033B**| Certificate Verification Screen | `lib/screens/dashboard/certificate_verification_screen.dart`<br>`CertificateVerificationScreen` | AppRouter `/verify-certificate` | Verification result | All | 1. Certificate code input field<br>2. "Verify Authenticity" CTA<br>3. Verification result card (Student name, course, verified date) | Public verification ledger record | Loading, Error | Real API: `POST /api/verify-certificate` |
| **S-034** | My Ticket Bookings | `lib/screens/dashboard/my_ticket_bookings_screen.dart`<br>`MyTicketBookingsScreen` | S-035 (Profile menu) | Ticket QR view | Authenticated | 1. TabBar (`Upcoming Events`, `Past Bookings`, `Agro-Tours`)<br>2. Booking cards with QR badge<br>3. "View Pass" button | Booked event passes and tour reservations | Loading, Empty | Real API: `GET /api/student/bookings` |
| **S-035** | Profile Screen (Tab 5 Root) | `lib/screens/profile/profile_screen.dart`<br>`ProfileScreen` | Shell Tab 5 | S-004 (Login), S-036 (Edit Profile), S-037 (Settings), Sub-screens | All | 1. User Header card (Avatar, Name, Role badge, Email)<br>2. Learning & Trade Features menu group<br>3. AI Advisory menu group<br>4. Community menu group<br>5. Account & Payments menu group<br>6. Settings, Dark mode toggle, Language sheet<br>7. Support & Info menu group<br>8. Login / Logout button | User account info, app settings, version | Default | Real API: `GET /api/user` |
| **S-036** | Edit Profile Screen | `lib/screens/profile/edit_profile_screen.dart`<br>`EditProfileScreen` | S-035, S-037 | S-035 (on save) | Authenticated | 1. Avatar picker (Gallery / Camera)<br>2. First Name, Last Name, Phone, Bio<br>3. "Save Changes" CTA | User profile fields | Saving, Error toasts | Real API: `POST /api/user/profile` |
| **S-037** | Settings Screen | `lib/screens/profile/settings_screen.dart`<br>`SettingsScreen` | S-035 ("Settings" tap) | S-036, S-038 (Change Password), Delete modal | Authenticated | 1. Preferences & Language sheet trigger<br>2. Account security links<br>3. Privacy Policy & Terms of Service links<br>4. "Delete Account" button | Account settings & legal page links | Loading, Deleting | Real API: `POST /api/user/delete-request` |
| **S-038** | Change Password Screen | `lib/screens/profile/change_password_screen.dart`<br>`ChangePasswordScreen` | S-037 | S-037 (on success) | Authenticated | 1. Current Password<br>2. New Password & Confirm Password<br>3. "Update Password" CTA | Password input fields | Updating, Error toasts | Real API: `POST /api/user/change-password` |
| **S-039** | Payment History Screen | `lib/screens/profile/payment_history_screen.dart`<br>`PaymentHistoryScreen` | S-035 | Payment Detail sheet | Authenticated | 1. Transactions list grouped by date<br>2. Item name (Course, Tour, Plan), Amount ETB/USD, Status pill (Paid, Failed, Pending)<br>3. Download receipt button | Invoices and payment history | Loading, Empty, Error | Real API: `GET /api/student/payments` |
| **S-040** | Notification Settings | `lib/screens/profile/notification_settings_screen.dart`<br>`NotificationSettingsScreen` | S-035 | S-035 | Authenticated | 1. Push notification toggles (Course updates, Market price alerts, RFQ bids, Tour departures)<br>2. Email digest switches | Notification preference flags | Saving | Real API: `POST /api/user/notification-settings` |
| **S-041** | Notifications Screen | `lib/screens/notifications/notifications_screen.dart`<br>`NotificationsScreen` | Home header bell icon | Target screen via deep link | Authenticated | 1. Filter tabs (`All`, `Unread`)<br>2. Notification items (icon, title, message, time ago)<br>3. "Mark all as read" action | Notification list | Loading, Empty | Real API: `GET /api/notifications` |
| **S-042** | In-App Messaging (Conversations) | `lib/screens/chat/conversations_screen.dart`<br>`ConversationsScreen` | AppRouter `/messages`, Profile | S-043 (Chat Screen) | Authenticated | 1. Search chats<br>2. Conversations list (Avatar, Name, last message, time, unread badge) | Active chat threads | Loading, Empty | Real API: `GET /api/messages` |
| **S-043** | In-App Chat Screen | `lib/screens/chat/chat_screen.dart`<br>`ChatScreen` | S-042 (tap thread) | S-042 | Authenticated | 1. Chat partner header (Avatar, name, online status)<br>2. Message bubbles list<br>3. Message input field & Send button | Message stream | Polling / Sending | Real API: `GET /api/messages/{id}`, `POST .../send` |
| **S-044** | Community Q&A Room | `lib/screens/community/qa_room_screen.dart`<br>`QaRoomScreen` | S-035 (Profile menu) | S-045 (Question Detail), Ask Question | All | 1. Search community discussions<br>2. Topic filter chips<br>3. Question cards list (Votes count, answers count, tags)<br>4. "Ask Question" FAB | Community forum questions | Loading, Empty | Real API: `GET /api/community/questions` |
| **S-045** | Question Detail Screen | `lib/screens/community/question_detail_screen.dart`<br>`QuestionDetailScreen` | S-044 | S-044 | All | 1. Question title, body, author, tags<br>2. Upvote/Downvote buttons<br>3. Answers list with accepted answer highlight<br>4. Post Answer input field | Question, answers, votes | Loading, Submitting | Real API: `GET /api/community/questions/{id}`, `POST .../answers` |
| **S-046** | Wishlist Screen | `lib/screens/wishlist/wishlist_screen.dart`<br>`WishlistScreen` | AppRouter `/wishlist`, Profile | S-011 (Course Detail), S-015 (Lot Detail) | Authenticated | 1. TabBar (`Saved Courses`, `Saved Coffee Lots`)<br>2. Bookmarked cards list<br>3. Remove from wishlist button | User's saved courses and coffee lots | Loading, Empty | Real API: `GET /api/wishlist`, `GET /api/marketplace/saved-listings` |
| **S-047** | Pomodoro Study & Focus Timer | `lib/screens/study_timer/pomodoro_timer_screen.dart`<br>`PomodoroTimerScreen` | AppRouter `/study-timer` | S-048 (Focus Progress) | Enrolled Student | 1. Circular countdown animation (25min work / 5min break)<br>2. Play / Pause / Reset controls<br>3. Linked course selector | Focus session duration | Interactive timer | Local timer state |
| **S-048** | Focus Progress & Statistics | `lib/screens/study_timer/focus_progress_screen.dart`<br>`FocusProgressScreen` | S-047 | S-047 | Enrolled Student | 1. Daily/Weekly study hours bar chart<br>2. Completed sessions count<br>3. Current streak badge | Focus metrics | Default | Local / API sync |
| **S-049** | Weekly Review Screen | `lib/screens/habit_tracker/weekly_review_screen.dart`<br>`WeeklyReviewScreen` | AppRouter `/weekly-review` | S-007 | Enrolled Student | 1. Weekly completion summary<br>2. Habit streak checkmarks<br>3. Goal celebration card | Habit metrics | Default | Local state |
| **S-050** | Instructors Directory | `lib/screens/instructor/all_instructors_screen.dart`<br>`AllInstructorsScreen` | AppRouter `/instructors` | S-051 (Instructor Profile) | All | 1. Search instructors<br>2. Instructor cards list (Photo, Name, Trade specialty, Courses count) | Faculty directory | Loading, Empty | Real API: `GET /api/instructors` |
| **S-051** | Instructor Profile Screen | `lib/screens/instructor/instructor_profile_screen.dart`<br>`InstructorProfileScreen` | S-050, Course detail author tap | S-011 (Course Detail), S-043 (Chat) | All | 1. Instructor avatar, name, verification badge, bio<br>2. Social links & credentials<br>3. Taught courses list<br>4. "Message Instructor" CTA | Instructor bio, ratings, courses | Loading, Error | Real API: `GET /api/instructors/{id}` |
| **S-052** | Bundles Catalog | `lib/screens/bundles/bundles_screen.dart`<br>`BundlesScreen` | Home bundles "See All" | S-053 (Bundle Detail) | All | 1. Search bundles<br>2. Bundle cards list with discount badges (`Save 35%`) | Curated course package list | Loading, Empty | Real API: `GET /api/bundles` |
| **S-053** | Bundle Detail Screen | `lib/screens/bundles/bundle_detail_screen.dart`<br>`BundleDetailScreen` | S-052, Home bundles | S-054 (Bundle Checkout) | All | 1. Bundle hero image & title<br>2. Included courses list with individual values<br>3. Bundle price & savings calculator<br>4. "Buy Bundle" CTA | Bundle metadata, included courses | Loading, Error | Real API: `GET /api/bundles/{id}` |
| **S-054** | Categories Catalog | `lib/screens/categories/categories_screen.dart`<br>`CategoriesScreen` | AppRouter `/categories` | S-055 (Category Detail) | All | 1. Grid of learning & trade categories with icons and course counts | Category list | Loading skeleton | Real API: `GET /api/categories` |
| **S-055** | Category Detail Screen | `lib/screens/category_detail/category_detail_screen.dart`<br>`CategoryDetailScreen` | S-054 | S-011 (Course Detail) | All | 1. Category name & banner<br>2. Filtered courses list under category | Category courses | Loading, Empty | Real API: `GET /api/categories/{id}` |

---

## 2. Current Workflows & Decision Points

### Workflow W-01: App Launch & Guest Discovery
```
[App Launch] 
   │
   ▼
[S-001: Splash Screen] ──(2600ms)──► [S-002: Splash Wrapper]
                                              │
                      ┌───────────────────────┴───────────────────────┐
                      ▼                                               ▼
         [First launch: !has_seen_onboarding]          [Returning user / has_seen]
                      │                                               │
                      ▼                                               ▼
           [S-003: Onboarding Slides]                       [S-007: Home Screen]
                      │ (Get Started / Skip)                          ▲
                      └───────────────────────────────────────────────┘
                                              │
                              ┌───────────────┴───────────────┐
                              ▼                               ▼
                      [Guest Browsing]             [Authenticated Action]
                      (Browse lots, courses,                  │
                       events, tools)                         ▼
                                                     [S-004: Auth Screen]
```
- **Decision Points:**
  - Has user completed onboarding? Yes -> Home (S-007); No -> Onboarding (S-003).
  - Is user logged in? No -> Guest access on Home with full read capabilities; Yes -> Personalized Home with "Continue Learning" or "Seller Portal" quick action.

### Workflow W-02: User Registration & OTP Activation
```
[S-004: Auth Screen (Sign Up Tab)]
   │ Enter Name, Email, Phone, Password
   ▼
[API: POST /api/auth/register]
   │
   ├─► (Validation Error) ──► Stays on S-004 (Toast notification)
   │
   └─► (Success) ──► [S-005: OTP Verification Screen]
                          │ Enter 6-digit SMS OTP
                          ▼
                     [API: POST /api/auth/register/verify]
                          │
                          ├─► (Invalid/Expired OTP) ──► Stays on S-005 (Error Toast)
                          │
                          └─► (Valid OTP) ──► Pop to S-004 or Navigate to [S-007: Home]
                                                 │
                                                 ▼
                                        [DEAD END / HALFWAY FLOW]:
                                        User lands directly on Home with no
                                        intent selection, no role configuration,
                                        and no business workspace onboarding.
```

### Workflow W-03: Coffee Lot Discovery & Sample Request
```
[S-007: Home Screen]
   │ Tap B2B Tab in Dock
   ▼
[S-014: Marketplace Home]
   │ Search / Select Origin (e.g. Sidama) / Filter by Process
   ▼
[S-015: Coffee Lot Detail]
   │ Review Cupping Score, ECTA Benchmark, Seller Profile
   │
   ├─► Tap "Bookmark" ──► [API: POST /marketplace/listings/{id}/save]
   │
   └─► Tap "Request Sample" 
          │
          ├─► [If Guest] ──► [VerificationPromptDialog] ──► [S-004: Auth Screen]
          │
          └─► [If Authenticated] ──► Modal Bottom Sheet Form (Address, Courier, Notes)
                                          │ Tap "Submit Request"
                                          ▼
                                     [API: POST /marketplace/listings/{id}/inquire]
                                          │
                                          ├─► (Success: 200) ──► Toast: "Sample dispatched!"
                                          │
                                          ▼
                                     [HALFWAY FLOW]:
                                     No courier tracking screen, no sample payment
                                     milestone, no retained sealed sample verification,
                                     and no cupping lab feedback loop for the buyer.
```

### Workflow W-04: Exporter Verification & Lot Publishing
```
[S-014: Marketplace Home] ──► Tap "Seller Portal" icon
   │
   ▼
[S-020: Seller Portal Screen]
   │
   ├─► [Check Verification Status: Unverified]
   │        │
   │        ▼
   │   [Prompt Dialog: Verification Required] ──► [S-021: Organization Verification]
   │                                                    │ Select Role: "Exporter"
   │                                                    │ Enter TIN & License No.
   │                                                    │ Tap "Attach & Vault"
   │                                                    ▼
   │                                           [CRITICAL DEFECT]:
   │                                           Submits dummy hardcoded byte string!
   │                                           No camera, no document picker.
   │                                                    │
   │                                                    ▼
   │                                           Status set to "pending_review".
   │                                           [HALFWAY FLOW]: Exporter cannot publish
   │                                           until admin manual database intervention.
   │
   └─► [Check Verification Status: Verified]
            │
            ├─► [Check Quota: Active Lots >= Limit] ──► [S-020A: QuotaLimitSheet] ──► [S-023: Pricing Plans]
            │
            └─► [Quota Available] ──► [S-016: Create Coffee Lot Screen]
                                          │ Fill Form (Origin, Grade, Bags, FOB Price)
                                          │ Tap "Publish Lot"
                                          ▼
                                     [API: POST /marketplace/seller/listings]
                                          │
                                          ▼
                                     [S-020: Seller Portal] (Lot appears in list)
```

### Workflow W-05: Buyer RFQ & Exporter Quotation
```
[S-014: Marketplace Home] ──► Tap "Buyer RFQs" icon
   │
   ▼
[S-018: Marketplace RFQs Screen]
   │
   ├─► [Buyer: Post New RFQ]
   │        │ Tap Floating Action Button "Post RFQ"
   │        ▼
   │   [S-019: Create RFQ Screen]
   │        │ Input Origin, Bags, Target FOB Price, Port
   │        ▼
   │   [API: POST /marketplace/rfqs] ──► Returns to S-018
   │
   └─► [Exporter: Quote on Open Tender]
            │ Tap "Quote Tender" on RFQ card
            ▼
       [Modal Bottom Sheet: Quote Form]
            │ Input Price/lb, Available Bags, Payment Terms
            ▼
       [API: POST /marketplace/rfqs/{id}/quote]
            │
            ├─► (Success) ──► Toast: "Quote submitted!"
            │
            ▼
       [DEAD END]:
       Buyer receives quotation in backend database, but the app has
       NO Deal Room to accept the quote, generate sales contracts,
       set milestone payments, or initiate logistics tracking.
```

---

## 3. Analysis of Flaws, Dead Ends & Halfway Flows in Current App

1. **Dead End — Post-Registration Landing:**
   - In `OtpVerificationScreen` (`lib/screens/auth/otp_verification_screen.dart:121-128`), upon successful verification, the app simply pops the navigator or replaces root with `AppConfig.getHomePage()`.
   - The user is never asked what they came to do (Export, Buy, Learn, Attend Events). There is no intent selection, no company setup, and no personalized onboarding.
2. **Halfway Flow — Sample Request Lifecycle:**
   - S-015 allows a buyer to submit an inquiry with `inquiry_type = 'sample_request'`.
   - Once submitted, there is **zero tracking interface** for either party. The seller cannot input courier waybills (DHL/FedEx), the buyer cannot track shipment transit, no sample delivery confirmation exists, and no lab evaluation outcome can be recorded.
3. **Missing Transition — Deal Room & Escrow Milestones:**
   - An exporter can submit a quote on an RFQ (S-018). The buyer can view lots (S-015).
   - But there is **no deal closing mechanism**: No contract agreement screen, no deposit/escrow milestone status, no shipping container tracking, no dispute resolution, and no final settlement confirmation.
4. **Mocked Document Verification:**
   - In `OrganizationVerificationScreen` (`lib/screens/marketplace/organization_verification_screen.dart:201-209`), tapping "Attach & Vault" submits dummy bytes:
     `final sampleBytes = utf8.encode('PDF_CERTIFICATE_DOCUMENT_STREAM_${DateTime.now().millisecondsSinceEpoch}');`
   - Real compliance verification is impossible because neither `image_picker` nor `file_picker` is invoked.
5. **Orphaned / Unreachable Screen:**
   - `lib/screens/home/home2_screen.dart` (`Home2Screen`) exists in the codebase and is routed under `/home2`, but no UI button or navigation trigger leads to it. It is an unmaintained duplicate.
6. **No Seller Public Profile / Verification Lookup:**
   - Tapping an exporter's name on a lot card or quote does nothing. Buyers cannot inspect an exporter's historical trade track record, licenses, certifications, or warehouse location.

