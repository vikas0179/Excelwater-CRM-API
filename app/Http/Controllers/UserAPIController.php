<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Addresses;
use App\Models\OrderItems;
use App\Models\Orders;
use App\Models\Products;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\PaymentReference;
use App\Models\Admin;
use App\Models\Settings;
use App\Models\ProductReviews;
use App\Models\ProductAttributes;
use App\Models\ProductsVariations;
use App\Models\ProductsVariationsItems;
use App\Models\ContactUs;
use App\Models\GiftCard;
use App\Models\GiftCardOrders;
use App\Models\DiscountHistory;
use App\Models\CountryCheckerModal;
use App\Models\Banners;
use App\Models\ProductResources;
use DB;
use Mail;
use Stripe;
use App\Mail\OrderMail;
use App\Mail\AdminOrderMail;
use App\Mail\ContactUsMail;
use App\Mail\GiftCardOTPMail;
use App\Mail\GiftCardReceiverMail;
use App\Mail\RegisterUserMail;
use App\Mail\PartnerWithUsMail;
use App\Models\ProductMaster;
use PDF;
use Razorpay\Api\Api;
use Razorpay\Api\Errors;

class UserAPIController extends Controller
{

	protected $currency_field_postfix = "";
	protected $current_country_code = "";

	public function __construct(Request $request)
	{
		\Config::set('auth.providers.admin.model', User::class);
		$this->middleware('jwt.verify', ['except' => ['login', 'registration', 'forgot_password', 'verify_token', 'reset_password', 'place_order', 'product_list', 'product_details', 'get_shopping_cart_products', 'get_data', 'get_filter_data', 'contact_us', 'partner_with_us', 'home_page', 'get_gift_card_amounts', 'gift_card_place_order', 'apply_gift_card', 'apply_gift_card_otp', 'get_user_currency', 'payment_success', 'payment_fail', 'razorpay_webhook']]);

		$ip_address = $request->ip();
		$country_log = CountryCheckerModal::select(["id", "country_code", "updated_at"])->where("ip_address", $ip_address)->first();
		$country_code = "";
		if (!empty($country_log)) {
			$country_code = (isset($country_log->country_code)) ? $country_log->country_code : "";
			/* returns min */
			$min = round(abs(time() - strtotime($country_log->updated_at)) / 60, 2);
			$compare_to = 5; /* 5 mins */
			if ($min <= $compare_to) {
				CountryCheckerModal::where("id", $country_log->id)->update(["updated_at" => DB::raw('NOW()')]);
			} else {
				$location = $this->get_ip_info("Visitor", "all");
				CountryCheckerModal::where("id", $country_log->id)->update([
					"country_name" => (isset($location["geoplugin_countryName"])) ? $location["geoplugin_countryName"] : "",
					"country_code" => (isset($location["geoplugin_countryCode"])) ? $location["geoplugin_countryCode"] : "",
				]);
			}
		} else {
			$location = $this->get_ip_info("Visitor", "all");
			CountryCheckerModal::create([
				"ip_address" => $ip_address,
				"country_name" => (isset($location["geoplugin_countryName"])) ? $location["geoplugin_countryName"] : "",
				"country_code" => (isset($location["geoplugin_countryCode"])) ? $location["geoplugin_countryCode"] : "",
			]);
			$country_code = (isset($location["geoplugin_countryCode"])) ? $location["geoplugin_countryCode"] : "";
		}
		$this->current_country_code = (!empty($country_code)) ? strtoupper($country_code) : "";
		if (strtoupper($country_code) == "CA") {
			$this->currency_field_postfix = "";
		} else {
			$this->currency_field_postfix = "_usd";
		}
	}

	public function get_user_currency()
	{
		return $this->get_ip_info("Visitor", "all");
	}

	public function get_gift_card_amounts(Request $request)
	{
		$data = GiftCard::select(["id", "amount"])->orderBy("amount", "ASC")->get();
		return $this->response("", false, $data);
	}

	public function get_regular_price_var()
	{
		return DB::raw("IF(type=0, IF(sale_price{$this->currency_field_postfix}=0, regular_price{$this->currency_field_postfix}, sale_price{$this->currency_field_postfix}), (SELECT IF(pv.sale_price{$this->currency_field_postfix}=0, pv.regular_price{$this->currency_field_postfix}, pv.sale_price{$this->currency_field_postfix}) FROM products_variations AS pv WHERE pv.product_id=products.id ORDER BY pv.regular_price{$this->currency_field_postfix} DESC LIMIT 1)) AS price");
	}

	public function home_page(Request $request)
	{
		$data = [];
		$shop_by_you_need = [];
		$product_categories = [];

		$banners = Banners::select(["path", "call_to_actioin_link", "mobile_path"])->where("type", 0)->orderBy("id", "DESC")->get();
		foreach ($banners as $val) {
			$val->url = $val->path != '' ? asset("storage/home_banners/{$val->path}") : "";
			$val->mobile_url = $val->mobile_path != '' ? asset("storage/home_banners/{$val->mobile_path}") : "";
		}
		$data["banners"] = $banners;

		$shop_by_you_need[] = array(
			"title" => "Water Softening Systems",
			"alt" => "",
			"redirect_to" => "/products/water-softeners",
			"image" => asset('storage/images/hm-water-softener.jpg')
		);

		$shop_by_you_need[] = array(
			"title" => "Water Filtration Systems",
			"alt" => "",
			"redirect_to" => "/products/water-filtration",
			"image" => asset('storage/images/hm-water-filter.jpg')
		);

		$shop_by_you_need[] = array(
			"title" => "Drinking Water - RO Systems",
			"alt" => "",
			"redirect_to" => "/products/reverse-osmosis",
			"image" => asset('storage/images/hm-ro.jpg')
		);

		$data["shop_by_you_need"] = $shop_by_you_need;

		$product_categories["one"] = array(
			"title" => "UV Lights",
			"alt" => "",
			"redirect_to" => "/products/uv-lights",
			"image" => asset('storage/images/hm-uv-bg.jpg')
		);

		$product_categories["two"] = array(
			"title" => "Resin Media",
			"alt" => "",
			"redirect_to" => "/products/resin-and-media",
			"image" => asset('storage/images/hm-resin-bg.jpg')
		);

		$product_categories["three"] = array(
			"title" => "Valves",
			"alt" => "",
			"redirect_to" => "/products/water-softener-parts",
			"image" => asset('storage/images/hm-valves.jpg')
		);

		$product_categories["four"] = array(
			"title" => "Point of Entry Filters",
			"alt" => "",
			"redirect_to" => "/products/point-of-entry-filters",
			"image" => asset('storage/images/hm-poc-bg.jpg')
		);

		$data["product_categories"] = $product_categories;

		$products = ProductMaster::join('products', 'product_master.id', '=', 'products.product_master_id')
			->select(
				'product_master.id as product_master_id',
				'product_master.product_name as title',
				'product_master.image',
				'products.id',
				'products.slug',
				'products.sku',
				'products.type',
				'products.has_stock',
				DB::raw("products.regular_price{$this->currency_field_postfix} as regular_price"),
				DB::raw("products.sale_price{$this->currency_field_postfix} as sale_price"),
				DB::raw("IF(products.type=0, IF(products.sale_price{$this->currency_field_postfix}=0, products.regular_price{$this->currency_field_postfix}, products.sale_price{$this->currency_field_postfix}), (SELECT IF(pv.sale_price{$this->currency_field_postfix}=0, pv.regular_price{$this->currency_field_postfix}, pv.sale_price{$this->currency_field_postfix}) FROM products_variations AS pv WHERE pv.product_id=products.id ORDER BY pv.regular_price{$this->currency_field_postfix} DESC LIMIT 1)) AS price"),
				DB::raw("IF(products.type=0, IF(products.sale_price{$this->currency_field_postfix}=0, products.regular_price{$this->currency_field_postfix}, products.sale_price{$this->currency_field_postfix}), (SELECT IF(pv.sale_price{$this->currency_field_postfix}=0, pv.sale_price{$this->currency_field_postfix}, pv.regular_price{$this->currency_field_postfix}) FROM products_variations AS pv WHERE pv.product_id=products.id ORDER BY pv.regular_price{$this->currency_field_postfix} DESC LIMIT 1)) AS s_price")
			)
			//->whereIn("id", [16, 30, 5, 2])
			->orderBy('product_master.id', 'DESC')
			->limit(10)
			->get();
		foreach ($products as $val) {
			$val->image = !empty($val->image) ? asset('storage/product_master/' . $val->image) : '';
			if ($val->type == 0) {
				$val->sale_price = empty($val->sale_price) ? $val->regular_price : $val->sale_price;
				$has_in_stock = $val->has_stock;
			} else {
				$val->regular_price = $val->s_price;
				$val->sale_price = $val->price;
				$has_in_stock = ProductsVariations::where("product_id", $val->id)->sum("has_stock") == 0 ? 0 : 1;
			}
			$val->has_stock = $has_in_stock;
		}
		$data["product_list"] = $products;
		return $this->response("", false, $data);
	}

	public function get_filter_data()
	{
		$category = Category::select(['id', 'name', 'slug'])->orderBy('name', 'ASC')->get();
		//color items
		$attr_1_list = ProductAttributes::select(['id', 'name', 'slug'])->where('parent_id', 4)->orderBy('name', 'ASC')->get();
		//size items
		$attr_2_list = ProductAttributes::select(['id', 'name', 'slug'])->where('parent_id', 7)->orderBy('name', 'ASC')->get();
		$data = [];
		$data['category_list'] = $category;
		$data['attr_1_list'] = $attr_1_list;
		$data['attr_2_list'] = $attr_2_list;
		return $this->response("", false, $data);
	}

	public function partner_with_us(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'fname' => 'required',
			'lname' => 'required',
			'phone' => 'required',
			'email' => 'required|email',
			'company_name' => 'required',
			'address' => 'required',
			'city' => 'required',
			'state' => 'required',
			'zipcode' => 'required',
			'message' => 'required'
		], [
			'fname.required' => 'Please enter first name',
			'lname.required' => 'Please enter last name',
			'phone.required' => 'Please enter phone number',
			'email.required' => 'Please enter email',
			'email.email' => 'Please enter valid email address',
			'company_name.required' => 'Please select company name',
			'address.required' => 'Please select street address',
			'city.required' => 'Please select city',
			'state.required' => 'Please select state/province',
			'zipcode.required' => 'Please select zip code',
			'message.required' => 'Please tell us about your company',
		]);

		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}

		// $admin = Admin::where("id", 1)->first();
		// Mail::to($admin->email)->send(
		// 	new PartnerWithUsMail($request, $admin)
		// );
		return $this->response("Thank your for sharing your details. We will be in touch within a few hours!", false);
	}

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

		$user = auth()->user();

		return array(
			"token" => $token,
			"name" => "{$user->first_name} {$user->last_name}",
			"email" => $user->email,
			"phone" => $user->phone,
		);
	}

	public function registration(Request $request)
	{

		$validator = Validator::make($request->all(), [
			'name' => 'required',
			'last_name' => 'required',
			'phone' => 'required',
			'email' => 'required|email|unique:users,email',
			'password' => 'required|min:8|regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{6,}$/ ',
		], [
			'name.required' => 'Please enter first name',
			'last_name.required' => 'Please enter last name',
			'email.required' => 'Please enter email',
			'email.email' => 'Please enter valid email address',
			'email.unique' => 'Entered email address is already in used!',
			'phone.required' => 'Please enter phone number',
			'password.required' => 'Please enter password',
			'password.min' => 'Please enter minimum 8 character in password',
			'password.regex' => 'Your password must be more than 8 characters long, should contain at-least 1 Uppercase, 1 Lowercase, 1 Numeric and 1 special character.',
		]);

		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}

		User::create([
			'name' => $request->name,
			'last_name' => $request->last_name,
			'email' => $request->email,
			'mobile' => $request->phone,
			'password' => Hash::make($request->password),
			'visible_pass' => $request->password,
			'role' => 1,
		]);

		// Mail::to($request->email)->send(
		// 	new RegisterUserMail("{$request->name} {$request->last_name}", $request->email, $request->password)
		// );

		$result = $this->generate_token($request);

		if ($result === false)
			return $this->response("Entered email address or password incorrect.", true);

		return $this->response("Congratulations! You have successfully registered with us. Please login to continue.", false, $result);
	}

	public function logout()
	{
		JWTAuth::invalidate(JWTAuth::getToken());
		return $this->response("You are logged out successfully.");
	}

	public function forgot_password(Request $request)
	{

		$validator = Validator::make($request->all(), [
			'email' => 'required|email',
		], [
			'email.required' => 'Please enter email',
			'email.email' => 'Please enter valid email address',
		]);

		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}

		$user = User::where('email', $request->email)->first();

		if (!$user) {
			return $this->response("We have mailed your password reset link.");
		}

		$token = Str::random(64);

		$has_token = DB::table('password_reset_tokens')->where("email", $request->email)->first();

		if ($has_token) {
			DB::table('password_reset_tokens')->where("email", $request->email)->update(['token' => $token]);
		} else {
			DB::table('password_reset_tokens')->insert(['email' => $request->email, 'token' => $token]);
		}

		// Mail::send('emails.api.user-reset-password-api-link', ['token' => $token, "username" => $user->full_name], function ($message) use ($request) {
		// 	$message->to($request->email);
		// 	$message->subject('Reset Password');
		// });

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
			return $this->response("", false);
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
			return $this->response("Invalid user token", true);
		}

		User::where('email', $user->email)->update(['password' => Hash::make($request->password), 'visible_pass' => $request->password]);
		DB::table('password_reset_tokens')->where('email', $user->email)->delete();

		return $this->response("Password reset successfully");
	}

	public function get_profile()
	{
		$id = auth()->user()->id;
		$user = User::select(array("email", "name", "last_name", "mobile", "id"))->where("id", $id)->first();
		if (!$user) {
			return $this->response("User not found", true);
		}
		return $this->response("", false, $user);
	}

	public function update_profile(Request $request)
	{

		$user = auth()->user();

		$validator = Validator::make($request->all(), [
			'first_name' => 'required',
			'last_name' => 'required',
			'phone' => 'required',
			'email' => 'required|email|unique:users,email,' . $user->id,
		], [
			'first_name.required' => 'First name is required',
			'last_name.required' => 'Last name is required',
			'phone.required' => 'Please enter phone',
			'email.required' => 'Please enter email',
			'email.email' => 'Please enter valid email address',
			'email.exists' => 'Entered email address is not found',
		]);

		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}

		$user = User::where('id', $user->id)->first();
		if (!$user) {
			return $this->response("User not found.", true);
		}
		$user->name = $request->first_name;
		$user->last_name = 	$request->last_name;
		$user->email = $request->email;
		$user->mobile = $request->phone;
		$user->save();
		return $this->response("User profile updated.");
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

		User::find(auth()->user()->id)->update(['password' => Hash::make($request->new_password), 'visible_pass' => $request->new_password]);

		return $this->response("Password changed sucessfully.");
	}

	public function add_address(Request $request)
	{

		$validator = Validator::make($request->all(), [
			'address' => 'required',
			'city' => 'required',
			'state' => 'required',
			'zip_code' => 'required',
			'country' => 'required',
		], [
			'address.required' => 'Please enter address',
			'city.required' => 'Please enter city',
			'state.required' => 'Please enter state',
			'zip_code.required' => 'Please enter zipcode',
			'country.required' => 'Please enter country',
		]);

		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}

		$is_default = 0;
		if ($request->has('is_default') && $request->is_default == 1) {
			Addresses::where('user_id', auth()->user()->id)->update(['is_default' => 0]);
			$is_default = $request->is_default;
		}

		Addresses::Create([
			'user_id' => auth()->user()->id,
			'address' => $request->address,
			'city' => $request->city,
			'state' => $request->state,
			'zip_code' => $request->zip_code,
			'country' => $request->country,
			'is_default' => $is_default,
		]);

		return $this->response("Address added successfully.");
	}

	public function get_address(Request $request)
	{

		$user = auth()->user();

		$address = Addresses::select(array(
			'id',
			'address',
			'city',
			'state',
			'zip_code',
			'country',
			'is_default',
		))
			->where('id', $request->id)
			->where('user_id', $user->id)
			->first();

		if (!$address) {
			return $this->response("Address not found.", true);
		}
		return $this->response("", false, $address);
	}

	public function update_address(Request $request)
	{

		$user = auth()->user();

		$validator = Validator::make($request->all(), [
			'id' => 'required',
			'address' => 'required',
			'city' => 'required',
			'state' => 'required',
			'zip_code' => 'required',
			'country' => 'required',
		], [
			'id.required' => 'Please send required parameters.',
			'address.required' => 'Please enter address',
			'city.required' => 'Please enter city',
			'state.required' => 'Please enter state',
			'zip_code.required' => 'Please enter zipcode',
			'country.required' => 'Please enter country',
		]);

		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}

		$address = Addresses::where('id', $request->id)->where('user_id', $user->id)->first();

		if (!$address) {
			return $this->response("Address not found", true);
		}

		$is_default = $address->is_default;

		if ($request->has('is_default') && $request->is_default == 1) {

			Addresses::where('user_id', auth()->user()->id)->update(['is_default' => 0]);

			$is_default = $request->is_default;
		}

		$address->address = $request->address;
		$address->city = $request->city;
		$address->state = $request->state;
		$address->zip_code = $request->zip_code;
		$address->country = $request->country;
		$address->is_default = $is_default;

		$address->save();

		return $this->response("Address updated successfully.");
	}

	public function address_list()
	{

		$user = auth()->user();

		$address = Addresses::select(array(
			'id',
			'address',
			'city',
			'state',
			'zip_code',
			'country',
			'is_default',
		))
			->where('user_id', $user->id)
			->get();

		return $this->response("", false, $address);
	}

	public function delete_address(Request $request)
	{
		$user = auth()->user();
		$address = Addresses::where('user_id', $user->id)->where('id', $request->id)->first();
		if (!$address) {
			return $this->response("Address not found.", true);
		}

		$address->delete();

		return $this->response("Address delete successfully.");
	}

	public function contact_us(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'name' => 'required',
			'reason' => 'required',
			'email' => 'required|email',
			'message' => 'required'
		], [
			'name.required' => 'Please enter name',
			'email.required' => 'Please enter email',
			'email.email' => 'Please enter valid email address',
			'reason.required' => 'Please select reason',
			'message.required' => 'Please enter message',
		]);

		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}

		$contactus = ContactUs::create(array(
			"name" => $request->name,
			"email" => $request->email,
			"reason" => $request->reason,
			"message" => $request->message
		));

		// $admin = Admin::where("id", 1)->first();
		// Mail::to($admin->email)->send(
		// 	new ContactUsMail($contactus, $admin)
		// );

		return $this->response("Thank your for sharing your details. We will be in touch within a few hours!", false);
	}

	public function product_list(Request $request)
	{
		$products = ProductMaster::join("products", "products.product_master_id", "=", "product_master.id")
			->select(
				'product_master.id',
				'products.id as ProductID',
				'product_master.product_name as title',
				'products.slug',
				'products.sku',
				'product_master.image',
				'products.type',
				'products.has_stock',
				DB::raw("products.regular_price{$this->currency_field_postfix} as regular_price"),
				DB::raw("products.sale_price{$this->currency_field_postfix} as sale_price"),
				DB::raw("IF(products.type=0, IF(products.sale_price{$this->currency_field_postfix}=0, products.regular_price{$this->currency_field_postfix}, products.sale_price{$this->currency_field_postfix}), (SELECT IF(pv.sale_price{$this->currency_field_postfix}=0, pv.regular_price{$this->currency_field_postfix}, pv.sale_price{$this->currency_field_postfix}) FROM products_variations AS pv WHERE pv.product_id=products.id ORDER BY pv.regular_price{$this->currency_field_postfix} DESC LIMIT 1)) AS price"),
			)
			->where("products.status", 1);

		if ($request->has('keyword') && !empty($request->keyword)) {
			$products->where(function ($qry) use ($request) {
				$qry->where('product_master.product_name', 'LIKE', '%' . $request->keyword . '%');
				$qry->orWhere('product_master.product_name', 'LIKE', '%' . rtrim($request->keyword, "s") . '%');
			});
		}

		if ($request->has('color_id') && $request->has('size_id')) {
			$arr = [];
			$variations_set = "IF(type=0, '', (SELECT GROUP_CONCAT(pvi.item_id) FROM products_variations_items AS pvi WHERE pvi.product_id=products.id) )";
			if ($request->color_id != "") {
				array_push($arr, "FIND_IN_SET({$request->color_id}, {$variations_set} )");
				$selected_attributes_set = "(SELECT GROUP_CONCAT(pvi.item_id) FROM product_selected_attributes AS pvi WHERE pvi.product_id=products.id)";
				array_push($arr, "FIND_IN_SET({$request->color_id}, {$selected_attributes_set} )");
			}

			if ($request->size_id != "") {
				array_push($arr, "FIND_IN_SET({$request->size_id}, {$variations_set} )");
			}

			if (!empty($arr)) {
				$arr = implode(" OR ", $arr);
				$products->whereRaw("($arr)");
			}
		}

		if ($request->has('sort_by')) {
			$sort_by = strtoupper($request->sort_by);
			if ($sort_by == "PLH") {
				$products->orderBy('price', 'ASC');
			} else if ($sort_by == "PHL") {
				$products->orderBy('price', 'DESC');
			} else if ($sort_by == "WN") {
				$products->orderBy('products.id', 'DESC');
			} else {
				$products->orderBy('products.reorder', 'ASC');
			}
		} else {
			$products->orderBy('products.reorder', 'ASC');
		}

		$category_name = "";
		$bc_category_name = "";
		$bc_sub_category_name = "";
		if ($request->has('category') && $request->category != '') {
			if ($request->category != "all") {
				$category = Category::where('slug', $request->category)->first();
				if ($category) {
					$products->where('products.category', $category->id);
					$category_name = $category->name;
					$bc_category_name = $category;
					$category_filtered = 1;
				} else {
					$sub_category = SubCategory::where("slug", $request->category)->first();
					$bc_sub_category_name = $sub_category;
					if ($sub_category) {
						$products->where('products.sub_category', $sub_category->id);
						$category = Category::where('id', $sub_category->c_id)->first();
						$bc_category_name = $category;
						if ($category) {
							$category_name = "{$category->name} - {$sub_category->name}";
						} else {
							$category_name = $sub_category->name;
						}
					} else {
						$products->where('products.category', 0);
					}
				}
			} else {
				$category_name = "Shop All";
			}
		}

		if ($request->has('keyword') && !empty($request->keyword)) {
			$category_name = "Search : {$request->keyword}";
		}
		$products = $products->paginate(20);

		foreach ($products as $val) {
			$val->image = !empty($val->image) ? asset('storage/product_master/' . $val->image) : '';
			if ($val->type == 0) {
				$val->sale_price = empty($val->sale_price) ? $val->regular_price : $val->sale_price;
				$has_in_stock = $val->has_stock;
			} else {
				$val->regular_price = $val->s_price;
				$val->sale_price = $val->price;
				$has_in_stock = ProductsVariations::where("product_id", $val->id)->sum("has_stock") == 0 ? 0 : 1;
			}
			$val->sale_price = number_format($val->sale_price, 2);
			$val->regular_price = number_format($val->regular_price, 2);
			$val->has_stock = $has_in_stock;
		}

		$breadcrumb = [];
		array_push($breadcrumb, [
			"name" => "Home",
			"slug" => "/"
		]);

		array_push($breadcrumb, [
			"name" => "Shop All",
			"slug" => "/products/all"
		]);

		if ($bc_category_name) {
			array_push($breadcrumb, [
				"name" => $bc_category_name->name,
				"slug" => "/products/{$bc_category_name->slug}"
			]);
		}

		if ($bc_sub_category_name) {
			array_push($breadcrumb, [
				"name" => $bc_sub_category_name->name,
				"slug" => "/products/{$bc_sub_category_name->slug}"
			]);
		}

		return $this->response("", false, array(
			"bc_category_name" => $bc_category_name,
			"breadcrumb" => $breadcrumb,
			"category_name" => $category_name,
			"meta_title" => "EW | Product List",
			"meta_keywords" => "meta keyword",
			"meta_desc" => "meta desc",
			"products" => $products,
		));
	}

	public function product_details(Request $request)
	{
		if (!$request->has('slug')) {
			return $this->response("Slug is required.", true);
		}

		$product = ProductMaster::join('products', 'products.product_master_id', '=', 'product_master.id')
			->where('products.slug', $request->slug)
			->where('products.status', 1)
			->select(array(
				"product_master.id as product_master_id",
				"product_master.product_name as title",
				"product_master.product_code",
				"product_master.price as product_master_price",
				"product_master.min_alert_qty",
				"product_master.desc as description",
				"product_master.image",
				"product_master.spare_parts",
				// Product Table 
				"products.id as product_id",
				"products.slug",
				"products.features",
				"products.specifications",
				"products.sku",
				"products.image_galley",
				"products.has_stock",
				"products.type",
				DB::raw("products.regular_price{$this->currency_field_postfix} as regular_price"),
				DB::raw("products.sale_price{$this->currency_field_postfix} as sale_price"),
				"products.weight",
				"products.attribute_ids",
				"products.category",
				"products.sub_category",
				"products.meta_title",
				"products.meta_description",
				"products.meta_keywords",
				"products.sub_category",
			))->first();

		if (!$product) {
			return $this->response("Product not found.", true);
		}

		$product->image = !empty($product->image) ? asset('storage/product_master/' . $product->image) : '';
		$product->quantity = 1;

		$product_img_arr = [];
		if ($product->image) {
			array_push($product_img_arr, $product->image);
		}

		if (!empty($product->image_galley)) {
			$images = trim($product->image_galley, ", ");
			$imageArray = array_filter(explode(',', $images));
			foreach ($imageArray as $image) {
				$img = asset('storage/product_master/product_gallery/' . $image);
				array_push($product_img_arr, $img);
			}
		}


		// Start Related PRoduct
		$related_products = ProductMaster::join('products', 'product_master.id', '=', 'products.product_master_id')
			->select(array(
				'product_master.id as product_master_id',
				'product_master.product_name as title',
				'product_master.image',
				'products.id as product_id',
				'products.slug',
				'products.sku',
				'products.type',
				'products.has_stock',
				DB::raw("products.regular_price{$this->currency_field_postfix} as regular_price"),
				DB::raw("products.sale_price{$this->currency_field_postfix} as sale_price"),
				DB::raw("IF(products.type=0, IF(products.sale_price{$this->currency_field_postfix}=0, products.regular_price{$this->currency_field_postfix}, products.sale_price{$this->currency_field_postfix}), (SELECT IF(pv.sale_price{$this->currency_field_postfix}=0, pv.regular_price{$this->currency_field_postfix}, pv.sale_price{$this->currency_field_postfix}) FROM products_variations AS pv WHERE pv.product_id=products.id ORDER BY pv.regular_price{$this->currency_field_postfix} DESC LIMIT 1)) AS price")
			));
		if ($product->sub_category > 0) {
			$related_products->where('products.sub_category', $product->sub_category);
		} else {
			$related_products->where('products.category', $product->category);
		}
		$related_products = $related_products->where("products.status", 1)
			->whereNot('products.slug', $request->slug)
			->where('products.parent_id', 0)
			->orderBy('product_master.id', 'DESC')
			->limit(10)
			->get();
		foreach ($related_products as $related_product) {
			$related_product->image = !empty($related_product->image) ? asset('storage/product_master/' . $related_product->image) : '';
			if ($related_product->type == 0) {
				$related_product->sale_price = empty($related_product->sale_price) ? $related_product->regular_price : $related_product->sale_price;
				$has_in_stock = $related_product->has_stock;
			} else {
				$related_product->sale_price = $related_product->price;
				$has_in_stock = ProductsVariations::where("product_id", $related_product->id)->sum("has_stock") == 0 ? 0 : 1;
			}
			$related_product->has_stock = $has_in_stock;
			$related_product->sale_price = number_format($related_product->sale_price, 2);
			$related_product->regular_price = number_format($related_product->regular_price, 2);
		}
		// End Related Products

		$arr_variations = [];
		$arr_variations_prices = [];
		$default_selected_option = [];
		$default_selected_option_item_id = "";

		if ($product->type == 1) {
			$variations = ProductsVariations::select(array("id", "has_stock", DB::raw("sale_price{$this->currency_field_postfix} as sale_price"), DB::raw("regular_price{$this->currency_field_postfix} as regular_price"), "weight"))->where("product_id", $product->product_id)->orderBy("id", "ASC")->get();
			foreach ($variations as $index => $var) {
				if ($index == 0) {
					$default_selected_option = ProductsVariationsItems::select(
						array(
							DB::raw("attribute_id as vId"),
							DB::raw("item_id as id"),
							DB::raw("(SELECT pa.name FROM product_attributes AS pa WHERE pa.id=products_variations_items.item_id) as name"),
						)
					)->where("pv_id", $var->id)->get()->toArray();
				}
				$var->sale_price = number_format($var->sale_price, 2);
				$var->regular_price = number_format($var->regular_price, 2);
				$var->combination = ProductsVariationsItems::select(array("item_id"))->where("pv_id", $var->id)->pluck("item_id")->toArray();
			}
			$arr_variations_prices = $variations;

			$selected_attributes = ProductAttributes::select(array("id", "is_color", "name"))->whereIn("id", explode(",", $product->attribute_ids))->get();
			$default_selected_option_item_id = array_column($default_selected_option, "id");
			$default_selected_option_item_id = implode(",", $default_selected_option_item_id);
			foreach ($selected_attributes as $index => $attr) {
				$attr->items = ProductAttributes::select(array("id", "name", "color_code", DB::raw("IF(FIND_IN_SET(id, '{$default_selected_option_item_id}')>0, 1 ,0 ) AS selected")))->whereIn("id", ProductsVariationsItems::where("product_id", $product->product_id)->where("attribute_id", $attr->id)->pluck("item_id")->toArray())->orderBy("sort_order", "ASC")->get();
			}
			$arr_variations = $selected_attributes;
		}
		$product->default_selected_option = $default_selected_option;
		$product->default_selected_option_item_id = $default_selected_option_item_id;

		$pcategory = Category::select("*")->where("id", $product->category)->first();
		$psubcategory = SubCategory::select("*")->where("id", $product->sub_category)->first();
		$product->category_name = $pcategory->name ?? "";
		$product->sub_category_name = $psubcategory->name ?? "";

		$product->variations = $arr_variations;
		$product->variations_prices = $arr_variations_prices;


		//Start Review
		$reviews = ProductReviews::where("product_id", $product->product_master_id)->where("status", 1);
		// Set Total Rating Product Details
		$product->total_rating = ceil($reviews->avg("rating"));
		$product->total_review = $reviews->count();
		// Set Product Reviews
		$product_reviews = ProductReviews::where("product_id", $product->product_master_id)->where("status", 1)->orderBy('id', 'DESC')->get();
		foreach ($product_reviews as $reviews) {
			$reviews->date = date("d M,Y", strtotime($reviews->created_at));
		}
		// End Product Reviews

		$product->regular_price = number_format($product->regular_price, 2);
		if ($product->type == 0) {
			$product->sale_price = number_format($product->sale_price, 2);
		} else {
			$pv = ProductsVariations::select(
				array(
					"id",
					DB::raw("sale_price{$this->currency_field_postfix} as sale_price"),
					DB::raw("regular_price{$this->currency_field_postfix} as regular_price"),
					"weight"
				)
			)->where("product_id", $product->product_id)->orderBy("id", "ASC")->first();
			$product->regular_price = number_format($pv->regular_price, 2);
			$product->sale_price = number_format($pv->sale_price, 2);
		}
		// End Product Details

		$breadcrumb = [];

		array_push($breadcrumb, [
			"name" => "Home",
			"slug" => "/"
		]);

		array_push($breadcrumb, [
			"name" => "Shop All",
			"slug" => "/products/all"
		]);

		if ($pcategory) {
			array_push($breadcrumb, [
				"name" => $pcategory->name,
				"slug" => "/products/{$pcategory->slug}"
			]);
		}

		if ($psubcategory) {
			array_push($breadcrumb, [
				"name" => $psubcategory->name,
				"slug" => "/products/{$psubcategory->slug}"
			]);
		}

		// Start Resources
		$resources = ProductResources::where("product_id", $product->product_id)->get();
		foreach ($resources as $val) {
			$val->file_url = asset('storage/\product_master/resources/' . $val->filename);
		}
		// End Resources

		$data = [];
		$data['breadcrumb'] = $breadcrumb;
		$data['product'] = $product;
		$data['product_images'] = $product_img_arr;
		$data['related_products'] = $related_products;
		$data['product_reviews'] = $product_reviews;
		$data['resources'] = $resources;
		// Unset
		unset($product->image_galley);
		unset($product->attribute_ids);
		unset($product->category);
		unset($product->sub_category);
		return $this->response("", false, $data);
	}

	public function product_review(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'id' => 'required',
			'name' => 'required',
			'email' => 'required',
			'rating' => 'required|numeric',
			'title' => 'required',
			'description' => 'required',
		], [
			'id.required' => 'Please send required parameters.',
			'name.required' => 'Please enter name.',
			'email.required' => 'Please enter email.',
			'rating.required' => 'Please enter rating.',
			'rating.numeric' => 'Please enter number only in rating.',
			'title.required' => 'Please enter title.',
			'description.required' => 'Please enter description.',
		]);

		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}

		ProductReviews::create([
			'product_id' => $request->id,
			'user_id' => auth()->user()->id,
			'name' => $request->name,
			'rating' => $request->rating,
			'email' => $request->email,
			'title' => $request->title,
			'description' => $request->description,
			'status' => 0,
		]);

		return $this->response("Product review add successfully, it will live shortly.");
	}

	public function get_shopping_cart_products(Request $request)
	{
		$validator = Validator::make(
			$request->all(),
			[
				'data' => 'required',
			],
			[
				'data.required' => 'Please send required parameters',
			]
		);

		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}

		$json = json_decode($request->data);

		if (!is_array($json)) {
			return $this->response("Please send required parameters", true);
		}

		if (empty($json)) {
			return $this->response("", false, []);
		}

		// if (!($request->has("card_number") && $request->has("email"))) {
		// 	return $this->response("Please send required parameters", true);
		// }


		/*********** GIFT CARD START *************/
		$gift_card_amount = 0;
		if (!empty($request->card_number)) {
			$gift_card = GiftCardOrders::where("gift_card_number", $request->card_number)->first();
			if (empty($gift_card)) {
				return $this->response("Gift card not found", true);
			}
			$token = JWTAuth::getToken();
			if ($token) {
				$user = JWTAuth::toUser($token);
				$user_email = $user->email;
			} else {
				$validator = Validator::make($request->all(), [
					'email' => 'required',
				], [
					'email.required' => 'Please fill login details or login to apply gift card',
				]);
				if ($validator->fails()) {
					return $this->response($validator->errors()->first(), true);
				}
				$user_email = $request->email;
			}
			if (strtoupper($gift_card->to_email) != strtoupper($user_email)) {
				return $this->response("Gift card not found", true);
			}
			if ($gift_card->pending_amount == 0) {
				return $this->response("You have used all amount for this gift card.", true);
			}
			if (today()->format('Y-m-d') > date("Y-m-d", strtotime($gift_card->expiry_date))) {
				return $this->response("Gift card is expired.", true);
			}
			$gift_card_amount = $gift_card->pending_amount;
		}
		/*********** GIFT CARD END *************/


		$product_arr = [];
		$sub_total = 0;
		$grand_total = 0;
		$total_tax = 0;
		$shipping_cost = $this->current_country_code == "CA" ? 99 : 75;

		foreach ($json as $val) {
			$product = ProductMaster::from('product_master as pm')
				->join('products as p', 'p.product_master_id', '=', 'pm.id')
				->select(array(
					"pm.id as product_master_id",
					"p.id",
					"pm.product_name as title",
					"p.slug",
					"p.sku",
					"pm.image",
					"p.type",
					DB::raw("sale_price{$this->currency_field_postfix} as sale_price"),
					DB::raw("regular_price{$this->currency_field_postfix} as regular_price"),
					"attribute_ids",
					"category",
					"sub_category"
				))->where("pm.id", $val->id ?? -1)->where("p.status", 1)->first();


			if (empty($product)) {
				continue;
			}

			$cate_arr = [];

			$category_name = Category::select("name")->where("id", $product->category)->pluck("name")->first() ?? "";
			$sub_category_name = Category::select("name")->where("id", $product->sub_category)->pluck("name")->first() ?? "";

			if (!empty($category_name)) {
				array_push($cate_arr, $category_name);
			}

			if (!empty($sub_category_name)) {
				array_push($cate_arr, $sub_category_name);
			}

			$product->category_name = implode("->", $cate_arr);

			$product->image = !empty($product->image) ? asset('storage/product_master/' . $product->image) : '';

			if ($product->type == 1) {
				$current_variation = ProductsVariations::select([DB::raw("sale_price{$this->currency_field_postfix} as sale_price"), DB::raw("regular_price{$this->currency_field_postfix} as regular_price")])->whereIn("id", ProductsVariationsItems::where("product_id", $product->id)->whereIn("item_id", $val->variations ?? [])->pluck("pv_id"))->first();

				$product->regular_price = $current_variation->regular_price ?? 0;
				$product->sale_price = $current_variation->sale_price ?? 0;

				$product->variations = collect(ProductAttributes::select(array(
					DB::raw("CONCAT( '<strong>', (SELECT pa.name FROM product_attributes AS pa WHERE pa.id=product_attributes.parent_id) ,'</strong>',' : ', name) as attr_name")
				))->whereIn("id", $val->variations ?? [])->pluck("attr_name"))->implode(", ");
			} else {
				$product->variations = "";
			}

			$row_total = empty($product->sale_price) ? $product->regular_price : $product->sale_price;

			$row_total = $row_total * $val->qty ?? 1;

			$product->row_total = number_format($row_total, 2);

			$sub_total += $row_total;

			$product->regular_price = number_format($product->regular_price, 2);
			$product->sale_price = number_format($product->sale_price, 2);

			array_push($product_arr, $product);
		}

		$tax = Settings::where('id', 1)->first();

		$total_tax = ($sub_total * $tax->value) / 100;

		$arr = [];
		$arr["sub_total"] = number_format($sub_total, 2);
		$arr["total_tax"] = number_format($total_tax, 2);
		$arr["tax_percent"] = $tax->value;
		$arr["shipping_cost"] = number_format($shipping_cost, 2);

		$grand_total_before_discount = $sub_total + $total_tax + $shipping_cost;

		if ($gift_card_amount > $grand_total_before_discount) {
			$total_discount = $grand_total_before_discount;
		} else {
			$total_discount = $gift_card_amount;
		}

		$arr["total_discount"] = number_format($total_discount, 2);

		$arr["grand_total"] = number_format($sub_total + $total_tax - $total_discount + $shipping_cost, 2);

		$arr["product"] = $product_arr;

		if ($this->current_country_code == "IN") {
			$allow_stripe = 0;
			$allow_razorpay = 1;
			$default_pg_selected = 1;
		} else {
			$allow_stripe = 0;
			$allow_razorpay = 1;
			$default_pg_selected = 1;
		}

		$allow_stripe = 1;
		$allow_razorpay = 0;
		$default_pg_selected = 0;

		$arr["allow_stripe"] = $allow_stripe;
		$arr["allow_razorpay"] = $allow_razorpay;
		$arr["default_pg_selected"] = $default_pg_selected;

		return $this->response("", false, $arr);
	}

	public function get_data()
	{
		$header_menu = [];
		$header_menu[] = array("name" => "Shop All", "id" => 0, "next_tab" => false, "slug" => "/products/all", "sub" => []);
		$shop = [];

		$shop = Category::select(["id", "name", "slug"])->where("status", 1)->get()->toArray();
		foreach ($shop as $val) {
			$submenu = SubCategory::select(["id", "name", "slug"])->where("c_id", $val["id"])->orderBy("reorder", "ASC")->get();
			$smenu = [];
			foreach ($submenu as $sm) {
				array_push(
					$smenu,
					[
						"name" => $sm["name"],
						"id" => $sm["id"],
						"next_tab" => false,
						"slug" => "/products/{$sm["slug"]}",
						"sub" => []
					]
				);
			}
			$header_menu[] = array("name" => $val["name"], "id" => -1, "next_tab" => false, "slug" => "/products/{$val["slug"]}", "sub" => $smenu);
		}
		$page_about_us = array("name" => "About Us", "id" => -1, "next_tab" => false, "slug" => "/about-us", "sub" => []);
		$page_contact_us = array("name" => "Contact Us", "id" => -1, "next_tab" => false, "slug" => "/contact-us", "sub" => []);
		$page_partner_with_us = array("name" => "Partner with Us", "id" => -1, "next_tab" => false, "slug" => "/partner-with-us", "sub" => []);
		$page_water_tips_and_resources = array("name" => "Water Tips and Resources", "id" => -1, "next_tab" => true, "slug" => "https://excelwater.ca/blog", "sub" => []);
		$header_menu[] = $page_partner_with_us;
		$footer_menu = [];
		$site_links = [];
		$site_links[] = $page_about_us;
		$site_links[] = $page_partner_with_us;
		$site_links[] = $page_water_tips_and_resources;
		$footer_menu[] = array(
			"heading" => "Site Links",
			"menu" => $site_links
		);
		$shop_menu = [];
		$shop_menu[] = array("name" => "Shop All", "id" => -1, "next_tab" => false, "slug" => "/products/all", "sub" => []);
		foreach ($shop as $val) {
			$shop_menu[] = array("name" => $val["name"], "id" => -1, "next_tab" => false, "slug" => "/products/{$val["slug"]}", "sub" => []);
		}
		$footer_menu[] = array(
			"heading" => "Shop",
			"menu" => $shop_menu
		);
		$page_my_account = array("name" => "My Account", "id" => -1, "next_tab" => false, "slug" => "/account/edit-personal-details", "sub" => []);
		$page_warranty_statement = array("name" => "Warranty Statement", "id" => -1, "next_tab" => false, "slug" => "/warranty-statement", "sub" => []);
		$page_shipping_and_returns_policy = array("name" => "Shipping and Returns Policy", "id" => -1, "next_tab" => false, "slug" => "/shipping-and-returns-policy", "sub" => []);
		$page_privacy_policy = array("name" => "Privacy Policy", "id" => -1, "next_tab" => false, "slug" => "/privacy-policy", "sub" => []);
		$page_terms_conditions = array("name" => "Terms and Conditions", "id" => -1, "next_tab" => false, "slug" => "/terms-and-conditions", "sub" => []);
		$help_menu = [];
		$help_menu[] = $page_contact_us;
		$help_menu[] = $page_my_account;
		$help_menu[] = $page_warranty_statement;
		$help_menu[] = $page_shipping_and_returns_policy;
		$help_menu[] = $page_privacy_policy;
		$help_menu[] = $page_terms_conditions;
		$footer_menu[] = array(
			"heading" => "Help",
			"menu" => $help_menu
		);
		$result = [];
		$result["header_menu"] = $header_menu;
		$result["footer_menu"] = $footer_menu;
		$result["currency_symbol"] = $this->_currency_symbol();
		return $this->response("", false, $result);
	}

	public function _currency_symbol()
	{
		$currency_symbol = "";
		if (strtoupper($this->currency_field_postfix) == "_INR") {
			$currency_symbol = "&#8377; ";
		} else if (strtoupper($this->currency_field_postfix) == "_USD") {
			$currency_symbol = "$";
		} else {
			$currency_symbol = self::get_currency_symbol();
		}
		return $currency_symbol;
	}

	public function _currency_symbol_strip()
	{
		$currency_symbol = "";
		if (strtoupper($this->currency_field_postfix) == "_INR") {
			$currency_symbol = "INR";
		} else if (strtoupper($this->currency_field_postfix) == "_USD") {
			$currency_symbol = "USD";
		} else {
			$currency_symbol = "AED";
		}
		return $currency_symbol;
	}

	public function place_order(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'data' => 'required',
			'payment_gateway' => 'required',
			'terms_and_condition' => 'required',
		], [
			'data.required' => 'Please send required parameters',
			'payment_gateway.required' => 'Please send required parameters',
			'terms_and_condition.required' => 'Please accept terms and conditions.',
		]);

		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}

		$json = json_decode($request->data);

		if (!is_array($json)) {
			return $this->response("Please send required parameters", true);
		}

		if (empty($json)) {
			return $this->response("Please select products", false, []);
		}

		$missing = 0;
		foreach ($json as $val) {
			$product = ProductMaster::join('products', 'products.product_master_id', '=', 'product_master.id')
				->select(array(
					"product_master.id as product_master_id",
					"products.id",
					"product_master.product_name as title",
					"products.slug",
					"products.sku",
					"product_master.image",
					"products.type",
					DB::raw("products.sale_price{$this->currency_field_postfix} as sale_price"),
					DB::raw("products.regular_price{$this->currency_field_postfix} as regular_price"),
					"products.attribute_ids",
					"products.category",
					"products.sub_category"
				))->where("product_master.id", $val->id ?? -1)->where("products.status", 1)->first();
			if (empty($product)) {
				$missing++;
			}
		}

		if ($missing != 0) {
			return $this->response("Some of the products are not available, please remove it and place order again...", true, []);
		}

		$token = JWTAuth::getToken();
		if ($token) {
			$validator = Validator::make($request->all(), [
				'billing_address' => 'required',
				'shipping_address' => 'required',
			], [
				'billing_address.required' => 'Please select billiing address',
				'shipping_address.required' => 'Please select shipping address',
			]);
			if ($validator->fails()) {
				return $this->response($validator->errors()->first(), true);
			}

			$user = JWTAuth::toUser($token);
			$billing_address_id = $request->billing_address;
			$shipping_address_id = $request->shipping_address;
		} else {
			$validator = Validator::make($request->all(), [
				'first_name' => 'required',
				'last_name' => 'required',
				'phone' => 'required',
				'email' => 'required|email|unique:users,email',
				'password' => 'required|min:8|regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{6,}$/ ',
				'b_address' => 'required',
				'b_city' => 'required',
				'b_state' => 'required',
				'b_zipcode' => 'required',
				'b_country' => 'required',
				's_address' => 'required',
				's_city' => 'required',
				's_state' => 'required',
				's_zipcode' => 'required',
				's_country' => 'required',
			], [
				'first_name.required' => 'Please enter first name',
				'last_name.required' => 'Please enter last name',
				'email.required' => 'Please enter email',
				'email.email' => 'Please enter valid email address',
				'email.unique' => 'Entered email address is already in used!',
				'phone.required' => 'Please enter phone number',
				'password.required' => 'Please enter password',
				'password.min' => 'Please enter minimum 8 character in password',
				'password.regex' => 'Your password must be more than 8 characters long, should contain at-least 1 Uppercase, 1 Lowercase, 1 Numeric and 1 special character.',
				'b_address.required' => 'Please enter billing address',
				'b_country.required' => 'Please enter billing country',
				'b_state.required' => 'Please enter billing state',
				'b_city.required' => 'Please enter billing city',
				'b_zipcode.required' => 'Please enter billing zipcode',
				's_address.required' => 'Please enter shipping address',
				's_country.required' => 'Please enter shipping country',
				's_state.required' => 'Please enter shipping state',
				's_city.required' => 'Please enter shipping city',
				's_zipcode.required' => 'Please enter shipping zipcode',
			]);

			if ($validator->fails()) {
				return $this->response($validator->errors()->first(), true);
			}

			$user = User::create([
				'name' => $request->first_name,
				'last_name' => $request->last_name,
				'email' => $request->email,
				'phone' => $request->phone,
				'password' => Hash::make($request->password),
				'visible_pass' => $request->password,
			]);

			// Mail::to($request->email)->send(
			// 	new RegisterUserMail("{$request->first_name} {$request->last_name}", $request->email, $request->password)
			// );

			$billing_address_id = 0;
			$shipping_address_id = 0;

			if (
				$request->b_address == $request->s_address
				&& $request->b_country == $request->s_country
				&& $request->b_state == $request->s_state
				&& $request->b_city == $request->s_city
				&& $request->b_zipcode == $request->s_zipcode
			) {
				$billing_address = Addresses::Create([
					'user_id' => $user->id,
					'address' => $request->b_address,
					'country' => $request->b_country,
					'state' => $request->b_state,
					'city' => $request->b_city,
					'zip_code' => $request->b_zipcode,
					'is_default' => 1,
				]);

				$billing_address_id = $billing_address->id;
				$shipping_address_id = $billing_address->id;
			} else {

				$billing_address = Addresses::Create([
					'user_id' => $user->id,
					'address' => $request->b_address,
					'country' => $request->b_country,
					'state' => $request->b_state,
					'city' => $request->b_city,
					'zip_code' => $request->b_zipcode,
					'is_default' => 0,
				]);

				$shipping_address = Addresses::Create([
					'user_id' => $user->id,
					'address' => $request->s_address,
					'country' => $request->s_country,
					'state' => $request->s_state,
					'city' => $request->s_city,
					'zip_code' => $request->s_zipcode,
					'is_default' => 1,
				]);

				$billing_address_id = $billing_address->id;
				$shipping_address_id = $shipping_address->id;
			}
		}

		/*********** GIFT CARD START *************/
		$gift_card_amount = 0;
		$discount_type = -1;
		$discount_code = "";
		$discount_code_id = -1;
		if (!empty($request->card_number)) {
			$gift_card = GiftCardOrders::where("gift_card_number", $request->card_number)->first();
			if (empty($gift_card)) {
				return $this->response("Gift card not found", true);
			}

			$token = JWTAuth::getToken();
			if ($token) {
				$user = JWTAuth::toUser($token);
				$user_email = $user->email;
			} else {
				$validator = Validator::make($request->all(), [
					'email' => 'required',
				], [
					'email.required' => 'Please fill login details or login to apply gift card',
				]);
				if ($validator->fails()) {
					return $this->response($validator->errors()->first(), true);
				}
				$user_email = $request->email;
			}

			if (strtoupper($gift_card->to_email) != strtoupper($user_email)) {
				return $this->response("Gift card not found", true);
			}

			if ($gift_card->pending_amount == 0) {
				return $this->response("You have used all amount for this gift card.", true);
			}

			if (today()->format('Y-m-d') > date("Y-m-d", strtotime($gift_card->expiry_date))) {
				return $this->response("Gift card is expired.", true);
			}

			$gift_card_amount = $gift_card->pending_amount;
			$discount_type = 0;
			$discount_code = $gift_card->gift_card_number;
			$discount_code_id = $gift_card->id;
		}
		/*********** GIFT CARD END *************/

		$tax = Settings::where('id', 1)->first();
		$tax_percent = $tax->value;
		$order_items = [];
		$sub_total = 0;

		foreach ($json as $val) {
			$product = ProductMaster::join('products', 'product_master.id', '=', 'products.product_master_id')
				->select(
					'product_master.id as product_master_id',
					'product_master.product_name as title',
					'products.id',
					'products.slug',
					'products.image',
					'products.type',
					'products.attribute_ids',
					'products.category',
					'products.sub_category',
					DB::raw("products.regular_price{$this->currency_field_postfix} as regular_price"),
					DB::raw("products.sale_price{$this->currency_field_postfix} as sale_price"),
					DB::raw("IF(products.type=0, IF(products.sale_price{$this->currency_field_postfix}=0, products.regular_price{$this->currency_field_postfix}, products.sale_price{$this->currency_field_postfix}), (SELECT IF(pv.sale_price{$this->currency_field_postfix}=0, pv.regular_price{$this->currency_field_postfix}, pv.sale_price{$this->currency_field_postfix}) FROM products_variations AS pv WHERE pv.product_id=products.id ORDER BY pv.regular_price{$this->currency_field_postfix} DESC LIMIT 1)) AS price"),
					DB::raw("IF(products.type=0, IF(products.sale_price{$this->currency_field_postfix}=0, products.regular_price{$this->currency_field_postfix}, products.sale_price{$this->currency_field_postfix}), (SELECT IF(pv.sale_price{$this->currency_field_postfix}=0, pv.sale_price{$this->currency_field_postfix}, pv.regular_price{$this->currency_field_postfix}) FROM products_variations AS pv WHERE pv.product_id=products.id ORDER BY pv.regular_price{$this->currency_field_postfix} DESC LIMIT 1)) AS s_price"),
				)
				->where('product_master.id', $val->id)
				->where('products.status', 1)
				->first();

			if ($product->type == 1) {
				$regular_price = $product->s_price;
				$sale_price = $product->price;
			} else {
				$regular_price = $product->regular_price;
				$sale_price = $product->sale_price;
			}

			$base_amount = empty($sale_price) ? $regular_price : $sale_price;
			$row_total = $base_amount * $val->qty ?? 1;
			$tax_amount = ($row_total * $tax_percent) / 100;
			$sub_total += $row_total;

			$arr = [];
			$arr["product_id"] = $product->product_master_id;
			$arr["product_name"] = $product->title;

			if ($product->type == 1) {
				$arr["variations"] = $val->variations ? implode(",", $val->variations) : "";
				$arr["sub_title"] = collect(ProductAttributes::select(array(
					DB::raw("CONCAT( '<strong>', (SELECT pa.name FROM product_attributes AS pa WHERE pa.id=product_attributes.parent_id) ,'</strong>',' : ', name) as attr_name")
				))->whereIn("id", $val->variations ?? [])->pluck("attr_name"))->implode(", ");
			} else {
				$arr["variations"] = "";
				$arr["sub_title"] = "";
			}

			$arr["base_amount"] = $base_amount;
			$arr["quantity"] = $val->qty;
			$arr["tax_amount"] = $tax_amount;
			$arr["discount_amount"] = 0;
			$arr["total_amount"] = $row_total;
			array_push($order_items, $arr);
		}

		$total_tax = ($sub_total * $tax_percent) / 100;
		$grand_total = $sub_total + $total_tax;
		$shipping_cost = $this->current_country_code == "CA" ? 99 : 75;
		$grand_total_before_discount = $sub_total + $total_tax + $shipping_cost;

		if ($gift_card_amount > $grand_total_before_discount) {
			$total_discount = $grand_total_before_discount;
		} else {
			$total_discount = $gift_card_amount;
		}

		$order_number = $this->get_ord_id();
		$order_hash = md5(time() . "_order_no:{$order_number}");

		$order = Orders::create([
			'order_no' => $order_number,
			'user_id' => $user->id,
			'pay_status' => 0,
			'base_amount' => $sub_total,
			'discount_amount' => $total_discount,
			'shipping_amount' => $shipping_cost,
			'tax_amount' => $total_tax,
			'tax_percent' => $tax_percent,
			'total_amount' => $grand_total,
			'status' => 0,
			'instructions' => $request->notes ?? "",
			'order_hash' => $order_hash,
			'billing_id' => $billing_address_id,
			'shipping_id' => $shipping_address_id,
			'discount_type' => $discount_type,
			'discount_code_id' => $discount_code_id,
			'discount_code' => $discount_code,
			'currency_symbol' => $this->_currency_symbol(),
			'paid_by' => $request->payment_gateway,
		]);

		foreach ($order_items as $index => $val) {
			$arr = $val;
			$arr["order_id"] = $order->id;
			OrderItems::create($arr);
		}

		/* If the amount are same and no payable amount pending then place order direct */
		if ($grand_total == $total_discount) {
			if ($discount_type == 0) {
				DiscountHistory::create([
					"amount" => $total_discount,
					'type' => $discount_type,
					'code_id' => $discount_code_id,
					'code' => $discount_code,
					'order_id' => $order->id,
					'user_id' => $user->id
				]);
				$gift_card_orders = GiftCardOrders::where("id", $discount_code_id)->first();
				$gift_card_orders->is_claimed = 1;
				$gift_card_orders->pending_amount = $gift_card_orders->pending_amount - $total_discount;
				$gift_card_orders->save();
			}
			$order = Orders::where('id', $order->id)->first();
			$order->pay_status = 1;
			$order->save();

			$user = User::where("id", $user->id)->first();
			$admin = Admin::where("id", 1)->first();
			$billing_address = Addresses::where('id', $order->billing_id)->first();
			$shipping_address = Addresses::where('id', $order->shipping_id)->first();
			$order_items = OrderItems::where('order_id', $order->id)->get();

			$pdf = PDF::loadView('emails.api.order-invoice-pdf', compact('order_items', 'order', 'shipping_address', 'billing_address', 'admin', 'user'));
			$user_mail = Mail::to($user->email)->send(new OrderMail($pdf, $user, $order, $order_items, $shipping_address, $billing_address));
			$admin_mail = Mail::to($admin->email)->send(new AdminOrderMail($pdf, $user, $admin, $order, $order_items, $shipping_address, $billing_address));

			return $this->response("", false, array(
				"client_secret" => "",
				"key" => "",
				"order_hash" => $order_hash,
			));
		}

		$order = Orders::where('id', $order->id)->first();
		$user = User::where("id", $user->id)->first();
		$admin = Admin::where("id", 1)->first();

		if ($request->payment_gateway == 0) {
			\Stripe\Stripe::setApiKey(getenv("STRIPE_SECRET_KEY"));
			if (empty($user->stripe_customer_id)) {
				$customer = \Stripe\Customer::create(["name" => "{$user->first_name} {$user->last_name}", "email" => "{$user->email}"]);
				$customer = json_decode(json_encode($customer), true);
				$customer_id = $customer["id"];
				$user->stripe_customer_id = $customer_id;
				$user->save();
			} else {
				$customer_id = $user->stripe_customer_id;
			}

			try {
				$description = array_column($order_items, "product_name");
				$description = implode(", ", $description);
				$intent = \Stripe\PaymentIntent::create([
					// 'amount' => $grand_total * 100,
					'amount' => intval(round($grand_total * 100)), // convert to integer
					'currency' => $this->_currency_symbol_strip(),
					'customer' => $customer_id,
					'description' => $description,
					'metadata' => [
						"cust_id" => $user->id,
						"order_id" => $order->id,
						"payment_for" => "ORD"
					],
				]);

				if (isset($intent->client_secret)) {
					if ($discount_type == 0) {
						DiscountHistory::create([
							"amount" => $total_discount,
							'type' => $discount_type,
							'code_id' => $discount_code_id,
							'code' => $discount_code,
							'order_id' => $order->id,
							'user_id' => $user->id
						]);
						$gift_card_orders = GiftCardOrders::where("id", $discount_code_id)->first();
						$gift_card_orders->is_claimed = 1;
						$gift_card_orders->pending_amount = $gift_card_orders->pending_amount - $total_discount;
						$gift_card_orders->save();
					}

					return $this->response("", false, array(
						"pg_type" => 0,
						"client_secret" => $intent->client_secret,
						"key" => getenv("STRIPE_PUBLISHABLE_KEY"),
						"order_hash" => $order_hash,
					));
				} else {
					return $this->response("Something went going wrong, please try again...", true);
				}
			} catch (\Stripe\Exception\Exception $e) {
				return $this->response("Something went going wrong, please try again...", true);
			}
		} else if ($request->payment_gateway == 1) {
			$api_key = getenv("RAZORPAY_KEY", "");
			$api = new Api($api_key, getenv("RAZORPAY_SECRET", ""));
			$description = array_column($order_items, "product_name");
			$description = implode(", ", $description);

			try {
				$razorpayOrder = $api->order->create(array(
					'receipt' => $order_number,
					'amount' => $grand_total * 100,
					'currency' => $this->current_country_code == "IN" ? "INR" : "USD"
				));
				if ($discount_type == 0) {
					DiscountHistory::create([
						"amount" => $total_discount,
						'type' => $discount_type,
						'code_id' => $discount_code_id,
						'code' => $discount_code,
						'order_id' => $order->id,
						'user_id' => $user->id
					]);
					$gift_card_orders = GiftCardOrders::where("id", $discount_code_id)->first();
					$gift_card_orders->is_claimed = 1;
					$gift_card_orders->pending_amount = $gift_card_orders->pending_amount - $total_discount;
					$gift_card_orders->save();
				}
			} catch (\Exception $e) {
				return $this->response("Payment failed, Please try again...", true, $e->getMessage());
			}
			if (empty($razorpayOrder)) {
				return $this->response("Payment failed, Please try again... order empty", true);
			}

			$data = [];
			$data["razorpay_key"] = $api_key;
			$data["payment_id"] = $order->id;
			$data["amount"] = $grand_total;
			$data["order_id"] = $razorpayOrder->id;
			$data["entity"] = $razorpayOrder->entity;
			$data["currency"] = $razorpayOrder->currency;
			$data["receipt"] = $razorpayOrder->receipt;
			$data["name"] = "{$user->first_name} {$user->last_name}";
			$data["email"] = $user->email;
			$data["phone"] = $user->phone;
			$data["description"] = $description;
			$data["pg_type"] = 1;
			return $this->response("", false, $data);
		}

		return $this->response("Something went going wrong, please try again...", true);

		/*$billing_address = Addresses::where('id',$billing_address_id )->first();
		$shipping_address = Addresses::where('id',$shipping_address_id )->first();
		
		$order_items = OrderItems::where('order_id',$order->id)->get();
		
		$pdf = PDF::loadView('emails.api.order-invoice-pdf',compact('order_items','order','shipping_address','billing_address','admin'));

		$user_mail = Mail::to($user->email)->send(new OrderMail($pdf,$user,$order,$order_items,$shipping_address,$billing_address )); 
		$admin_mail = Mail::to($admin->email)->send(new AdminOrderMail($pdf,$user,$admin,$order,$order_items,$shipping_address,$billing_address )); 
		
		if(!$user_mail || !$admin_mail){
			return $this->response("Please try again,something went going wrong.");
		}*/

		return $this->response("Order Placed Successfully.");
	}

	public function order_list()
	{
		$user = Auth()->user();
		$orders = Orders::select("orders.*", "payment_reference.pay_by")
			->leftJoin("payment_reference", "payment_reference.id", "=", "orders.payment_ref_id")
			->where('user_id', $user->id)
			->orderBy('id', "DESC")
			->get();
		foreach ($orders as $order) {
			$order->date = date('d M,Y', strtotime($order->created_at));
			$order->total_amount = number_format($order->total_amount, 2);
			$items_arr = [];
			$items = OrderItems::where('order_id', $order->id)->get();
			foreach ($items as $item) {
				$arr = [];
				$arr['product_id'] = $item['product_id'];
				$arr['parent_id'] = $item['parent_id'];
				$arr['product_name'] = $item['product_name'];
				$arr['base_amount'] = $item['base_amount'];
				$arr['quantity'] = $item['quantity'];
				$arr['tax_amount'] = $item['tax_amount'];
				$arr['discount_amount'] = $item['discount_amount'];
				$arr['total_amount'] = $item['total_amount'];
				array_push($items_arr, $arr);
			}
			$order->items = isset($items_arr) ? $items_arr : [];
		}
		return $this->response("", false, $orders);
	}

	public function order_details(Request $request)
	{
		$order = Orders::where('order_no', $request->order_no)->first();
		if (!$order) {
			return $this->response("Order details not found.", true);
		}

		$items_arr = [];
		$user = User::where('id', $order->user_id)->first();
		$order->username = "{$user->name} {$user->last_name}";
		$order->email =  $user->email;
		$order->phone =  $user->phone;
		$order->date = date("d M,Y", strtotime($order->created_at));
		$order->total_amount = number_format($order->total_amount, 2);

		$items = OrderItems::where('order_id', $order->id)->get();
		foreach ($items as $item) {
			$arr = [];
			$product = ProductMaster::join('products', 'product_master.id', '=', 'products.product_master_id')
				->where('product_master.id', $item->product_id)
				->select('product_master.image', 'products.category', 'products.sub_category')
				->first();
			$category = Category::where('id', $product->category)->first();
			$sub_category = SubCategory::where('id', $product->sub_category)->first();
			$arr['product_id'] = $item['product_id'];
			$arr['parent_id'] = $item['parent_id'];
			$arr['image'] = !empty($product->image) ? asset('storage/product_master/' . $product->image) : asset('assets/admin/media/svg/files/blank-image.svg');
			$arr['category_name'] = $category->name;
			$arr['sub_category_name'] = isset($sub_category->name) ? $sub_category->name : '';
			$arr['product_name'] = $item['product_name'];
			$arr['base_amount'] = number_format($item['base_amount'], 2);
			$arr['quantity'] = $item['quantity'];
			$arr['tax_amount'] = number_format($item['tax_amount'], 2);
			$arr['discount_amount'] = number_format($item['discount_amount'], 2);
			$arr['total_amount'] = number_format($item['total_amount'], 2);
			array_push($items_arr, $arr);
		}
		$order->items = isset($items_arr) ? $items_arr : [];
		$billing_address = Addresses::where('id', $order->billing_id)->first();
		$shipping_address = Addresses::where('id', $order->shipping_id)->first();
		$order->billing_address = isset($billing_address) ? $billing_address : [];
		$order->shipping_address = isset($shipping_address) ? $shipping_address : [];
		return $this->response("", false, $order);
	}

	public function print_invoice($id)
	{
		$user = Auth()->user();

		$order = Orders::select("*")->where('id', $id)->where('user_id', $user->id)->first();
		if (empty($order)) {
			return back();
		}

		$admin = Admin::where("id", 1)->first();
		$billing_address = Addresses::where('id', $order->billing_id)->first();
		$shipping_address = Addresses::where('id', $order->shipping_id)->first();
		$billing_address = isset($billing_address) ? $billing_address : [];
		$shipping_address = isset($shipping_address) ? $shipping_address : [];
		$order_items = OrderItems::where('order_id', $order->id)->get();
		$pdf = PDF::loadView('emails.api.order-invoice-pdf', compact('order_items', 'order', 'shipping_address', 'billing_address', 'admin', 'user'));
		return $pdf->stream("dompdf_out.pdf");
	}

	public function gift_card_order_list(Request $request)
	{
		$user_id = Auth()->user()->id;
		$list = GiftCardOrders::leftJoin("users", function ($join) {
			$join->on("gift_card_orders.user_id", "=", "users.id");
		})->select([
			"gift_card_orders.*",
			"users.name as first_name",
			"users.last_name",
			"users.phone"
		])->where("user_id", $user_id)->orderBy("id", "DESC")->get();
		foreach ($list as $val) {
			$val->date = date('d M,Y', strtotime($val->created_at));
			$val->expiry_date_f = date('d M,Y', strtotime($val->expiry_date));
			$val->is_expired = is_null($val->expiry_date) ? false : (today()->format('Y-m-d') > date("Y-m-d", strtotime($val->expiry_date)) ? true : false);
		}
		return $this->response("", false, $list);
	}

	public function gift_card_order_details($id)
	{
		$user_id = Auth()->user()->id;

		$list = GiftCardOrders::leftJoin("users", function ($join) {
			$join->on("gift_card_orders.user_id", "=", "users.id");
		})->select([
			"gift_card_orders.*",
			"users.name as first_name",
			"users.last_name",
			"users.phone"
		])->where("gift_card_orders.user_id", $user_id)
			->where("gift_card_orders.id", $id)
			->first();

		$list->date = date('d M,Y', strtotime($list->created_at));
		$list->expiry_date_f = date('d M,Y', strtotime($list->expiry_date));
		$list->is_expired = is_null($list->expiry_date) ? false : (today()->format('Y-m-d') > date("Y-m-d", strtotime($list->expiry_date)) ? true : false);

		$history = DiscountHistory::leftJoin("orders", function ($join) {
			$join->on("discount_history.order_id", "=", "orders.id");
		})->select(array('discount_history.*', 'orders.order_no'))
			->where("discount_history.type", 0)
			->where("discount_history.code_id", $id);
		$list->history = $history->orderBy('discount_history.id', 'DESC')->get();

		foreach ($list->history as $val) {
			$val->date = date('d M,Y', strtotime($val->created_at));
		}
		return $this->response("", false, $list);
	}

	private function generate_gift_card_number()
	{
		$numbers = rand(10000, 99999);
		$numbers = date("Ymd") . $numbers;
		$is = GiftCardOrders::where("gift_card_number", $numbers)->first();
		if ($is) {
			return $this->generate_gift_card_number();
		}
		return $numbers;
	}

	public function gift_card_place_order(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'terms_and_condition' => 'required',
			'gift_card_id' => 'required',
			'to_name' => 'required',
			'to_email' => 'required',
			'to_phone' => 'required',
			'message' => 'required',
		], [
			'terms_and_condition.required' => 'Please accept terms and conditions.',
			'gift_card_id.required' => 'Please select gift card.',
			'to_name.required' => 'Please enter recipient name.',
			'to_email.required' => 'Please enter recipient email.',
			'to_phone.required' => 'Please enter recipient phone.',
			'message.required' => 'Please enter message for recipient.',
		]);

		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}

		$gift_card = GiftCard::where("id", $request->gift_card_id)->first();

		if (empty($gift_card)) {
			return $this->response("Please select gift card", true);
		}

		$token = JWTAuth::getToken();
		if ($token) {
			$user = JWTAuth::toUser($token);
		} else {
			$validator = Validator::make($request->all(), [
				'first_name' => 'required',
				'last_name' => 'required',
				'phone' => 'required',
				'email' => 'required|email|unique:users,email',
				'password' => 'required|min:8|regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{6,}$/ ',
			], [
				'first_name.required' => 'Please enter first name',
				'last_name.required' => 'Please enter last name',
				'email.required' => 'Please enter email',
				'email.email' => 'Please enter valid email address',
				'email.unique' => 'Entered email address is already in used!',
				'phone.required' => 'Please enter phone number',
				'password.required' => 'Please enter password',
				'password.min' => 'Please enter minimum 8 character in password',
				'password.regex' => 'Your password must be more than 8 characters long, should contain at-least 1 Uppercase, 1 Lowercase, 1 Numeric and 1 special character.',
			]);

			if ($validator->fails()) {
				return $this->response($validator->errors()->first(), true);
			}

			$user = User::create([
				'name' => $request->first_name,
				'last_name' => $request->last_name,
				'email' => $request->email,
				'phone' => $request->phone,
				'password' => Hash::make($request->password),
				'visible_pass' => $request->password,
			]);

			Mail::to($request->email)->send(
				new RegisterUserMail("{$request->first_name} {$request->last_name}", $request->email, $request->password)
			);
		}

		$order = GiftCardOrders::create(array(
			"user_id" => $user->id,
			"user_email" => $user->email,
			"amount" => $gift_card->amount,
			"pending_amount" => $gift_card->amount,
			"gift_card_number" => $this->generate_gift_card_number(),
			"to_name" => $request->to_name,
			"to_email" => $request->to_email,
			"to_phone" => $request->to_phone,
			"message" => $request->message,
		));

		\Stripe\Stripe::setApiKey(getenv("STRIPE_SECRET_KEY"));

		if (empty($user->stripe_customer_id)) {
			$customer = \Stripe\Customer::create(["name" => "{$user->first_name} {$user->last_name}", "email" => "{$user->email}"]);
			$customer = json_decode(json_encode($customer), true);
			$customer_id = $customer["id"];
			$user->stripe_customer_id = $customer_id;
			$user->save();
		} else {
			$customer_id = $user->stripe_customer_id;
		}

		try {
			$description = "Gift Card of {$gift_card->amount}";
			$intent = \Stripe\PaymentIntent::create([
				'amount' => intval(round($gift_card->amount * 100)), // convert to integer
				// 'amount' => $gift_card->amount * 100,
				'currency' => 'INR',
				'customer' => $customer_id,
				'description' => $description,
				'metadata' => [
					"cust_id" => $user->id,
					"order_id" => $order->id,
					"payment_for" => "GC"
				],
			]);

			if (isset($intent->client_secret)) {
				return $this->response("", false, array(
					"client_secret" => $intent->client_secret,
					"key" => getenv("STRIPE_PUBLISHABLE_KEY"),
				));
			} else {
				return $this->response("Something went going wrong, please try again...", true);
			}
		} catch (\Stripe\Exception\Exception $e) {
			return $this->response("Something went going wrong, please try again...", true);
		}

		return $this->response("Thank you. Your order for Gift Card is completed successfully. An email has been shared with <strong>{$request->to_name}</strong> for this Gift Card.");
	}

	public function apply_gift_card(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'card_number' => 'required',
		], [
			'card_number.required' => 'Please enter gift card number',
		]);

		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}

		$gift_card = GiftCardOrders::where("gift_card_number", $request->card_number)->first();

		if (empty($gift_card)) {
			return $this->response("Gift card not found", true);
		}

		$token = JWTAuth::getToken();
		if ($token) {
			$user = JWTAuth::toUser($token);
			$user_email = $user->email;
		} else {
			$validator = Validator::make($request->all(), [
				'email' => 'required',
			], [
				'email.required' => 'Please fill login details or login to apply gift card',
			]);

			if ($validator->fails()) {
				return $this->response($validator->errors()->first(), true);
			}
			$user_email = $request->email;
		}

		if (strtoupper($gift_card->to_email) != strtoupper($user_email)) {
			return $this->response("Gift card not found", true);
		}

		if ($gift_card->pending_amount == 0) {
			return $this->response("You have used all amount for this gift card.", true);
		}

		if (today()->format('Y-m-d') > date("Y-m-d", strtotime($gift_card->expiry_date))) {
			return $this->response("Gift card is expired.", true);
		}

		$otp = rand(111111, 999999);
		$has_token = DB::table('password_reset_tokens')->where("email", $user_email)->first();
		if ($has_token) {
			DB::table('password_reset_tokens')->where("email", $user_email)->update(['token' => $otp]);
		} else {
			DB::table('password_reset_tokens')->insert(['email' => $user_email, 'token' => $otp]);
		}

		Mail::to($user_email)->send(
			new GiftCardOTPMail($gift_card->to_name, $otp)
		);

		$minFill = 4;
		$user_email = preg_replace_callback(
			'/^(.)(.*?)([^@]?)(?=@[^@]+$)/u',
			function ($m) use ($minFill) {
				return $m[1]
					. str_repeat("*", max($minFill, mb_strlen($m[2], 'UTF-8')))
					. ($m[3] ?: $m[1]);
			},
			$user_email
		);

		return $this->response("We have sent you an OTP on <strong>{$user_email}</strong>{$otp}. Please verify to avail gift card amount");
	}

	public function apply_gift_card_otp(Request $request)
	{

		$validator = Validator::make($request->all(), [
			'card_number' => 'required',
			'otp' => 'required',
		], [
			'card_number.required' => 'Please enter gift card number.',
			'otp.required' => 'Please enter OTP.',
		]);

		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}

		$gift_card = GiftCardOrders::where("gift_card_number", $request->card_number)->first();

		if (empty($gift_card)) {
			return $this->response("Gift card not found", true);
		}

		if ($gift_card->pending_amount == 0) {
			return $this->response("You have used all amount for this gift card.", true);
		}

		if (today()->format('Y-m-d') > date("Y-m-d", strtotime($gift_card->expiry_date))) {
			return $this->response("Gift card is expired.", true);
		}

		$token = JWTAuth::getToken();
		if ($token) {
			$user = JWTAuth::toUser($token);
			$user_email = $user->email;
		} else {
			$validator = Validator::make($request->all(), [
				'email' => 'required',
			], [
				'email.required' => 'Please fill login details or login to apply gift card',
			]);

			if ($validator->fails()) {
				return $this->response($validator->errors()->first(), true);
			}
			$user_email = $request->email;
		}

		$has_token = DB::table('password_reset_tokens')->where("email", $user_email)->where("token", $request->otp)->first();

		if ($has_token) {
			/*DB::table('password_reset_tokens')->where('email',$user_email)->delete();*/
			return $this->response("Gift Card discount applied successfully.");
		}

		return $this->response("Entered OTP is not valid.", true);
	}

	public function payment_success(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'payment_id' => 'required',
			'order_id' => 'required',
			'razorpay_payment_id' => 'required',
			'razorpay_signature' => 'required',
			'payment_for' => 'required',
		], [
			'payment_id.required' => 'Please send required parameters',
			'order_id.required' => 'Please send required parameters',
			'razorpay_payment_id.required' => 'Please enter coupon code',
			'razorpay_signature.required' => 'Please enter coupon code',
			'payment_for.required' => 'Please enter coupon code',
		]);

		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}

		$string = "{$request->order_id}|{$request->razorpay_payment_id}";
		$secret = getenv("RAZORPAY_SECRET", "");
		$sig = hash_hmac('sha256', $string, $secret);

		if ($sig == $request->razorpay_signature) {
			if (strtoupper($request->payment_for) == "ORD") {
				$order = Orders::where('id', $request->payment_id)->first();
				$order->pay_status = 1;
				$order->save();
			} else {
			}
			return $this->response("Thank you, Your payment has been received successfully.", false);
		}

		if (strtoupper($request->payment_for) == "ORD") {
			$order = Orders::where('id', $request->payment_id)->first();
			$order->pay_status = 0;
			$order->save();
		}
		return $this->response("Payment failed, Please try again...", false);
	}

	public function payment_fail(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'payment_id' => 'required',
		], [
			'payment_id.required' => 'Please send required parameters',
		]);

		if ($validator->fails()) {
			return $this->response($validator->errors()->first(), true);
		}

		Orders::where('id', $request->payment_id)->delete();
		OrderItems::where('order_id', $request->payment_id)->delete();
		DiscountHistory::where('order_id', $request->payment_id)->delete();

		/*Payments::where("id", $request->payment_id)->delete();
		PurchasedCourse::where("payment_id", $request->payment_id)->delete();*/

		return $this->response("Payment failed, Please try again...", false);
	}

	public function razorpay_webhook(Request $request)
	{

		$pr_ref = -1;
		$message = "Event not found";

		$headers = $request->header();
		$req = $request->all();
		$post = file_get_contents('php://input');

		if (isset($req["event"])) {

			$api_key = getenv("RAZORPAY_KEY", "");

			$api = new Api($api_key, getenv("RAZORPAY_SECRET", ""));

			try {
				$api->utility->verifyWebhookSignature($post, $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'], getenv("RAZORPAY_WEBHOOK_SECRET", ""));

				if ($req["event"] == "payment.authorized") {

					$entity = $request["payload"]["payment"]["entity"];

					$order_id = $entity["notes"]["coid"];
					$payment_for = $entity["notes"]["payment_for"];
					$amount = $entity["amount"];

					$order = Orders::where('id', $order_id)->first();

					$cust_id = $order->user_id;

					$payment = PaymentReference::create([
						'pay_id' => $entity["id"],
						'cust_id' => $cust_id,
						'amount' => $amount,
						'order_id' => $order_id,
						'status' => 1,
						'json' => json_encode($request),
					]);

					if (strtoupper($payment_for) == "ORD") {

						$order = Orders::where('id', $order_id)->first();
						$order->pay_status = 1;
						$order->save();

						$user = User::where("id", $cust_id)->first();
						$admin = Admin::where("id", 1)->first();

						$billing_address = Addresses::where('id', $order->billing_id)->first();
						$shipping_address = Addresses::where('id', $order->shipping_id)->first();

						$order_items = OrderItems::where('order_id', $order->id)->get();

						$pdf = PDF::loadView('emails.api.order-invoice-pdf', compact('order_items', 'order', 'shipping_address', 'billing_address', 'admin', 'user'));

						$user_mail = Mail::to($user->email)->send(new OrderMail($pdf, $user, $order, $order_items, $shipping_address, $billing_address));
						$admin_mail = Mail::to($admin->email)->send(new AdminOrderMail($pdf, $user, $admin, $order, $order_items, $shipping_address, $billing_address));
					}
				}
			} catch (Errors\SignatureVerificationError $e) {
				$log = array(
					'message'   => $e->getMessage(),
					'data'      => $req,
					'event'     => 'razorpay.wc.signature.verify_failed'
				);

				$message = json_encode($log);
			}
		}

		//PaymentReference
		DB::table("webhook_calls")->insert(array(
			"name" => "Razorpay",
			"url" => "rp",
			"headers" => json_encode($headers),
			"exception" => $message,
			"payload" => json_encode($req)
		));
	}
}
