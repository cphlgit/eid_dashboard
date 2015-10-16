<?php namespace EID\Http\Controllers;

use EID\Http\Requests;
use EID\Http\Controllers\Controller;

//use EID\Models\Pilot;
use EID\Models\Facility;
use EID\Models\PilotFacility;
use EID\Models\Location\Region;
use EID\Models\Location\District;
use EID\Models\FacilityLevel;
use EID\Models\Sample;

use Validator;
use Lang;
use Redirect;
use Request;
use Session;

class DashboardController extends Controller {

	public function __construct()
	{
		//$this->middleware('auth');
	}

	public function show($time=""){
		if(empty($time)) $time=date("Y");
		$regions=['all'=>'REGION']+Region::regionsArr();
		$districts=District::districtsArr();
		$facility_levels=FacilityLevel::facilityLevelsArr();
		//$count_positives=Sample::countPositives($time);
		$av_positivity=Sample::avPositivity($time);
		$count_positives_arr=Sample::countPositives2($time);
		$count_positives=array_sum($count_positives_arr);
		$av_positivity_arr=Sample::avPositivity2($time);
		//$av_positivity=array_sum($av_positivity_arr)/count($av_positivity_arr);

		$positives_by_region=Sample::countPositivesByRegions($time);
		$pos_by_reg_sums=$this->regionSums($positives_by_region);
		$pos_by_reg_sums["all"]=$count_positives;

		$nums_by_region=Sample::sampleNumbersByRegions($time);
		$av_by_region=$this->regionAvs($nums_by_region,$positives_by_region);
		$av_by_region["all"]=$av_positivity;
		$av_by_reg_mth=$this->regionMthAvs($nums_by_region,$positives_by_region);
		

		return view('db/show',compact(
			"time",
			"regions",
			"districts",
			"facility_levels",
			"count_positives",
			"av_positivity",
			"count_positives_arr",
			"av_positivity_arr",
			"positives_by_region",
			"pos_by_reg_sums",
			"av_by_region",
			"av_by_reg_mth"
			));
	}

	private function regionSums($arr){
		$ret=[];
		foreach ($arr as $k => $v) {
			$ret[$k]=array_sum($v);			
		}
		return $ret;
	}

	private function regionAvs($nums_by_region,$positives_by_region){
		$ret=[];
		foreach ($nums_by_region as $k => $v) {
			$num=array_sum($v);	
			$pos=array_sum($positives_by_region[$k]);	
			$av=$num>0?($pos/$num)*100:0;
			$ret[$k]=round($av,1);	
		}
		return $ret;		
	}

	private function regionMthAvs($nums_by_region,$positives_by_region){
		$ret=[];
		foreach ($nums_by_region as $k => $v) {
			foreach ($v as $mth => $mth_val){
				$m_av=$mth_val>0?(($positives_by_region[$k][$mth])/$mth_val)*100:0;
				$ret[$k][$mth]=round($m_av,1);
			}
		}
		return $ret;		
	}

	/*

	I would say that he is a ‘master’, if it were not for my belief that no one ‘masters’ anything, that each finds or makes his candle, then tries to see by the guttering light. Mum has made a good candle. And Mum has good eyes.

	Gwendolyn Brooks


	Whether you are witness or executioner, the victim whose humanity you can never erase
	knows with clarity, more solid than granite that no matter which side you are on,
	any day or night, an injury to one remains an injury to all
	some where on this coninent, an ancient voice warns, that those who shit on the road, will find flies on their way back...



	*/

}
