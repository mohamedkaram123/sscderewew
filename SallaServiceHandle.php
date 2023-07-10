<?php

namespace App\AppServices\SallaServices;

use App\Models\AbandBaskts;
use App\Models\SpUser;
use App\Models\SuccessTempModel;
use App\Models\Team;
use App\Services\SallaService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

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

    public function make_order_create_and_update($data, $json)
    {
        $this->service->make_msg($data, $json);
    }

    public function make_otp($data, $json)
    {
        $store_id = $data->merchant;
        $event = $data->event;
        $customer_mobile = $data->data->contact;
        $phone = str_replace("+", "", $customer_mobile);
        $otp = $data->data->code;
        $send_once = $phone . $otp;
        $count_data = SuccessTempModel::where("unique_number", $send_once)->count();
        if ($count_data === 0) {
            $array =  $this->service->get_merchant_settings($store_id);
            $setting = $array->data->settings;

            $get_instance_id =  $this->service->get_instance_id2($store_id);
            $instance_id = $get_instance_id[0][0];
            $instance_id_B = $get_instance_id[0][1] ?? $get_instance_id[0][0];
            $api_url = $get_instance_id['1'];

            $send_or_not = $setting->karzoun_otp_msg_check ?? '0';
            $message_to_send = $setting->karzoun_otp_msg ?? 'رمز التحقق {رمز التحقق}';
            // echo $message_to_send.PHP_EOL;
            ///////////التعامل مع المتغيرات/////////////
            preg_match_all("/{(.*?)}/", $message_to_send, $search);
            foreach ($search[1] as $variable) {
                if ($variable == "رمز التحقق" || $variable == "رمز التحقق") {
                    $message_to_send = str_replace('{' . $variable . '}', $otp, $message_to_send);
                    // echo $message_to_send.PHP_EOL;
                }
            }

            $message_to_send = urlencode($message_to_send);

            if ($send_or_not == 1) {
                $this->service->log_result(true, $json, 'NotSet', $event, $send_once, 'started');

                $response =  $this->service->send_message($phone, $message_to_send, $instance_id, $store_id, $api_url);

                if ($api_url == 'api.ultramsg.com') {
                    $this->service->log_result(true, $json, 'NotSet', $event, $send_once, 'ULTRA');
                    return;
                }
                $check_if_sent = json_decode($response);

                if (!isset($check_if_sent->status)) {
                    echo $response;
                    echo $message_to_send;
                } elseif (!empty($check_if_sent) && $check_if_sent->status == 'success' && isset($check_if_sent->message)) {
                    $this->service->log_result(true, $json, 'NotSet', $event, $send_once, 'true');
                    echo $response;
                    //   file_put_contents('karzoun_log/success-new-customer.txt', $log, FILE_APPEND);
                } else {
                    $this->service->log_result(false, $json, $response, $event, $send_once, 'null');
                    echo $response; // . $curlerr . ' error says : ' . $curlerr2.PHP_EOL;
                }
            }
        } else {
            echo '{"status":"sent before"}';
        }
    }

    public function make_customer($data, $json)
    {
        $store_id = $data->merchant;
        $created_at = $data->created_at;
        $sotre_url = $data->data->urls->customer;
        $customer_mobile = $data->data->mobile;
        $customer_mobile_code = $data->data->mobile_code;
        $customer_id = $data->data->id;
        $customer_mobile_code_clean = str_replace("+", "", $customer_mobile_code);
        $phone = $customer_mobile_code_clean . $customer_mobile;
        $send_once = $customer_id . $phone;

        $count_data = SuccessTempModel::where("unique_number", $send_once)->count();
        if ($count_data === 0) {

            $customer_first_name = $data->data->first_name;
            $customer_last_name = $data->data->last_name;
            $customer_full_name = $customer_first_name . ' ' . $customer_last_name;
            $array =  $this->service->get_merchant_settings($store_id);
            $setting = $array->data->settings;
            $get_instance_id =  $this->service->get_instance_id2($store_id);
            if ($array === null) {
                $log = json_encode($json . PHP_EOL, JSON_UNESCAPED_UNICODE) . PHP_EOL;
                Storage::disk('local')->append('no_settings/no_settings.txt', $log);
                return "reconnect";
            }

            if ($get_instance_id == null || $array == null) {
                return "reconnect";
            }
            $instance_id = $get_instance_id[0][0];
            $instance_id_B = $get_instance_id[0][1] ?? $get_instance_id[0][0];
            $api_url = $get_instance_id['1'];

            $send_or_not = $setting->karzoun_welcome_msg_check;
            $message_to_send = $setting->karzoun_welcome_msg;

            preg_match_all("/{(.*?)}/", $message_to_send, $search);
            foreach ($search[1] as $variable) {
                if ($variable == "اسم العميل" || $variable == "اسم العميل") {
                    $message_to_send = str_replace("{" . $variable . "}", $customer_full_name, $message_to_send);
                }
            }
            $message_to_send = urlencode($message_to_send);

            if ($send_or_not == 1) {
                $SuccessTempModel = new SuccessTempModel();
                $SuccessTempModel->unique_number = $send_once;
                $SuccessTempModel->values = $json;
                $SuccessTempModel->type = "new-customer";
                $SuccessTempModel->event_from = "salla";
                $SuccessTempModel->status = "started";
                //$SuccessTempModel->save();

                $response =  $this->service->send_message($phone, $message_to_send, $instance_id, $store_id, $api_url);
                $check_if_sent = json_decode($response);

                echo $response;

                $log = $send_once . ' ' . $created_at . PHP_EOL;


                if (!isset($check_if_sent->status)) {
                } elseif (!empty($check_if_sent)) {

                    if ($check_if_sent->status == 'success') {
                        $SuccessTempModel = new SuccessTempModel();
                        $SuccessTempModel->unique_number = $send_once;
                        $SuccessTempModel->values = $json;
                        $SuccessTempModel->type = "new-customer";
                        $SuccessTempModel->event_from = "salla";
                        $SuccessTempModel->save();
                    } else {
                        create_faild_msg($send_once, 0, $json, $response, "new-customer");
                    }
                } else {
                    create_faild_msg($send_once, 0, $json, $response, "new-customer");
                }
            } else {
                echo 'set to not send';
            }
        } else {
            echo "Sent Before :)";
        }
    }

    public function update_setting($data)
    {
        $merchant_id = $data->merchant;
        $serialized_data = serialize($data);

        Team::where('ids', $merchant_id)
            ->update(['app_settings' => $serialized_data]);
    }

    public function make_subscription($data, $json)
    {
        $merchant_id = $data->merchant;
        $plan_name = $data->data->plan_name;
        $end_date_full =

            $end_date = strtotime($data->data->end_date); //substr($end_date_full, 0, 10);
        if ($plan_name == 'Free') {
            $plan_id = 1;
        } elseif ($plan_name == 'Pro') {
            $plan_id = 2;
        } elseif ($plan_name == 'Business') {
            $plan_id = 3;
        } elseif ($plan_name == 'Enterprise') {
            $plan_id = 4;
        } else {
            $plan_id = 1;
        }
    }
}
