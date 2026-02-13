<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Categories that use "Project Name" field in Asset Transaction
    |--------------------------------------------------------------------------
    | When user selects one of these categories for Assign, the form shows
    | "Project Name" field instead of "Employee Name". All other categories
    | show "Employee Name" field.
    | Use exact category_name as in asset_categories table (case-insensitive match).
    */
    'project_name_categories' => [
        'Printer',
        'Plotter',
        'Scanner',
        'All-in-One Printer',
        'License',
        'Cloud Subscription',
        'SSL Certificate',
        'Domain',
        'Public IP',
        'NAS Storage',
        'SAN Storage',
        'Backup Device / Tape',
        'UPS',
        'PDU',
        'Server Rack',
        'Rack Accessories',
        'PABX',
        'Telephone',
        'Video Conferencing',
        'Interactive Panel / Smart TV',
        'CCTV Camera',
        'NVR / DVR',
        'Projector',
        'Monitor',
        'Keyboard',
        'Mouse',
        'Docking Station',
        'Webcam',
        'Headset',
        'Virtual Machine',
        '4G/5G Router',
        'Load Balancer',
        'KVM Switch',
        'Network Cable',
        'SFP Module',
        'DR Site',
    ],
];
