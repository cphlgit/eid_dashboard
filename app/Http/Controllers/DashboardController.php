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
use EID\Models\HealthFacility;
use EID\Mongo;
use Validator;
use Lang;
use Redirect;
use Request;
use Session;
use Log;
//use Illuminate\Http\Request;

class DashboardController extends Controller {

	public function __construct(){
		$this->months=\MyHTML::initMonths();
		$this->mongo=Mongo::connect();
		$this->conditions=$this->_setConditions();
		$this->db = \DB::connection('live_db');
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

		$fclty_counts=Sample::niceCounts($time,"","",1);//niceCounts($year="",$postives="",$art_inits="",$grpby_fclts="")
		$fclty_pos_counts=Sample::niceCounts($time,1,"",1);
		$fclty_inits=Sample::niceCounts($time,1,1,1);

		$facility_numbers=$this->facilityNumbers($fclty_counts,$fclty_pos_counts,$fclty_inits);

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
			"dist_n_reg_ids",

			"facility_numbers"
			
			));
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

	private function _dateNMonthsBack(){
    	$ret=0;
    	$n=env('INIT_MONTHS');
        $m=date('m');
        $y=date('Y');
        for($i=1;$i<=$n;$i++){
        	
            if($m==0){
                $m=12;
                $y--;
            }
            if($i==$n){
        		$ret=$y.str_pad($m, 2,0, STR_PAD_LEFT);
        	} 
            $m--;
        }
        return $ret;
    }
    private function _getDefaultStartDate(){
    	$ret=0;
    	$n=env('INIT_MONTHS');
        $m=date('m');
        $y=date('Y');
        for($i=1;$i<=$n;$i++){
        	
            if($m==0){
                $m=12;
                $y--;
            }
            if($i==$n){
        		$ret=$y.str_pad($m, 2,0, STR_PAD_LEFT)."01";
        	} 
            $m--;
        }
        return $ret;
    }
	private function _setConditions(){
		extract(\Request::all());
	
		
		//if((empty($fro_date) && empty($to_date))||$fro_date=='all' && $to_date=='all'){
		//	$to_date=date("Ym");
		//	$fro_date=$this->_dateNMonthsBack();
		//}

		if(empty($fro_date) && empty($to_date)){
			$to_date=date("Ymd");
			$fro_date=$this->_getDefaultStartDate();
			
		}
       

		$conds=[];
		$source = !isset($source)?'cphl':$source;
		if($source!='all'){
			$conds['$and'][] = ['source'=>$source];
		}
		//$conds['$and'][]=['year_month'=>  ['$gte'=> (int)$fro_date] ];
		//$conds['$and'][]=[ 'year_month'=>  ['$lte'=> (int)$to_date] ];
		
		$conds['$and'][]=['year_month_day'=>  ['$gte'=> (int)$fro_date] ];
		$conds['$and'][]=[ 'year_month_day'=>  ['$lte'=> (int)$to_date] ];

		if(!empty($age_ids)&&$age_ids!='[]') {
			
			$age_bands=json_decode($age_ids);
			$number_of_age_bands=sizeof($age_bands);

			$lower_age_band=0;
			$upper_age_band=0;
			if($number_of_age_bands > 0){
				$lower_age_band=$age_bands[0];
				$last_index = $number_of_age_bands - 1;
				$upper_age_band=$age_bands[$last_index];
			}

			
			$conds['$and'][]=[ 'age_in_months'=>  ['$gte'=> (int)$lower_age_band] ];
			$conds['$and'][]=[ 'age_in_months'=>  ['$lte'=> (int)$upper_age_band] ];
			
			
		}
		if(!empty($districts)&&$districts!='[]') $conds['$and'][]=[ 'district_id'=>  ['$in'=> json_decode($districts)] ];
		if(!empty($regions)&&$regions!='[]') $conds['$and'][]=[ 'region_id'=>  ['$in'=> json_decode($regions)] ];

		if(!empty($hubs)&&$hubs!='[]') $conds['$and'][]=[ 'hub_id'=>  ['$in'=> json_decode($hubs)] ];
		if(!empty($care_levels)&&$care_levels!='[]') $conds['$and'][]=[ 'care_level_id'=>  ['$in'=> json_decode($care_levels)] ];

		if(!empty($genders)&&$genders!='[]') $conds['$and'][]=[ 'sex'=>  ['$in'=> json_decode($genders)] ];
		if(!empty($pcrs)&&$pcrs!='[]') $conds['$and'][]=[ 'pcr'=>  ['$in'=> json_decode($pcrs)] ];
			
		return $conds;
	}
	private function _setApiConditions($from_date,$to_date){
		extract(\Request::all());
	
       
		$conds=[];
		$source = !isset($source)?'cphl':$source;
		if($source!='all'){
			$conds['$and'][] = ['source'=>$source];
		}
	
		$conds['$and'][]=['year_month_day'=>  ['$gte'=> (int)$from_date] ];
		$conds['$and'][]=[ 'year_month_day'=>  ['$lte'=> (int)$to_date] ];

		return $conds;
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

	private function facilityNumbers($counts,$positive_counts,$init_counts){
		$ret=[];
		foreach ($counts as $k => $v) {
			extract($v);			
			$abs_positives=array_key_exists($k, $positive_counts)?$positive_counts[$k]["value"]:0;
			$positivity_rate=$value>0?($abs_positives/$value)*100:0;
			$positivity_rate=round($positivity_rate,1);

			$initiated=array_key_exists($k, $init_counts)?$init_counts[$k]["value"]:0;
			$initiation_rate=$abs_positives>0?($initiated/$abs_positives)*100:0;
			$initiation_rate=round($initiation_rate);


			$ret[]=[
				"facility_id"=>$facility_id,
				"facility_name"=>$facility_name,
				"district_id"=>$district_id,
				"region_id"=>$region_id,
				"level_id"=>$level_id,
				"abs_positives"=>$abs_positives,
				"total_results"=>$value,
				"positivity_rate"=>$positivity_rate,
				"initiation_rate"=>$initiation_rate
				];
				
		}
		return $ret;
	}

	private function numberMaps($counts,$positive_counts,$init_counts){
		$ret=[];
		foreach ($counts as $k => $v) {
			extract($v);
			$abs_positives=array_key_exists($k, $positive_counts)?$positive_counts[$k]["value"]:0;
			$positivity_rate=$value>0?($abs_positives/$value)*100:0;
			$positivity_rate=round($positivity_rate,1);

			$initiated=array_key_exists($k, $init_counts)?$init_counts[$k]["value"]:0;
			$initiation_rate=$abs_positives>0?($initiated/$abs_positives)*100:0;
			$initiation_rate=round($initiation_rate);


			$ret[]=[
				"facility_id"=>$facility_id,
				"month"=>$month,				
				"facility_name"=>$facility_name,
				"district_id"=>$district_id,
				"region_id"=>$region_id,
				"level_id"=>$level_id,
				"abs_positives"=>$abs_positives,
				"total_results"=>$value,
				"positivity_rate"=>$positivity_rate,
				"initiation_rate"=>$initiation_rate
				];
				
		}
		return $ret;
	}

	public function other_data(){
		$hubs=iterator_to_array($this->mongo->hubs->find());
		$regions=iterator_to_array($this->mongo->regions->find());
		$districts=iterator_to_array($this->mongo->districts->find());
		$facilities=iterator_to_array($this->mongo->facilities->find());
		$care_levels=iterator_to_array($this->mongo->care_levels->find());

	
		return compact("hubs","regions","districts","facilities","care_levels");
	}

	public function live(){

		$whole_numbers=$this->_wholeNumbers();
		$duration_numbers=$this->_durationNumbers();
		
		$dist_numbers_for_zero_to_two_months = $this->_districtNumbersForZeroToTwoMonths();
		$dist_numbers_for_zero_to_two_months_pcr1 = $this->_districtNumbersForZeroToTwoMonthsFirstPCR();
		$dist_numbers_for_positives_zero_to_two_months = $this->_districtNumbersPositivesForZeroToTwoMonths();


		$dist_numbers_for_positives = $this->_districtNumbersForPositivePCRs();
		$dist_numbers=$this->_districtNumbers();

		$facility_numbers=$this->_facilityNumbers();
		$poc_facility_numbers=$this->_pocfacilityNumbers();
		$facility_numbers_for_positives=$this->_facilityNumbersForPositivePCRs();
		$facility_numbers_zero_to_two_months = $this->_facilityNumbersForZeroToTwoMonths();
		$facility_numbers_zero_to_two_months_pcr1=$this->_facilityNumbersForZeroToTwoMonthsFirstPcr();
		$facility_numbers_positives_zero_to_two_months = $this->_facilityNumbersPositivesForZeroToTwoMonths();

		return compact("whole_numbers","duration_numbers",
			"dist_numbers","dist_numbers_for_positives","dist_numbers_for_zero_to_two_months_pcr1",
			
			"dist_numbers_for_zero_to_two_months","dist_numbers_for_positives_zero_to_two_months",
			"facility_numbers_for_positives","facility_numbers_zero_to_two_months",
			"facility_numbers_zero_to_two_months_pcr1",
			"facility_numbers_positives_zero_to_two_months",
			"facility_numbers","poc_facility_numbers");
	}
	
	private function _wholeNumbers(){


		$match_stage['$match']=$this->conditions;
		$project_stage = array(

			'$group' => array(
				 '_id'=>null,
				'total_tests' => array('$sum' => 1 ),
				'pcr_one' => array('$sum' => array('$cond'=>array(array('$eq' => array('$pcr','FIRST')),1,0))),
				'pcr_two' => array('$sum' => array('$cond'=>array(array('$eq' => array('$pcr','SECOND')),1,0))),

				'pcr_three' => array('$sum' => array('$cond'=>array(array('$eq' => array('$pcr','THIRD')),1,0))),
				'repeat_one' => array('$sum' => array('$cond'=>array(array('$eq' => array('$pcr','R1')),1,0))),
				'repeat_two' => array('$sum' => array('$cond'=>array(array('$eq' => array('$pcr','R2')),1,0))),
				'repeat_three' => array('$sum' => array('$cond'=>array(array('$eq' => array('$pcr','R3')),1,0))),

				'hiv_positive_infants' => array('$sum' => array('$cond'=>array(array('$eq' => array('$accepted_result','POSITIVE')),1,0))),
				'art_initiated' => array('$sum' => array('$cond'=>array(array('$eq' => array('$art_initiation_status','YES')),1,0))),
			 ));
		$res=$this->mongo->eid_dashboard->aggregate($match_stage,$project_stage);
		
		
		return isset($res['result'])?$res['result']:[];
	}
	
	private function _durationNumbers(){
		$match_stage['$match']=$this->conditions;
	   //total tests
	 	$total_tests_statement = array('$sum' => 1 );

	 	//tatal of samples whose testing is completed
	 	$if_testing_is_completed = array('$eq' => array('$testing_completed','YES'));
	 	$testing_is_completed_cond = array($if_testing_is_completed,1,0);
	 	$testing_is_completed_cond_statement = array('$cond' =>$testing_is_completed_cond );
	 	$total_testing_completed_sum_statement = array('$sum'=>$testing_is_completed_cond_statement);

		//total pcr1 tests	        
		$if_pcr_one = array('$eq' => array('$pcr','FIRST'));
		$pcr1_cond = array($if_pcr_one,1,0);
		$pcr1_cond_statement = array('$cond' =>$pcr1_cond );
		$pcr1_sum_statement = array('$sum'=>$pcr1_cond_statement);

		//total pcr2 tests
		$if_pcr_two = array('$eq' => array('$pcr','SECOND'));
		$pcr2_cond = array($if_pcr_two,1,0);
		$pcr2_cond_statement = array('$cond' =>$pcr2_cond );
		$pcr2_tests_sum_statement = array('$sum'=>$pcr2_cond_statement);

		//total pcr3 tests
		$if_pcr_three = array('$eq' => array('$pcr','THIRD'));
		$pcr3_cond = array($if_pcr_three,1,0);
		$pcr3_cond_statement = array('$cond' =>$pcr3_cond );
		$pcr3_tests_sum_statement = array('$sum'=>$pcr3_cond_statement);

		//total R1 tests
		$if_pcr_r1 = array('$eq' => array('$pcr','R1'));
		$pcr_r1_cond = array($if_pcr_r1,1,0);
		$pcr_r1_cond_statement = array('$cond' =>$pcr_r1_cond );
		$pcr_r1_tests_sum_statement = array('$sum'=>$pcr_r1_cond_statement);

		//total R2 tests
		$if_pcr_r2 = array('$eq' => array('$pcr','R2'));
		$pcr_r2_cond = array($if_pcr_r2,1,0);
		$pcr_r2_cond_statement = array('$cond' =>$pcr_r2_cond );
		$pcr_r2_tests_sum_statement = array('$sum'=>$pcr_r2_cond_statement);

		//total R3 tests
		$if_pcr_r3 = array('$eq' => array('$pcr','R3'));
		$pcr_r3_cond = array($if_pcr_r3,1,0);
		$pcr_r3_cond_statement = array('$cond' =>$pcr_r3_cond );
		$pcr_r3_tests_sum_statement = array('$sum'=>$pcr_r3_cond_statement);


		//pcr_one_hiv_positive_infants
			$if_pcr_one = array('$eq' => array('$pcr','FIRST'));
			$if_accepted_result = array('$eq' => array('$accepted_result','POSITIVE'));
			$and_cond = array($if_pcr_one,$if_accepted_result);
			$and_array=array('$and'=>$and_cond);

			$cond_statement = array($and_array,1,0);
			$cond_array=array('$cond'=>$cond_statement);
			$sum_satement = array('$sum'=>$cond_array);

		//pcr_two_hiv_positive_infants
			$pcr2_if_pcr_two = array('$eq' => array('$pcr','SECOND'));
			$pcr2_if_accepted_result = array('$eq' => array('$accepted_result','POSITIVE'));
			$pcr2_and_cond = array($pcr2_if_pcr_two,$pcr2_if_accepted_result);
			$pcr2_and_array=array('$and'=>$pcr2_and_cond);

			$pcr2_cond_statement = array($pcr2_and_array,1,0);
			$pcr2_cond_array=array('$cond'=>$pcr2_cond_statement);
			$pcr2_sum_satement = array('$sum'=>$pcr2_cond_array);

		//pcr_three_hiv_positive_infants
			$pcr3_if_pcr_three = array('$eq' => array('$pcr','THIRD'));
			$pcr3_if_accepted_result = array('$eq' => array('$accepted_result','POSITIVE'));
			$pcr3_and_cond = array($pcr3_if_pcr_three,$pcr3_if_accepted_result);
			$pcr3_and_array=array('$and'=>$pcr3_and_cond);

			$pcr3_cond_statement = array($pcr3_and_array,1,0);
			$pcr3_cond_array=array('$cond'=>$pcr3_cond_statement);
			$pcr3_sum_satement = array('$sum'=>$pcr3_cond_array);

		//pcr_R1_hiv_positive_infants
			$pcr_if_pcr_R1 = array('$eq' => array('$pcr','R1'));
			$pcr_if_accepted_result_R1 = array('$eq' => array('$accepted_result','POSITIVE'));
			$pcr_and_cond_R1 = array($pcr_if_pcr_R1,$pcr_if_accepted_result_R1);
			$pcr_and_array_R1=array('$and'=>$pcr_and_cond_R1);

			$pcr_cond_statement_R1 = array($pcr_and_array_R1,1,0);
			$pcr_cond_array_R1=array('$cond'=>$pcr_cond_statement_R1);
			$pcr_sum_satement_R1 = array('$sum'=>$pcr_cond_array_R1);

		//pcr_R2_hiv_positive_infants
			$pcr_if_pcr_R2 = array('$eq' => array('$pcr','R2'));
			$pcr_if_accepted_result_R2 = array('$eq' => array('$accepted_result','POSITIVE'));
			$pcr_and_cond_R2 = array($pcr_if_pcr_R2,$pcr_if_accepted_result_R2);
			$pcr_and_array_R2=array('$and'=>$pcr_and_cond_R2);

			$pcr_cond_statement_R2 = array($pcr_and_array_R2,1,0);
			$pcr_cond_array_R2=array('$cond'=>$pcr_cond_statement_R2);
			$pcr_sum_satement_R2 = array('$sum'=>$pcr_cond_array_R2);

		//pcr_R3_hiv_positive_infants
			$pcr_if_pcr_R3 = array('$eq' => array('$pcr','R3'));
			$pcr_if_accepted_result_R3 = array('$eq' => array('$accepted_result','POSITIVE'));
			$pcr_and_cond_R3 = array($pcr_if_pcr_R3,$pcr_if_accepted_result_R3);
			$pcr_and_array_R3=array('$and'=>$pcr_and_cond_R3);

			$pcr_cond_statement_R3 = array($pcr_and_array_R3,1,0);
			$pcr_cond_array_R3=array('$cond'=>$pcr_cond_statement_R3);
			$pcr_sum_satement_R3 = array('$sum'=>$pcr_cond_array_R3);


		//pcr_one_art_initiated	
			$pcr1_art_initiated_if=array('$eq' => array('$pcr','FIRST'));
			$pcr1_art_initiated_if_accepted_result=array('$eq' => array('$art_initiation_status','YES'));
			$pcr1_art_initiated_and_cond=array($pcr1_art_initiated_if,$pcr1_art_initiated_if_accepted_result);
			$pcr1_art_initiated_and_array=array('$and'=>$pcr1_art_initiated_and_cond);
			$pcr1_art_initiated_cond_statement = array($pcr1_art_initiated_and_array,1,0);
			$pcr1_art_initiated_cond_array=array('$cond'=>$pcr1_art_initiated_cond_statement);
			$pcr1_art_initiated_sum_statement = array('$sum'=>$pcr1_art_initiated_cond_array);

		//pcr_two_art_initiated	
			$pcr2_art_initiated_if=array('$eq' => array('$pcr','SECOND'));
			$pcr2_art_initiated_if_accepted_result=array('$eq' => array('$art_initiation_status','YES'));
			$pcr2_art_initiated_and_cond=array($pcr2_art_initiated_if,$pcr2_art_initiated_if_accepted_result);
			$pcr2_art_initiated_and_array=array('$and'=>$pcr2_art_initiated_and_cond);
			$pcr2_art_initiated_cond_statement = array($pcr2_art_initiated_and_array,1,0);
			$pcr2_art_initiated_cond_array=array('$cond'=>$pcr2_art_initiated_cond_statement);
			$pcr2_art_initiated_sum_statement = array('$sum'=>$pcr2_art_initiated_cond_array);

		//pcr_three_art_initiated	
			$pcr3_art_initiated_if=array('$eq' => array('$pcr','THIRD'));
			$pcr3_art_initiated_if_accepted_result=array('$eq' => array('$art_initiation_status','YES'));
			$pcr3_art_initiated_and_cond=array($pcr3_art_initiated_if,$pcr3_art_initiated_if_accepted_result);
			$pcr3_art_initiated_and_array=array('$and'=>$pcr3_art_initiated_and_cond);
			$pcr3_art_initiated_cond_statement = array($pcr3_art_initiated_and_array,1,0);
			$pcr3_art_initiated_cond_array=array('$cond'=>$pcr3_art_initiated_cond_statement);
			$pcr3_art_initiated_sum_statement = array('$sum'=>$pcr3_art_initiated_cond_array);

		//pcr_art_initiated	R1
			$pcr_art_initiated_if_R1=array('$eq' => array('$pcr','R1'));
			$pcr_art_initiated_if_accepted_result_R1=array('$eq' => array('$art_initiation_status','YES'));
			$pcr_art_initiated_and_cond_R1=array($pcr_art_initiated_if_R1,$pcr_art_initiated_if_accepted_result_R1);
			$pcr_art_initiated_and_array_R1=array('$and'=>$pcr_art_initiated_and_cond_R1);
			$pcr_art_initiated_cond_statement_R1 = array($pcr_art_initiated_and_array_R1,1,0);
			$pcr_art_initiated_cond_array_R1=array('$cond'=>$pcr_art_initiated_cond_statement_R1);
			$pcr_art_initiated_sum_statement_R1 = array('$sum'=>$pcr_art_initiated_cond_array_R1);

		//pcr_art_initiated	R2
			$pcr_art_initiated_if_R2=array('$eq' => array('$pcr','R2'));
			$pcr_art_initiated_if_accepted_result_R2=array('$eq' => array('$art_initiation_status','YES'));
			$pcr_art_initiated_and_cond_R2=array($pcr_art_initiated_if_R2,$pcr_art_initiated_if_accepted_result_R2);
			$pcr_art_initiated_and_array_R2=array('$and'=>$pcr_art_initiated_and_cond_R2);
			$pcr_art_initiated_cond_statement_R2 = array($pcr_art_initiated_and_array_R2,1,0);
			$pcr_art_initiated_cond_array_R2=array('$cond'=>$pcr_art_initiated_cond_statement_R2);
			$pcr_art_initiated_sum_statement_R2 = array('$sum'=>$pcr_art_initiated_cond_array_R2);


		//pcr_art_initiated	R3
			$pcr_art_initiated_if_R3=array('$eq' => array('$pcr','R3'));
			$pcr_art_initiated_if_accepted_result_R3=array('$eq' => array('$art_initiation_status','YES'));
			$pcr_art_initiated_and_cond_R3=array($pcr_art_initiated_if_R3,$pcr_art_initiated_if_accepted_result_R3);
			$pcr_art_initiated_and_array_R3=array('$and'=>$pcr_art_initiated_and_cond_R3);
			$pcr_art_initiated_cond_statement_R3 = array($pcr_art_initiated_and_array_R3,1,0);
			$pcr_art_initiated_cond_array_R3=array('$cond'=>$pcr_art_initiated_cond_statement_R3);
			$pcr_art_initiated_sum_statement_R3 = array('$sum'=>$pcr_art_initiated_cond_array_R3);

		//'hiv_positive_infants'      	
			$hiv_positive_infants_if=array('$eq' => array('$accepted_result','POSITIVE'));
	     	$hiv_positive_infants_cond_statement = array($hiv_positive_infants_if,1,0);
	     	$hiv_positive_infants_cond_array=array('$cond'=>$hiv_positive_infants_cond_statement);
	     	$hiv_positive_infants_sum_statement = array('$sum'=>$hiv_positive_infants_cond_array);
     
    	//'art_initiated' 
	     	$art_initiated_if=array('$eq' => array('$art_initiation_status','YES'));
	     	$art_initiated_cond=array($art_initiated_if,1,0);
	     	$art_initiated_cond_array=array('$cond'=>$art_initiated_cond);
	     	$art_initiated_sum_statement=array('$sum'=>$art_initiated_cond_array);

		//group stage
		$group_array =array(
			'_id'=>'$year_month',

			'total_tests'=> $total_tests_statement,
			'total_testing_completed' => $total_testing_completed_sum_statement,
			
			'pcr_one'=>$pcr1_sum_statement,
			'pcr_two'=>$pcr2_tests_sum_statement,
			'pcr_three'=>$pcr3_tests_sum_statement,
			'pcr_R1'=>$pcr_r1_tests_sum_statement,
			'pcr_R2'=>$pcr_r2_tests_sum_statement,
			'pcr_R3'=>$pcr_r3_tests_sum_statement,

			'pcr_one_hiv_positive_infants'=>$sum_satement,
			'pcr_two_hiv_positive_infants'=>$pcr2_sum_satement,
			'pcr_three_hiv_positive_infants'=>$pcr3_sum_satement,
			'pcr_R1_hiv_positive_infants'=>$pcr_sum_satement_R1,
			'pcr_R2_hiv_positive_infants'=>$pcr_sum_satement_R2,
			'pcr_R3_hiv_positive_infants'=>$pcr_sum_satement_R3, 

			//'pcr_one_art_initiated'=>$pcr1_art_initiated_sum_statement,
			//'pcr_two_art_initiated'=>$pcr2_art_initiated_sum_statement,
			//'pcr_three_art_initiated'=>$pcr3_art_initiated_sum_statement,
			//'pcr_R1_art_initiated'=>$pcr_art_initiated_sum_statement_R1,
			//'pcr_R2_art_initiated'=>$pcr_art_initiated_sum_statement_R2,
			//'pcr_R3_art_initiated'=>$pcr_art_initiated_sum_statement_R3,

			'hiv_positive_infants'=>$hiv_positive_infants_sum_statement,
			'art_initiated'=>$art_initiated_sum_statement

			);
		$group_stage  = array('$group' =>  $group_array);

		$sort_stage = array(
			'$sort'=>array('_id'=>1)
			);
		$res=$this->mongo->eid_dashboard->aggregate($match_stage,$group_stage,$sort_stage);
		
		
		return isset($res['result'])?$res['result']:[];


	}
	private function _facilityNumbers(){
	
		$match_stage['$match']=$this->conditions;
		$group_stage = array(

			'$group' => array(
				'_id' => '$facility_id', 
				'total_tests' => array('$sum' => 1 ),
				'pcr_one' => array('$sum' => array('$cond'=>array(array('$eq' => array('$pcr','FIRST')),1,0))),
				'pcr_two' => array('$sum' => array('$cond'=>array(array('$eq' => array('$pcr','SECOND')),1,0))),
				'pcr_three' => array('$sum' => array('$cond'=>array(array('$eq' => array('$pcr','THIRD')),1,0))),
				'pcr_R1' => array('$sum' => array('$cond'=>array(array('$eq' => array('$pcr','R1')),1,0))),
				'pcr_R2' => array('$sum' => array('$cond'=>array(array('$eq' => array('$pcr','R2')),1,0))),
				'pcr_R3' => array('$sum' => array('$cond'=>array(array('$eq' => array('$pcr','R3')),1,0))),

				
				/*
				'pcr_one_art_initiated'=>array('$sum' => array('$cond'=>array(
					array( '$and'
							=> array(
								array('$eq'=>array('$pcr','FIRST'),
									'$eq'=>array('$art_initiation_status', 'YES'))
							 )
						),1,0))),
				'pcr_two_art_initiated'=>array('$sum' => array('$cond'=>array(
					array( '$and'
							=> array(
								array('$eq'=>array('$pcr','SECOND'),
									'$eq'=>array('$art_initiation_status', 'YES'))
							 )
						),1,0))),
				'pcr_three_art_initiated'=>array('$sum' => array('$cond'=>array(
					array( '$and'
							=> array(
								array('$eq'=>array('$pcr','THREE'),
									'$eq'=>array('$art_initiation_status', 'YES'))
							 )
						),1,0))),
				'pcr_art_initiated_R1'=>array('$sum' => array('$cond'=>array(
					array( '$and'
							=> array(
								array('$eq'=>array('$pcr','R1'),
									'$eq'=>array('$art_initiation_status', 'YES'))
							 )
						),1,0))),
				'pcr_art_initiated_R2'=>array('$sum' => array('$cond'=>array(
					array( '$and'
							=> array(
								array('$eq'=>array('$pcr','R2'),
									'$eq'=>array('$art_initiation_status', 'YES'))
							 )
						),1,0))),
				'pcr_art_initiated_R3'=>array('$sum' => array('$cond'=>array(
					array( '$and'
							=> array(
								array('$eq'=>array('$pcr','R3'),
									'$eq'=>array('$art_initiation_status', 'YES'))
							 )
						),1,0))),*/

				'hiv_positive_infants' => array('$sum' => array('$cond'=>array(array('$eq' => array('$accepted_result','POSITIVE')),1,0))),
				'art_initiated' => array('$sum' => array('$cond'=>array(array('$eq' => array('$art_initiation_status','YES')),1,0))),
			 ));
		/*"$and": [ { "$eq": [ "$pcr", "FIRST" ] }, { "$eq": [ "$accepted_result", "POSITIVE" ] } ] 
		=> array(
			array('$eq'=>array('$pcr','FIRST'),
				'$eq'=>array('$accepted_result', 'POSITIVE'))
		 );*/
		
		$res=$this->mongo->eid_dashboard->aggregate($match_stage,$group_stage );
		
		
		return isset($res['result'])?$res['result']:[];
	}
	
	private function _districtNumbers(){
	
		$match_stage['$match']=$this->conditions;
		$group_stage = array(

			'$group' => array(
				'_id' => '$district_id', 
				'total_tests' => array('$sum' => 1 ),
				'pcr_one' => array('$sum' => array('$cond'=>array(array('$eq' => array('$pcr','FIRST')),1,0))),
				'pcr_two' => array('$sum' => array('$cond'=>array(array('$eq' => array('$pcr','SECOND')),1,0))),
				'pcr_three' => array('$sum' => array('$cond'=>array(array('$eq' => array('$pcr','THIRD')),1,0))),
				'pcr_R1' => array('$sum' => array('$cond'=>array(array('$eq' => array('$pcr','R1')),1,0))),
				'pcr_R2' => array('$sum' => array('$cond'=>array(array('$eq' => array('$pcr','R2')),1,0))),
				'pcr_R3' => array('$sum' => array('$cond'=>array(array('$eq' => array('$pcr','R3')),1,0))),


				'hiv_positive_infants' => array('$sum' => array('$cond'=>array(array('$eq' => array('$accepted_result','POSITIVE')),1,0))),
				'art_initiated' => array('$sum' => array('$cond'=>array(array('$eq' => array('$art_initiation_status','YES')),1,0))),
			 ));
		/*"$and": [ { "$eq": [ "$pcr", "FIRST" ] }, { "$eq": [ "$accepted_result", "POSITIVE" ] } ] 
		=> array(
			array('$eq'=>array('$pcr','FIRST'),
				'$eq'=>array('$accepted_result', 'POSITIVE'))
		 );*/
		
		$res=$this->mongo->eid_dashboard->aggregate($match_stage,$group_stage );
		
		
		return isset($res['result'])?$res['result']:[];
	}
	private function _districtNumbersForPositivePCRs(){
		
		$conds=$this->conditions;
		
		$conds['$and'][]=[ 'accepted_result'=>  ['$eq'=> 'POSITIVE'] ];
		

		$match_stage['$match']=$conds;
		$group_stage = array(

			'$group' => array(
				'_id' => '$district_id', 
				
				'pcr_one_hiv_positive_infants' => array('$sum' => array('$cond'=>array(array('$eq' => array('$pcr','FIRST')),1,0))),
				'pcr_two_hiv_positive_infants' => array('$sum' => array('$cond'=>array(array('$eq' => array('$pcr','SECOND')),1,0))),
				'pcr_three_hiv_positive_infants' => array('$sum' => array('$cond'=>array(array('$eq' => array('$pcr','THIRD')),1,0))),
				'pcr_hiv_positive_infants_R1' => array('$sum' => array('$cond'=>array(array('$eq' => array('$pcr','R1')),1,0))),
				'pcr_hiv_positive_infants_R2' => array('$sum' => array('$cond'=>array(array('$eq' => array('$pcr','R2')),1,0))),
				'pcr_hiv_positive_infants_R3' => array('$sum' => array('$cond'=>array(array('$eq' => array('$pcr','R3')),1,0)))
			 ));
		
		
		$res=$this->mongo->eid_dashboard->aggregate($match_stage,$group_stage );
		
		$pcr_positives=[];
		foreach ($res['result'] as $key => $value) {
			$key_id = $value['_id'];
			$pcr_positives[$key_id] = $value;
		}
		//return isset($res['result'])?$res['result']:[];
		return $pcr_positives;
	}
	private function _facilityNumbersForPositivePCRs(){
		
		$conds=$this->conditions;
		
		$conds['$and'][]=[ 'accepted_result'=>  ['$eq'=> 'POSITIVE'] ];
		

		$match_stage['$match']=$conds;
		$group_stage = array(

			'$group' => array(
				'_id' => '$facility_id', 
				
				'pcr_one_hiv_positive_infants' => array('$sum' => array('$cond'=>array(array('$eq' => array('$pcr','FIRST')),1,0))),
				'pcr_two_hiv_positive_infants' => array('$sum' => array('$cond'=>array(array('$eq' => array('$pcr','SECOND')),1,0))),
				'pcr_three_hiv_positive_infants' => array('$sum' => array('$cond'=>array(array('$eq' => array('$pcr','THIRD')),1,0))),
				'pcr_hiv_positive_infants_R1' => array('$sum' => array('$cond'=>array(array('$eq' => array('$pcr','R1')),1,0))),
				'pcr_hiv_positive_infants_R2' => array('$sum' => array('$cond'=>array(array('$eq' => array('$pcr','R2')),1,0))),
				'pcr_hiv_positive_infants_R3' => array('$sum' => array('$cond'=>array(array('$eq' => array('$pcr','R3')),1,0)))
			 ));
		
		
		$res=$this->mongo->eid_dashboard->aggregate($match_stage,$group_stage );
		
		$pcr_positives=[];
		foreach ($res['result'] as $key => $value) {
			$key_id = $value['_id'];
			$pcr_positives[$key_id] = $value;
		}
		//return isset($res['result'])?$res['result']:[];
		return $pcr_positives;
	}

	private function _districtNumbersForZeroToTwoMonths(){
		
		$conds=$this->conditions;
		
		$conds['$and'][]=[ 'age_in_months'=>  ['$lte'=> 2] ];
		

		$match_stage['$match']=$conds;
		$group_stage = array(

			'$group' => array(
				'_id' => '$district_id', 
				'total_tests' => array('$sum' => 1 )
				 ));
		
		
		$res=$this->mongo->eid_dashboard->aggregate($match_stage,$group_stage );
		
		$pcr_positives=[];
		foreach ($res['result'] as $key => $value) {
			$key_id = $value['_id'];
			$pcr_positives[$key_id] = $value;
		}
		//return isset($res['result'])?$res['result']:[];
		return $pcr_positives;
	}
	private function _districtNumbersForZeroToTwoMonthsFirstPCR(){
		
		$conds=$this->conditions;
		
		$conds['$and'][]=[ 'age_in_months'=>  ['$lte'=> 2] ];
		$conds['$and'][]=[ 'pcr'=>  ['$eq'=> 'FIRST'] ];
		

		$match_stage['$match']=$conds;
		$group_stage = array(

			'$group' => array(
				'_id' => '$district_id', 
				'total_tests' => array('$sum' => 1 )
				 ));
		
		
		$res=$this->mongo->eid_dashboard->aggregate($match_stage,$group_stage );
		
		$pcr_positives=[];
		foreach ($res['result'] as $key => $value) {
			$key_id = $value['_id'];
			$pcr_positives[$key_id] = $value;
		}
		//return isset($res['result'])?$res['result']:[];
		return $pcr_positives;
	}
	private function _districtNumbersPositivesForZeroToTwoMonths(){
		
		$conds=$this->conditions;
		
		$conds['$and'][]=[ 'age_in_months'=>  ['$lte'=> 2] ];
		$conds['$and'][]=[ 'accepted_result'=>  ['$eq'=> 'POSITIVE'] ];

		$match_stage['$match']=$conds;
		$group_stage = array(

			'$group' => array(
				'_id' => '$district_id', 
				'total_tests' => array('$sum' => 1 )
				 ));
		
		
		$res=$this->mongo->eid_dashboard->aggregate($match_stage,$group_stage );
		
		$pcr_positives=[];
		foreach ($res['result'] as $key => $value) {
			$key_id = $value['_id'];
			$pcr_positives[$key_id] = $value;
		}
		//return isset($res['result'])?$res['result']:[];
		return $pcr_positives;
	}
	private function _districtNumbersPositivesForZeroToTwoMonthsFirstPCR(){
		
		$conds=$this->conditions;
		
		$conds['$and'][]=[ 'age_in_months'=>  ['$lte'=> 2] ];
		$conds['$and'][]=[ 'accepted_result'=>  ['$eq'=> 'POSITIVE'] ];
		$conds['$and'][]=[ 'pcr'=>  ['$eq'=> 'FIRST'] ];

		$match_stage['$match']=$conds;
		$group_stage = array(

			'$group' => array(
				'_id' => '$district_id', 
				'total_tests' => array('$sum' => 1 )
				 ));
		
		
		$res=$this->mongo->eid_dashboard->aggregate($match_stage,$group_stage );
		
		$pcr_positives=[];
		foreach ($res['result'] as $key => $value) {
			$key_id = $value['_id'];
			$pcr_positives[$key_id] = $value;
		}
		//return isset($res['result'])?$res['result']:[];
		return $pcr_positives;
	}
    private function _facilityNumbersForZeroToTwoMonths(){
		
		$conds=$this->conditions;
		
		$conds['$and'][]=[ 'age_in_months'=>  ['$lte'=> 2] ];
		

		$match_stage['$match']=$conds;
		$group_stage = array(

			'$group' => array(
				'_id' => '$facility_id', 
				'total_tests' => array('$sum' => 1 )
				 ));
		
		
		$res=$this->mongo->eid_dashboard->aggregate($match_stage,$group_stage );
		
		$pcr_positives=[];
		foreach ($res['result'] as $key => $value) {
			$key_id = $value['_id'];
			$pcr_positives[$key_id] = $value;
		}
		//return isset($res['result'])?$res['result']:[];
		return $pcr_positives;
	}
	private function _facilityNumbersForZeroToTwoMonthsFirstPcr(){
		
		$conds=$this->conditions;
		
		$conds['$and'][]=[ 'age_in_months'=>  ['$lte'=> 2] ];
		$conds['$and'][]=[ 'pcr'=>  ['$eq'=> 'FIRST'] ];

		$match_stage['$match']=$conds;
		$group_stage = array(

			'$group' => array(
				'_id' => '$facility_id', 
				'total_tests' => array('$sum' => 1 )
				 ));
		
		
		$res=$this->mongo->eid_dashboard->aggregate($match_stage,$group_stage );
		
		$pcr_positives=[];
		foreach ($res['result'] as $key => $value) {
			$key_id = $value['_id'];
			$pcr_positives[$key_id] = $value;
		}
		//return isset($res['result'])?$res['result']:[];
		return $pcr_positives;
	}
	private function _facilityNumbersPositivesForZeroToTwoMonths(){
		
		$conds=$this->conditions;
		
		$conds['$and'][]=[ 'age_in_months'=>  ['$lte'=> 2] ];
		$conds['$and'][]=[ 'accepted_result'=>  ['$eq'=> 'POSITIVE'] ];

		$match_stage['$match']=$conds;
		$group_stage = array(

			'$group' => array(
				'_id' => '$facility_id', 
				'total_tests' => array('$sum' => 1 )
				 ));
		
		
		$res=$this->mongo->eid_dashboard->aggregate($match_stage,$group_stage );
		
		$pcr_positives=[];
		foreach ($res['result'] as $key => $value) {
			$key_id = $value['_id'];
			$pcr_positives[$key_id] = $value;
		}
		//return isset($res['result'])?$res['result']:[];
		return $pcr_positives;
	}
	

	/*
db.eid_dashboard.aggregate(
  [    {
       $match: {year_month:{$gt:201801}}
      },
      {
          $group:{
              _id:'$year_month',
              hiv_pcr_one: {"$sum": 
              					{
                                  "$cond": 
                                  [ 
                                  	{ "$and": 
                                		[ 
                                    		{ $eq: [ "$pcr", "FIRST" ] }
                                    
                                        ] 
                                   }, 
                                   1, 
                                   0 
                                   ]
                                }
                            },
                            
          }
      }
  ]
)

	*/
	private function _districtNumbersTester(){
	
		$match_stage['$match']=$this->conditions;
		$group_tester = array('$group' => array(
			'_id'=>'$year_month',
			'hiv_pcr_one'=>array('$sum'=>array(
				'$cond'=>array(array('$and'=>array(
					array('$eq'=>array('$pcr','FIRST'))
					)),1,0))),
			), 
		);

		$group_stage = array(

			'$group' => array(
				'_id' => '$district_id', 
				'total_tests' => array('$sum' => 1 ),
				'pcr_one' => array('$sum' => array('$cond'=>array(array('$eq' => array('$pcr','FIRST')),1,0))),
				'pcr_two' => array('$sum' => array('$cond'=>array(array('$eq' => array('$pcr','SECOND')),1,0))),
				'pcr_three' => array('$sum' => array('$cond'=>array(array('$eq' => array('$pcr','THIRD')),1,0))),
				'pcr_R1' => array('$sum' => array('$cond'=>array(array('$eq' => array('$pcr','R1')),1,0))),
				'pcr_R2' => array('$sum' => array('$cond'=>array(array('$eq' => array('$pcr','R2')),1,0))),
				'pcr_R3' => array('$sum' => array('$cond'=>array(array('$eq' => array('$pcr','R3')),1,0))),

				'pcr_one_hiv_positive_infants'=>array('$sum' => array('$cond'=>array(
					array( '$and'
							=> array(
								array('$eq'=>array('$pcr','FIRST'),
									'$eq'=>array('$accepted_result', 'POSITIVE'))
							 )
						),1,0))),
				'pcr_two_hiv_positive_infants'=>array('$sum' => array('$cond'=>array(
					array( '$and'
							=> array(
								array('$eq'=>array('$pcr','SECOND'),
									'$eq'=>array('$accepted_result', 'POSITIVE'))
							 )
						),1,0))),
				'pcr_three_hiv_positive_infants'=>array('$sum' => array('$cond'=>array(
					array( '$and'
							=> array(
								array('$eq'=>array('$pcr','THREE'),
									'$eq'=>array('$accepted_result', 'POSITIVE'))
							 )
						),1,0))),
				'pcr_hiv_positive_infants_R1'=>array('$sum' => array('$cond'=>array(
					array( '$and'
							=> array(
								array('$eq'=>array('$pcr','R1'),
									'$eq'=>array('$accepted_result', 'POSITIVE'))
							 )
						),1,0))),
				'pcr_hiv_positive_infants_R2'=>array('$sum' => array('$cond'=>array(
					array( '$and'
							=> array(
								array('$eq'=>array('$pcr','R2'),
									'$eq'=>array('$accepted_result', 'POSITIVE'))
							 )
						),1,0))),
				'pcr_hiv_positive_infants_R3'=>array('$sum' => array('$cond'=>array(
					array( '$and'
							=> array(
								array('$eq'=>array('$pcr','R3'),
									'$eq'=>array('$accepted_result', 'POSITIVE'))
							 )
						),1,0))),


				'pcr_one_art_initiated'=>array('$sum' => array('$cond'=>array(
					array( '$and'
							=> array(
								array('$eq'=>array('$pcr','FIRST'),
									'$eq'=>array('$art_initiation_status', 'YES'))
							 )
						),1,0))),
				'pcr_two_art_initiated'=>array('$sum' => array('$cond'=>array(
					array( '$and'
							=> array(
								array('$eq'=>array('$pcr','SECOND'),
									'$eq'=>array('$art_initiation_status', 'YES'))
							 )
						),1,0))),
				'pcr_three_art_initiated'=>array('$sum' => array('$cond'=>array(
					array( '$and'
							=> array(
								array('$eq'=>array('$pcr','THIRD'),
									'$eq'=>array('$art_initiation_status', 'YES'))
							 )
						),1,0))),
				'pcr_art_initiated_R1'=>array('$sum' => array('$cond'=>array(
					array( '$and'
							=> array(
								array('$eq'=>array('$pcr','R1'),
									'$eq'=>array('$art_initiation_status', 'YES'))
							 )
						),1,0))),
				'pcr_art_initiated_R2'=>array('$sum' => array('$cond'=>array(
					array( '$and'
							=> array(
								array('$eq'=>array('$pcr','R2'),
									'$eq'=>array('$art_initiation_status', 'YES'))
							 )
						),1,0))),
				'pcr_art_initiated_R3'=>array('$sum' => array('$cond'=>array(
					array( '$and'
							=> array(
								array('$eq'=>array('$pcr','R3'),
									'$eq'=>array('$art_initiation_status', 'YES'))
							 )
						),1,0))),

				'hiv_positive_infants' => array('$sum' => array('$cond'=>array(array('$eq' => array('$accepted_result','POSITIVE')),1,0))),
				'art_initiated' => array('$sum' => array('$cond'=>array(array('$eq' => array('$art_initiation_status','YES')),1,0))),
			 ));
		/*"$and": [ { "$eq": [ "$pcr", "FIRST" ] }, { "$eq": [ "$accepted_result", "POSITIVE" ] } ] 
		=> array(
			array('$eq'=>array('$pcr','FIRST'),
				'$eq'=>array('$accepted_result', 'POSITIVE'))
		 );*/

		$group_stage1 = array(

			'$group' => array(
				'_id' => '$district_id', 
				//'total_tests' => array('$sum' => 1 ),
				//'pcr_one' => array('$sum' => array('$cond'=>array(array('$eq' => array('$pcr','FIRST')),1,0))),
				//'pcr_two' => array('$sum' => array('$cond'=>array(array('$eq' => array('$pcr','SECOND')),1,0))),
				//'pcr_three' => array('$sum' => array('$cond'=>array(array('$eq' => array('$pcr','THIRD')),1,0))),
				//'pcr_r1' => array('$sum' => array('$cond'=>array(array('$eq' => array('$pcr','R1')),1,0))),
				//'pcr_r2' => array('$sum' => array('$cond'=>array(array('$eq' => array('$pcr','R2')),1,0))),
				//'pcr_r3' => array('$sum' => array('$cond'=>array(array('$eq' => array('$pcr','R3')),1,0))),

				
				'pcr_one_pst'=>[
								'$sum'=>['$cond'=>[['$and'=>[[
													 			'$eq'=>['$pcr','FIRST'],
													 			'$eq'=>['$accepted_result','POSITIVE'],
													 		]]],1,0]]
				                ],
				'pcr_two_pst'=>[
								'$sum'=>['$cond'=>[['$and'=>[[
													 			'$eq'=>['$pcr','SECOND'],
													 			'$eq'=>['$accepted_result','POSITIVE'],
													 		]]],1,0]]
				                ],
				'pcr_three_pst'=>[
								'$sum'=>['$cond'=>[['$and'=>[[
													 			'$eq'=>['$pcr','THIRD'],
													 			'$eq'=>['$accepted_result','POSITIVE'],
													 		]]],1,0]]
				                ],
				'r1_pst'=>[
								'$sum'=>['$cond'=>[['$and'=>[[
													 			'$eq'=>['$pcr','R1'],
													 			'$eq'=>['$accepted_result','POSITIVE'],
													 		]]],1,0]]
				                ],
				'r2_pst'=>[
								'$sum'=>['$cond'=>[['$and'=>[[
													 			'$eq'=>['$pcr','R2'],
													 			'$eq'=>['$accepted_result','POSITIVE'],
													 		]]],1,0]]
				                ],
				'r3_pst'=>[
								'$sum'=>['$cond'=>[['$and'=>[[
													 			'$eq'=>['$pcr','R3'],
													 			'$eq'=>['$accepted_result','POSITIVE'],
													 		]]],1,0]]
				                ],
		));
		
		$res=null;
		try{
			$res=$this->mongo->eid_dashboard->aggregate($match_stage,$group_stage1 );
		}catch(Exception $e){
			$e->getMessage();
		}
		
		return isset($res['result'])?$res['result']:[];
	}
 

    public function mongoQueryExecutor($condition,$group){
    	
    }
	public function getResultsPrintingStatistics(){
		
			$sql="SELECT f.facility,h.hub,ips.ip,
                SUM(CASE WHEN fp.dispatched =0 THEN 1 ELSE 0 END) AS pending_results,
                MAX(fp.dispatch_at) AS last_printed_on,
                MIN(CASE WHEN fp.dispatched =0 THEN fp.qc_at END) AS oldest_result_pending_printing
                FROM dbs_samples AS s
                INNER JOIN batches AS b ON s.batch_id = b.id
                INNER JOIN facility_printing AS fp ON b.id = fp.batch_id
                inner join facilities f on f.id=b.facility_id 
			    inner join hubs h on h.id=f.hubID 
			    left join ips on ips.id = h.ipID
                WHERE date(fp.qc_at)>='".env('RESULTS_CUTOFF', '')."'
                GROUP BY f.id";

			$facilities = $this->db->select($sql);
		
		return compact('facilities');

	}

	public function getPocFacilityStatistics(){
			$sql = "SELECT district, f.id, facility, COUNT(DISTINCT facility) AS peripheral_sites, poc_device, SUM(CASE WHEN p.created_at > DATE_SUB(NOW(), INTERVAL 1 WEEK) THEN 1 ELSE 0 END) as thiswk,
 SUM(CASE WHEN p.created_at BETWEEN DATE_SUB(NOW(), INTERVAL 1 WEEK) AND NOW() THEN 1 ELSE 0 END) as wk1, SUM(CASE WHEN p.created_at BETWEEN DATE_SUB(NOW(), INTERVAL 2 WEEK) AND DATE_SUB(NOW(), INTERVAL 1 WEEK) THEN 1 ELSE 0 END) as wk2, SUM(CASE WHEN p.created_at BETWEEN DATE_SUB(NOW(), INTERVAL 3 WEEK) AND DATE_SUB(NOW(), INTERVAL 2 WEEK) THEN 1 ELSE 0 END) as wk3, SUM(CASE WHEN p.created_at BETWEEN DATE_SUB(NOW(), INTERVAL 4 WEEK) AND DATE_SUB(NOW(), INTERVAL 3 WEEK) THEN 1 ELSE 0 END) as wk4, SUM(CASE WHEN p.created_at BETWEEN DATE_SUB(NOW(), INTERVAL 5 WEEK) AND DATE_SUB(NOW(), INTERVAL 4 WEEK) THEN 1 ELSE 0 END) as wk5, SUM(CASE WHEN p.created_at BETWEEN DATE_SUB(NOW(), INTERVAL 6 WEEK) AND DATE_SUB(NOW(), INTERVAL 5 WEEK) THEN 1 ELSE 0 END) as wk6, SUM(CASE WHEN p.created_at BETWEEN DATE_SUB(NOW(), INTERVAL 7 WEEK) AND DATE_SUB(NOW(), INTERVAL 6 WEEK) THEN 1 ELSE 0 END) as wk7, SUM(CASE WHEN p.created_at BETWEEN DATE_SUB(NOW(), INTERVAL 8 WEEK) AND DATE_SUB(NOW(), INTERVAL 7 WEEK) THEN 1 ELSE 0 END) as wk8, max(created_at) AS latest_date,
				COUNT(p.id) AS tests,  
				SUM(CASE WHEN results='Negative' THEN 1 ELSE 0 END) AS negatives,
				SUM(CASE WHEN results='Positive' THEN 1 ELSE 0 END) AS positives,
				SUM(CASE WHEN results='Error' THEN 1 ELSE 0 END) AS errors
				FROM poc_data AS p
				LEFT JOIN facilities AS f ON p.facility_id=f.id
				LEFT JOIN districts AS d ON f.districtID=d.id
				GROUP BY testing_facility;";

		    $pocfacilities = $this->db->select($sql);

		    return compact('pocfacilities');

	}

	public function stock_data(Request $request){
        $from = $request->get('start');
        $to = $request->get('end');
		$records = \DB::select("select d.*,f.facility from inventory_data d JOIN facilities f ON(d.facility_id = f.id)");
        if($request->has('word')){
                $date = date("Ymdhi");
                $fileName = "stock_status_".$date.".doc";
                $headers = array(
                    "Content-type"=>"text/html",
                    "Content-Disposition"=>"attachment;Filename=".$fileName
                );
                $content = view('poc.exportStockLog')
                    ->with('records', $records)
                    ->withInput($request->all());
                return \Response::make($content,200, $headers);
            }
            else{
		return view('poc.poc_stock',compact('records'));
            }
	}

	public function getSurgeTests(Request $request_parameters,$from_date,$to_date){
		$error_message = "";
		$surge_test_report  = array();
		try {
			$from_date_int = intval($from_date);
			$to_date_int = intval($to_date);
			if($to_date_int >= $from_date_int){

				$surge_test_report = $this->prepareSurgeTestReports($from_date_int,$to_date_int);

			}else{
				$error_message = "The from-date must older than the to-date.";
			}
		} catch (Exception $e) {
			$error_message = "Ensure the dates are in the format 'YYYYMMDD'";
		}
		if($error_message != ""){
			return $error_message;
		}
				
		return $surge_test_report;
	}

	private function prepareSurgeTestReports($from_date,$to_date){
		$this->conditions = $this->_setApiConditions($from_date,$to_date);

		$facility_numbers=$this->_facilityNumbers();
		//$facility_numbers_for_positives=$this->_facilityNumbersForPositivePCRs();
		$facility_numbers_zero_to_two_months = $this->_facilityNumbersForZeroToTwoMonths();
		$facility_numbers_zero_to_two_months_pcr1=$this->_facilityNumbersForZeroToTwoMonthsFirstPcr();
		//$facility_numbers_positives_zero_to_two_months = $this->_facilityNumbersPositivesForZeroToTwoMonths();

		$mongo_facilities=iterator_to_array($this->mongo->facilities->find());

		$clean_result_set=array();
        //$clean_result_set['description']=$params;
           
            //headers
            $header['cphl_facilityID']='facilityID';
            $header['cphl_facility_name']='facility_name';
            $header['facility_dhis2_name']='dhis2_facility_name';
            $header['facility_dhis2_code']='dhis2_uid';

            $header['total_tested_first_pcr']='total_tested_first_pcr';
            $header['total_tested_0_2_months']='total_tested_0_2_months';
            
        	array_push($clean_result_set, $header);

        	$facilities  = array( );
        	foreach ($mongo_facilities as $key => $facility) {
        	 	

        	 	$health_facilitity = new HealthFacility(
        	 		$facility['id'],
        	 		$facility['name'],
        	 		$facility['district_id'],
        	 		$facility['dhis2_name'],
        	 		$facility['dhis2_uid']
        	 		);
        	 	
        	 	
        	 	$facilities[$facility['id']] = $health_facilitity;
        	} 

        	foreach ($facility_numbers as $key => $record) {

            $facility_id=$record['_id'];
            $facility = $facilities[$facility_id];

            $fields['cphl_facilityID']=$record['_id'];
            
            
            $fields['cphl_facility_name']=isset($facility->name) ? $facility->name: 'Null';
            $fields['facility_dhis2_name'] = isset($facility->dhis2_uid) ? $facility->dhis2_uid: 'Null';
            $fields['facility_dhis2_code']=isset($facility->dhis2_name) ? $facility->dhis2_name: 'Null';
           
           	$fields['total_tested_first_pcr']=isset($facility_numbers_zero_to_two_months_pcr1[$facility_id]->total_tests) ?
           									$facility_numbers_zero_to_two_months_pcr1[$facility_id]->total_tests:
           									0;
            $fields['total_tested_0_2_months']=isset($facility_numbers_zero_to_two_months[$facility_id]->total_tests) ? 
            									$facility_numbers_zero_to_two_months[$facility_id]->total_tests : 
            									0;
                        
            array_push($clean_result_set, $fields);
        }
        return $clean_result_set;
	}

	private function _pocfacilityNumbers(){
	
		$sql = "SELECT f.id, COUNT(DISTINCT f.id) AS total
				FROM poc_data AS p
				LEFT JOIN facilities AS f ON p.facility_id=f.id
				LEFT JOIN districts AS d ON f.districtID=d.id;";

		    $pocfacilities = $this->db->select($sql);
		// $group_stage = array(

		// 	);
		
		// $res=$this->mongo->eid_dashboard->aggregate($match_stage,$group_stage );
		
		
		return isset($pocfacilities['total'])?$pocfacilities['total']:[];
	}

	/*

	I would say that he is a ‘master’, if it were not for my belief that no one ‘masters’ anything, that each finds or makes his candle, then tries to see by the guttering light. Mum has made a good candle. And Mum has good eyes.

	Gwendolyn Brooks


	Whether you are witness or executioner, the victim whose humanity you can never erase
	knows with clarity, more solid than granite that no matter which side you are on,
	any day or night, an injury to one remains an injury to all
	some where on this coninent, the voice of the ancient warns, that those who shit on the road, will find flies on their way back..

	*/

}