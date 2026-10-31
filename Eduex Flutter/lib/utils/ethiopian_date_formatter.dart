/// Ethiopian Calendar & Localized Date Formatter for Fira Link LMS
class EthiopianDate {
  final int year;
  final int month;
  final int day;

  const EthiopianDate({
    required this.year,
    required this.month,
    required this.day,
  });

  static const List<String> monthNamesAmharic = <String>[
    'መስከረም', // 1: Meskerem (Sept - Oct)
    'ጥቅምት',  // 2: Tikimt (Oct - Nov)
    'ኅዳር',   // 3: Hidar (Nov - Dec)
    'ታኅሣሥ',  // 4: Tahsas (Dec - Jan)
    'ጥር',     // 5: Tir (Jan - Feb)
    'የካቲት',  // 6: Yekatit (Feb - Mar)
    'መጋቢት',  // 7: Megabit (Mar - Apr)
    'ሚያዝያ',  // 8: Miazia (Apr - May)
    'ግንቦት',  // 9: Ginbot (May - Jun)
    'ሰኔ',     // 10: Sene (Jun - Jul)
    'ሐምሌ',   // 11: Hamle (Jul - Aug)
    'ነሐሴ',   // 12: Nehase (Aug - Sept)
    'ጳጉሜ',   // 13: Pagume (intercalary days)
  ];

  static const List<String> gregorianMonthsAmharic = <String>[
    'ጃንዩወሪ', 'ፌብሩወሪ', 'ማርች', 'ኤፕሪል', 'ሜይ', 'ጁን',
    'ጁላይ', 'ኦገስት', 'ሴፕቴምበር', 'ኦክቶበር', 'ኖቬምበር', 'ዲሴምበር',
  ];

  static const List<String> weekdaysAmharic = <String>[
    'ሰኞ', 'ማክሰኞ', 'ረቡዕ', 'ሐሙስ', 'አርብ', 'ቅዳሜ', 'እሑድ',
  ];

  /// Convert standard Gregorian DateTime to Ethiopian Calendar
  factory EthiopianDate.fromGregorian(DateTime gregorian) {
    final int gYear = gregorian.year;
    final int gMonth = gregorian.month;
    final int gDay = gregorian.day;

    // Determine Ethiopian Year
    int ethYear = gYear - 8;
    // Check if Gregorian date is before Ethiopian New Year (Sept 11 or 12 in leap year)
    final bool isGregLeap = (gYear % 4 == 0 && gYear % 100 != 0) || (gYear % 400 == 0);
    final int newYearDay = isGregLeap ? 12 : 11;

    if (gMonth < 9 || (gMonth == 9 && gDay < newYearDay)) {
      ethYear = gYear - 8;
    } else {
      ethYear = gYear - 7;
    }

    // Days since Ethiopian New Year
    final DateTime ethNewYear = DateTime(gYear, 9, newYearDay);
    DateTime prevNewYear;
    if (gMonth < 9 || (gMonth == 9 && gDay < newYearDay)) {
      final bool prevYearLeap = ((gYear - 1) % 4 == 0 && (gYear - 1) % 100 != 0) || ((gYear - 1) % 400 == 0);
      prevNewYear = DateTime(gYear - 1, 9, prevYearLeap ? 12 : 11);
    } else {
      prevNewYear = ethNewYear;
    }

    final int diffDays = gregorian.difference(prevNewYear).inDays;
    int ethMonth = (diffDays ~/ 30) + 1;
    int ethDay = (diffDays % 30) + 1;

    if (ethMonth > 13) {
      ethMonth = 13;
    }

    return EthiopianDate(year: ethYear, month: ethMonth, day: ethDay);
  }

  String get monthName => monthNamesAmharic[(month - 1).clamp(0, 12)];

  /// Formatted as: "ጥቅምት 21 ቀን 2017 ዓ.ም"
  String formatFull() {
    return '$monthName $day ቀን $year ዓ.ም';
  }

  /// Formatted as: "ጥቅምት 2017"
  String formatMonthYear() {
    return '$monthName $year ዓ.ም';
  }
}

/// Helper methods for formatting dates across the app in Amharic & English
class AppDateFormatter {
  /// Format a DateTime according to current active locale
  static String formatDate(DateTime date, {required String languageCode, bool useEthiopianCalendar = false}) {
    if (languageCode == 'am') {
      if (useEthiopianCalendar) {
        return EthiopianDate.fromGregorian(date).formatFull();
      }
      final monthName = EthiopianDate.gregorianMonthsAmharic[(date.month - 1).clamp(0, 11)];
      return '$monthName ${date.day}፣ ${date.year}';
    }
    // Default English format: "Oct 31, 2026"
    const enMonths = <String>['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    return '${enMonths[date.month - 1]} ${date.day}, ${date.year}';
  }

  /// Localized relative time ("2 hours ago" / "ከ 2 ሰዓት በፊት")
  static String formatRelative(DateTime date, {required String languageCode}) {
    final now = DateTime.now();
    final diff = now.difference(date);

    if (languageCode == 'am') {
      if (diff.inSeconds < 60) return 'አሁን';
      if (diff.inMinutes < 60) return 'ከ${diff.inMinutes} ደቂቃ በፊት';
      if (diff.inHours < 24) return 'ከ${diff.inHours} ሰዓት በፊት';
      if (diff.inDays == 1) return 'ትናንት';
      if (diff.inDays < 7) return 'ከ${diff.inDays} ቀናት በፊት';
      if (diff.inDays < 30) return 'ከ${(diff.inDays / 7).floor()} ሳምንት በፊት';
      return EthiopianDate.fromGregorian(date).formatFull();
    }

    if (diff.inSeconds < 60) return 'Just now';
    if (diff.inMinutes < 60) return '${diff.inMinutes}m ago';
    if (diff.inHours < 24) return '${diff.inHours}h ago';
    if (diff.inDays == 1) return 'Yesterday';
    if (diff.inDays < 7) return '${diff.inDays}d ago';
    return '${date.day}/${date.month}/${date.year}';
  }
}
