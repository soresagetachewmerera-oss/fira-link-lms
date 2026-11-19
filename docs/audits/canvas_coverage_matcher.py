import json
from pathlib import Path

CANVAS_FILE = Path(r"D:\.gradle\fira-link-lms\docs\design\flows\canvas.json")
with open(CANVAS_FILE, encoding="utf-8") as f:
    canvas = json.load(f)

# Flow mappings to Flutter screens
flow_to_flutter = {
    "Splash.dc.html": "screens/splash/splash_screen.dart",
    "Main.dc.html": "screens/auth/language_choice_screen.dart",
    "Onboard1.dc.html": "screens/onboarding/onboarding_screen.dart (Page 1)",
    "Onboard2.dc.html": "screens/onboarding/onboarding_screen.dart (Page 2)",
    "Onboard3.dc.html": "screens/onboarding/onboarding_screen.dart (Page 3)",
    "Auth.dc.html": "screens/auth/phone_sign_in_screen.dart / auth_screen.dart",
    "Otp.dc.html": "screens/auth/otp_verification_screen.dart",
    "EmailLogin.dc.html": "screens/auth/auth_screen.dart (Email Tab)",
    "Forgot.dc.html": "screens/auth/forgot_password_screen.dart",
    "ResetCode.dc.html": "screens/auth/otp_verification_screen.dart (Reset Mode)",
    "NewPass.dc.html": "screens/auth/new_password_screen.dart",
    "ResetDone.dc.html": "In-screen SnackBar / Dialog (no dedicated screen)",
    "Intent.dc.html": "screens/onboarding/intent_welcome_screen.dart",
    "Business.dc.html": "screens/onboarding/business_workspace_setup_screen.dart",
    "Workspace.dc.html": "screens/profile/workspace_switcher_sheet.dart",
    "Home.dc.html": "screens/home/home_screen.dart / home2_screen.dart",
    "Search.dc.html": "screens/home/widgets/home_search_bar.dart (NO standalone screen)",
    "Notifications.dc.html": "screens/notifications/notifications_screen.dart",
    "Market.dc.html": "screens/marketplace/marketplace_home_screen.dart",
    "Filters.dc.html": "screens/marketplace/widgets/market_filters_sheet.dart",
    "ExporterProfile.dc.html": "screens/marketplace/exporters_screen.dart",
    "LotDetail.dc.html": "screens/marketplace/coffee_listing_detail_screen.dart",
    "PublicVerify.dc.html": "screens/marketplace/public_seller_verification_screen.dart",
    "Evidence.dc.html": "screens/marketplace/lot_evidence_screen.dart",
    "Dossier.dc.html": "screens/marketplace/organization_verification_screen.dart",
    "Capture.dc.html": "screens/document_scanner/fira_document_scanner_screen.dart",
    "RFQ.dc.html": "screens/marketplace/create_rfq_screen.dart",
    "Compare.dc.html": "screens/marketplace/compare_quotes_screen.dart",
    "SampleRequest.dc.html": "screens/marketplace/request_sample_screen.dart",
    "SampleTracking.dc.html": "screens/marketplace/sample_tracking_screen.dart",
    "DealRoom.dc.html": "screens/deal_room/deal_room_hub_screen.dart",
    "Milestones.dc.html": "screens/deal_room/payment_milestones_screen.dart",
    "ChatWarning.dc.html": "screens/trust_safety/scam_warning_sheet.dart",
    "Report.dc.html": "screens/trust_safety/report_modal.dart",
    "Toolkit.dc.html": "screens/coffee_toolkit/coffee_toolkit_hub_screen.dart",
    "Fob.dc.html": "screens/coffee_toolkit/coffee_profit_calculator_screen.dart",
    "SellerHome.dc.html": "screens/marketplace/seller_portal_screen.dart",
    "CreateLot.dc.html": "screens/marketplace/create_lot_screen.dart",
    "SampleInbox.dc.html": "screens/marketplace/samples_screen.dart",
    "QuoteRespond.dc.html": "screens/marketplace/submit_quote_screen.dart",
    "Events.dc.html": "screens/events/events_screen.dart",
    "EventDetail.dc.html": "screens/events/event_detail_screen.dart",
    "EventRegister.dc.html": "screens/events/ticket_booking_screen.dart",
    "TicketPass.dc.html": "screens/events/widgets/ticket_pass_view.dart",
    "Tours.dc.html": "screens/tours/tours_screen.dart",
    "TourDetail.dc.html": "screens/tours/tour_detail_screen.dart",
    "TourBooking.dc.html": "screens/tours/tour_booking_screen.dart",
    "TourConfirmed.dc.html": "screens/tours/tour_booking_confirmation_screen.dart",
    "Academy.dc.html": "screens/courses/courses_screen.dart",
    "Course.dc.html": "screens/course_detail/course_detail_screen.dart",
    "Lesson.dc.html": "screens/course_access/course_access_screen.dart",
    "FiraAI.dc.html": "screens/ai_chat/ai_chat_screen.dart",
    "Profile.dc.html": "screens/profile/profile_screen.dart",
    "Settings.dc.html": "screens/profile/settings_screen.dart",
}

print("=== CANVAS BOARDS VS BUILT SCREENS COVERAGE ===")
order = canvas.get("order", [])
for board in order:
    binfo = canvas.get("boards", {}).get(board, {})
    title = binfo.get("title", "")
    mapped = flow_to_flutter.get(board, "NOT MAPPED")
    print(f"{board:22} | {title:40} | -> {mapped}")

