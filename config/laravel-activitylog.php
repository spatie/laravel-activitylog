<?php

return [

    /**
     * When running the clean-command all recording activities older than
     * the number of days specified here will be deleted.
     */
    'delete_records_older_than_days' => 365,


    /**
     * When not specifying a log name when logging activity
     * we'll using this log name.
     */
    'default_log_name' => 'default',

    /**
     * You can specify an auth driver here that gets user models.
     * When this is null we'll use the default Laravel auth driver.
     */
    'default_auth_driver' => null,

    /**
    * When set to true, the subject returns soft deleted models
    */
    'subject_withTrashed' => false,
];
