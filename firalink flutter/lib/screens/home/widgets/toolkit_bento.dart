import 'package:flutter/material.dart';

import '../../ai_chat/ai_chat_screen.dart';
import '../../coffee_toolkit/coffee_offer_generator_screen.dart';
import '../../coffee_toolkit/coffee_profit_calculator_screen.dart';
import '../../coffee_toolkit/coffee_toolkit_hub_screen.dart';
import '../../document_scanner/fira_document_scanner_screen.dart';
import 'home_design_constants.dart';

class ToolkitBento extends StatelessWidget {
  const ToolkitBento({super.key});

  @override
  Widget build(BuildContext context) {
    final tools = <_ToolData>[
      _ToolData(
        title: 'Ask Fira AI',
        subtitle: 'Trade and learning guidance',
        icon: Icons.auto_awesome_outlined,
        destination: const AiChatScreen(),
      ),
      _ToolData(
        title: 'Calculate profit',
        subtitle: 'Check FOB and CIF margins',
        icon: Icons.calculate_outlined,
        destination: const CoffeeProfitCalculatorScreen(),
      ),
      _ToolData(
        title: 'Create offer',
        subtitle: 'Prepare an export offer sheet',
        icon: Icons.description_outlined,
        destination: const CoffeeOfferGeneratorScreen(),
      ),
      _ToolData(
        title: 'Scan document',
        subtitle: 'Extract and analyze real content',
        icon: Icons.document_scanner_outlined,
        destination: const FiraDocumentScannerScreen(),
      ),
    ];

    return Padding(
      padding: const EdgeInsets.symmetric(
        horizontal: HomeDesignConstants.horizontalPadding,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Quick tools',
                      style: TextStyle(
                        color: HomeDesignConstants.text(context),
                        fontSize: 18,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                    const SizedBox(height: 3),
                    Text(
                      'Complete common export tasks',
                      style: TextStyle(
                        color: HomeDesignConstants.mutedText(context),
                        fontSize: 12.5,
                      ),
                    ),
                  ],
                ),
              ),
              TextButton(
                onPressed: () => Navigator.of(context).push(
                  MaterialPageRoute(
                    builder: (_) => const CoffeeToolkitHubScreen(),
                  ),
                ),
                child: const Text('See all'),
              ),
            ],
          ),
          const SizedBox(height: 12),
          LayoutBuilder(
            builder: (context, constraints) {
              final columns = constraints.maxWidth >= 720 ? 4 : 2;
              const gap = 10.0;
              final width =
                  (constraints.maxWidth - (gap * (columns - 1))) / columns;
              return Wrap(
                spacing: gap,
                runSpacing: gap,
                children: tools
                    .map(
                      (tool) => SizedBox(
                        width: width,
                        child: _ToolTile(tool: tool),
                      ),
                    )
                    .toList(),
              );
            },
          ),
        ],
      ),
    );
  }
}

class _ToolTile extends StatelessWidget {
  const _ToolTile({required this.tool});
  final _ToolData tool;

  @override
  Widget build(BuildContext context) {
    final primary = HomeDesignConstants.brandPrimary(context);
    return Material(
      color: HomeDesignConstants.surface(context),
      shape: RoundedRectangleBorder(
        side: BorderSide(color: primary.withValues(alpha: .12)),
        borderRadius: BorderRadius.circular(16),
      ),
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: () => Navigator.of(
          context,
        ).push(MaterialPageRoute(builder: (_) => tool.destination)),
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Icon(tool.icon, color: primary, size: 23),
              const SizedBox(height: 14),
              Text(
                tool.title,
                style: TextStyle(
                  color: HomeDesignConstants.text(context),
                  fontSize: 13.5,
                  fontWeight: FontWeight.w700,
                ),
              ),
              const SizedBox(height: 3),
              Text(
                tool.subtitle,
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
                style: TextStyle(
                  color: HomeDesignConstants.mutedText(context),
                  fontSize: 11.5,
                  height: 1.3,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _ToolData {
  const _ToolData({
    required this.title,
    required this.subtitle,
    required this.icon,
    required this.destination,
  });

  final String title;
  final String subtitle;
  final IconData icon;
  final Widget destination;
}
