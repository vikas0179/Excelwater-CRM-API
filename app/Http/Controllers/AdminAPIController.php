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
use App\Models\LeadsDuplicateTracking;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductMaster;
use App\Models\ProductStock;
use App\Models\SpareParts;
use App\Models\Stocks;
use App\Models\Supplier;
use App\Models\Transaction;
use Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Reader\Exception;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Arr;
use League\CommonMark\Node\Query\OrExpr;
use PHPMailer\PHPMailer\PHPMailer;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Helper;
use App\Models\ActivityLog;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class AdminAPIController extends Controller
{

	protected $auth;

	private $problems_with_water = array(
		"Drinking Water Has A Bad Taste/Odor",
		"Clothes Appear Unclean And Stiff",
		"Dishes Have A Stingy Smell/Spots",
		"Brown Stains On Bathtub/Toilet",
		"Hair Feels Dull And Your Skin Feels Dry/Itchy"
	);

	private $water_type = array(
		"City Water",
		"Well Water",
		"Not Sure"
	);

	private $interest_in = array(
		"Whole House Water Softener",
		"Chlorine/Chloramine Removal System",
		"Iron & Sulphur Removal System",
		"Under Sink Drinking Water System",
		"Detergent Free Laundry System",
		"Well Water Softeners",
		"Whole Home Water Filtration",
		"Well Water Filtration",
		"Reverse Osmosis Systems"
	);

	private $interest_in_wts = array(
		"Kent DropPro Connect Water Softener",
		"Kent 5.0 Filter + Softener Duplex Tanks System",
		"Kent Iron & Sulfur Removal Water Filter",
		"Kent 4.0 High-Efficiency Water Softening System",
		"Kent Salt-Free Water Softener",
		"Kent 4.0 Twin Demand Water Softener",
		"Whole Home Water Filtration for City Water",
		"Well Water Filtration",
		"Reverse Osmosis Systems",
		"Kent Chlorine/Chloramine Removal Water Filter",
		"NL 7 Reverse Osmosis Water Filtration System",
		"UF 5 Water Filtration System",
		"Kent Excell Plus",
		"Kent Ultraviolet Disinfection System",
		"Kent Automatic Ceramic Disc Backwater Pre-Filter",
		"Kent Reverse Osmosis Water Filtration System",
		"Essentials (Kent 4.0 High-Efficiency Water Softener + NL 7 Reverse Osmosis Water Filtration System)",
		"Plus (Kent 5.0 Softener + Chlorine/Chloramine Filter Duplex Tanks System)",
		"Premium (Kent 5.0 Softener + Chlorine/Chloramine Filter Duplex Tanks System + NL 7 Reverse Osmosis Water Filtration System)",
	);

	private $cust_type = array(
		"New",
		"Existing",
	);

	public function __construct()
	{
		\Config::set('auth.providers.users.model', Admin::class);

		//date_default_timezone_set(getenv("APP_TIMEZONE", "UTC"));

		$this->user = auth("api")->user();

		$this->helper = new Helper();

		$this->middleware('jwt.verify', ['except' => ['login', 'forgot_password', 'verify_token', 'reset_password', 'get_data', 'get_static_data', 'lead_save']]);
	}

	public function get_data(Request $request)
	{

		//StaticData::load_book_water_test_data($request);
		//StaticData::load_contact_data($request);
		//StaticData::load_lp_data($request);


		$data = [];
		$data["roles"] = array(
			["id" => 1, "name" => "Sub Admin"],
			["id" => 2, "name" => "Sales"]
		);

		$data["is_logged_in"] = $this->user ? 1 : 0;
		$data["role"] = $this->user ? $this->user->role : -1;
		$data["full_name"] = $this->user ? $this->user->name : "";
		$data["email"] = $this->user ? $this->user->email : "";

		return $this->response("", false, $data);
	}

	public function get_static_data(Request $request)
	{

		$data = [];
		$data["problems_with_water"] = $this->problems_with_water;
		$data["water_type"] = $this->water_type;
		$data["interest_in"] = $this->interest_in;
		$data["cust_type"] = $this->cust_type;

		return $this->response("", false, $data);
	}

	/************ @AUTH SECTION ***************/
	public function login(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'email' => 'required|email',
			'password' => 'required'
		], [
			'email.required' => 'Please enter email',
			'email.email' => 'Please enter valid email address',
			'password.required' => 'Please enter password',
		]);

		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}

		$result = $this->generate_token($request);

		if ($result === false)
			return $this->response("Entered email address or password incorrect.", true);

		return $this->response("You are logged in successfully.", false, $result);
	}

	private function generate_token($request)
	{

		$credentials = $request->only('email', 'password');

		$token = "";
		try {
			$token = JWTAuth::attempt($credentials);

			if (!$token) {
				return false;
			}
		} catch (JWTException $e) {
			return false;
		}

		$user =  auth()->user();

		if ($user->role == 0) {
			$user_type  = "Super Admin";
		} elseif ($user->role == 1) {
			$user_type  = "Admin";
		} else {
			$user_type  = "Sales";
		}

		return array(
			"token" => $token,
			"name" => "{$user->name}",
			"email" => $user->email,
			"user_role" => $user->role,
			"user_type" => $user_type,
		);
	}

	public function logout()
	{
		JWTAuth::invalidate(JWTAuth::getToken());
		return $this->response("You are logged out successfully.");
	}

	public function forgot_password(Request $request)
	{

		$validator = Validator::make($request->all(), [
			'email' => 'required|email|exists:admin',
		], [
			'email.required' => 'Please enter email',
			'email.email' => 'Please enter valid email address',
			'email.exists' => 'Entered email address is not found',
		]);

		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}

		$user = Admin::where('email', $request->email)->first();

		$token = Str::random(64);

		$has_token = DB::table('password_reset_tokens')->where("email", $request->email)->first();

		if ($has_token) {
			DB::table('password_reset_tokens')->where("email", $request->email)->update([
				'token' => $token
			]);
		} else {
			DB::table('password_reset_tokens')->insert([
				'email' => $request->email,
				'token' => $token
			]);
		}

		$username = $user->full_name;

		/*return view('emails.admin-reset-password-link', compact('token',"username"));*/
		Mail::send('emails.admin-reset-password-link', ['token' => $token, "username" => $user->full_name], function ($message) use ($request) {
			$message->to($request->email);
			$message->subject('Reset Password');
		});

		return $this->response("We have mailed your password reset link.");
	}

	public function verify_token(Request $request)
	{

		$validator = Validator::make($request->all(), [
			'token' => 'required',
		], [
			'token.required' => 'Please send required parameters',
		]);

		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}

		$token = DB::table('password_reset_tokens')->where('token', $request->token)->first();

		if ($token) {
			return $this->response("Password reset link is valid", false);
		} else {
			return $this->response("Password reset link is expired or invalid, Please try again...", true);
		}
	}

	public function reset_password(Request $request)
	{

		$validator = Validator::make($request->all(), [
			'token' => 'required',
			'password' => 'required|min:8|regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{6,}$/ ',
			'confirm_password' => 'required|same:password',

		], [
			'token.required' => 'Token is required',
			'password.required' => 'Please enter password',
			'password.min' => 'Please enter minimum 8 character in password',
			'password.regex' => 'Your password must be more than 8 characters long, should contain at-least 1 Uppercase, 1 Lowercase, 1 Numeric and 1 special character.',
			'confirm_password.required' => 'Please enter confirm password',
			'confirm_password.same' => 'Entered new password and confirm new password not matched',
		]);

		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}

		$user = DB::table('password_reset_tokens')->where('token', $request->token)->first();

		if (!$user) {
			return $this->response("Password reset link is expired.", true);
		}

		Admin::where('email', $user->email)->update(['password' => Hash::make($request->password)]);

		DB::table('password_reset_tokens')->where('email', $user->email)->delete();

		return $this->response("Password reset successfully");
	}

	public function get_profile()
	{

		$id = auth()->user()->id;

		$user =	Admin::select("*")
			->where("id", $id)
			->first();

		if (!$user) {
			return $this->response("User not found", true);
		}
		return $this->response("", false, $user);
	}

	public function update_profile(Request $request)
	{

		$user = auth()->user();

		$validator = Validator::make($request->all(), [
			'name' => 'required',
			'email' => 'required|email|unique:users,email,' . $user->id,
		], [
			'name.required' => 'Please enter name',
			'email.required' => 'Please enter email',
			'email.email' => 'Please enter valid email address',
			'email.exists' => 'Entered email address is not found',
		]);

		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}

		$user = Admin::where('id', $user->id)->first();

		if (!$user) {
			return $this->response("User not found.", true);
		}

		$user->name = $request->name;
		$user->email = $request->email;

		$user->save();

		return $this->response("Your profile updated successfully.");
	}

	public function change_password(Request $request)
	{

		$validator = Validator::make($request->all(), [
			'current_password' => array(
				'required',
				function ($attribute, $value, $fail) {
					if (!Hash::check($value, auth::user()->password)) {
						$fail('Entered current password is wrong');
					}
				}
			),
			'new_password' => 'required|min:8||regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{6,}$/',
			'new_confirm_password' => 'required|same:new_password'
		], [
			'current_password.required' => 'Please enter current password',
			'new_password.required' => 'Please enter new password',
			'new_password.min' => 'Please enter minimum 8 character in password',
			'new_password.regex' => 'Your password must be more than 8 characters long, should contain at-least 1 Uppercase, 1 Lowercase, 1 Numeric and 1 special character.',
			'new_confirm_password.required' => 'Please enter confirm password',
			'new_confirm_password.same' => 'Entered new password and confirm new password not matched'
		]);

		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}

		Admin::find(auth()->user()->id)->update(['password' => Hash::make($request->new_password)]);

		return $this->response("Password changed sucessfully.");
	}

	/************ END AUTH SECTION ***************/

	/************ SUB ADMIN SECTION ***************/

	public function add_sub_user(Request $request)
	{

		$validator = Validator::make($request->all(), [
			'name' => 'required',
			'email' => 'required|email|unique:admin',
			'role' => 'required',
			'password' => 'required',
		], [
			'name.required' => 'Please enter name',
			'email.required' => 'Please enter email',
			'email.email' => 'Please enter valid email address',
			'email.exists' => 'This email is already available, Please use other one.',
			'role.required' => 'Please select role',
			'password.required' => 'Please enter password',
		]);

		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}

		$pwd = $request->password;

		Admin::create(array(
			"name" => $request->name,
			"email" => $request->email,
			"password" => Hash::make($pwd),
			"role" => $request->role,
			"status" => 1
		));

		Mail::send('emails.create-users', ['name' => $request->name, 'email' => $request->email, "pwd" => $pwd], function ($message) use ($request) {
			$message->to($request->email);
			$message->subject("New Account Created");
		});

		return $this->response("Sales User added successfully.");
	}

	public function update_sub_user(Request $request)
	{

		$validator = Validator::make($request->all(), [
			'id' => 'required',
			'name' => 'required',
			'email' => 'required|email|unique:admin,email,' . $request->id,
			'role' => 'required',
		], [
			'id.required' => 'Please enter id',
			'name.required' => 'Please enter name',
			'email.required' => 'Please enter email',
			'email.email' => 'Please enter valid email address',
			'email.exists' => 'This email is already available, Please use other one.',
			'role.required' => 'Please select role',
		]);

		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}

		Admin::where("id", $request->id)->update(array(
			"name" => $request->name,
			"email" => $request->email,
			"role" => $request->role,
			"status" => 1
		));

		return $this->response("Sub user details updated sucessfully.");
	}

	public function get_sub_users(Request $request)
	{

		$list = Admin::select("*")->where("role", "!=", 0);


		if ($request->has('search') && $request->search != '') {
			$list->where(function ($where) use ($request) {
				$where->where('admin.name', 'LIKE', '%' . $request->search . '%');
				$where->orwhere('admin.email', 'LIKE', '%' . $request->search . '%');
			});
		}

		$list->orderBy("id", "DESC");

		if ($request->has('all')) {
			$list = $list->get();

			foreach ($list as $val) {
				$val->date = $this->toLocalDate($val->created_at);
			}

			return $list;
		}

		$list = $list->paginate(100);

		foreach ($list as $val) {
			$val->date = $this->toLocalDate($val->created_at);
		}

		return $list;
	}

	public function search_sales_person(Request $request)
	{

		$list = Admin::select("id", "name")->where("role", 2);

		//->where("status", 1);

		if (isset($request["search"]) && $request["search"] != '') {
			$list->where(function ($where) use ($request) {
				$where->where('admin.name', 'LIKE', "%{$request["search"]}%");
				$where->orwhere('admin.email', 'LIKE', "%{$request["search"]}%");
			});
		}

		return $list->get();
	}

	public function get_sub_user($id)
	{
		$user = Admin::select("*")->where("id", $id)->first();

		$user->date = $this->toLocalDate($user->created_at);

		return $this->response("", false, $user);
	}

	public function sub_user_status_update($id)
	{

		$user = Admin::select("*")->where("id", $id)->first();

		if (empty($user)) {
			return $this->response("User not found", true);
		}

		if ($user->status == 0) {
			Admin::where("id", $id)->update(array("status" => 1));
			$msg = "User activated successfully.";
		} else {
			Admin::where("id", $id)->update(array("status" => 0));
			$msg = "User deactivated successfully.";
		}

		return $this->response($msg);
	}

	/************ END SUB ADMIN SECTION ***************/

	public function add_lead(Request $request)
	{

		$validator = Validator::make($request->all(), [
			'date' => 'required',
			'name' => 'required',
			'phone' => 'required',
			//'city' => 'required',
			//'customer_type' => 'required',
			//'water_type' => 'required',
			//'interested_in' => 'required',
			'message' => 'required',
		], [
			'date.required' => 'Please enter date',
			'name.required' => 'Please enter name',
			'phone.required' => 'Please enter phone number',
			'email.required' => 'Please enter email address',
			'city.required' => 'Please enter city',
			'customer_type.required' => 'Please select customer type',
			'water_type.required' => 'Please select water type',
			'interested_in.required' => 'Please select interested in',
			'problems.required' => 'Please select Problem(s) with water',
			'message.required' => 'Please enter message',
			'hear_about.required' => 'Please enter hear about',
		]);

		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}

		$user = auth()->user();

		/*$count = Leads::where("email", $request->email)->count();
		
		if($count!=0){
			return $this->response("Email address is already registered",true);
		}
		
		$count = Leads::where("phone", $request->phone)->count();
		
		if($count!=0){
			return $this->response("Phone number is already registered",true);
		}*/

		$request->phone = str_replace(" ", "", $request->phone);

		$has = Leads::select("*");

		if ($request->email) {
			$has->where("email", $request->email);
		}

		if ($request->phone) {
			$has->orWhereRaw("REPLACE(phone,'+','')='{$request->phone}'");
		}

		$has = $has->orderBy("id", "ASC")->first();

		if ($has) {
			$oneHourAgo = Carbon::now('UTC')->subHour();

			$exist = Leads::select("*")->where('created_at', '>=', $oneHourAgo);

			if ($request->email || $request->phone) {
				$exist->where(function ($w) use ($request) {
					if ($request->email) {
						$w->where("email", $request->email);
					}
					if ($request->phone) {
						$w->orWhereRaw("REPLACE(phone,'+','')='{$request->phone}'");
					}
				});
			}

			$exist = $exist->exists();

			if ($exist) {
				return $this->response("Email or phone number is already registered", true);
			}
		}

		$old_lead_id = $has->id ?? -1;

		$customer_type = $request->customer_type ?? -1;
		$customer_type = $customer_type == "undefined" ? -1 : $customer_type;

		$water_type = $request->water_type ?? "";
		$water_type = $water_type == "undefined" ? "" : $water_type;

		$arr = array(
			"created_at" => $this->toUTCDateYMD(date("Y-m-d", strtotime($request->date)), true, "Y-m-d") . " " . date("H:i:s"),
			"name" => $request->name,
			"phone" => $request->phone,
			"email" => $request->email ?? "",
			"city" => $request->city ?? "",
			"cust_type" => $customer_type,
			"water_type" => $water_type,
			"interest_in" => $request->interested_in ?? "",
			"problem_with_your_water" => $request->problems ?? "",
			"message" => $request->message,
			"type" => 7,
			"hear_about" => $request->hear_about ?? "",
			"created_by" => $user->id
		);

		if ($user->role == 2) {
			$arr["status"] = 1;
			$arr["assigned_to"] = $user->id;
			$arr["assigned_date"] = $this->toUTCDateYMD(date("Y-m-d", strtotime($request->date)), true, "Y-m-d") . " " . date("H:i:s");
		}

		$lead = Leads::create($arr);

		LeadsHistory::insert(array(
			"lead_id" => $lead->id,
			"user_id" => $user->id,
			"message" => "Lead created by <TAGSYSTEM>created_by:{$user->id}</TAGSYSTEM>",
		));

		if ($old_lead_id != -1) {
			LeadsDuplicateTracking::create([
				"lead_id" => $old_lead_id,
				"duplicate_lead_id" => $lead->id
			]);

			$has->is_duplicate = 1;
			$has->save();
		}

		if ($user->role == 2) {
			LeadsHistory::insert(array(
				"lead_id" => $lead->id,
				"user_id" => $user->id,
				"message" => "<TAGSYSTEM>assigned_by:{$user->id}</TAGSYSTEM> assigned lead to self.",
			));
		}

		return $this->response("Lead saved successfully", false);
	}

	public function update_lead($id, Request $request)
	{

		$validator = Validator::make($request->all(), [
			// 'date' => 'required',
			'name' => 'required',
			'phone' => 'required',
			//'city' => 'required',
			//'customer_type' => 'required',
			//'water_type' => 'required',
			//'interested_in' => 'required',
			'message' => 'required',
		], [
			// 'date.required' => 'Please enter date',
			'name.required' => 'Please enter name',
			'phone.required' => 'Please enter phone number',
			'email.required' => 'Please enter email address',
			'city.required' => 'Please enter city',
			'customer_type.required' => 'Please select customer type',
			'water_type.required' => 'Please select water type',
			'interested_in.required' => 'Please select interested in',
			'problems.required' => 'Please select Problem(s) with water',
			'message.required' => 'Please enter message',
			'hear_about.required' => 'Please enter hear about',
		]);

		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}

		$user = auth()->user();

		/*$count = Leads::where("email", $request->email)->where("id", "!=", $id)->count();
		if($count!=0){
			return $this->response("Email address is already registered",true);
		}
		
		$count = Leads::where("phone", $request->phone)->where("id", "!=", $id)->count();
		if($count!=0){
			return $this->response("Phone number is already registered",true);
		}*/

		$customer_type = $request->customer_type ?? -1;
		$customer_type = $customer_type == "undefined" ? -1 : $customer_type;

		$water_type = $request->water_type ?? "";
		$water_type = $water_type == "undefined" ? "" : $water_type;

		$lead_details = Leads::where("id", $id)->first();
		$lead_details_time = date("H:i:s", strtotime($lead_details->created_at));

		Leads::where("id", $id)->update(array(
			// "created_at" => $this->toUTCDateYMD(date("Y-m-d", strtotime($request->date)), true, "Y-m-d"). " ". date("H:i:s"),
			// "created_at" => $this->toUTCDateYMD(date("Y-m-d", strtotime($request->date)), true, "Y-m-d"). " ". $lead_details_time,
			"name" => $request->name,
			"phone" => $request->phone,
			"email" => $request->email ?? "",
			"city" => $request->city ?? "",
			"cust_type" => $customer_type,
			"water_type" => $water_type,
			"interest_in" => $request->interested_in ?? "",
			"problem_with_your_water" => $request->problems ?? "",
			"message" => $request->message,
			"hear_about" => $request->hear_about ?? ""
		));

		if ($request->email != $lead_details->email || $request->phone != $lead_details->phone) {
			$leads = LeadsDuplicateTracking::where("lead_id", $id)->orderBy("id", "ASC")->get();

			if (count($leads) >= 1) {
				$all_leads_id = collect($leads)->pluck("duplicate_lead_id");
				$new_lead_id = $all_leads_id->first();
				LeadsDuplicateTracking::whereIn("duplicate_lead_id", $all_leads_id)->update(["lead_id" => $new_lead_id]);
				LeadsDuplicateTracking::where("duplicate_lead_id", $new_lead_id)->where("lead_id", $new_lead_id)->delete();

				Leads::where("id", $new_lead_id)->update(["is_duplicate" => 1]);
				Leads::where("id", $id)->update(["is_duplicate" => 0]);
			} else {
				$other_lead = LeadsDuplicateTracking::where("duplicate_lead_id", $id)->first();
				if (!empty($other_lead)) {
					$actual_lead_id = $other_lead->lead_id;

					$total_count = LeadsDuplicateTracking::where("lead_id", $actual_lead_id)->count();

					if ($total_count == 0) {
						Leads::where("id", $actual_lead_id)->update(["is_duplicate" => 0]);
					}
				}
				LeadsDuplicateTracking::where("duplicate_lead_id", $id)->delete();
			}
		}

		return $this->response("Lead updated successfully", false);
	}

	public function delete_lead($lead_id)
	{

		$leads = LeadsDuplicateTracking::where("lead_id", $lead_id)->orderBy("id", "ASC")->get();

		if (count($leads) >= 1) {

			/*
				first get all the records
				pick the second lead and take id
				update in all leads
			*/

			$all_leads_id = collect($leads)->pluck("duplicate_lead_id");

			$new_lead_id = $all_leads_id->first();

			LeadsDuplicateTracking::whereIn("duplicate_lead_id", $all_leads_id)->update(["lead_id" => $new_lead_id]);
			LeadsDuplicateTracking::where("duplicate_lead_id", $new_lead_id)->where("lead_id", $new_lead_id)->delete();
			Leads::where("id", $lead_id)->delete();
			LeadsHistory::where("lead_id", $lead_id)->delete();

			$main_deleted = true;
		} else {
			/*
				delete from leads duplicate table
				delete from leads table
				delete from leads history table
			*/

			$new_lead_id = LeadsDuplicateTracking::where("duplicate_lead_id", $lead_id)->pluck("lead_id")->first();

			LeadsDuplicateTracking::where("duplicate_lead_id", $lead_id)->delete();
			Leads::where("id", $lead_id)->delete();
			LeadsHistory::where("lead_id", $lead_id)->delete();

			$main_deleted = false;
		}

		$total = LeadsDuplicateTracking::where("lead_id", $new_lead_id)->count();

		return $this->response("Lead deleted successfully", false, ["main_deleted" => $main_deleted, "total" => $total]);
	}

	public function delete_lead_old($id, Request $request)
	{

		$user = auth()->user();

		if ($user->role == 2)
			return $this->response("Access denied", true);

		$lead = Leads::where("id", $id)->first();

		if (!$lead) {
			return $this->response("Lead Not found", true);
		}

		$lead->delete();

		$leadhistories = LeadsHistory::where("lead_id", $id)->get();

		foreach ($leadhistories as $leadhistory) {
			if (!empty($leadhistory->attachment)) {
				$this->remove_file($leadhistory->attachment, "attachments");
			}
		}

		LeadsHistory::where("lead_id", $id)->delete();

		return $this->response("Lead has been deleted successfully", false);
	}

	public function lead_message($id, Request $request)
	{

		$lead = Leads::where("id", $id)->first();

		if (!$lead) {
			return $this->response("Lead Not found", true);
		}

		$lead_message = !empty($lead->message) ? $lead->message : "No message found";

		return $this->response("", false, $lead_message);
	}

	public function lead_detail($id, Request $request)
	{

		$lead = Leads::where("id", $id)->first();

		if (!$lead) {
			return $this->response("Lead Not found", true);
		}

		return $this->response("", false, $lead);
	}

	public function get_admin_leads(Request $request)
	{

		$user = auth()->user();

		if ($user->role == 2)
			return $this->response("Access denied", true);

		$field_has_comment = "IF( (SELECT COUNT(DISTINCT CASE WHEN LH.message LIKE 'Lead assigned by%' THEN 'Lead assigned' ELSE LH.id END) FROM leads_history AS LH WHERE LH.lead_id=leads.id)<=2, 0, 1)";

		$list = Leads::select([
			"*",
			DB::raw("IF(status>=2, 1, $field_has_comment) as has_comments")
		]);

		if (
			$request->has('source')
			&& $request->source != ''
			&& $request->source != -1
		) {
			$list->where('leads.type', '=', $request->source);
		}

		if (
			$request->has('status') && $request->status == -2
		) {
			$list->whereIn('leads.status', [0, 1]);
		} else if (
			$request->has('status')
			&& $request->status != ''
			&& $request->status != -1
		) {
			$list->where('leads.status', '=', $request->status);
		}

		if (
			$request->has('user_id')
			&& $request->user_id != ''
			&& $request->user_id != -1
		) {
			$list->where('leads.assigned_to', '=', $request->user_id);
		}

		if (
			$request->has('start_date')
			&& $request->start_date != ''
			&& $request->has('end_date')
			&& $request->end_date != ''
		) {
			$start_date = Carbon::createFromFormat('d-m-Y', "{$request->start_date}", 'America/Toronto')->startOfDay()->setTimezone('UTC')->format("Y-m-d H:i:s");
			$end_date = Carbon::createFromFormat('d-m-Y', "{$request->end_date}", 'America/Toronto')->endOfDay()->setTimezone('UTC')->format("Y-m-d H:i:s");
			/*$start_date = $this->toUTCDateYMD("{$request->start_date} 00:00:00", true);
			$end_date = $this->toUTCDateYMD("{$request->end_date} 23:59:59", true);*/
			// $list->whereRaw("DATE(created_at)>='{$start_date}' AND DATE(created_at)<='{$end_date}'");
			$list->whereRaw("created_at BETWEEN '{$start_date}' AND '{$end_date}' ");
		}

		if ($request->has('search') && $request->search != '') {
			$list->where(function ($where) use ($request) {
				$where->where('leads.name', 'LIKE', '%' . $request->search . '%');
				$where->orwhere('leads.email', 'LIKE', '%' . $request->search . '%');
				$where->orwhere('leads.phone', 'LIKE', '%' . $request->search . '%');
				$where->orwhere('leads.city', 'LIKE', '%' . $request->search . '%');
				$where->orwhere('leads.post_code', 'LIKE', '%' . $request->search . '%');
				$where->orwhere('leads.address', 'LIKE', '%' . $request->search . '%');
			});
		}

		$list->orderBy("created_at", "DESC");

		$list = $list->paginate(100);

		foreach ($list as $val) {
			$val->date = $this->toLocalDate($val->created_at);
			if (!empty($val->phone)) {
				$val->phone = str_replace(' ', '', $val->phone);
			}
		}

		return $list;
	}

	public function admin_assign_lead(Request $request)
	{

		$validator = Validator::make($request->all(), [
			'id' => 'required',
			'user_id' => 'required'
		], [
			'id.required' => 'Please enter id',
			'user_id.required' => 'Please select user to assign'
		]);

		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}

		$user = auth()->user();

		if ($user->role == 2)
			return $this->response("Access denied", true);

		$lead = Leads::select("*")->where('id', $request->id)->first();

		if (empty($lead)) {
			return $this->response("Lead not found", true);
		}

		/*if($lead->assigned_to!=-1){
			return $this->response("Lead is already assigned to other sales person", true);
		}*/

		Leads::where('id', $request->id)->update(array(
			"assigned_date" => date("Y-m-d H:i:s"),
			"assigned_to" => $request->user_id,
			"status" => 1
		));

		LeadsHistory::insert(array(
			"lead_id" => $request->id,
			"user_id" => $request->user_id,
			"assigned_by" => $user->id,
			"message" => "Lead assigned by <strong>{$user->name}</strong> to <TAGSYSTEM>assigned_name:{$request->user_id}</TAGSYSTEM>",
		));

		$admin = Admin::where("id", $request->user_id)->first();
		$leads = Leads::where("id", $request->id)->first();

		Mail::send('emails.assign-lead-to-sales', ['admin' => $admin, "lead" => $leads], function ($message) use ($request, $admin, $user) {
			$message->to($admin->email);
			$message->subject("New Lead assigned by {$user->name}");
		});

		return $this->response("Lead assigned successfully", false);
	}


	public function get_assigned_lead($user_id, Request $request)
	{

		$user = auth()->user();

		if ($user->role == 2)
			return $this->response("Access denied", true);

		$list = Leads::select(["id", DB::raw("CONCAT(name, ' (', phone, ')') as text")])->where("assigned_to", $user_id);

		if ($request->has('search') && $request->search != '') {
			$list->where(function ($where) use ($request) {
				$where->where('leads.name', 'LIKE', '%' . $request->search . '%');
				$where->orwhere('leads.email', 'LIKE', '%' . $request->search . '%');
				$where->orwhere('leads.phone', 'LIKE', '%' . $request->search . '%');
				$where->orwhere('leads.city', 'LIKE', '%' . $request->search . '%');
				$where->orwhere('leads.post_code', 'LIKE', '%' . $request->search . '%');
				$where->orwhere('leads.address', 'LIKE', '%' . $request->search . '%');
			});
		}

		$list = $list->orderBy("assigned_date", "DESC")->get()->toArray();

		return $list;
	}

	public function admin_transfer_leads(Request $request)
	{

		$validator = Validator::make($request->all(), [
			'from_user_id' => 'required',
			'to_user_id' => 'required',
			'id' => 'required'
		], [
			'from_user_id.required' => 'Please from user',
			'id.required' => 'Please select lead',
			'to_user_id.required' => 'Please select to user'
		]);

		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}

		$user = auth()->user();

		if ($user->role == 2)
			return $this->response("Access denied", true);

		$lead = Leads::select("id")
			->where('assigned_to', $request->from_user_id);

		if ($request->id != -1) {
			$lead = $lead->whereIn("id", explode(",", $request->id));
		}

		$lead = $lead->get();

		if (count($lead) == 0) {
			return $this->response("Leads not found", true);
		}

		if ($request->id != -1) {
			Leads::where('assigned_to', $request->from_user_id)->whereIn("id", explode(",", $request->id))->update(array(
				"assigned_date" => date("Y-m-d H:i:s"),
				"assigned_to" => $request->to_user_id,
				"is_transfered" => 1
			));
		} else {
			Leads::where('assigned_to', $request->from_user_id)->update(array(
				"assigned_date" => date("Y-m-d H:i:s"),
				"assigned_to" => $request->to_user_id,
				"is_transfered" => 1
			));
		}

		foreach ($lead as $vl) {
			LeadsHistory::insert(array(
				"lead_id" => $vl->id,
				"user_id" => $request->to_user_id,
				"assigned_by" => $user->id,
				"message" => "Lead transferred by <strong>{$user->name}</strong> to <TAGSYSTEM>assigned_name:{$request->to_user_id}</TAGSYSTEM>",
			));
		}

		$admin = Admin::where("id", $request->to_user_id)->first();

		Mail::send('emails.transfer-lead-to-sales', ['admin' => $admin], function ($message) use ($request, $admin, $user) {
			$message->to($admin->email);
			$message->subject("Multiple Leads transferred by {$user->name}");
		});

		return $this->response("Lead transferred successfully", false);
	}

	public function get_lead_details($lead_id)
	{

		$lead = Leads::select("*")
			->where("id", $lead_id)
			->first();

		if (empty($lead)) {
			return $this->response("Lead not found or not assigned to you.", true);
		}

		$pw = [];

		$arr = explode(",", $lead->problem_with_your_water);

		foreach ($arr as $val) {

			$l = $this->problems_with_water[$val] ?? "";

			if ($l) {
				array_push($pw, $l);
			}
		}

		$lead->problem_with_your_water_text = implode(", ", $pw);


		$pw = [];

		$arr = explode(",", $lead->interest_in);

		foreach ($arr as $val) {

			if (
				$lead->type == 5 || $lead->type == 8 || $lead->type == 9 || $lead->type == 10 || $lead->type == 11 || $lead->type == 12 || $lead->type == 13 ||
				$lead->type == 14 || $lead->type == 15 || $lead->type == 17 || $lead->type == 18 || $lead->type == 19 || $lead->type == 20 || $lead->type == 21 || $lead->type == 22
			) {
				$l = $this->interest_in_wts[$val] ?? "";
			} else {
				// 0-4 and 16 will be shown from here
				$l = $this->interest_in[$val] ?? "";
			}

			if ($l) {
				array_push($pw, $l);
			}
		}

		$lead->interest_in_text = implode(", ", $pw);

		$lead->water_type_text = $this->water_type[$lead->water_type] ?? "";
		$lead->cust_type_text = $this->cust_type[$lead->cust_type] ?? "";

		$lead->date = $this->toLocalDate($lead->created_at);
		$lead->date_time = $this->toLocalDate($lead->date_time);

		return $this->response("", false, $lead);
	}

	public function get_lead_history($lead_id)
	{

		$lead_history = LeadsHistory::select(array(
			"*",
			DB::raw("(SELECT admin.name FROM admin WHERE admin.id=leads_history.user_id) as sales_person_name")
		))->where("lead_id", $lead_id)->orderBy("id", "DESC")->paginate(10);

		foreach ($lead_history as $val) {

			$val->date = $this->toLocalDate($val->created_at);
			$val->followup_date = is_null($val->followup_date) ? "" : $this->toLocalDate("{$val->followup_date} 11:00:00", true);

			$val->message = $this->extractMessage($val->message);

			if ($val->attachment) {
				$val->attachment = $this->_url("attachments/{$val->attachment}");
			}
		}

		return $this->response("", false, $lead_history);
	}

	public function get_lead_last_comment($lead_id)
	{

		$has_more = LeadsHistory::select(array(
			"message"
		))->where("lead_id", $lead_id)->count();

		if ($has_more <= 1) {
			return $this->response("", false, "");
		}

		$message = LeadsHistory::select(array(
			"message"
		))->where("lead_id", $lead_id)->orderBy("id", "DESC")->first();

		$message = $this->extractMessage($message->message ?? "");

		return $this->response("", false, $message);
	}

	public function get_revenue_report(Request $request)
	{

		$rlist = Leads::where("status", 3);

		$list = Leads::select(array(
			"created_at",
			"name",
			"revenue",
			"utm_source",
			"type",
			"hear_about",
			DB::raw("(SELECT admin.name FROM admin WHERE admin.id=converted_by) as converted_user")
		))->where("status", 3);

		if (
			$request->has('start_date')
			&& $request->start_date != ''
			&& $request->has('end_date')
			&& $request->end_date != ''
		) {
			$start_date = $this->toUTCDateYMD("{$request->start_date} 00:00:00", true);
			$end_date = $this->toUTCDateYMD("{$request->end_date} 23:59:59", true);

			$list->whereRaw("DATE(created_at)>='{$start_date}' AND DATE(created_at)<='{$end_date}'");
			$rlist->whereRaw("DATE(created_at)>='{$start_date}' AND DATE(created_at)<='{$end_date}'");
		} else {

			$year = date("Y");
			$month = date("m");

			$start_date = $this->toUTCDateYMD("01-{$month}-{$year} 00:00:00", true);
			$end_date = $this->toUTCDateYMD("31-{$month}-{$year} 23:59:59", true);

			$list->whereRaw("DATE(created_at)>='{$start_date}' AND DATE(created_at)<='{$end_date}'");
			$rlist->whereRaw("DATE(created_at)>='{$start_date}' AND DATE(created_at)<='{$end_date}'");
		}

		$list->orderBy("id", "DESC");

		$list = $list->paginate(100);

		foreach ($list as $val) {
			$val->date = $this->toLocalDate($val->created_at);
		}

		$total_revenue = $rlist->sum("revenue");

		$data = [];
		$data["total_revenue"] = $total_revenue ? number_format($total_revenue, 2) : "0.00";
		$data["list"] = $list;

		return $this->response("", false, $data);
	}

	/************** SALES USERS START *************/

	public function get_sales_user_assigned_leads(Request $request)
	{

		$user = auth()->user();

		$field_has_comment = "IF( (SELECT COUNT(DISTINCT CASE WHEN LH.message LIKE 'Lead assigned by%' THEN 'Lead assigned' ELSE LH.id END) FROM leads_history AS LH WHERE LH.lead_id=leads.id)<=2, 0, 1)";

		$list = Leads::select([
			"*",
			DB::raw("IF(status>=2, 1, $field_has_comment) as has_comments")
		])->where("assigned_to", $user->id);

		if (
			$request->has('source')
			&& $request->source != ''
			&& $request->source != -1
		) {
			$list->where('leads.type', '=', $request->source);
		}

		if (
			$request->has('status') && $request->status == -2
		) {
			$list->whereIn('leads.status', [0, 1]);
		} else if (
			$request->has('status')
			&& $request->status != ''
			&& $request->status != -1
		) {
			$list->where('leads.status', '=', $request->status);
		}

		if ($request->has('search') && $request->search != '') {
			$list->where(function ($where) use ($request) {
				$where->where('leads.name', 'LIKE', '%' . $request->search . '%');
				$where->orwhere('leads.email', 'LIKE', '%' . $request->search . '%');
				$where->orwhere('leads.phone', 'LIKE', '%' . $request->search . '%');
				$where->orwhere('leads.city', 'LIKE', '%' . $request->search . '%');
				$where->orwhere('leads.post_code', 'LIKE', '%' . $request->search . '%');
				$where->orwhere('leads.address', 'LIKE', '%' . $request->search . '%');
			});
		}

		if ($request->has('start_date') && $request->start_date != '' && $request->has('end_date') && $request->end_date != '') {
			$start_date = Carbon::createFromFormat('d-m-Y', "{$request->start_date}", 'America/Toronto')->startOfDay()->setTimezone('UTC')->format("Y-m-d H:i:s");
			$end_date = Carbon::createFromFormat('d-m-Y', "{$request->end_date}", 'America/Toronto')->endOfDay()->setTimezone('UTC')->format("Y-m-d H:i:s");
			// $list->whereRaw("DATE(created_at)>='{$start_date}' AND DATE(created_at)<='{$end_date}'");
			$list->whereRaw("created_at BETWEEN '{$start_date}' AND '{$end_date}' ");
		}

		$list->orderBy("created_at", "DESC");

		$list = $list->paginate(100);

		foreach ($list as $val) {
			$val->date = $this->toLocalDate($val->created_at);
		}

		return $list;
	}

	public function get_sales_user_status_update(Request $request)
	{

		$validator = Validator::make($request->all(), [
			'lead_id' => 'required',
			'status' => ['required', Rule::in([2, 3])],
			'product_id' => 'required_if:status,3',
			'installation_date' => 'required_if:status,3|date',

		], [
			'lead_id.required' => 'Please select lead',
			'status.required' => 'Please select status',
			'status.in' => 'Please select valid status',
			'product_id.required_if' => 'Product ID is required when status is converted.',
			'installation_date.required_if' => 'Installation date is required when status is converted.',
			'installation_date.date' => 'Installation date must be a valid date.',
		]);


		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}

		$user = auth()->user();

		$lead = Leads::select("*")
			->where("id", $request->lead_id)
			->where("assigned_to", $user->id)
			->first();

		if (empty($lead)) {
			return $this->response("Lead not found or not assigned to you.", true);
		}

		if ($request->status == 3) {

			$productIds = explode(',', $request->product_id);
			$products = Product::whereIn('id', $productIds)->get();
			$productNames = $products->pluck('name')->join(', ');

			if (empty($products)) {
				return $this->response("Products not found.", true);
			}
		}

		$leadarry = [];

		$installation_date = "";

		if ($request->status == 2) {
			$to_status = "Dropped";
		} else {

			$to_status = "Converted";
			$leadarry["product_id"] = $request->product_id;
			$leadarry["installation_date"] = $this->toUTCDateYMD("{$request->installation_date} 00:00:00", true, "Y-m-d");

			$installation_date = $this->toLocalDate("{$leadarry["installation_date"]} 00:00:00", true, "d-m-Y");
		}

		$closed_reason = $request->reason ?? "";

		$leadarry["converted_date"] = $this->toUTCDateYMD(date("Y-m-d H:i:s"), true, "Y-m-d H:i:s");
		$leadarry["status"] = $request->status;
		$leadarry["converted_by"] = $request->converted_by;
		$leadarry["revenue"] = $request->status == 3 ? ($request->revenue ?? 0) : 0;
		$leadarry["closed_reason"] = $request->closed_reason;

		Leads::where('id', $request->lead_id)->update($leadarry);

		$lead_history = LeadsHistory::where("lead_id", $request->lead_id)->orderBy("id", "DESC")->first();

		$msg = "Lead status updated to <strong>{$to_status}</strong> by <TAGSYSTEM>closed_name:{$user->id}</TAGSYSTEM>";

		if (!empty($closed_reason)) {
			$msg .= "<br /><strong>Notes: </strong>{$closed_reason}";
		}

		if (!empty($installation_date)) {
			$msg .= "<br /><strong>Installation Date: </strong>{$installation_date}";
		}

		if (!empty($products)) {
			$msg .= "<br /><strong>Products Name: </strong>{$productNames}";
		}

		LeadsHistory::insert(array(
			"lead_id" => $request->lead_id,
			"user_id" => $user->id,
			"assigned_by" => $lead_history->assigned_by ?? -1,
			"message" => $msg,
		));

		if ($request->status == 3) {
			$admin = Admin::where("role", 0)->first();

			Mail::send('emails.lead-converted-to-admin', ['admin_name' => $admin->name, 'sales_name' => $user->name, 'client_name' => $lead->name, 'installation_date' => $installation_date, 'product_name' => $productNames, 'revenue' => $request->revenue, 'date' => $this->toUTCDateYMD(date("Y-m-d H:i:s"), true, "M d, Y h:i A"), 'closed_reason' => $closed_reason], function ($message) use ($request, $admin, $user) {
				$message->to($admin->email);
				$message->subject("Lead Converted by {$user->name}");
			});
		}

		return $this->response("Lead {$to_status} successfully", false);
	}

	public function get_sales_dashboard(Request $request)
	{

		//$today = $this->toUTCDateYMD(date("Y-m-d H:i:s"), true);
		$today = date("Y-m-d");

		$user = auth()->user();

		$leads = Leads::select("*")
			->where("assigned_to", $user->id)
			->whereRaw("followup_date='{$today}'")
			->get();

		foreach ($leads as $val) {
			$val->date = $this->toLocalDate($val->created_at);
			$val->followup_date = $this->toLocalDate("{$val->followup_date} 11:00:00", true);
		}

		$data = [];
		$data["todays_follow_up"] = $leads;

		$start_date = date("Y-m-01");
		$end_date = date("Y-m-31");

		$total_leads = Leads::where("assigned_to", $user->id)->whereRaw("DATE(assigned_date)>='{$start_date}' AND DATE(assigned_date)<='{$end_date}'")->count();

		$total_sales = Leads::where("assigned_to", $user->id)->whereRaw("DATE(created_at)>='{$start_date}' AND DATE(created_at)<='{$end_date}'")->where("status", 3)->count();

		$total_droped = Leads::where("assigned_to", $user->id)->whereRaw("DATE(created_at)>='{$start_date}' AND DATE(created_at)<='{$end_date}'")->where("status", 2)->count();

		$total_in_prog = Leads::where("assigned_to", $user->id)->whereRaw("DATE(assigned_date)>='{$start_date}' AND DATE(assigned_date)<='{$end_date}'")->where("status", 1)->count();

		$total_conversion = ($total_sales * 100) / (empty($total_leads) ? 1 : $total_leads);
		$total_conversion = number_format($total_conversion, 2);

		$total_revenue = Leads::where("assigned_to", $user->id)->whereRaw("DATE(created_at)>='{$start_date}' AND DATE(created_at)<='{$end_date}'")->where("status", 3)->sum("revenue");

		$data["total_sales"] = $total_sales;
		$data["total_revenue"] = number_format($total_revenue, 2);
		$data["total_conversion"] = "{$total_conversion}%";
		$data["total_leads"] = $total_leads;
		$data["total_droped"] = $total_droped;
		$data["total_in_prog"] = $total_in_prog;

		$start_date = date("Y-m-01", strtotime("-1 months"));
		$end_date = date("Y-m-31", strtotime("-1 months"));


		$month_leads = Leads::where("assigned_to", $user->id)->whereRaw("DATE(assigned_date)>='{$start_date}' AND DATE(assigned_date)<='{$end_date}'")->count();

		$month_sales = Leads::where("assigned_to", $user->id)->whereRaw("DATE(created_at)>='{$start_date}' AND DATE(created_at)<='{$end_date}'")->where("status", 3)->count();

		$month_droped = Leads::where("assigned_to", $user->id)->whereRaw("DATE(created_at)>='{$start_date}' AND DATE(created_at)<='{$end_date}'")->where("status", 2)->count();

		$month_in_prog = Leads::where("assigned_to", $user->id)->whereRaw("DATE(assigned_date)>='{$start_date}' AND DATE(assigned_date)<='{$end_date}'")->where("status", 1)->count();

		$month_conversion = ($month_sales * 100) / (empty($month_leads) ? 1 : $month_leads);
		$month_conversion = number_format($month_conversion, 2);

		$month_revenue = Leads::where("assigned_to", $user->id)->whereRaw("DATE(created_at)>='{$start_date}' AND DATE(created_at)<='{$end_date}'")->where("status", 3)->sum("revenue");

		$data["month_label"] = "Previous Month";
		$data["month_sales"] = $month_sales;
		$data["month_revenue"] = "$" . number_format($month_revenue, 2);
		$data["month_conversion"] = "{$month_conversion}%";
		$data["month_leads"] = $month_leads;
		$data["month_droped"] = $month_droped;
		$data["month_in_prog"] = $month_in_prog;

		return $this->response("", false, $data);
	}

	public function get_admin_dashboard(Request $request)
	{

		$today = $this->toUTCDateYMD(date("Y-m-d H:i:s"), true);

		$user = auth()->user();

		$leads = Leads::select("*")
			->whereRaw("followup_date='{$today}'")
			->get();

		$start_date = date("Y-01-01");
		$end_date = date("Y-12-31");

		$total_leads = Leads::whereRaw("DATE(created_at)>='{$start_date}' AND DATE(created_at)<='{$end_date}'")->count();
		$total_sales = Leads::whereRaw("DATE(created_at)>='{$start_date}' AND DATE(created_at)<='{$end_date}'")->where("status", 3)->count();
		$total_revenue = Leads::whereRaw("DATE(created_at)>='{$start_date}' AND DATE(created_at)<='{$end_date}'")->where("status", 3)->sum("revenue");

		$total_conversion = ($total_sales * 100) / (empty($total_leads) ? 1 : $total_leads);
		$total_conversion = number_format($total_conversion, 2);

		$data = [];
		$data["total_sales"] = $total_sales;
		$data["total_revenue"] = number_format($total_revenue, 2);
		$data["total_conversion"] = "{$total_conversion}%";
		$data["total_leads"] = $total_leads;
		$data["todays_follow_up"] = $leads;

		$mstart_date = date("Y-m-01");
		// $mend_date = date("Y-m-31");
		$mend_date = date("Y-m-t");

		$month_leads = Leads::whereRaw("DATE(created_at)>='{$mstart_date}' AND DATE(created_at)<='{$mend_date}'")->count();
		$month_sales = Leads::whereRaw("DATE(created_at)>='{$mstart_date}' AND DATE(created_at)<='{$mend_date}'")->where("status", 3)->count();
		$month_revenue = Leads::whereRaw("DATE(created_at)>='{$mstart_date}' AND DATE(created_at)<='{$mend_date}'")->where("status", 3)->sum("revenue");

		$month_conversion = ($month_sales * 100) / (empty($month_leads) ? 1 : $month_leads);
		$month_conversion = number_format($month_conversion, 2);

		$data["month_label"] = "Current month";
		$data["month_sales"] = $month_sales;
		$data["month_revenue"] = "$" . number_format($month_revenue, 2);
		$data["month_conversion"] = "{$month_conversion}%";
		$data["month_leads"] = $month_leads;

		$total_leads_seo = Leads::whereRaw("DATE(created_at)>='{$start_date}' AND DATE(created_at)<='{$end_date}'")
			->where("type", "!=", 7)
			->where("utm_source", "!=", "google")
			->count();

		$total_leads_ads = Leads::whereRaw("DATE(created_at)>='{$start_date}' AND DATE(created_at)<='{$end_date}'")
			->where("type", "!=", 7)
			->where("utm_source", "=", "google")
			->count();

		$total_leads_calls = Leads::whereRaw("DATE(created_at)>='{$start_date}' AND DATE(created_at)<='{$end_date}'")
			->where("type", 7)
			->count();

		$campaigns = [];
		$campaigns[] = ["label" => "SEO", "value" => $total_leads_seo];
		$campaigns[] = ["label" => "Google Ads", "value" => $total_leads_ads];
		$campaigns[] = ["label" => "Calls", "value" => $total_leads_calls];
		/*$campaigns[] = ["label"=>"Total", "value"=>$total_leads_seo + $total_leads_ads + $total_leads_calls];*/

		$data["campaigns"] = $campaigns;

		$graph = [];
		$graph["fill"] = ["#" . substr(dechex(crc32("Revenue")), 0, 6), "#" . substr(dechex(crc32("In Progress leads")), 0, 6)];

		$graph_data = Leads::select([
			"created_at",
			DB::raw("DATE_FORMAT(CONVERT_TZ(created_at, '+00:00', '-05:00'),'%b-%Y') as month"),
			DB::raw("COUNT(id) as total"),
			DB::raw("SUM(IF(status=1, 1,0)) as in_progress_leads"),
			DB::raw("SUM(IF(status=2, 1,0)) as droped_leads"),
			DB::raw("SUM(IF(status=3, 1,0)) as convered_leads"),
			DB::raw("SUM(IF(status=3, revenue,0)) as total_revenue"),
			// DB::raw("(SELECT SUM(ASP.amount) FROM ads_spends AS ASP 
			// 		  WHERE DATE_FORMAT(CONVERT_TZ(ASP.date, '+00:00', '-05:00'), '%b-%Y') = 
			// 		  DATE_FORMAT(CONVERT_TZ(leads.created_at, '+00:00', '-05:00'), '%b-%Y')) as total_ads_spends"),
			DB::raw("(SELECT SUM(ASP.amount) FROM ads_spends AS ASP 
					WHERE DATE_FORMAT(ASP.date, '%b-%Y') = 
					DATE_FORMAT(leads.created_at, '%b-%Y')) as total_ads_spends"),
		])
			->groupByRaw("YEAR(CONVERT_TZ(created_at, '+00:00', '-05:00')), MONTH(CONVERT_TZ(created_at, '+00:00', '-05:00'))")
			->orderByRaw("YEAR(CONVERT_TZ(created_at, '+00:00', '-05:00')) ASC, MONTH(CONVERT_TZ(created_at, '+00:00', '-05:00')) ASC")
			//->toSql();
			->get()->slice(-12)->toArray();

		$total_revenue = array_column($graph_data, 'total_revenue');
		$total_ads_spends = array_column($graph_data, 'total_ads_spends');

		foreach ($total_revenue as $index => $val) {
			$total_revenue[$index] = is_null($val) ? 0 : number_format($val / 1000, 2);
		}
		foreach ($total_ads_spends as $index => $val) {
			$total_ads_spends[$index] = is_null($val) ? 0 : number_format($val / 1000, 2);
		}

		$total_in_progress_leads = array_column($graph_data, 'in_progress_leads');
		$total_droped_leads = array_column($graph_data, 'droped_leads');
		$total_convered_leads = array_column($graph_data, 'convered_leads');
		$total_leads = array_column($graph_data, 'total');
		$months = array_column($graph_data, 'month');
		$months = array_values(array_filter($months, fn($item) => !is_null($item)));

		$graph["categories"] = $months;

		$series = [];
		$series[] = ["name" => "Total Ads Spend", "color" => "#" . substr(dechex(crc32("Total Ads Spend")), 0, 6), "data" => $total_ads_spends];
		$series[] = ["name" => "Revenue", "color" => "#" . substr(dechex(crc32("Revenue")), 0, 6), "data" => $total_revenue];
		$series[] = ["name" => "In Progress leads", "color" => "#" . substr(dechex(crc32("In Progress leads")), 0, 6), "data" => $total_in_progress_leads];
		$series[] = ["name" => "Dropped leads", "color" => "#" . substr(dechex(crc32("Dropped leads")), 0, 6), "data" => $total_droped_leads];
		$series[] = ["name" => "Converted leads", "color" => "#" . substr(dechex(crc32("Converted leads")), 0, 6), "data" => $total_convered_leads];
		$series[] = ["name" => "Total leads", "color" => "#" . substr(dechex(crc32("Total leads")), 0, 6), "data" => $total_leads];

		$graph["series"] = $series;

		$data["graph"] = $graph;

		$insight = Leads::select(array(
			"created_at",
			DB::raw("count(id) as total"),
			DB::raw("sum(IF(type=1, 1,0)) as contact_leads"),
			DB::raw("sum(IF(type=2, 1,0)) as contact_popup"),
			DB::raw("sum(IF(type=3, 1,0)) as contact_home"),
			DB::raw("sum(IF(type=7, 1,0)) as contact_call"),
			DB::raw("sum(IF(type=0, 1,0)) as book_test"),
			DB::raw("sum(IF(type=4, 1,0)) as refer_friend"),
			DB::raw("sum(IF(type=5, 1,0)) as lp_wts"),
			DB::raw("sum(IF(type=6, 1,0)) as lp_ro"),
		))
			->groupByRaw("YEAR(created_at), MONTH(created_at)")
			->orderByRaw("YEAR(created_at) DESC, MONTH(created_at) DESC")
			->get();

		foreach ($insight as $val) {
			$val->label = $this->toLocalDate($val->created_at, false, "M-Y");
		}

		$data["insight"] = $insight;

		$performance = Leads::select(array(
			"created_at",
			DB::raw("(SELECT admin.name FROM admin WHERE admin.id=assigned_to) as name"),
			DB::raw("count(id) as assigned_leads"),
			DB::raw("sum(IF(status=1, 1,0)) as in_progress_leads"),
			DB::raw("sum(IF(status=2, 1,0)) as droped_leads"),
			DB::raw("sum(IF(status=3, 1,0)) as convered_leads"),
			DB::raw("sum(IF(status=3, revenue,0)) as total_revenue"),
		))
			->whereRaw("DATE(created_at)>='{$start_date}' AND DATE(created_at)<='{$end_date}'")
			->groupBy("assigned_to")
			->orderByRaw("sum(IF(status=3, revenue,0)) DESC")
			->get();

		foreach ($performance as $val) {
			$val->revenue = number_format($val->total_revenue, 2);
		}

		$data["performance"] = $performance;

		return $this->response("", false, $data);
	}

	public function get_sales_user_lead_details($lead_id)
	{

		$user = auth()->user();

		$lead = Leads::select("*")
			->where("id", $lead_id)
			->where("assigned_to", $user->id)
			->first();

		if (empty($lead)) {
			return $this->response("Lead not found or not assigned to you.", true);
		}

		$lead->date = $this->toLocalDate($lead->created_at);

		return $this->response("", false, $lead);
	}

	public function get_sales_user_lead_history($lead_id)
	{

		$user = auth()->user();

		$lead_history = LeadsHistory::select(array(
			"*",
			DB::raw("(SELECT admin.name FROM admin WHERE admin.id=leads_history.user_id) as sales_person_name")
		))->where("lead_id", $lead_id)->orderBy("id", "DESC")->paginate(10);

		/*->where("user_id", $user->id)*/

		foreach ($lead_history as $val) {

			$val->date = $this->toLocalDate($val->created_at);
			$val->followup_date = is_null($val->followup_date) ? "" : $this->toLocalDate("{$val->followup_date} 11:00:00", true);
			$val->message = $this->extractMessage($val->message);

			if ($val->attachment) {
				$val->attachment = $this->_url("attachments/{$val->attachment}");
			}
		}

		return $this->response("", false, $lead_history);
	}

	public function update_sales_user_lead(Request $request)
	{

		$validator = Validator::make($request->all(), [
			'lead_id' => 'required',
			'message' => 'required',
		], [
			'lead_id.required' => 'Please select lead',
			'message.required' => 'Please enter message',
		]);

		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}

		$user = auth()->user();

		$lead = Leads::select("*")
			->where("id", $request->lead_id)
			->where("assigned_to", $user->id)
			->first();

		if (empty($lead)) {
			return $this->response("Lead not found or not assigned to you.", true);
		}

		$filename = "";

		if ($request->hasfile('attachment')) {
			$filename = $this->upload_file($request->file('attachment'), "attachments", "attachment_");
		}

		$followup_date = null;

		if ($request->followup_date && $request->followup_date != "") {
			//$followup_date = date("Y-m-d", strtotime($request->followup_date)); //$this->toUTCDateYMD("{$request->followup_date} 00:00:00", true);
			$followup_date = $this->toUTCDateYMD("{$request->followup_date} 00:00:00", true);

			Leads::where("id", $request->lead_id)->update(array(
				"followup_date" => $followup_date
			));
		}

		LeadsHistory::insert(array(
			"lead_id" => $request->lead_id,
			"user_id" => $user->id,
			"assigned_by" => -1,
			"message" => $request->message,
			"followup_date" => $followup_date,
			"attachment" => $filename,
		));

		return $this->response("Lead updated successfully", false);
	}

	/************** SALES USERS END *************/

	public function lead_save(Request $request)
	{

		$validator = Validator::make($request->all(), [
			'name' => 'required',
			'phone' => 'required',
			'email' => 'required',
			'city' => 'required',
		], [
			'name.required' => 'Please enter name',
			'phone.required' => 'Please enter phone number',
			'email.required' => 'Please enter email address',
			'city.required' => 'Please enter city',
		]);

		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}

		/*$count = Leads::where("email", $request->email)->count();
		
		if($count!=0){
			return $this->response("Email address is already registered",true);
		}
		
		$count = Leads::where("phone", $request->phone)->count();
		
		if($count!=0){
			return $this->response("Phone number is already registered",true);
		}*/

		$has = Leads::select("*");

		if ($request->email) {
			$has->where("email", $request->email);
		}

		if ($request->phone) {
			$has->orWhereRaw("REPLACE(phone,'+','')='{$request->phone}'");
		}

		$has = $has->orderBy("id", "ASC")->first();

		if ($has) {
			$oneHourAgo = Carbon::now('UTC')->subHour();

			$exist = Leads::select("*")->where('created_at', '>=', $oneHourAgo);

			if ($request->email || $request->phone) {
				$exist->where(function ($w) use ($request) {
					if ($request->email) {
						$w->where("email", $request->email);
					}
					if ($request->phone) {
						$w->orWhereRaw("REPLACE(phone,'+','')='{$request->phone}'");
					}
				});
			}

			$exist = $exist->exists();

			if ($exist) {
				return $this->response("Email or phone number is already registered", true);
			}
		}

		$old_lead_id = $has->id ?? -1;

		$water_type = $request->water_type ?? -1;
		if ($request->type == 17 || $request->type == 18 || $request->type == 19) {
			$water_type = 2;
		}

		$interest_in = $request->interest_in ?? "";
		$intersted_in_leads = $request->intersted_in_leads ?? "";
		if ($interest_in == "" && $intersted_in_leads != '') {
			$interest_in = isset($this->interest_in_wts[$intersted_in_leads]) ? $this->interest_in_wts[$intersted_in_leads] : "";
		}

		Leads::create(array(
			"name" => $request->name,
			"phone" => $request->phone,
			"email" => $request->email,
			"city" => $request->city,
			"message" => $request->message ?? "",
			"type" => $request->type ?? -1,

			"address" => $request->address ?? "",
			"post_code" => $request->post_code ?? "",
			"date_time" => $request->date_time ? $this->toUTCDateYMD($request->date_time, false, "Y-m-d H:i:s") : null,

			"friend_name" => $request->frd_name  ?? "",
			"friend_email" => $request->frd_email ?? "",
			"friend_phone" => $request->frd_phone ?? "",
			"friend_city" => $request->frd_city ?? "",

			"water_type" => $water_type,
			"interest_in" => $interest_in,
			"problem_with_your_water" => $request->problem_with_your_water ?? "",
			"utm_source" => $request->utm_source ?? "",

			"cust_type" => -1,
			"assigned_to" => -1,
			"created_by" => -1,
			"status" => 0,
			"water_system_issues" => "",
			"hear_about" => $request->hear_about ?? "",
		));

		if ($old_lead_id != -1) {
			LeadsDuplicateTracking::create([
				"lead_id" => $old_lead_id,
				"duplicate_lead_id" => $lead->id
			]);

			$has->is_duplicate = 1;
			$has->save();
		}

		return $this->response("Lead saved successfully", false);
	}

	public function add_sales_channel(Request $request)
	{

		$validator = Validator::make($request->all(), [
			'name' => 'required',
		], [
			'name.required' => 'please enter sales channel name',
		]);

		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}

		SalesChannels::create([
			"name" => $request->name
		]);

		return $this->response("Sales channel  created successfully", false);
	}

	public function update_sales_channel($id, Request $request)
	{

		$validator = Validator::make($request->all(), [
			'name' => 'required',
		], [
			'name.required' => 'please enter sales channel name',
		]);

		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}

		$sales = SalesChannels::where("id", $id)->first();

		if (!$sales) {
			return $this->response("Sales channel not found", true);
		}

		$sales->update([
			"name" => $request->name,
		]);

		return $this->response("Sales channel has been updated successfully", false);
	}

	public function sales_channel_detail($id, Request $request)
	{

		$sales = SalesChannels::where("id", $id)->first();

		if (!$sales) {
			return $this->response("Sales channel not found", true);
		}

		return $this->response("", false, $sales);
	}

	public function delete_sales_channel($id)
	{

		$sales = SalesChannels::where("id", $id)->first();

		if (!$sales) {
			return $this->response("Sales channel not found", true);
		}

		$sales->delete();

		return $this->response("Sales channel deleted successfully", false);
	}

	public function get_all_sales_channel(Request $request)
	{

		$list = SalesChannels::select("*")->orderBy("name", "ASC")->get();

		return $this->response("", false, $list);
	}

	public function get_all_products(Request $request)
	{

		$list = Product::select("*")->orderBy("sort_order", "ASC")->get();

		return $this->response("", false, $list);
	}

	public function sales_channel_list(Request $request)
	{

		$list =	SalesChannels::select("*");

		if ($request->has('search') && $request->search != '') {
			$searchTerm = $request->search;
			$list->where(function ($query) use ($searchTerm) {
				$query->where("name", "LIKE", "%{$searchTerm}%");
			});
		}

		$list->orderBy("id", "DESC");

		$list = $list->paginate(20);

		return $this->response("", false, $list);
	}

	public function send_estimate(Request $request)
	{

		$validator = Validator::make($request->all(), [
			'id' => 'required|integer',
			'email' => 'required|email',
			'content' => 'required',
		], [
			'id.required' => 'Please enter id',
			'id.integer' => 'Entered id must be Numaric',
			'email.required' => 'Please enter email address',
			'content.required' => 'Please enter content',
		]);

		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}

		$user =  auth()->user();

		$leads = Leads::where("id", $request->id)->first();

		if (!$leads) {
			return $this->response("Lead detail not found", true);
		}

		$leads->update([
			"email" => $request->email,
		]);

		$attach_file_names = [];
		$filenames = [];

		if ($request->hasFile("attachment")) {

			foreach ($request->file("attachment") as $file) {

				$filename = $this->upload_file($file, "attachments", "attachment_");

				$filepath = public_path('storage/attachments/' . $filename);

				if (file_exists($filepath)) {
					$filenames[] = $filepath;
					$attach_file_names[] = basename($filepath);
				}
			}
		}

		$first_attachment =  $attach_file_names[0] ?? "";

		$mail_content = str_replace("<p>&nbsp;</p>", "", $request->content);

		$leadHistry = LeadsHistory::create([
			"lead_id" => $request->id,
			"user_id" => $user->id,
			"assigned_by" => -1,
			"message" => "Estimate has been sent to <a href='mailto:{$request->email}'><strong>{$request->email}</strong></a><br /><br /><strong>Email Content:</strong><br />{$mail_content}",
			"attachment" =>  $first_attachment,
		]);

		foreach ($attach_file_names as $index => $att) {

			if ($index == 0) {
				continue;
			}

			$leadHistry = LeadsHistory::create([
				"lead_id" => $request->id,
				"user_id" => $user->id,
				"assigned_by" => -1,
				"message" => "",
				"attachment" =>  $att,
			]);
		}

		Mail::send('emails.estimate-detail', ['name' => $leads->name, 'content' => $mail_content], function ($message) use ($request, $filenames) {
			$message->to($request->email);
			$message->subject("Estimate from Kent Water Purification Systems");
			if (count($filenames) > 0) {
				foreach ($filenames as $file) {
					$message->attach($file);
				}
			}
		});

		return $this->response("Estimate details send to entered email address", false);
	}

	public function installation_compete($id, Request $request)
	{

		$validator = Validator::make($request->all(), [
			'water_test_date' => 'required|date',
			'installation_date' => 'required|date',
		], [
			'installation_date.required' => 'Please enter Installation date.',
			'installation_date.date' => 'Installation date must be a valid date.',
			'water_test_date.required' => 'Please enter Water test date.',
			'water_test_date .date' => 'Water test date must be a valid date.',
		]);

		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}

		$user =  auth()->user();

		$lead = Leads::where("id", $id)->first();

		if (!$lead) {
			return $this->response("Lead Not found", true);
		}

		$lead->update([
			"installation_date" => $this->toUTCDateYMD("{$request->installation_date} 00:00:00", true, "Y-m-d"),
			"water_test_date" => $this->toUTCDateYMD("{$request->water_test_date} 00:00:00", true, "Y-m-d"),
			"installation_status" => 1,
		]);

		$installation_date = $this->toUTCDateYMD("{$request->installation_date} 00:00:00", true, "d-m-Y");
		$water_test_date = $this->toUTCDateYMD("{$request->water_test_date} 00:00:00", true, "d-m-Y");

		$leadHistry = LeadsHistory::create([
			"lead_id" => $id,
			"user_id" => $user->id,
			"assigned_by" => -1,
			"message" => "Installation completed by <TAGSYSTEM>installation_compete:{$user->id}</TAGSYSTEM>, <br /><strong>Installation Date:</strong> {$installation_date} <br /><strong>Water Test Date:</strong> {$water_test_date}",
			"attachment" => "",
		]);

		return $this->response("Installation completed successfully", false);
	}

	public function water_test_compete($id, Request $request)
	{

		$validator = Validator::make($request->all(), [
			'water_test_date' => 'required|date',
		], [
			'water_test_date.required' => 'Please enter Water test date.',
			'water_test_date .date' => 'Water test date must be a valid date.',
		]);

		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}

		$user =  auth()->user();

		$lead = Leads::where("id", $id)->first();

		if (!$lead) {
			return $this->response("Lead Not found", true);
		}

		$lead->update([
			"water_test_date" => $this->toUTCDateYMD($request->water_test_date, true, "Y-m-d"),
			"water_test_status" => 1,
		]);

		$water_test_date = $this->toUTCDateYMD("{$request->water_test_date} 00:00:00", true, "d-m-Y");

		$leadHistry = LeadsHistory::create([
			"lead_id" => $id,
			"user_id" => $user->id,
			"assigned_by" => -1,
			"message" => "Water Test completed by <TAGSYSTEM>water_test_compete:{$user->id}</TAGSYSTEM>, <br /><strong>Water Test Date:</strong> {$water_test_date}",
			"attachment" => "",
		]);

		return $this->response("Water test completed successfully", false);
	}

	public function get_pending_installations(Request $request)
	{

		$user =  auth()->user();

		$mstart_date = date("Y-m");

		if ($request->has("month")) {
			$mstart_date = date("Y-m", strtotime("01-{$request->month}"));
		}

		$pending_installations =  Leads::whereRaw("DATE_FORMAT(installation_date,'%Y-%m')='{$mstart_date}'");

		$pending_installations->where("status", 3);
		$pending_installations->whereIn("installation_status", [-1, 0]);
		$pending_installations->orderBy('installation_date', 'ASC');

		if ($user->role == 2) {
			$pending_installations->where("assigned_to", $user->id);
		}

		$pending_installations = $pending_installations->get();

		foreach ($pending_installations as $val) {
			$val->installation_date_dmy = date("d-m-Y", strtotime($val->installation_date));
		}

		return $this->response("", false, $pending_installations);
	}

	public function get_pending_water_test(Request $request)
	{

		$user =  auth()->user();

		$mstart_date = date("Y-m");

		if ($request->has("month")) {
			$mstart_date = date("Y-m", strtotime("01-{$request->month}"));
		}

		$pending_water_test =  Leads::whereRaw("DATE_FORMAT(water_test_date,'%Y-%m')='{$mstart_date}'");

		$pending_water_test->where("status", 3);
		$pending_water_test->where("installation_status", 1);
		$pending_water_test->whereIn("water_test_status", [-1, 0]);
		$pending_water_test->orderBy('water_test_date', 'ASC');

		if ($user->role == 2) {
			$pending_water_test->where("assigned_to", $user->id);
		}

		$pending_water_test = $pending_water_test->get();

		foreach ($pending_water_test as $val) {
			$val->water_test_date_dmy = date("d-m-Y", strtotime($val->water_test_date));
		}

		return $this->response("", false, $pending_water_test);
	}

	public function get_upcoming_followups(Request $request)
	{

		$user =  auth()->user();

		$mstart_date = date("Y-m");

		if ($request->has("month")) {
			$mstart_date = date("Y-m", strtotime("01-{$request->month}"));
		}

		$pending_water_test =  Leads::whereRaw("DATE_FORMAT(followup_date,'%Y-%m')='{$mstart_date}'");

		$pending_water_test->orderBy('followup_date', 'ASC');

		if ($user->role == 2) {
			$pending_water_test->where("assigned_to", $user->id);
		}

		$pending_water_test = $pending_water_test->get();

		foreach ($pending_water_test as $val) {
			$val->water_test_date_dmy = date("d-m-Y", strtotime($val->followup_date));
		}

		return $this->response("", false, $pending_water_test);
	}

	private function lead_count_by_type($leads, $type, $source)
	{
		if ($source == 'other') {
			return collect($leads)->filter(function ($val) use ($type) {
				$utmSource = strtoupper(trim($val->utm_source));
				return $val->type == $type && ($utmSource !== 'SEO' && $utmSource !== 'GOOGLE');
			})->count();
		} else {
			return collect($leads)->filter(function ($val) use ($type, $source) {
				if (strtoupper($source) == "SEO") {
					return $val->type == $type && (strtoupper(trim($val->utm_source)) == strtoupper($source) || trim($val->utm_source) == '');
				}
				return $val->type == $type && strtoupper(trim($val->utm_source)) == strtoupper($source);
			})->count();
		}
	}

	private function lead_count_by_hear_about($leads, $type, $hear_about)
	{
		return collect($leads)->filter(function ($val) use ($hear_about, $type) {
			return strtoupper(trim($val->hear_about)) == strtoupper($hear_about) && $val->type == $type;
		})->count();
	}

	public function update_ads_spends(Request $request)
	{

		$validator = Validator::make($request->all(), [
			'amount' => 'required',
			'month' => 'required',
		], [
			'amount.required' => 'Please enter Amount.',
			'month.required' => 'Please enter date.',
		]);

		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}


		$date = date("Y-m", strtotime("01-{$request->month}"));

		$ads_spend = AdsSpends::whereRaw("DATE_FORMAT(date,'%Y-%m')='{$date}'")->first();

		if (!$ads_spend) {

			AdsSpends::create([
				"date" => "{$date}-01",
				"amount" => $request->amount
			]);
		} else {
			AdsSpends::where("id", $ads_spend->id)->update([
				"amount" => $request->amount
			]);
		}

		return $this->response("Ads spend Updated successfully", false);
	}

	public function leads_statistics(Request $request)
	{

		$sales_channel = SalesChannels::select("name")->orderBy("name", "ASC")->get();

		$mstart_date = Carbon::now('America/Toronto')->startOfDay()->setTimezone('UTC')->format("Y-m-01 H:i:s");
		$mend_date = Carbon::now('America/Toronto')->endOfDay()->setTimezone('UTC')->format("Y-m-t H:i:s");

		//$mstart_date = date("Y-m");
		if ($request->has("month")) {
			// $mstart_date = Carbon::createFromFormat('d-M-Y', "01-{$request->month}",'America/Toronto')->startOfDay()->setTimezone('UTC')->format("Y-m-d H:i:s");
			// $mend_date = Carbon::createFromFormat('d-M-Y', "31-{$request->month}",'America/Toronto')->startOfDay()->setTimezone('UTC')->format("Y-m-d H:i:s");

			$newdate = Carbon::createFromFormat('M-Y', $request->month, 'America/Toronto');
			$mstart_date = $newdate->copy()->startOfMonth()->startOfDay()->setTimezone('UTC')->format("Y-m-d H:i:s");
			$mend_date = $newdate->copy()->endOfMonth()->endOfDay()->setTimezone('UTC')->format("Y-m-d H:i:s");
			$ads_mstart_date = date("Y-m", strtotime("01-{$request->month}"));
		}
		// dd($mstart_date.' '.$mend_date);

		// $leads = Leads::whereRaw("created_at>='{$mstart_date}' AND created_at<='{$mend_date}'")->get();
		$leads = Leads::whereRaw("created_at BETWEEN '{$mstart_date}' AND '{$mend_date}' ")->get();

		$adspend = AdsSpends::whereRaw("DATE_FORMAT(date,'%Y-%m')='{$ads_mstart_date}'")->first();

		$source_arr = [];
		$source_arr[] = array("name" => "Book Water Test", "type" => 0);
		$source_arr[] = array("name" => "Contact Page", "type" => 1);
		$source_arr[] = array("name" => "Refer a Friend", "type" => 4);
		//$source_arr[] = array("name"=>"Inbound Call", "type"=>7);
		$source_arr[] = array("name" => "WS", "type" => 8);
		$source_arr[] = array("name" => "Whole House Water - WS", "type" => 9);
		$source_arr[] = array("name" => "Well Water - WS", "type" => 10);
		$source_arr[] = array("name" => "WF", "type" => 11);
		$source_arr[] = array("name" => "Whole Home", "type" => 12);
		$source_arr[] = array("name" => "Well Water - WF", "type" => 13);
		$source_arr[] = array("name" => "Reverse Osmosis", "type" => 14);
		$source_arr[] = array("name" => "Plans & Pricing", "type" => 15);
		$source_arr[] = array("name" => "Water Testing Services", "type" => 16);
		$source_arr[] = array("name" => "Brampton - LP", "type" => 17);
		$source_arr[] = array("name" => "Cambridge - LP", "type" => 18);
		$source_arr[] = array("name" => "Mississauga - LP", "type" => 19);
		$source_arr[] = array("name" => "WF Rental", "type" => 20);
		$source_arr[] = array("name" => "RO Rental", "type" => 21);
		$source_arr[] = array("name" => "WS Rental", "type" => 22);
		// $source_arr[] = array("name"=>"Total Leads", "type"=>-1);
		$source_arr[] = array("name" => "WTS", "type" => 5);
		$source_arr[] = array("name" => "RO", "type" => 6);
		$source_arr[] = array("name" => "Popup", "type" => 2);
		$source_arr[] = array("name" => "Home Popup", "type" => 3);


		$arr = [];

		foreach ($source_arr as $source) {
			$ar = [];
			$ar["name"] = $source["name"];
			$ar["type"] = $source["type"];

			$seo_leads = $this->lead_count_by_type($leads, $source["type"], "seo");
			$google_ads_leads = $this->lead_count_by_type($leads, $source["type"], "google");
			$other_ads_leads = $this->lead_count_by_type($leads, $source["type"], "other");

			$ar["seo_leads"] = $seo_leads;
			$ar["google_ads_leads"] = $google_ads_leads;
			$ar["other_ads_leads"] = $other_ads_leads;
			$ar["total"] = $seo_leads + $google_ads_leads + $other_ads_leads;
			array_push($arr, $ar);
		}

		$headings = [];
		$headings[] = array("name" => "Page Name", "key" => "name");
		$headings[] = array("name" => "SEO", "key" => "seo_leads");
		$headings[] = array("name" => "Google Ads", "key" => "google_ads_leads");
		$headings[] = array("name" => "Other Ads", "key" => "other_ads_leads");
		$headings[] = array("name" => "Total", "key" => "total");

		$collect = collect($arr);

		$keysToRemove = collect($collect->first())
			->keys()
			->filter(function ($key) use ($collect) {
				return $collect->pluck($key)->every(function ($value) {
					return $value === 0;
				});
			});

		/*$arr = $collect->map(function ($item) use ($keysToRemove) {
			$filtered = collect($item)->except($keysToRemove);
			
			$total = $filtered->filter(function ($value, $key) {
				return is_numeric($value) && $key!='type';
			})->sum();
			
			$filtered->put('total', $total);
			
			return $filtered->all();
		});*/

		$vseototal = 0;
		$vgoogletotal = 0;
		$vothertotal = 0;
		foreach ($arr as $val) {
			$vseototal += $val["seo_leads"];
			$vgoogletotal += $val["google_ads_leads"];
			$vothertotal += $val["other_ads_leads"];
		}

		$arr[] = array(
			"name" => "Total",
			"type" => -1,
			"seo_leads" => $vseototal,
			"google_ads_leads" => $vgoogletotal,
			"other_ads_leads" => $vothertotal,
			"total" => $vseototal + $vgoogletotal + $vothertotal
		);

		$sc_leads = [];

		$totalsc_leads = 0;
		foreach ($sales_channel as $val) {
			$name = preg_replace('/[^a-zA-Z0-9_.]/', '_', $val->name);
			$name = strtolower($name);

			$lc = $this->lead_count_by_hear_about($leads, 7, $val->name);
			$sc_leads[] = array("name" => $val->name, "leads" => $lc);
			$totalsc_leads += $lc;
		}

		// $all_sc_count = Leads::whereRaw("created_at>='{$mstart_date}' AND created_at<='{$mend_date}' AND type=7")->count();
		$all_sc_count = Leads::whereRaw("created_at BETWEEN '{$mstart_date}' AND '{$mend_date}' AND type=7")->count();

		$sc_leads[] = array("name" => "No Sales Channel", "leads" => $all_sc_count - $totalsc_leads);
		$sc_leads[] = array("name" => "Total Leads", "leads" => $all_sc_count);

		$data = [];
		$data["google_ads_spend"] = $adspend->amount ?? 0;
		$data["headings"] = $headings;
		$data["sales_channel"] = $sc_leads;
		$data["rows"] = $arr;
		$data["total_leads"] = count($leads);

		return $this->response("", false, $data);
	}

	public function export_leads(Request $request)
	{

		// $validator = Validator::make($request->all(), [
		//     'from' => 'required',
		//     'to' => 'required',
		// ],[
		//    'from.required' => 'Please select start date',
		//    'to.required' => 'Please select end date',
		// ]);

		// if($validator->fails()){
		// 	return $validator->errors()->first();
		// }

		ini_set('max_execution_time', 0);
		ini_set('memory_limit', '400000M');

		$user =  auth()->user();

		if ($user->role != 0) {
			return "Access Denied";
		}

		$data_array = [];

		$data_array[] = array(
			"Created Date",
			"Name",
			"Email",
			"Phone",
			"Status",
			"Source",
			"Revenue",
			"Page",
			"City",
			"Water Type",
			"Intrested In",
			"Problem with Water",
			"Message",
			"Customer Type",
			"Post Code",
			"Address",
			"Date/Time",
			"Follow Up Date",
			"Friend Name",
			"Friend Email",
			"Friend Phone",
			"Friend City",
			"Closed Reason",
			"Installation Status",
			"Installation Date",
			"Water Test Status",
			"Water Test Date"
		);

		// $start_date = $this->toUTCDateYMD("{$request->from} 00:00:00", true);
		// $end_date = $this->toUTCDateYMD("{$request->to} 23:59:59", true);

		// $leads = Leads::whereRaw("DATE(created_at)>='{$start_date}' AND DATE(created_at)<='{$end_date}'")->orderBy("id", "DESC")->get();

		$leads = Leads::select([
			"*"
		]);

		if (
			$request->has('source')
			&& $request->source != ''
			&& $request->source != -1
		) {
			$leads->where('leads.type', '=', $request->source);
		}

		if (
			$request->has('status') && $request->status == -2
		) {
			$leads->whereIn('leads.status', [0, 1]);
		} else if (
			$request->has('status')
			&& $request->status != ''
			&& $request->status != -1
		) {
			$leads->where('leads.status', '=', $request->status);
		}

		if (
			$request->has('user_id')
			&& $request->user_id != ''
			&& $request->user_id != -1
		) {
			$leads->where('leads.assigned_to', '=', $request->user_id);
		}

		if (
			$request->has('start_date')
			&& $request->start_date != ''
			&& $request->has('end_date')
			&& $request->end_date != ''
		) {
			/*$start_date = $this->toUTCDateYMD("{$request->start_date} 00:00:00", true);
			$end_date = $this->toUTCDateYMD("{$request->end_date} 23:59:59", true);*/
			$start_date = Carbon::createFromFormat('d-m-Y', "{$request->start_date}", 'America/Toronto')->startOfDay()->setTimezone('UTC')->format("Y-m-d H:i:s");
			$end_date = Carbon::createFromFormat('d-m-Y', "{$request->end_date}", 'America/Toronto')->endOfDay()->setTimezone('UTC')->format("Y-m-d H:i:s");
			// $leads->whereRaw("DATE(created_at)>='{$start_date}' AND DATE(created_at)<='{$end_date}'");
			$leads->whereRaw("created_at BETWEEN '{$start_date}' AND '{$end_date}' ");
		}

		$leads = $leads->orderBy("id", "DESC")->get();

		$leads->map(function ($item) use (&$data_array) {

			$status_str = 'Pending';

			if ($item->status == 1) {
				$status_str = 'Assigned';
			} else if ($item->status == 2) {
				$status_str = 'Dropped';
			} else if ($item->status == 3) {
				$status_str = 'Converted';
			}

			$page = '';

			if ($item->type == 0) {
				$page = "Book Water Test";
			} else if ($item->type == 1) {
				$page = "Contact Page";
			} else if ($item->type == 2) {
				$page = "Popup";
			} else if ($item->type == 3) {
				$page = "Home Popup";
			} else if ($item->type == 4) {
				$page = "Friend";
			} else if ($item->type == 5) {
				$page = "WTS";
			} else if ($item->type == 6) {
				$page = "RO";
			} else if ($item->type == 7) {
				$page = $item->hear_about;
			} else if ($item->type == 8) {
				$page = "WS";
			} else if ($item->type == 9) {
				$page = "Whole House Water (WS)";
			} else if ($item->type == 10) {
				$page = "Well Water - (WS)";
			} else if ($item->type == 11) {
				$page = "WF";
			} else if ($item->type == 12) {
				$page = "Whole Home - (WF)";
			} else if ($item->type == 13) {
				$page = "Well Water - (WF)";
			} else if ($item->type == 14) {
				$page = "Reverse Osmosis - (WF)";
			} else if ($item->type == 15) {
				$page = "Plans & Pricing";
			} else if ($item->type == 16) {
				$page = "Water Testing Services";
			} else if ($item->type == 17) {
				$page = "WF - Brampton";
			} else if ($item->type == 18) {
				$page = "WF - Cambridge";
			} else if ($item->type == 19) {
				$page = "WF - Mississauga";
			} else if ($item->type == 20) {
				$page = "WF Rental";
			} else if ($item->type == 21) {
				$page = "RO Rental";
			} else if ($item->type == 22) {
				$page = "WS Rental";
			}

			$arr = [];
			$arr["Created Date"] = $this->toLocalDate($item->created_at);
			$arr["Name"] = $item->name;
			$arr["Email"] = $item->email;
			$arr["Phone"] = $item->phone;
			$arr["Status"] = $status_str;

			// $arr["Source"] = strtoupper($item->utm_source)=="GOOGLE" ? "Google Ads" : "SEO";
			$utm_source = "SEO";
			if (!empty($item->utm_source)) {
				if (strtoupper($item->utm_source) == "GOOGLE" || strtoupper($item->utm_source) == "GOOGLE ADS") {
					$utm_source = "Google Ads";
				} else {
					$utm_source = $item->utm_source;
				}
			}
			$arr["Source"] = $utm_source;
			$arr["Revenue"] = $item->revenue;
			$arr["Page"] = $page;

			$pw = [];

			$arrx = explode(",", $item->problem_with_your_water);

			foreach ($arrx as $val) {

				$l = $this->problems_with_water[$val] ?? "";

				if ($l) {
					array_push($pw, $l);
				}
			}

			$pwin = [];

			$arrx = explode(",", $item->interest_in);

			foreach ($arrx as $val) {

				if (
					$item->type == 5 || $item->type == 8 || $item->type == 9 || $item->type == 10 || $item->type == 11 || $item->type == 12 || $item->type == 13
					|| $item->type == 14 || $item->type == 15 || $item->type == 17 || $item->type == 18 || $item->type == 19 || $item->type == 20 || $item->type == 21 || $item->type == 22
				) {
					$l = $this->interest_in_wts[$val] ?? "";
				} else {
					// 0-4 and 16 will be shown from here
					$l = $this->interest_in[$val] ?? "";
				}

				if ($l) {
					array_push($pwin, $l);
				}
			}

			$arr["City"] = $item->city;
			$arr["Water Type"] = $this->water_type[$item->water_type] ?? "";
			$arr["Intrested In"] = implode(", ", $pwin);

			$arr["Problem with Water"] = implode(", ", $pw);
			$arr["Message"] = $item->message;
			$arr["Customer Type"] = $this->cust_type[$item->cust_type] ?? "";
			$arr["Post Code"] = $item->post_code;
			$arr["Address"] = $item->address;
			$arr["Date/Time"] = $item->date_time ? $this->toLocalDate($item->date_time) : "";
			$arr["Follow Up Date"] = $item->followup_date ? $this->toLocalDate($item->followup_date) : "";
			$arr["Friend Name"] = $item->friend_name;
			$arr["Friend Email"] = $item->friend_email;
			$arr["Friend Phone"] = $item->friend_phone;
			$arr["Friend City"] = $item->friend_city;
			$arr["Closed Reason"] = $item->closed_reason;
			$arr["Installation Status"] = $item->installation_status == 0 ? "Pending" : ($item->installation_status == 1 ? "Completed" : "");
			$arr["Installation Date"] = $item->installation_status == 1 ? $this->toLocalDate($item->installation_date) : "";
			$arr["Water Test Status"] = $item->water_test_status == 0 ? "Pending" : ($item->water_test_status == 1 ? "Completed" : "");
			$arr["Water Test Date"] = $item->water_test_status == 1 ? $this->toLocalDate($item->water_test_date) : "";

			$data_array[] = $arr;
		});

		try {
			$spreadSheet = new Spreadsheet();

			$spreadSheet->getActiveSheet()->getRowDimension('1')->setRowHeight(30, 'px');

			$spreadSheet->getActiveSheet()->getStyle('A1:AB1')->getFont()->setBold(true);
			//$spreadSheet->getActiveSheet()->getStyle('A')->getFont()->setBold(true);

			$spreadSheet->getActiveSheet()->getStyle('D')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
			$spreadSheet->getActiveSheet()->getStyle('G')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
			$spreadSheet->getActiveSheet()->getStyle('A')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
			//$spreadSheet->getActiveSheet()->getStyle('A')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

			$spreadSheet->getActiveSheet()->getStyle('A1:AB1')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
			$spreadSheet->getActiveSheet()->getStyle('A1:AB1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

			$spreadSheet->getActiveSheet()->getColumnDimension("A")->setAutoSize(true);
			$spreadSheet->getActiveSheet()->getColumnDimension("B")->setAutoSize(true);
			$spreadSheet->getActiveSheet()->getColumnDimension("C")->setAutoSize(true);
			$spreadSheet->getActiveSheet()->getColumnDimension("D")->setAutoSize(true);
			$spreadSheet->getActiveSheet()->getColumnDimension("E")->setAutoSize(true);
			$spreadSheet->getActiveSheet()->getColumnDimension("F")->setAutoSize(true);
			$spreadSheet->getActiveSheet()->getColumnDimension("G")->setAutoSize(true);
			$spreadSheet->getActiveSheet()->getColumnDimension("H")->setAutoSize(true);
			$spreadSheet->getActiveSheet()->getColumnDimension("I")->setAutoSize(true);
			$spreadSheet->getActiveSheet()->getColumnDimension("J")->setAutoSize(true);
			$spreadSheet->getActiveSheet()->getColumnDimension("K")->setAutoSize(true);
			$spreadSheet->getActiveSheet()->getColumnDimension("L")->setAutoSize(true);
			$spreadSheet->getActiveSheet()->getColumnDimension("M")->setAutoSize(true);
			$spreadSheet->getActiveSheet()->getColumnDimension("N")->setAutoSize(true);
			$spreadSheet->getActiveSheet()->getColumnDimension("O")->setAutoSize(true);
			$spreadSheet->getActiveSheet()->getColumnDimension("P")->setAutoSize(true);
			$spreadSheet->getActiveSheet()->getColumnDimension("Q")->setAutoSize(true);
			$spreadSheet->getActiveSheet()->getColumnDimension("R")->setAutoSize(true);
			$spreadSheet->getActiveSheet()->getColumnDimension("S")->setAutoSize(true);
			$spreadSheet->getActiveSheet()->getColumnDimension("T")->setAutoSize(true);
			$spreadSheet->getActiveSheet()->getColumnDimension("U")->setAutoSize(true);
			$spreadSheet->getActiveSheet()->getColumnDimension("V")->setAutoSize(true);
			$spreadSheet->getActiveSheet()->getColumnDimension("W")->setAutoSize(true);
			$spreadSheet->getActiveSheet()->getColumnDimension("X")->setAutoSize(true);
			$spreadSheet->getActiveSheet()->getColumnDimension("Y")->setAutoSize(true);
			$spreadSheet->getActiveSheet()->getColumnDimension("Z")->setAutoSize(true);
			$spreadSheet->getActiveSheet()->getColumnDimension("AA")->setAutoSize(true);

			$spreadSheet->getActiveSheet()->fromArray($data_array);

			$Excel_writer = new Xls($spreadSheet);
			header('Access-Control-Allow-Origin: *');
			header('Content-Type: application/vnd.ms-excel');
			header('Content-Disposition: attachment;filename="list.xls"');
			header('Cache-Control: max-age=0');

			$Excel_writer->save('php://output');
			exit();
		} catch (Exception $e) {
			return;
		}
	}

	public function get_admin_duplicate_leads(Request $request)
	{

		$user = auth()->user();

		if ($user->role == 2)
			return $this->response("Access denied", true);

		$field_has_comment = "IF( (SELECT COUNT(DISTINCT CASE WHEN LH.message LIKE 'Lead assigned by%' THEN 'Lead assigned' ELSE LH.id END) FROM leads_history AS LH WHERE LH.lead_id=leads.id)<=2, 0, 1)";

		$list = Leads::select([
			"*",
			DB::raw("IF(status>=2, 1, $field_has_comment) as has_comments")
		])->whereRaw("id IN(SELECT LDT.lead_id FROM leads_duplicate_tracker AS LDT)");

		if (
			$request->has('source')
			&& $request->source != ''
			&& $request->source != -1
		) {
			$list->where('leads.type', '=', $request->source);
		}

		if (
			$request->has('utm_source')
			&& $request->utm_source != ''
			&& $request->utm_source != -1
		) {
			$list->where('leads.utm_source', '=', $request->utm_source);
		}


		if (
			$request->has('status') && $request->status == -2
		) {
			$list->whereIn('leads.status', [0, 1]);
		} else if (
			$request->has('status')
			&& $request->status != ''
			&& $request->status != -1
		) {
			$list->where('leads.status', '=', $request->status);
		}

		if (
			$request->has('user_id')
			&& $request->user_id != ''
			&& $request->user_id != -1
		) {
			$list->where('leads.assigned_to', '=', $request->user_id);
		}

		if (
			$request->has('start_date')
			&& $request->start_date != ''
			&& $request->has('end_date')
			&& $request->end_date != ''
		) {
			$start_date = date("Y-m-d", strtotime($request->start_date)); // $this->toUTCDateYMD("{$request->start_date}", true);
			$end_date = date("Y-m-d", strtotime($request->end_date)); //$this->toUTCDateYMD("{$request->end_date}", true);

			$list->whereRaw("DATE(created_at)>='{$start_date}' AND DATE(created_at)<='{$end_date}'");
		}

		if ($request->has('search') && $request->search != '') {
			$list->where(function ($where) use ($request) {
				$where->where('leads.name', 'LIKE', '%' . $request->search . '%');
				$where->orwhere('leads.email', 'LIKE', '%' . $request->search . '%');
				$where->orwhere('leads.phone', 'LIKE', '%' . $request->search . '%');
				$where->orwhere('leads.city', 'LIKE', '%' . $request->search . '%');
				$where->orwhere('leads.utm_source', 'LIKE', '%' . $request->search . '%');
			});
		}

		$list->orderBy("id", "DESC");

		$list = $list->paginate(100);

		foreach ($list as $val) {
			$val->date = $this->toLocalDate($val->created_at);
			$val->date_dmy = date("d-m-Y", strtotime($val->created_at));
			if (!empty($val->phone)) {
				$val->phone = str_replace(' ', '', $val->phone);
			}
		}

		return $list;
	}

	public function duplicate_leads_combined($lead_id)
	{

		$duplicate_lead_ids = LeadsDuplicateTracking::where("lead_id", $lead_id)->pluck("duplicate_lead_id")->toArray();
		$lead_ids = LeadsDuplicateTracking::where("duplicate_lead_id", $lead_id)->pluck("lead_id")->toArray();

		$all_id = array_merge($duplicate_lead_ids, $lead_ids);

		$dlist = LeadsDuplicateTracking::whereIn("lead_id", $all_id)->orWhereIn("duplicate_lead_id", $all_id)->get()->toArray();

		$arr_li = array_column($dlist, "lead_id");
		$arr_dli = array_column($dlist, "duplicate_lead_id");

		$all_id = array_merge($arr_li, $arr_dli);

		$field_has_comment = "IF( (SELECT COUNT(DISTINCT CASE WHEN LH.message LIKE 'Lead assigned by%' THEN 'Lead assigned' ELSE LH.id END) FROM leads_history AS LH WHERE LH.lead_id=leads.id)<=2, 0, 1)";

		$list = Leads::select([
			"*",
			DB::raw("IF(status>=2, 1, $field_has_comment) as has_comments")
		])->whereIn("id", $all_id);

		$list->orderBy("id", "DESC");
		$list = $list->get();

		foreach ($list as $val) {
			$val->date = $this->toLocalDate($val->created_at);
		}

		return $this->response("", false, $list);
	}













	// Suppliers 
	public function get_suppliers()
	{
		$supplierList = Supplier::leftJoin('spare_parts', function ($join) {
			$join->on(DB::raw("FIND_IN_SET(spare_parts.id, supplier.spare_part_ids)"), '>', DB::raw('0'));
		})
			->select(
				'supplier.*',
				DB::raw('GROUP_CONCAT(spare_parts.part_name SEPARATOR " | ") as spare_part_names ')
			)
			->groupBy('supplier.id')
			->orderBy("supplier.id", "DESC")
			->paginate(100);

		if (!empty($supplierList)) {
			foreach ($supplierList as $supplier) {
				$supplier->logo_name = !empty($supplier->logo) ? $supplier->logo : NULL;
				$supplier->logo = asset('/storage/supplier/' . $supplier->logo);
			}
		}

		if (!empty($supplierList)) {
			return $this->response("", false, $supplierList);
		} else {
			return $this->response("Suppliers Not Found.", true);
		}
	}

	public function add_supplier(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'name' => 'required',
			'email' => 'required',
			'phone' => 'required',
			'address' => 'required',
			'spare_part_ids' => 'required|array',
		], [
			'name.required' => 'Please Enter Name',
			'email.required' => 'Please Enter Email',
			'phone.required' => 'Please Enter Phone Number',
			'address.required' => 'Please Enter Address',
			'spare_part_ids.required' => 'Please Select Raw Material',
			'spare_part_ids.array' => 'Raw Material must be an array',
		]);

		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}

		$fileName = NULL;
		if ($request->hasFile('logo')) {
			$file = $request->file('logo');
			$fileName = uniqid() . '.' . $file->getClientOriginalExtension();
			$path = public_path() . '/storage/supplier/';
			$file->move($path, $fileName);
		}

		$last_id = Supplier::create([
			'name' => $request->name,
			'email' => $request->email,
			'phone' => $request->phone,
			'address' => $request->address,
			'tan_number' => (!empty($request->tan_number)) ? $request->tan_number : NULL,
			'spare_part_ids' => (!empty($request->spare_part_ids)) ? implode(',', $request->spare_part_ids) : [],
			'logo' => $fileName,
		]);

		if (!empty($last_id)) {
			$this->helper->ActivityLog($last_id->id, "Add Supplier", date('Y-m-d'), json_encode($last_id), $this->user->name, "Supplier", "", "Create");
			return $this->response("Supplier Add Successfully.", false);
		} else {
			return $this->response("Supplier Add Error.", true);
		}
	}

	public function edit_supplier(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'id' => 'required',
			'name' => 'required',
			'email' => 'required',
			'phone' => 'required',
			'address' => 'required',
			'spare_part_ids' => 'required|array',
		], [
			'id.required' => 'ID Is Required',
			'name.required' => 'Please Enter Name',
			'email.required' => 'Please Enter Email',
			'phone.required' => 'Please Enter Phone Number',
			'address.required' => 'Please Enter Address',
			'spare_part_ids.required' => 'Please Select Raw Material',
			'spare_part_ids.array' => 'Raw Material must be an array',
		]);

		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}

		$fileName = "";
		if ($request->hasFile('logo')) {
			$file = $request->file('logo');
			$fileName = uniqid() . '.' . $file->getClientOriginalExtension();
			$path = public_path() . '/storage/supplier/';
			$file->move($path, $fileName);
			DB::table('supplier')->where("id", $request->id)->update([
				'logo' => $fileName,
			]);
		}

		$supplierData = Supplier::where('id', $request->id)->first();
		$in['name'] = $request->name;
		$in['email'] = $request->email;
		$in['phone'] = $request->phone;
		$in['address'] = $request->address;
		$in['tan_number'] = (!empty($request->tan_number)) ? $request->tan_number : NULL;
		$in['spare_part_ids'] = (!empty($request->spare_part_ids)) ? implode(',', $request->spare_part_ids) : [];
		$supplierData->update($in);
		$getdata = $supplierData->getChanges();

		$this->helper->ActivityLog($request->id, "Edit Supplier", date('Y-m-d'), json_encode($request->all()), $this->user->name, "Supplier", json_encode($getdata), "Update");

		return $this->response("Supplier Update Successfully.", false);
	}

	public function delete_supplier(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'id' => 'required',
		], [
			'id.required' => 'ID is Required',
		]);

		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}

		DB::table('supplier')->where('id', $request->id)->delete();

		$this->helper->ActivityLog($request->id, "Delete Supplier", date('Y-m-d'), "", $this->user->name, "Supplier", "", "Delete");
		return $this->response("Supplier Delete Successfully");
	}

	public function get_supplier($id)
	{
		if (empty($id)) {
			return $this->response("Supplier ID Is Required");
		}

		$supplierData = Supplier::leftJoin('spare_parts', function ($join) {
			$join->on(DB::raw("FIND_IN_SET(spare_parts.id, supplier.spare_part_ids)"), '>', DB::raw('0'));
		})
			->select(
				'supplier.*',
				DB::raw('GROUP_CONCAT(spare_parts.part_name) as spare_part_names')
			)
			->where('supplier.id', $id)
			->groupBy('supplier.id')
			->first();

		$supplierData->logo_name = !empty($supplierData->logo) ? $supplierData->logo : NULL;
		$supplierData->logo = asset('/storage/supplier/' . $supplierData->logo);

		if (!empty($supplierData)) {
			return $this->response("Supplier Data.", false, $supplierData);
		} else {
			return $this->response("Supplier Not Found.", true);
		}
	}

	public function get_all_supplier()
	{
		$supplierList = Supplier::select('id', 'name')->get();
		if (!empty($supplierList)) {
			return $this->response("", false, $supplierList);
		} else {
			return $this->response("Supplier Not Found.", true);
		}
	}


	// Product Mastre
	public function product_master()
	{
		$ProductMasterList = ProductMaster::leftJoin('invoice_item', 'invoice_item.product_id', '=', 'product_master.id')
			->select('product_master.*', DB::raw("SUM(invoice_item.amount) as final_price"))
			->groupBy('product_master.id')
			->orderBy("product_master.id", "DESC")
			->paginate(100);

		if (!empty($ProductMasterList)) {
			foreach ($ProductMasterList as $ProductMaster) {
				$ProductMaster->image = asset('/storage/product_master/' . $ProductMaster->image);
				$ProductMaster->spare_part = (!empty($ProductMaster->spare_parts)) ? json_decode($ProductMaster->spare_parts, JSON_PRETTY_PRINT) : [];
				unset($ProductMaster['spare_parts']);
				$stock_qty = ProductStock::where('status', 0)->where('product_id', $ProductMaster->id)->select(DB::raw("COUNT(id) as total_qty"))->first();
				$ProductMaster->stock_qty = isset($stock_qty->total_qty) ? $stock_qty->total_qty : 0;
				$ProductStockList = ProductStock::where('product_id', $ProductMaster->id)->orderBy("status", "asc")->get();
				if (!empty($ProductStockList)) {
					foreach ($ProductStockList as &$stock) {
						$stock->status = isset($stock->status) && $stock->status == 1 ? "Sell" : "Not Sell";
					}
				}
				$ProductMaster->product_stock_list = $ProductStockList;
			}
		}

		if (!empty($ProductMasterList)) {
			return $this->response("", false, $ProductMasterList);
		} else {
			return $this->response("Not Found Product List.", true);
		}
	}

	public function add_product_master(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'product_name' => 'required',
			'product_code' => 'required',
			'min_alert_qty' => 'required',
			'price' => 'required',
			'desc' => 'required',
			'image' => 'required',
			'spare_parts_id' => 'required',
			'item' => 'required',
			'qty' => 'required',
		], [
			'product_name.required' => 'Please Enter Product Name',
			'product_code.required' => 'Please Enter Product Code',
			'min_alert_qty.required' => 'Please Enter Min Alert Quantity',
			'price.required' => 'Please Enter Price',
			'desc.required' => 'Please Enter Description',
			'image.required' => 'Please Select Image',
			'spare_parts_id.required' => 'Please Select Raw Material Name',
			'item.required' => 'Please Enter Item',
			'qty.required' => 'Please Enter Quantity',
		]);

		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}

		if ($request->hasFile('image')) {
			$file = $request->file('image');
			$fileName = uniqid() . '.' . $file->getClientOriginalExtension();
			$path = public_path() . '/storage/product_master/';
			$file->move($path, $fileName);
		}

		$spare_parts_Arry = [];
		if (!empty($request->item)) {
			foreach ($request->item as $key => $item) {
				$spare_parts_Arry[] = array(
					'spare_parts_id' => $request->spare_parts_id[$key],
					'item' => $item,
					'qty' => isset($request->qty[$key]) ? $request->qty[$key] : 0,
				);
			}
		}
		$json_item = json_encode($spare_parts_Arry);

		$pMaster = new ProductMaster();
		$pMaster->product_name = $request->product_name;
		$pMaster->product_code = $request->product_code;
		$pMaster->price = $request->price;
		$pMaster->desc = $request->desc;
		$pMaster->image = $request->image;
		$pMaster->spare_parts = $json_item;
		$pMaster->min_alert_qty = $request->min_alert_qty;
		$pMaster->save();

		if (!empty($pMaster)) {
			$this->helper->ActivityLog($pMaster->id, "Add Product Master", date('Y-m-d'), json_encode($pMaster), $this->user->name, "Product Master", "", "Create");
			return $this->response("Product Add Successfully.", false);
		} else {
			return $this->response("Product  Error.", true);
		}
	}

	public function edit_product_master(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'id' => 'required',
			'product_name' => 'required',
			'product_code' => 'required',
			'min_alert_qty' => 'required',
			'price' => 'required',
			'desc' => 'required',
			'spare_parts_id' => 'required',
			'item' => 'required',
			'qty' => 'required',
		], [
			'id.required' => 'ID Is Required',
			'product_name.required' => 'Please Enter Product Name',
			'product_code.required' => 'Please Enter Product Code',
			'min_alert_qty.required' => 'Please Enter Min Alert Quantity',
			'price.required' => 'Please Enter Price',
			'desc.required' => 'Please Enter Description',
			'spare_parts_id.required' => 'Please Select Raw Material Name',
			'item.required' => 'Please Enter Item',
			'qty.required' => 'Please Enter Quantity',
		]);

		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}

		if ($request->hasFile('image')) {
			$file = $request->file('image');
			$fileName = uniqid() . '.' . $file->getClientOriginalExtension();
			$path = public_path() . '/storage/product_master/';
			$file->move($path, $fileName);
			// img update
			DB::table('product_master')->where("id", $request->id)->update([
				'image' => $fileName,
			]);
		}

		$spare_parts_Arry = [];
		if (!empty($request->item)) {
			foreach ($request->item as $key => $item) {
				$spare_parts_Arry[] = array(
					'spare_parts_id' => $request->spare_parts_id[$key],
					'item' => $item,
					'qty' => isset($request->qty[$key]) ? $request->qty[$key] : 0,
				);
			}
		}
		$json_item = json_encode($spare_parts_Arry);

		$PMasterData = ProductMaster::where('id', $request->id)->first();
		$in['product_name'] = $request->product_name;
		$in['product_code'] = $request->product_code;
		$in['price'] = $request->price;
		$in['desc'] = $request->desc;
		$in['spare_parts'] = $json_item;
		$in['min_alert_qty'] = $request->min_alert_qty;
		$PMasterData->update($in);
		$getdata = $PMasterData->getChanges();

		$this->helper->ActivityLog($request->id, "Edit Product Master", date('Y-m-d'), json_encode($request->all()), $this->user->name, "Product Master", json_encode($getdata), "Update");
		return $this->response("Product Master Update Successfully.", false);
	}

	public function delete_product_master(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'id' => 'required',
		], [
			'id.required' => 'ID is Required',
		]);

		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}

		if (ProductMaster::find($request->id)) {
			DB::table('product_master')->where('id', $request->id)->delete();
			$this->helper->ActivityLog($request->id, "Delete Product Master", date('Y-m-d'), "", $this->user->name, "Product Master", "", "Delete");
			return $this->response("Product Delete Successfully", false);
		} else {
			return $this->response("Product Data Not Found.", true);
		}
	}

	public function get_product_master($id)
	{
		if (empty($id)) {
			return $this->response("Product ID Is Required");
		}

		$ProductMasterData = ProductMaster::where('id', $id)->first();
		$ProductMasterData->image = asset('/storage/product_master/' . $ProductMasterData->image);
		$ProductMasterData->spare_parts = (!empty($ProductMasterData->spare_parts)) ? json_decode($ProductMasterData->spare_parts, true) : [];
		if (!empty($ProductMasterData)) {
			return $this->response("Product Data.", false, $ProductMasterData);
		} else {
			return $this->response("Product Data Not Found.", true);
		}
	}

	public function get_all_product()
	{
		$ProductList = ProductMaster::select('id', 'product_name', 'desc', 'price')->get();
		if (!empty($ProductList)) {
			return $this->response("", false, $ProductList);
		} else {
			return $this->response("Product Part Found.", true);
		}
	}



	// Spare Part
	public function spare_parts()
	{
		$SparePartsData = SpareParts::orderBy("id", "DESC")->paginate(100);

		if (!$SparePartsData->isEmpty()) {
			foreach ($SparePartsData as $sparePart) {
				$orderItemData = OrderItem::where('item', $sparePart->part_name)->select(DB::raw("SUM(delivery_qty) as total_delivery_qty"),)->first();
				$total_delivery_qty = (!empty($orderItemData->total_delivery_qty)) ? $orderItemData->total_delivery_qty : 0;
				$opening_stock = (!empty($sparePart->opening_stock)) ? $sparePart->opening_stock : 0;
				$total_opening_and_delivery_qty = ($total_delivery_qty + $opening_stock);
				// Img
				$sparePart->image_name = (!empty($sparePart->image)) ? $sparePart->image : '';
				$sparePart->image = asset('/storage/spare_part/' . $sparePart->image);
				// Total Delivery qty
				$totalSparePartQty = 0;
				$productMasters = ProductMaster::all();
				foreach ($productMasters as $product) {
					if (!empty($product->spare_parts)) {
						$sparePartsArr = json_decode($product->spare_parts, true);
						foreach ($sparePartsArr as $part) {
							if ($part['spare_parts_id'] == $sparePart->id) {
								$totalSparePartQty += $part['qty'];
							}
						}
					}
				}
				$sparePart->stock_qty = ($total_opening_and_delivery_qty - $totalSparePartQty);
			}
		}

		if (!$SparePartsData->isEmpty()) {
			return $this->response("", false, $SparePartsData);
		} else {
			return $this->response("Not Found Raw Material List.", true);
		}
	}

	public function add_spare_part(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'part_name' => 'required',
			'price' => 'required',
			'min_alert_qty' => 'required',
			'desc' => 'required',
			'opening_stock' => 'required',
		], [
			'part_name.required' => 'Please Enter Part Name',
			'price.required' => 'Please Enter Price',
			'min_alert_qty.required' => 'Please Enter Min Alert Quantity',
			'desc.required' => 'Please Enter Description',
			'opening_stock.required' => 'Please Enter Opening Stock',
		]);

		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}

		$fileName = null;
		if ($request->hasFile('image')) {
			$file = $request->file('image');
			$fileName = uniqid() . '.' . $file->getClientOriginalExtension();
			$path = public_path() . '/storage/spare_part/';
			$file->move($path, $fileName);
		}

		$sparePartData = new SpareParts();
		$sparePartData->part_name = $request->part_name;
		$sparePartData->part_number = $request->part_number;
		$sparePartData->price = $request->price;
		$sparePartData->min_alert_qty = $request->min_alert_qty;
		$sparePartData->desc = $request->desc;
		$sparePartData->image = $fileName;
		$sparePartData->stock_qty = $request->stock_qty;
		$sparePartData->opening_stock = $request->opening_stock;
		$sparePartData->save();

		if ($sparePartData) {
			$this->helper->ActivityLog($sparePartData->id, "Add Spare Part", date('Y-m-d'), json_encode($sparePartData), $this->user->name, "Spare Part", "", "Create");
			return $this->response("Raw Material  Add Successfully.", false);
		} else {
			return $this->response("Raw Material Error.", true);
		}
	}

	public function edit_spare_part(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'id' => 'required',
			'part_name' => 'required',
			'part_number' => 'nullable',
			'price' => 'required',
			'min_alert_qty' => 'required',
			'desc' => 'required',
			'opening_stock' => 'required',
		], [
			'id.required' => 'ID Is Required',
			'part_name.required' => 'Please Enter Part Name',
			'price.required' => 'Please Enter Price',
			'min_alert_qty.required' => 'Please Enter Min Alert Quantity',
			'desc.required' => 'Please Enter Description',
			'opening_stock.required' => 'Please Enter Opening Stock',
		]);

		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}

		if ($request->hasFile('image')) {
			$file = $request->file('image');
			$fileName = uniqid() . '.' . $file->getClientOriginalExtension();
			$path = public_path() . '/storage/spare_part/';
			$file->move($path, $fileName);
			DB::table('spare_parts')->where("id", $request->id)->update([
				'image' => $fileName,
			]);
		}

		$SPartData = SpareParts::where('id', $request->id)->first();
		$in['part_name'] = $request->part_name;
		$in['part_number'] = $request->part_number;
		$in['price'] = $request->price;
		$in['min_alert_qty'] = $request->min_alert_qty;
		$in['desc'] = $request->desc;
		$in['stock_qty'] = $request->stock_qty;
		$in['opening_stock'] = $request->opening_stock;
		$SPartData->update($in);
		$getdata = $SPartData->getChanges();

		$this->helper->ActivityLog($request->id, "Edit Spare Part", date('Y-m-d'), json_encode($request->all()), $this->user->name, "Spare Part", json_encode($getdata), "Update");
		return $this->response("Raw Material Update Successfully.", false);
	}

	public function delete_spare_part(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'id' => 'required',
		], [
			'id.required' => 'ID is Required',
		]);

		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}

		if (SpareParts::find($request->id)) {
			DB::table('spare_parts')->where('id', $request->id)->delete();
			$this->helper->ActivityLog($request->id, "Delete Spare Part", date('Y-m-d'), "", $this->user->name, "Spare Part", "", "Delete");
			return $this->response("Raw Material Delete Successfully", false);
		} else {
			return $this->response("Raw Material Data Not Found.", true);
		}
	}

	public function get_spare_part($id)
	{
		if (empty($id)) {
			return $this->response("Raw Material ID Is Required");
		}

		$SparePartsData = SpareParts::where('id', $id)->first();

		if (!empty($SparePartsData)) {
			$SparePartsData->image = asset('/storage/spare_part/' . $SparePartsData->image);
			return $this->response("Raw Material Data.", false, $SparePartsData);
		} else {
			return $this->response("Raw Material Data Not Found.", true);
		}
	}

	public function get_all_spare_part()
	{
		$SparePartsList = SpareParts::select('id', 'part_name', 'price', 'stock_qty', 'opening_stock')->get();

		if (!empty($SparePartsList)) {
			foreach ($SparePartsList as &$sparePart) {
				$orderItemData = OrderItem::where('item', $sparePart->part_name)->select(DB::raw("SUM(delivery_qty) as total_delivery_qty"),)->first();
				$total_delivery_qty = (!empty($orderItemData->total_delivery_qty)) ? $orderItemData->total_delivery_qty : 0;
				$opening_stock = (!empty($sparePart->opening_stock)) ? $sparePart->opening_stock : 0;
				$total_opening_and_delivery_qty = ($total_delivery_qty + $opening_stock);
				// Total Delivery qty
				$totalSparePartQty = 0;
				$productMasters = ProductMaster::all();
				foreach ($productMasters as $product) {
					if (!empty($product->spare_parts)) {
						$sparePartsArr = json_decode($product->spare_parts, true);
						foreach ($sparePartsArr as $part) {
							if ($part['spare_parts_id'] == $sparePart->id) {
								$totalSparePartQty += $part['qty'];
							}
						}
					}
				}
				$sparePart->stock_qty = ($total_opening_and_delivery_qty - $totalSparePartQty);
			}
			return $this->response("", false, $SparePartsList);
		} else {
			return $this->response("Raw Material Found.", true);
		}
	}

	public function getsupplierWiseSparePart(Request $request)
	{

		$validator = Validator::make($request->all(), [
			'id' => 'required',
		], [
			'id.required' => 'Please Select Supplier Name',
		]);

		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}

		$supplierData = Supplier::where('id', $request->id)->select('spare_part_ids')->first();

		if (!empty($supplierData->spare_part_ids)) {
			$data = SpareParts::whereIn("id", explode(',', $supplierData->spare_part_ids))->select('id', 'part_name', 'price', 'desc')->get();
			return $this->response("", false, $data);
		} else {
			return $this->response("Not Found Raw Material!", true);
		}
	}



	// Order
	public function add_order(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'supplier_id' => 'required',
			'spare_id' => 'required',
			'item' => 'required',
			'desc' => 'required',
			'qty' => 'required',
			'description' => 'required',
		], [
			'supplier_id.required' => 'Please Select Supplier Name',
			'spare_id.required' => 'Please Select Raw Materials',
			'item.required' => 'Please Enter Item',
			'desc.required' => 'Please Enter Desc',
			'qty.required' => 'Please Enter Quantity',
			'description' => 'Please Enter Description',
		]);

		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}

		$LastRecordCheck = Order::whereDate('created_at', date('Y-m-d'))->orderBy("id", "DESC")->first();

		if (empty($LastRecordCheck)) {
			$GenerateORderNumber = "P" . date("dmY") . "01";
		} else {
			$orderNumber = substr($LastRecordCheck->order_number, 9);
			$IncreseOrderNumber = str_pad(((int)$orderNumber + 1), strlen($orderNumber), '0', STR_PAD_LEFT);
			$GenerateORderNumber = "P" . date("dmY") . $IncreseOrderNumber;
		}

		$OrderData = new Order();
		$OrderData->supplier_id = $request->supplier_id;
		$OrderData->desc = $request->description;
		$OrderData->order_id = time() . rand(1, 1000);
		$OrderData->order_number = $GenerateORderNumber;
		$OrderData->save();
		$id = $OrderData->id;

		$this->helper->ActivityLog($id, "Add Order", date('Y-m-d'), json_encode($OrderData), $this->user->name, "Order", "", "Create");

		if (!empty($request->item)) {
			foreach ($request->item as $key => $item) {
				$OrderItemData = new OrderItem();
				$OrderItemData->order_id = $id;
				$OrderItemData->item = $item;
				$OrderItemData->desc = $request->desc[$key];
				$OrderItemData->qty = $request->qty[$key];
				$OrderItemData->save();
				$this->helper->ActivityLog($OrderItemData->id, "Add Order Item", date('Y-m-d'), json_encode($OrderItemData), $this->user->name, "Order Item", "", "Create");
			}
		}

		if (!empty($id)) {
			$OrderList = Order::Join('supplier', 'supplier.id', '=', 'order.supplier_id')->where('order.id', $id)->select('order.supplier_id', 'order.order_id', 'order.order_number', 'supplier.name as supplier_name', 'supplier.email as supplier_email')->first();
			$OrderItemList = OrderItem::where('order_id', $id)->get();
			$html = view('emails.order', compact('OrderItemList', 'OrderList'))->render();

			$mail = new PHPMailer(true);
			try {
				$mail->SMTPDebug = 0;
				$mail->isSMTP();
				$mail->Host = env('MAIL_HOST');
				$mail->SMTPAuth = true;
				$mail->Username = env('MAIL_USERNAME');
				$mail->Password = env('MAIL_PASSWORD');
				$mail->SMTPSecure = env('MAIL_ENCRYPTION');
				$mail->Port = env('MAIL_PORT');
				$mail->setFrom(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
				$mail->addAddress($OrderList->supplier_email);
				$mail->isHTML(true);
				$mail->Subject = "Order";
				$mail->Body = $html;
				if (!$mail->send()) {
					return $this->response($mail->ErrorInfo, true);
				} else {
					return $this->response("Raw Material Order Add Successfully.", false);
				}
			} catch (Exception $e) {
				return $this->response("Message could not be sent.", true);
			}
		} else {
			return $this->response("Raw Material Order Error.", true);
		}
	}

	public function orders()
	{
		$orderData = Order::orderBy("id", "DESC")->paginate(100);
		if (!empty($orderData)) {
			foreach ($orderData as $order) {
				$supplier = Supplier::find($order->supplier_id);
				$order->supplier_name = (!empty($supplier) && !empty($supplier->name)) ? $supplier->name : null;
				if (!empty($order->invoice_file)) {
					$order->invoice_file = asset('/storage/invoice/' . $order->invoice_file);
				}

				$orderitemList = OrderItem::where('order_id', $order->id)->get();
				if ($orderitemList->isNotEmpty()) {
					foreach ($orderitemList as $item) {
						$item->status_label = $item->status == 1 ? "Complete" : "Pending";
					}
					$order->order_items = $orderitemList;
				}

				$StocksList = Stocks::where('order_id', $order->id)->get();
				if ($StocksList->isNotEmpty()) {
					foreach ($StocksList as &$stock) {
						$supplier = Supplier::where('id', $stock->supplier_id)->first();
						$stock->suppliername = (!empty($supplier) && !empty($supplier->name)) ? $supplier->name : null;
						$spare = SpareParts::where('id', $stock->spare_id)->first();
						$stock->spare_part_name = $spare->part_name ?? '';
						$stock->spare_parts_id = $spare->id ?? null;
					}
					$order->stock_items = $StocksList;
				}
			}

			return $this->response("", false, $orderData);
		} else {
			return $this->response("Raw Material Order Not Found.", true);
		}
	}

	public function get_order($id)
	{
		if (Order::where('id', $id)->first()) {
			$orderData = Order::where('id', $id)->first();
			if (!empty($orderData->invoice_file)) {
				$orderData->invoice_file = asset('/storage/invoice/' . $orderData->invoice_file);
			}
			$orderitemData = OrderItem::where('order_id', $id)->get();
			$orderAry = array(
				'order' => $orderData,
				'order_detail' => $orderitemData,
			);

			return $this->response("Raw Material Order Data.", false, $orderAry);
		} else {
			return $this->response("Raw Material Order Not Found.", true);
		}
	}

	public function delete_order(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'id' => 'required',
		], [
			'id.required' => 'ID is Required',
		]);

		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}

		if (Order::find($request->id)) {
			DB::table('order')->where('id', $request->id)->delete();
			DB::table('order_item')->where('order_id', $request->id)->delete();
			$this->helper->ActivityLog($request->id, "Delete Order", date('Y-m-d'), "", $this->user->name, "Order", "", "Delete");
			return $this->response("Raw Material Order Delete Successfully", false);
		} else {
			return $this->response("Raw Material Order Not Found.", true);
		}
	}

	public function edit_order(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'id' => 'required',
			'supplier_id' => 'required',
			'spare_id' => 'required',
			'item' => 'required',
			'desc' => 'required',
			'qty' => 'required',
			'description' => 'required',
		], [
			'id.required' => 'ID is Required',
			'spare_id.required' => 'Please Select Raw Materials',
			'supplier_id.required' => 'Please Select Supplier Name',
			'item.required' => 'Please Enter Item',
			'desc.required' => 'Please Enter Desc',
			'qty.required' => 'Please Enter Quantity',
			'description' => 'Please Enter Description',
		]);

		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}

		$OrderData = Order::where('id', $request->id)->first();
		$OrderData->supplier_id = $request->supplier_id;
		$OrderData->desc = $request->description;
		$OrderData->save();
		$this->helper->ActivityLog($request->id, "Edit Order", date('Y-m-d'), json_encode($OrderData), $this->user->name, "Order", "", "Update");

		DB::table('order_item')->where('order_id', $request->id)->delete();
		$this->helper->ActivityLog($request->id, "Edit Order Item", date('Y-m-d'), "", $this->user->name, "Order", "", "Delete");

		if (!empty($request->item)) {
			foreach ($request->item as $key => $item) {
				$OrderItemData = new OrderItem();
				$OrderItemData->order_id = $request->id;
				$OrderItemData->item = $item;
				$OrderItemData->desc = $request->desc[$key];
				$OrderItemData->qty = $request->qty[$key];
				$OrderItemData->save();
				$this->helper->ActivityLog($OrderItemData->id, "Add Invoice Item", date('Y-m-d'), json_encode($OrderItemData), $this->user->name, "Invoice Item", "", "Create");
			}
		}

		if (!empty($id)) {
			$OrderList = Order::Join('supplier', 'supplier.id', '=', 'order.supplier_id')->where('order.id', $request->id)->select('order.supplier_id', 'order.order_id', 'order.order_number', 'supplier.name as supplier_name', 'supplier.email as supplier_email')->first();
			$OrderItemList = OrderItem::where('order_id', $request->id)->get();
			$html = view('emails.order', compact('OrderItemList', 'OrderList'))->render();

			$mail = new PHPMailer(true);
			try {
				$mail->SMTPDebug = 0;
				$mail->isSMTP();
				$mail->Host = env('MAIL_HOST');
				$mail->SMTPAuth = true;
				$mail->Username = env('MAIL_USERNAME');
				$mail->Password = env('MAIL_PASSWORD');
				$mail->SMTPSecure = env('MAIL_ENCRYPTION');
				$mail->Port = env('MAIL_PORT');
				$mail->setFrom(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
				$mail->addAddress($OrderList->supplier_email);
				$mail->isHTML(true);
				$mail->Subject = "Order";
				$mail->Body = $html;
				if (!$mail->send()) {
					return $this->response($mail->ErrorInfo, true);
				} else {
					return $this->response("Raw Material Order Add Successfully.", false);
				}
			} catch (Exception $e) {
				return $this->response("Message could not be sent.", true);
			}
		}
	}

	public function edit_order_details(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'id' => 'required',
			'invoice_desc' => 'required',
			'invoice_file' => 'required',
			'delivery_qty' => 'required',
			'order_item_id' => 'required',
			'rate' => 'required',
		], [
			'id.required' => 'Order ID is required',
			'invoice_desc.required' => 'Invoice Description is required',
			'invoice_file.required' => 'Invoice File is required',
			'delivery_qty.required' => 'Delivery Quantity is required',
			'order_item_id.required' => 'Order Item ID is required',
			'rate.required' => 'Rate is required',
		]);

		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}

		if ($request->hasFile('invoice_file')) {
			$file = $request->file('invoice_file');
			$fileName = uniqid() . '.' . $file->getClientOriginalExtension();
			$path = public_path() . '/storage/invoice/';
			$file->move($path, $fileName);
		}

		$OrderData = Order::where('id', $request->id)->first();
		$OrderData->invoice_desc = $request->invoice_desc;
		$OrderData->invoice_file = $fileName;
		$OrderData->delivery_date = date("Y-m-d");
		$OrderData->save();
		$this->helper->ActivityLog($request->id, "Edit Order", date('Y-m-d'), json_encode($OrderData), $this->user->name, "Order", "", "Update");

		$order_item_ids = (array) $request->order_item_id;
		$delivery_qtys = (array) $request->delivery_qty;
		$rates = (array) $request->rate;

		$total_amount = 0;
		if (!empty($order_item_ids)) {
			foreach ($order_item_ids as $key => $item_id) {
				$qty = $delivery_qtys[$key] ?? null;
				$rate = $rates[$key] ?? null;
				if ($qty !== null) {

					$OrderItemData = \App\Models\OrderItem::find($item_id);
					$OrderItemData->delivery_qty = $qty;
					$OrderItemData->rate = $rate;
					$OrderItemData->status = 1;
					$OrderItemData->amount = ($rate * $qty);
					$total_amount += ($rate * $qty);
					$OrderItemData->save();
					$this->helper->ActivityLog($OrderItemData->id, "Add Order", date('Y-m-d'), json_encode($OrderItemData), $this->user->name, "Order", "", "Create");
				}
			}
		}

		$OrderDataSec = Order::where('id', $request->id)->first();
		$OrderDataSec->sub_total = $total_amount;
		$OrderDataSec->total_amount = $total_amount;
		$OrderDataSec->save();
		$this->helper->ActivityLog($request->id, "Edit Order", date('Y-m-d'), json_encode($OrderDataSec), $this->user->name, "Order", "", "Update");

		return $this->response("Raw Material Order details updated successfully.", false);
	}


	// Invoices
	public function invoices()
	{
		$invoices = Invoice::where('void_status', 0)->orderBy("id", "DESC")->paginate(100);
		if (!empty($invoices)) {
			foreach ($invoices as $invoice) {

				if ($invoice->transaction_type == 1) {
					$payment_status = "Complete";
				} elseif ($invoice->transaction_type == 0 && empty($invoice->remaining_amount)) {
					$payment_status = "Pending";
				} else {
					$payment_status = "Partial Payment";
				}
				$invoice->payment_status = $payment_status;
				$userData = User::where('id', $invoice->customer_id)->first();
				$invoice->customer_name = isset($userData->name) ? $userData->name : '';
				$receiving_payment = Transaction::where('invoice_id', $invoice->id)->select(DB::raw('SUM(amount) as receivingpayment'))->first();
				$ReceivingPayment = isset($receiving_payment->receivingpayment) && !empty($receiving_payment->receivingpayment) ? $receiving_payment->receivingpayment : NULL;
				$invoice->receiving_payment = $ReceivingPayment;
				$invoice->invoice_detail = \App\Models\InvoiceItem::leftJoin('product_stock', function ($join) {
					$join->on(DB::raw("FIND_IN_SET(product_stock.id, invoice_item.product_stock_id)"), '>', DB::raw('0'));
				})
					->where('invoice_item.invoice_id', $invoice->id)
					->select(
						'invoice_item.*',
						DB::raw('GROUP_CONCAT(product_stock.product_code) as product_code'),
						DB::raw('GROUP_CONCAT(product_stock.id) as product_code_id'),
					)
					->groupBy(DB::raw("product_stock.product_id"))
					->get();
			}
		}
		if (!empty($invoices)) {
			return $this->response("", false, $invoices);
		}
	}

	public function VoidInvoices()
	{
		$invoices = Invoice::where('void_status', 1)->orderBy("id", "DESC")->paginate(100);
		if (!empty($invoices)) {
			foreach ($invoices as $invoice) {
				$userData = User::where('id', $invoice->customer_id)->first();
				$invoice->customer_name = isset($userData->name) ? $userData->name : '';
				$invoice_detail = InvoiceItem::leftJoin('product_stock', function ($join) {
					$join->on(DB::raw("FIND_IN_SET(product_stock.id, invoice_item.product_stock_id)"), '>', DB::raw('0'));
				})
					->where('invoice_item.invoice_id', $invoice->id)
					->select(
						'invoice_item.*',
						DB::raw('GROUP_CONCAT(product_stock.product_code) as product_code'),
						DB::raw('GROUP_CONCAT(product_stock.id) as product_code_id'),
					)
					->groupBy(DB::raw("product_stock.product_id"))
					->get();
				$invoice->invoice_detail = $invoice_detail;
			}
		}
		if (!empty($invoices)) {
			return $this->response("", false, $invoices);
		} else {
			return $this->response("Void Invoice Not Found.", true);
		}
	}

	public function add_invoice(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'description' => 'required',
			'invoice_date' => 'required',
			'invoice_no' => 'required|unique:invoice,invoice_no',
		], [
			'invoice_date.required' => 'Please Select Invoice Date',
			'description.required' => 'Please Enter Description',
			'invoice_no.unique' => 'This Invoice Number already exists',
		]);

		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}

		$is_send_mail = isset($request->is_send_mail) ? $request->is_send_mail : 0;

		$InvoiceIstData = new Invoice();
		$InvoiceIstData->invoice_no = $request->invoice_no;
		$InvoiceIstData->desc = $request->description;
		$InvoiceIstData->customer_id = (!empty($request->customer_id)) ? $request->customer_id : 0;
		$InvoiceIstData->ship_to = (!empty($request->ship_to)) ? $request->ship_to : NULL;
		$InvoiceIstData->bill_to = (!empty($request->bill_to)) ? $request->bill_to : NULL;
		$InvoiceIstData->invoice_date = date('Y-m-d', strtotime($request->invoice_date));
		$InvoiceIstData->save_send = $is_send_mail;
		$InvoiceIstData->save();
		$id = $InvoiceIstData->id;

		$this->helper->ActivityLog($id, "Add Invoice", date('Y-m-d'), json_encode($InvoiceIstData), $this->user->name, "Invoice", "", "Create");

		$total_amount = 0;
		if (!empty($request->item)) {
			foreach ($request->item as $key => $item) {
				$product_stock_id = (isset($request->product_stock_id[$key]) && !empty($request->product_stock_id[$key])) ? $request->product_stock_id[$key] : NULL;
				if (!empty($product_stock_id)) {
					$exp_product_stock_id = explode(',', $product_stock_id);
					foreach ($exp_product_stock_id as $p_stock_id) {
						ProductStock::where('id', $p_stock_id)->update(['status' => 1]);
					}
				}

				$InvoiceItemData = new InvoiceItem();
				$InvoiceItemData->invoice_id = $id;
				$InvoiceItemData->product_id = $request->product_id[$key];
				$InvoiceItemData->product_stock_id = $product_stock_id;
				$InvoiceItemData->item = $item;
				$InvoiceItemData->qty = $request->qty[$key];
				$InvoiceItemData->rate = $request->rate[$key];
				$InvoiceItemData->amount = ($request->qty[$key] * $request->rate[$key]);
				$InvoiceItemData->save();
				$total_amount += ($request->qty[$key] * $request->rate[$key]);
				$this->helper->ActivityLog($id, "Add Invoice Item", date('Y-m-d'), json_encode($InvoiceItemData), $this->user->name, "Invoice Item", "", "Create");
			}
		}

		$EditInvoiceData = Invoice::where('id', $id)->first();
		$in['sub_total'] = $total_amount;
		$in['total_amount'] = $total_amount;
		$EditInvoiceData->update($in);
		$getdata = $EditInvoiceData->getChanges();

		$this->helper->ActivityLog($id, "Add Invoice", date('Y-m-d'), json_encode($EditInvoiceData), $this->user->name, "Invoice", $EditInvoiceData, "Create");

		if (!empty($id)) {
			$InvoiceData = Invoice::leftJoin('users', 'users.id', '=', 'invoice.customer_id')->where('invoice.id', $id)->select('invoice.*', 'users.name as customer_name', 'users.email as customer_email', 'users.bcc', 'users.cc')->first();
			if (!empty($InvoiceData->customer_email) && $InvoiceData->invoice_no && $is_send_mail == 1) {
				$bccMails = isset($InvoiceData->bcc) && !empty($InvoiceData->bcc) ? explode(',', $InvoiceData->bcc) : NULL;
				$ccMails = isset($InvoiceData->cc) && !empty($InvoiceData->cc) ? explode(',', $InvoiceData->cc) : NULL;

				$InvoiceItemsData = InvoiceItem::leftJoin('product_stock', function ($join) {
					$join->on(DB::raw("FIND_IN_SET(product_stock.id, invoice_item.product_stock_id)"), '>', DB::raw('0'));
				})
					->where('invoice_item.invoice_id', $InvoiceData->id)
					->select(
						'invoice_item.*',
						DB::raw('GROUP_CONCAT(product_stock.product_code) as product_code'),
						DB::raw('GROUP_CONCAT(product_stock.id) as product_code_id'),
					)
					->groupBy(DB::raw("product_stock.product_id"))
					->get();

				$InvoiceUrl = "https://crm.excelwater.ca/manage_invoice/invoice_detail/" . $InvoiceData->invoice_no;
				$html = view('emails.invoice', compact('InvoiceData', 'InvoiceItemsData', 'InvoiceUrl'))->render();

				$mail = new PHPMailer(true);
				try {
					$mail->SMTPDebug = 0;
					$mail->isSMTP();
					$mail->Host = env('MAIL_HOST');
					$mail->SMTPAuth = true;
					$mail->Username = env('MAIL_USERNAME');
					$mail->Password = env('MAIL_PASSWORD');
					$mail->SMTPSecure = env('MAIL_ENCRYPTION');
					$mail->Port = env('MAIL_PORT');
					$mail->setFrom(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
					$mail->addAddress($InvoiceData->customer_email);
					if (!empty($bccMails)) {
						foreach ($bccMails as $bccmail) {
							$mail->addBCC($bccmail);
						}
					}
					if (!empty($ccMails)) {
						foreach ($ccMails as $ccmail) {
							$mail->addCC($ccmail);
						}
					}
					$mail->isHTML(true);
					$mail->Subject = "Invoice";
					$mail->Body = $html;
					if (!$mail->send()) {
						return $this->response($mail->ErrorInfo, true);
					} else {
						$invoiceInsertAry = array('id' => $id, 'send_status' => $is_send_mail);
						return $this->response("Email has been sent.", false, $invoiceInsertAry);
					}
				} catch (Exception $e) {
					return $this->response("Message could not be sent.", true);
				}
			}

			$invoiceInsertAry = array('id' => $id, 'send_status' => $is_send_mail);
			return $this->response("Invoice Added Successfully", false, $invoiceInsertAry);
		} else {
			return $this->response("Invoice Error.", true);
		}
	}

	public function get_invoice($id)
	{
		if (Invoice::where('id', $id)->first()) {
			$invoiceData = Invoice::where('id', $id)->first();
			$userData = User::where('id', $invoiceData->customer_id)->first();
			$invoiceData->customer_name = isset($userData->name) ? $userData->name : '';

			$invoice_detail = InvoiceItem::leftJoin('product_stock', function ($join) {
				$join->on(DB::raw("FIND_IN_SET(product_stock.id, invoice_item.product_stock_id)"), '>', DB::raw('0'));
			})
				->where('invoice_item.invoice_id', $id)
				->select(
					'invoice_item.*',
					DB::raw('GROUP_CONCAT(product_stock.product_code) as product_code'),
					DB::raw('GROUP_CONCAT(product_stock.id) as product_code_id'),
				)
				->groupBy(DB::raw("product_stock.product_id"))
				->get();

			$invoiceAry = array(
				'invoice' => $invoiceData,
				'invoice_detail' => $invoice_detail,
			);
			return $this->response("", false, $invoiceAry);
		} else {
			return $this->response("Invoice Not Found.", true);
		}
	}

	public function delete_invoice(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'id' => 'required',
		], [
			'id.required' => 'ID is Required',
		]);

		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}

		if (Invoice::find($request->id)) {
			DB::table('invoice')->where('id', $request->id)->delete();
			DB::table('invoice_item')->where('invoice_id', $request->id)->delete();
			$this->helper->ActivityLog($request->id, "Delete Invoice", date('Y-m-d'), "", $this->user->name, "Invoice", "", "Delete");
			return $this->response("Invoice Delete Successfully", false);
		} else {
			return $this->response("Invoice Not Found.", true);
		}
	}

	public function edit_invoice(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'id' => 'required',
			'invoice_date' => 'required',
			'description' => 'required',
		], [
			'id.required' => 'ID Is Required',
			'invoice_date.required' => 'Please Select Invoice Date',
			'description.required' => 'Please Enter Description',
		]);

		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}

		$is_send_mail = isset($request->is_send_mail) ? $request->is_send_mail : 0;

		$EditInvoiceData = Invoice::where('id', $request->id)->first();
		$EditInvoiceData->customer_id = (!empty($request->customer_id)) ? $request->customer_id : 0;
		$EditInvoiceData->ship_to = (!empty($request->ship_to)) ? $request->ship_to : NULL;
		$EditInvoiceData->bill_to = (!empty($request->bill_to)) ? $request->bill_to : NULL;
		$EditInvoiceData->invoice_date = date('Y-m-d', strtotime($request->invoice_date));
		$EditInvoiceData->desc = $request->description;
		$EditInvoiceData->save_send = $is_send_mail;
		$EditInvoiceData->save();
		$getdata = $EditInvoiceData->getChanges();

		$this->helper->ActivityLog($request->id, "Edit Invoice", date('Y-m-d'), json_encode($EditInvoiceData), $this->user->name, "Invoice", $EditInvoiceData, "Update");

		DB::table('invoice_item')->where('invoice_id', $request->id)->delete();
		$this->helper->ActivityLog($request->id, "Delete Invoice Item", date('Y-m-d'), "", $this->user->name, "Invoice", $EditInvoiceData, "Delete");

		$total_amount = 0;
		if (!empty($request->item)) {
			foreach ($request->item as $key => $item) {
				$product_stock_id = (isset($request->product_stock_id[$key]) && !empty($request->product_stock_id[$key])) ? $request->product_stock_id[$key] : NULL;
				if (!empty($product_stock_id)) {
					$exp_product_stock_id = explode(',', $product_stock_id);
					foreach ($exp_product_stock_id as $p_stock_id) {
						ProductStock::where('id', $p_stock_id)->update(['status' => 1]);
					}
				}

				$InvoiceItemData = new InvoiceItem();
				$InvoiceItemData->invoice_id = $request->id;
				$InvoiceItemData->product_id = $request->product_id[$key];
				$InvoiceItemData->product_stock_id = $product_stock_id;
				$InvoiceItemData->item = $item;
				$InvoiceItemData->qty = $request->qty[$key];
				$InvoiceItemData->rate = $request->rate[$key];
				$InvoiceItemData->amount = ($request->qty[$key] * $request->rate[$key]);
				$InvoiceItemData->save();
				$total_amount += ($request->qty[$key] * $request->rate[$key]);
				$this->helper->ActivityLog($request->id, "Add Invoice Item", date('Y-m-d'), json_encode($InvoiceItemData), $this->user->name, "Invoice Item", "", "Create");
			}
		}

		$EditInvoiceData = Invoice::where('id', $request->id)->first();
		$in['sub_total'] = $total_amount;
		$in['total_amount'] = $total_amount;
		$EditInvoiceData->update($in);
		$getdata = $EditInvoiceData->getChanges();

		$this->helper->ActivityLog($request->id, "Add Invoice", date('Y-m-d'), json_encode($EditInvoiceData), $this->user->name, "Invoice", $EditInvoiceData, "Create");

		$InvoiceData = Invoice::leftJoin('users', 'users.id', '=', 'invoice.customer_id')->where('invoice.id', $request->id)->select('invoice.*', 'users.name as customer_name', 'users.email as customer_email')->first();
		if (!empty($InvoiceData->customer_email) && $InvoiceData->invoice_no && $is_send_mail == 1) {
			$bccMails = isset($InvoiceData->bcc) && !empty($InvoiceData->bcc) ? explode(',', $InvoiceData->bcc) : NULL;
			$ccMails = isset($InvoiceData->cc) && !empty($InvoiceData->cc) ? explode(',', $InvoiceData->cc) : NULL;
			$InvoiceItemsData = InvoiceItem::leftJoin('product_stock', function ($join) {
				$join->on(DB::raw("FIND_IN_SET(product_stock.id, invoice_item.product_stock_id)"), '>', DB::raw('0'));
			})
				->where('invoice_item.invoice_id', $InvoiceData->id)
				->select(
					'invoice_item.*',
					DB::raw('GROUP_CONCAT(product_stock.product_code) as product_code'),
					DB::raw('GROUP_CONCAT(product_stock.id) as product_code_id'),
				)
				->groupBy(DB::raw("product_stock.product_id"))
				->get();
			$InvoiceUrl = "https://crm.excelwater.ca/manage_invoice/invoice_detail/" . $InvoiceData->invoice_no;
			$QrCode = QrCode::size(80)->generate($InvoiceUrl);
			$html = view('emails.invoice', compact('InvoiceData', 'InvoiceItemsData', 'QrCode', 'InvoiceUrl'))->render();

			$mail = new PHPMailer(true);
			try {
				$mail->SMTPDebug = 0;
				$mail->isSMTP();
				$mail->Host = env('MAIL_HOST');
				$mail->SMTPAuth = true;
				$mail->Username = env('MAIL_USERNAME');
				$mail->Password = env('MAIL_PASSWORD');
				$mail->SMTPSecure = env('MAIL_ENCRYPTION');
				$mail->Port = env('MAIL_PORT');
				$mail->setFrom(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
				$mail->addAddress($InvoiceData->customer_email);
				if (!empty($bccMails)) {
					foreach ($bccMails as $bccmail) {
						$mail->addBCC($bccmail);
					}
				}
				if (!empty($ccMails)) {
					foreach ($ccMails as $ccmail) {
						$mail->addCC($ccmail);
					}
				}
				$mail->isHTML(true);
				$mail->Subject = "Invoice";
				$mail->Body = $html;
				if (!$mail->send()) {
					return $this->response($mail->ErrorInfo, true);
				} else {
					$invoiceInsertAry = array('id' => $request->id, 'send_status' => $is_send_mail);
					return $this->response("Email has been sent.", false, $invoiceInsertAry);
				}
			} catch (Exception $e) {
				return $this->response("Message could not be sent.", true);
			}
		}

		$invoiceInsertAry = array('id' => $request->id, 'send_status' => $is_send_mail);
		return $this->response("Invoice Update Successfully", false, $invoiceInsertAry);
	}

	public function check_use_parts()
	{
		$Stock_analysis = DB::table('stocks as st')
			->join('use_parts as up', 'up.part_id', '=', 'st.spare_id')
			->select(
				'st.qty as stock_qty',
				'up.qty as parts_qty',
				DB::raw('(st.qty - up.qty) as remaining_qty')
			)
			->get();

		if (!empty($Stock_analysis)) {
			return $this->response("", false, $Stock_analysis);
		}
	}

	public function ChangeVoidStatus(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'id' => 'required',
			'status' => 'required',
		], [
			'id.required' => 'Please Enter ID',
			'status.required' => 'Please Change Status',
		]);

		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}

		$in = $request->all();
		$status = isset($in['status']) ? $in['status'] : 0;

		$invoiceData = Invoice::where('id', $in['id'])->first();
		$invoiceData->void_status = $status;
		$invoiceData->save();

		if (!empty($invoiceData)) {
			$this->helper->ActivityLog($in['id'], "Change Status Invoice", date('Y-m-d'), json_encode(array('void_status' => $status)), $this->user->name, "Invoice", json_encode(array('void_status' => $status)), "Change Status");
			return $this->response("Void Status Changed!", false, $invoiceData);
		} else {
			return $this->response("Invoice Not Found.", true);
		}
	}

	public function SettlePayment(Request $request)
	{

		$validator = Validator::make($request->all(), [
			'customer_id' => 'required',
			'date' => 'required',
			'type' => 'required',
			'amount' => 'required',
		], [
			'customer_id.required' => 'Please Select Customer Name',
			'date.required' => 'Please Select Date',
			'type.required' => 'Please Enter Type',
			'amount.required' => 'Please Enter Amount',
		]);

		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}

		$in = $request->all();
		$transaction_amount = isset($in['amount']) && !empty($in['amount']) ? $in['amount'] : 0;
		$invoiceData = Invoice::where('transaction_type', 0)->where('customer_id', $in['customer_id'])->orderBy("id", "ASC")->select('id', 'sub_total', 'total_amount', 'remaining_amount', 'transaction_type')->get();

		$subAmount = $transaction_amount;

		if (!empty($invoiceData) && !empty($transaction_amount)) {

			$response = Transaction::create([
				'customer_id' => $in['customer_id'],
				'date' => $in['date'],
				'type' => $in['type'],
				'desc' => isset($in['desc']) && !empty($in['desc']) ? $in['desc'] : NULL,
				'amount' => $transaction_amount,
				'status' => 1,
				'created_at' => date('Y-m-d H:i:s'),
				'updated_at' => date('Y-m-d H:i:s'),
			]);

			$this->helper->ActivityLog("Add Transaction", $response->id, $response, $this->user->name);


			foreach ($invoiceData as $invoice) {
				$remaining = !empty($invoice->remaining_amount) ? $invoice->remaining_amount : $invoice->total_amount;

				if ($subAmount >= $remaining) {
					$invoice->remaining_amount = 0;
					$invoice->transaction_type = 1;
					$subAmount -= $remaining;
				} else {
					$invoice->remaining_amount = $remaining - $subAmount;
					$invoice->transaction_type = 0;
					$subAmount = 0;
				}
				$invoice->save();

				$this->helper->ActivityLog($request->id, "Invoice Settle Amount", date('Y-m-d'), $invoice, $this->user->name, "Customer", "", "Create");

				if ($subAmount <= 0) {
					break;
				}
			}

			return $this->response("Invoices updated.", false);
		} else {
			return $this->response("Not Found.", true);
		}
	}

	public function TransactionSummary($id)
	{
		$userData = User::where('users.id', $id)->select(
			'name',
			'email',
			'mobile',
			DB::raw("CONCAT_WS(', ', billing_address, billing_landmark, billing_city, billing_state, billing_zipcode) AS bill_to"),
			DB::raw("CONCAT_WS(', ', shipping_address, shipping_landmark, shipping_city, shipping_state, shipping_zipcode) AS bill_to"),
		)->first();

		if (empty($userData)) {
			return $this->response("Customer Not Found.", true);
		}

		$totalAmount = Invoice::where('customer_id', $id)->select(DB::raw("SUM(total_amount) as total_amount"))->first();
		$totalTransactionAmount = Transaction::where('customer_id', $id)->select(DB::raw("SUM(amount) as total_amount"))->first();

		$userData->total_amount = $total_amount = isset($totalAmount->total_amount) ? $totalAmount->total_amount : 0;
		$userData->total_overdue_amount = isset($totalTransactionAmount->total_amount) ? ($total_amount - $totalTransactionAmount->total_amount) : $total_amount;

		$transactionList = Transaction::join('users', 'users.id', '=', 'transaction.customer_id')
			->where('customer_id', $id)
			->orderBy("transaction.id", "DESC")
			->select('users.name as customer_name', 'transaction.date', 'transaction.type', 'transaction.desc', 'transaction.amount')
			->get();

		$data = array(
			'userdata' => $userData,
			'transactionList' => $transactionList,
		);

		return $this->response("", false, $data);
	}

	public function InvoicePaymentSettlement(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'customer_id' => 'required',
			'invoice_id' => 'required',
			'date' => 'required',
			'type' => 'required',
			'amount' => 'required',
		], [
			'customer_id.required' => 'Please Select Customer Name',
			'invoice_id.required' => 'Please Select Invoice Row',
			'date.required' => 'Please Select Date',
			'type.required' => 'Please Enter Type',
			'amount.required' => 'Please Enter Amount',
		]);

		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}

		$in = $request->all();
		$transaction_amount = isset($in['amount']) && !empty($in['amount']) ? $in['amount'] : 0;
		$invoiceData = Invoice::where('transaction_type', 0)->where('customer_id', $in['customer_id'])->where('id', $in['invoice_id'])->select('id', 'sub_total', 'total_amount', 'remaining_amount', 'transaction_type')->first();
		$subAmount = $transaction_amount;
		if (!empty($invoiceData) && !empty($transaction_amount)) {

			$response = Transaction::create([
				'customer_id' => $in['customer_id'],
				'invoice_id' => $in['invoice_id'],
				'date' => $in['date'],
				'type' => $in['type'],
				'desc' => isset($in['desc']) && !empty($in['desc']) ? $in['desc'] : NULL,
				'amount' => $transaction_amount,
				'status' => 1,
				'created_at' => date('Y-m-d H:i:s'),
				'updated_at' => date('Y-m-d H:i:s'),
			]);
			$this->helper->ActivityLog($response->id, "Add Transaction", date('Y-m-d'), json_encode($response), $this->user->name, "Transaction", "", "Create");

			$remaining = !empty($invoiceData->remaining_amount) ? $invoiceData->remaining_amount : $invoiceData->total_amount;

			if ($subAmount >= $remaining) {
				$invoiceData->remaining_amount = 0;
				$invoiceData->transaction_type = 1;
				$subAmount -= $remaining;
			} else {
				$invoiceData->remaining_amount = $remaining - $subAmount;
				$invoiceData->transaction_type = 0;
				$subAmount = 0;
			}
			$invoiceData->save();
			$this->helper->ActivityLog($request->id, "Invoice Settle Amount", date('Y-m-d'), $invoiceData, $this->user->name, "Transaction", "", "Create");
			return $this->response("Invoices updated.", false);
		} else {
			return $this->response("Not Found.", true);
		}
	}

	public function GetInvoicePaymentSettlement(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'id' => 'required',
		], [
			'id.required' => 'Please Select Invoice',
		]);

		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}

		$TransactionList = Invoice::Join('transaction', 'transaction.invoice_id', '=', 'invoice.id')
			->where('invoice.id', $request->id)
			->select('transaction.date', 'transaction.type', 'transaction.amount', 'transaction.desc')
			->paginate(10);
		if (!empty($TransactionList)) {
			return $this->response("", false, $TransactionList);
		} else {
			return $this->response("Transaction Not Found.", true);
		}
	}

	public function GetActivityLog()
	{
		$activityList = ActivityLog::select('title', 'date', 'response', 'updater', 'type', 'desc', 'status')->orderBy('id', "DESC")->paginate(50);
		if (!empty($activityList)) {
			foreach ($activityList as &$activity) {
				$activity->date = date("d-m-Y", strtotime($activity->date));
				$activity->response = $this->deepJsonDecode($activity->response ?? null);
				$activity->desc = $this->deepJsonDecode($activity->desc ?? null);
			}
			return $this->response("", false, $activityList);
		} else {
			return $this->response("Activity Log Not Found.", true);
		}
	}

	public function deepJsonDecode($value)
	{
		if (is_string($value) && $this->isJson($value)) {
			$value = json_decode($value, true);
		}
		if (is_array($value)) {
			foreach ($value as $key => $val) {
				$value[$key] = $this->deepJsonDecode($val);
			}
		}
		return $value;
	}

	public function isJson($string)
	{
		if (!is_string($string)) return false;
		json_decode($string);
		return json_last_error() === JSON_ERROR_NONE;
	}




	// Customers
	public function GetUsers()
	{
		$userList = User::where('status', 1)->orderBy("id", "DESC")->paginate(100);
		if (!empty($userList)) {
			foreach ($userList as &$user) {
				$user->bcc = (!empty($user->bcc)) ? explode(',', $user->bcc) : null;
				$user->cc  = (!empty($user->cc))  ? explode(',', $user->cc)  : null;
			}
			return $this->response("", false, $userList);
		} else {
			return $this->response("Customer Not Found.", true);
		}
	}

	public function AddUser(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'name' => 'required',
			'mobile' => 'required|unique:users',
			'email' => 'required|email|unique:users',
			'billing_address' => 'required',
			'billing_city' => 'required',
			'billing_state' => 'required',
			'billing_zipcode' => 'required',
			'shipping_address' => 'required',
			'shipping_city' => 'required',
			'shipping_state' => 'required',
			'shipping_zipcode' => 'required',
		], [
			'name.required' => 'Please Enter Full Name',
			'mobile.required' => 'Please Enter Mobile Number',
			'email.required' => 'Please Enter Email ID',
			'billing_address.required' => 'Please Enter Billing Address',
			'billing_city.required' => 'Please Enter Billing City',
			'billing_state.required' => 'Please Enter Billing State',
			'billing_zipcode.required' => 'Please Enter Billing Zip-Code',
			'shipping_address.required' => 'Please Enter Shipping Address',
			'shipping_city.required' => 'Please Enter Shipping City',
			'shipping_state.required' => 'Please Enter Shipping State',
			'shipping_zipcode.required' => 'Please Enter Shipping Zip-Code',
		]);

		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}

		$in = $request->all();
		$in['bcc'] = isset($request->bcc) && !empty($request->bcc) ? implode(',', $request->bcc) : NULL;
		$in['cc'] = isset($request->cc) && !empty($request->cc) ? implode(',', $request->cc) : NULL;
		$userData = User::create($in);

		if (!empty($userData)) {
			$this->helper->ActivityLog($userData->id, "Add Customer", date('Y-m-d'), json_encode($userData), $this->user->name, "Customer", "", "Create");
			return $this->response("Add New Customer Successfully!", false);
		} else {
			return $this->response("Error", true);
		}
	}

	public function EditUser(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'id' => 'required',
			'name' => 'required',
			'mobile' => 'required|unique:users,mobile,' . $request->id,
			'email' => 'required|email|unique:users,email,' . $request->id,
			'billing_address' => 'required',
			// 'billing_landmark' => 'required',
			'billing_city' => 'required',
			'billing_state' => 'required',
			'billing_zipcode' => 'required',
			'shipping_address' => 'required',
			// 'shipping_landmark' => 'required',
			'shipping_city' => 'required',
			'shipping_state' => 'required',
			'shipping_zipcode' => 'required',
		], [
			'id.required' => 'ID is Required',
			'name.required' => 'Please Enter Full Name',
			'mobile.required' => 'Please Enter Mobile Number',
			'email.required' => 'Please Enter Email ID',
			'billing_address.required' => 'Please Enter Billing Address',
			// 'billing_landmark.required' => 'Please Enter Billing Landmark',
			'billing_city.required' => 'Please Enter Billing City',
			'billing_state.required' => 'Please Enter Billing State',
			'billing_zipcode.required' => 'Please Enter Billing Zip-Code',
			'shipping_address.required' => 'Please Enter Shipping Address',
			// 'shipping_landmark.required' => 'Please Enter Shipping Landmark',
			'shipping_city.required' => 'Please Enter Shipping City',
			'shipping_state.required' => 'Please Enter Shipping State',
			'shipping_zipcode.required' => 'Please Enter Shipping Zip-Code',
		]);

		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}

		$in = $request->all();
		$in['bcc'] = isset($request->bcc) && !empty($request->bcc) ? implode(',', $request->bcc) : NULL;
		$in['cc'] = isset($request->cc) && !empty($request->cc) ? implode(',', $request->cc) : NULL;
		$userData = User::where('id', $request->id)->first();
		unset($in['id']);
		$userData->update($in);
		$getdata = $userData->getChanges();

		if (!empty($userData)) {
			$this->helper->ActivityLog($request->id, "Edit Customer", date('Y-m-d'), json_encode($userData), $this->user->name, "Customer", json_encode($getdata), "Update");
			return $this->response("Edit Customer Successfully!", false);
		} else {
			return $this->response("Error", true);
		}
	}

	public function DeleteUser(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'id' => 'required',
		], [
			'id.required' => 'ID is Required',
		]);

		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}

		$userDelete = User::find($request->id)->delete();
		if ($userDelete) {
			$this->helper->ActivityLog($request->id, "Delete Customer", date('Y-m-d'), "", $this->user->name, "Customer", "", "Delete");
			return $this->response("Customer Delete Successfully!", false);
		} else {
			return $this->response("Error", true);
		}
	}

	public function DashboardAllCount()
	{
		$CustomerCount = User::where('status', 1)->count();
		$ProductMasterCount = ProductMaster::count();
		$SupplierCount = Supplier::count();
		$SparePartCount = SpareParts::count();
		$OrderCount = Order::count();
		$InvoiceCount = Invoice::count();

		$monthlyData = Invoice::select(DB::raw("DATE_FORMAT(invoice_date, '%m') as month"), DB::raw("SUM(total_amount) as total_amount"))
			->whereYear('invoice_date', Carbon::now()->year)
			->groupBy(DB::raw("DATE_FORMAT(invoice_date, '%Y-%m')"))
			->orderBy(DB::raw("DATE_FORMAT(invoice_date, '%Y-%m')"))
			->get();

		$monthlyTotals = [];
		foreach ($monthlyData as $data) {
			$monthlyTotals[$data->month] = $data->total_amount;
		}

		$TotalAmountArray = [];
		for ($i = 1; $i <= 12; $i++) {
			$month = sprintf('%02d', $i);
			$TotalAmountArray[$month] = isset($monthlyTotals[$month]) ? $monthlyTotals[$month] : 0;
		}

		$countAry = array(
			'customer_count' => $CustomerCount,
			'product_count' => $ProductMasterCount,
			'supplier_count' => $SupplierCount,
			'spare_part_count' => $SparePartCount,
			'order_count' => $OrderCount,
			'invoice_count' => $InvoiceCount,
			'chart_data' => $TotalAmountArray,
		);

		return $this->response("", false, $countAry);
	}

	public function GetMaterialReport()
	{
		$SparePartsData = SpareParts::get();
		$productMasters = ProductMaster::all();
		$orderItems = OrderItem::select('item', DB::raw('SUM(delivery_qty) as total_delivery_qty'))->groupBy('item')->pluck('total_delivery_qty', 'item');

		$sparePartUsage = [];
		foreach ($productMasters as $product) {
			if (!empty($product->spare_parts)) {
				$sparePartsArr = json_decode($product->spare_parts, true);
				foreach ($sparePartsArr as $part) {
					$sparePartUsage[$part['spare_parts_id']] = ($sparePartUsage[$part['spare_parts_id']] ?? 0) + $part['qty'];
				}
			}
		}

		$NewAry = [];
		if (!$SparePartsData->isEmpty()) {
			foreach ($SparePartsData as $sparePart) {
				unset($sparePart->created_at, $sparePart->updated_at, $sparePart->desc);

				$total_delivery_qty = $orderItems[$sparePart->part_name] ?? 0;
				$opening_stock = $sparePart->opening_stock ?? 0;
				$total_opening_and_delivery_qty = $total_delivery_qty + $opening_stock;

				$sparePart->image_name = $sparePart->image ?? '';
				$sparePart->image = asset('/storage/spare_part/' . $sparePart->image);

				$totalSparePartQty = $sparePartUsage[$sparePart->id] ?? 0;
				$sparePart->stock_qty = $stock_qty = ($total_opening_and_delivery_qty - $totalSparePartQty);

				$min_alert_qty = $sparePart->min_alert_qty;
				if ($stock_qty <= $min_alert_qty) {
					$NewAry[] = $sparePart;
				}
			}
		}

		if (!empty($NewAry)) {
			return $this->response(null, false, $NewAry);
		} else {
			return $this->response(null, true);
		}
	}

	public function GetProductReportDashboard(Request $request)
	{
		$perPage = 25;
		$page = $request->input('page', 1);

		$ProductMasterList = ProductMaster::leftJoin('invoice_item', 'invoice_item.product_id', '=', 'product_master.id')
			->select('product_master.id', 'product_master.product_name', 'product_master.min_alert_qty')
			->groupBy('product_master.id')
			->get();

		$FilteredList = collect();
		foreach ($ProductMasterList as $ProductMaster) {
			$stock_qty = ProductStock::where('status', 0)
				->where('product_id', $ProductMaster->id)
				->select(DB::raw("COUNT(id) as total_qty"))
				->first();

			$ProductMaster->stock_qty = $qty = $stock_qty->total_qty ?? 0;
			$min_alert_qty = $ProductMaster->min_alert_qty;
			if ($qty <= $min_alert_qty) {
				$FilteredList->push($ProductMaster);
			}
		}

		$total = $FilteredList->count();
		$results = $FilteredList->forPage($page, $perPage)->values();

		$paginated = new LengthAwarePaginator($results, $total, $perPage, $page, [
			'path' => $request->url(),
			'query' => $request->query(),
		]);

		return $this->response(null, !$results->isEmpty(), $paginated);
	}

	public function GetMaterialReportDashboard(Request $request)
	{
		$perPage = 25;
		$page = $request->input('page', 1);

		$SparePartsData = SpareParts::select('id', 'part_name', 'min_alert_qty', 'stock_qty', 'opening_stock')->get();
		$productMasters = ProductMaster::all();
		$orderItems = OrderItem::select('item', DB::raw('SUM(delivery_qty) as total_delivery_qty'))
			->groupBy('item')
			->pluck('total_delivery_qty', 'item');

		$sparePartUsage = [];
		foreach ($productMasters as $product) {
			if (!empty($product->spare_parts)) {
				$sparePartsArr = json_decode($product->spare_parts, true);
				foreach ($sparePartsArr as $part) {
					$sparePartUsage[$part['spare_parts_id']] = ($sparePartUsage[$part['spare_parts_id']] ?? 0) + $part['qty'];
				}
			}
		}

		$FilteredParts = collect();
		foreach ($SparePartsData as $sparePart) {
			$total_delivery_qty = $orderItems[$sparePart->part_name] ?? 0;
			$opening_stock = $sparePart->opening_stock ?? 0;
			$total_opening_and_delivery_qty = $total_delivery_qty + $opening_stock;

			$totalSparePartQty = $sparePartUsage[$sparePart->id] ?? 0;
			$sparePart->stock_qty = $stock_qty = ($total_opening_and_delivery_qty - $totalSparePartQty);

			$min_alert_qty = $sparePart->min_alert_qty;
			unset($sparePart->id);

			if ($stock_qty <= $min_alert_qty) {
				$FilteredParts->push($sparePart);
			}
		}

		$total = $FilteredParts->count();
		$results = $FilteredParts->forPage($page, $perPage)->values();
		$paginated = new LengthAwarePaginator($results, $total, $perPage, $page, [
			'path' => $request->url(),
			'query' => $request->query(),
		]);

		return $this->response(null, !$results->isEmpty(), $paginated);
	}



	// Employee
	public function GetEmployee()
	{
		$user = auth()->user();
		if ($user->role != 0) {
			return $this->response("Access denied", true);
		}
		$AdminList = Admin::where('role', 1)->orderBy("id", "DESC")->paginate(20);
		if (!empty($AdminList) && count($AdminList) > 0) {
			return $this->response("", false, $AdminList);
		} else {
			return $this->response("Employee Not Found!", true);
		}
	}

	public function AddEmployee(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'name' => 'required',
			'email' => 'required|email|unique:admin',
			'password' => 'required',
		], [
			'name.required' => 'Please Enter Name',
			'email.required' => 'Please Enter Email ID',
			'password.required' => 'Please Enter Password',
		]);

		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}

		$admin = new Admin();
		$admin->name = $request->name;
		$admin->email = $request->email;
		$admin->password = Hash::make($request->password);
		$admin->role = 1;
		$admin->status = 1;
		$admin->save();

		$this->helper->ActivityLog($admin->id, "Add Employee", date('Y-m-d'), json_encode($admin), $this->user->name, "Employee", "", "Create");

		return $this->response("Added Employee Successfully", false);
	}

	public function EditEmployee(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'id' => 'required',
			'name' => 'required',
			'email' => 'required|email|unique:admin,email,' . $request->id,
			'password' => 'required',
		], [
			'id.required' => 'Please Enter ID',
			'name.required' => 'Please Enter Name',
			'email.required' => 'Please Enter Email ID',
			'password.required' => 'Please Enter Password',
		]);

		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}

		$EditEmp = Admin::findOrFail($request->id);
		$EditEmp->update($request->all());
		$getdata = $EditEmp->getChanges();

		$this->helper->ActivityLog($request->id, "Edit Employee", date('Y-m-d'), json_encode($request->all()), $this->user->name, "Employee", json_encode($getdata), "Update");

		return $this->response("Update Employee Successfully", false);
	}

	public function DeleteEmployee(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'id' => 'required',
		], [
			'id.required' => 'Please Enter ID',
		]);

		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}

		Admin::where('id', $request->id)->delete();

		$this->helper->ActivityLog($request->id, "Delete Employee", date('Y-m-d'), "", $this->user->name, "Employee", "", "Delete");
		return $this->response("Admin Delete Successfully");
	}

	public function ChangeEmployeeStatus(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'id' => 'required',
			'status' => 'required',
		], [
			'id.required' => 'Please Enter ID',
			'status.required' => 'Please Change Status',
		]);

		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}

		$EditEmp = Admin::findOrFail($request->id);
		$EditEmp->update($request->all());
		$getdata = $EditEmp->getChanges();

		$this->helper->ActivityLog($request->id, "Change Status Employee", date('Y-m-d'), json_encode(array('status' => $request->status)), $this->user->name, "Employee", json_encode($getdata), "Change Status");

		return $this->response("Status Change Successfully", false);
	}

	public function exit_product_stock_code()
	{
		$code = 'PSC' . str_pad(random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
		$ExitProductStock = ProductStock::where('product_code', $code)->first();
		if (!empty($ExitProductStock)) {
			$code = $this->exit_product_stock_code();
		}
		return $code;
	}

	public function AddProductStore(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'product_id' => 'required',
			'qty' => 'required|integer|min:1',

		], [
			'product_id.required' => 'Please Enter Product  ID',
			'qty.required' => 'Please Enter Quantity',
			'qty.min' => 'Quantity must be at least 1.',
			'qty.integer' => 'Quantity must be a valid integer.'
		]);

		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}

		$qty = isset($request->qty) ? $request->qty : 0;
		if (!empty($qty)) {
			for ($i = 1; $i <= $qty; $i++) {
				$productCode = $this->exit_product_stock_code();

				$productstock = new ProductStock();
				$productstock->product_id = $request->product_id;
				$productstock->product_code = $productCode;
				$productstock->qty = 1;
				$productstock->status = 0;
				$productstock->save();
			}
		}
		return $this->response("Add  Successfully", false);
	}

	public function GetProductStore(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'product_id' => 'required',

		], [
			'product_id.required' => 'Please Enter Product ID',
		]);

		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}

		$ProductStock = ProductStock::where('product_id', $request->product_id)->where('status', 0)->select('product_code', 'id')->get();
		if (!empty($ProductStock) && count($ProductStock) > 0) {
			return $this->response("", false, $ProductStock);
		} else {
			return $this->response("Product Stock Not Found", true);
		}
	}
}
