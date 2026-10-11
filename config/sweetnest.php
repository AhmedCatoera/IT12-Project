<?php

/*
 * SweetNest's own settings: the shop, the order rules (rush fee, refund) and the
 * choices offered when ordering a cake (only cakes get those fields).
 */
return [

    // The shop itself: a pickup order starts with this address as the pickup spot (the
    // customer can ask for another place). The parts match a customer's saved address.
    // The map pin is approximate (Brgy. Santo Niño, Tugbok); drag it on the map to the exact spot if needed.
    'shop' => [
        'name' => 'SweetNest Homemade',
        'pickup_note' => null,
        'address' => [
            'street' => 'Lot 14 Block 5, Sampaguita St.',
            'address_line2' => 'Tugbok District',
            'barangay' => 'Santo Niño',
            'city' => 'Davao City',
            'province' => 'Davao del Sur',
            'postal_code' => '8000',
            'latitude' => 7.0846,
            'longitude' => 125.5093,
        ],
    ],

    // Deliveries only go to this city; customers elsewhere pick up instead.
    'delivery_city' => 'Davao City',

    // An order is rush when it is due within this many days of the order date
    // (1 = due tomorrow or today). Regular orders are due 2 or more days later.
    'rush_within_days' => 1,

    // Rush orders pay an extra fee on top of the items (percent of the items total).
    'rush_fee_default' => 40,
    'rush_fee_choices' => [40, 45],

    // When a fully paid order is cancelled, this share of the order total goes back to the
    // customer. An order that only paid its down payment gets nothing back.
    'refund_percent' => 50,

    // Type of cake. Sponge and Moist are plain cakes, so their flavor comes from
    // the icing. Chiffon cakes are flavored themselves.
    'cake_types' => [
        'sponge' => 'Sponge (plain)',
        'moist' => 'Moist (plain)',
        'chiffon' => 'Chiffon',
    ],

    // Flavors of a chiffon cake.
    'cake_flavors' => ['Ube', 'Orange', 'Strawberry', 'Chocolate', 'Mocha', 'Mango'],

    // Icing flavors for sponge and moist cakes.
    // PLACEHOLDER: replace with SweetNest's real icing list once Ahmed sends it.
    'icing_flavors' => ['Vanilla', 'Chocolate', 'Strawberry', 'Ube', 'Mocha', 'Cream cheese'],

    // Cake sizes (inches). "3x2" is the bento cake.
    'cake_sizes' => ['3x2 (bento cake)', '6x6', '6x5', '7x7', '7x5', '8x8'],

    // How many tiers a cake has.
    'cake_tiers' => [1, 2, 3],

];
