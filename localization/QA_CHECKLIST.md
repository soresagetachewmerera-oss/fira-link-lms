# Fira Link LMS - Amharic (አማርኛ) & English Manual QA Checklist

This checklist provides comprehensive manual test verification for the **Amharic (አማርኛ)** and **English** bilingual localization on Android and iOS devices.

---

## 1. Initial Launch & Language Selection
- [ ] **First Launch - System Locale Detection**:
  - Set device language to **አማርኛ (Ethiopia)**. Launch app. Verify app opens in **Amharic** automatically.
  - Set device language to **English (US/UK)**. Launch app. Verify app opens in **English**.
- [ ] **Language Selection in Profile & Settings**:
  - Open **Profile** (`/profile`) and tap the language switcher item.
  - Open **Settings** (`/settings`) and tap **ቋንቋ / Language**.
  - Verify modal displays exactly two options in their native scripts:
    1. **English** (with British/US flag icon `assets/img/icons/en.png`)
    2. **አማርኛ** (with authentic Ethiopian flag icon `assets/img/icons/am.png`)
  - Select **አማርኛ**: Verify entire interface switches to Amharic immediately without app restart.
  - Select **English**: Verify entire interface switches back to English immediately without app restart.
- [ ] **Persistence Across App Restarts**:
  - Set language to **አማርኛ**, force close app, and re-open.
  - Verify app launches and remains in **አማርኛ**.

---

## 2. Typography & Ge'ez Font Rendering (Noto Sans Ethiopic)
- [ ] **Ethiopic Character Rendering**:
  - Inspect headings, subtitles, and labels. Verify no tofu boxes (`□`) or missing glyphs.
  - Verify bundled `Noto Sans Ethiopic` variable font renders with crisp weights (Regular, Medium, Bold).
- [ ] **Line Heights & Vertical Alignment**:
  - Ethiopic glyphs are taller; verify adequate line spacing in:
    - App bar titles
    - Course cards & lesson list titles
    - Tab bar labels & Bottom navigation dock
    - Form field labels & helper texts
- [ ] **Text Overflow & Expansion**:
  - Test on smaller screen widths (320dp – 360dp).
  - Verify no `RenderFlex overflowed` yellow-and-black stripes on:
    - Checkout summary card
    - Coffee profit calculator cards
    - Tour reservation dialogs

---

## 3. Screen-by-Screen Verification Flows

### A. Authentication & Onboarding
- [ ] **Onboarding Screen**:
  - Slides 1 to 4: Titles and descriptions display properly formatted Amharic.
  - "ቀጣይ" (Next), "ዝለል" (Skip), "ጀምር" (Get Started) buttons work as expected.
- [ ] **Login / Registration (`auth_screen.dart`)**:
  - Tabs: "ግባ" (Login) and "ተመዝገብ" (Sign Up).
  - Form labels: "ኢሜይል" (Email), "የይለፍ ቃል" (Password), "ስም" (First Name), "የአባት ስም" (Last Name), "ስልክ ቁጥር" (Phone Number).
  - Toggle password: "አሳይ" (Show) and "ደብቅ" (Hide).
  - "የይለፍ ቃል ረሱ?" (Forgot Password?) links to recovery.
  - Error toasts on empty input or invalid credentials appear in Amharic.
- [ ] **OTP Verification (`otp_verification_screen.dart`)**:
  - Title: "የማረጋገጫ ኮድ አስገባ" (Enter Verification Code).
  - Subtitle: "ወደ ስልክዎ ወይም ኢሜይልዎ ባለ 6-አሃዝ የማረጋገጫ ኮድ ልከናል".
  - "ኮድ አልደረሰዎትም?" (Didn't receive code?), "እንደገና ላክ" (Resend Code).

### B. Home & Dashboard
- [ ] **Market Ticker & Top Navigation**:
  - Market strip shows live ICE Arabica and local benchmark rates with localized currency (`ብር`).
  - Search bar placeholder: "ኮርሶችን ወይም ርዕሶችን ፈልግ..." (Search courses or topics...).
- [ ] **Bottom Navigation Dock**:
  - 5 dock tabs: "ዋና ገጽ" (Home), "ኮርሶች" (Courses), "መሣሪያዎች" (Toolkit), "ጉብኝቶች" (Tours), "መገለጫ" (Profile).
  - Ensure labels fit without clipping.
- [ ] **Course Cards**:
  - Badges ("ተወዳጅ" - Popular, "አዲስ" - New, "የተረጋገጠ" - Verified).
  - Rating, student counts ("... ተማሪዎች"), and prices ("... ብር").

### C. Coffee Export Toolkit (`/coffee_toolkit`)
- [ ] **Toolkit Hub**:
  - "የኢትዮጵያ ቡና ኤክስፖርት መሣሪያዎች" (Ethiopian Coffee Export Toolkit).
  - 4 Core tool tiles:
    1. "የትርፍ ማስያዣ" (Profit Calculator)
    2. "የንግድ አቅርቦት ማመንጫ" (Offer Generator)
    3. "የኤክስፖርት ዝግጁነት" (Export Readiness)
    4. "የገበያ መረጃ" (Market Intelligence)
- [ ] **Profit Calculator**:
  - Real-time deal modeling in FOB/CIF.
  - Estimated Net Margin: "የተጣራ ትርፍ መጠን" (Profit Margin).
  - "ስሌቱን አስቀምጥ" (Save Calculation) triggers green success snackbar: "ስሌቱ በተሳካ ሁኔታ ተቀምጧል".
- [ ] **Offer Generator & Export Readiness**:
  - FCO sheet preview, PDF export, and EUDR compliance checklist.

### D. Origin Tours & Expeditions (`/tours`)
- [ ] **Tour Listing & Details**:
  - Duration, difficulty, remaining seats ("የቀሩ መቀመጫዎች").
  - Price per person ("... ብር ለአንድ ሰው").
- [ ] **Tour Booking Flow (`tour_booking_screen.dart`)**:
  - Header: "ቦታ ማስያዝን ያረጋግጡ" (Confirm Reservation).
  - Lead Passenger Details: "የተሳታፊ መረጃ".
  - Form fields: "ስም *", "የአባት ስም *", "ኢሜይል *", "ስልክ ቁጥር *".
  - Validation error on empty fields: "ይህ ቦታ መሞላት አለበት" (This field is required).
  - Payment method: "Chapa (ቴሌብር፣ ሲቢኢ ብር እና ካርዶች)".
  - Confirmation button: "ቦታ ያስይዙ" (Confirm Reservation).
- [ ] **My Bookings (`my_tour_bookings_screen.dart`)**:
  - Active and historical tour reservations.
  - Cancellation dialog: "ቦታ ማስያዝ ይሰረዝ?" (Cancel Reservation?) with "ሰርዝ" (Cancel) and "አረጋግጥ" (Confirm).

### E. Course Access, Quizzes & Community
- [ ] **Course Player**:
  - Curriculum list, lesson tabs, resources, and video notes.
  - "ትምህርቱን አጠናቅቅ" (Mark Complete).
- [ ] **Quiz Attempt (`quiz_attempt_screen.dart`)**:
  - Question counter: "ጥያቄ 1/10" (Question 1/10).
  - Timer, option selectors, "ቀዳሚ" (Previous), "ቀጣይ" (Next), "ፈተናውን አስገባ" (Submit Quiz).
  - Result dialog: "እንኳን ደስ አለዎት! ፈተናውን አልፈዋል።" (Congratulations! You passed the quiz) or "እንደገና ይሞክሩ" (Try again).
- [ ] **Q&A & Discussions**:
  - "አስተያየቶች" (Comments), "ምላሾች" (Replies), "የተሰካ" (Pinned), "ማስታወቂያ" (Announcement).

### F. Profile & Account Settings
- [ ] **Profile (`/profile`)**:
  - User details, earned points, badges, certificates ("ሰርተፍኬቶች").
  - Referral program ("የአጋርነት ፕሮግራም").
- [ ] **Settings (`/settings`)**:
  - Preferences: "ቋንቋ" (Language).
  - Account: "መገለጫ አርም" (Edit Profile), "የይለፍ ቃል ቀይር" (Change Password).
  - Destructive action: "መለያ ሰርዝ" (Delete Account) prompt in Amharic with confirmation dialog.
  - Support: "ስለ እኛ" (About Us), "እርዳታና ድጋፍ" (Help & Support), "መተግበሪያውን ደረጃ ይስጡ" (Rate App), "መተግበሪያውን ያጋሩ" (Share App).
  - "ውጣ" (Logout) confirmation.

---

## 4. Date & Currency Formats
- [ ] **Currency Formatting**:
  - When in Amharic: `1,250.00 ብር`.
  - When in English: `ETB 1,250.00`.
- [ ] **Calendar & Relative Time**:
  - Gregorian dates translated with Amharic month names (e.g. `ጥቅምት 31, 2026`).
  - Optional Ethiopian calendar dates display (ዓ.ም).
  - Relative dates: "ዛሬ" (Today), "ትናንት" (Yesterday), "ነገ" (Tomorrow), "ደቂቃዎች በፊት" (min ago), "ሰዓታት በፊት" (hours ago).

---

## 5. Backend Header Verification
- [ ] Open Network Inspector or view Laravel logs.
- [ ] Verify HTTP requests include the header:
  `Accept-Language: am` when in Amharic, and `Accept-Language: en` when in English.
