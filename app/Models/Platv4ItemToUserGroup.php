<?php
namespace App\Models;

use Illuminate\Support\Facades\DB;

class Platv4ItemToUserGroup extends BaseModel
{
    protected $connection = 'plat';
    protected $table = 'platv4_item_to_user_group';
}