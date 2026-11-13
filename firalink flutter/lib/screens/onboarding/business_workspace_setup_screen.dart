import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../providers/workspace_provider.dart';
import 'intent_welcome_screen.dart';

class BusinessWorkspaceSetupScreen extends StatefulWidget {
  final String intent; // 'export' or 'buy'

  const BusinessWorkspaceSetupScreen({super.key, required this.intent});

  @override
  State<BusinessWorkspaceSetupScreen> createState() =>
      _BusinessWorkspaceSetupScreenState();
}

class _BusinessWorkspaceSetupScreenState
    extends State<BusinessWorkspaceSetupScreen> {
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _countryController = TextEditingController(text: 'Ethiopia');
  final _emailController = TextEditingController();

  late String _selectedType;
  bool _isCreating = false;

  @override
  void initState() {
    super.initState();
    _selectedType = widget.intent == 'buy' ? 'buyer' : 'exporter';
    if (widget.intent == 'buy') {
      _countryController.text = 'United States';
    }
  }

  @override
  void dispose() {
    _nameController.dispose();
    _countryController.dispose();
    _emailController.dispose();
    super.dispose();
  }

  Future<void> _handleCreate() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isCreating = true);
    final ws = Provider.of<WorkspaceProvider>(context, listen: false);

    final success = await ws.createWorkspace(
      name: _nameController.text.trim(),
      businessType: _selectedType,
      country: _countryController.text.trim(),
      billingEmail: _emailController.text.trim().isNotEmpty
          ? _emailController.text.trim()
          : null,
    );

    if (!mounted) return;
    setState(() => _isCreating = false);

    if (success) {
      Navigator.pushReplacement(
        context,
        MaterialPageRoute(
          builder: (_) => IntentWelcomeScreen(intent: widget.intent),
        ),
      );
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(ws.errorMessage ?? 'Could not create workspace'),
          backgroundColor: Colors.redAccent,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    const primaryColor = Color(0xFF6D1A95);
    final isBuy = widget.intent == 'buy';

    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: Color(0xFF1E293B)),
          onPressed: () => Navigator.pop(context),
        ),
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.symmetric(horizontal: 24.0, vertical: 12.0),
          child: Form(
            key: _formKey,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Text(
                  isBuy ? 'Setup Your Sourcing Entity' : 'Setup Your Export Workspace',
                  style: const TextStyle(
                    fontSize: 22,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF1E293B),
                  ),
                ),
                const SizedBox(height: 8),
                Text(
                  isBuy
                      ? 'Create your company or roastery workspace to request lots, negotiate quotes, and receive sample deliveries.'
                      : 'Create your trade entity to manage coffee inventory, lot evidence, and export contracts.',
                  style: const TextStyle(fontSize: 13, color: Colors.black54, height: 1.4),
                ),
                const SizedBox(height: 28),

                // Company Name
                const Text(
                  'Company or Trade Name *',
                  style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13),
                ),
                const SizedBox(height: 6),
                TextFormField(
                  controller: _nameController,
                  decoration: InputDecoration(
                    hintText: isBuy ? 'e.g. Nordic Roasters ApS' : 'e.g. Abyssinia Specialty Exporters',
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                    contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                  ),
                  validator: (v) =>
                      v == null || v.trim().isEmpty ? 'Company name is required' : null,
                ),
                const SizedBox(height: 20),

                // Business Type Dropdown
                const Text(
                  'Business Classification *',
                  style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13),
                ),
                const SizedBox(height: 6),
                DropdownButtonFormField<String>(
                  value: _selectedType,
                  decoration: InputDecoration(
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                    contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                  ),
                  items: isBuy
                      ? const [
                          DropdownMenuItem(value: 'buyer', child: Text('International Green Buyer / Roaster')),
                          DropdownMenuItem(value: 'roaster', child: Text('Specialty Coffee Roastery')),
                          DropdownMenuItem(value: 'broker', child: Text('Import Broker / Representative')),
                        ]
                      : const [
                          DropdownMenuItem(value: 'exporter', child: Text('Licensed Coffee Exporter')),
                          DropdownMenuItem(value: 'cooperative', child: Text('Primary Cooperative / Union')),
                          DropdownMenuItem(value: 'commercial_farm', child: Text('Commercial Farm Producer')),
                          DropdownMenuItem(value: 'supplier', child: Text('Washing Station / Wet Mill')),
                        ],
                  onChanged: (val) {
                    if (val != null) setState(() => _selectedType = val);
                  },
                ),
                const SizedBox(height: 20),

                // Country
                const Text(
                  'Operating Country *',
                  style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13),
                ),
                const SizedBox(height: 6),
                TextFormField(
                  controller: _countryController,
                  decoration: InputDecoration(
                    hintText: 'e.g. Ethiopia, Germany, USA, Japan',
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                    contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                  ),
                  validator: (v) =>
                      v == null || v.trim().isEmpty ? 'Country is required' : null,
                ),
                const SizedBox(height: 20),

                // Billing Email (Optional)
                const Text(
                  'Official Contact / Billing Email',
                  style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13),
                ),
                const SizedBox(height: 6),
                TextFormField(
                  controller: _emailController,
                  keyboardType: TextInputType.emailAddress,
                  decoration: InputDecoration(
                    hintText: 'trade@yourcompany.com (optional)',
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                    contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                  ),
                ),
                const SizedBox(height: 36),

                ElevatedButton(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: primaryColor,
                    foregroundColor: Colors.white,
                    minimumSize: const Size.fromHeight(52),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(16),
                    ),
                    elevation: 0,
                  ),
                  onPressed: _isCreating ? null : _handleCreate,
                  child: _isCreating
                      ? const SizedBox(
                          width: 22,
                          height: 22,
                          child: CircularProgressIndicator(
                            color: Colors.white,
                            strokeWidth: 2,
                          ),
                        )
                      : const Text(
                          'Create Workspace & Finish',
                          style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                        ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

