<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Storage;
use App\Models\Admin;
use App\Models\Category;
use App\Models\Products;
use App\Models\Settings;
use App\Models\SubCategory;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use DB;

class Controller extends BaseController
{
	use AuthorizesRequests, ValidatesRequests;

	protected $defaultDateFormat = "M d, Y";
	protected $defaultDateTimeFormat = "M d, Y h:i A";

	public function response($message, $error = false, $datax = array())
	{
		$data = [];
		$data["status"] = $error ? "RC100" : "RC200";
		$data["message"] = $message;
		$data["data"] = $datax;

		return response()->json($data);
	}

	public function _url($path)
	{

		if (Storage::exists("public/{$path}")) {
			return asset("storage/{$path}");
		}

		return asset('blank-image.svg');
	}

	public static function __url($filename, $dir)
	{

		if (!empty($filename) && Storage::exists("public/{$dir}/{$filename}")) {
			return asset("storage/{$dir}/{$filename}");
		}

		return asset('ic-user.png');
	}

	public function upload_file($file, $directory, $prefix = '')
	{

		if (!Storage::exists("public/$directory")) {
			Storage::makeDirectory("public/$directory");
		}

		$random_number = random_int(1000, 9999);
		$ext = $file->extension();

		$arr = [];

		if (!empty($prefix)) {
			$arr[] = $prefix;
		}

		$arr[] = $random_number;

		$filename = implode("-", $arr);

		$filename = "{$filename}.{$ext}";

		$file->storeAs("public/{$directory}", $filename);

		return $filename;
	}

	public function remove_file($name, $directory)
	{

		$name = "public/{$directory}/{$name}";

		if (Storage::exists($name)) {
			Storage::delete($name);
		}
	}

	public function toLocalDate($date_object, $onlyDate = false, $format = "")
	{

		if (is_null($date_object)) {
			return "";
		}

		$date = new \DateTime($date_object, new \DateTimeZone(getenv("APP_TIMEZONE", "UTC")));
		$date->setTimezone(new \DateTimeZone('America/Toronto'));

		if (!empty($format)) {
			return $date->format($format);
		}

		if ($onlyDate) {
			return $date->format('M d, Y');
		}

		return $date->format('M d, Y h:i A');
	}

	public function toUTCDateYMD($date_object, $onlyDate = false, $format = "")
	{

		if (is_null($date_object)) {
			return "";
		}

		$date = new \DateTime($date_object, new \DateTimeZone('America/Toronto'));
		$date->setTimezone(new \DateTimeZone('UTC'));

		if (!empty($format)) {
			return $date->format($format);
		}

		if ($onlyDate) {
			return $date->format('Y-m-d');
		}

		return $date->format('Y-m-d H:i:s');
	}

	public function extractMessage($string)
	{

		if (strpos($string, "<TAGSYSTEM>") === false) {
			return $string;
		}

		$startsAt = strpos($string, "<TAGSYSTEM>") + strlen("<TAGSYSTEM>");
		$endsAt = strpos($string, "</TAGSYSTEM>", $startsAt);
		$result = substr($string, $startsAt, $endsAt - $startsAt);

		if (empty($result))
			return $string;

		$data = explode(":", $result);

		$admin = Admin::where("id", $data[1])->first();

		$found_string = "<strong>{$admin->name}</strong>";

		$string = str_replace($result, $found_string, $string);
		$string = str_replace("<TAGSYSTEM>", "", $string);
		$string = str_replace("</TAGSYSTEM>", "", $string);

		return $string;
	}


	public function get_ip_info($ip = NULL, $purpose = "location", $deep_detect = TRUE)
	{
		$output = NULL;

		if (filter_var($ip, FILTER_VALIDATE_IP) === FALSE) {
			$ip = $_SERVER["REMOTE_ADDR"];
			if ($deep_detect) {
				if (filter_var(@$_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP))
					$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
				if (filter_var(@$_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP))
					$ip = $_SERVER['HTTP_CLIENT_IP'];
			}
		}

		$purpose    = str_replace(array("name", "\n", "\t", " ", "-", "_"), NULL, strtolower(trim($purpose)));

		$support    = array("country", "countrycode", "state", "region", "city", "location", "address", "timezone", "all");

		$continents = array(
			"AF" => "Africa",
			"AN" => "Antarctica",
			"AS" => "Asia",
			"EU" => "Europe",
			"OC" => "Australia (Oceania)",
			"NA" => "North America",
			"SA" => "South America"
		);


		if (filter_var($ip, FILTER_VALIDATE_IP) && in_array($purpose, $support)) {
			$ipdat = @json_decode(file_get_contents("http://www.geoplugin.net/json.gp?ip=" . $ip));

			if (@strlen(trim($ipdat->geoplugin_countryCode)) == 2) {
				switch ($purpose) {
					case "location":
						$output = array(
							"city"           => @$ipdat->geoplugin_city,
							"state"          => @$ipdat->geoplugin_regionName,
							"country"        => @$ipdat->geoplugin_countryName,
							"country_code"   => @$ipdat->geoplugin_countryCode,
							"continent"      => @$continents[strtoupper($ipdat->geoplugin_continentCode)],
							"continent_code" => @$ipdat->geoplugin_continentCode
						);
						break;
					case "address":
						$address = array($ipdat->geoplugin_countryName);
						if (@strlen($ipdat->geoplugin_regionName) >= 1)
							$address[] = $ipdat->geoplugin_regionName;
						if (@strlen($ipdat->geoplugin_city) >= 1)
							$address[] = $ipdat->geoplugin_city;
						$output = implode(", ", array_reverse($address));
						break;
					case "city":
						$output = @$ipdat->geoplugin_city;
						break;
					case "state":
						$output = @$ipdat->geoplugin_regionName;
						break;
					case "region":
						$output = @$ipdat->geoplugin_regionName;
						break;
					case "country":
						$output = @$ipdat->geoplugin_countryName;
						break;
					case "countrycode":
						$output = @$ipdat->geoplugin_countryCode;
						break;
					case "timezone":
						$output = @$ipdat->geoplugin_timezone;
						break;
					case "all":
						$output = json_decode(json_encode($ipdat), true);
						break;
				}
			}
		}
		return $output;
	}

	public function convert_currency($to)
	{

		$content = @file_get_contents("https://cdn.jsdelivr.net/gh/fawazahmed0/currency-api@1/latest/currencies/aed/{$to}.json");
		/*$content = @file_get_contents("https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@latest/v1/currencies/aed.json");*/

		if (empty($content)) {
			return 0;
		}

		$output = @json_decode($content, true);

		return isset($output[$to]) ? $output[$to] : 0;
	}

	public static function toKformat($input, $allow_symbol = true, $only_m = false)
	{
		$input_orginal = $input;
		$input = number_format($input);

		$input_count = substr_count($input, ',');

		$return_val = "";

		if ($input_count != '0') {
			if ($input_count == '1') {
				if (!$only_m)
					$return_val = substr($input, 0, -4) . 'K';
				else
					$return_val = $input;
			} else if ($input_count == '2') {
				$return_val = substr($input, 0, -8) . 'M';
			} else if ($input_count == '3') {
				$return_val = substr($input, 0, -12) . 'B';
			} else {
				$return_val = "";
			}
		} else {
			$return_val = number_format($input_orginal, 2);
		}

		return $allow_symbol ? (self::get_currency_symbol() . $return_val) : $return_val;
	}


	public static function get_currency_symbol($truncat = false)
	{

		$settings = Settings::where('name', "currency_symbol")->first();

		$symbol = $settings->value ?? "";

		return $truncat ? trim($symbol) : $symbol;
	}


	public function create_slug(Request $request)
	{

		$slug = Str::slug($request->value, '-');

		$is = Category::where('slug', $slug)->first();

		if ($is) {
			$slug = $slug . "-" . rand(1000, 9999);
		}
		return $this->response("", false, $slug);
	}

	public function create_sub_category_slug(Request $request)
	{

		$slug = Str::slug($request->value, '-');

		$is = SubCategory::where('slug', $slug)->first();

		if ($is) {
			$slug = $slug . "-" . rand(1000, 9999);
		}
		return $this->response("", false, $slug);
	}

	public function create_attribute_slug(Request $request)
	{

		$slug = Str::slug($request->value, '-');

		$is = SubCategory::where('slug', $slug)->first();

		if ($is) {
			$slug = $slug . "-" . rand(1000, 9999);
		}
		return $this->response("", false, $slug);
	}

	public function file_upload(Request $request)
	{

		$is = '';

		if ($request->hasfile('file')) {

			$image = $request->file('file');
			$random_number = random_int(1000, 9999);
			$image_slug =  "Image";
			$image = $image_slug . '-' . $random_number . '.' . $image->extension();
			$is = $request->file('file')->storeAs('public/temporary-file', $image);
		}

		if ($is) {
			return $this->response("", false, ["file" => $image]);
		} else {
			return $this->response("", true);
		}
	}

	public function create_product_slug(Request $request)
	{
		$slug = Str::slug($request->value, '-');
		$is = Products::where('slug', $slug)->first();
		if ($is) {
			$slug = $slug . "-" . rand(1000, 9999);
		}
		return $this->response("", false, $slug);
	}

	public function get_ord_id()
	{
		$prefix = "ORD-";
		$date = date("mdY");

		$ord_id = DB::table('orders')->latest('id')->first();

		if (empty($ord_id)) {
			return "{$prefix}{$date}-1";
		} else {

			$last_digit_array = explode("-", $ord_id->order_no);
			$last_digit = end($last_digit_array);

			$last_digit = $last_digit + 1;

			return "{$prefix}{$date}-{$last_digit}";
		}
	}
}
