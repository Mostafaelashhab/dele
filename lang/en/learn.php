<?php

/*
 * The manual, one lesson per role.
 *
 * Written to be read by somebody deciding whether to use this, so every step
 * says what actually happens rather than what it is called. Nothing here
 * describes behaviour the product does not have — where a limit or a wait
 * exists, the step says so.
 */

return [
    'hub' => [
        'eyebrow' => 'Learn the system',
        'title' => 'Pick which one you are, and I will explain all of it in order.',
        'body' => 'Each guide is written for one role only. You will not be made to read somebody else’s instructions.',
        'cta' => 'Start the guide',
        'steps_word' => 'steps',
        'switch' => 'Not you? Read the guide for:',
        'back' => 'All guides',
    ],

    'individual' => [
        'label' => 'Someone sending a parcel',
        'tagline' => 'No shop, no paperwork.',
        'intro' => 'You have something to get to someone in Banha. You have no shop and you are not about to start a company — you want somebody to carry it, deliver it, and prove it arrived. Here is exactly what happens, step by step.',

        'steps' => [
            1 => [
                'title' => 'An account, in about a minute',
                'body' => 'We need your name, phone, email and a password. We do not ask for a trade licence or a tax card — you are not a company. The account works immediately, with no review.',
                'points' => [
                    'No paperwork and no signup fee',
                    'Ready to send your first order the same moment',
                    'You work from the browser; there is nothing to install',
                ],
            ],
            2 => [
                'title' => 'You write the order and the price forms in front of you',
                'body' => 'You enter where from, where to, and who is receiving it. As you type, the price is calculated and itemised: how far it is, which area, what that area costs. No other figure is quoted to you afterwards.',
                'points' => [
                    'Priced from distance, area and how urgently you need it moved',
                    'Choose scheduled (cheapest), standard, or express',
                    'Say whether it is paid up front or collected on delivery',
                    'Leave a note for the rider — the floor, a landmark',
                ],
            ],
            3 => [
                'title' => 'The network looks for someone to carry it',
                'body' => 'The moment you confirm, the order is offered to every delivery company and independent rider covering that area — all at the same time. The first to accept takes it. You call nobody and you wait on nobody.',
                'points' => [
                    'Offered to several at once, not one after another',
                    'Ranked by rider proximity, price, response speed and completion rate',
                    'If nobody answers, it moves to another group on its own',
                    'If nobody at all is available, you are told — you are not left waiting',
                ],
            ],
            4 => [
                'title' => 'Whoever receives it follows a link',
                'body' => 'They get a link. It opens on any phone with nothing to download and no signup. They see where it is, who is carrying it and when it arrives. The rider’s position updates as they ride — only while they are carrying it, never before or after.',
                'points' => [
                    'A link that opens in any browser; no app',
                    'The rider’s position shows only while it is in transit',
                    'The expected arrival updates on its own',
                    'That link carries their address — do not forward it to anyone else',
                ],
            ],
            5 => [
                'title' => 'The handover is recorded, not asserted',
                'body' => 'Once the rider is on the way, a code appears for the recipient on the tracking page. They read it to the rider at the door, and the rider types it in to close the order. If nobody is there to read a code out, the rider photographs the parcel where it was left. It does not close without one of the two.',
                'points' => [
                    'The code proves it reached the person, not just an address',
                    'Limited attempts, so it cannot be guessed',
                    'The photo stays attached to the order and you can open it any time',
                    'Money is between you and whoever delivered — the platform holds none',
                ],
            ],
        ],
    ],

    'business' => [
        'label' => 'A shop owner',
        'tagline' => 'Order once; the companies compete.',
        'intro' => 'You have a shop and you deliver to customers. The problem is not the delivery — it is that you call a company, nobody answers, you call the next one, you negotiate the price again, and your customer rings asking where the order is and you cannot say. That is what changes.',

        'steps' => [
            1 => [
                'title' => 'Register the shop and set your area',
                'body' => 'Shop name, what you sell, your main area and contact details. The account works immediately — no review and no waiting. You can send your first order today.',
                'points' => [
                    'Active from the first moment',
                    'Your main area is saved so you are not retyping it',
                    'Add other pickup addresses later',
                    'Add staff with their own logins',
                ],
            ],
            2 => [
                'title' => 'The order goes to every company at once',
                'body' => 'You do not pick a company. You create the order and the network offers it to every company and rider covering that route in the same second. The first to accept takes it. You are not tied to one company when it is late, closed, or has raised its price.',
                'points' => [
                    'No calls and no haggling',
                    'If your usual company is busy, the order finds another by itself',
                    'You can prefer a company, or block one after a bad run',
                    'If nobody answers, it is offered to another group automatically',
                ],
            ],
            3 => [
                'title' => 'Priced by rules, not by mood',
                'body' => 'The price comes from fixed rules: distance, area, parcel size and urgency. It is itemised in front of you before you confirm. Months later you can open any old order and understand where the figure came from.',
                'points' => [
                    'Every line of the price is named',
                    'Three levels: scheduled is cheaper, standard, express',
                    'Paid up front or collected on delivery',
                    'The platform takes no commission — the price goes to whoever delivered',
                ],
            ],
            4 => [
                'title' => 'Your customer stops calling you',
                'body' => 'Every order produces a tracking link for your customer. They see where the parcel is, who has it and when it arrives. That ends the calls you cannot answer, and makes a small shop look like it has real logistics behind it.',
                'points' => [
                    'A link that opens on any phone, with no app',
                    'The rider’s position shows only while it is in transit',
                    'A handover code, so nobody else can take the parcel',
                    'Proof of delivery by photo or code, kept on the order',
                ],
            ],
            5 => [
                'title' => 'If it is disputed, the answer is already there',
                'body' => 'Every step is recorded with its time and who performed it, from creation to handover. And the handover itself does not close without proof. If someone says it never arrived, you are not arguing — you are opening the order and looking.',
                'points' => [
                    'A complete timeline on every order',
                    'Delivery photos open from the order page at any time',
                    'The code proves the person who received it was the right one',
                    'Every pound has a ledger entry you can check',
                ],
            ],
            6 => [
                'title' => 'Connect your store if you want to',
                'body' => 'If you have an online store or a point of sale, connect it with an API key and orders are sent automatically without anyone retyping them. If you do not need that, the screen is enough.',
                'points' => [
                    'An API key per shop',
                    'Orders sent automatically from your system',
                    'Your system is notified when a status changes',
                    'Entirely optional',
                ],
            ],
        ],
    ],

    'company' => [
        'label' => 'A delivery company',
        'tagline' => 'Orders are being distributed. Be inside it.',
        'intro' => 'You run a delivery company with riders. The work you get comes through relationships and phone calls, and a rider sits idle between jobs. This network distributes Banha shop orders across the companies inside it — and that happens whether you are in or out.',

        'steps' => [
            1 => [
                'title' => 'Register, then wait for review',
                'body' => 'You enter the company name, how many riders you run, and the areas you cover. The account works immediately and you can set everything up — but no offers reach you until the platform activates it. That is not an obstruction; it is what lets a shop trust whoever collects its parcel.',
                'points' => [
                    'Set up your areas, pricing and riders while you wait',
                    'Offers begin the moment the account is activated',
                    'No signup fee and no subscription',
                ],
            ],
            2 => [
                'title' => 'Offers come to you; you do not hunt for them',
                'body' => 'The moment a shop creates an order in an area you cover, it is offered to you and to others at the same instant. You have a limited window to answer. The first to accept takes it — so your response speed is not a detail, it is the difference between taking the job and watching it go.',
                'points' => [
                    'The offer carries the route, the distance, the price and your share',
                    'Accept or decline in one tap',
                    'Miss the window and it goes to somebody else',
                    'Turn on auto-accept if you are confident in your coverage',
                ],
            ],
            3 => [
                'title' => 'Your ranking is computed and published',
                'body' => 'When an order is offered, companies are ranked on computed criteria — not on anyone’s mood and not on who knows whom. Those criteria are shown to you up front and you can work on them.',
                'points' => [
                    'How near your closest rider is to the pickup',
                    'How many riders you have free right now',
                    'Your price against the rest',
                    'Your completion rate and how fast you answer',
                ],
            ],
            4 => [
                'title' => 'You assign it to your riders',
                'body' => 'After you accept, you choose the rider. The job appears on their screen, they accept, and they go. You see all your riders on a map with their positions updating as they ride.',
                'points' => [
                    'A live map of every rider and where they are',
                    'See who is free and who is carrying how many',
                    'If a rider does not answer, assign another',
                    'Each rider has a capacity that is never exceeded',
                ],
            ],
            5 => [
                'title' => 'The handover is recorded',
                'body' => 'A rider cannot mark an order delivered without proof: the customer’s code, or a photo of the parcel. That protects you before it protects the shop — if someone denies receiving it, the evidence is on the order rather than on your rider’s word.',
                'points' => [
                    'A code the customer reads to the rider at the door',
                    'Or a photo of the parcel where it was left',
                    'Both kept on the order permanently',
                    'A full timeline of every step and who performed it',
                ],
            ],
            6 => [
                'title' => 'Your books close themselves',
                'body' => 'Every closed order is recorded with a double-entry posting: the delivery price, what you are owed, and any cash collected. Settlements are generated from those entries rather than reconciled by hand at month end.',
                'points' => [
                    'A statement you can check at any time',
                    'The platform takes no commission at this stage',
                    'Cash collected on delivery is recorded per order',
                    'You can enter a job that came to you outside the network so its customer gets tracking',
                ],
            ],
        ],
    ],

    'rider' => [
        'label' => 'An independent rider',
        'tagline' => 'Nobody behind you? Register in your own name.',
        'intro' => 'You deliver on your own; you are not employed by a company. The network treats you like any company: orders are offered to you on the same criteria, and the delivery price is yours in full. The only difference is that we have to know who you are first.',

        'steps' => [
            1 => [
                'title' => 'Register in your own name',
                'body' => 'Your name, your phone, what you ride, and the areas you cover. No trade licence, no company, and nobody needed to vouch for you.',
                'points' => [
                    'No company paperwork and no fees',
                    'You set your own areas',
                    'You set your own hours',
                ],
            ],
            2 => [
                'title' => 'We need your ID and your photo',
                'body' => 'This is the one thing asked of you and of nobody else. A company vouches for its employees; alone, nobody vouches for you — and you will be collecting a stranger’s parcel. So we take both sides of your ID card and a clear photo of you.',
                'points' => [
                    'Your ID is stored where it has no public address — only the platform reviewer can reach it',
                    'Only your photo is ever shown to a customer at the door',
                    'We do not ask companies or shops for an ID',
                ],
            ],
            3 => [
                'title' => 'After review, orders start reaching you',
                'body' => 'Until your details are checked the account works but is not in dispatch. Once activated you sit in exactly the same ranking as the companies — your proximity, your price, your response speed, your completion rate.',
                'points' => [
                    'You compete beside the companies on the same criteria',
                    'One job at a time — your capacity is one',
                    'You see the route, the distance and your share before accepting',
                    'Stop receiving at any time from a single button',
                ],
            ],
            4 => [
                'title' => 'Run it from your phone',
                'body' => 'The screen is built to be used one-handed while wearing a helmet. Large targets, and one step in front of you at a time: collected, on the way, arrived, delivered.',
                'points' => [
                    'Runs in the browser; nothing to install',
                    'Your position is sent as you ride so the customer can follow',
                    'Sent only while you are carrying a parcel — not all day',
                    'The customer’s notes and address are in front of you',
                ],
            ],
            5 => [
                'title' => 'Close the job with proof',
                'body' => 'You cannot mark an order delivered without one of the two: the customer’s code read to you at the door, or a photo of the parcel where you left it. This protects you — if anyone denies receiving it, the evidence is recorded.',
                'points' => [
                    'The code is faster at a door',
                    'The photo is for when nobody is there to read one out',
                    'Both are kept on the order',
                    'Your rating is built on your own completion, not a company’s',
                ],
            ],
            6 => [
                'title' => 'The money is yours',
                'body' => 'The delivery price goes to you in full. The platform takes no commission at this stage and holds no money between you and the shop — you settle between yourselves exactly as you do now.',
                'points' => [
                    'No commission on an order',
                    'No monthly subscription',
                    'A statement covering every job you have done',
                    'Cash collected on delivery is recorded per order',
                ],
            ],
        ],
    ],
];
