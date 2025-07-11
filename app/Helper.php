<?php

namespace App;

use App\Models\ActivityLog;

class Helper
{

    public function ActivityLog($id, $module_title, $date, $response, $updater, $type, $desc, $status)
    {
        ActivityLog::create([
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s"),
            'module_id' => $id,
            'title' => $module_title,
            'date' => $date,
            'response' => !empty($response) ? $response : NULL,
            'updater' => $updater,
            'type' => $type,
            'desc' => !empty($desc) ? $desc : NULL,
            'status' => $status,
        ]);
        return true;
    }
}
