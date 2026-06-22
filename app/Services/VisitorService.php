<?php
namespace App\Services;
use App\Models\VisitorLog;
class VisitorService {
    public function checkIn(array $data): VisitorLog { $data['checked_in_at']=$data['checked_in_at'] ?? now(); return VisitorLog::create($data); }
    public function checkOut(VisitorLog $log): VisitorLog { $log->update(['checked_out_at'=>now(),'status'=>'checked_out']); return $log; }
}
