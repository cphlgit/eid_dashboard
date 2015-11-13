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

	public function __construct(){
		$this->months=\MyHTML::initMonths();
		//$this->middleware('auth');
	}

	public function show($time=""){
		if(empty($time)) $time=date("Y");

		$regions=Region::regionsArr();
		$districts=District::districtsArr();
		$reg_districts=District::districtsByRegions();
		$facility_levels=FacilityLevel::facilityLevelsArr();

		//other metrics
		$first_pcr_ttl_grped=Sample::getNumberTotals($time,"FIRST");
		$sec_pcr_ttl_grped=Sample::getNumberTotals($time,"SECOND");
		$samples_ttl_grped=Sample::getNumberTotals($time);
		$initiated_ttl_grped=Sample::getNumberTotals($time,"",1);
		
		$first_pcr_total=$this->totalSums($first_pcr_ttl_grped);
		$sec_pcr_total=$this->totalSums($sec_pcr_ttl_grped);
		$total_initiated=$this->totalSums($initiated_ttl_grped);
		$total_samples=$this->totalSums($samples_ttl_grped);

		$first_pcr_ages=Sample::PCRAges($time,"FIRST");
		$first_pcr_median_age=$this->median($first_pcr_ages);
		$sec_pcr_ages=Sample::PCRAges($time,"SECOND");
		$sec_pcr_median_age=$this->median($sec_pcr_ages);

		$nice_counts=Sample::niceCounts($time);
		$nice_counts_positives=Sample::niceCounts($time,1);
		$nice_counts_art_inits=Sample::niceCounts($time,1,1);
		
		$av_initiation_rate_months=$this->getAverageRatesByMonth($nice_counts_art_inits,$nice_counts_positives);

		$count_positives_arr=$this->getTotalsByMonth($nice_counts_positives);
		$count_positives=array_sum($count_positives_arr);

		$nums_by_months=$this->getTotalsByMonth($nice_counts);

		$av_positivity=$this->getAverageRate($nice_counts_positives,$nice_counts);
		$av_positivity_arr=$this->getAverageRatesByMonth($nice_counts_positives,$nice_counts);

		$av_initiation_rate=$count_positives>0?($total_initiated/$count_positives)*100:0;		
		$av_initiation_rate=round($av_initiation_rate,1);
		

		$dist_n_reg_ids=District::distsNregs();

		return view('d',compact(
			"time",
			"regions",
			"districts",
			"reg_districts",
			"facility_levels",
			"count_positives",
			"av_positivity",
			"count_positives_arr",
			"av_positivity_arr",

			"first_pcr_total",
			"sec_pcr_total",
			"first_pcr_median_age",
			"sec_pcr_median_age",
			"total_initiated",
			"total_samples",

			"av_initiation_rate",

			"first_pcr_ttl_grped",
			"sec_pcr_ttl_grped",
			"samples_ttl_grped",
			"initiated_ttl_grped",

			"nums_by_months",
			"av_initiation_rate_months",

			"nice_counts",
			"nice_counts_positives",
			"nice_counts_art_inits",
			"dist_n_reg_ids"
			
			));
	}

	private function avInitRateM($count_positives_arr,$inits_by_M){
		$ret=[];
		$months=\MyHTML::initMonths();
		foreach ($months as $m=>$v) {
			$av=0;
			if(array_key_exists($m, $inits_by_M) && array_key_exists($m, $count_positives_arr)){
				$av=($inits_by_M[$m]/$count_positives_arr[$m])*100;
			}			
			$av=round($av,1);
			$ret[$m]=$av;
		}
		return $ret;
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
			$val=0;
			if(array_key_exists($k, $arr_vals)){
				$val=array_sum($arr_vals[$k]);	
			}
			
			$av=$ttl>0?($val/$ttl)*100:0;
			$ret[$k]=round($av,1);	
		}
		return $ret;		
	}

	private function arrMonthAvs($arr_ttls,$arr_vals){
		$ret=[];
		foreach ($arr_ttls as $k => $months) {
			foreach ($months as $mth => $mth_ttl){
				$m_av=0.0;
				if(array_key_exists($k, $arr_vals)){
					if(array_key_exists($mth, $arr_vals[$k])) $m_av=$mth_ttl>0?(($arr_vals[$k][$mth])/$mth_ttl)*100:0;
				}
				
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


	private function totalSums($totals){
		$ret=0;
		foreach ($totals as $lvl_id => $reg_data) {
			foreach ($reg_data as $reg_id => $dist_data) {
				$ret+=array_sum($dist_data);				
			}
		}
		return $ret;
	}

	private function totalSums2($arr){
		$ret=0;
		foreach ($arr as $lvl_id => $reg_data) {
			foreach ($reg_data as $reg_id => $dist_data) {
				foreach ($dist_data as $month_data) {
					$ret+=array_sum($month_data);					
				}								
			}
		}
		return $ret;
	}

	private function getTotalsByMonth($arr){
		$ret=$this->months;
		foreach ($arr as $lvl_id => $reg_data) {
			foreach ($reg_data as $reg_id => $dist_data) {
				foreach ($dist_data as $month_data) {
					foreach ($month_data as $mth => $val) $ret[$mth]+=$val;			
				}								
			}
		}
		return $ret;
	}

	private function getAverageRate($arr_up,$arr_down){
		$ret=0;
		$ttl_up=0;
		$ttl_down=0;
		foreach ($arr_up as $lvl_id => $reg_data) {
			foreach ($reg_data as $reg_id => $dist_data) {
				foreach ($dist_data as $dist_id => $month_data) {
					$ttl_up+=array_sum($month_data);
					$ttl_down+=array_sum($arr_down[$lvl_id][$reg_id][$dist_id]);
				}
			}
		}
		$ret=$ttl_down>0?($ttl_up/$ttl_down)*100:0;
		$ret=round($ret,1);
		return $ret;
	}

	private function getAverageRatesByMonth($arr_up,$arr_down){
		$up_res=$this->months;
		$down_res=$this->months;
		$ret=$this->months;
		foreach ($arr_up as $lvl_id => $reg_data) {
			foreach ($reg_data as $reg_id => $dist_data) {
				foreach ($dist_data as $dist_id => $month_data) {
					foreach ($month_data as $mth => $val){
						$up_res[$mth]+=$val;
						$down_res[$mth]+=$arr_down[$lvl_id][$reg_id][$dist_id][$mth];
					}		
				}								
			}
		}

		foreach ($up_res as $m => $v) {			
			$ret_val=$down_res[$m]>0?($up_res[$m]/$down_res[$m])*100:0;
			$ret[$m]=round($ret_val,1);
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
