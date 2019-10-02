<?php

namespace Spatie\Activitylog\Models;

use Illuminate\Database\Eloquent\Model;

class AnonymousCauser extends Model
{
    public $guarded = [];

    public function __construct(array $attributes = [])
    {
        if (! isset($this->connection)) {
            $this->setConnection(config('activitylog.database_connection'));
        }

        if (! isset($this->table)) {
            $this->setTable(config('activitylog.anonymous_causers_table_name'));
        }

        parent::__construct($attributes);
    }
    
}