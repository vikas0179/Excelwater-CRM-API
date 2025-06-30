<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\StaticData;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use DB;
use Mail;
use Storage;
use App\Models\Admin;
use App\Models\User;
use App\Models\Leads;
use App\Models\LeadsHistory;
use App\Models\SalesChannels;
use App\Models\Product;
use App\Models\AdsSpends;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\ProductStock;
use Carbon\Carbon;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class HomeController extends Controller
{

    public function GetInvoiceDetailOld(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'invoice_no' => 'required',
        ], [
            'invoice_no.required' => 'Invoice Number is required',
        ]);

        if ($validator->fails()) {
            return $this->response($validator->errors()->first(), true);
        }

        // Fetch invoice data including customer_id
        $InvoiceData = Invoice::where('invoice_no', $request->invoice_no)
            ->select('id', 'created_at', 'bill_to', 'ship_to', 'invoice_no', 'invoice_date', 'total_amount', 'customer_id')
            ->first();

        if (!empty($InvoiceData)) {
            // Fetch the customer name from User model
            $userData = User::where('id', $InvoiceData->customer_id)->first();
            $InvoiceData->customer_name = $userData ? $userData->name : '';

            // Fetch invoice items
            $InvoiceItemData = InvoiceItem::where('invoice_id', $InvoiceData->id)
                ->select('item', 'desc', 'qty', 'rate', 'amount')
                ->get();
        }

        $data = [
            'invoice' => isset($InvoiceData) && (!empty($InvoiceData)) ? $InvoiceData : null,
            'items'   => isset($InvoiceItemData) && (!empty($InvoiceItemData)) ? $InvoiceItemData : null,
        ];

        if (!empty($InvoiceData)) {
            return $this->response("", false, $data);
        } else {
            return $this->response("", true);
        }
    }


    public function GetInvoiceDetail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_code' => 'required',
        ], [
            'product_code.required' => 'Product Code is required',
        ]);

        if ($validator->fails()) {
            return $this->response($validator->errors()->first(), true);
        }

        $ProductStockData = ProductStock::where('product_code', $request->product_code)->select('id', 'product_id', 'product_code')->first();
        if (!empty($ProductStockData)) {
            $InvoiceItemData = InvoiceItem::where('product_stock_id', $ProductStockData->id)->select('item', 'desc', 'qty', 'rate', 'amount', 'invoice_id')->first();
            $InvoiceData = Invoice::where('id', $InvoiceItemData->invoice_id)->select('id', 'created_at', 'bill_to', 'ship_to', 'invoice_no', 'invoice_date', 'total_amount', 'customer_id')->first();
            if (!empty($InvoiceData)) {
                $userData = User::where('id', $InvoiceData->customer_id)->first();
                $InvoiceData->customer_name = isset($userData->name) ? $userData->name : '';
                $InvoiceData->customer_email = isset($userData->email) ? $userData->email : '';
                $InvoiceData->customer_mobile = isset($userData->mobile) ? $userData->mobile : '';
                $InvoiceData->product_code = isset($ProductStockData->product_code) ? $ProductStockData->product_code : '';
            }

            $data = [
                'invoice' => isset($InvoiceData) && (!empty($InvoiceData)) ? $InvoiceData : null,
                'items'   => isset($InvoiceItemData) && (!empty($InvoiceItemData)) ? $InvoiceItemData : null,
            ];
            return $this->response("", false, $data);
        } else {
            return $this->response("", true);
        }

        // Fetch invoice data including customer_id
        $InvoiceData = Invoice::where('invoice_no', $request->invoice_no)->select('id', 'created_at', 'bill_to', 'ship_to', 'invoice_no', 'invoice_date', 'total_amount', 'customer_id')->first();

        if (!empty($InvoiceData)) {
            // Fetch the customer name from User model
            $userData = User::where('id', $InvoiceData->customer_id)->first();
            $InvoiceData->customer_name = $userData ? $userData->name : '';

            // Fetch invoice items
            $InvoiceItemData = InvoiceItem::where('invoice_id', $InvoiceData->id)
                ->select('item', 'desc', 'qty', 'rate', 'amount')
                ->get();
        }

        $data = [
            'invoice' => isset($InvoiceData) && (!empty($InvoiceData)) ? $InvoiceData : null,
            'items'   => isset($InvoiceItemData) && (!empty($InvoiceItemData)) ? $InvoiceItemData : null,
        ];

        if (!empty($InvoiceData)) {
            return $this->response("", false, $data);
        } else {
            return $this->response("", true);
        }
    }
}
