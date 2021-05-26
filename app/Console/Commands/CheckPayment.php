<?php

namespace App\Console\Commands;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckPayment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:payment';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Every 10 minutes check payments from Qiwi AND Kassa24';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
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
                        'payment_type' => 'Kassa24',
                    ],
                ]);
                $result = $response->getBody()->getContents();
                $result = json_decode($result, true);
                if ($result['success'] == true) {
                    DB::table('payments')->where('id', $payment->id)->update(['status' => 1]);
                }
            } catch (BadResponseException $e) {
                info($e);
            }
        }
    }
}
