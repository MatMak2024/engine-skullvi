<?php

return [

    'rubric_version' => 'v2',

    'thresholds' => [
        'priority' => 70,
        'to_review' => 50,
    ],

    'levels' => [
        'beginner' => 0.6,
        'intermediate' => 0.85,
        'advanced' => 1.0,
    ],

    'criteria' => [
        'skills' => [
            'max' => 30,
            'required_max' => 25,
            'bonus_max' => 5,
        ],
        'experience' => [
            'max' => 20,
            'points_per_year' => 2,
            'year_cap' => 5,
            'points_per_project' => 2,
            'project_cap' => 5,
        ],
        'education' => [
            'max' => 15,
            'points' => [
                'none' => 0,
                'bac' => 6,
                'licence' => 10,
                'master' => 15,
                'other' => 8,
            ],
        ],
        'proofs' => [
            'max' => 15,
            'github' => 8,
            'portfolio' => 5,
            'linkedin' => 2,
            'cv' => 3,
            'qualifications' => 2,
            'hosts' => [
                'github' => 'github.com',
                'linkedin' => 'linkedin.com',
            ],
        ],
        'motivation' => [
            'max' => 10,
            'length_max' => 6,
            'keyword_max' => 4,
            'short_below' => 80,
            'length_bands' => [
                ['min' => 400, 'points' => 6],
                ['min' => 200, 'points' => 4],
                ['min' => 80, 'points' => 2],
            ],
        ],
        'availability' => [
            'max' => 5,
            'points' => [
                'immediate' => 5,
                'one_month' => 3,
                'later' => 1,
            ],
        ],
        'location' => [
            'max' => 5,
            'declared' => [
                'country' => 3,
                'city' => 1,
                'precise' => 1,
            ],
            'onsite' => [
                'country' => 2,
                'city' => 2,
                'precise' => 1,
            ],
            'hybrid' => [
                'country' => 3,
                'city' => 1,
                'precise' => 1,
            ],
        ],
    ],

];
