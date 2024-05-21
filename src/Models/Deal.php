<?php 
namespace Megaads\Generatesitemap\Models;

class Deal extends BaseModel
{
    protected $table='deals';
    protected $primaryKey = 'id';

    public function store()
    {
        return $this->belongsTo('Megaads\Generatesitemap\Models\Stores', 'store_id');
    }
}
