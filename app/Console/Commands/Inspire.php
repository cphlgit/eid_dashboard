<?php

namespace EID\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Foundation\Inspiring;

class Inspire extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inspire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display an inspiring quote';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
       // $this->comment(PHP_EOL.Inspiring::quote().PHP_EOL);
      
     $this->loadFoundDhis2Codes();
     //$this->updateDhis2Fields();
    }
    
    private function loadFoundDhis2Codes(){
        $file_name = "./docs/eid_found_dhis2.csv";
        $eid_found_dhis2_codes_list = $this->getEidFoundDhis2CodesList($file_name);
        
        //update facilities' table
        foreach ($eid_found_dhis2_codes_list as $key => $facility_record) {
            $facility_id = $facility_record["id"];
            $facility_dhis2_uid = $facility_record["dhis2_facility_uid"];
            $facility_dhis2_name = $facility_record["dhis2_facility_name"];

            $update_sql_statement = "UPDATE facilities SET dhis2_uid=?, dhis2_name=? where id=?";
            try{
                \DB::connection("live_db")->update($update_sql_statement,
                    [$facility_dhis2_uid,$facility_dhis2_name,$facility_id]);

            }catch(Exception $e){
                $this->comment($e->getMessage());
            }
        }
        $this->comment("Done... loading DHIS2 Codes into mysql database");

    }
    private function getEidFoundDhis2CodesList($file_name){
        $file = fopen($file_name, "r");
        $data = array();
        //loading CSV entire data
        
        while ( ! feof($file )) {

            $array_instance = fgetcsv($file);
            //print_r($array_instance);

             //dhis2_facility_uid,dhis2_facility_name,dhis2_subcounty_uid,dhis2_subcounty_name,dhis2_district_uid,dhis2_district_name,dhis2_region

                $facility['id']=$array_instance[0];
                $facility['cphl_facility_name']=$array_instance[1];
                $facility['cphl_district']=$array_instance[2];
                $facility['dhis2_facility_uid']=$array_instance[3];
                $facility['dhis2_facility_name']=$array_instance[4];
                
               
                
                array_push($data, $facility);
             
            
        }
        
        //remove duplicates
        $facilities = $this->unique_multidim_array($data,'dhis2_facility_uid'); 

        return $facilities;
    }
    private function updateDhis2Fields(){
        //load csv master_facility_list
        $file_name = "./docs/dhis2_master_facility_list.csv";
        $dhis2_master_facility_list=$this->getDhis2MasterFacilityList($file_name);

        //load sites_missing_dhis2_fields
        $file_name_for_sites_missing = "./docs/eid_missing.csv";
        $facilities_missing_dhis2_fields = $this->getFacilitiesWithMissingDhis2Codes($file_name_for_sites_missing);


        //match sites
        $final_list=[];
        $counter =0;
        foreach ($facilities_missing_dhis2_fields as $key => $facility_missing_dhis2_fields) {
            $region_of_facility = $facility_missing_dhis2_fields['cphl_region'];

            
            //$dhis2_facilities_in_region = $this->getRegionDhis2Facilities($region_of_facility,$dhis2_master_facility_list);
            $dhis2_facilities_in_region = $this->getNationalDhis2Facilities($dhis2_master_facility_list);
            $cphl_facility_name = $facility_missing_dhis2_fields['cphl_facility_name'];
            
            if($cphl_facility_name === "cphl_facility_name")
               continue;
         $this->comment("...$cphl_facility_name.....");

            $dhis2_facility_name = $this->getClosestMatchByLevenshtein($cphl_facility_name, $dhis2_facilities_in_region);

            $updated_facility_record=[];
            $updated_facility_record['id']=$facility_missing_dhis2_fields["id"];
            $updated_facility_record['cphl_facility_name']=$facility_missing_dhis2_fields["cphl_facility_name"];
            $updated_facility_record['cphl_district']=$facility_missing_dhis2_fields["cphl_district"];
            $updated_facility_record['cphl_region']=$facility_missing_dhis2_fields["cphl_region"];
    
            $updated_facility_record['dhis2_facility_name']=$dhis2_facility_name;

            $cphl_district =$facility_missing_dhis2_fields["cphl_district"];
            $found_facility_record = $this->getDhis2FacilityRecord($dhis2_facility_name,$cphl_district ,$dhis2_master_facility_list);

            

            $this->comment("updated.....");
            if(sizeof($found_facility_record) >0){
                $this->comment("g.....");
                $updated_facility_record['dhis2_facility_uid'] = $found_facility_record['dhis2_facility_uid'];
                $updated_facility_record['dhis2_facility_name'] = $found_facility_record['dhis2_facility_name'];

                $updated_facility_record['dhis2_district_uid'] = $found_facility_record['dhis2_district_uid'];
                $updated_facility_record['dhis2_district_name'] = $found_facility_record['dhis2_district_name'];

                $updated_facility_record['dhis2_subcounty_uid'] = $found_facility_record['dhis2_subcounty_uid'];
                $updated_facility_record['dhis2_subcounty_name'] = $found_facility_record['dhis2_subcounty_name'];

                $updated_facility_record['dhis2_region'] = $found_facility_record['dhis2_region'];
                array_push( $final_list, $updated_facility_record);
            }else{
                \Log::info($updated_facility_record['id']);
            }
            
            $counter++;
        }
        
        //create csv_file or update the database table:facilities
        $this->generateCsvOfNewlyUpdatedFacilities($final_list);


    }

    private function generateCsvOfNewlyUpdatedFacilities($final_list){
        $filename = './docs/updated_file.csv';
        $f = fopen($filename, 'w');
        if ($f === false) {
            die('Error opening the file ' . $filename);
        }

        // write each row at a time to a file
        //first line/header
         $header=['id','cphl_facility_name','cphl_district','cphl_region',
         'dhis2_facility_uid','dhis2_facility_name','dhis2_district_uid','dhis2_district_name',
         'dhis2_subcounty_uid','dhis2_subcounty_name','dhis2_region'];
        fputcsv($f, $header);
        foreach ($final_list as $final_record) {
            $row=[
                $final_record['id'],$final_record['cphl_facility_name'],$final_record['cphl_district'],
                $final_record['cphl_region'],
                $final_record['dhis2_facility_uid'],$final_record['dhis2_facility_name'],
                $final_record['dhis2_district_uid'],$final_record['dhis2_district_name'],
                $final_record['dhis2_subcounty_uid'],$final_record['dhis2_subcounty_name'],
                $final_record['dhis2_region']
                ];
            fputcsv($f, $row);
        }

        // close the file
        fclose($f);
        $this->comment('File Generated successfully');
    }
    private function getDhis2FacilityRecord($dhis2_facility_name,$cphl_district,$dhis2_master_facility_list){
        //district,hub,cphl_facility_name,dhis2_facility_name,dhis2_facility_uid

        $found_facility_record=[];
        foreach ($dhis2_master_facility_list as $key => $dhis2_facility_record) {
            
            $dummy_district = $dhis2_facility_record['dhis2_district_name'];
            $dummy_district_array = explode(" ", $dummy_district);

            $cleaned_district_name = $dummy_district_array[0];
            $cleaned_district_name = strtolower($cleaned_district_name);
            $cphl_district = strtolower($cphl_district);

            if($cphl_district == $cleaned_district_name){
                $facility = $dhis2_facility_record['dhis2_facility_name'];
                $facility = strtolower($facility);
                if($facility == strtolower($dhis2_facility_name)){
                    $found_facility_record = $dhis2_facility_record;
                    break;
                }
                
            }
        }

        return $found_facility_record ;

    }
    private function getDistrictDhis2Facilities($cphl_district,$dhis2_master_facility_list){
        //district,hub,cphl_facility_name,dhis2_facility_name,dhis2_facility_uid
        
        $facility_list=[];
        foreach ($dhis2_master_facility_list as $key => $facility_record) {
            $cleaned_district_name = $facility_record['district'];

            $cleaned_district_name = strtolower($cleaned_district_name);
            $cphl_district = strtolower($cphl_district);

            $facility="";

            if($cphl_district == $cleaned_district_name){
                $facility = $facility_record['dhis2_facility_name'];
                array_push($facility_list, $facility);
                
            }
        }

        return $facility_list;

    }
    private function getRegionDhis2Facilities($cphl_region,$dhis2_master_facility_list){
        //district,hub,cphl_facility_name,dhis2_facility_name,dhis2_facility_uid
        
        $facility_list=[];
        foreach ($dhis2_master_facility_list as $key => $facility_record) {
            $cleaned_region_name = $facility_record['dhis2_region'];

            $cleaned_region_name = strtolower($cleaned_region_name);
            $cphl_region = strtolower($cphl_region);

            $facility="";

            if($cphl_region == $cleaned_region_name){
                $facility = $facility_record['dhis2_facility_name'];
                array_push($facility_list, $facility);
                
            }
        }

        return $facility_list;

    }
    private function getNationalDhis2Facilities($dhis2_master_facility_list){
        //district,hub,cphl_facility_name,dhis2_facility_name,dhis2_facility_uid
        
        $facility_list=[];
        foreach ($dhis2_master_facility_list as $key => $facility_record) {
                $facility = $facility_record['dhis2_facility_name'];
                array_push($facility_list, $facility);
        }

        return $facility_list;

    }
    private function getClosestMatch($cphl_facility_name, $possible_dhis2_facility_names){
        $facility_to_search_for = $cphl_facility_name;

        // array of words to check against
        $possible_dhis2_facility_names  = $possible_dhis2_facility_names;

        $closest_match="";

        $matches_close = [];
        $matched_data = [];

        $array_size = sizeof($possible_dhis2_facility_names);
        $this->comment("Array size:$array_size for ".$cphl_facility_name);

        var_dump($possible_dhis2_facility_names);
        // loop through words to find the closest
        foreach ($possible_dhis2_facility_names as $possible_dhis2_facility_name_instance) {
            $precentage_ = 0;
            similar_text($cphl_facility_name, $possible_dhis2_facility_name_instance,$precentage_);
             


             $matches_close['percentage']=$precentage_;
             $matches_close['facility_name']=$possible_dhis2_facility_name_instance;
             

             array_push($matched_data, $matches_close );
            if($precentage_ >= 70){
                $closest_match = $possible_dhis2_facility_name_instance;

            }
        }
        $closest_match = $this->getBestMatch($matched_data);
        return $closest_match;
    }
    private function getBestMatch($matched_data){
        $this->comment('started closest match ....');
        $percentage_list =[];
        foreach ($matched_data as $key => $matched_data_instance) {
           
            array_push($percentage_list, $matched_data_instance['percentage']);
        }
        $best_percentage = max($percentage_list);
        $closest_match = '';

        foreach ($matched_data as $key => $matched_data_instance) {
           if($best_percentage == $matched_data_instance['percentage'] ){
                 $closest_match = $matched_data_instance['facility_name'];
                 break;
           }
        }

        return $closest_match;
    }
    private function getClosestMatchByLevenshtein($cphl_facility_name, $possible_dhis2_facility_names){
        $facility_to_search_for = $cphl_facility_name;

        // array of words to check against
        $possible_dhis2_facility_names  = $possible_dhis2_facility_names;

        $closest_match=0;

        // no shortest distance found, yet
        $shortest = -1;

        // loop through words to find the closest
        foreach ($possible_dhis2_facility_names as $possible_dhis2_facility_name_instance) {

            // calculate the distance between the input word,
            // and the current word
            $lev = levenshtein($facility_to_search_for, $possible_dhis2_facility_name_instance);

            // check for an exact match
            if ($lev == 0) {

                // closest word is this one (exact match)
                $closest_match = $possible_dhis2_facility_name_instance;
                $shortest = 0;

                // break out of the loop; we've found an exact match
                break;
            }

            // if this distance is less than the next found shortest
            // distance, OR if a next shortest word has not yet been found
            if ($lev <= $shortest || $shortest < 0) {
                // set the closest match, and shortest distance
                $closest_match  = $possible_dhis2_facility_name_instance;
                $shortest = $lev;
            }
        }

        return $closest_match;
    }
    private function getDhis2MasterFacilityList($file_name){
        $file = fopen("./docs/dhis2_master_facility_list.csv", "r");
        $data = array();
        //loading CSV entire data
        
        while ( ! feof($file )) {

            $array_instance = fgetcsv($file);
            //print_r($array_instance);

             //dhis2_facility_uid,dhis2_facility_name,dhis2_subcounty_uid,dhis2_subcounty_name,dhis2_district_uid,dhis2_district_name,dhis2_region

                $facility['dhis2_facility_uid']=$array_instance[0];
                $facility['dhis2_facility_name']=$array_instance[1];
                $facility['dhis2_subcounty_uid']=$array_instance[2];
                $facility['dhis2_subcounty_name']=$array_instance[3];
                $facility['dhis2_district_uid']=$array_instance[4];
                $facility['dhis2_district_name']=$array_instance[5];
                $facility['dhis2_region']=$array_instance[6];
               
                
                array_push($data, $facility);
             
            
        }
        
        //remove duplicates
        $facilities = $this->unique_multidim_array($data,'dhis2_facility_uid'); 

        return $facilities;
    }
    private function getFacilitiesWithMissingDhis2Codes($file_name){

       $file = fopen($file_name, "r");
        $data = array();
        //loading CSV entire data
        
        while ( ! feof($file )) {

            $array_instance = fgetcsv($file);
            //print_r($array_instance);

             //id,cphl_facility_name,cphl_district,cphl_region

                $facility['id']=$array_instance[0];
                $facility['cphl_facility_name']=$array_instance[1];
                $facility['cphl_district']=$array_instance[2];
                $facility['cphl_region']=$array_instance[3];
           
               
                
                array_push($data, $facility);
             
            
        }
        
        //remove duplicates
        $facilities = $this->unique_multidim_array($data,'id'); 

        return $facilities;
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
   
}
