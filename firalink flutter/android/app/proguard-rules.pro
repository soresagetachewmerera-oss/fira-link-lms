# Flutter and Android R8/ProGuard Configuration

# Flutter Wrapper
-keep class io.flutter.app.** { *; }
-keep class io.flutter.plugin.** { *; }
-keep class io.flutter.util.** { *; }
-keep class io.flutter.view.** { *; }
-keep class io.flutter.** { *; }
-keep class io.flutter.plugins.** { *; }

# ProGuard rules for Stripe
-dontwarn com.stripe.android.pushProvisioning.**
-dontwarn com.stripe.**
-keep class com.stripe.** { *; }

# Razorpay
-dontwarn com.razorpay.**
-keep class com.razorpay.** { *; }

# Mollie / SSLCommerz
-dontwarn com.plugon.mollie.**
-keep class com.plugon.mollie.** { *; }

# Keep native methods and reflection models
-keepclasseswithmembernames class * {
    native <methods>;
}

-keepclassmembers enum * {
    public static **[] values();
    public static ** valueOf(java.lang.String);
}
