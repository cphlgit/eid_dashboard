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
    protected $signature = 'eidengine:run {--facilities}';

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
        if($facilities_flag == 1){
            $this->updateFacilityDhis2Records();
            $this->_loadFacilities();
        }else{
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

        $where_id_in_string = $this->removeLastComma($where_id_in_string);
        $sql = 'UPDATE facilities SET dhis2_name = 
        (CASE id  '.$dhis2_name_string .' END),  dhis2_uid = (CASE id  '.$dhis2_uid_string .' END) 
        WHERE id IN ('.$where_id_in_string .')';

        $result_flag = \DB::connection('live_db')->select($sql);
        $this->comment("....DHIS2 Records Update: Completed ....");
        



    }//end method

    private function removeLastComma($where_string){
      return substr_replace($where_string, '', -1);
    }

  


}
