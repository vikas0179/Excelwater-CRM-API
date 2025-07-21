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
use PHPMailer\PHPMailer\PHPMailer;
use PhpOffice\PhpSpreadsheet\Reader\Exception;

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
            ->select('invoice.id', 'invoice.invoice_no', 'users.name as customer_name')
            ->orderBy("id", "DESC")
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
                $GetProductStockID = ProductStock::where('product_code', $product_code)->select('id', 'product_id')->first();
                $GetProductStockID->status = 1;
                $GetProductStockID->save();
                if (!empty($GetProductStockID->id)) {
                    if (isset($ProductCodeExit[$GetProductStockID->product_id])) {
                        $ProductCodeExit[$GetProductStockID->product_id]['product_stock_id'][] = $GetProductStockID->id;
                    } else {
                        $ProductCodeExit[$GetProductStockID->product_id]['product_stock_id'] = [$GetProductStockID->id];
                    }
                }
            }

            if (!empty($ProductCodeExit)) {
                $totalAmount = 0;
                foreach ($ProductCodeExit as $product) {
                    if (!empty($product['product_stock_id'])) {
                        $product_stock_id = implode(',', $product['product_stock_id']);
                        $productStocks = DB::table('product_stock as PS')
                            ->select('PS.id', 'PM.id as product_id', 'PM.product_name', 'PM.price')
                            ->join('product_master as PM', 'PM.id', '=', 'PS.product_id')
                            ->whereIn('PS.id', [$product_stock_id])
                            ->groupBy('PS.product_id')
                            ->first();

                        $qty = isset($product['product_stock_id']) ? count($product['product_stock_id']) : 1;
                        $price = isset($productStocks->price) ? $productStocks->price : 0;
                        $amount = ($price * $qty);
                        $totalAmount += $amount;
                        $InvoiceItemData = new InvoiceItem();
                        $InvoiceItemData->created_at = date("Y-m-d H:i:s");
                        $InvoiceItemData->updated_at = date("Y-m-d H:i:s");
                        $InvoiceItemData->invoice_id = $invoice_id;
                        $InvoiceItemData->product_id = $productStocks->product_id;
                        $InvoiceItemData->product_stock_id = $product_stock_id;
                        $InvoiceItemData->item = $productStocks->product_name;
                        $InvoiceItemData->qty = $qty;
                        $InvoiceItemData->rate = $price;
                        $InvoiceItemData->amount = $amount;
                        $InvoiceItemData->save();
                    }
                }

                $EditInvoice = Invoice::where('id', $invoice_id)->first();
                $in['sub_total'] = $totalAmount;
                $in['total_amount'] = $totalAmount;
                $EditInvoice->update($in);
            }
            return $this->response("Successfully Changed!", false);
        } else {
            return $this->response("Error!", true);
        }
    }


    public function MailSend()
    {
        $mail = new PHPMailer(true);
        try {
            $mail->SMTPDebug = 0;
            $mail->isSMTP();
            $mail->Host = "smtp.gmail.com";
            $mail->SMTPAuth = true;
            $mail->Username = "report.alert@aspiradiagnostics.com";
            $mail->Password = "gtrg jwaf qzcb doup";
            $mail->SMTPSecure = "tls";
            $mail->Port = 587;
            $mail->setFrom("report.alert@aspiradiagnostics.com", "Excel Water System");
            $mail->addAddress("chauhandharmesh8957@gmail.com");
            $mail->isHTML(true);
            $mail->Subject = "Testing";
            $mail->Body = "Mail Body";
            if (!$mail->send()) {
                return $this->response($mail->ErrorInfo, true);
            } else {
                return $this->response("Email has been sent.", false);
            }
        } catch (Exception $e) {
            return $this->response("Message could not be sent.", true);
        }
    }

    public function CheckInvoiceNo()
    {
        $LastRecordCheck = Invoice::whereDate('created_at', date('Y-m-d'))->orderBy("id", "DESC")->first();

        if (empty($LastRecordCheck)) {
            $GenerateInvoiceNumber = date("dmY") . "1";
        } else {
            $orderNumber = substr($LastRecordCheck->invoice_no, 7);
            $IncreseInvoiceNumber = str_pad(((int)$orderNumber + 1), strlen($orderNumber), '0', STR_PAD_LEFT);
            $GenerateInvoiceNumber = date("dmY") . $IncreseInvoiceNumber;
        }

        $data = array(
            'invoice_no' => $GenerateInvoiceNumber,
        );
        return $this->response("", false, $data);
    }
}
