<?php

namespace App\Http\Controllers\API;

use App\AppServices\SallaServices\SallaServiceHandle;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SallaController extends Controller
{
    //
    public $action;

    public function __construct()
    {
        $this->action = new SallaServiceHandle;
    }

    public function make_event($json = null)
    {
        //    return $this->get_users_setting();

        if ($json == null) {
            $json = file_get_contents('php://input');
        }
        $data = json_decode($json);

        if ($data->event == 'app.store.authorize') {
            $this->action->make_auth($data,$json);
        } elseif ($data->event == 'abandoned.cart') {
            $this->action->make_abandBasket($data,$json);
        } elseif ($data->event == 'app.settings.updated') {
            $this->action->update_setting($data);
        } elseif ($data->event == 'app.subscription.started' || $data->event == 'app.subscription.renewed') {
            //Something to write to txt log
            $log = json_encode($data, JSON_UNESCAPED_UNICODE);
            Storage::disk('local')->append('subscriptions/subscriptions.log', $log);

            include_once 'subscription.php';
        } elseif ($data->event == 'order.created' || $data->event == 'order.updated') {
            // $log = json_encode($data, JSON_UNESCAPED_UNICODE);
            // Storage::disk('local')->append('debug1.log',$log);
            include_once 'order-status.php';
            // $log = json_encode($data, JSON_UNESCAPED_UNICODE);
            // Storage::disk('local')->append('debug.log',$log);
        } elseif ($data->event == 'customer.otp.request') {
            include_once 'otp.php';
        } elseif ($data->event == 'customer.created' || $data->event == 'review.added') {
            include_once 'customer-created.php';
        } elseif ($data->event == 'order.status.updated') {
            //include_once 'manual-review-request.php';
            // do nothing
            return;
        } elseif ($data->event == 'app.subscription.expired' || $data->event == 'app.subscription.canceled' || $data->event == 'app.uninstalled') {
            $log = json_encode($data, JSON_UNESCAPED_UNICODE) . PHP_EOL;
            Storage::disk('local')->append('subscriptions/' . $data->event . '.log', $log);
        } else {
            $log = json_encode($data, JSON_UNESCAPED_UNICODE) . PHP_EOL;
            Storage::disk('local')->append($data->event . '.log', $log);
            return 200;
        }
    }
}
