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
use Carbon\Carbon;

class CronController extends Controller
{
	public function send_emails_after_lead_dropped()
	{
		$date = date("Y-m-d");
		$leads = Leads::where("status", 2)->whereIn("marketing_email_status", [-1, 0, 1])
			->whereRaw("(next_marketing_email_date IS NULL OR next_marketing_email_date='$date')")
			->whereDate("converted_date", ">=", "2025-01-01")
			->orderBy("id", "asc")
			->get();
		foreach ($leads as $val) {

			$token = base64_encode("kw{$val->id}");
			if ($val->marketing_email_status == -1) {

				if(!empty($val->email) && filter_var($val->email, FILTER_VALIDATE_EMAIL)){
					Mail::send('emails.marketing.dropped_lead_first', ['token' => $token, "name" => $val->name], function ($message) use ($val) {
						$message->to($val->email);
						if(isset($val->is_old_leads) && $val->is_old_leads == 0){
							$message->bcc('service@kentwater.ca');
						}
						$message->subject("Your Home Deserves the Best – See the Difference with Kent Water");
					});
				}

				$next_marketing_email_date = Carbon::parse($val->converted_date)->addDays(7)->format('Y-m-d');
				// $next_marketing_email_date = date("Y-m-d", strtotime($converted_date . " +7 days"));
				Leads::where("id", $val->id)->update([
					"marketing_email_status" => 0,
					"next_marketing_email_date" => $next_marketing_email_date
				]);
			} else if ($val->marketing_email_status == 0) {
				if(!empty($val->email) && filter_var($val->email, FILTER_VALIDATE_EMAIL)){
					Mail::send('emails.marketing.dropped_lead_second', ['token' => $token, "name" => $val->name], function ($message) use ($val) {
						$message->to($val->email);
						if(isset($val->is_old_leads) && $val->is_old_leads == 0){
							$message->bcc('service@kentwater.ca');
						}
						$message->subject("No Payments & No Interest for Up to12 Months – Find Your Perfect Plan");
					});
				}

				$next_marketing_email_date = Carbon::parse($val->converted_date)->addDays(15)->format('Y-m-d');
				Leads::where("id", $val->id)->update([
					"marketing_email_status" => 1,
					"next_marketing_email_date" => $next_marketing_email_date
				]);
			} else if ($val->marketing_email_status == 1) {
				if(!empty($val->email) && filter_var($val->email, FILTER_VALIDATE_EMAIL)){
					Mail::send('emails.marketing.dropped_lead_third', ['token' => $token, "name" => $val->name], function ($message) use ($val) {
						$message->to($val->email);
						if(isset($val->is_old_leads) && $val->is_old_leads == 0){
							$message->bcc('service@kentwater.ca');
						}
						$message->subject("Why Families Trust Kent Water – Watch Their Stories");
					});
				}

				Leads::where("id", $val->id)->update([
					"marketing_email_status" => 2
				]);
			}
		}
		echo "marketing mail send successfully!";
	}

	public function send_emails_after_lead_dropped_interest(Request $request)
	{

		$validator = Validator::make($request->all(), [
			't' => 'required',
		], [
			't.required' => 'Please send required parameters',
		]);

		if ($validator->fails()) {
			echo '<h1>Access Denied</h1>';
			exit;
		}

		$id = base64_decode($request->t);
		$id = str_replace("kw", "", $id);

		$lead = Leads::where("id", $id)->first();

		if ($lead && $lead->status == 2) {

			$admin = Admin::where("role", 0)->first();

			Mail::send('emails.marketing.dropped_lead_admin_sp', ["name" => $admin->name, "client_name" => $lead->name, "phone" => $lead->phone, "email_address" => $lead->email], function ($message) use ($admin, $lead) {
				$message->to($admin->email);
				$message->subject("Interest shown by {$lead->name}");
			});
			$admin_id = $admin->id;
			$admin = Admin::where("id", $lead->assigned_to)->first();

			if ($admin) {
				Mail::send('emails.marketing.dropped_lead_admin_sp', ["name" => $admin->name, "client_name" => $lead->name, "phone" => $lead->phone, "email_address" => $lead->email], function ($message) use ($admin, $lead) {
					$message->to($admin->email);
					$message->subject("Interest shown by {$lead->name}");
				});

				$admin_id = $admin->id;
			}
			LeadsHistory::insert(array(
				"lead_id" => $lead->id,
				"user_id" => $admin_id,
				"assigned_by" => -1,
				"message" => "<strong>{$lead->name}</strong> has shown interest in the product. Please call/email him on {$lead->phone} / {$lead->email} to discuss.",
			));

			Leads::where("id", $id)->increment("marketing_email_status", 1);

			$status = 0;

			Leads::where("id", $id)->update([
				"status" => 1,
				"marketing_email_status" => -1,
			]);

			echo "<h2>Thank you...our sales team will be in touch with you soon!</h2>";
		}
	}
}
