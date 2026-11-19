import re
from pathlib import Path

FLUTTER_LIB = Path(r"D:\.gradle\fira-link-lms\firalink flutter\lib")

# Flow file mapping
flows = {
    "1. First launch": [
        "screens/splash/splash_screen.dart",
        "screens/auth/language_choice_screen.dart",
        "screens/onboarding/onboarding_screen.dart",
    ],
    "2. Auth & Password": [
        "screens/auth/auth_screen.dart",
        "screens/auth/phone_sign_in_screen.dart",
        "screens/auth/otp_verification_screen.dart",
        "screens/auth/email_sign_up_screen.dart",
        "screens/auth/forgot_password_screen.dart",
        "screens/auth/new_password_screen.dart",
    ],
    "3. Intent & Workspace": [
        "screens/onboarding/intent_welcome_screen.dart",
        "screens/onboarding/business_workspace_setup_screen.dart",
        "screens/profile/workspace_switcher_sheet.dart",
    ],
    "4. Verification Dossier": [
        "screens/profile/verification_dossier_screen.dart",
        "screens/document_scanner/fira_document_scanner_screen.dart",
        "screens/marketplace/public_seller_verification_screen.dart",
    ],
    "5. Seller Lots & Evidence": [
        "screens/marketplace/seller_portal_screen.dart",
        "screens/marketplace/create_lot_screen.dart",
        "screens/marketplace/lot_evidence_screen.dart",
    ],
    "6. Buyer Marketplace & RFQ": [
        "screens/marketplace/marketplace_home_screen.dart",
        "screens/marketplace/widgets/market_filters_sheet.dart",
        "screens/marketplace/coffee_listing_detail_screen.dart",
        "screens/marketplace/create_rfq_screen.dart",
        "screens/marketplace/compare_quotes_screen.dart",
    ],
    "7. Samples": [
        "screens/marketplace/request_sample_screen.dart",
        "screens/marketplace/sample_tracking_screen.dart",
        "screens/marketplace/samples_screen.dart",
    ],
    "8. Deal Room & Trust": [
        "screens/deal_room/deal_room_hub_screen.dart",
        "screens/deal_room/deal_step_screen.dart",
        "screens/deal_room/payment_milestones_screen.dart",
        "screens/chat/trade_chat_screen.dart",
        "screens/trust_safety/report_modal.dart",
        "screens/trust_safety/scam_warning_sheet.dart",
    ],
    "9. Events, Tours, Academy": [
        "screens/events/events_screen.dart",
        "screens/events/event_detail_screen.dart",
        "screens/events/event_register_screen.dart",
        "screens/tours/tours_screen.dart",
        "screens/tours/tour_detail_screen.dart",
        "screens/tours/tour_booking_screen.dart",
        "screens/courses/courses_screen.dart",
        "screens/course_detail/course_detail_screen.dart",
    ],
    "10. Home, AI, Profile": [
        "screens/home/home_screen.dart",
        "screens/search/search_screen.dart",
        "screens/ai_chat/ai_chat_screen.dart",
        "screens/profile/profile_screen.dart",
        "screens/profile/settings_screen.dart",
    ]
}

print("=== CHECKING FLOWS FOR UX DEFECTS ===")
for flow_name, files in flows.items():
    print(f"\n--- {flow_name} ---")
    for f in files:
        p = FLUTTER_LIB / f
        if not p.exists():
            print(f"  [MISSING FILE] {f}")
            continue
        content = p.read_text(encoding="utf-8", errors="replace")
        
        # Check for error handling and feedback
        has_snackbar = "ScaffoldMessenger.of" in content or "showToast" in content or "toast" in content
        has_dialog = "showDialog" in content or "showModalBottomSheet" in content
        has_pop = "Navigator.pop" in content or "Navigator.of(context).pop" in content
        has_leak = re.findall(r'e\.toString\(\)|error\.toString\(\)', content)
        has_will_pop = "WillPopScope" in content or "PopScope" in content
        
        # Check form fields without confirmation on exit (data loss)
        has_text_controller = "TextEditingController" in content
        data_loss_risk = has_text_controller and not has_will_pop
        
        print(f"  {p.name}: Controller={has_text_controller}, PopScope={has_will_pop}, Leaks={len(has_leak)}, SnackBar={has_snackbar}")

