import 'package:eduex_app/models/home_data.dart';
import 'package:eduex_app/screens/home/models/market_data.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  test('parses real homepage context, progress, and sourced market data', () {
    final home = HomeData.fromJson({
      'data': {
        'banners': [],
        'categories': [],
        'popular_courses': [],
        'ai_suggestions': [],
        'recent_courses': [],
        'user_context': {
          'authenticated': true,
          'role': 'student',
          'persona': 'seller',
          'capabilities': {'learn': true, 'sell_coffee': false},
        },
        'continue_learning': {
          'course_id': 9,
          'title': 'Coffee Export Fundamentals',
          'current_lesson': 3,
          'total_lessons': 10,
          'progress': 0.2,
        },
        'market_items': [
          {
            'label': 'Arabica C',
            'value': '250.00 US¢/lb',
            'trend': 'up',
            'source': 'ICE',
            'as_of': '2026-11-04',
          },
        ],
      },
    });

    expect(home.userContext.role, 'student');
    expect(home.userContext.persona, 'seller');
    expect(home.userContext.can('learn'), isTrue);
    expect(home.userContext.can('sell_coffee'), isFalse);
    expect(home.continueLearning?.courseId, 9);
    expect(home.continueLearning?.currentLesson, 3);
    expect(home.marketItems.single.source, 'ICE');
    expect(home.marketItems.single.trend, MarketTrend.positive);
  });

  test('does not manufacture missing progress or market data', () {
    final home = HomeData.fromJson({
      'data': {
        'banners': [],
        'categories': [],
        'popular_courses': [],
        'ai_suggestions': [],
        'recent_courses': [],
      },
    });

    expect(home.continueLearning, isNull);
    expect(home.marketItems, isEmpty);
    expect(home.userContext.role, 'visitor');
    expect(home.userContext.persona, 'visitor');
  });
}
