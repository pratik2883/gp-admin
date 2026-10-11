import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:gp_app/ui/widgets/app_network_avatar.dart';

void main() {
  group('avatarDecodeExtent (M4-03)', () {
    test('scales the decode box by devicePixelRatio', () {
      expect(avatarDecodeExtent(size: 48, devicePixelRatio: 3.0), 144);
      expect(avatarDecodeExtent(size: 52, devicePixelRatio: 2.0), 104);
      expect(avatarDecodeExtent(size: 64, devicePixelRatio: 1.0), 64);
    });

    test('rounds fractional physical sizes instead of truncating', () {
      expect(avatarDecodeExtent(size: 42, devicePixelRatio: 2.75), 116);
      expect(avatarDecodeExtent(size: 40, devicePixelRatio: 2.625), 105);
    });
  });

  testWidgets('blank url renders the fallback and builds no network image',
      (tester) async {
    await tester.pumpWidget(
      const MaterialApp(
        home: Scaffold(
          body: AppNetworkAvatar(
            url: '   ',
            size: 48,
            fallback: Text('A'),
          ),
        ),
      ),
    );

    expect(find.text('A'), findsOneWidget);
    expect(find.byType(CachedNetworkImage), findsNothing);
    expect(tester.getSize(find.byType(AppNetworkAvatar)), const Size(48, 48));
  });

  testWidgets('null url renders the fallback', (tester) async {
    await tester.pumpWidget(
      const MaterialApp(
        home: Scaffold(body: AppNetworkAvatar(url: null, size: 40)),
      ),
    );
    expect(find.byType(CachedNetworkImage), findsNothing);
    expect(tester.getSize(find.byType(AppNetworkAvatar)), const Size(40, 40));
  });

  testWidgets('a url decodes at the physical-pixel extent, not source size',
      (tester) async {
    tester.view.devicePixelRatio = 3.0;
    addTearDown(tester.view.reset);

    await tester.pumpWidget(
      const MaterialApp(
        home: Scaffold(
          body: AppNetworkAvatar(url: 'https://example.test/a.jpg', size: 48),
        ),
      ),
    );

    final image = tester.widget<CachedNetworkImage>(
      find.byType(CachedNetworkImage),
    );
    expect(image.memCacheWidth, 144, reason: '48 logical px @3x == 144 physical');
    expect(image.memCacheHeight, 144);

    // Drain any cache/provider work scheduled during the first frame.
    await tester.pump(const Duration(milliseconds: 1));
  });
}
