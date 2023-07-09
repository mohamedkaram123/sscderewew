<?php

namespace App\Services;

use App\Models\Instance;
use App\Models\ReviewRequest;
use App\Models\Team;
use Illuminate\Support\Facades\Storage;

class SallaService
{
    public $apitype;
    public $u_token;

    public function get($url,$auth_token)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        // curl_setopt($curl, CURLOPT_, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $auth_token",
            "Content-Type: application/json"
        ]);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        // curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        $response = curl_exec($curl);
        $res_data = json_decode($response);
        $curel_error =  curl_errno($curl);
        if (curl_errno($curl)){
            $res_data = curl_error($curl);
        }
        curl_close($curl);

        return $res_data;
    }

    public function make_msg()
    {
        $event = $data->event;
        if ($event == 'order.created' && $cod_msg_check_if_active == 1 && $payment_method == 'cod') {
            $send_or_not = 1;
            $message_to_send = $array
                ->data
                ->settings->karzoun_cod_msg;
        } elseif ($order_status_check == 'قيد التنفيذ') {
            $send_or_not = $array
                ->data
                ->settings->karzoun_order_proccessing_msg_check;
            $message_to_send = $array
                ->data
                ->settings->karzoun_order_proccessing_msg;
        } elseif ($order_status_check == 'بإنتظار المراجعة') {
            $send_or_not = $array
                ->data
                ->settings->karzoun_order_on_hold_msg_check;
            $message_to_send = $array
                ->data
                ->settings->karzoun_order_on_hold_msg;
        } elseif ($order_status_check == 'تم التنفيذ') {
            $send_or_not = $array
                ->data
                ->settings->karzoun_order_completed_msg_check;
            $message_to_send = $array
                ->data
                ->settings->karzoun_order_completed_msg;
            $review_msg_check_if_active = $array
                ->data
                ->settings->karzoun_review_msg_check ?? 0;
            if ($review_msg_check_if_active == 1 && $karzoun_review_check_status == 'completed') {
                //$id_data = $data->data->id;
                // $chec_aband = ReviewRequest::where("data", "like", "%$order_id%")->first();
                // if (empty($chec_aband))
                // {
                $appand_review = new ReviewRequest();
                $appand_review->data = $json;
                $appand_review->save();
                // }
            }
        } elseif ($order_status_check == 'بإنتظار الدفع') {
            $checkout_url = $data
                ->data
                ->urls
                ->customer;
            $send_or_not = $array
                ->data
                ->settings->karzoun_order_pending_payment_msg_check;
            $message_to_send = $array
                ->data
                ->settings->karzoun_order_pending_payment_msg;
        } elseif ($order_status_check == 'تم التوصيل') {
            $send_or_not = $array
                ->data
                ->settings->karzoun_order_delivered_msg_check;
            $message_to_send = $array
                ->data
                ->settings->karzoun_order_delivered_msg;
            $review_msg_check_if_active = $array
                ->data
                ->settings->karzoun_review_msg_check ?? 0;
            if ($review_msg_check_if_active == 1 && $karzoun_review_check_status == 'delivered') {
                //$id_data = $data->data->id;
                // $chec_aband = ReviewRequest::where("data", "like", "%$order_id%")->first();
                // if (empty($chec_aband))
                // {
                $appand_review = new ReviewRequest();
                $appand_review->data = $json;
                $appand_review->save();
                // }
            }
        } elseif ($order_status_check == 'ملغي') {
            $send_or_not = $array
                ->data
                ->settings->karzoun_order_canceled_msg_check;
            $message_to_send = $array
                ->data
                ->settings->karzoun_order_canceled_msg;
        } elseif ($order_status_check == 'جاري التوصيل') {
            $send_or_not = $array
                ->data
                ->settings->karzoun_on_delivery_msg_check;
            $message_to_send = $array
                ->data
                ->settings->karzoun_on_delivery_msg;
        } elseif ($order_status_check == 'مسترجع') {
            $send_or_not = $array
                ->data
                ->settings->karzoun_order_refunded_check;
            $message_to_send = $array
                ->data
                ->settings->karzoun_order_refunded_msg;
        } elseif ($order_status_check == 'قيد الإسترجاع') {
            $send_or_not = $array
                ->data
                ->settings->karzoun_order_refunding_check;
            $message_to_send = $array
                ->data
                ->settings->karzoun_order_refunding_msg;
        } elseif ($order_status_check == 'تم الشحن') {
            $send_or_not = $array
                ->data
                ->settings->karzoun_shipped_msg_check;
            $message_to_send = $array
                ->data
                ->settings->karzoun_shipped_msg;
            $review_msg_check_if_active = $array
                ->data
                ->settings->karzoun_review_msg_check ?? 0;
            if ($review_msg_check_if_active == 1 && $karzoun_review_check_status == 'shipped') {
                //$id_data = $data->data->id;
                // $chec_aband = ReviewRequest::where("data", "like", "%$order_id%")->first();
                // if (empty($chec_aband))
                // {
                $appand_review = new ReviewRequest();
                $appand_review->data = $json;
                $appand_review->save();
                // }
            }
        } else {
            $send_or_not = 0;
            return;
        }


        preg_match_all("/{(.*?)}/", $message_to_send, $search);
        foreach ($search[1] as $variable) {
            if ($variable == "حالة الطلب" || $variable == "حالة الطلب") {
                $message_to_send = str_replace("{" . $variable . "}", $order_status, $message_to_send);
            } else if ($variable == "رقم الطلب" || $variable == "رقم الطلب") {
                $message_to_send = str_replace("{" . $variable . "}", $order_id, $message_to_send);
            } else if ($variable == "قيمة الطلب" || $variable == "قيمة الطلب") {
                $message_to_send = str_replace("{" . $variable . "}", $order_amount, $message_to_send);
            } else if ($variable == "اسم العميل" || $variable == "اسم العميل") {
                $message_to_send = str_replace("{" . $variable . "}", $customer_full_name, $message_to_send);
            } else if ($variable == "العملة") {
                $message_to_send = str_replace("{" . $variable . "}", $currency, $message_to_send);
            } else if ($variable == "ايميل العميل" || $variable == "ايميل العميل") {
                $message_to_send = str_replace("{" . $variable . "}", $customer_email, $message_to_send);
            } else if ($variable == "رابط معلومات الطلب" || $variable == "رابط معلومات الطلب") {
                $message_to_send = str_replace("{" . $variable . "}", $order_url, $message_to_send);
            } else if ($variable == "رقم التتبع" || $variable == "رقم التتبع") {
                $message_to_send = str_replace("{" . $variable . "}", $tracking_number, $message_to_send);
            } else if ($variable == "اسم المستلم" || $variable == "اسم المستلم") {
                $message_to_send = str_replace("{" . $variable . "}", $ship_to, $message_to_send);
            } else if ($variable == "المدينة") {
                $message_to_send = str_replace("{" . $variable . "}", $city, $message_to_send);
            } else if ($variable == "رابط التتبع" || $variable == "رابط التتبع") {
                $message_to_send = str_replace("{" . $variable . "}", $tracking_link, $message_to_send);
            } else if ($variable == "رابط الدفع" || $variable == "رابط الدفع") {
                $message_to_send = str_replace("{" . $variable . "}", $checkout_url, $message_to_send);
            } else if ($variable == "رابط التقييم" || $variable == "رابط التقييم") {
                $message_to_send = str_replace("{" . $variable . "}", $rating_link, $message_to_send);
            } else if ($variable == "المنتجات") {
                foreach ($data->data->items as $item) {
                    $code_list[] = $item->name . ' | عدد ' . $item->quantity;
                }

                $digital_products_codes = implode(PHP_EOL, $code_list);
                $message_to_send = str_replace("{" . $variable . "}", $digital_products_codes, $message_to_send);
            } else if ($variable == "شركة الشحن" || $variable == "شركة الشحن") {
                $message_to_send = str_replace("{" . $variable . "}", $shipping_company, $message_to_send);
            } else if ($variable == "كود المنتج" || $variable == "كود المنتج") {
                $code_list = array();
                foreach ($data->data->items as $item) {
                    foreach ($item->codes as $code) {
                        $code_list[] = $item->name . ' :' . PHP_EOL . $code->code . PHP_EOL;
                    }
                }
                $digital_products_codes = implode(PHP_EOL, $code_list);
                $message_to_send = str_replace("{" . $variable . "}", $digital_products_codes, $message_to_send);
            } else if ($variable == "الملفات") {
                $file_list = array();
                foreach ($data->data->items as $item) {
                    foreach ($item->files as $file_url) {
                        $file_list[] = $item->name . PHP_EOL . $file_url->url . PHP_EOL;
                    }
                }
                $product_files = implode(PHP_EOL, $file_list);
                $message_to_send = str_replace("{" . $variable . "}", $product_files, $message_to_send);
            } else if ($variable == "زر التأكيد" || $variable == "زر التأكيد") {
                //$confirm_box = "_^confirm_تأكيد_^_ ".PHP_EOL." _^reject_إلغاء_^_";
                $confirm_box = "";
                $message_to_send = str_replace("{" . $variable . "}", 'للتأكيد ارسل كلمة نعم, وللإلغاء ارسل كلمة إلغاء', $message_to_send);
            }
        }
        $message_to_send = urlencode($message_to_send);

    }


    public function get_settings($data,$json)
    {
        $store_id = $data->merchant;

        $array =  $this->get_merchant_settings($store_id);
        if ($array === null) {
            $log = json_encode($json . PHP_EOL, JSON_UNESCAPED_UNICODE) . PHP_EOL;
            Storage::disk('local')->append('no_settings/order_status_no_settings.txt', $log);
            return "reconnect";
        }
        $get_instance_id =  $this->get_instance_id2($store_id, $conn);
        if ($get_instance_id == null || $array == null) {
            return "reconnect";
        } elseif (isset($array->data->settings->karzoun_custom_sending_check) && $array->data->settings->karzoun_custom_sending_check == 'karzoun_send_customized') {
            $instance_id = $get_instance_id[0][1] ?? $get_instance_id[0][0];
            //echo $get_instance_id[0][0];
        } else {
            $instance_id = $get_instance_id[0][0];
        }
        $api_url = $get_instance_id[1];
        $team_id = $get_instance_id[2];
        $conn->close();
    }


    public function get_merchant_settings($store_id)
    {
       $user_team =  Team::where("ids",$store_id)->first();
       if(!empty($user_team)){
        return $user_team->app_settings;
       }
       return null;
    }

    public function get_instance_id2($store_id, $conn)
    {
        $reconnect = 0;
        // $sql_get_team_id = "SELECT id FROM sp_team WHERE ids = $store_id";
        // $get_team_id = $conn->query($sql_get_team_id);
        // $team_id = $get_team_id->fetch_assoc()['id'];
        $team =  Team::where("ids",$store_id)->first();
        $team_id = $team->id;

        $instance = Instance::where("access_token",$store_id)->first();
        if($instance->api == 1){
            $instance_id[] = $instance->instance_id;  //$instances_data['instance_id'];
            $this->u_token = $instance->token; //$instances_data['token'];
            $api_url = 'karzoun.app';
            // echo 'test';
            $this->apitype = 1;
        }elseif($instance->api == 5){
            $instance_id[] = $instance->instance_id;  //$instances_data['instance_id'];
            $this->u_token = $instance->token; //$instances_data['token'];
            $api_url = 'wa.karzoun.app';
            $this->apitype = 5;
        }elseif($instance->api == 2){
            $instance_id[] = $instance->instance_id;  //$instances_data['instance_id'];
            $this->u_token = $instance->token; //$instances_data['token'];
            $api_url = 'api.ultramsg.com';
            $this->apitype = 2;
        }elseif($instance->api == 3){
            $instance_id[] = $instance->instance_id;  //$instances_data['instance_id'];
            $this->u_token = $instance->token; //$instances_data['token'];
            $api_url = 'api.karzoun.app/CloudApi.php';
                $this->apitype = 3;
        }elseif($instance->api == 4){
            $instance_id[] = $instance->instance_id;  //$instances_data['instance_id'];
            $this->u_token = $instance->token; //$instances_data['token'];
            $api_url = 'api.karzoun.app/CloudApi.php';
            $this->apitype = 4;
        }
        $query = mysqli_query($conn, "SELECT * FROM instances WHERE access_token = $store_id");
        if ($query->num_rows > 0) {
            $instances_data = mysqli_fetch_array($query);

            if ($instances_data['api'] == 1) {
                $instance_id[] = $instances_data['instance_id'];
                $this->u_token = $instances_data['token'];
                $api_url = 'karzoun.app';
                // echo 'test';
                $this->apitype = 1;
            } elseif ($instances_data['api'] == 5) {
                //$token = sp_Acount2::where("team_id",$team_id)->where("status",1)->first()->token ?? null;

                $instance_id[] = $instances_data['instance_id'];
                $this->u_token = $instances_data['token'];
                $api_url = 'wa.karzoun.app';

                // echo 'test';
                $this->apitype = 5;
            } elseif ($instances_data['api'] == 2) {
                $instance_id[] = $instances_data['instance_id'];
                $this->u_token = $instances_data['token'];
                //dd($this->u_token);
                $api_url = 'api.ultramsg.com';
                $this->apitype = 2;
            } elseif ($instances_data['api'] == 3) {
                $instance_id[] = $instances_data['instance_id'];
                //$this->wa_token = $instances_data['token'];
                //dd($this->u_token);
                $api_url = 'api.karzoun.app/CloudApi.php';
                $this->apitype = 3;
            } elseif ($instances_data['api'] == 4) {
                $instance_id[] = $instances_data['instance_id'];
                $api_url = 'api.karzoun.app/CloudApi.php';
                $this->apitype = 4;
            }
        } else {
            $this->apitype = 0;
            //   $query=mysqli_query($conn, "SELECT * FROM sp_accounts WHERE team_id = $team_id");
            //             if ($query->num_rows > 0) {

            //     while($data = mysqli_fetch_array($query)){
            //     $instance_id[] = $data["token"];
            //     }
            //     $api_url = 'karzoun.app';
            //     }else{
            //         echo 'reconnect please Team id : '.$team_id.' Merchant Id : '.$store_id.PHP_EOL;
            //         $reconnect = 1;

            //     }
            $token = sp_Acount2::where("team_id", $team_id)->where("status", 1)->first()->token ?? null;
            if ($token == null) {
                $result = null;
            }
            $instance_id[] = $token; //$instances_data['instance_id'];
            $api_url = 'wa.karzoun.app';
        }
        if ($reconnect == 1) {
            $result = null;
        } else {
            $result = array($instance_id, $api_url, $team_id);
        }
        return $result;
    }

}
