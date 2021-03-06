<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function payboxPayment(Request $request)
    {
        $iin = (int)$request->input('iin');
        $amount = abs($request->input('amount'));
        $phone = $request->input('phone');
        $result['success'] = false;
        do {
            if (!$iin) {
                $result['message'] = 'Не передан иин';
                break;
            }
            if (!$amount) {
                $result['message'] = 'Не передан сумма';
                break;
            }
            DB::beginTransaction();
            $paymentID = DB::table('payments')->insertGetId([
                'iin' => $iin,
                'amount' => $amount,
                'payment_type' => 1,
                'status' => 1,
                'phone' => $phone,
                'payment_date' => date('Y-m-d H:i:s'),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
            if (!$paymentID) {
                DB::rollBack();
                $result['message'] = 'Что то произошло не так';
                break;
            }
            $source = 'PayBox';
            $url = "https://icredit-crm.kz/api/webhock/payment/payments.php?iin=$iin&amount=$amount&paymentID=$paymentID&source=$source";
            $response = file_get_contents($url);

            DB::commit();
            $result['success'] = true;
        } while (false);
        return response()->json($result);
    }

    public function qiwiCheck(Request $request)
    {
        $command = $request->input('command');
        $txn_id = $request->input('txn_id');
        $account = $request->input('account');
        $sum = $request->input('sum');
        $result = '<?xml version="1.0" encoding="utf-8"?>';
        $result .= '<response>';
        do {
            if (!$command) {
                $result .= '<osmp_txt_id>'.$txn_id.'</osmp_txt_id>';
                $result .= '<result>5</result>';
                $result .= '<comment></comment>';
                break;
            }
            if (!$txn_id) {
                $result .= '<osmp_txt_id>'.$txn_id.'</osmp_txt_id>';
                $result .= '<result>5</result>';
                $result .= '<comment></comment>';
                break;
            }
            if (!$account) {
                $result .= '<osmp_txt_id>'.$txn_id.'</osmp_txt_id>';
                $result .= '<result>5</result>';
                $result .= '<comment></comment>';
                break;
            }
            if ($command == 'check') {
                $url = "https://icredit-crm.kz/api/webhock/check.php?iin=$account";
                $response = file_get_contents($url);
                $response = json_decode($response, true);

                if ($response['success'] == true) {
                    $result .= '<osmp_txt_id>'.$txn_id.'</osmp_txt_id>';
                    $result .= '<result>0</result>';
                    $result .= '<fields>';
                    $result .= '<field1 name="sum">'.$response['total'].'</field1>';
                    $result .= '</fields>';
                    $result .= '<comment></comment>';
                    break;

                } else {
                    $result .= '<osmp_txt_id>'.$txn_id.'</osmp_txt_id>';
                    $result .= '<result>5</result>';
                    $result .= '<comment></comment>';
                    break;
                }
                break;
            }
            if ($command == 'pay') {
                if (!$sum){
                    $result .= '<osmp_txt_id>'.$txn_id.'</osmp_txt_id>';
                    $result .= '<result>5</result>';
                    $result .= '<comment></comment>';
                    break;
                    break;
                }
                $url = "https://icredit-crm.kz/api/webhock/check.php?iin=$account";
                $response = file_get_contents($url);
                $response = json_decode($response, true);

                if ($response['success'] == true) {
                    $result .= '<osmp_txt_id>'.$txn_id.'</osmp_txt_id>';
                    $result .= '<result>0</result>';
                    $result .= '<comment></comment>';
                    $date = date('Y-m-d');
                    $paymentID = DB::table('payments')->insertGetId([
                        'iin' => $account,
                        'amount' => $sum,
                        'payment_type' => 2,
                        'status' => 1,
                        'payment_date' => date('Y-m-d H:i:s', strtotime($date)),
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ]);
                    if (!$paymentID) {
                        $result .= '<code>5</code>';
                        $result .= '<message>Попробуйте позже</message>';
                        break;
                    }
                    $source = 'Qiwi';
                    $url = "https://icredit-crm.kz/api/webhock/payment/payments.php?iin=$account&amount=$sum&paymentID=$paymentID&source=$source";
                    $response = file_get_contents($url);
                    break;
                } else {
                    $result .= '<osmp_txt_id>'.$txn_id.'</osmp_txt_id>';
                    $result .= '<result>5</result>';
                    $result .= '<comment></comment>';
                    break;
                }
                break;
            }
        } while (false);
        $result .= '</response>';
        return response()->xml($result);
    }

    public function astanaPlat(Request $request)
    {
        $action = $request->input('action');
        $number = $request->input('number');
        $amount = $request->input('amount');
        $receipt = $request->input('receipt');
        $date = $request->input('date');
        $type = $request->input('type');
        $result = '<?xml version="1.0" encoding="utf-8"?>';
        $result .= '<response>';
        do {
            if (!$action) {
                $result .= '<code>1</code>';
                $result .= '<message>Не передан action</message>';
                break;
            }
            if ($action == 'check') {
                if (!$number) {
                    $result .= '<code>2</code>';
                    $result .= '<message>Не передан аккаунт</message>';
                    break;
                }
                $url = "https://icredit-crm.kz/api/webhock/check.php?iin=$number";
                $response = file_get_contents($url);
                $response = json_decode($response, true);

                if ($response['success'] == true) {
                    $result .= '<code>0</code>';
                    $result .= '<message>Абонент существует</message>';
                    break;
                } else {
                    $result .= '<code>0</code>';
                    $result .= '<message>Не найден аккаунт</message>';
                    break;
                }
                break;
            }
            if ($action == 'payment') {
                if (!$number) {
                    $result .= '<code>2</code>';
                    $result .= '<message>Не передан аккаунт</message>';
                    break;
                }
                if (!$amount) {
                    $result .= '<code>3</code>';
                    $result .= '<message>Не передан сумма</message>';
                    break;
                }
                if (!$date) {
                    $result .= '<code>5</code>';
                    $result .= '<message>Не передан дата</message>';
                    break;
                }

                DB::beginTransaction();

                $paymentID = DB::table('payments')->insertGetId([
                    'iin' => $number,
                    'amount' => $amount,
                    'payment_type' => 3,
                    'status' => 1,
                    'payment_date' => date('Y-m-d H:i:s', strtotime($date)),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
                if (!$paymentID) {
                    $result .= '<code>5</code>';
                    $result .= '<message>Попробуйте позже</message>';
                    break;
                }
                $source = 'Kassa24';
                $url = "https://icredit-crm.kz/api/webhock/payment/payments.php?iin=$number&amount=$amount&paymentID=$paymentID&source=$source";
                $response = file_get_contents($url);

                DB::commit();
                $result .= '<code>0</code>';
                $result .= '<authcode>135</authcode>';
                $result .= '<message>Платеж принят</message>';
//                $result['code'] = 0;
//                $result['authcode'] = 135;
//                $result['message'] = 'Платеж принят';
            }

        } while (false);
        $result .= '</response>';
        return response()->xml($result,200,['charset' => 'utf-8']);
        //return $result;
    }

    public function testPayment(){
        $payments = DB::table('payments')->where('payment_type', 3)->where('status', 0)->get();
        foreach ($payments as $payment) {
            $http = new Client();
            try {
                $response = $http->get('https://icredit-crm.kz/api/webhock/payment/payments.php', [
                    'query' => [
                        'iin' => $payment->iin,
                        'amount' => $payment->amount,
                        'paymentID' => $payment->id,
                        'payment_date' => $payment->created_at,
                        'source' => 'Kassa24',
                    ],
                ]);
                var_dump($response);
                $result = $response->getBody()->getContents();
                $result = json_decode($result, true);
                var_dump($result);
                if ($result['success'] == true) {
                    DB::table('payments')->where('id', $payment->id)->update(['status' => 1]);
                }
            } catch (BadResponseException $e) {
                info($e);
            }
        }
    }


}
