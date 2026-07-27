<?php
return [
    'name'=>env('APP_NAME','Support Portal'), 'env'=>env('APP_ENV','production'),
    'debug'=>(bool)env('APP_DEBUG',false), 'url'=>env('APP_URL','http://localhost'),
    'version'=>'1.0.0',
    'ticket_statuses'=>['open'=>'Open','assigned'=>'Assigned','in_progress'=>'In Progress','waiting_for_client'=>'Waiting for Client','resolved'=>'Resolved','closed'=>'Closed'],
    'ticket_priorities'=>['low'=>'Low','medium'=>'Medium','high'=>'High','critical'=>'Critical'],
    'roles'=>['super_admin'=>'Super Admin','employee'=>'Employee','client'=>'Client'],
];
