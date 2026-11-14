<?php

return [
    'errors' => [
        'business_required' => 'መጀመሪያ የንግድ የሥራ ቦታ ይፍጠሩ ወይም ወደ እሱ ይቀይሩ።',
        'not_allowed' => 'ይህን ማድረግ የሚችሉት የዚህ ንግድ ባለቤት ወይም ሥራ አስኪያጅ ብቻ ናቸው።',
        'buyer_role' => 'ጥያቄ መለጠፍ የሚችሉት ገዢና ደላላ ንግዶች ብቻ ናቸው።',
        'tier_required' => 'መጀመሪያ የደረጃ 1 ማረጋገጫ ያስፈልጋል። የሚከፈልባቸው ዕቅዶች ማረጋገጫን አይገዙም።',
        'sellers_only' => 'ጥያቄዎችን ማየትና መመለስ የሚችሉት የተረጋገጡ ሻጭ ንግዶች ብቻ ናቸው።',
        'own_request' => 'ለራስዎ ጥያቄ ዋጋ መስጠት አይችሉም።',
        'request_closed' => 'ይህ ጥያቄ ተዘግቷል።',
        'lot_required' => 'ከታተሙ ምርቶችዎ አንዱን ያያይዙ።',
        'volume_too_high' => 'ይህ ምርት :bags ከረጢቶች ብቻ አሉት።',
        'contact_details' => 'የስልክ ቁጥሮችን፣ ኢሜሎችን፣ ሊንኮችንና የመልእክት መተግበሪያ ስሞችን ያስወግዱ። ስምምነቱን በፊራ ሊንክ ውስጥ ያቆዩ።',
        'quote_limit' => 'የዕቅድዎ ወርሃዊ የዋጋ ማቅረቢያ ገደብ ደርሷል።',
    ],

    'blockers' => [
        'REQUEST_CLOSED' => 'ይህ ጥያቄ ተዘግቷል።',
        'QUOTE_NOT_OPEN' => 'ይህ የዋጋ ማቅረቢያ ከአሁን በኋላ ክፍት አይደለም።',
        'LOT_REQUIRED' => 'ይህ የዋጋ ማቅረቢያ የተያያዘ ምርት የለውም፣ ስለዚህ መቀበል አይቻልም።',
        'SELLER_NOT_VERIFIED' => 'ሻጩ በአሁኑ ጊዜ የተረጋገጠ አይደለም፣ ስለዚህ ይህን የዋጋ ማቅረቢያ መቀበል አይቻልም።',
        'BUYER_NOT_VERIFIED' => 'የዋጋ ማቅረቢያዎችን ለመቀበል ንግድዎ የሚሰራ የደረጃ 1 ማረጋገጫ ያስፈልገዋል።',
        'BELOW_MINIMUM' => 'ይህ ዋጋ ከECTA ዝቅተኛ የወጪ ንግድ ዋጋ በታች ነው፣ ስለዚህ መቀበል አይቻልም።',
    ],

    'notifications' => [
        'quote_received_title' => 'አዲስ የዋጋ ማቅረቢያ',
        'quote_received' => ':seller ለ«:rfq» የዋጋ ማቅረቢያ ልኳል።',
        'quote_accepted_title' => 'የዋጋ ማቅረቢያ ተቀባይነት አግኝቷል',
        'quote_accepted' => 'ለ«:rfq» ያቀረቡት ዋጋ ተቀባይነት አግኝቷል። ለመቀጠል የስምምነት ክፍሉን ይክፈቱ።',
        'quote_not_selected_title' => 'የዋጋ ማቅረቢያ አልተመረጠም',
        'quote_not_selected' => 'ገዢው ለ«:rfq» ሌላ የዋጋ ማቅረቢያ መርጧል።',
        'quote_declined_title' => 'የዋጋ ማቅረቢያ ውድቅ ተደርጓል',
        'quote_declined' => 'ገዢው ለ«:rfq» ያቀረቡትን ዋጋ ውድቅ አድርጓል።',
    ],
];
