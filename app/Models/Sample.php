<?php   namespace EID\Models;

use Illuminate\Database\Eloquent\Model;

class Sample extends Model {

	protected $guarded = array('id');
	protected $table = 'dbs_samples';
	protected $dates = array('f_date_results_collected', 'f_date_ART_initiated');

	public $timestamps = false;

	public function parent_batch()
	{
		return $this->belongsTo('Batch');
	}

	public function getEntryPoint(){
		return $this->attributes["infant_entryPoint"];
	}

	public function getAnteNatalPMTCT(){
		return $this->attributes["mother_antenatal_prophylaxis"];
	}

	public function getDeliveryPMTCT(){
		return $this->attributes["mother_delivery_prophylaxis"];	
	}

	public function getPostNatalPMTCT(){
		return $this->attributes["mother_postnatal_prophylaxis"];
	}

	public function getInfantPMTCT(){
		return $this->attributes["infant_prophylaxis"];
	}

	public function wasRejected(){
		if( empty ($this->attributes["sample_verified_by"]) ){
			return null;
		}
		
		$sample_rejected = ($this->attributes["sample_rejected"] === "YES") ? true : false;

		return 	$sample_rejected;
	}

	public function wasAccepted(){
		$status = $this->WasRejected();

		if($status === null)
			return null;
		else
			return !$status;
	}

	public static function quickStats($level,$case){
		$res=Sample::select('id','batch_id');
		$res=$level=='batches'?Sample::select('batch_id'):Sample::select('id');
		switch ($case) {
			case 'pending2approve':
				$res=$res->where('sample_rejected','=','NOT_YET_CHECKED');
				break;

			case 'ready4lab':
				$res=$res->where('sample_rejected','=','NO')->where('in_workSheet','=','NO');
				break;
			
			default:
				# code...
				break;
		}
		$res=$level=='batches'?$res->groupby('batch_id'):$res;
		return $res->get()->count();
	}	

	public static function countPositives($year=""){
		if(empty($year)) $year=date("Y");
		/*$res=\DB::select("SELECT count(s.id) AS number_positive FROM dbs_samples AS s 
					   WHERE year(date_results_entered)=$year AND accepted_result='POSITIVE'");*/
		$res=Sample::select(\DB::raw(" count(s.id) AS number_positive"))
				->from("dbs_samples AS s")
				->whereYear('s.date_results_entered','=',$year)
				->where('s.accepted_result','=','POSITIVE')
				->get()
				->first();
		
		return $res->number_positive;
	}

	public static function countPositives2($year=""){
		if(empty($year)) $year=date("Y");
		/*$res=\DB::select("SELECT count(s.id) AS number_positive FROM dbs_samples AS s 
					   WHERE year(date_results_entered)=$year AND accepted_result='POSITIVE'");*/
		$res=Sample::select(\DB::raw("month(date_results_entered) AS mth, count(s.id) AS number_positive"))
				->from("dbs_samples AS s")
				->whereYear('s.date_results_entered','=',$year)
				->where('s.accepted_result','=','POSITIVE')
				->groupby('mth')
				->get();
		$months=\MyHTML::initMonths();
		foreach ($res as $k) {
			$months[$k->mth]=$k->number_positive;
		}

		//echo json_encode($res);
		return $months;
	}

	public static function avPositivity($year=""){
		if(empty($year)) $year=date("Y");
		$res_all=Sample::select(\DB::raw("count(s.id) AS num"))
				->from("dbs_samples AS s")
				->whereYear('s.date_results_entered','=',$year)
				->get()->first();
		$ttl_num=$res_all->num;
		$ttl_pos=Sample::countPositives($year);
		$ttl_av=$ttl_num>0?($ttl_pos/$ttl_num)*100:0;
		return round($ttl_av,1);
	}

	public static function avPositivity2($year=""){
		if(empty($year)) $year=date("Y");
		$res=Sample::select(\DB::raw("month(date_results_entered) AS mth,count(s.id) AS num"))
				->from("dbs_samples AS s")
				->whereYear('s.date_results_entered','=',$year)
				->groupby('mth')
				->get();
		$mths_with_p_nrs=Sample::countPositives2($year);
		$months=\MyHTML::initMonths();
		foreach ($res as $k) {
			$ttl_num=$k->num;
			$ttl_pos=$mths_with_p_nrs[$k->mth];
			$av=$ttl_num>0?($ttl_pos/$ttl_num)*100:0;
			$months[$k->mth]=round($av,1);
		}
		return $months;
	}
}
