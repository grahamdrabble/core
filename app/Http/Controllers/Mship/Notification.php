<?php

namespace App\Http\Controllers\Mship;

use Config;
use Auth;
use Request;
use URL;
use Input;
use Session;
use Redirect;
use VatsimSSO;
use App\Models\Mship\Account;
use App\Models\Mship\Qualification as QualificationType;

class Notification extends \App\Http\Controllers\BaseController {

    public function postAcknowledge($notification){
        $this->_account->readNotifications()->attach($notification);

        // If this is an interrupt AND we're got no more important notifications, then let's go back!
        if(Session::has("force_notification_read_return_url")){
            if(!Auth::user()->has_unread_important_notifications AND !Auth::user()->get_unread_must_read_notifications){
                return Redirect::to(Session::pull("force_notification_read_return_url"));
            }
        }

        return Redirect::route("mship.notification.list");
    }

    public function getList() {
        // Get all unread notifications.
        $unreadNotifications = $this->_account->unreadNotifications;
        $readNotifications = $this->_account->readNotifications;

        return $this->viewMake("mship.notification.list")
                    ->with("unreadNotifications", $unreadNotifications)
                    ->with("readNotifications", $readNotifications)
                    ->with("allowedToLeave", (!Session::has("force_notification_read_return_url") OR (!Auth::user()->has_unread_important_notifications AND !Auth::user()->get_unread_must_read_notifications)));
    }

}