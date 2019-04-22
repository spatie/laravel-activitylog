<?php

namespace Spatie\Activitylog\Causers;

use Spatie\Activitylog\Contracts\CauserManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Session\SessionManager;
use Illuminate\Contracts\Config\Repository;

class CauserManagerSession implements CauserManager
{
    /** @var Illuminate\Session\SessionManager */
    protected $sess;

    protected $causerClass;

    public function __construct(SessionManager $sess, Repository $config)
    {
        $this->sess = $sess;
        $this->causerClass = config('activitylog.session_causer_class');
    }

    public function getCauser($modelOrId)
    {
        if ($this->causerClass) {
            return $model = $this->causerClass::find($modelOrId);
        }
        return null;
    }

    public function getDefaultCauser() {
        $sess_id = $this->sess::getId();
        if ($this->causerClass) {
            return $this->causerClass::find($sess_id);
        }
        return null;
    }

}
