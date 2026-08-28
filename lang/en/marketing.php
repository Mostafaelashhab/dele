<?php

return [
    'badge' => 'Live tracking on every delivery',

    // The headline is split so the middle phrase can carry the accent colour —
    // the page has colour from its first line, before any visual loads.
    'headline' => [
        'one' => 'One system',
        'accent' => 'for all your',
        'two' => 'deliveries.',
    ],

    'hero_body' => 'Connect your shop to every delivery company in Banha from one place. Create the order, let the network pick the best available company, and follow it to the customer’s door.',

    'stat_one_value' => 'Minutes',
    'stat_one_label' => 'from order to a company accepting',
    'stat_two_value' => 'Companies',
    'stat_two_label' => 'competing for it, not one phone call',
    'stat_three_value' => 'No app',
    'stat_three_label' => 'your customer just opens a link',

    // Labels for the cards floating over the hero visual.
    'float' => [
        'location' => 'Live location',
        'location_value' => 'Farid Nada St.',
        'eta' => 'Arriving in',
        'eta_value' => '12 minutes',
        'accepted' => 'Accepted by',
        'accepted_value' => 'Banha Express',
    ],

    'cta_business' => 'Register your business',
    'cta_login' => 'Sign in',
    'cta_track' => 'Track a delivery',

    'how' => [
        'eyebrow' => 'The process',
        'title' => 'How an order moves',
        'subtitle' => 'From the second you write it to the moment it reaches the door',
        'steps' => [
            ['title' => 'You write the order', 'body' => 'Pickup, customer, and the price shown before you confirm.'],
            ['title' => 'The network searches', 'body' => 'It offers the job to the best available companies at once.'],
            ['title' => 'A company accepts', 'body' => 'Assigns a rider, and you get the price and expected time.'],
            ['title' => 'The customer follows it', 'body' => 'Through a link sent by SMS — no app, no sign-up.'],
        ],
    ],

    'features' => [
        'items' => [
            [
                'title' => 'An open market for your orders',
                'body' => 'Your order is offered to several companies at the same moment, and the first to accept takes it. You are not waiting on anyone to call back, and not tied to one company when it is late or closed.',
            ],
            [
                'title' => 'Pricing you can read',
                'body' => 'Every price comes from fixed rules and is itemised line by line, so any figure still makes sense months later.',
            ],
            [
                'title' => 'Live tracking',
                'body' => 'The rider’s position updates as they ride, and your customer sees it only while it is on its way.',
            ],
            [
                'title' => 'Books that close themselves',
                'body' => 'Every pound has a double-entry record: what you owe, the platform’s fee, and what the company and rider are due. Settlements are generated from those entries rather than reconciled by hand at month end.',
            ],
            [
                'title' => 'A programmable interface',
                'body' => 'Connect your store or point of sale and send orders automatically with an API key.',
            ],
        ],
    ],

    'pricing' => [
        'popular' => 'Most used',
        'per_delivery' => '/ per delivery',
        'note' => 'These are indicative for a small parcel within Banha. The final price is worked out from distance, zone and time of day, and is shown in full before you confirm the order.',
    ],

    'zones' => [
        'eyebrow' => 'Coverage',
        'title' => 'Where we deliver',
        'subtitle' => 'These are the starting prices for a standard order inside each zone — they rise with distance and priority.',
        'diagram_hint' => 'The closer a zone sits to the centre, the less it costs. These are starting prices for a standard order, rising with distance and priority.',
        'tier_count' => '{1} 1 zone|[2,*] :count zones',
        'minutes_short' => 'm',
        'total' => 'covered zones',
        'outside' => 'Your area not listed? Register and tell us — we add zones as demand appears.',
        'explore_hint' => 'Point at any zone below to see its catchment on the map — and the other way round.',
        'explore_hint_touch' => 'Tap any zone below to see its catchment on the map above — and the other way round.',
        'from' => 'From',
    ],

    // Aimed squarely at delivery companies, and deliberately hard-edged.
    //
    // Every line here is a description of how the dispatcher actually behaves:
    // an order really is offered to several companies at once, ranking really
    // is computed from these weights, and a company that is not on the network
    // really is never offered the order. The pressure is real, which is why it
    // does not need inventing — no fabricated counts, no fake deadlines, no
    // invented scarcity.
    /*
     * The two doors.
     *
     * A shop and a courier fleet are buying opposite things, so the pitch is
     * split rather than averaged. As everywhere else on this page: no invented
     * counts, no fake deadlines — the pressure described here is the pressure
     * the product actually applies.
     */
    /*
     * Protection: proof of delivery.
     *
     * This describes two mechanisms that exist in the product — a code issued
     * with every order, and photographs uploaded by the rider — and a
     * delivery does not close without one of them. Nothing promised here is
     * unimplemented.
     */
    /*
     * The problem, as it stands today.
     *
     * Every line here describes a situation that actually happens rather than
     * an invented statistic. The page used to present a solution to a problem
     * it never named, so the reader never got the "that's my day" moment.
     */
    'problem' => [
        'eyebrow' => 'Today',
        'title' => 'Delivery runs on phone calls. That costs you.',
        'body' => 'No persuasion needed — this is your day.',

        'before_title' => 'Without the network',
        'before' => [
            'You call a company, nobody answers, you call the next one',
            'The price is negotiated from scratch every time',
            'Your customer calls asking where the order is and you cannot say',
            'It arrived or it did not — the rider\'s word is the evidence',
            'Month end is reconciled from paper and phone calls',
        ],

        'after_title' => 'With the network',
        'after' => [
            'One order reaches every company at the same moment',
            'The price is known and itemised before you send',
            'Your customer follows a link and stops calling',
            'The order does not close without a code or a photo',
            'Every pound has a ledger entry, and settlements generate themselves',
        ],
    ],

    /*
     * The shop's case.
     */
    'forshop' => [
        'eyebrow' => 'If you run a shop',
        'title' => 'Write the order once. The network handles the rest.',
        'body' => 'No calls, no haggling, and no dependence on one company when it is late, full, or closed.',
        'screen_caption' => 'The order screen — the price is calculated as you type.',
    ],

    /*
     * The end customer.
     *
     * Their experience is the strongest thing a shop is buying, and it was
     * scattered across the page with no section holding it together.
     */
    'customer' => [
        'eyebrow' => 'And your customer',
        'title' => 'Your customer follows it without installing anything.',
        'body' => 'They get a link. It opens on any phone and shows where the order is, who is carrying it and when it arrives — and it gives them the code they read to the rider at the door.',
        'screen_caption' => 'The tracking page exactly as your customer sees it.',

        'points' => [
            [
                'title' => 'A link, not an app',
                'body' => 'Nothing to download, nothing to sign up for. The link opens straight away in any browser.',
            ],
            [
                'title' => 'Where the rider is',
                'body' => 'Their position updates as they ride — and only while they are carrying your customer\'s parcel, never before or after.',
            ],
            [
                'title' => 'The handover code',
                'body' => 'A code appears for your customer in transit. They read it to the rider, who types it in to close the order.',
            ],
            [
                'title' => 'Expected arrival',
                'body' => 'An arrival time computed from distance and movement, updating on its own.',
            ],
        ],
    ],

    /*
     * Cost — one section answering one question.
     *
     * The fee and the tiers used to be two sections four apart, and they are
     * two halves of the same question: what will this cost me?
     */
    /*
     * Cost, during the pilot.
     *
     * The network is free right now: no commission per order, no
     * subscription, and the platform never touches money between a shop and a
     * delivery company. This says exactly that — nobody should find a figure
     * on the page that the product does not charge.
     */
    'free' => [
        'eyebrow' => 'Cost',
        'title' => 'The network is free right now.',
        'body' => 'This is the first operating phase in Banha, and it is free to use — for shops and for delivery companies alike. No commission on an order, no monthly subscription.',

        'points' => [
            [
                'title' => 'No commission on an order',
                'body' => 'The delivery price goes to the delivery company in full. The platform takes none of it.',
            ],
            [
                'title' => 'No monthly subscription',
                'body' => 'No fixed fee, no order minimum, no fixed-term contract.',
            ],
            [
                'title' => 'The platform is not between your money and theirs',
                'body' => 'You settle with the delivery company directly, exactly as you do now. The platform routes the order, tracks it and records the handover — it does not hold money.',
            ],
            [
                'title' => 'If that changes, you will know first',
                'body' => 'Any future charge will be announced well before it applies. Nothing will be deducted by surprise.',
            ],
        ],

        'tiers_lede' => 'The same delivery — you pick the speed on each order, not a plan you subscribe to.',
        'tiers_goes_to' => 'This amount goes to the delivery company in full.',
        'tier_default' => 'Default',
        'tier_cheaper' => ':amount cheaper',
        'tier_dearer' => ':amount more',
        'tier_same' => 'The baseline',
        'tiers_title' => 'The delivery price',
        'tiers_body' => 'This is what you pay the delivery company, and it changes with how urgently you need the order moved.',
    ],

    'cost' => [
        'eyebrow' => 'Cost',
        'title' => 'What will you pay? All of it is here.',
        'body' => 'Two things only: the delivery price, set by rules, and the platform share, taken when the order closes. That is the whole of it.',
        'tiers_title' => 'The delivery price',
        'tiers_body' => 'It changes with how urgently you need the order moved.',
        'platform_title' => 'The platform fee',
    ],

    /*
     * Questions.
     *
     * The objections people actually raise, answered by describing what the
     * system does rather than by promising.
     */
    'faq' => [
        'eyebrow' => 'Questions',
        'title' => 'What gets asked before signing up.',
        'items' => [
            [
                'q' => 'Why not just call a delivery company directly?',
                'a' => 'You can. The difference is that here the order is offered to every subscribed company at the same moment, so you take the fastest to answer instead of working down a list. And you are not tied to one company when it is late, closed, or has raised its price.',
            ],
            [
                'q' => 'Who decides which company gets my order?',
                'a' => 'The dispatcher does, on computed and published criteria: how near the closest rider is, how many riders are free, the price, the expected time, the company\'s completion rate, and how fast it answers. The weights are shown on this page.',
            ],
            [
                'q' => 'What if a parcel is lost, or the customer says it never arrived?',
                'a' => 'An order does not close without proof — a code your customer reads to the rider, or a photo of the parcel at handover. Both are recorded against the order and you can open them from the order page at any time. Every step is timestamped with who performed it.',
            ],
            [
                'q' => 'Do I need to install an app?',
                'a' => 'No. You work from the browser, and your customer gets a link that opens on any phone. Only riders have a dedicated screen, and that runs in the browser too.',
            ],
            [
                'q' => 'Is there a monthly subscription?',
                'a' => 'No. The network is free during this first operating phase — no subscription and no commission on an order. The delivery price goes to the delivery company in full, and the platform holds no money between you and them. Any future charge will be announced well in advance.',
            ],
            [
                'q' => 'Do you cover areas outside Banha?',
                'a' => 'The network runs in Banha today, and the covered zones are listed with their prices on this page. Any address beyond them is not served yet.',
            ],
        ],
    ],

    'protection' => [
        'eyebrow' => 'Order protection',
        'title' => 'A delivery does not close without proof.',
        'subtitle' => 'Not a policy. A rider cannot mark an order delivered without one of these.',

        'code_title' => 'A code only your customer sees',
        'code_body' => 'Every order gets its own code. Your customer sees it on their tracking page while the rider is carrying the parcel, and reads it out at the door. The rider types it in to close the delivery.',
        'code_points' => [
            'It proves the parcel reached the person who placed the order, not just an address',
            'Visible only while it is in transit — before and after, it is hidden',
            'Limited attempts, so it cannot be guessed',
        ],

        'photo_title' => 'A photo at handover',
        'photo_body' => 'If nobody is there, or nobody can read a code out, the rider photographs the parcel where it was left. The photos stay attached to the order, and you open them from the order page whenever you need them.',
        'photo_points' => [
            'Two slots: the parcel and the place',
            'Attached to the order permanently, never deleted',
            'You, the delivery company and the platform can all see them in a dispute',
        ],

        'closer' => 'Both are recorded against the order. If anyone says it never arrived, the answer is already there.',
        'diagram_hint' => 'The code is issued with the order, shown to your customer in transit, and typed in at the door.',
    ],

    /*
     * Fees: a share of each order, no subscription.
     *
     * The figures in this section are computed by the pricing engine and the
     * live configuration rather than written by hand. There is no
     * subscription concept anywhere in the system — that is a fact about the
     * product, not a marketing line.
     */
    'fees' => [
        'subtitle' => 'A month with no orders costs nothing. The fee is calculated as each order closes and shown in the price breakdown in front of you.',

        'zero_label' => 'Monthly subscription',
        'zero_value' => '0',
        'zero_note' => 'No fixed fee, no order minimum, no fixed-term contract.',

        'rate_label' => 'Platform fee',
        'rate_note' => 'A share of the delivery price, taken when the order closes.',

        'example_title' => 'A real quote from the pricing engine',
        'example_note' => 'These are not illustrative figures — they come from the same engine that prices your orders.',
        'example_total' => 'Delivery price',
        'example_fee' => 'Platform fee',
        'example_company' => 'Goes to the delivery company',

        'chart_title' => 'What you pay, by order volume',
        'chart_note' => 'Calculated on the example order above. A monthly subscription is charged even at zero orders — a share is not.',
        'chart_orders' => 'orders',
        'chart_series_fee' => 'Platform fee',

        'points' => [
            'You pay nothing until an order actually closes',
            'A cancelled or failed order carries no fee',
            'The fee is itemised on every order, not a lump sum at month end',
            'Stop using the platform whenever you like, with nothing owed',
        ],
    ],

    'choose' => [
        'eyebrow' => 'Which side are you on?',
        'title' => 'The network has two doors. Take yours.',
        'body' => 'Both sides use the same system from opposite ends — one sends the orders, the other competes for them.',

        'business_title' => 'I run a shop and need delivery',
        'business_body' => 'Order once and the network works the companies for you. No phone calls.',
        'business_points' => [
            'One order reaches every delivery company at the same moment',
            'The companies compete for your order — you stop chasing them',
            'The price is known before you send, not negotiated each time',
            'Your customer follows a link and stops calling to ask where it is',
        ],
        'business_cta' => 'Register your business',
        'business_note' => 'The account works immediately. You can send your first order today.',

        'company_title' => 'I run a delivery company',
        'company_body' => 'Shop orders are distributed across the network every day. Be inside the distribution.',
        'company_points' => [
            'Orders come to you — no relationships, no phone calls',
            'Your ranking is computed on published criteria, not on who knows whom',
            'You set your zones, your price and your fleet capacity',
            'Your balance and settlements are recorded to the piastre',
        ],
        'company_cta' => 'Register your company',
        'company_note' => 'The platform reviews the account first. Until it is activated, no orders reach you.',

        'closer' => 'Not sure which you are? If you send orders you are a shop. If you carry them you are a company.',
    ],

    /*
     * Beside the login and register forms — the argument for the door you
     * came through.
     */
    'pitch' => [
        'business_title' => 'Stop hunting for a rider.',
        'business_body' => 'Write the order once; the network puts it to the delivery companies and brings back the fastest one to accept. Price, status and rider, all in one place.',
        'company_title' => 'The orders exist. The question is whether you are inside them.',
        'company_body' => 'Shops across Banha send their orders to the network, and each order is offered to every subscribed company at the same moment. A company outside the network never sees it.',
        'both_title' => 'One system. Both sides run on it.',
        'both_body' => 'Shops send, delivery companies compete, the customer follows a link. All in one place.',
    ],

    'companies' => [
        'eyebrow' => 'For delivery companies',
        'headline_one' => 'The order will not call you.',
        'headline_two' => 'It gets distributed.',
        'body' => 'The shop that works with you today rings you when it needs you. The moment it moves onto the network, the order rings nobody — it is offered in the same second to every company on it, and the first to accept takes it. If you are not on the network, that order never reaches you. You will not lose it. You will not see it.',

        'ranking_title' => 'And the ranking is not a favour',
        'ranking_body' => 'Every order is scored for every company, and these are the actual weights the system uses. Whoever answers faster, prices closer and has a rider nearer takes the work. There is no queue and no seniority.',
        'ranking_note' => 'These are the live weights the platform runs on right now — not an example.',

        'in_title' => 'On the network',
        'in_points' => [
            'Every order in your service areas reaches you',
            'You see the price and the payout before accepting',
            'Your performance lifts your rank on the next one',
            'Your earnings are worked out as you go',
        ],

        'out_title' => 'Outside it',
        'out_points' => [
            'The order never reaches you at all',
            'You wait for a shop to remember you and call',
            'You compete for shops that thin out every month',
            'And you have no number to prove you are good',
        ],

        'closer' => 'The network is running in Banha now. Every shop that joins is orders being handed out without you.',
        'cta' => 'Put your company on the network',
        'cta_note' => 'Registration is free. Commission applies only to orders you accept.',
    ],

    'network' => [
        'eyebrow' => 'The network',
        'title' => 'Real numbers from the network, not promises.',
        'delivered' => 'Deliveries completed',
        'companies' => 'Active delivery companies',
        'riders' => 'Riders on the network',
        'minutes' => 'Average delivery time in minutes',
    ],

    'footer' => [
        'blurb' => 'Delivery infrastructure for Banha. It connects shops to delivery companies and carries an order from creation through to hand-off and settlement.',
        'contact' => 'Contact',
        'governorate' => 'Qalyubia',
        'rights' => 'All rights reserved.',
        'columns' => [
            [
                'title' => 'For shops',
                'links' => ['Create a delivery', 'Track shipments', 'Invoices and accounts', 'Connect your store'],
            ],
            [
                'title' => 'For delivery companies',
                'links' => ['Receive orders', 'Manage riders', 'Service areas', 'Settlements'],
            ],
        ],
    ],
];
