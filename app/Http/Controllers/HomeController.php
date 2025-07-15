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

        $ProductStockData = ProductStock::where('product_code', $request->product_code)->select('id', 'product_id', 'product_code')->where('status', 1)->first();
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
            return $this->response("Not Found Data", true);
        }

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


    // App
    public function DraftInvoice()
    {
        $ProductMasterList = Invoice::leftJoin('users', 'users.id', '=', 'invoice.customer_id')
            // ->where('invoice.transaction_type', 1)
            ->select('invoice.id', 'invoice.invoice_no', 'users.name as customer_name')
            ->get();

        if (!empty($ProductMasterList) && count($ProductMasterList) > 0) {
            return $this->response("", false, $ProductMasterList);
        } else {
            return $this->response("Invoice Not Found", true);
        }
    }

    public function SaveInvoice(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'invoice_id' => 'required',
            'product_code' => 'required|array',
        ], [
            'invoice_id.required' => 'Please Enter Invoice ID',
            'product_code.required' => 'Please Enter Product Code',
        ]);

        if ($validator->fails()) {
            return $this->response($validator->errors()->first(), true);
        }

        $product_codes = $request->product_code;
        $invoice_id = $request->invoice_id;

        $ProductCodeExit = [];
        if (!empty($product_codes)) {
            foreach ($product_codes as $product_code) {
                $GetProductStockID = ProductStock::where('product_code', $product_code)->select('id')->first();
                if (!empty($GetProductStockID->id)) {
                    if (isset($ProductCodeExit[$GetProductStockID->id])) {
                        $ProductCodeExit[$GetProductStockID->id] += 1;
                    } else {
                        $ProductCodeExit[$GetProductStockID->id] = 1;
                    }
                }
            }

            $InvoiceItemList = InvoiceItem::where('invoice_id', $invoice_id)->select('id', 'qty', 'product_stock_id')->get();
            if (!empty($InvoiceItemList) && count($InvoiceItemList) > 0) {
                foreach ($InvoiceItemList as $key => $item) {
                    $product_stock_ids = explode(',', $item['product_stock_id']);
                    $total_qty = 0;
                    foreach ($product_stock_ids as $pid) {
                        $pid = (int)$pid;
                        if (isset($ProductCodeExit[$pid])) {
                            $total_qty += $ProductCodeExit[$pid];
                        }
                    }
                    $item->qty = ($item->qty + $total_qty);
                    $item->save();
                }
            }

            return $this->response("Successfully Changed!", false);
        } else {
            return $this->response("Error!", true);
        }
    }
}
