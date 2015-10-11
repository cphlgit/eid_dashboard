<?php namespace EID\Models\Location;

use Illuminate\Database\Eloquent\Model;

class Region extends Model {

	//

	protected $table = 'regions';

	public static $rules = [
		'region' => 'required'
	];
	
	protected $fillable = ['region','created','createdby'];

	public $timestamps = false;

	public function districts(){
        return $this->hasMany('EID\Models\Location\District','regionID','id');
    }

    public static function regionsArr(){
		$arr=array();
		foreach(Region::all() AS $region){
			$arr[$region->id]=$region->region;
		}
		return $arr;
	}



}
