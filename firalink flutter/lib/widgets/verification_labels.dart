import 'package:flutter/material.dart';

import '../l10n/app_localizations.dart';
import '../theme/fira_colors.dart';
import '../utils/ethiopian_date_formatter.dart';

/// Localized labels for verification values that come from the server.
class VerificationLabels {
  const VerificationLabels._();

  static String date(BuildContext context, DateTime? d) =>
      d == null ? '' : AppDateFormatter.formatDate(d, languageCode: Localizations.localeOf(context).languageCode);

  static String docType(AppLocalizations l10n, String type) {
    switch (type) {
      case 'trade_license':
        return l10n.docTypeTradeLicense;
      case 'cooperative_certificate':
        return l10n.docTypeCooperativeCertificate;
      case 'association_registration':
        return l10n.docTypeAssociationRegistration;
      case 'certificate_of_incorporation':
        return l10n.docTypeCertificateOfIncorporation;
      case 'tin_certificate':
        return l10n.docTypeTinCertificate;
      case 'owner_id':
        return l10n.docTypeOwnerId;
      case 'bank_letter':
        return l10n.docTypeBankLetter;
      case 'ecta_exporter_certificate':
        return l10n.docTypeEctaExporterCertificate;
      case 'certified_lab':
        return l10n.docTypeCertifiedLab;
      case 'taster_certificate':
        return l10n.docTypeTasterCertificate;
      case 'warehouse':
        return l10n.docTypeWarehouse;
      case 'supplier_operating_license':
        return l10n.docTypeSupplierOperatingLicense;
      case 'lab_report':
        return l10n.kindLabReport;
      default:
        return type;
    }
  }

  static String state(AppLocalizations l10n, String state) {
    switch (state) {
      case 'approved':
        return l10n.docStateApproved;
      case 'in_review':
        return l10n.docStateInReview;
      case 'needs_changes':
        return l10n.docStateNeedsChanges;
      case 'rejected':
        return l10n.docStateRejected;
      case 'expired':
        return l10n.docStateExpired;
      default:
        return l10n.docStateNotUploaded;
    }
  }

  /// Saved review reasons are localized; a reason typed by the reviewer is shown as written.
  static String? reason(AppLocalizations l10n, String? code, String? text) {
    switch (code) {
      case 'blurry_photo':
        return l10n.reasonBlurryPhoto;
      case 'edges_cut_off':
        return l10n.reasonEdgesCutOff;
      case 'name_mismatch':
        return l10n.reasonNameMismatch;
      case 'expired':
        return l10n.reasonExpired;
      case 'wrong_document':
        return l10n.reasonWrongDocument;
      case 'number_unreadable':
        return l10n.reasonNumberUnreadable;
      case 'lot_code_not_visible':
        return l10n.reasonLotCodeNotVisible;
      case 'wrong_subject':
        return l10n.reasonWrongSubject;
      case 'not_taken_in_app':
        return l10n.reasonNotTakenInApp;
      case 'lab_not_confirmed':
        return l10n.reasonLabNotConfirmed;
      default:
        return (text == null || text.isEmpty) ? null : text;
    }
  }

  static String field(AppLocalizations l10n, String field) {
    switch (field) {
      case 'lab_name':
        return l10n.captureFieldLabName;
      case 'taster_name':
        return l10n.captureFieldTasterName;
      case 'warehouse_address':
        return l10n.captureFieldWarehouseAddress;
      default:
        return field;
    }
  }

  static String level(AppLocalizations l10n, String level) {
    switch (level) {
      case 'documented':
        return l10n.levelDocumented;
      case 'lab_backed':
        return l10n.levelLabBacked;
      case 'inspected':
        return l10n.levelInspected;
      default:
        return l10n.levelClaimed;
    }
  }

  static String lotKind(AppLocalizations l10n, String kind) {
    switch (kind) {
      case 'photo_bags':
        return l10n.kindPhotoBags;
      case 'photo_beans':
        return l10n.kindPhotoBeans;
      case 'photo_warehouse':
        return l10n.kindPhotoWarehouse;
      default:
        return l10n.kindLabReport;
    }
  }

  static String lotStatus(AppLocalizations l10n, String status) {
    switch (status) {
      case 'active':
        return l10n.lotStatusActive;
      case 'paused':
        return l10n.lotStatusPaused;
      case 'archived':
        return l10n.lotStatusArchived;
      case 'sold_out':
        return l10n.lotStatusSoldOut;
      default:
        return l10n.lotStatusDraft;
    }
  }

  static String? check(AppLocalizations l10n, String? check) =>
      check == 'account_name_matches' ? l10n.checkAccountNameMatches : null;

  /// Short hint under a document that is not uploaded yet (from the Dossier flow).
  static String? hint(AppLocalizations l10n, String type) {
    switch (type) {
      case 'bank_letter':
        return l10n.verifyHintBankLetter;
      case 'ecta_exporter_certificate':
        return l10n.verifyHintIssueExpiry;
      case 'certified_lab':
        return l10n.verifyHintLab;
      case 'taster_certificate':
        return l10n.verifyHintTaster;
      case 'warehouse':
        return l10n.verifyHintInApp;
      default:
        return null;
    }
  }
}

/// Review state of one document, drawn only from theme tokens.
/// In review (pending) is purple on lavender, never amber or green.
class DocumentStatePill extends StatelessWidget {
  const DocumentStatePill({super.key, required this.state});

  final String state;

  @override
  Widget build(BuildContext context) {
    final fira = context.fira;
    final (Color fg, Color bg) = switch (state) {
      'in_review' => (fira.pending, fira.brandTint),
      'approved' => (fira.success, fira.success.withValues(alpha: 0.12)),
      'needs_changes' || 'rejected' => (fira.danger, fira.danger.withValues(alpha: 0.10)),
      _ => (fira.mutedText, fira.chipUnselectedBg),
    };

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(color: bg, borderRadius: BorderRadius.circular(12)),
      child: Text(
        VerificationLabels.state(context.l10n, state),
        style: TextStyle(color: fg, fontSize: 11, fontWeight: FontWeight.w700),
      ),
    );
  }
}

/// Lot evidence level. Claimed is gray, as the flow says; higher levels use the brand colours.
/// Always shown apart from the business tier.
class LotLevelChip extends StatelessWidget {
  const LotLevelChip({super.key, required this.level});

  final String level;

  @override
  Widget build(BuildContext context) {
    final fira = context.fira;
    final (Color fg, Color bg) = switch (level) {
      'documented' || 'lab_backed' || 'inspected' => (fira.brandPrimary, fira.brandTint),
      _ => (fira.mutedText, fira.chipUnselectedBg),
    };

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(color: bg, borderRadius: BorderRadius.circular(12)),
      child: Text(
        VerificationLabels.level(context.l10n, level),
        style: TextStyle(color: fg, fontSize: 11, fontWeight: FontWeight.w700),
      ),
    );
  }
}
