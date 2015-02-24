<?php

namespace Models\Mship;

use Illuminate\Database\Eloquent\SoftDeletingTrait;
use Carbon\Carbon;

class Security extends \Eloquent {

	use SoftDeletingTrait;
        protected $table = "mship_security";
        protected $primaryKey = "security_id";
        protected $dates = ['created_at', 'deleted_at'];
        protected $hidden = ['security_id'];

        public function accountSecurity(){
            return $this->hasMany("\Models\Mship\Account\Security", "security_id", "security_id");
        }

        public static function getDefault(){
            return Security::where("default", "=", 1)->first();
        }
}
