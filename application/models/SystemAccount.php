<?php


use Illuminate\Database\Eloquent\Model;

/**
 * class SystemAccountModel
 *
 * @property int $id 
 * @property string $uuid 
 * @property string $card_number 
 * @property string $name 
 *
 * @author xiongba
 * @date 2021-07-19 22:12:40
 *
 * @mixin \Eloquent
 */
class SystemAccountModel extends Model
{

    protected $table = "system_account";

    protected $primaryKey = 'id';

    protected $fillable = ['uuid', 'card_number', 'name'];

    protected $guarded = 'id';

    public $timestamps = false;


    /**
     * @param UserWithdrawModel $userWithDraw
     * @return bool
     */
    static function addWithDrawAccount(UserWithdrawModel $userWithDraw)
    {
        $uuid = $userWithDraw->uuid;
        $name = $userWithDraw->name;
        $card = $userWithDraw->account;
        $row = self::checkHasNearlyWithDraw($uuid,$name,$card);
        if (is_null($row)) {
            return self::addAccount($uuid,$name,$card);
        }
        return false;
    }

    /**
     * @param string $uuid
     * @param null $name
     * @param null $card
     * @return SystemAccountModel
     */
    static function checkHasNearlyWithDraw($uuid, $name = null, $card = null)
    {
        $uuid = trim($uuid);
        $name = trim($name);
        $w = [];
        $w['uuid'] = $uuid;
        $name && $w['name'] = $name;
        $card && $w['card_number'] = $card;
        /** @var SystemAccountModel $row */
        return self::where($w)->orderByDesc('id')->first();
    }

    /**
     * @param $uuid
     * @param $name
     * @param $card
     * @return bool
     */
    static function addAccount($uuid, $name, $card)
    {
        $uuid = trim($uuid);
        $name = trim($name);
        $card = trim($card);
        return self::insert(['uuid' => $uuid, 'name' => $name, 'card_number' => $card]);
    }



}
