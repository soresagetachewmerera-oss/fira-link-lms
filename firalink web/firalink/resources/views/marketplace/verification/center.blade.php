@extends('layouts.app')

@section('title', 'Organization Verification & Compliance Vault | Fira Link')

@push('styles')
<style>
.role-tab.active {
    border-color: #6D1A95;
    background-color: #f5f3ff;
    color: #6D1A95;
}
</style>
@endpush

@section('content')
<div class="min-h-screen bg-gray-50/50 pb-20">
    <!-- Header -->
    <div class="bg-gradient-to-r from-[#1E072A] via-[#3D0E54] to-[#6D1A95] text-white py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 text-xs font-semibold uppercase tracking-wider text-purple-200 mb-2 border border-white/10">
                    <i class="fas fa-shield-alt mr-1"></i> Trust &amp; Legal Compliance Vault
                </div>
                <h1 class="text-3xl font-extrabold tracking-tight">
                    Organization Verification
                </h1>
                <p class="text-sm text-purple-100 mt-1">
                    Authenticate your business identity, sector licenses, and export rights. Trust is never for sale.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('marketplace.dashboard') }}" class="px-4 py-2 bg-white/10 hover:bg-white/20 text-white rounded-lg text-sm font-medium border border-white/20 transition">
                    <i class="fas fa-arrow-left mr-1"></i> Return to Trade Desk
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-6">
        <!-- Trust Banner -->
        <div class="bg-white rounded-xl shadow-sm border border-purple-100 p-5 mb-8 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-purple-50 text-[#6D1A95] flex items-center justify-center font-bold text-xl flex-shrink-0">
                    <i class="fas fa-certificate"></i>
                </div>
                <div>
                    <h3 class="font-bold text-gray-900 text-base">
                        {{ $org->name }}
                        @if($org->is_verified)
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-800 ml-2">
                                <i class="fas fa-check-circle"></i> VERIFIED ENTITY
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 ml-2">
                                <i class="fas fa-hourglass-half"></i> VERIFICATION PENDING
                            </span>
                        @endif
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Active Verified Roles: 
                        @if(!empty($org->verified_roles))
                            @foreach($org->verified_roles as $role)
                                <span class="uppercase font-semibold text-purple-700 bg-purple-50 px-2 py-0.5 rounded text-[11px] mr-1">{{ $role }}</span>
                            @endforeach
                        @else
                            <span class="text-gray-400 italic">None approved yet</span>
                        @endif
                        &bull; Jurisdiction: {{ $org->country ?? 'Ethiopia' }}
                    </p>
                </div>
            </div>
            <div class="text-xs text-gray-500 max-w-md bg-gray-50 p-3 rounded-lg border border-gray-100">
                <strong>Zero-Exposure Vault:</strong> All legal and regulatory documents are stored in an encrypted private vault and verified directly against ECTA and MoTI registries.
            </div>
        </div>

        @if(session('success'))
            <div class="mb-6 p-4 rounded-xl bg-green-50 border border-green-200 text-green-800 text-sm flex items-center gap-3">
                <i class="fas fa-check-circle text-lg"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('warning'))
            <div class="mb-6 p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-sm flex items-center gap-3">
                <i class="fas fa-exclamation-triangle text-lg"></i>
                <span>{{ session('warning') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm flex items-center gap-3">
                <i class="fas fa-times-circle text-lg"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <!-- Roles Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            @php
                $rolesMeta = [
                    'exporter' => [
                        'title' => 'Licensed Coffee Exporter',
                        'icon' => 'fa-ship',
                        'desc' => 'Authorized by ECTA and MoTI to issue export contracts, export green coffee, and book deal rooms.',
                        'docs' => [
                            'commercial_reg' => 'Commercial Registration Certificate (MoTI)',
                            'sector_license' => 'ECTA Coffee Export Competence Certificate',
                            'tin_cert' => 'TIN & VAT Registration Certificate',
                            'rep_id' => 'Authorized Signatory Passport / National ID',
                        ],
                    ],
                    'supplier' => [
                        'title' => 'Washing Station / Farm',
                        'icon' => 'fa-tractor',
                        'desc' => 'Wet/dry mill operator or commercial coffee estate with traceable washing stations and processing capacity.',
                        'docs' => [
                            'commercial_reg' => 'Business License / Cooperative Certificate',
                            'sector_license' => 'Washing Station / Wet Mill Operating License',
                            'rep_id' => 'Manager Kebele / National ID',
                        ],
                    ],
                    'buyer' => [
                        'title' => 'International Buyer / Roaster',
                        'icon' => 'fa-mug-hot',
                        'desc' => 'Commercial green coffee importer, roaster, or distributor registered outside Ethiopia.',
                        'docs' => [
                            'commercial_reg' => 'Certificate of Incorporation / Registration',
                            'tin_cert' => 'Tax ID / VAT / EIN Registration',
                            'address_proof' => 'Business Address Utility Bill (< 90 Days)',
                        ],
                    ],
                    'broker' => [
                        'title' => 'Certified Broker / Agent',
                        'icon' => 'fa-handshake',
                        'desc' => 'Trade representative operating under a notarized Letter of Representation (LoR) from a verified principal.',
                        'docs' => [
                            'commercial_reg' => 'Brokerage / Agency Business License',
                            'broker_mandate' => 'Notarized Letter of Representation (LoR)',
                            'rep_id' => 'Agent Passport / National ID',
                        ],
                    ],
                ];
            @endphp

            @foreach($rolesMeta as $roleKey => $meta)
                @php
                    $verification = $verifications->get($roleKey);
                    $status = $verification?->status ?? 'unsubmitted';
                    $statusColors = [
                        'approved' => 'bg-green-100 text-green-800 border-green-200',
                        'pending_review' => 'bg-amber-100 text-amber-800 border-amber-200',
                        'under_scrutiny' => 'bg-purple-100 text-purple-800 border-purple-200',
                        'rejected' => 'bg-red-100 text-red-800 border-red-200',
                        'expired' => 'bg-orange-100 text-orange-800 border-orange-200',
                        'suspended' => 'bg-gray-100 text-gray-800 border-gray-200',
                        'unsubmitted' => 'bg-gray-100 text-gray-600 border-gray-200',
                    ];
                @endphp

                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden flex flex-col justify-between">
                    <div>
                        <!-- Card Header -->
                        <div class="p-6 border-b border-gray-100">
                            <div class="flex items-center justify-between gap-4 mb-2">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-purple-50 text-[#6D1A95] flex items-center justify-center text-lg">
                                        <i class="fas {{ $meta['icon'] }}"></i>
                                    </div>
                                    <div>
                                        <h2 class="font-bold text-gray-900 text-lg">{{ $meta['title'] }}</h2>
                                        <p class="text-xs text-gray-500">{{ $meta['desc'] }}</p>
                                    </div>
                                </div>
                                <span class="px-3 py-1 rounded-full text-xs font-bold border uppercase tracking-wider {{ $statusColors[$status] ?? 'bg-gray-100 text-gray-600' }}">
                                    {{ str_replace('_', ' ', $status) }}
                                </span>
                            </div>

                            @if($verification && $verification->rejection_reason)
                                <div class="mt-3 p-3 bg-red-50 border border-red-200 rounded-lg text-xs text-red-800">
                                    <strong>Compliance Feedback:</strong> {{ $verification->rejection_reason }}
                                </div>
                            @endif

                            @if($verification && $verification->license_expires_at)
                                <div class="mt-2 text-xs text-gray-500 flex items-center gap-2">
                                    <i class="far fa-calendar-alt text-[#6D1A95]"></i>
                                    <span>License Expiry: <strong>{{ $verification->license_expires_at->format('M d, Y') }}</strong></span>
                                    @if($verification->isExpired())
                                        <span class="text-red-600 font-bold ml-1">(Expired - Action Required)</span>
                                    @endif
                                </div>
                            @endif
                        </div>

                        <!-- Document Dossier Checklist -->
                        <div class="p-6 bg-gray-50/50">
                            <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-3">Required Compliance Documents</h4>
                            <div class="space-y-3">
                                @foreach($meta['docs'] as $docType => $docLabel)
                                    @php
                                        $uploadedDoc = $verification ? $verification->getDocumentByType($docType) : null;
                                    @endphp
                                    <div class="p-3 bg-white rounded-xl border border-gray-200 flex items-center justify-between gap-3">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <div class="w-8 h-8 rounded-lg {{ $uploadedDoc ? 'bg-green-50 text-green-600' : 'bg-gray-100 text-gray-400' }} flex items-center justify-center text-sm flex-shrink-0">
                                                <i class="fas {{ $uploadedDoc ? 'fa-check' : 'fa-file-upload' }}"></i>
                                            </div>
                                            <div class="min-w-0">
                                                <div class="text-xs font-semibold text-gray-800 truncate">{{ $docLabel }}</div>
                                                <div class="text-[11px] text-gray-500 truncate">
                                                    @if($uploadedDoc)
                                                        {{ $uploadedDoc->original_filename }} &bull; {{ $uploadedDoc->file_size_kb }} KB
                                                    @else
                                                        <span class="text-amber-600">Pending Upload</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2 flex-shrink-0">
                                            @if($uploadedDoc)
                                                <a href="{{ route('marketplace.verification.download', $uploadedDoc->id) }}" class="px-2.5 py-1 text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 rounded font-medium transition" title="Inspect Document">
                                                    <i class="fas fa-download"></i> View
                                                </a>
                                                @if($status !== 'approved')
                                                    <form action="{{ route('marketplace.verification.delete-document', $uploadedDoc->id) }}" method="POST" onsubmit="return confirm('Remove this document from dossier?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="p-1 text-gray-400 hover:text-red-600 transition" title="Remove">
                                                            <i class="fas fa-trash-alt text-xs"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            @else
                                                <button type="button" onclick="document.getElementById('upload-modal-{{ $roleKey }}-{{ $docType }}').classList.remove('hidden')" class="px-2.5 py-1 text-xs bg-[#6D1A95] hover:bg-[#561477] text-white rounded font-medium transition">
                                                    Upload
                                                </button>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Upload Modal -->
                                    <div id="upload-modal-{{ $roleKey }}-{{ $docType }}" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
                                        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-gray-100">
                                            <h3 class="font-bold text-gray-900 text-base mb-1">Upload {{ $docLabel }}</h3>
                                            <p class="text-xs text-gray-500 mb-4">Files are vaulted privately with AES-256 encryption. Allowed formats: PDF, JPG, PNG (Max 10MB).</p>
                                            <form action="{{ route('marketplace.verification.upload') }}" method="POST" enctype="multipart/form-data">
                                                @csrf
                                                <input type="hidden" name="target_role" value="{{ $roleKey }}">
                                                <input type="hidden" name="document_type" value="{{ $docType }}">
                                                <div class="mb-4">
                                                    <input type="file" name="document" required class="block w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-purple-50 file:text-[#6D1A95] hover:file:bg-purple-100 cursor-pointer">
                                                </div>
                                                <div class="flex items-center justify-end gap-2">
                                                    <button type="button" onclick="document.getElementById('upload-modal-{{ $roleKey }}-{{ $docType }}').classList.add('hidden')" class="px-3 py-1.5 text-xs text-gray-600 hover:bg-gray-100 rounded-lg">Cancel</button>
                                                    <button type="submit" class="px-4 py-1.5 text-xs bg-[#6D1A95] hover:bg-[#561477] text-white rounded-lg font-semibold shadow-sm transition">Upload Document</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Details Form -->
                    <div class="p-6 border-t border-gray-100 bg-white">
                        <form action="{{ route('marketplace.verification.submit') }}" method="POST">
                            @csrf
                            <input type="hidden" name="target_role" value="{{ $roleKey }}">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Tax ID / TIN Number</label>
                                    <input type="text" name="tin_number" value="{{ old('tin_number', $verification->tin_number ?? '') }}" placeholder="e.g. 0012345678" class="w-full text-xs border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-[#6D1A95] focus:outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">License / Reg Number</label>
                                    <input type="text" name="license_number" value="{{ old('license_number', $verification->license_number ?? '') }}" placeholder="e.g. ECTA-EXP-2026-089" class="w-full text-xs border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-[#6D1A95] focus:outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Issuing Authority</label>
                                    <input type="text" name="issuing_authority" value="{{ old('issuing_authority', $verification->issuing_authority ?? ($roleKey === 'exporter' ? 'ECTA' : 'Ministry of Trade')) }}" class="w-full text-xs border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-[#6D1A95] focus:outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">License Expiry Date</label>
                                    <input type="date" name="license_expires_at" value="{{ old('license_expires_at', $verification && $verification->license_expires_at ? $verification->license_expires_at->format('Y-m-d') : '') }}" class="w-full text-xs border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-[#6D1A95] focus:outline-none">
                                </div>
                            </div>

                            <button type="submit" class="w-full py-2.5 px-4 bg-[#6D1A95] hover:bg-[#561477] text-white text-xs font-bold rounded-xl shadow transition flex items-center justify-center gap-2">
                                <i class="fas fa-paper-plane"></i>
                                Submit {{ $meta['title'] }} Dossier for Audit
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection

