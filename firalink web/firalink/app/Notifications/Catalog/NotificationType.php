<?php

namespace App\Notifications\Catalog;

use App\Notifications\Catalog\Channel as C;
use App\Notifications\Catalog\MessageClass as K;

/**
 * The catalogue of every message Fira Link sends (messaging 2026-11-21, slice 1).
 *
 * One case per message. Its definition (see definition()) says: group (A to K), class, the channels
 * it may use and uses by default, whether a person may turn it off, priority, time to live, the
 * app route a tap opens (always behind the sign-in guard), the auth level, the Android channel and
 * whether it is sent immediately (codes) instead of queued.
 *
 * Texts live in resources/lang/{en,am}/messages.php under messages.<key>.* (subject, preheader,
 * eyebrow, heading, body, cta, push_title, push_body, sms). Templates only display: a badge or the
 * word "Verified" comes from BusinessSummary / TierService, never from a parameter a caller passes.
 */
enum NotificationType: string
{
    // ── A. Security ─────────────────────────────────────────────────────────
    case OtpSignIn = 'security.otp_sign_in';
    case OtpSignUp = 'security.otp_sign_up';
    case OtpPasswordReset = 'security.otp_password_reset';
    case OtpStepUp = 'security.otp_step_up';
    case OtpContactChange = 'security.otp_contact_change';
    case PasswordResetLink = 'security.password_reset_link';
    case NewDeviceSignIn = 'security.new_device_sign_in';
    case PasswordChanged = 'security.password_changed';
    case EmailChanged = 'security.email_changed';
    case PhoneChanged = 'security.phone_changed';
    case TwoFactorEnabled = 'security.two_factor_enabled';
    case TwoFactorDisabled = 'security.two_factor_disabled';
    case RecoveryCodesViewed = 'security.recovery_codes_viewed';
    case RecoveryCodesRegenerated = 'security.recovery_codes_regenerated';
    case SuspiciousActivity = 'security.suspicious_activity';
    case AccountLocked = 'security.account_locked';
    case AccountUnlocked = 'security.account_unlocked';

    // ── B. Account and workspace ────────────────────────────────────────────
    case Welcome = 'account.welcome';
    case WorkspaceCreated = 'workspace.created';
    case MemberInvited = 'workspace.member_invited';
    case InviteAccepted = 'workspace.invite_accepted';
    case InviteDeclined = 'workspace.invite_declined';
    case RoleChanged = 'workspace.role_changed';
    case MemberRemoved = 'workspace.member_removed';

    // ── C. Verification ─────────────────────────────────────────────────────
    case VerificationDocumentsReceived = 'verification.documents_received';
    case VerificationInReview = 'verification.in_review';
    case VerificationApproved = 'verification.approved';
    case VerificationNeedsChanges = 'verification.needs_changes';
    case VerificationRejected = 'verification.rejected';
    case VerificationExpiring = 'verification.expiring';
    case VerificationExpired = 'verification.expired';
    case VerificationTierChanged = 'verification.tier_changed';
    case VerificationWithdrawn = 'verification.withdrawn';
    case BusinessSuspended = 'verification.suspended';
    case BusinessUnsuspended = 'verification.unsuspended';

    // ── D. Marketplace ──────────────────────────────────────────────────────
    case LotPublished = 'lot.published';
    case LotNeedsEvidence = 'lot.needs_evidence';
    case LotEvidenceApproved = 'lot.evidence_approved';
    case LotEvidenceRejected = 'lot.evidence_rejected';
    case LotLevelChanged = 'lot.level_changed';
    case LotExpiring = 'lot.expiring';
    case RfqPosted = 'rfq.posted';
    case QuoteReceived = 'quote.received';
    case QuoteAccepted = 'quote.accepted';
    case QuoteDeclined = 'quote.declined';
    case QuoteNotSelected = 'quote.not_selected';
    case QuoteExpired = 'quote.expired';
    case TradeMessageReceived = 'trade.message_received';

    // ── E. Samples and deals ────────────────────────────────────────────────
    case SampleRequested = 'sample.requested';
    case SamplePaid = 'sample.paid';
    case SampleShipped = 'sample.shipped';
    case SampleReceived = 'sample.received';
    case SampleApproved = 'sample.approved';
    case SampleRejected = 'sample.rejected';
    case DealOpened = 'deal.opened';
    case DealDocumentUploaded = 'deal.document_uploaded';
    case DealMilestoneReached = 'deal.milestone_reached';
    case DealPaymentRequested = 'deal.payment_requested';
    case DealDisputeOpened = 'deal.dispute_opened';
    case DealDisputeResolved = 'deal.dispute_resolved';
    case ReportReceived = 'report.received';
    case PayoutAccountChanged = 'payout.account_changed';
    case PayoutAccountRejected = 'payout.account_rejected';

    // ── F. Payments ─────────────────────────────────────────────────────────
    case PaymentInitiated = 'payment.initiated';
    case PaymentReceipt = 'payment.receipt';
    case PaymentFailed = 'payment.failed';
    case PaymentRefunded = 'payment.refunded';
    case PaymentSlipRejected = 'payment.slip_rejected';
    case PayoutStatus = 'payout.status';

    // ── G. Academy ──────────────────────────────────────────────────────────
    case CourseEnrolled = 'academy.enrolled';
    case LessonReminder = 'academy.lesson_reminder';
    case CourseCompleted = 'academy.course_completed';
    case CertificateIssued = 'academy.certificate_issued';
    case NewCourseInCategory = 'academy.new_course';
    case CourseUpdated = 'academy.course_updated';
    case LiveClassScheduled = 'academy.live_class_scheduled';
    case LiveClassReminder = 'academy.live_class_reminder';
    case LiveClassCancelled = 'academy.live_class_cancelled';
    case InstructorNewEnrollment = 'academy.instructor_new_enrollment';
    case InstructorNewReview = 'academy.instructor_new_review';

    // ── H. Events and tours ─────────────────────────────────────────────────
    case BookingConfirmed = 'booking.confirmed';
    case BookingReminder24h = 'booking.reminder_24h';
    case BookingReminder1h = 'booking.reminder_1h';
    case BookingScheduleChanged = 'booking.schedule_changed';
    case BookingCancelled = 'booking.cancelled';
    case BookingBalanceDue = 'booking.balance_due';
    case BookingOrganizerMessage = 'booking.organizer_message';
    case BookingFollowUp = 'booking.follow_up';

    // ── I. Market ───────────────────────────────────────────────────────────
    case MarketPriceAlert = 'market.price_alert';
    case MarketDigest = 'market.digest';
    case MarketSourceUnavailable = 'market.source_unavailable';

    // ── J. System and admin ─────────────────────────────────────────────────
    case Announcement = 'system.announcement';
    case Maintenance = 'system.maintenance';
    case PolicyUpdate = 'system.policy_update';
    case DataExportReady = 'system.data_export_ready';
    case DeletionRequested = 'system.deletion_requested';
    case DeletionConfirmed = 'system.deletion_confirmed';
    case DeletionRejected = 'system.deletion_rejected';
    case StaffVerificationSubmitted = 'staff.verification_submitted';
    case StaffVerificationSummary = 'staff.verification_summary';
    case StaffNewInstructor = 'staff.new_instructor';
    case StaffContactForm = 'staff.contact_form';

    // ── K. Marketing ────────────────────────────────────────────────────────
    case Newsletter = 'marketing.newsletter';
    case MarketingAnnouncement = 'marketing.announcement';

    public const AUTH_SIGNED_IN = 'signed_in';
    public const AUTH_STAFF = 'staff';
    public const AUTH_NONE = 'none'; // codes and links: no app screen behind them

    public const ANDROID_CHANNELS = ['security', 'deals', 'learning', 'updates'];

    public function definition(): TypeDefinition
    {
        return self::table()[$this->value];
    }

    public function messageClass(): MessageClass
    {
        return $this->definition()->class;
    }

    public function canDisable(): bool
    {
        return $this->definition()->canDisable;
    }

    public function sendsImmediately(): bool
    {
        return $this->definition()->immediate;
    }

    public function allows(string $channel): bool
    {
        return in_array($channel, $this->definition()->channels, true);
    }

    /** messages.<key> */
    public function translationPrefix(): string
    {
        return 'messages.' . $this->value;
    }

    /** @return array<string, TypeDefinition> */
    private static function table(): array
    {
        static $table = null;
        if ($table !== null) {
            return $table;
        }

        $day = 86400;
        $sec = fn (string $group = 'A', array $ch = [C::Mail, C::Database, C::Push], ?array $def = null, string $route = '/settings', int $ttl = 7 * 86400)
            => new TypeDefinition($group, K::Security, $ch, $def ?? $ch, false, 'high', $ttl, $route, self::AUTH_SIGNED_IN, 'security', false);
        $code = fn () => new TypeDefinition('A', K::Security, [C::Sms, C::Mail], [C::Sms, C::Mail], false, 'high', 600, null, self::AUTH_NONE, 'security', true);
        $tx = fn (string $group, string $route, string $android = 'updates', array $ch = [C::Mail, C::Database, C::Push], bool $canDisable = false, ?string $param = null)
            => new TypeDefinition($group, K::Transactional, $ch, $ch, $canDisable, 'normal', 7 * $day, $route, self::AUTH_SIGNED_IN, $android, false, $param);
        $prod = fn (string $group, string $route, string $android = 'updates', array $ch = [C::Mail, C::Database, C::Push], int $ttl = 7 * 86400, ?string $param = null)
            => new TypeDefinition($group, K::Product, $ch, array_values(array_filter($ch, fn ($c) => K::Product->defaultOn($c))), true, 'low', $ttl, $route, self::AUTH_SIGNED_IN, $android, false, $param);
        $staff = fn () => new TypeDefinition('J', K::Transactional, [C::Mail], [C::Mail], false, 'normal', 2 * $day, null, self::AUTH_STAFF, 'updates', false);
        $mkt = fn () => new TypeDefinition('K', K::Marketing, [C::Mail, C::Database, C::Push], [], true, 'low', 14 * $day, '/notifications', self::AUTH_SIGNED_IN, 'updates', false);

        $deal = fn (string $android = 'deals') => $tx('E', '/deal-room', $android, param: 'deal_room_id');
        $sample = fn () => $tx('E', '/marketplace/samples/tracking', 'deals', param: 'sample_id');

        $table = [
            // A
            self::OtpSignIn->value => $code(),
            self::OtpSignUp->value => $code(),
            self::OtpPasswordReset->value => $code(),
            self::OtpStepUp->value => $code(),
            self::OtpContactChange->value => $code(),
            self::PasswordResetLink->value => new TypeDefinition('A', K::Security, [C::Mail], [C::Mail], false, 'high', 3600, null, self::AUTH_NONE, 'security', true),
            self::NewDeviceSignIn->value => $sec(),
            self::PasswordChanged->value => $sec(ch: [C::Mail, C::Database, C::Push, C::Sms], def: [C::Mail, C::Database, C::Push]),
            self::EmailChanged->value => $sec(ch: [C::Mail, C::Database, C::Push, C::Sms], def: [C::Mail, C::Database, C::Push]),
            self::PhoneChanged->value => $sec(ch: [C::Mail, C::Database, C::Push, C::Sms], def: [C::Mail, C::Database, C::Push, C::Sms]),
            self::TwoFactorEnabled->value => $sec(ch: [C::Mail]),
            self::TwoFactorDisabled->value => $sec(ch: [C::Mail]),
            self::RecoveryCodesViewed->value => $sec(ch: [C::Mail]),
            self::RecoveryCodesRegenerated->value => $sec(ch: [C::Mail]),
            self::SuspiciousActivity->value => $sec(ch: [C::Mail, C::Database, C::Push, C::Sms], def: [C::Mail, C::Database, C::Push]),
            self::AccountLocked->value => $sec(ch: [C::Mail, C::Sms], def: [C::Mail, C::Sms], route: '/auth'),
            self::AccountUnlocked->value => $sec(ch: [C::Mail]),

            // B
            self::Welcome->value => $tx('B', '/home', ch: [C::Mail, C::Database]),
            self::WorkspaceCreated->value => $tx('B', '/profile', ch: [C::Mail, C::Database]),
            self::MemberInvited->value => $tx('B', '/profile'),
            self::InviteAccepted->value => $tx('B', '/profile', ch: [C::Database, C::Push], canDisable: true),
            self::InviteDeclined->value => $tx('B', '/profile', ch: [C::Database, C::Push], canDisable: true),
            self::RoleChanged->value => $tx('B', '/profile'),
            self::MemberRemoved->value => $tx('B', '/profile'),

            // C
            self::VerificationDocumentsReceived->value => $tx('C', '/marketplace/verification', ch: [C::Mail, C::Database]),
            self::VerificationInReview->value => $tx('C', '/marketplace/verification'),
            self::VerificationApproved->value => $tx('C', '/marketplace/verification'),
            self::VerificationNeedsChanges->value => $tx('C', '/marketplace/verification'),
            self::VerificationRejected->value => $tx('C', '/marketplace/verification'),
            self::VerificationExpiring->value => $tx('C', '/marketplace/verification'),
            self::VerificationExpired->value => $tx('C', '/marketplace/verification'),
            self::VerificationTierChanged->value => $tx('C', '/marketplace/verification'),
            self::VerificationWithdrawn->value => $tx('C', '/marketplace/verification'),
            self::BusinessSuspended->value => $tx('C', '/marketplace/verification', ch: [C::Mail, C::Database, C::Push, C::Sms]),
            self::BusinessUnsuspended->value => $tx('C', '/marketplace/verification'),

            // D
            self::LotPublished->value => $tx('D', '/marketplace/seller', 'deals', [C::Database, C::Push], true),
            self::LotNeedsEvidence->value => $tx('D', '/marketplace/seller', 'deals'),
            self::LotEvidenceApproved->value => $tx('D', '/marketplace/seller', 'deals', [C::Database, C::Push], true),
            self::LotEvidenceRejected->value => $tx('D', '/marketplace/seller', 'deals'),
            self::LotLevelChanged->value => $tx('D', '/marketplace/seller', 'deals', [C::Database, C::Push], true),
            self::LotExpiring->value => $tx('D', '/marketplace/seller', 'deals'),
            self::RfqPosted->value => $prod('D', '/marketplace/rfqs', 'deals', [C::Mail, C::Database, C::Push], 3 * $day),
            self::QuoteReceived->value => $tx('D', '/marketplace/rfqs', 'deals'),
            self::QuoteAccepted->value => $tx('D', '/marketplace/rfqs', 'deals'),
            self::QuoteDeclined->value => $tx('D', '/marketplace/rfqs', 'deals', [C::Database, C::Push], true),
            self::QuoteNotSelected->value => $tx('D', '/marketplace/rfqs', 'deals', [C::Database, C::Push], true),
            self::QuoteExpired->value => $tx('D', '/marketplace/rfqs', 'deals', [C::Database, C::Push], true),
            self::TradeMessageReceived->value => $tx('D', '/notifications', 'deals', [C::Mail, C::Database, C::Push], true),

            // E
            self::SampleRequested->value => $sample(),
            self::SamplePaid->value => $sample(),
            self::SampleShipped->value => $sample(),
            self::SampleReceived->value => $sample(),
            self::SampleApproved->value => $sample(),
            self::SampleRejected->value => $sample(),
            self::DealOpened->value => $deal(),
            self::DealDocumentUploaded->value => $deal(),
            self::DealMilestoneReached->value => $deal(),
            self::DealPaymentRequested->value => $deal(),
            self::DealDisputeOpened->value => $deal(),
            self::DealDisputeResolved->value => $deal(),
            self::ReportReceived->value => $tx('E', '/notifications', 'updates', [C::Mail, C::Database]),
            self::PayoutAccountChanged->value => new TypeDefinition('E', K::Security, [C::Mail, C::Database, C::Push, C::Sms], [C::Mail, C::Database, C::Push, C::Sms], false, 'high', 7 * $day, '/marketplace/seller', self::AUTH_SIGNED_IN, 'security', false),
            self::PayoutAccountRejected->value => $tx('E', '/marketplace/seller', 'deals'),

            // F
            self::PaymentInitiated->value => $tx('F', '/payment-history', 'deals', [C::Database]),
            self::PaymentReceipt->value => $tx('F', '/payment-history', 'deals', [C::Mail, C::Database, C::Push, C::Sms]),
            self::PaymentFailed->value => $tx('F', '/payment-history', 'deals'),
            self::PaymentRefunded->value => $tx('F', '/payment-history', 'deals'),
            self::PaymentSlipRejected->value => $tx('F', '/payment-history', 'deals'),
            self::PayoutStatus->value => $tx('F', '/marketplace/seller', 'deals'),

            // G
            self::CourseEnrolled->value => $tx('G', '/my-courses', 'learning', [C::Mail, C::Database, C::Push, C::Sms]),
            self::LessonReminder->value => $prod('G', '/my-courses', 'learning', [C::Database, C::Push], $day),
            self::CourseCompleted->value => $tx('G', '/my-courses', 'learning', [C::Mail, C::Database, C::Push], true),
            self::CertificateIssued->value => $tx('G', '/certificates', 'learning'),
            self::NewCourseInCategory->value => $prod('G', '/home', 'learning'),
            self::CourseUpdated->value => $prod('G', '/my-courses', 'learning'),
            self::LiveClassScheduled->value => $prod('G', '/my-courses', 'learning'),
            self::LiveClassReminder->value => $prod('G', '/my-courses', 'learning', [C::Database, C::Push], 3600),
            self::LiveClassCancelled->value => $tx('G', '/my-courses', 'learning'),
            self::InstructorNewEnrollment->value => $prod('G', '/dashboard', 'learning'),
            self::InstructorNewReview->value => $prod('G', '/dashboard', 'learning'),

            // H
            self::BookingConfirmed->value => $tx('H', '/bookings', 'updates', [C::Mail, C::Database, C::Push, C::Sms]),
            self::BookingReminder24h->value => $prod('H', '/bookings', 'updates', [C::Database, C::Push, C::Sms], $day),
            self::BookingReminder1h->value => $prod('H', '/bookings', 'updates', [C::Database, C::Push], 3600),
            self::BookingScheduleChanged->value => $tx('H', '/bookings', 'updates', [C::Mail, C::Database, C::Push, C::Sms]),
            self::BookingCancelled->value => $tx('H', '/bookings', 'updates', [C::Mail, C::Database, C::Push, C::Sms]),
            self::BookingBalanceDue->value => $tx('H', '/bookings'),
            self::BookingOrganizerMessage->value => $tx('H', '/bookings', 'updates', [C::Mail, C::Database], true),
            self::BookingFollowUp->value => $prod('H', '/bookings'),

            // I
            self::MarketPriceAlert->value => new TypeDefinition('I', K::Product, [C::Database, C::Push, C::Mail], [], true, 'normal', 3600, '/coffee-toolkit/market', self::AUTH_SIGNED_IN, 'updates', false),
            self::MarketDigest->value => new TypeDefinition('I', K::Product, [C::Mail, C::Database], [], true, 'low', $day, '/coffee-toolkit/market', self::AUTH_SIGNED_IN, 'updates', false),
            self::MarketSourceUnavailable->value => $staff(),

            // J
            self::Announcement->value => $prod('J', '/notifications'),
            self::Maintenance->value => $prod('J', '/notifications', 'updates', [C::Mail, C::Database, C::Push], $day),
            self::PolicyUpdate->value => $tx('J', '/settings', ch: [C::Mail, C::Database]),
            self::DataExportReady->value => $tx('J', '/settings', ch: [C::Mail, C::Database]),
            self::DeletionRequested->value => $sec('J'),
            self::DeletionConfirmed->value => $sec('J', ch: [C::Mail], route: '/home'),
            self::DeletionRejected->value => $sec('J', ch: [C::Mail, C::Database]),
            self::StaffVerificationSubmitted->value => $staff(),
            self::StaffVerificationSummary->value => $staff(),
            self::StaffNewInstructor->value => $staff(),
            self::StaffContactForm->value => $staff(),

            // K
            self::Newsletter->value => $mkt(),
            self::MarketingAnnouncement->value => $mkt(),
        ];

        return $table;
    }

    /** @return array<string, self[]> types by group letter, A to K */
    public static function byGroup(): array
    {
        $out = [];
        foreach (self::cases() as $t) {
            $out[$t->definition()->group][] = $t;
        }
        ksort($out);

        return $out;
    }
}
