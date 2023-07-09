<?php

namespace App\AppServices\SallaServices;

use App\Models\AbandBaskts;
use App\Models\SpUser;
use App\Models\Team;
use App\Services\SallaService;
use Carbon\Carbon;

class SallaServiceHandle
{
    public $service;

    function __construct()
    {
        $this->service = new SallaService();
    }

    public function make_auth($data, $json)
    {
        $merchant_id = $data->merchant; // = ids
        $auth_token = $data->data->access_token;
        $refresh_auth_token = $data->data->refresh_token;
        $created_at = $data->created_at;

        if ($this->check_user($json)) {

            $update_tokens =  SpUser::query()
                ->where('ids', $merchant_id)
                ->update([
                    'access_token' => $auth_token,
                    'refresh_token' => $refresh_auth_token
                ]);
        } else {
            $url = "https://api.salla.dev/admin/v2/oauth2/user/info";
            $karzoun_callback =  $this->service->get($url, $auth_token);
            $user = $this->store_auth($karzoun_callback, $data);
        }
    }



    public function store_auth($karzoun_callback, $data)
    {
        $merchant_id = $data->merchant; // = ids
        $auth_token = $data->data->access_token;
        $refresh_auth_token = $data->data->refresh_token;
        $created_at = $data->created_at;
        $store_id = $karzoun_callback->data->merchant->id;
        $usrname = preg_replace('/[^A-Za-z0-9\-\'".]/', '', $karzoun_callback->data->merchant->username);
        $store_name_none_clean = $karzoun_callback->data->merchant->username; // fullname
        $store_name = preg_replace('/[^A-Za-z0-9\-\'".]/', '', $store_name_none_clean);
        $store_domain = $karzoun_callback->data->merchant->domain;
        $email = strtolower($karzoun_callback->data->email);
        $phone_none_clean = $karzoun_callback->data->mobile;
        $phone = str_replace("+", "", $phone_none_clean);
        $usr_data = json_encode($karzoun_callback);
        $plan_id = '1'; // have to be updated with new plan id
        echo $usrname . ' ' . $store_name;
        /*** generate password to send to clinet ***/
        $usrpassword = md5($merchant_id);
        $date = Carbon::now();
        $date->addDays(12);
        $date_plus_12 = $date->toDateTimeString();

        $SpUser = new SpUser();
        $SpUser->ids = $merchant_id;
        $SpUser->role = 0;
        $SpUser->fullname = $store_name;
        $SpUser->username = $store_name;
        $SpUser->merchant_phone = $phone;
        $SpUser->email = $email;
        $SpUser->password = $usrpassword;
        $SpUser->plan = 1;
        $SpUser->expiration_date = '1704070861';
        $SpUser->timezone = 'Asia/Riyadh';
        $SpUser->login_type = 'salla';
        $SpUser->status = 2;
        $SpUser->created = $created_at;
        $SpUser->data = $usr_data;
        $SpUser->access_token = $auth_token;
        $SpUser->refresh_token = $refresh_auth_token;
        $SpUser->token_expiry = $date_plus_12;
        $SpUser->save();

        return $SpUser;
    }

    public function check_user($json)
    {
        $user_data = json_decode($json);
        $user = SpUser::where("ids", $user_data->merchant)->first();

        $flag = false;
        if (!empty($user)) {
            $flag = true;
        }

        return $flag;
    } 

    public function make_abandBasket($data, $json)
    {
        $id_data = $data->data->id;
        $check_aband = AbandBaskts::where("data", "like", "%$id_data%")->first();
        if (empty($check_aband)) {
            $appand_baskts = new AbandBaskts();
            $appand_baskts->data = $json;
            $appand_baskts->save();
            echo 'test';
        }
    }

    public function update_setting($data)
    {
        $merchant_id = $data->merchant;
        $serialized_data = serialize($data);

        Team::where('ids', $merchant_id)
            ->update(['app_settings' => $serialized_data]);
    }


   
}
