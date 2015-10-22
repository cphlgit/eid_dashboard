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

		//for regions filtering -- positive numbers
		$positives_by_region=Sample::countPositivesByRegions($time);
		$pos_by_reg_sums=$this->arrSums($positives_by_region);
		$pos_by_reg_sums["all"]=$count_positives;

		//for districts filtering -- positive numbers
		$positives_by_dist=Sample::countPositivesByDistricts($time);
		$pos_by_dist_sums=$this->arrSums($positives_by_dist);


		//filtering by regions -- average positive rates
		$nums_by_region=Sample::sampleNumbersByRegions($time);
		$av_by_region=$this->arrAvs($nums_by_region,$positives_by_region);
		$av_by_region["all"]=$av_positivity;
		$av_by_reg_mth=$this->arrMonthAvs($nums_by_region,$positives_by_region);

		//filtering by districts -- average positive rates
		$nums_by_dist=Sample::sampleNumbersByDistricts($time);
		$av_by_dist=$this->arrAvs($nums_by_dist,$positives_by_dist);
		$av_by_dist_mth=$this->arrMonthAvs($nums_by_dist,$positives_by_dist);

		//other metrics
		$first_pcr_total=Sample::NumberTotals($time,'FIRST');
		$sec_pcr_total=Sample::NumberTotals($time,'SECOND');

		$total_initiated=Sample::NumberTotals($time,"",1);
		$total_samples=Sample::NumberTotals($time);

		$first_pcr_ages=Sample::PCRAges($time,"FIRST");
		$first_pcr_median_age=$this->median($first_pcr_ages);
		$sec_pcr_ages=Sample::PCRAges($time,"SECOND");
		$sec_pcr_median_age=$this->median($sec_pcr_ages);

		//other metrics by region
		$first_pcr_total_reg=Sample::NumberTotalsGroupBy($time,"FIRST","","regionID");
		$sec_pcr_total_reg=Sample::NumberTotalsGroupBy($time,"SECOND","","regionID");
		$total_samples_reg=Sample::NumberTotalsGroupBy($time,"","","regionID");
		$total_initiated_reg=Sample::NumberTotalsGroupBy($time,"",1,"regionID");

		//other metrics by district
		$first_pcr_total_dist=Sample::NumberTotalsGroupBy($time,"FIRST","","districtID");
		$sec_pcr_total_dist=Sample::NumberTotalsGroupBy($time,"SECOND","","districtID");
		$total_samples_dist=Sample::NumberTotalsGroupBy($time,"","","districtID");
		$total_initiated_dist=Sample::NumberTotalsGroupBy($time,"",1,"districtID");


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
			"av_by_reg_mth",
			"positives_by_dist",
			"pos_by_dist_sums",
			"av_by_dist",
			"av_by_dist_mth",
			"first_pcr_total",
			"sec_pcr_total",
			"first_pcr_median_age",
			"sec_pcr_median_age",
			"total_initiated",
			"total_samples",

			"first_pcr_total_reg",
			"sec_pcr_total_reg",
			"total_samples_reg",
			"total_initiated_reg",

			"first_pcr_total_dist",
			"sec_pcr_total_dist",
			"total_samples_dist",			
			"total_initiated_dist"

			
			));
	}

	private function arrSums($arr){
		$ret=[];
		foreach ($arr as $k => $v) {
			$ret[$k]=array_sum($v);			
		}
		return $ret;
	}

	private function arrAvs($arr_ttls,$arr_vals){
		$ret=[];
		foreach ($arr_ttls as $k => $v) {
			$ttl=array_sum($v);	
			$val=array_sum($arr_vals[$k]);	
			$av=$ttl>0?($val/$ttl)*100:0;
			$ret[$k]=round($av,1);	
		}
		return $ret;		
	}

	private function arrMonthAvs($arr_ttls,$arr_vals){
		$ret=[];
		foreach ($arr_ttls as $k => $months) {
			foreach ($months as $mth => $mth_ttl){
				$m_av=$mth_ttl>0?(($arr_vals[$k][$mth])/$mth_ttl)*100:0;
				$ret[$k][$mth]=round($m_av,1);
			}
		}
		return $ret;		
	}


	private function median($arr){
		sort($arr);
		$quantity=count($arr);
		$half_quantity=(int)($quantity/2);
		$ret=0;
		if($quantity%2==0){
			 $ret=($arr[($half_quantity-1)]+$arr[$half_quantity])/2;
		}else{
			$ret=$arr[$half_quantity];
		}
		return $ret;
	}

	/*

	I would say that he is a ‘master’, if it were not for my belief that no one ‘masters’ anything, that each finds or makes his candle, then tries to see by the guttering light. Mum has made a good candle. And Mum has good eyes.

	Gwendolyn Brooks


	Whether you are witness or executioner, the victim whose humanity you can never erase
	knows with clarity, more solid than granite that no matter which side you are on,
	any day or night, an injury to one remains an injury to all
	some where on this coninent, the voice of the ancient warns, that those who shit on the road, will find flies on their way back...



	*/

}
