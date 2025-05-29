<?php
namespace App\Http\Controllers;

use App\Models\Leads;
use App\Models\LeadsHistory;
use Illuminate\Http\Request;

class StaticData{
	
	/*public static $INTERESTED_IN = array(
		'Whole House Water Softener',
		'Chlorine/Chloramine Removal System',
		'Iron & Sulphur Removal System',
		'Under Sink Drinking Water System',
		'Detergent Free Laundry System'
	);
	
	public static $PROBLEMS_IN_WATER = array(
		'Drinking water has a bad taste/odor',
		'Clothes appear unclean and stiff',
		'Dishes have a stingy smell/spots',
		'Brown stains on bathtub/toilet',
		'Hair feels dull and your skin feels dry/itchy'
	);
	
	public static $WATER_TYPE = array(
		"City Water",
		"Well Water",
		"Not Sure"
	);
	
	public static function get_customer_type($type){
		if($type==0)
			return "New Customer";
		else if($type==1)
			return "Existing Customer";
		
		return "";
	}
	
	public static function get_water_type($var){
		return self::WATER_TYPE[$var] ?? "";
	}*/
	
	public static function load_book_water_test_data(){
		
		$json = file_get_contents("https://kentro.nexcesstech.com/import-bwt.json");
		
		$json = json_decode($json, true);
		
		foreach($json as $val){
			
			$status = strtoupper(trim($val["Status"]));
			$sales_rep = strtoupper(trim($val["Sales rep"]));
			$conv_drop_date = trim($val["Date (Convert/Drop)"]);
			$conv_drop_date = str_replace("-23","-2023", $conv_drop_date);
			$conv_drop_date = str_replace("-24","-2024", $conv_drop_date);
			
			$date_time = trim($val["date_time"]);
			$date_time = str_replace("-23","-2023", $date_time);
			$date_time = str_replace("-24","-2024", $date_time);
			
			$date_created = trim($val["date_created"]);
			$date_created = str_replace("-23","-2023", $date_created);
			$date_created = str_replace("-24","-2024", $date_created);
			
			$conv_drop_reason = strtoupper(trim($val["Reason (Convert/Drop)"]));
			
			$status_int = -2;
			$assigned_to = -1;
			$revenue = 0;
			$closed_reason = "";
			$to_status = "";
			
			if($status=="DROPPED"){
				$status_int = 2;
				$conv_drop_date = $conv_drop_date!='' ? date("Y-m-d H:i:s", strtotime($conv_drop_date)) : null;
				$closed_reason = $conv_drop_reason;
				$to_status = "Dropped";
			}else if($status=="CONVERTED"){
				$status_int = 3;
				$revenue = trim($val["Revenue"]);
				$conv_drop_date = $conv_drop_date!='' ? date("Y-m-d H:i:s", strtotime($conv_drop_date)) : null;
				$closed_reason = $conv_drop_reason;
				$to_status = "Converted";
			}else if($status=="IN PROGRESS"){
				$status_int = 1;
				$conv_drop_date = null;
			}
			
			if($sales_rep=="JASPREET"){
				$assigned_to = 14;
			}else if($sales_rep=="SANMEET"){
				$assigned_to = 15;
			}else if($sales_rep=="SAMEER" || $sales_rep=="SAMEER/MEHER"){
				$assigned_to = 17;
			}
			
			$arr = [];
			$arr["type"] = 0;
			$arr["name"] = $val["name"];
			$arr["email"] = $val["email"];
			$arr["phone"] = $val["phone"];
			$arr["city"] = $val["city"];
			$arr["post_code"] = $val["post_code"];
			$arr["address"] = $val["address"];
			$arr["created_at"] = $val["date_created"] ? date("Y-m-d H:i:s", strtotime($date_created)) : null;
			$arr["assigned_date"] = $val["date_created"] ? date("Y-m-d H:i:s", strtotime($date_created)) : null;
			$arr["date_time"] = $date_time ? date("Y-m-d H:i:s", strtotime($date_time)) : null;
			$arr["message"] = $val["message"];
			
			//$arr["message"] = $val["remarks"];
			//$arr["converted_date"] = $conv_drop_reason;
			$arr["status"] = $status_int;
			$arr["assigned_to"] = $assigned_to;
			$arr["revenue"] = $revenue;
			$arr["converted_date"] = $conv_drop_date;
			$arr["converted_by"] = $assigned_to;
			$arr["closed_reason"] = $closed_reason;
			
			$lead = Leads::create($arr);
			
			if($assigned_to!=-1){
				
				LeadsHistory::insert(array(
					"created_at"=> $val["date_created"] ? date("Y-m-d H:i:s", strtotime($date_created)) : null,
					"lead_id"=> $lead->id,
					"user_id"=> $assigned_to,
					"assigned_by"=> 1,
					"message"=> "Lead assigned by <strong>Kent Water Purification Systems</strong> to <TAGSYSTEM>assigned_name:{$assigned_to}</TAGSYSTEM>",
				));
				
				$msg = "Lead status updated to <strong>{$to_status}</strong> by <TAGSYSTEM>closed_name:{$assigned_to}</TAGSYSTEM>";
				
				if(!empty($closed_reason)){
					$msg .= "<br /><strong>Reason: </strong>{$closed_reason}";
				}
				
				LeadsHistory::insert(array(
					"created_at"=> $conv_drop_date,
					"lead_id"=> $lead->id,
					"user_id"=> $assigned_to,
					"assigned_by"=> 1,
					"message"=> $msg,
				));
				
			}
		}
		
		echo "done";
		
	}
	
	public static function load_lp_data(){
		
		$json = file_get_contents("https://kentro.nexcesstech.com/import-lp-leads.json");
		
		$json = json_decode($json, true);
		
		$emails = array_column($json, 'Email');
		//$emails = array_unique($emails);
		
		$headers = array();
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,"https://www.kentwater.ca/lpdata.php");
		curl_setopt($ch, CURLOPT_POST,1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $emails);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		$result=curl_exec($ch); 
		curl_close($ch);
		
		$result = json_decode($result, true);
		
		foreach($json as $val){
			
			$status = strtoupper(trim($val["Status"]));
			
			$conv_drop_date = trim($val["Date (Convert/Drop)"]);
			$conv_drop_date = str_replace("-23","-2023", $conv_drop_date);
			$conv_drop_date = str_replace("-24","-2024", $conv_drop_date);
			
			$conv_drop_reason = strtoupper(trim($val["Reason (Convert/Drop)"]));
			
			$sales_rep = strtoupper(trim($val["Sales rep"]));
			
			$status_int = 0;
			$assigned_to = -1;
			$revenue = 0;
			$closed_reason = "";
			$to_status = "";
			
			if($status=="DROPPED"){
				$status_int = 2;
				$conv_drop_date = $conv_drop_date!='' ? date("Y-m-d H:i:s", strtotime($conv_drop_date)) : null;
				$closed_reason = $conv_drop_reason;
				$to_status = "Dropped";
			}else if($status=="CONVERTED"){
				$status_int = 3;
				$revenue = trim($val["Revenue"]);
				$conv_drop_date = $conv_drop_date!='' ? date("Y-m-d H:i:s", strtotime($conv_drop_date)) : null;
				$closed_reason = $conv_drop_reason;
				$to_status = "Converted";
			}else if($status=="IN PROGRESS"){
				$status_int = 1;
				$conv_drop_date = null;
			}else if($status=="SERVICE CALL"){
				$status_int = 1;
				$conv_drop_date = null;
			}else if($status=="SERVICE DONE"){
				$status_int = 3;
				$revenue = trim($val["Revenue"]);
				$conv_drop_date = $conv_drop_date!='' ? date("Y-m-d H:i:s", strtotime($conv_drop_date)) : null;
				$closed_reason = $conv_drop_reason;
				$to_status = "Converted";
			}
			
			if($sales_rep=="JASPREET"){
				$assigned_to = 14;
			}else if($sales_rep=="SANMEET"){
				$assigned_to = 15;
			}else if($sales_rep=="SAMEER" || $sales_rep=="SAMEER/MEHER"){
				$assigned_to = 17;
			}
			
			$email = $val["Email"];
			
			$arr = array_filter($result, function($v) use($email){
				return $v["email"]==$email;
			});
			$arr = reset($arr);
			
			if(!$arr){
				echo "{$arr["id"]} is not inserted<br />";
				continue;
			}
			
			$in_arr = [];
			$in_arr["email"] = $arr["email"];
			$in_arr["name"] = $arr["name"];
			$in_arr["phone"] = $arr["phone"];
			$in_arr["city"] = $arr["city"];
			$in_arr["post_code"] = "";
			$in_arr["address"] = "";
			$in_arr["date_time"] = null;
			$in_arr["message"] = $arr["message"];
			$in_arr["type"] = $arr["type"]==0 ? 5 : 6;
			$in_arr["interest_in"] = $arr["intersted_in_leads"];
			$in_arr["created_at"] = date("Y-m-d H:i:s", strtotime($arr["date_created"]));
			$in_arr["assigned_date"] = date("Y-m-d H:i:s", strtotime($arr["date_created"]));
			$in_arr["status"] = $status_int;
			$in_arr["assigned_to"] = $assigned_to;
			$in_arr["revenue"] = $revenue;
			$in_arr["converted_date"] = $conv_drop_date;
			$in_arr["converted_by"] = $assigned_to;
			$in_arr["closed_reason"] = $closed_reason;
			$in_arr["utm_source"] = $arr["utm_source"];
			
			$lead = Leads::create($in_arr);
			
			if($assigned_to!=-1){
				
				LeadsHistory::insert(array(
					"created_at"=> date("Y-m-d H:i:s", strtotime($arr["date_created"])),
					"lead_id"=> $lead->id,
					"user_id"=> $assigned_to,
					"assigned_by"=> 1,
					"message"=> "Lead assigned by <strong>Kent Water Purification Systems</strong> to <TAGSYSTEM>assigned_name:{$assigned_to}</TAGSYSTEM>",
				));
				
			}
			
			if($arr["remarks"]){
				$in_arr_add_remark_only = [];
				$in_arr_add_remark_only["created_at"] = date("Y-m-d H:i:s", strtotime($arr["date_created"]));
				$in_arr_add_remark_only["lead_id"] = $lead->id;
				$in_arr_add_remark_only["user_id"] = $assigned_to;
				$in_arr_add_remark_only["assigned_by"] = 1;
				$in_arr_add_remark_only["message"] = $arr["remarks"];
				
				LeadsHistory::insert($in_arr_add_remark_only);
			}
			
			if($assigned_to!=-1 && $to_status){
				$msg = "Lead status updated to <strong>{$to_status}</strong> by <TAGSYSTEM>closed_name:{$assigned_to}</TAGSYSTEM>";
				
				if(!empty($closed_reason)){
					$msg .= "<br /><strong>Reason: </strong>{$closed_reason}";
				}
				
				LeadsHistory::insert(array(
					"created_at"=> $conv_drop_date,
					"lead_id"=> $lead->id,
					"user_id"=> $assigned_to,
					"assigned_by"=> 1,
					"message"=> $msg,
				));
			}
			
		}
		
		echo "done";
		
	}
	
	public static function load_contact_data(Request $request){
		
		$json = file_get_contents("https://kentro.nexcesstech.com/contact-json.json");
		
		$json = json_decode($json, true);
		
		$emails = array_column($json, 'Email');
		
		$data = [];
		
		foreach($json as $val){
			$email = $val["Email"];
			$phone = $val["Phone"];
			
			if($email){
				$data["emails"][]=$email;
			}else{
				$data["phones"][]=$phone;
			}
		}
		
		$data = json_encode($data);
		
		$payload = [];
		$payload["datas"] = $data;
		
		$headers = array();
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,"https://www.kentwater.ca/cdata.php");
		curl_setopt($ch, CURLOPT_POST,1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, ($payload));
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		$result=curl_exec($ch); 
		curl_close($ch);
		
		$result = json_decode($result, true);
		
		foreach($json as $val){
			
			$status = strtoupper(trim($val["Status"]));
			
			$conv_drop_date = trim($val["Date (Convert/Drop)"]);
			$conv_drop_date = str_replace("-23","-2023", $conv_drop_date);
			$conv_drop_date = str_replace("-24","-2024", $conv_drop_date);
			
			$conv_drop_reason = strtoupper(trim($val["Reason (Convert/Drop)"]));
			
			$sales_rep = strtoupper(trim($val["Sales rep"]));
			
			$status_int = 0;
			$assigned_to = -1;
			$revenue = 0;
			$closed_reason = "";
			$to_status = "";
			
			if($status=="DROPPED"){
				$status_int = 2;
				$conv_drop_date = $conv_drop_date!='' ? date("Y-m-d H:i:s", strtotime($conv_drop_date)) : null;
				$closed_reason = $conv_drop_reason;
				$to_status = "Dropped";
			}else if($status=="CONVERTED"){
				$status_int = 3;
				$revenue = trim($val["Revenue"]);
				$conv_drop_date = $conv_drop_date!='' ? date("Y-m-d H:i:s", strtotime($conv_drop_date)) : null;
				$closed_reason = $conv_drop_reason;
				$to_status = "Converted";
			}else if($status=="IN PROGRESS"){
				$status_int = 1;
				$conv_drop_date = null;
			}
			
			if($sales_rep=="JASPREET"){
				$assigned_to = 14;
			}else if($sales_rep=="SANMEET"){
				$assigned_to = 15;
			}else if($sales_rep=="SAMEER"){
				$assigned_to = 17;
			}else if($sales_rep=="SUKHMEHAR"){
				$assigned_to = 12;
			}
			
			$email = $val["Email"];
			$phone = $val["Phone"];
			
			$arr = array_filter($result, function($v) use($email, $phone){
				if($v["email"] && $email){
					return $v["email"]==$email;
				}else{
					return $v["phone"]==$phone;
				}
			});
			$arr = reset($arr);
			
			if(!$arr){
				echo "{$val["Email"]}, {$val["Name"]}, {$val["Phone"]} is not inserted<br />";
				continue;
			}
			
			$type = -1;
			
			if($arr["type"]==0){
				if($arr["source"]==0){
					$type = 1;
				}else if($arr["source"]==1){
					$type = 2;
				}else if($arr["source"]==2){
					$type = 3;
				}else if($arr["source"]==3){
					$type = 7;
				}else{
					$type = 2;
				}
			}else if($arr["type"]==2){
				$type = 4;
			}
			
			$in_arr = [];
			$in_arr["email"] = $arr["email"];
			$in_arr["name"] = $arr["name"];
			$in_arr["phone"] = $arr["phone"];
			$in_arr["city"] = $arr["city"];
			$in_arr["post_code"] = "";
			$in_arr["address"] = "";
			$in_arr["date_time"] = null;
			$in_arr["message"] = $arr["message"];
			$in_arr["type"] = $type;
			$in_arr["interest_in"] = $arr["interest_in"];
			$in_arr["created_at"] = date("Y-m-d H:i:s", strtotime($arr["date_created"]));
			$in_arr["assigned_date"] = date("Y-m-d H:i:s", strtotime($arr["date_created"]));
			$in_arr["status"] = $status_int;
			$in_arr["assigned_to"] = $assigned_to;
			$in_arr["revenue"] = $revenue;
			$in_arr["converted_date"] = $conv_drop_date;
			$in_arr["converted_by"] = $assigned_to;
			$in_arr["closed_reason"] = $closed_reason;
			$in_arr["utm_source"] = "";
			$in_arr["cust_type"] = $arr["cust_type"]==-1 ? $arr["customer_type"] : $arr["cust_type"];
			$in_arr["water_type"] = $arr["water-type"];
			$in_arr["problem_with_your_water"] = $arr["problem_with_your_water"];
			$in_arr["water_system_issues"] = $arr["water_system_issues"];
			$in_arr["friend_name"] = $arr["frd_name"];
			$in_arr["friend_email"] = $arr["frd_email"];
			$in_arr["friend_phone"] = $arr["frd_phone"];
			$in_arr["friend_city"] = $arr["frd_city"];
			$in_arr["hear_about"] = $arr["hear_about"];
			
			$lead = Leads::create($in_arr);
			
			if($assigned_to!=-1){
				
				LeadsHistory::insert(array(
					"created_at"=> date("Y-m-d H:i:s", strtotime($arr["date_created"])),
					"lead_id"=> $lead->id,
					"user_id"=> $assigned_to,
					"assigned_by"=> 1,
					"message"=> "Lead assigned by <strong>Kent Water Purification Systems</strong> to <TAGSYSTEM>assigned_name:{$assigned_to}</TAGSYSTEM>",
				));
				
			}
			
			if($arr["remarks"]){
				$in_arr_add_remark_only = [];
				$in_arr_add_remark_only["created_at"] = date("Y-m-d H:i:s", strtotime($arr["date_created"]));
				$in_arr_add_remark_only["lead_id"] = $lead->id;
				$in_arr_add_remark_only["user_id"] = $assigned_to;
				$in_arr_add_remark_only["assigned_by"] = 1;
				$in_arr_add_remark_only["message"] = $arr["remarks"];
				
				LeadsHistory::insert($in_arr_add_remark_only);
			}
			
			if($assigned_to!=-1 && $to_status){
				$msg = "Lead status updated to <strong>{$to_status}</strong> by <TAGSYSTEM>closed_name:{$assigned_to}</TAGSYSTEM>";
				
				if(!empty($closed_reason)){
					$msg .= "<br /><strong>Reason: </strong>{$closed_reason}";
				}
				
				LeadsHistory::insert(array(
					"created_at"=> $conv_drop_date,
					"lead_id"=> $lead->id,
					"user_id"=> $assigned_to,
					"assigned_by"=> 1,
					"message"=> $msg,
				));
			}
			
		}
		
		echo "done";
		
	}
	
	public static function load_contact_datax(Request $request){
		
		$json = $request->all();
		
		foreach($json as $val){
			
			$type = -2;
			
			if($val["type"]==0){
				if($val["source"]==0){
					$type = 1;
				}else if($val["source"]==1){
					$type = 2;
				}else if($val["source"]==2){
					$type = 3;
				}else if($val["source"]==3){
					$type = 7;
				}
			}else{
				$type = 4;
			}
			
			$arr = [];
			$arr["type"] = $type;
			$arr["name"] = $val["name"] ?? "";
			$arr["email"] = $val["email"] ?? "";
			$arr["phone"] = $val["phone"] ?? "";
			$arr["city"] = $val["city"] ?? "";
			$arr["message"] = $val["message"] ?? "";
			
			$arr["cust_type"] = $val["cust_type"] ?? "";
			$arr["water_type"] = $val["water-type"] ?? "";
			$arr["interest_in"] = $val["interest_in"] ?? "";
			$arr["problem_with_your_water"] = $val["problem_with_your_water"] ?? "";
			$arr["water_system_issues"] = $val["water_system_issues"] ?? "";
			
			Leads::insert($arr);
			
		}
		
		echo "done";
		
	}
	
	public static function load_lp_datax(Request $request){
		
		$json = $request->all();
		
		foreach($json as $val){
			
			$type = -2;
			
			if($val["type"]==0){
				$type=5;
			}else{
				$type = 6;
			}
			
			$arr = [];
			$arr["type"] = $type;
			$arr["name"] = $val["name"] ?? "";
			$arr["email"] = $val["email"] ?? "";
			$arr["phone"] = $val["phone"] ?? "";
			$arr["city"] = $val["city"] ?? "";
			$arr["message"] = $val["message"] ?? "";
			
			$arr["interest_in"] = $val["intersted_in_leads"] ?? "";
			$arr["utm_source"] = $val["utm_source"] ?? "";
			
			Leads::insert($arr);
			
		}
		
		echo "done";
		
	}
	
}