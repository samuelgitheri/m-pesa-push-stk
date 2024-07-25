<?php
/*
* FOR THE HTTP REQUESTS TO WORK PROGMATICALY IN LARAVEL, ENSURE TO INSTALL THE GUZZLE LIBLARY. YOU CAN DO THAT THROUGH THE COMPOSER USING THIS COMMAND "composer require guzzlehttp/guzzle" THAT SHOULD ALLOW YOU PERFOM CURl FOR SAFARICOM MPESA END POINTS
*/
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class MpesaController extends Controller
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    // Method to get M-Pesa access token
    protected function getAccessToken()
    {
        $consumerKey = 'YOUR_CONSUMER_KEY';
        $consumerSecret = 'YOUR_CONSUMER_SECRET';
        $credentials = base64_encode($consumerKey . ':' . $consumerSecret);

        try {
            // Make HTTP request to generate access token
            $response = $this->client->request('GET', 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials', [
                'headers' => [
                    'Authorization' => 'Basic ' . $credentials,
                    'Content-Type' => 'application/json',
                ],
            ]);

            $accessToken = json_decode($response->getBody())->access_token;
            return $accessToken;

        } catch (\Exception $e) {
            // Log error for debugging (optional)
            Log::error('Failed to get M-Pesa access token: ' . $e->getMessage());
            throw new \Exception('Failed to get M-Pesa access token');
        }
    }

    // Method to generate password for STK push
    protected function generatePassword()
    {
        $timestamp = now()->format('YmdHis');
        $shortcode = 'YOUR_SHORTCODE';
        $passkey = 'YOUR_PASSKEY'; // Obtain your passkey from Safaricom
        return base64_encode($shortcode . $passkey . $timestamp);
    }

    public function push_stk(Request $request)
    {
        try {
            // Get access token
            $accessToken = $this->getAccessToken();

            // Set M-Pesa API endpoint (sandbox or production)
            $endpoint = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';

            // Prepare STK push request parameters
            $data = [
                'BusinessShortCode' => 'YOUR_SHORTCODE',
                'Password' => $this->generatePassword(),
                'Timestamp' => now()->format('YmdHis'),
                'TransactionType' => 'CustomerPayBillOnline',
                'Amount' => 'AMOUNT',
                'PartyA' => 'PHONE_NUMBER',
                'PartyB' => 'YOUR_SHORTCODE',
                'PhoneNumber' => 'PHONE_NUMBER',
                'CallBackURL' => route('m_pesa_push_stk_call_back'),
                'AccountReference' => 'ACCOUNT_REF',
                'TransactionDesc' => 'TRANSACTION_DESC',
            ];

            // Make POST request to M-Pesa API
            $response = $this->client->request('POST', $endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => $data,
            ]);

            // Handle M-Pesa API response
            $responseBody = json_decode($response->getBody());

            // Log the response for debugging (optional)
            Log::info('STK Push Response: ' . json_encode($responseBody));

            // Process response and return appropriate view or redirect
            return response()->json($responseBody);

        } catch (\Exception $e) {
            // Log the error for debugging (optional)
            Log::error('STK Push Error: ' . $e->getMessage());

            // Handle exceptions
            return response()->json(['error' => 'Failed to initiate STK push.'], 500);
        }
    }

    public function call_back(Request $request)
    {
        // Log the callback data for debugging (optional)
        Log::info('STK Callback Data: ' . json_encode($request->all()));

        // Process M-Pesa callback here
        // Validate the callback data as per M-Pesa documentation
        // Example validation (adjust as per M-Pesa requirements)
        if ($request->input('ResultCode') == 0) {
            // Payment successful, update your database or perform necessary actions
            // Example: Save transaction details to database
            // $transaction = Transaction::create([
            //     'transaction_id' => $request->input('CheckoutRequestID'),
            //     'amount' => $request->input('Amount'),
            //     'phone_number' => $request->input('PhoneNumber'),
            //     'status' => 'completed',
            // ]);
            // return response()->json(['message' => 'Payment successful']);
        } else {
            // Payment failed or other error occurred
            // Handle accordingly
            // return response()->json(['error' => 'Payment failed']);
        }
    }
}