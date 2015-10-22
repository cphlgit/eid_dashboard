<?php namespace EID\Models\Location;

use Illuminate\Database\Eloquent\Model;

class District extends Model {

	//

	protected $table = 'districts';

	public static $rules = [
		'district_nr' => 'required',
		'district' => 'required'
	];

	protected $fillable=[
	   'district_nr',
	   'district',
	   'regionID',
	   'created',
	   'createdby'
	   ];


	public $timestamps = false;

	public function region() {
        return $this->belongsTo('EID\Models\Location\Region');
    }

    public function facilities(){
        return $this->hasMany('EID\Models\Location\Facility');
    }

    public static function districtsList(){
    	return District::leftjoin("regions AS r","r.id","=","d.regionID")->select("d.*","r.region")->from("districts AS d")->get();
    }

    public static function districtsArr(){
		$arr=array();
		foreach(District::all() AS $d){
			$arr[$d->id]=$d->district;
		}
		return $arr;
	}

	public static function districtsInit(){
		$arr=array();
		foreach(District::all() AS $d){
			$arr[$d->id]=0;
		}
		return $arr;
	}

}
