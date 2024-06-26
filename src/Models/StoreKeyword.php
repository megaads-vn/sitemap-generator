<?php

namespace Megaads\Generatesitemap\Models;

class StoreKeyword extends BaseModel
{
    const STATUS_VISIBLE = 'visible';

    protected $table = 'store_n_keyword';
    protected $primaryKey = 'id';
    public $timestamps = true;

    public function store()
    {
        return $this->belongsTo('Megaads\Generatesitemap\Models\Stores', 'store_id');
    }
}
