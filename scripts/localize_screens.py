import os
import sys

sys.stdout.reconfigure(encoding='utf-8')

def replace_in_file(path, replacements):
    with open(path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    modified = content
    for old, new in replacements:
        if old in modified:
            modified = modified.replace(old, new)
        else:
            print(f"Warning: pattern not found in {os.path.basename(path)}: {old[:40]}...")
            
    if modified != content:
        with open(path, 'w', encoding='utf-8') as f:
            f.write(modified)
        print(f"Updated {path}")
    else:
        print(f"No changes made to {path}")

# 1. tour_booking_screen.dart (rest of strings)
tour_path = 'Eduex Flutter/lib/screens/tours/tour_booking_screen.dart'
tour_replacements = [
    ("'Valid email required'", "context.l10n.fieldRequired"),
    ("'Phone required'", "context.l10n.fieldRequired"),
    ("const Text('Number of Participants',", "Text(context.l10n.attendees,"),
    ("Text('Number of Participants',", "Text(context.l10n.attendees,"),
    ("const Text('Passengers',", "Text(context.l10n.attendees,"),
    ("Text('Passengers',", "Text(context.l10n.attendees,"),
    ("labelText: 'Special Requests / Dietary Needs (Optional)',", "labelText: '${context.l10n.notes} (${context.l10n.optional})',"),
    ("const Text('Payment Method',", "Text(context.l10n.paymentMethod,"),
    ("Text('Payment Method',", "Text(context.l10n.paymentMethod,"),
    ("const Text('Total Payable:',", "Text('${context.l10n.total}:',"),
    ("Text('Total Payable:',", "Text('${context.l10n.total}:',"),
    ("const Text('Confirm & Reserve Seats',", "Text(context.l10n.confirmReservation,"),
    ("Text('Confirm & Reserve Seats',", "Text(context.l10n.confirmReservation,"),
    ("'Free Entry'", "context.l10n.free"),
]
replace_in_file(tour_path, tour_replacements)

# 2. my_tour_bookings_screen.dart
my_tour_path = 'Eduex Flutter/lib/screens/tours/my_tour_bookings_screen.dart'
my_tour_replacements = [
    ("import 'tour_booking_confirmation_screen.dart';", "import 'tour_booking_confirmation_screen.dart';\nimport '../../l10n/app_localizations.dart';"),
    ("title: const Text('My Tour Bookings',", "title: Text(context.l10n.myTourBookings,"),
    ("title: Text('My Tour Bookings',", "title: Text(context.l10n.myTourBookings,"),
    ("title: const Text('Cancel Reservation?'),", "title: Text(context.l10n.cancelReservation),"),
    ("child: const Text('Keep')", "child: Text(context.l10n.cancel)"),
    ("child: const Text('Yes, Cancel',", "child: Text(context.l10n.confirm,"),
    ("const SnackBar(content: Text('Booking cancelled successfully.'))", "SnackBar(content: Text(context.l10n.bookingCancelled))"),
]
replace_in_file(my_tour_path, my_tour_replacements)

# 3. coffee_profit_calculator_screen.dart
calc_path = 'Eduex Flutter/lib/screens/coffee_toolkit/coffee_profit_calculator_screen.dart'
calc_replacements = [
    ("import '../../theme/app_theme.dart';", "import '../../theme/app_theme.dart';\nimport '../../l10n/app_localizations.dart';"),
    ("title: Text(\n          'Profit Calculator',", "title: Text(\n          context.l10n.profitCalculator,"),
    ("title: Text('Profit Calculator',", "title: Text(context.l10n.profitCalculator,"),
    ("Text(\n                'ESTIMATED NET MARGIN',", "Text(\n                context.l10n.profitMargin,"),
    ("Text('ESTIMATED NET MARGIN',", "Text(context.l10n.profitMargin,"),
    ("const SnackBar(\n          content: Text('Deal calculation saved successfully to workspace!'),", "SnackBar(\n          content: Text(context.l10n.calculationSaved),"),
]
replace_in_file(calc_path, calc_replacements)

# 4. coffee_market_screen.dart
market_path = 'Eduex Flutter/lib/screens/coffee_toolkit/coffee_market_screen.dart'
market_replacements = [
    ("import '../../theme/app_theme.dart';", "import '../../theme/app_theme.dart';\nimport '../../l10n/app_localizations.dart';"),
    ("title: Text(\n          'Market Intelligence & Minimum Prices',", "title: Text(\n          context.l10n.marketIntelligence,"),
    ("title: Text('Market Intelligence & Minimum Prices',", "title: Text(context.l10n.marketIntelligence,"),
]
replace_in_file(market_path, market_replacements)

# 5. quiz_attempt_screen.dart
quiz_path = 'Eduex Flutter/lib/screens/course_access/quiz_attempt_screen.dart'
quiz_replacements = [
    ("child: Text(\n                            'Previous',", "child: Text(\n                            context.l10n.previous,"),
    ("child: Text('Previous',", "child: Text(context.l10n.previous,"),
    ("? 'Next'\n                                    : 'Close'", "? context.l10n.next\n                                    : context.l10n.close"),
    ("? 'Next'\n                                    : 'Submit Quiz'", "? context.l10n.next\n                                    : context.l10n.submitQuiz"),
]
replace_in_file(quiz_path, quiz_replacements)

# 6. course_discussion_detail_screen.dart
disc_path = 'Eduex Flutter/lib/screens/course_access/course_discussion_detail_screen.dart'
disc_replacements = [
    ("import '../../theme/app_theme.dart';", "import '../../theme/app_theme.dart';\nimport '../../l10n/app_localizations.dart';"),
    ("title: const Text('Discussion Details'),", "title: Text(context.l10n.discussion),"),
    ("title: Text('Discussion Details'),", "title: Text(context.l10n.discussion),"),
    ("'Replies (${discussion.replies.length})'", "'${context.l10n.replies} (${discussion.replies.length})'"),
    ("child: Text('Pinned',", "child: Text(context.l10n.pinned,"),
    ("child: Text('Announcement',", "child: Text(context.l10n.announcement,"),
]
replace_in_file(disc_path, disc_replacements)

# 7. auth_screen.dart
auth_path = 'Eduex Flutter/lib/screens/auth/auth_screen.dart'
auth_replacements = [
    ("child: Text(\n                    'Show',", "child: Text(\n                    widget.isPasswordVisible ? context.l10n.hide : context.l10n.show,"),
    ("child: Text('Show',", "child: Text(widget.isPasswordVisible ? context.l10n.hide : context.l10n.show,"),
]
replace_in_file(auth_path, auth_replacements)

print("Localization sweep completed.")
