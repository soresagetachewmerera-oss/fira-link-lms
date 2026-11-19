from pathlib import Path
import json

CANVAS_FILE = Path(r"D:\.gradle\fira-link-lms\docs\design\flows\canvas.json")
FLUTTER_SCREENS_DIR = Path(r"D:\.gradle\fira-link-lms\firalink flutter\lib\screens")

# Mapped screens
mapped_files = {
    "screens/splash/splash_screen.dart",
    "screens/auth/language_choice_screen.dart",
    "screens/onboarding/onboarding_screen.dart",
    "screens/auth/phone_sign_in_screen.dart",
    "screens/auth/auth_screen.dart",
    "screens/auth/otp_verification_screen.dart",
    "screens/auth/forgot_password_screen.dart",
    "screens/auth/new_password_screen.dart",
    "screens/onboarding/intent_welcome_screen.dart",
    "screens/onboarding/business_workspace_setup_screen.dart",
    "screens/profile/workspace_switcher_sheet.dart",
    "screens/home/home_screen.dart",
    "screens/home/home2_screen.dart",
    "screens/notifications/notifications_screen.dart",
    "screens/marketplace/marketplace_home_screen.dart",
    "screens/marketplace/widgets/market_filters_sheet.dart",
    "screens/marketplace/exporters_screen.dart",
    "screens/marketplace/coffee_listing_detail_screen.dart",
    "screens/marketplace/public_seller_verification_screen.dart",
    "screens/marketplace/lot_evidence_screen.dart",
    "screens/marketplace/organization_verification_screen.dart",
    "screens/document_scanner/fira_document_scanner_screen.dart",
    "screens/marketplace/create_rfq_screen.dart",
    "screens/marketplace/compare_quotes_screen.dart",
    "screens/marketplace/request_sample_screen.dart",
    "screens/marketplace/sample_tracking_screen.dart",
    "screens/deal_room/deal_room_hub_screen.dart",
    "screens/deal_room/payment_milestones_screen.dart",
    "screens/trust_safety/scam_warning_sheet.dart",
    "screens/trust_safety/report_modal.dart",
    "screens/coffee_toolkit/coffee_toolkit_hub_screen.dart",
    "screens/coffee_toolkit/coffee_profit_calculator_screen.dart",
    "screens/marketplace/seller_portal_screen.dart",
    "screens/marketplace/create_lot_screen.dart",
    "screens/marketplace/samples_screen.dart",
    "screens/marketplace/submit_quote_screen.dart",
    "screens/events/events_screen.dart",
    "screens/events/event_detail_screen.dart",
    "screens/events/ticket_booking_screen.dart",
    "screens/events/widgets/ticket_pass_view.dart",
    "screens/tours/tours_screen.dart",
    "screens/tours/tour_detail_screen.dart",
    "screens/tours/tour_booking_screen.dart",
    "screens/tours/tour_booking_confirmation_screen.dart",
    "screens/courses/courses_screen.dart",
    "screens/course_detail/course_detail_screen.dart",
    "screens/course_access/course_access_screen.dart",
    "screens/ai_chat/ai_chat_screen.dart",
    "screens/profile/profile_screen.dart",
    "screens/profile/settings_screen.dart",
}

all_screen_files = []
for p in FLUTTER_SCREENS_DIR.rglob("*.dart"):
    rel = "screens/" + p.relative_to(FLUTTER_SCREENS_DIR).as_posix()
    if p.name.endswith("_screen.dart") or "screen" in p.name.lower():
        all_screen_files.append(rel)

unmapped = [s for s in all_screen_files if s not in mapped_files]
print(f"Total built screens in Flutter: {len(all_screen_files)}")
print(f"Screens matched to flows in canvas.json: {len(mapped_files)}")
print(f"Built screens NOT in flows: {len(unmapped)}")
print("\n--- BUILT SCREENS NOT IN CANVAS FLOWS ---")
for s in sorted(unmapped):
    print(f"  {s}")

