<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
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
        do {
            if (!$command) {
                $result['osmp_txn_id'] = $txn_id;
                $result['result'] = 5;
                $result['comment'] = '';
                break;
            }
            if (!$txn_id) {
                $result['osmp_txn_id'] = $txn_id;
                $result['result'] = 5;
                $result['comment'] = '';
                break;
            }
            if (!$account) {
                $result['osmp_txn_id'] = $txn_id;
                $result['result'] = 5;
                $result['comment'] = '';
                break;
            }
            if ($command == 'check') {
                $url = "https://icredit-crm.kz/api/webhock/check.php?iin=$account";
                $response = file_get_contents($url);
                $response = json_decode($response, true);

                if ($response['success'] == true) {
                    $result['osmp_txn_id'] = $txn_id;
                    $result['result'] = 0;
                    $result['comment'] = '';
                } else {
                    $result['osmp_txn_id'] = $txn_id;
                    $result['result'] = 5;
                    $result['comment'] = '';
                }
                break;
            }
            if ($command == 'pay') {
                $url = "https://icredit-crm.kz/api/webhock/check.php?iin=$account";
                $response = file_get_contents($url);
                $response = json_decode($response, true);

                if ($response['success'] == true) {
                    $result['osmp_txn_id'] = $txn_id;
                    $result['result'] = 0;
                    $result['comment'] = '';
                } else {
                    $result['osmp_txn_id'] = $txn_id;
                    $result['result'] = 5;
                    $result['comment'] = '';
                }
                break;
            }
        } while (false);
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
        do {
            if (!$action) {
                $result['code'] = 1;
                $result['message'] = 'Не передан action';
                break;
            }
            if ($action == 'check') {
                if (!$number) {
                    $result['code'] = 2;
                    $result['message'] = 'Не передан аккаунт';
                    break;
                }
                $url = "https://icredit-crm.kz/api/webhock/check.php?iin=$number";
                $response = file_get_contents($url);
                $response = json_decode($response, true);

                if ($response['success'] == true) {
                    $result['code'] = 0;
                    $result['message'] = 'Абонент существует';
                    break;
                } else {
                    $result['code'] = 2;
                    $result['message'] = 'Не найден аккаунт';
                    break;
                }
                break;
            }
            if ($action == 'payment') {
                if (!$number) {
                    $result['code'] = 2;
                    $result['message'] = 'Не передан аккаунт';
                    break;
                }
                if (!$amount) {
                    $result['code'] = 3;
                    $result['message'] = 'Не передан сумма';
                    break;
                }
                if (!$date) {
                    $result['code'] = 5;
                    $result['message'] = 'Не передан дата';
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
                    $result['code'] = 5;
                    $result['message'] = 'Попробуйте позже';
                    break;
                }
                $source = 'Kassa24';
                $url = "https://icredit-crm.kz/api/webhock/payment/payments.php?iin=$number&amount=$amount&paymentID=$paymentID&source=$source";
                $response = file_get_contents($url);

                DB::commit();
                $result['code'] = 0;
                $result['authcode'] = 135;
                $result['message'] = 'Платеж принят';
            }

        } while (false);
        return response()->xml($result,200,['charset' => 'utf-8']);
    }
}
