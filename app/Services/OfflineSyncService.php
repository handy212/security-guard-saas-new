<?php
namespace App\Services;
use App\Models\OfflineSyncBatch;
class OfflineSyncService {
    public function queue(array $data): OfflineSyncBatch { return OfflineSyncBatch::create(['tenant_id'=>$data['tenant_id'],'user_id'=>$data['user_id'] ?? null,'device_uuid'=>$data['device_uuid'] ?? null,'payload'=>$data['payload'],'status'=>'pending']); }
    public function markProcessed(OfflineSyncBatch $batch, array $result): OfflineSyncBatch { $batch->update(['status'=>'processed','result'=>$result,'processed_at'=>now()]); return $batch; }
}
