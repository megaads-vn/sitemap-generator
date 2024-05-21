<?php 
namespace Megaads\Generatesitemap\Models;

class Categories extends BaseModel 
{
    protected $table='category';
    protected $primaryKey = 'id';
    public $timestamps = false;

    public function store()
    {
        return $this->belongsTo('Megaads\Generatesitemap\Models\Stores', 'store_id');
    }
}