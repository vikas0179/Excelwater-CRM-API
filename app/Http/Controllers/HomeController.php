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
use Carbon\Carbon;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class HomeController extends Controller
{

    public function GetInvoiceDetail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'invoice_no' => 'required',
        ], [
            'invoice_no.required' => 'Invoice Number is required',
        ]);

        if ($validator->fails()) {
            return $this->response($validator->errors()->first(), true);
        }
        $InvoiceData = Invoice::where('invoice_no', $request->invoice_no)->select('id', 'created_at', 'bill_to', 'ship_to', 'invoice_no', 'invoice_date', 'total_amount')->first();
        if (!empty($InvoiceData)) {
            // $InvoiceUrl = "https://crm.excelwater.ca/manage_invoice/invoice_detail/" . $InvoiceData->invoice_no;
            // $qrCodeName = 'qrcode_' . $InvoiceData->invoice_no . '.svg';
            // $qrCodeDir = public_path('storage/qrcode/');
            // $qrCodePath = $qrCodeDir . $qrCodeName;
            // if (!file_exists($qrCodeDir)) {
            //     mkdir($qrCodeDir, 0777, true);
            // }
            // file_put_contents($qrCodePath, $svg);
            // $InvoiceData->qr_code_path = 'storage/qrcode/' . $qrCodeName;
            $InvoiceItemData = InvoiceItem::where('invoice_id', $InvoiceData->id)->select('item', 'desc', 'qty', 'rate', 'amount')->get();
        }

        $data = array(
            'invoice' => isset($InvoiceData) && (!empty($InvoiceData)) ? $InvoiceData : NULL,
            'items' => isset($InvoiceItemData) && (!empty($InvoiceItemData)) ? $InvoiceItemData : NULL,
        );

        if (!empty($InvoiceData)) {
            return $this->response("", false, $data);
        } else {
            return $this->response("", true);
        }
    }
}
