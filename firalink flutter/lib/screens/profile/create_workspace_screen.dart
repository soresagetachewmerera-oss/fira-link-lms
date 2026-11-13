import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../providers/workspace_provider.dart';

class CreateWorkspaceScreen extends StatefulWidget {
  const CreateWorkspaceScreen({super.key});

  @override
  State<CreateWorkspaceScreen> createState() => _CreateWorkspaceScreenState();
}

class _CreateWorkspaceScreenState extends State<CreateWorkspaceScreen> {
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _countryController = TextEditingController(text: 'Ethiopia');
  final _emailController = TextEditingController();

  String _selectedType = 'exporter';
  bool _isCreating = false;

  @override
  void dispose() {
    _nameController.dispose();
    _countryController.dispose();
    _emailController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
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
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Workspace created and switched successfully!'),
          backgroundColor: Colors.green,
        ),
      );
      Navigator.pop(context);
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(ws.errorMessage ?? 'Failed to create workspace'),
          backgroundColor: Colors.redAccent,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    const primaryColor = Color(0xFF6D1A95);

    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        title: const Text(
          'Add Business Workspace',
          style: TextStyle(color: Color(0xFF1E293B), fontWeight: FontWeight.bold, fontSize: 16),
        ),
        backgroundColor: Colors.white,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: Color(0xFF1E293B)),
          onPressed: () => Navigator.pop(context),
        ),
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(20),
          child: Form(
            key: _formKey,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                const Text(
                  'Multi-Entity Architecture',
                  style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: Color(0xFF1E293B)),
                ),
                const SizedBox(height: 6),
                const Text(
                  'Add an additional company, washing station, or roastery entity. You can switch between them with one click without logging out.',
                  style: TextStyle(fontSize: 13, color: Colors.black54, height: 1.4),
                ),
                const SizedBox(height: 24),

                // Name
                const Text('Company / Entity Name *', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                const SizedBox(height: 6),
                TextFormField(
                  controller: _nameController,
                  decoration: InputDecoration(
                    hintText: 'e.g. Oromia Wet Mill Cooperative',
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  validator: (v) => v == null || v.trim().isEmpty ? 'Company name is required' : null,
                ),
                const SizedBox(height: 18),

                // Business Type
                const Text('Business Classification *', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                const SizedBox(height: 6),
                DropdownButtonFormField<String>(
                  value: _selectedType,
                  decoration: InputDecoration(
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  items: const [
                    DropdownMenuItem(value: 'exporter', child: Text('Licensed Coffee Exporter')),
                    DropdownMenuItem(value: 'supplier', child: Text('Washing Station / Wet Mill Producer')),
                    DropdownMenuItem(value: 'cooperative', child: Text('Primary Cooperative / Union')),
                    DropdownMenuItem(value: 'commercial_farm', child: Text('Commercial Farm Producer')),
                    DropdownMenuItem(value: 'buyer', child: Text('International Green Buyer / Roaster')),
                    DropdownMenuItem(value: 'broker', child: Text('Coffee Broker / Representative')),
                  ],
                  onChanged: (val) {
                    if (val != null) setState(() => _selectedType = val);
                  },
                ),
                const SizedBox(height: 18),

                // Country
                const Text('Country *', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                const SizedBox(height: 6),
                TextFormField(
                  controller: _countryController,
                  decoration: InputDecoration(
                    hintText: 'Operating country',
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  validator: (v) => v == null || v.trim().isEmpty ? 'Country is required' : null,
                ),
                const SizedBox(height: 18),

                // Billing Email
                const Text('Billing Email (Optional)', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                const SizedBox(height: 6),
                TextFormField(
                  controller: _emailController,
                  keyboardType: TextInputType.emailAddress,
                  decoration: InputDecoration(
                    hintText: 'billing@entity.com',
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                ),
                const SizedBox(height: 32),

                ElevatedButton(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: primaryColor,
                    foregroundColor: Colors.white,
                    minimumSize: const Size.fromHeight(52),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                    elevation: 0,
                  ),
                  onPressed: _isCreating ? null : _submit,
                  child: _isCreating
                      ? const SizedBox(
                          width: 22,
                          height: 22,
                          child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                        )
                      : const Text('Create Workspace', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

