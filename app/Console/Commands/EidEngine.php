<?php

namespace EID\Console\Commands;

use Illuminate\Console\Command;
use EID\Mongo;
use EID\Models\LiveData;

class EidEngine extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eidengine:run {--facilities} {--hibrid}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Loads data into Mongo';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->mongo=Mongo::connect();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        ini_set('memory_limit', '2500M');
        //
        $this->comment("Engine has started at :: ".date('YmdHis'));
        
        $facilities_flag = $this->option('facilities');
        $hibrid_flag = $this->option('hibrid');
        if($facilities_flag == 1){
            $this->updateFacilityDhis2Records();
            $this->_loadFacilities();
        }elseif($hibrid_flag == 1){
            $this->sendSurgeDataToHibrid();
        }
        else{
             $this->_loadHubs();
            $this->_loadDistricts();
            $this->_loadRegions();
            $this->_loadCareLevels();
            $this->_loadFacilities();

            $this->_loadData();
        }

       
        
        $this->comment("Engine has stopped at :: ".date('YmdHis'));

    }
    private function _loadData(){
        $this->mongo->eid_dashboard->drop();
        $year=2014;
        $current_year=date('Y');
       
       
        while($year<=$current_year){
            $samples_records = LiveData::getSamples($year);
            $counter=0;
            
            try {
                foreach($samples_records AS $s){
                    $data=[];
                    $year_month = $year.str_pad($s->month_of_year,2,0,STR_PAD_LEFT);
            
                    $data["sample_id"]=isset($s->id)? (int)$s->id: 0;
                    $data["infant_exp_id"]=isset($s->infant_exp_id)? $s->infant_exp_id: "UNKNOWN";//
                    $data["year_month"] = (int)$year_month;

                    $data["year_month_day"] = $this->extractYearMonthDay($s->date_dbs_taken);

                    $data['district_id']=isset($s->districtID)?(int)$s->districtID:0;
                    $data['hub_id']=isset($s->hubID)?(int)$s->hubID:0;

                    $data['region_id']=isset($s->regionID)?(int)$s->regionID:0;
                    $data['care_level_id']=isset($s->care_level_id)?(int)$s->care_level_id:0;
                    $data["facility_id"] = isset($s->facility_id)?(int)$s->facility_id:0;

                    $data["age_in_months"] = isset($s->age_in_months)?(int)$s->age_in_months:-1;
                 
                    $data["sex"] = isset($s->sex)?$s->sex:0;
                    
                    $data["art_initiation_status"] = isset($s->f_ART_initiated)?$s->f_ART_initiated:"UNKNOWN";
                    $data["art_initiation_date"] = isset($s->f_date_ART_initiated)?$s->f_date_ART_initiated:"UNKNOWN";
                    
                    
                    $data["pcr_test_requested"]=isset($s->PCR_test_requested)? $s->PCR_test_requested: "UNKNOWN";//
                    $data["testing_completed"]=isset($s->testing_completed)? $s->testing_completed: "UNKNOWN";
                    $data["accepted_result"]=isset($s->accepted_result)? $s->accepted_result: "UNKNOWN";
                    //$data["pcr"]=isset($s->pcr)? $s->pcr: "UNKNOWN";//
                    $data["pcr"]=$this->extractPCR($s);
                    $data["source"] = "cphl";
                   

                   $this->mongo->eid_dashboard->insert($data);
                   $counter ++;
                }//end of for loop
              echo " inserted $counter records for $year\n";
              $year++;
            } catch (Exception $e) {
                var_dump($e);
            }//end catch

        }//end of while loop
    }
    private function extractYearMonthDay($date_string){
        $date_array = explode("-", $date_string);

        $year_string=$this->getDigitString(intval($date_array[0]));
        $month_string=$this->getDigitString(intval($date_array[1]));
        $day_string=$this->getDigitString(intval($date_array[2]));


        return $this->getDateNumbericValue($year_string,$month_string,$day_string);

    }
    private function getDateNumbericValue($year_string,$month_string,$day_string){
        $year_month_day_string=$year_string.$month_string.$day_string;
        return intval($year_month_day_string);
    }
    private function getDigitString($number_value){
        if($number_value > 9)
            return "".$number_value;
        else
            return "0".$number_value;
    }
    private function extractPCR($sample){
        $pcr='UNKNOWN';
        if(isset($sample->pcr)){
            
            if($sample->pcr == 'FIRST' && $sample->non_routine == 'R2')//R1
                $pcr = 'R1';
            elseif ($sample->pcr == 'SECOND' && $sample->non_routine == 'R2'){//R2
                $pcr = 'R2';
            }else{
                $pcr = $sample->pcr;
            }
        }

        return $pcr;
    }
    public function _loadHubs(){
        $this->mongo->hubs->drop();
        $res=LiveData::getHubs();
        foreach($res AS $row){
            $data=['id'=>$row->id,'name'=>$row->hub];
            $this->mongo->hubs->insert($data);
        }
    }

    public function _loadDistricts(){
        $this->mongo->districts->drop();
        $res=LiveData::getDistricts();
        foreach($res AS $row){
            $data=['id'=>$row->id,'name'=>$row->district];
            $this->mongo->districts->insert($data);
        }
    }

    public function _loadRegions(){
        $this->mongo->regions->drop();
        $res=LiveData::getRegions();
        foreach($res AS $row){
            $data=['id'=>$row->id,'name'=>$row->region];
            $this->mongo->regions->insert($data);
        }
    }

    public function _loadCareLevels(){
        $this->mongo->care_levels->drop();
        $res=LiveData::getCareLevels();
        foreach($res AS $row){
            $data=['id'=>$row->id,'name'=>$row->facility_level];
            $this->mongo->care_levels->insert($data);
        }
    }

    public function _loadFacilities(){
        $this->mongo->facilities->drop();
        $res=LiveData::getFacilities();
        foreach($res AS $row){
            $data=[
            'id'=>$row->id,
            'name'=>$row->facility,
            'district_id'=>$row->districtID,
            'hub_id' => $row->hubID,
            'dhis2_name'=>$row->dhis2_name,
            'dhis2_uid' =>$row->dhis2_uid
            ];
            $this->mongo->facilities->insert($data);
        }
    }

    private function sendSurgeDataToHibrid(){
       $this->comment('started sending ....');
        //parameters, 
        $current_date = date("Ymd");
        $this->comment($current_date);
        //current week number
        $sending_date = strtotime("Tuesday");
        $start_date_timestamp = strtotime("-11 days",$sending_date);
        $end_date_timestamp = strtotime("-5 days", $sending_date);

        $start_date = date("Ymd",$start_date_timestamp);
        $end_date = date("Ymd",$end_date_timestamp);//20200216;

        $week_number = idate('W',$start_date_timestamp);//'W08';//
        $week_number_string =  idate('Y',$start_date_timestamp).$week_number;
        $this->comment('Start date: '.$start_date);
        $this->comment('End date: '.$end_date);
        $this->comment('Week Number: '.$week_number_string);

        //fetch data
        //--facility, number_of_pcr_1,number_of_0-2_in pcr1
        $surge_tests_payload = $this->getSurgeTestsPayload($start_date,$end_date,$week_number_string);
       \Log::info($surge_tests_payload);
        //send data to Hibrid

        
        $this->sendPayLoad($surge_tests_payload);

        $this->comment('finished sending to HIBRID ....');
    }


     private function sendPayLoad($data_instance){
        $this->comment( 'started sending data to server....' );
        $url = 'https://hibrid.ug.s-3.com/dhis/api/dataValueSets';
        $username = 'CPHL';
        $password = '11!January!2021';
        $auth_details = 'CPHL:11!January!2021';
        $toke_authentication = base64_encode($auth_details);
        // Collection object
        $data = $data_instance;
        // Initializes a new cURL session
        $curl_session_instance = curl_init($url);
        // Set the CURLOPT_RETURNTRANSFER option to true
        curl_setopt($curl_session_instance, CURLOPT_RETURNTRANSFER, true);
        // Set the CURLOPT_POST option to true for POST request
        curl_setopt($curl_session_instance, CURLOPT_POST, true);
        // Set the request data as JSON using json_encode function
        curl_setopt($curl_session_instance, CURLOPT_POSTFIELDS,  $data);
        // Set custom headers for RapidAPI Auth and Content-Type header
        curl_setopt($curl_session_instance, CURLOPT_HTTPHEADER, [
          //'X-RapidAPI-Host: kvstore.p.rapidapi.com',
          //'X-RapidAPI-Key: 7xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
          "Authorization: Basic $toke_authentication",
          'Content-Type: application/json'
        ]);

       // url: curl -d @one_site.json  -d @surge.json "https://hibrid.uat.s-3.net/dhis/api/dataValueSets" -H "Content-Type:application/json" -u CPHL_user:our password

        // Execute cURL request with all previous settings
        $response = curl_exec($curl_session_instance);
        // Close cURL session
        curl_close($curl_session_instance);
        $this->comment( 'Finished sending data ... response is below:' );
        $this->comment( $response );
    }
    
    public function getSurgeTestsPayload($from_date,$to_date,$week){
        $error_message = "";
        $surge_payload_json ="";
        $surge_test_report  = array();
        try {
            $from_date_int = intval($from_date);
            $to_date_int = intval($to_date);
            if($to_date_int >= $from_date_int){

                $pcr_tests = $this->getPcrTests($from_date_int,$to_date_int);
                $facility_numbers_zero_to_two_months = $this->_facilityNumbersForZeroToTwoMonths($from_date_int,$to_date_int);
                $cphl_facilities_array = iterator_to_array($this->mongo->facilities->find());
                $hibrid_dhis2_list = $this->getHibridDhis2MappedList();
                
                $row_counter = 0;
                foreach ($pcr_tests as $key => $pcr_test_instance) {
                    
                    $org_unit_hibrid_uid = $this->getHealthFaciltyHibridID($pcr_test_instance['_id'],$cphl_facilities_array, $hibrid_dhis2_list);

                    $surge_report_instance_pcr_one = array();
                    $surge_report_instance_pcr_one['categoryOptionCombo']='jnFowjaZhn6';
                    $surge_report_instance_pcr_one["attributeOptionCombo"]="snzGc8kSms5";
                    $surge_report_instance_pcr_one["dataElement"] = "mphv2XEpcs8";
                    $surge_report_instance_pcr_one["period"] = $week;
                    $surge_report_instance_pcr_one["orgUnit"] = $org_unit_hibrid_uid;
                    $surge_report_instance_pcr_one["value"]=$pcr_test_instance['pcr_one'];

                    if(trim($org_unit_hibrid_uid) == '')
                        continue;
                    array_push($surge_test_report, $surge_report_instance_pcr_one);
                    
                    if($row_counter == 20)
                        break;

                    $row_counter++;
                }//end_foreach

                foreach ($facility_numbers_zero_to_two_months as $key => $zero_to_two_months_instance) {
                    
                    
                   

                    $org_unit_hibrid_uid = $this->getHealthFaciltyHibridID($zero_to_two_months_instance['_id'],$cphl_facilities_array, $hibrid_dhis2_list);

                    $surge_report_instance_0_2_months = array();
                    $surge_report_instance_0_2_months['categoryOptionCombo']='jnFowjaZhn6';
                    $surge_report_instance_0_2_months["attributeOptionCombo"]="snzGc8kSms5";
                    $surge_report_instance_0_2_months["dataElement"] = "LZoIPma1Oeq";
                    $surge_report_instance_0_2_months["period"] = $week;
                    $surge_report_instance_0_2_months["orgUnit"] = $org_unit_hibrid_uid;
                    $surge_report_instance_0_2_months["value"]=$zero_to_two_months_instance['total_tests'];

                    if(trim($org_unit_hibrid_uid) == '')
                        continue;
                    array_push($surge_test_report, $surge_report_instance_0_2_months);

                     if($row_counter == 20)
                        break;
                    
                }//end foreach

              
              
               $surge_payload['dataValues'] = $surge_test_report;
               $surge_payload_json = json_encode($surge_payload);


            }else{
                $error_message = "The from-date must older than the to-date.";
            }
        } catch (Exception $e) {
            $error_message = "Ensure the dates are in the format 'YYYYMMDD'";
        }
        if($error_message != ""){
            return $error_message;
        }
                
        return $surge_payload_json;
    }

    private function getHibridDhis2MappedList(){
        $file = fopen("public/csvs/hibrid_dhis2_mapping.csv", "r");
        $data = array();
        //loading CSV entire data
       
        
        while ( ! feof($file )) {

            $array_instance = fgetcsv($file);
            //print_r($array_instance);

                $facility['facility']=$array_instance[0];
                $facility['dhis2_uid']=$array_instance[5];
                $facility['dhis2_name']=$array_instance[6];
                $facility['DHIS2_UID2']=$array_instance[7];
                $facility['HIBRID_UID']=$array_instance[8];

               
                
                array_push($data, $facility);

                
        }
        
        //remove duplicates
        $facilities = $this->unique_multidim_array($data,'HIBRID_UID'); 
        return $this->getFacilityMap($facilities);
        
    }
    private function getFacilityMap($facilities_array){

        $facility_map = array();
        $row=0;
        foreach ($facilities_array as $key => $facility_instance) {
            $row++;
           if($facility_instance['dhis2_uid'] == "\N"){
                $map_key = $facility_instance['DHIS2_UID2'];
                $facility_map[$map_key] = $facility_instance['HIBRID_UID'];
           }elseif($facility_instance['DHIS2_UID2'] == "#N/A"){

                 $map_key = $facility_instance['dhis2_uid'];
                 $facility_map[$map_key] = $facility_instance['HIBRID_UID'];
           }else{
                
                 $map_key = $facility_instance['DHIS2_UID2'];
                 $facility_map[$map_key] = $facility_instance['HIBRID_UID'];
           }
        }
        return $facility_map;
    }
    private function unique_multidim_array($array, $key) { 
        $temp_array = array(); 
        $i = 0; 
        $key_array = array(); 
        
        foreach($array as $val) { 
            if (!in_array($val[$key], $key_array)) { 
                $key_array[$i] = $val[$key]; 
                $temp_array[$i] = $val; 
            } 
            $i++; 
        } 
        return $temp_array; 
    }
    private function getHealthFaciltyHibridID($facility_id,$cphl_facility_array,$hibrid_mapped_array){
        $hibrid_uid = '';
        foreach ($cphl_facility_array as $key => $cphl_facility) {
                if(intval($cphl_facility['id']) == intval($facility_id )){
                 
                 if($cphl_facility['dhis2_uid'] === NULL || $cphl_facility['dhis2_uid'] === '')
                    continue;
                    try {
                           
                            $cphl_dhis2_uid = $cphl_facility['dhis2_uid'];

                            if( isset($hibrid_mapped_array[$cphl_dhis2_uid]) )
                               $hibrid_uid = $hibrid_mapped_array[$cphl_dhis2_uid];
                               
                       
                    } catch (Exception $e) {
                        $hibrid_uid = 'UNKNOWN';
                       
                    }
                     break;
                }

        }  
        return $hibrid_uid; 
    }
    private function _facilityNumbersForZeroToTwoMonths($from_date,$to_date){
        $this->conditions = $this->_setApiConditions($from_date,$to_date);
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
    private function _setApiConditions($from_date,$to_date){

        $conds=[];
    
        $conds['$and'][]=['year_month_day'=>  ['$gte'=> (int)$from_date] ];
        $conds['$and'][]=[ 'year_month_day'=>  ['$lte'=> (int)$to_date] ];

        return $conds;
    }

    private function getPcrTests($from_date,$to_date){
        $this->conditions = $this->_setApiConditions($from_date,$to_date);

        $match_stage['$match']=$this->conditions;
        $group_stage = array(

            '$group' => array(
                '_id' => '$facility_id', 
                'total_tests' => array('$sum' => 1 ),
                'pcr_one' => array('$sum' => array('$cond'=>array(array('$eq' => array('$pcr','FIRST')),1,0))),
                
             ));
      
        $res=$this->mongo->eid_dashboard->aggregate($match_stage,$group_stage );
        
        return isset($res['result'])?$res['result']:[];
 
    }

   private function updateFacilityDhis2Records(){
        $eid_facilities = LiveData::getFacilities();
        $dhis2_facilities = LiveData::getDhis2FacilityData();
       

        $dhis2_name_string = "";
        $dhis2_uid_string = "";
        $where_id_in_string = "";

        $counter =0;
        foreach ($eid_facilities as $eid_facility) {
            
            foreach ($dhis2_facilities as $dhis2_facility) {
                if($eid_facility->facility == $dhis2_facility->facility){
                    $dhis2_name_string = $dhis2_name_string . 'WHEN '.$eid_facility->id .' THEN "'.$dhis2_facility->dhis2_name.'" ';
                    $dhis2_uid_string = $dhis2_uid_string . 'WHEN '.$eid_facility->id .' THEN "'.$dhis2_facility->dhis2_uid.'" ';
                    $where_id_in_string = $where_id_in_string . $dhis2_facility->id .',';
                    $counter++;

                    break;
                }
            }
            
        }
try{


        $where_id_in_string = $this->removeLastComma($where_id_in_string);
        $sql = 'UPDATE facilities SET dhis2_name = 
        (CASE id  '.$dhis2_name_string .' END),  dhis2_uid = (CASE id  '.$dhis2_uid_string .' END) 
        WHERE id IN ('.$where_id_in_string .')';

        $result_flag = \DB::connection('live_db')->select($sql);
        $this->comment("....DHIS2 Records Update: Completed ....");
    }catch(Exception $e){
        
    }
        



    }//end method

    private function removeLastComma($where_string){
      return substr_replace($where_string, '', -1);
    }

  


}
