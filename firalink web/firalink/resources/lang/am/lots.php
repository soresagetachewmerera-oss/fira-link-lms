<?php

return [
    'levels' => [
        'claimed' => 'በሻጩ የተገለጸ',
        'documented' => 'በሰነድ የተደገፈ',
        'lab_backed' => 'በላብራቶሪ የተደገፈ',
        'inspected' => 'የተመረመረ',
    ],

    'kinds' => [
        'photo_bags' => 'የምርት ኮድ ያለባቸው ከረጢቶች',
        'photo_beans' => 'የጥሬ ቡና ፍሬ በቅርበት',
        'photo_warehouse' => 'የመጋዘን እይታ',
        'lab_report' => 'የላብራቶሪ ወይም የቅምሻ ሪፖርት',
    ],

    'checks' => [
        'lot_code_visible' => 'የምርት ኮዱ በከረጢቶቹ ላይ ይታያል',
        'lab_contacted' => 'ሪፖርቱን ከሰጠው ላብራቶሪ ጋር አረጋግጫለሁ',
    ],

    'reasons' => [
        'blurry_photo' => 'ፎቶው ደብዛዛ ነው። እንደገና ያንሱት።',
        'lot_code_not_visible' => 'የምርት ኮዱ በከረጢቶቹ ላይ አይታይም።',
        'wrong_subject' => 'ፎቶው የተጠየቀውን አያሳይም።',
        'not_taken_in_app' => 'ፎቶውን በመተግበሪያው ውስጥ ያንሱ።',
        'lab_not_confirmed' => 'ላብራቶሪው ይህን ሪፖርት ማረጋገጥ አልቻለም።',
        'number_unreadable' => 'የሪፖርቱ ቁጥር አይነበብም።',
    ],

    'statuses' => [
        'draft' => 'ረቂቅ',
        'active' => 'የታተመ',
        'paused' => 'የቆመ',
        'archived' => 'በፊራ ሊንክ የተወገደ',
        'sold_out' => 'ተሽጦ አልቋል',
    ],

    'errors' => [
        'kind_unknown' => 'ይህ የማስረጃ አይነት የምርቱ አካል አይደለም።',
        'not_in_app' => 'ይህን ፎቶ በፊራ ሊንክ መተግበሪያ ውስጥ ያንሱ። ከማዕከለ-ስዕላት የተመረጡ ፎቶዎች ተቀባይነት የላቸውም።',
        'location_invalid' => 'የፎቶው አካባቢ ትክክል አይደለም።',
        'lab_name' => 'የላብራቶሪውን ስም ያስገቡ።',
        'image_processing' => 'ፎቶዎችን አሁን ማስኬድ አይቻልም። ቆይተው እንደገና ይሞክሩ።',
        'not_allowed' => 'የዚህን ንግድ ምርቶች መቀየር የሚችሉት ባለቤቱ ወይም ሥራ አስኪያጁ ብቻ ናቸው።',
        'seller_role' => 'ምርቶችን ማከል የሚችሉት ላኪና አቅራቢ ንግዶች ብቻ ናቸው።',
        'tier_required' => 'የደረጃ 1 ማረጋገጫ እስኪጸድቅ ድረስ ምርቶችዎ ለገዢዎች አይታዩም።',
        'business_required' => 'መጀመሪያ የንግድ የሥራ ቦታ ይፍጠሩ ወይም ወደ እሱ ይቀይሩ።',
        'not_editable' => 'ይህ ምርት በፊራ ሊንክ ተወግዷል፣ መቀየር አይቻልም።',
        'admin_activate_blocked' => 'ይህን ምርት ንቁ ማድረግ አይቻልም፤ ንግዱ የተረጋገጠ ሻጭ አይደለም ወይም ምርቱ አስፈላጊ መረጃዎች ይጎድሉታል።',
        'total_below_taken' => 'ከዚህ ምርት :bags ከረጢቶች ለተቀበሉ የዋጋ ማቅረቢያዎች ተይዘዋል ወይም ተሽጠዋል። አጠቃላዩ ከዚህ ማነስ አይችልም።',
        'lot_reserved' => 'የዚህ ምርት ከረጢቶች ለተቀበለ የዋጋ ማቅረቢያ ተይዘዋል። ስምምነቱ እስኪፈረም ወይም እስኪሰረዝ ይጠብቁ።',
        'export_licence_required' => 'Fira Link የECTA ላኪ የብቃት ማረጋገጫዎን እስኪያጸድቅ ድረስ ምርቶችዎ ለገዢዎች አይታዩም።',
    ],

    'notifications' => [
        'approved_title' => 'የምርት ማስረጃ ጸድቋል',
        'approved' => 'የ:lot :evidence ጸድቋል።',
        'needs_changes_title' => 'የምርት ማስረጃ ለውጥ ያስፈልገዋል',
        'needs_changes' => 'የ:lot :evidence ለውጥ ያስፈልገዋል፦ :reason',
        'rejected_title' => 'የምርት ማስረጃ ውድቅ ተደርጓል',
        'rejected' => 'የ:lot :evidence ውድቅ ተደርጓል፦ :reason',
        'expired_title' => 'የምርት ማስረጃ ጊዜው አልፏል',
        'expired' => 'የ:lot :evidence ጊዜው አልፏል። ደረጃውን ለማቆየት አዲስ ፎቶዎችን ያንሱ።',
        'expiring_title' => 'የምርት ማስረጃን ያድሱ',
        'expiring' => 'የ:lot :evidence በ:days ቀናት ውስጥ ጊዜው ያልፋል። ደረጃውን ለማቆየት አዲስ ፎቶዎችን ያንሱ።',
    ],

    'admin' => [
        'tab_business' => 'የንግድ ሰነዶች',
        'tab_lots' => 'የምርት ማስረጃ',
        'lot' => 'ምርት',
        'lot_code' => 'የምርት ኮድ',
        'evidence' => 'ማስረጃ',
        'level' => 'ደረጃ',
        'captured' => 'በመተግበሪያው የተነሳ',
        'location' => 'አካባቢ',
        'no_location' => 'አካባቢ አልተላከም',
        'open_lot' => 'የምርቱ ገጽ',
        'queue_empty' => 'ለግምገማ የሚጠብቅ የምርት ማስረጃ የለም።',
        'lab_name' => 'ላብራቶሪ',
        'report_number' => 'የሪፖርት ቁጥር',
    ],
];
