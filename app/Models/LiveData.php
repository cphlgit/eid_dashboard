<?php

namespace EID\Models;

use Illuminate\Database\Eloquent\Model;

class LiveData extends Model
{
	const AGE_IN_MONTHS_STRING = "(YEAR(s.date_dbs_taken) - YEAR(s.infant_dob)) * 12 + 
	(MONTH(s.date_data_entered) - MONTH(s.infant_dob)) - 
	IF(DAYOFMONTH(s.date_dbs_taken) < DAYOFMONTH(s.infant_dob),1,0)";


	const GENDER_CASE = "CASE WHEN infant_gender ='MALE' THEN 'm' WHEN infant_gender='FEMALE' 
	THEN 'f' ELSE 'UNKNOWN' END";

	const RESULT_CASE = "CASE WHEN accepted_result ='POSITIVE' THEN 'POSITIVE' WHEN accepted_result='NEGATIVE' 
	THEN 'NEGATIVE' ELSE 'UNKNOWN' END";

	public static function getHubs(){

    	$sql = "SELECT * FROM hubs";
        $hubs =  \DB::connection('live_db')->select($sql);
        
        return $hubs;
    }

    public static function getDistricts(){

    	$sql = "SELECT * FROM districts";
        $districts =  \DB::connection('live_db')->select($sql);
        
        return $districts;
    }

    public static function getRegions(){

    	$sql = "SELECT * FROM regions";
        $regions =  \DB::connection('live_db')->select($sql);
        
        return $regions;
    }

    public static function getCareLevels(){

    	$sql = "SELECT * FROM facility_levels";
        $care_levels =  \DB::connection('live_db')->select($sql);
        
        return $care_levels;
    }

    public static function getFacilities(){

    	$sql = "SELECT * FROM facilities";
        $facilities =  \DB::connection('live_db')->select($sql);
        
        return $facilities;
    }

    public static function getSamples($year){

    	$sql = "SELECT s.id,s.infant_exp_id,".self::GENDER_CASE." as sex,s.infant_dob,month(s.date_dbs_taken) as month_of_year,
		 ".self::AGE_IN_MONTHS_STRING." as age_in_months ,b.facility_id,f.hubID,f.facilityLevelID as care_level_id,f.districtID,
		 d.regionID, s.f_ART_initiated,s.f_date_ART_initiated,".self::RESULT_CASE." as accepted_result,s.testing_completed, s.PCR_test_requested,s.pcr FROM dbs_samples s 
		inner join batches b on s.batch_id =b.id 
		inner join facilities f on f.id = b.facility_id 
		inner join districts d on d.id = f.districtID

		where s.PCR_test_requested like 'YES' 
		and year(s.date_dbs_taken)=$year";

		$samples = \DB::connection('live_db')->select($sql);

		return $samples;
    }


}

?>