<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function payboxPayment(Request $request){
        $iin = (int)$request->input('iin');
        $amount = abs($request->input('amount'));
        $phone = $request->input('phone');
        $result['success']  = false;
        do{
            if (!$iin){
                $result['message'] = 'Не передан иин';
                break;
            }
            if (!$amount){
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
            if (!$paymentID){
                DB::rollBack();
                $result['message'] = 'Что то произошло не так';
                break;
            }
            $source = 'PayBox';
            $url = "https://icredit-crm.kz/api/webhock/payment/payments.php?iin=$iin&amount=$amount&paymentID=$paymentID&source=$source";
            $response = file_get_contents($url);
            var_dump($response);
            DB::commit();
            $result['success'] = true;
        }while(false);
        return response()->json($result);
    }
}
