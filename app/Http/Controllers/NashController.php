<?php

namespace App\Http\Controllers;


use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class NashController extends Controller
{
    public function qiwiNash(Request $request){
        $command = $request->input('command');
        $txn_id = $request->input('txn_id');
        $account = $request->input('account');
        $sum = $request->input('sum');
        $result = '<?xml version="1.0" encoding="utf-8"?>';
        $result .= '<response>';
        do {
            if (!$command) {
                $result .= '<osmp_txn_id>'.$txn_id.'</osmp_txn_id>';
                $result .= '<result>5</result>';
                $result .= '<comment></comment>';
                break;
            }
            if (!$txn_id) {
                $result .= '<osmp_txn_id>'.$txn_id.'</osmp_txn_id>';
                $result .= '<result>5</result>';
                $result .= '<comment></comment>';
                break;
            }
            if (!$account) {
                $result .= '<osmp_txn_id>'.$txn_id.'</osmp_txn_id>';
                $result .= '<result>5</result>';
                $result .= '<comment></comment>';
                break;
            }
            if ($command == 'check') {
                $url = "https://nash-crm.kz/api/anticollect/check.php?iin=$account";

                $response = file_get_contents($url);
                $response = json_decode($response, true);

                if ($response['success'] == true) {
                    $result .= '<osmp_txn_id>'.$txn_id.'</osmp_txn_id>';
                    $result .= '<result>0</result>';
                    $result .= '<fields>';
                    $result .= '<field1 name="sum">'.$response['total'].'</field1>';
                    $result .= '</fields>';
                    $result .= '<comment></comment>';
                    break;

                } else {
                    $result .= '<osmp_txn_id>'.$txn_id.'</osmp_txn_id>';
                    $result .= '<result>5</result>';
                    $result .= '<comment></comment>';
                    break;
                }
                break;
            }
            if ($command == 'pay') {
                if (!$sum){
                    $result .= '<osmp_txn_id>'.$txn_id.'</osmp_txn_id>';
                    $result .= '<result>5</result>';
                    $result .= '<comment></comment>';
                    break;
                    break;
                }
                $url = "https://nash-crm.kz/api/anticollect/check.php?iin=$account";
                $response = file_get_contents($url);
                $response = json_decode($response, true);

                if ($response['success'] == true) {
                    $result .= '<osmp_txn_id>'.$txn_id.'</osmp_txn_id>';
                    $result .= '<result>0</result>';
                    $result .= '<comment></comment>';
                    $date = date('Y-m-d');
                    $pay = DB::table('payments')
                        ->where('payment_type',4)
                        ->where('iin',$account)
                        ->where('amount',$sum)
                        ->where('payment_date',date('Y-m-d H:i:s', strtotime($date)))
                        ->first();
                    if (!$pay){
                        $paymentID = DB::table('payments')->insertGetId([
                            'iin' => $account,
                            'amount' => $sum,
                            'payment_type' => 4,
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
                        $url = "https://nash-crm.kz/api/webhock/payments.php?iin=$account&amount=$sum&paymentID=$paymentID&source=$source";
                        $response = file_get_contents($url);
                    }
                    break;
                } else {
                    $result .= '<osmp_txn_id>'.$txn_id.'</osmp_txn_id>';
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

    public function astanaPlatNash(Request $request){
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
                $url = "https://nash-crm.kz/api/anticollect/check.php?iin=$number";
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
                $pay = DB::table('payments')
                    ->where('iin',$number)
                    ->where('amount',$amount)
                    ->where('payment_type',5)
                    ->first();
                if (!$pay){
                    $paymentID = DB::table('payments')->insertGetId([
                        'iin' => $number,
                        'amount' => $amount,
                        'payment_type' => 5,
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
                    $url = "https://nash-crm.kz/api/webhock/payments.php?iin=$number&amount=$amount&paymentID=$paymentID&source=$source";
                    $response = file_get_contents($url);

                    DB::commit();
                    $result .= '<code>0</code>';
                    $result .= '<authcode>135</authcode>';
                    $result .= '<message>Платеж принят</message>';
                }

//                $result['code'] = 0;
//                $result['authcode'] = 135;
//                $result['message'] = 'Платеж принят';
            }

        } while (false);
        $result .= '</response>';
        return response()->xml($result,200,['charset' => 'utf-8']);
    }

    public function payBoxNash(Request $request){
        $iin = $request->input('iin');
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
                'payment_type' => 6,
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
            $url = "https://nash-crm.kz/api/webhock/payments.php?iin=$iin&amount=$amount&paymentID=$paymentID&source=$source";
            $response = file_get_contents($url);

            DB::commit();
            $result['success'] = true;
        } while (false);
        return response()->json($result);
    }
}
