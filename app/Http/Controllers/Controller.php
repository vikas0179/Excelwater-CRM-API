<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Storage;
use App\Models\Admin;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
	
	protected $defaultDateFormat = "M d, Y";
	protected $defaultDateTimeFormat = "M d, Y h:i A";
	
	public function response($message,$error=false, $datax=array()){
        $data = [];
        $data["status"] = $error ? "RC100" : "RC200";
        $data["message"] = $message;
        $data["data"] = $datax;

        return response()->json($data);
    }
	
	public function _url($path){
		
		if(Storage::exists("public/{$path}")){
			return asset("storage/{$path}");
		}
		
		return asset('blank-image.svg');
	}

	public static function __url($filename,$dir){
		
		if(!empty($filename) && Storage::exists("public/{$dir}/{$filename}")){
			return asset("storage/{$dir}/{$filename}");
		}
		
		return asset('ic-user.png');
	}
	
	public function upload_file($file, $directory, $prefix=''){
		
		if(!Storage::exists("public/$directory")){
			Storage::makeDirectory("public/$directory");
		}
		
		$random_number = random_int(1000, 9999);
		$ext = $file->extension();
		
		$arr = [];
		
		if(!empty($prefix)){
			$arr[]= $prefix;
		}
		
		$arr[]= $random_number;
		
		$filename = implode("-", $arr);
		
		$filename = "{$filename}.{$ext}";
		
		$file->storeAs("public/{$directory}", $filename);
		
		return $filename;
		
	}
	
	public function remove_file($name, $directory){
		
		$name = "public/{$directory}/{$name}";
		
		if(Storage::exists($name)){
			Storage::delete($name);
		}
	}
	
	public function toLocalDate($date_object, $onlyDate=false, $format=""){
		
		if(is_null($date_object)){
			return "";
		}
		
		$date = new \DateTime($date_object, new \DateTimeZone(getenv("APP_TIMEZONE","UTC")));
		$date->setTimezone(new \DateTimeZone('America/Toronto'));
		
		if(!empty($format)){
			return $date->format($format);
		}
		
		if($onlyDate){
			return $date->format('M d, Y');
		}
		
		return $date->format('M d, Y h:i A');
	}
	
	public function toUTCDateYMD($date_object, $onlyDate=false, $format=""){
		
		if(is_null($date_object)){
			return "";
		}
		
		$date = new \DateTime($date_object, new \DateTimeZone('America/Toronto'));
		$date->setTimezone(new \DateTimeZone('UTC'));
		
		if(!empty($format)){
			return $date->format($format);
		}
		
		if($onlyDate){
			return $date->format('Y-m-d');
		}
		
		return $date->format('Y-m-d H:i:s');
	}
	
	public function extractMessage($string){
		
		if(strpos($string, "<TAGSYSTEM>")===false){
			return $string;
		}
		
		$startsAt = strpos($string, "<TAGSYSTEM>") + strlen("<TAGSYSTEM>");
		$endsAt = strpos($string, "</TAGSYSTEM>", $startsAt);
		$result = substr($string, $startsAt, $endsAt - $startsAt);
		
		if(empty($result))
			return $string;
		
		$data = explode(":",$result);
		
		$admin = Admin::where("id", $data[1])->first();
		
		$found_string = "<strong>{$admin->name}</strong>";
		
		$string = str_replace($result,$found_string, $string);
		$string = str_replace("<TAGSYSTEM>","", $string);
		$string = str_replace("</TAGSYSTEM>","", $string);
		
		return $string;
	}
}
