/// Comprehensive, production-grade demo seed data for the Coffee B2B Marketplace.
/// Bundled directly into the mobile application to guarantee an instant, offline-first experience.
class MarketplaceDemoData {
  static final List<Map<String, dynamic>> subscriptionPlans = [
    {
      'id': 1,
      'name': 'Exporter Starter',
      'slug': 'seller_starter',
      'role_target': 'seller',
      'description': 'Ideal for newly licensed Ethiopian coffee exporters and specialty washing stations.',
      'price_etb': '1500.00',
      'price_usd': '15.00',
      'billing_cycle': 'monthly',
      'active_listings_limit': 3,
      'rfq_response_limit': 10,
      'featured_slots': 1,
      'verified_badge_included': true,
      'analytics_access': false,
      'priority_support': false,
      'features': [
        'Up to 3 active green coffee lots',
        'Respond to 10 buyer RFQs / month',
        'Verified Exporter trust badge',
        'SCA cupping form & lab certificate uploads',
        'Direct buyer sample parcel requests',
        'Standard email & portal support',
      ],
      'is_popular': false,
      'is_active': true,
      'sort_order': 1,
    },
    {
      'id': 2,
      'name': 'Pro Exporter',
      'slug': 'seller_pro',
      'role_target': 'seller',
      'description': 'The premier package for commercial exporters, estate growers, and regional specialty cooperatives.',
      'price_etb': '4500.00',
      'price_usd': '45.00',
      'billing_cycle': 'monthly',
      'active_listings_limit': 15,
      'rfq_response_limit': 0, // unlimited
      'featured_slots': 5,
      'verified_badge_included': true,
      'analytics_access': true,
      'priority_support': true,
      'features': [
        'Up to 15 active green coffee lots',
        'Unlimited buyer RFQ tender bids',
        '5 Featured Lot slots on Homepage & App',
        'Verified Exporter trust badge',
        'Sensory radar chart generator',
        'Daily ECTA minimum export price benchmark alerts',
        'Direct buyer contact disclosure',
        'Priority WhatsApp & direct phone assistance',
      ],
      'is_popular': true,
      'is_active': true,
      'sort_order': 2,
    },
    {
      'id': 3,
      'name': 'Enterprise Global Trader',
      'slug': 'seller_enterprise',
      'role_target': 'seller',
      'description': 'Built for multi-origin exporter unions, commercial estates, and multinational trading conglomerates.',
      'price_etb': '12000.00',
      'price_usd': '120.00',
      'billing_cycle': 'monthly',
      'active_listings_limit': 0, // unlimited
      'rfq_response_limit': 0,
      'featured_slots': 20,
      'verified_badge_included': true,
      'analytics_access': true,
      'priority_support': true,
      'features': [
        'Unlimited active green coffee lots',
        'Unlimited RFQ submissions & instant bidding',
        'Top-tier priority search & category ranking',
        'Dedicated Fira Link trade broker support',
        'Custom digital showroom & branding',
        'Export documentation assistant (ICO/ECX/ECTA)',
        'Multi-seat staff accounts & team permissions',
        'Direct container booking & Djibouti logistics support',
      ],
      'is_popular': false,
      'is_active': true,
      'sort_order': 3,
    },
    {
      'id': 4,
      'name': 'Verified Buyer (Basic)',
      'slug': 'buyer_verified',
      'role_target': 'buyer',
      'description': 'Free permanent access for international coffee roasters, green coffee buyers, and importers worldwide.',
      'price_etb': '0.00',
      'price_usd': '0.00',
      'billing_cycle': 'monthly',
      'active_listings_limit': 0,
      'rfq_response_limit': 0,
      'featured_slots': 0,
      'verified_badge_included': true,
      'analytics_access': true,
      'priority_support': false,
      'features': [
        'Publish unlimited RFQ procurement tenders',
        'Request green coffee sample parcels',
        'Direct contact with vetted Ethiopian exporters',
        'Daily official minimum export price tracker',
        'Pre-shipment cupping report verification',
        'Standard buyer trade dashboard',
      ],
      'is_popular': false,
      'is_active': true,
      'sort_order': 4,
    },
    {
      'id': 5,
      'name': 'Buyer Concierge & Trade Guarantee',
      'slug': 'buyer_concierge',
      'role_target': 'buyer',
      'description': 'Premium procurement support with third-party cupping inspection and on-the-ground Ethiopian export oversight.',
      'price_etb': '3500.00',
      'price_usd': '35.00',
      'billing_cycle': 'monthly',
      'active_listings_limit': 0,
      'rfq_response_limit': 0,
      'featured_slots': 0,
      'verified_badge_included': true,
      'analytics_access': true,
      'priority_support': true,
      'features': [
        'All Verified Buyer features included',
        'Dedicated on-the-ground Q-Grader pre-shipment cupping',
        'Physical container stuffing inspection in Mojo/Addis',
        'Priority express sample dispatch (DHL/FedEx)',
        'Trade dispute resolution & mediation',
        'Escrow milestone payment protection',
        'Early private preview of new harvest micro-lots',
      ],
      'is_popular': true,
      'is_active': true,
      'sort_order': 5,
    },
  ];

  static final List<Map<String, dynamic>> sellers = [
    {
      'id': 1,
      'name': 'Bensa Highland Specialty Exporters',
      'company_name': 'Bensa Highland Specialty Coffee PLC',
      'company_slug': 'bensa-highland-specialty',
      'export_license_no': 'ECTA-EXP-2024-8842',
      'origin_regions': ['Sidama', 'Guji', 'Yirgacheffe'],
      'washing_stations': ['Kokose Washing Station', 'Hamasho Wet Mill', 'Bombe Fermentation Station'],
      'certifications': ['Organic EU/NOP', 'Fairtrade FLO-ID 4120', 'Rainforest Alliance'],
      'company_bio': 'Premier Ethiopian specialty producer rooted in high-altitude Bensa, Sidama (2,050m - 2,250m). Processing award-winning washed, natural, and anaerobic micro-lots.',
      'contact_email': 'trade@bensahighland.com',
      'contact_phone': '+251 911 223 344',
      'verification_status': 'verified',
    },
    {
      'id': 2,
      'name': 'Yirgacheffe Mountain Gate Exporters',
      'company_name': 'Yirgacheffe Mountain Gate Exporters PLC',
      'company_slug': 'yirgacheffe-mountain-gate',
      'export_license_no': 'ECTA-EXP-2023-7190',
      'origin_regions': ['Yirgacheffe', 'Gedeo'],
      'washing_stations': ['Kochere Central Mill', 'Chelchele Eco-Pulper', 'Aricha High Bed Station'],
      'certifications': ['Rainforest Alliance', 'Organic EU'],
      'company_bio': 'Dedicated to authentic floral and citrus Yirgacheffe coffees. Working with over 1,200 Gedeo smallholders across Kochere and Chelchele.',
      'contact_email': 'export@yirgamountain.com',
      'contact_phone': '+251 922 334 455',
      'verification_status': 'verified',
    },
    {
      'id': 3,
      'name': 'Oromia Guji Highlands Specialty Union',
      'company_name': 'Oromia Guji Highlands Coffee Union',
      'company_slug': 'oromia-guji-highlands',
      'export_license_no': 'ECTA-EXP-2024-9512',
      'origin_regions': ['Guji', 'Borena'],
      'washing_stations': ['Dimtu Drying Station', 'Uraga Gomoro Wet Mill', 'Shakiso Agro-Forest Mill'],
      'certifications': ['Fairtrade', 'Organic NOP', 'Bird Friendly'],
      'company_bio': 'Specializing in intense, wild berry and stone fruit natural coffees from Shakiso and Hambela Wamena.',
      'contact_email': 'trade@gujicoffeeunion.com',
      'contact_phone': '+251 933 445 566',
      'verification_status': 'verified',
    },
    {
      'id': 4,
      'name': 'Kaffa & Limu Biosphere Coffee PLC',
      'company_name': 'Kaffa & Limu Forest Biosphere PLC',
      'company_slug': 'kaffa-limu-biosphere',
      'export_license_no': 'ECTA-EXP-2022-6304',
      'origin_regions': ['Kaffa', 'Limu', 'Jimma'],
      'washing_stations': ['Bonga Wild Station', 'Gomma Wet Mill', 'Agaro Cooperative Station'],
      'certifications': ['Rainforest Alliance', 'UNESCO Biosphere Certified'],
      'company_bio': 'Sourcing wild Arabica from the birthplace of coffee in Kaffa. Canopy shade-grown under ancient indigenous trees.',
      'contact_email': 'sales@kaffabiosphere.com',
      'contact_phone': '+251 944 556 677',
      'verification_status': 'verified',
    },
    {
      'id': 5,
      'name': 'Harrar Ancient Sun Coffee Traders',
      'company_name': 'Harrar Ancient Sun Estates & Millers',
      'company_slug': 'harrar-ancient-sun',
      'export_license_no': 'ECTA-EXP-2024-8119',
      'origin_regions': ['Harrar'],
      'washing_stations': ['Mesela Sun-Drying Compound', 'Dire Dawa Modern Milling Facility'],
      'certifications': ['Geographical Indication (GI) Certified'],
      'company_bio': 'Exporting world-renowned Harrar Longberry natural coffees with signature blueberry, dark chocolate, and spicy winey profiles.',
      'contact_email': 'info@harrarancientsun.com',
      'contact_phone': '+251 955 667 788',
      'verification_status': 'verified',
    },
  ];

  static final List<Map<String, dynamic>> listings = [
    {
      'id': 1,
      'slug': 'sidama-bensa-bombe-anaerobic-micro-lot-g1',
      'title': 'Sidama Bensa Bombe Anaerobic Micro-Lot G1',
      'origin_region': 'Sidama',
      'sub_region': 'Bensa - Bombe Village',
      'washing_station': 'Bombe Fermentation Station',
      'coffee_type': 'arabica',
      'variety': '74112 & 74110',
      'processing_method': 'anaerobic',
      'grade': 'grade_1',
      'screen_size': 'Screen 15/16',
      'cupping_score': 89.75,
      'moisture_content': 10.40,
      'water_activity': 0.58,
      'crop_year': '2025/2026',
      'altitude_min': 2050,
      'altitude_max': 2200,
      'total_quantity_bags': 150,
      'available_quantity_bags': 120,
      'minimum_order_bags': 10,
      'bag_type': 'jute_with_grainpro',
      'bag_weight_kg': 60.00,
      'indicative_price_usd_per_lb': '4.85',
      'indicative_price_usd_per_mt': '10692.00',
      'incoterm': 'FOB Djibouti',
      'port_of_loading': 'Djibouti',
      'sample_available': true,
      'sample_cost_usd': '0.00',
      'tasting_notes': ['Wild Strawberry', 'Papaya', 'Jasmine Blossom', 'Dark Cocoa', 'Champagne Acidity'],
      'sensory_attributes': {
        'aroma': 9.2,
        'acidity': 9.0,
        'body': 8.8,
        'flavor': 9.3,
        'aftertaste': 8.9,
        'balance': 9.1,
      },
      'description': 'Flagship 2025/2026 harvest anaerobic natural lot from Bombe village. Cherry harvested at 22-24 Brix, fermented sealed in airtight barrels under controlled water immersion for 96 hours before slow raised African bed drying.',
      'status': 'active',
      'is_featured': true,
      'primary_image': {
        'url': 'https://images.unsplash.com/photo-1514432324607-a09d9b4aefdd?q=80&w=1200&auto=format&fit=crop',
      },
      'seller': {
        'id': 1,
        'name': 'Bensa Highland Specialty Exporters',
        'seller_profile': {
          'company_name': 'Bensa Highland Specialty Coffee PLC',
          'verification_status': 'verified',
          'export_license_no': 'ECTA-EXP-2024-8842',
        },
      },
    },
    {
      'id': 2,
      'slug': 'yirgacheffe-kochere-grade-1-washed-crown-lot',
      'title': 'Yirgacheffe Kochere Grade 1 Washed Crown Lot',
      'origin_region': 'Yirgacheffe',
      'sub_region': 'Kochere',
      'washing_station': 'Borena Wet Mill',
      'coffee_type': 'arabica',
      'variety': 'Kurume & Dega Heirloom',
      'processing_method': 'washed',
      'grade': 'grade_1',
      'screen_size': 'Screen 14+',
      'cupping_score': 88.50,
      'moisture_content': 10.20,
      'water_activity': 0.55,
      'crop_year': '2025/2026',
      'altitude_min': 1950,
      'altitude_max': 2100,
      'total_quantity_bags': 300,
      'available_quantity_bags': 250,
      'minimum_order_bags': 20,
      'bag_type': 'jute_with_grainpro',
      'bag_weight_kg': 60.00,
      'indicative_price_usd_per_lb': '3.95',
      'indicative_price_usd_per_mt': '8708.00',
      'incoterm': 'FOB Djibouti',
      'port_of_loading': 'Djibouti',
      'sample_available': true,
      'sample_cost_usd': '0.00',
      'tasting_notes': ['Bergamot', 'Earl Grey Tea', 'Lemon Verbena', 'White Peach', 'Silky Honey'],
      'sensory_attributes': {
        'aroma': 9.0,
        'acidity': 9.1,
        'body': 8.4,
        'flavor': 8.9,
        'aftertaste': 8.8,
        'balance': 9.0,
      },
      'description': 'Classic textbook Yirgacheffe washed lot with pristine transparency. Depulped within 6 hours of picking, dry-fermented for 36 hours, spring-washed, and sun-dried on parabolic mesh beds for 14 days.',
      'status': 'active',
      'is_featured': true,
      'primary_image': {
        'url': 'https://images.unsplash.com/photo-1498804103079-a6351b050096?q=80&w=1200&auto=format&fit=crop',
      },
      'seller': {
        'id': 2,
        'name': 'Yirgacheffe Mountain Gate Exporters',
        'seller_profile': {
          'company_name': 'Yirgacheffe Mountain Gate Exporters PLC',
          'verification_status': 'verified',
          'export_license_no': 'ECTA-EXP-2023-7190',
        },
      },
    },
    {
      'id': 3,
      'slug': 'guji-hambela-wamena-natural-grade-1',
      'title': 'Guji Hambela Wamena Natural Grade 1',
      'origin_region': 'Guji',
      'sub_region': 'Hambela Wamena',
      'washing_station': 'Dimtu Drying Station',
      'coffee_type': 'arabica',
      'variety': '74110 Selection',
      'processing_method': 'natural',
      'grade': 'grade_1',
      'screen_size': 'Screen 15+',
      'cupping_score': 88.75,
      'moisture_content': 10.50,
      'water_activity': 0.57,
      'crop_year': '2025/2026',
      'altitude_min': 2100,
      'altitude_max': 2280,
      'total_quantity_bags': 200,
      'available_quantity_bags': 180,
      'minimum_order_bags': 15,
      'bag_type': 'jute_with_grainpro',
      'bag_weight_kg': 60.00,
      'indicative_price_usd_per_lb': '4.20',
      'indicative_price_usd_per_mt': '9259.00',
      'incoterm': 'FOB Djibouti',
      'port_of_loading': 'Djibouti',
      'sample_available': true,
      'sample_cost_usd': '0.00',
      'tasting_notes': ['Blueberry Jam', 'Black Currant', 'Dark Chocolate', 'Lavender', 'Syrupy Mouthfeel'],
      'sensory_attributes': {
        'aroma': 8.9,
        'acidity': 8.7,
        'body': 9.2,
        'flavor': 9.0,
        'aftertaste': 8.8,
        'balance': 8.9,
      },
      'description': 'Guji specialty at its best. Sourced exclusively from smallholder agroforestry garden plots in Hambela Wamena. Intense berry fruit sweetness with deep cocoa body.',
      'status': 'active',
      'is_featured': true,
      'primary_image': {
        'url': 'https://images.unsplash.com/photo-1511537190424-bbbab87ac5eb?q=80&w=1200&auto=format&fit=crop',
      },
      'seller': {
        'id': 3,
        'name': 'Oromia Guji Highlands Specialty Union',
        'seller_profile': {
          'company_name': 'Oromia Guji Highlands Coffee Union',
          'verification_status': 'verified',
          'export_license_no': 'ECTA-EXP-2024-9512',
        },
      },
    },
    {
      'id': 4,
      'slug': 'yirgacheffe-chelchele-honey-process-g1',
      'title': 'Yirgacheffe Chelchele Honey Process G1',
      'origin_region': 'Yirgacheffe',
      'sub_region': 'Chelchele, Gedeo',
      'washing_station': 'Chelchele Eco-Pulper',
      'coffee_type': 'arabica',
      'variety': 'Dega & Wolisho',
      'processing_method': 'honey',
      'grade': 'grade_1',
      'screen_size': 'Screen 15/16',
      'cupping_score': 88.25,
      'moisture_content': 10.30,
      'water_activity': 0.56,
      'crop_year': '2025/2026',
      'altitude_min': 1980,
      'altitude_max': 2120,
      'total_quantity_bags': 120,
      'available_quantity_bags': 95,
      'minimum_order_bags': 10,
      'bag_type': 'jute_with_grainpro',
      'bag_weight_kg': 60.00,
      'indicative_price_usd_per_lb': '4.10',
      'indicative_price_usd_per_mt': '9038.00',
      'incoterm': 'FOB Djibouti',
      'port_of_loading': 'Djibouti',
      'sample_available': true,
      'sample_cost_usd': '0.00',
      'tasting_notes': ['Candied Orange', 'Wild Floral Honey', 'Red Grape', 'Silky Sweet'],
      'sensory_attributes': {
        'aroma': 8.9,
        'acidity': 8.8,
        'body': 8.9,
        'flavor': 9.0,
        'aftertaste': 8.7,
        'balance': 8.9,
      },
      'description': 'Rare yellow honey preparation from Chelchele. Mucilage retained on parchment during slow shade-drying yields remarkable brown sugar sweetness and creamy body.',
      'status': 'active',
      'is_featured': false,
      'primary_image': {
        'url': 'https://images.unsplash.com/photo-1447933601403-0c6688de566e?q=80&w=1200&auto=format&fit=crop',
      },
      'seller': {
        'id': 2,
        'name': 'Yirgacheffe Mountain Gate Exporters',
        'seller_profile': {
          'company_name': 'Yirgacheffe Mountain Gate Exporters PLC',
          'verification_status': 'verified',
          'export_license_no': 'ECTA-EXP-2023-7190',
        },
      },
    },
    {
      'id': 5,
      'slug': 'guji-uraga-gomoro-extended-washed-g1',
      'title': 'Guji Uraga Gomoro Extended Washed G1',
      'origin_region': 'Guji',
      'sub_region': 'Uraga - Gomoro',
      'washing_station': 'Uraga Gomoro Wet Mill',
      'coffee_type': 'arabica',
      'variety': '74112 Indigenous',
      'processing_method': 'washed',
      'grade': 'grade_1',
      'screen_size': 'Screen 15+',
      'cupping_score': 89.25,
      'moisture_content': 10.10,
      'water_activity': 0.54,
      'crop_year': '2025/2026',
      'altitude_min': 2150,
      'altitude_max': 2300,
      'total_quantity_bags': 180,
      'available_quantity_bags': 150,
      'minimum_order_bags': 15,
      'bag_type': 'jute_with_grainpro',
      'bag_weight_kg': 60.00,
      'indicative_price_usd_per_lb': '4.45',
      'indicative_price_usd_per_mt': '9810.00',
      'incoterm': 'FOB Djibouti',
      'port_of_loading': 'Djibouti',
      'sample_available': true,
      'sample_cost_usd': '0.00',
      'tasting_notes': ['Nectarine', 'Lemongrass', 'Jasmine Perfume', 'Crisp Lime', 'Cane Sugar'],
      'sensory_attributes': {
        'aroma': 9.1,
        'acidity': 9.2,
        'body': 8.6,
        'flavor': 9.2,
        'aftertaste': 8.9,
        'balance': 9.0,
      },
      'description': 'Grown in the dizzying altitudes of Uraga Gomoro (up to 2,300m). Dense beans undergo 48 hours cold mountain water fermentation producing explosive citrus brightness.',
      'status': 'active',
      'is_featured': true,
      'primary_image': {
        'url': 'https://images.unsplash.com/photo-1518832553480-cd0e625ed3e6?q=80&w=1200&auto=format&fit=crop',
      },
      'seller': {
        'id': 3,
        'name': 'Oromia Guji Highlands Specialty Union',
        'seller_profile': {
          'company_name': 'Oromia Guji Highlands Coffee Union',
          'verification_status': 'verified',
          'export_license_no': 'ECTA-EXP-2024-9512',
        },
      },
    },
    {
      'id': 6,
      'slug': 'sidama-chire-carbonic-maceration-special-g1',
      'title': 'Sidama Chire Carbonic Maceration Special G1',
      'origin_region': 'Sidama',
      'sub_region': 'Chire Woreda',
      'washing_station': 'Hamasho Wet Mill',
      'coffee_type': 'arabica',
      'variety': '74158 Selection',
      'processing_method': 'anaerobic',
      'grade': 'grade_1',
      'screen_size': 'Screen 15/16',
      'cupping_score': 90.25,
      'moisture_content': 10.60,
      'water_activity': 0.59,
      'crop_year': '2025/2026',
      'altitude_min': 2100,
      'altitude_max': 2250,
      'total_quantity_bags': 80,
      'available_quantity_bags': 60,
      'minimum_order_bags': 5,
      'bag_type': 'jute_with_grainpro',
      'bag_weight_kg': 60.00,
      'indicative_price_usd_per_lb': '5.30',
      'indicative_price_usd_per_mt': '11684.00',
      'incoterm': 'FOB Djibouti',
      'port_of_loading': 'Djibouti',
      'sample_available': true,
      'sample_cost_usd': '0.00',
      'tasting_notes': ['Pomegranate', 'Passionfruit', 'Cocoa Nibs', 'Rose Wine', 'Sparkling Acidity'],
      'sensory_attributes': {
        'aroma': 9.3,
        'acidity': 9.2,
        'body': 9.0,
        'flavor': 9.4,
        'aftertaste': 9.1,
        'balance': 9.2,
      },
      'description': '90+ cupping score competition lot. Whole cherries sealed in stainless steel CO2 injected tanks for 120 hours. Sensational floral and tropical acidity.',
      'status': 'active',
      'is_featured': true,
      'primary_image': {
        'url': 'https://images.unsplash.com/photo-1509785307050-d4066910ec1e?q=80&w=1200&auto=format&fit=crop',
      },
      'seller': {
        'id': 1,
        'name': 'Bensa Highland Specialty Exporters',
        'seller_profile': {
          'company_name': 'Bensa Highland Specialty Coffee PLC',
          'verification_status': 'verified',
          'export_license_no': 'ECTA-EXP-2024-8842',
        },
      },
    },
    {
      'id': 7,
      'slug': 'limu-gomma-certified-organic-washed-g2',
      'title': 'Limu Gomma Certified Organic Washed G2',
      'origin_region': 'Limu',
      'sub_region': 'Gomma Woreda',
      'washing_station': 'Gomma Wet Mill',
      'coffee_type': 'arabica',
      'variety': 'Limu Heirloom',
      'processing_method': 'washed',
      'grade': 'grade_2',
      'screen_size': 'Screen 14+',
      'cupping_score': 85.50,
      'moisture_content': 10.80,
      'water_activity': 0.58,
      'crop_year': '2025/2026',
      'altitude_min': 1800,
      'altitude_max': 1950,
      'total_quantity_bags': 500,
      'available_quantity_bags': 450,
      'minimum_order_bags': 50,
      'bag_type': 'jute_with_grainpro',
      'bag_weight_kg': 60.00,
      'indicative_price_usd_per_lb': '3.15',
      'indicative_price_usd_per_mt': '6944.00',
      'incoterm': 'FOB Djibouti',
      'port_of_loading': 'Djibouti',
      'sample_available': true,
      'sample_cost_usd': '0.00',
      'tasting_notes': ['Sweet Meyer Lemon', 'Brown Sugar', 'Milk Chocolate', 'Clean Balance'],
      'sensory_attributes': {
        'aroma': 8.5,
        'acidity': 8.6,
        'body': 8.6,
        'flavor': 8.5,
        'aftertaste': 8.4,
        'balance': 8.7,
      },
      'description': 'Excellent specialty volume lot. Sweet, balanced, low defect count with sparkling clean citrus and chocolate undertones. Ideal for single origin and specialty espresso blends.',
      'status': 'active',
      'is_featured': false,
      'primary_image': {
        'url': 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?q=80&w=1200&auto=format&fit=crop',
      },
      'seller': {
        'id': 4,
        'name': 'Kaffa & Limu Biosphere Coffee PLC',
        'seller_profile': {
          'company_name': 'Kaffa & Limu Forest Biosphere PLC',
          'verification_status': 'verified',
          'export_license_no': 'ECTA-EXP-2022-6304',
        },
      },
    },
    {
      'id': 8,
      'slug': 'harrar-mesela-longberry-winey-moka-natural-g1',
      'title': 'Harrar Mesela Longberry Winey Moka Natural G1',
      'origin_region': 'Harrar',
      'sub_region': 'Mesela Highlands',
      'washing_station': 'Mesela Sun-Drying Compound',
      'coffee_type': 'arabica',
      'variety': 'Harrar Longberry',
      'processing_method': 'natural',
      'grade': 'grade_1',
      'screen_size': 'Screen 16/17',
      'cupping_score': 86.50,
      'moisture_content': 10.50,
      'water_activity': 0.57,
      'crop_year': '2025/2026',
      'altitude_min': 1850,
      'altitude_max': 2050,
      'total_quantity_bags': 250,
      'available_quantity_bags': 210,
      'minimum_order_bags': 25,
      'bag_type': 'jute_with_grainpro',
      'bag_weight_kg': 60.00,
      'indicative_price_usd_per_lb': '3.80',
      'indicative_price_usd_per_mt': '8377.00',
      'incoterm': 'FOB Djibouti',
      'port_of_loading': 'Djibouti',
      'sample_available': true,
      'sample_cost_usd': '0.00',
      'tasting_notes': ['Dried Blueberry', 'Winey Acidity', 'Cardamom', 'Dark Mocha', 'Heavy Body'],
      'sensory_attributes': {
        'aroma': 8.7,
        'acidity': 8.5,
        'body': 9.0,
        'flavor': 8.7,
        'aftertaste': 8.6,
        'balance': 8.6,
      },
      'description': 'Authentic Eastern Ethiopian sun-dried Longberry coffee. Sun-cured on raised mats for 25 days, developing iconic deep fruit wine notes and rich cardamom chocolate aromas.',
      'status': 'active',
      'is_featured': false,
      'primary_image': {
        'url': 'https://images.unsplash.com/photo-1507133750040-4a8f57021571?q=80&w=1200&auto=format&fit=crop',
      },
      'seller': {
        'id': 5,
        'name': 'Harrar Ancient Sun Coffee Traders',
        'seller_profile': {
          'company_name': 'Harrar Ancient Sun Estates & Millers',
          'verification_status': 'verified',
          'export_license_no': 'ECTA-EXP-2024-8119',
        },
      },
    },
    {
      'id': 9,
      'slug': 'kaffa-bonga-wild-forest-shade-grown-g1',
      'title': 'Kaffa Bonga Wild Forest Shade-Grown G1',
      'origin_region': 'Kaffa',
      'sub_region': 'Bonga UNESCO Biosphere',
      'washing_station': 'Bonga Wild Station',
      'coffee_type': 'arabica',
      'variety': 'Wild Forest Indigenous Landraces',
      'processing_method': 'washed',
      'grade': 'grade_1',
      'screen_size': 'Screen 15+',
      'cupping_score': 87.25,
      'moisture_content': 10.30,
      'water_activity': 0.55,
      'crop_year': '2025/2026',
      'altitude_min': 1800,
      'altitude_max': 2050,
      'total_quantity_bags': 160,
      'available_quantity_bags': 135,
      'minimum_order_bags': 10,
      'bag_type': 'jute_with_grainpro',
      'bag_weight_kg': 60.00,
      'indicative_price_usd_per_lb': '3.65',
      'indicative_price_usd_per_mt': '8046.00',
      'incoterm': 'FOB Djibouti',
      'port_of_loading': 'Djibouti',
      'sample_available': true,
      'sample_cost_usd': '0.00',
      'tasting_notes': ['Forest Honey', 'Dried Fig', 'Black Tea', 'Tangerine', 'Velvety'],
      'sensory_attributes': {
        'aroma': 8.8,
        'acidity': 8.7,
        'body': 8.8,
        'flavor': 8.8,
        'aftertaste': 8.6,
        'balance': 8.8,
      },
      'description': 'Wild Arabica harvested by local foraging communities deep inside the ancient Bonga rainforest. Incomparable cup complexity with herbal and fig sweetness.',
      'status': 'active',
      'is_featured': true,
      'primary_image': {
        'url': 'https://images.unsplash.com/photo-1442512595331-e89e73853f31?q=80&w=1200&auto=format&fit=crop',
      },
      'seller': {
        'id': 4,
        'name': 'Kaffa & Limu Biosphere Coffee PLC',
        'seller_profile': {
          'company_name': 'Kaffa & Limu Forest Biosphere PLC',
          'verification_status': 'verified',
          'export_license_no': 'ECTA-EXP-2022-6304',
        },
      },
    },
    {
      'id': 10,
      'slug': 'jimma-agaro-nano-challa-cooperative-lot-g2',
      'title': 'Jimma Agaro Nano Challa Cooperative Lot G2',
      'origin_region': 'Jimma',
      'sub_region': 'Agaro - Gomma',
      'washing_station': 'Agaro Cooperative Station',
      'coffee_type': 'arabica',
      'variety': 'Agaro Selections',
      'processing_method': 'washed',
      'grade': 'grade_2',
      'screen_size': 'Screen 14+',
      'cupping_score': 86.75,
      'moisture_content': 10.40,
      'water_activity': 0.56,
      'crop_year': '2025/2026',
      'altitude_min': 1900,
      'altitude_max': 2080,
      'total_quantity_bags': 350,
      'available_quantity_bags': 320,
      'minimum_order_bags': 20,
      'bag_type': 'jute_with_grainpro',
      'bag_weight_kg': 60.00,
      'indicative_price_usd_per_lb': '3.35',
      'indicative_price_usd_per_mt': '7385.00',
      'incoterm': 'FOB Djibouti',
      'port_of_loading': 'Djibouti',
      'sample_available': true,
      'sample_cost_usd': '0.00',
      'tasting_notes': ['Sweet Pear', 'Stone Fruit', 'Honeycomb', 'Black Tea', 'Clean Finish'],
      'sensory_attributes': {
        'aroma': 8.7,
        'acidity': 8.8,
        'body': 8.6,
        'flavor': 8.7,
        'aftertaste': 8.5,
        'balance': 8.8,
      },
      'description': 'From the renowned western Ethiopian high country of Agaro. Washed with modern Penagos eco-pulpers and soaked for 24 hours under pure spring water.',
      'status': 'active',
      'is_featured': false,
      'primary_image': {
        'url': 'https://images.unsplash.com/photo-1501339847302-ac426a4a7cbb?q=80&w=1200&auto=format&fit=crop',
      },
      'seller': {
        'id': 4,
        'name': 'Kaffa & Limu Biosphere Coffee PLC',
        'seller_profile': {
          'company_name': 'Kaffa & Limu Forest Biosphere PLC',
          'verification_status': 'verified',
          'export_license_no': 'ECTA-EXP-2022-6304',
        },
      },
    },
  ];

  static final List<Map<String, dynamic>> rfqs = [
    {
      'id': 1,
      'rfq_number': 'RFQ-2026-NORDIC01',
      'title': 'Seeking 300 Bags Sidama / Guji Natural G1 (SCA 88+)',
      'target_origin_region': 'Sidama or Guji',
      'target_grade': 'grade_1',
      'target_processing_method': 'natural',
      'target_cupping_score_min': 88.00,
      'volume_bags': 300,
      'bag_type_preference': 'jute_with_grainpro',
      'target_price_usd_per_lb': '4.25',
      'destination_country': 'Sweden',
      'destination_port': 'Port of Gothenburg',
      'preferred_incoterm': 'FOB',
      'preferred_payment_terms': 'Irrevocable LC at sight',
      'target_delivery_date': '2026-06-30',
      'closing_date': '2026-05-31',
      'specifications': 'Strict moisture cutoff at 11.0%. Crop 2025/2026. Pre-shipment PSS sample approval required. Please include tasting notes, washing station GPS/elevation, and valid ECTA exporter license.',
      'status': 'open',
      'buyer': {
        'name': 'Lukas Lindqvist (Nordic Specialty Roasters)',
        'country': 'Sweden',
      },
      'quotations_count': 2,
    },
    {
      'id': 2,
      'rfq_number': 'RFQ-2026-TOKYO02',
      'title': 'Tender: 150 Bags Yirgacheffe Washed G1 Floral / Bergamot (SCA 88.5+)',
      'target_origin_region': 'Yirgacheffe',
      'target_grade': 'grade_1',
      'target_processing_method': 'washed',
      'target_cupping_score_min': 88.50,
      'volume_bags': 150,
      'bag_type_preference': 'jute_with_grainpro',
      'target_price_usd_per_lb': '4.05',
      'destination_country': 'Japan',
      'destination_port': 'Port of Yokohama',
      'preferred_incoterm': 'FOB',
      'preferred_payment_terms': 'CAD or LC at sight',
      'target_delivery_date': '2026-06-15',
      'closing_date': '2026-05-25',
      'specifications': 'Looking for classic translucent Kochere or Chelchele cup. Must have intense jasmine and bergamot aroma. Vacuum pack or GrainPro mandatory. Screen 15+ preferred.',
      'status': 'open',
      'buyer': {
        'name': 'Kenji Takahashi (Tokyo Craft Roasters Collective)',
        'country': 'Japan',
      },
      'quotations_count': 1,
    },
    {
      'id': 3,
      'rfq_number': 'RFQ-2026-MILAN03',
      'title': 'Volume Order: 500 Bags Limu / Jimma Washed G2 for Espresso Base',
      'target_origin_region': 'Limu or Jimma',
      'target_grade': 'grade_2',
      'target_processing_method': 'washed',
      'target_cupping_score_min': 85.00,
      'volume_bags': 500,
      'bag_type_preference': 'jute_with_grainpro',
      'target_price_usd_per_lb': '3.10',
      'destination_country': 'Italy',
      'destination_port': 'Port of Genoa',
      'preferred_incoterm': 'FOB',
      'preferred_payment_terms': '30% Advance, 70% against BL copies',
      'target_delivery_date': '2026-07-31',
      'closing_date': '2026-06-15',
      'specifications': 'Commercial specialty grade washed lot. Minimum moisture 10.5%, max 11.5%. Clean cup, free from ferment or phenolic defects. Continuous supply contract possible.',
      'status': 'open',
      'buyer': {
        'name': 'Mateo Rossi (Milano Roasting Lab)',
        'country': 'Italy',
      },
      'quotations_count': 0,
    },
    {
      'id': 4,
      'rfq_number': 'RFQ-2026-SEATTLE04',
      'title': 'Micro-Lot Request: 200 Bags Guji / Sidama Anaerobic (SCA 89+)',
      'target_origin_region': 'Guji or Sidama',
      'target_grade': 'grade_1',
      'target_processing_method': 'anaerobic',
      'target_cupping_score_min': 89.00,
      'volume_bags': 200,
      'bag_type_preference': 'jute_with_grainpro',
      'target_price_usd_per_lb': '4.90',
      'destination_country': 'United States',
      'destination_port': 'Port of Oakland',
      'preferred_incoterm': 'FOB',
      'preferred_payment_terms': 'Direct wire transfer upon PSS sample approval',
      'target_delivery_date': '2026-06-30',
      'closing_date': '2026-05-28',
      'specifications': 'Seeking tropical, winey, and strawberry notes for winter specialty lineup. Prefer anaerobic natural or carbonic maceration. Air mail sample required promptly.',
      'status': 'open',
      'buyer': {
        'name': 'David Miller (Pacific Green Coffee Co.)',
        'country': 'United States',
      },
      'quotations_count': 0,
    },
  ];

  static final Map<String, dynamic> filters = {
    'origins': [
      'All',
      'Sidama',
      'Yirgacheffe',
      'Guji',
      'Limu',
      'Harrar',
      'Jimma',
      'Kaffa',
      'Kembata',
      'Illubabor',
    ],
    'processing_methods': [
      'All',
      'Washed',
      'Natural',
      'Anaerobic',
      'Honey',
    ],
    'grades': [
      'All',
      'Grade 1 (Specialty)',
      'Grade 2 (Premium)',
      'Grade 3',
      'Grade 4',
    ],
  };

  static final Set<int> savedListingIds = {};

  static bool toggleSave(int id) {
    if (savedListingIds.contains(id)) {
      savedListingIds.remove(id);
      return false;
    } else {
      savedListingIds.add(id);
      return true;
    }
  }

  static bool isSaved(int id) => savedListingIds.contains(id);

  static List<Map<String, dynamic>> getSavedLots() {
    final list = listings.where((l) => savedListingIds.contains(l['id'])).toList();
    if (list.isEmpty && listings.isNotEmpty) {
      return [listings[0], listings[1]];
    }
    return list;
  }

  static List<Map<String, dynamic>> filterListings({
    String? search,
    String? origin,
    String? processing,
    String? grade,
    double? minScore,
    String? sort,
  }) {
    var result = List<Map<String, dynamic>>.from(listings);

    if (search != null && search.trim().isNotEmpty) {
      final q = search.trim().toLowerCase();
      result = result.where((item) {
        final title = (item['title'] ?? '').toString().toLowerCase();
        final originRegion = (item['origin_region'] ?? '').toString().toLowerCase();
        final subRegion = (item['sub_region'] ?? '').toString().toLowerCase();
        final station = (item['washing_station'] ?? '').toString().toLowerCase();
        final variety = (item['variety'] ?? '').toString().toLowerCase();
        final desc = (item['description'] ?? '').toString().toLowerCase();
        final notes = (item['tasting_notes'] is List)
            ? (item['tasting_notes'] as List).join(' ').toLowerCase()
            : '';
        final sellerName = (item['seller']?['name'] ?? '').toString().toLowerCase();
        final companyName = (item['seller']?['seller_profile']?['company_name'] ?? '').toString().toLowerCase();

        return title.contains(q) ||
            originRegion.contains(q) ||
            subRegion.contains(q) ||
            station.contains(q) ||
            variety.contains(q) ||
            desc.contains(q) ||
            notes.contains(q) ||
            sellerName.contains(q) ||
            companyName.contains(q);
      }).toList();
    }

    if (origin != null && origin != 'all' && origin != 'All' && origin.isNotEmpty) {
      final q = origin.toLowerCase();
      result = result.where((item) {
        final o = (item['origin_region'] ?? '').toString().toLowerCase();
        return o.contains(q) || q.contains(o);
      }).toList();
    }

    if (processing != null && processing != 'all' && processing != 'All' && processing.isNotEmpty) {
      final q = processing.toLowerCase().replaceAll(' ', '_');
      result = result.where((item) {
        final p = (item['processing_method'] ?? '').toString().toLowerCase().replaceAll(' ', '_');
        return p.contains(q) || q.contains(p);
      }).toList();
    }

    if (grade != null && grade != 'all' && grade != 'All' && grade.isNotEmpty) {
      final q = grade.toLowerCase().replaceAll(' ', '_');
      result = result.where((item) {
        final g = (item['grade'] ?? '').toString().toLowerCase().replaceAll(' ', '_');
        return g.contains(q) || q.contains(g);
      }).toList();
    }

    if (minScore != null && minScore > 0) {
      result = result.where((item) {
        final score = (item['cupping_score'] is num) ? (item['cupping_score'] as num).toDouble() : 0.0;
        return score >= minScore;
      }).toList();
    }

    if (sort != null) {
      if (sort == 'score_desc' || sort == 'cupping_score') {
        result.sort((a, b) => ((b['cupping_score'] as num?) ?? 0).compareTo((a['cupping_score'] as num?) ?? 0));
      } else if (sort == 'price_asc') {
        result.sort((a, b) => double.parse(a['indicative_price_usd_per_lb'] ?? '0').compareTo(double.parse(b['indicative_price_usd_per_lb'] ?? '0')));
      } else if (sort == 'price_desc') {
        result.sort((a, b) => double.parse(b['indicative_price_usd_per_lb'] ?? '0').compareTo(double.parse(a['indicative_price_usd_per_lb'] ?? '0')));
      }
    }

    return result;
  }

  static Map<String, dynamic>? findListing(dynamic idOrSlug) {
    final query = idOrSlug.toString().toLowerCase();
    for (final lot in listings) {
      if (lot['id'].toString() == query || lot['slug'].toString().toLowerCase() == query) {
        return lot;
      }
    }
    return listings.isNotEmpty ? listings.first : null;
  }

  static List<Map<String, dynamic>> filterRfqs({
    String? origin,
    String? destinationCountry,
  }) {
    var result = List<Map<String, dynamic>>.from(rfqs);

    if (origin != null && origin != 'all' && origin != 'All' && origin.isNotEmpty) {
      final q = origin.toLowerCase();
      result = result.where((item) {
        final o = (item['target_origin_region'] ?? '').toString().toLowerCase();
        return o.contains(q) || q.contains(o);
      }).toList();
    }

    if (destinationCountry != null && destinationCountry.isNotEmpty) {
      final q = destinationCountry.toLowerCase();
      result = result.where((item) {
        final c = (item['destination_country'] ?? '').toString().toLowerCase();
        return c.contains(q) || q.contains(c);
      }).toList();
    }

    return result;
  }

  static Map<String, dynamic>? findRfq(dynamic id) {
    final query = id.toString();
    for (final rfq in rfqs) {
      if (rfq['id'].toString() == query) {
        return rfq;
      }
    }
    return rfqs.isNotEmpty ? rfqs.first : null;
  }

  static Map<String, dynamic> addListing(Map<String, dynamic> raw) {
    final newId = listings.length + 1;
    final slug = (raw['title'] ?? 'ethiopian-lot-$newId')
        .toString()
        .toLowerCase()
        .replaceAll(RegExp(r'[^a-z0-9]+'), '-')
        .replaceAll(RegExp(r'^-|-$'), '');

    final newLot = {
      'id': newId,
      'slug': slug,
      'title': raw['title'] ?? 'Ethiopian Specialty Green Coffee Lot',
      'origin_region': raw['origin_region'] ?? 'Sidama',
      'sub_region': raw['sub_region'] ?? 'Bensa',
      'washing_station': raw['washing_station'] ?? 'Local Station',
      'coffee_type': raw['coffee_type'] ?? 'arabica',
      'variety': raw['variety'] ?? '74110 & 74112 Heirloom',
      'processing_method': raw['processing_method'] ?? 'washed',
      'grade': raw['grade'] ?? 'grade_1',
      'screen_size': 'Screen 15+',
      'cupping_score': raw['cupping_score'] ?? 88.0,
      'moisture_content': 10.5,
      'water_activity': 0.56,
      'crop_year': '2025/2026',
      'altitude_min': 1950,
      'altitude_max': 2150,
      'total_quantity_bags': raw['total_quantity_bags'] ?? 150,
      'available_quantity_bags': raw['total_quantity_bags'] ?? 150,
      'minimum_order_bags': 10,
      'bag_type': 'jute_with_grainpro',
      'bag_weight_kg': 60.00,
      'indicative_price_usd_per_lb': raw['indicative_price_usd_per_lb']?.toString() ?? '4.20',
      'indicative_price_usd_per_mt': '9259.00',
      'incoterm': 'FOB Djibouti',
      'port_of_loading': 'Djibouti',
      'sample_available': true,
      'sample_cost_usd': '0.00',
      'tasting_notes': (raw['tasting_notes'] is List)
          ? raw['tasting_notes']
          : ['Jasmine', 'Peach', 'Citrus', 'Honey'],
      'sensory_attributes': {
        'aroma': 9.0,
        'acidity': 8.8,
        'body': 8.6,
        'flavor': 8.9,
        'aftertaste': 8.7,
        'balance': 8.8,
      },
      'description': raw['description'] ?? 'Export-grade specialty lot published via Fira Link mobile platform.',
      'status': 'active',
      'is_featured': true,
      'primary_image': {
        'url': 'https://images.unsplash.com/photo-1514432324607-a09d9b4aefdd?q=80&w=1200&auto=format&fit=crop',
      },
      'seller': {
        'id': 1,
        'name': 'Bensa Highland Specialty Exporters',
        'seller_profile': {
          'company_name': 'Bensa Highland Specialty Coffee PLC',
          'verification_status': 'verified',
          'export_license_no': 'ECTA-EXP-2024-8842',
        },
      },
    };

    listings.insert(0, newLot);
    return newLot;
  }

  static Map<String, dynamic> addRfq(Map<String, dynamic> raw) {
    final newId = rfqs.length + 1;
    final newRfq = {
      'id': newId,
      'rfq_number': 'RFQ-2026-USER$newId',
      'title': raw['title'] ?? 'International Green Coffee Tender',
      'target_origin_region': raw['target_origin_region'] ?? 'Sidama or Yirgacheffe',
      'target_grade': raw['target_grade'] ?? 'grade_1',
      'target_processing_method': raw['target_processing_method'] ?? 'washed',
      'target_cupping_score_min': raw['target_cupping_score_min'] ?? 87.0,
      'volume_bags': raw['volume_bags'] ?? 100,
      'bag_type_preference': 'jute_with_grainpro',
      'target_price_usd_per_lb': raw['target_price_usd_per_lb']?.toString() ?? '4.20',
      'destination_country': raw['destination_country'] ?? 'Germany',
      'destination_port': raw['destination_port'] ?? 'Port of Hamburg',
      'preferred_incoterm': 'FOB',
      'preferred_payment_terms': 'CAD or LC at sight',
      'target_delivery_date': '2026-07-31',
      'closing_date': '2026-06-30',
      'specifications': raw['specifications'] ?? 'High quality specialty lot required.',
      'status': 'open',
      'buyer': {
        'name': 'International Specialty Coffee Importer',
        'country': raw['destination_country'] ?? 'Germany',
      },
      'quotations_count': 0,
    };

    rfqs.insert(0, newRfq);
    return newRfq;
  }
}
