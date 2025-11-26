<?php
    session_start();
    include "config.php";
    
    $cursor = $scillarListing->aggregate([
    [
        '$lookup' => [
            'from' => 'users',           // collection to join
            'localField' => 'user_id',   // field in listing
            'foreignField' => '_id',     // field in users
            'as' => 'user'               // name of the new array field
        ]
    ],
    [
        '$unwind' => '$user'             // convert array to object (if you want single object per listing)
    ]
]);