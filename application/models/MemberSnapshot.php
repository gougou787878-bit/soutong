<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class MemberSnapshotModel
 *
 * @property int $id 
 * @property int $uid 
 * @property string $data 
 * @property int $status 
 * @property string $created_at 
 * @property string $updated_at 
 *
 * @author xiongba
 * @date 2022-09-27 22:05:08
 *
 * @mixin \Eloquent
 */
class MemberSnapshotModel extends Model
{

    protected $table = "member_snapshot";

    protected $primaryKey = 'id';

    protected $fillable = ['uid', 'data', 'status', 'created_at', 'updated_at'];

    protected $guarded = 'id';

    public $timestamps = false;




}
