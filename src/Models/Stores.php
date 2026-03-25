<?php
namespace Megaads\Generatesitemap\Models;

class Stores extends BaseModel
{
    static $STATUS_ACTIVE = 'enable';
    static $STATUS_INACTIVE = 'disable';
    protected $table = 'store';
    protected $primaryKey = 'id';
    public $timestamps = false;


}