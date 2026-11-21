// File generated for Fira Link Platform (Firebase Project: fira-link)
import 'package:firebase_core/firebase_core.dart' show FirebaseOptions;
import 'package:flutter/foundation.dart'
    show defaultTargetPlatform, kIsWeb, TargetPlatform;

/// Default [FirebaseOptions] for use with your Firebase apps.
class DefaultFirebaseOptions {
  static FirebaseOptions get currentPlatform {
    if (kIsWeb) {
      return web;
    }
    switch (defaultTargetPlatform) {
      case TargetPlatform.android:
        return android;
      case TargetPlatform.iOS:
        return ios;
      default:
        return android;
    }
  }

  static const FirebaseOptions web = FirebaseOptions(
    apiKey: 'AIzaSyCIBAv6-xBuyAW0pUicGD538Y1WNr8UJSE',
    appId: '1:1047667063241:web:4bf557340c182654cb1018',
    messagingSenderId: '1047667063241',
    projectId: 'fira-link',
    storageBucket: 'fira-link.firebasestorage.app',
  );

  static const FirebaseOptions android = FirebaseOptions(
    apiKey: 'AIzaSyCIBAv6-xBuyAW0pUicGD538Y1WNr8UJSE',
    appId: '1:1047667063241:android:4bf557340c182654cb1018',
    messagingSenderId: '1047667063241',
    projectId: 'fira-link',
    storageBucket: 'fira-link.firebasestorage.app',
  );

  static const FirebaseOptions ios = FirebaseOptions(
    apiKey: 'AIzaSyCIBAv6-xBuyAW0pUicGD538Y1WNr8UJSE',
    appId: '1:1047667063241:ios:4bf557340c182654cb1018',
    messagingSenderId: '1047667063241',
    projectId: 'fira-link',
    storageBucket: 'fira-link.firebasestorage.app',
  );
}

