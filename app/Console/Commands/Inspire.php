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
        $_text_percentage = 0;
        $first="Mantoroba Health Centre II";
        $data=["Rwimi Prison HC III","Sanyu Clinic (Rwimi TC)","Shifa Health Centre HC II","St. Francis Medical Centre","St. John's Medical Centre","Trivest Medical Centre HC II","Yerya HC III","Mantoroba HC II"];
        foreach ($data as $key => $second) {
           similar_text($first, $second,$_text_percentage);
           $this->comment("First: $first, Second: $second, Percentage: $_text_percentage");
        }
        
       // $this->updateDhis2Fields();
    }
   
    private function updateDhis2Fields(){
        //load csv master_facility_list
        $file_name = "./docs/dhis2_master_facility_list.csv";
        $dhis2_master_facility_list=$this->getDhis2MasterFacilityList($file_name);

        //load sites_missing_dhis2_fields
        $facilities_missing_dhis2_fields = $this->getFacilitiesWithMissingDhis2Codes();


        //match sites
        $final_list=[];
        foreach ($facilities_missing_dhis2_fields as $key => $facility_missing_dhis2_fields) {
            $district_of_facility = $facility_missing_dhis2_fields->district;


            $dhis2_facilities_in_district = $this->getDistrictDhis2Facilities($district_of_facility,$dhis2_master_facility_list);

            $cphl_facility_name = $facility_missing_dhis2_fields->facility;
            $dhis2_facility_name =$this->getClosestMatch($cphl_facility_name, $dhis2_facilities_in_district);

            $updated_facility_record=[];
            $updated_facility_record['id']=$facility_missing_dhis2_fields->id;
            $updated_facility_record['cphl_facility_name']=$facility_missing_dhis2_fields->facility;
            $updated_facility_record['cphl_district']=$facility_missing_dhis2_fields->district;

    
            $updated_facility_record['dhis2_facility_name']=$dhis2_facility_name;

            $cphl_district =$facility_missing_dhis2_fields->district;
            $found_facility_record = $this->getDhis2FacilityRecord($dhis2_facility_name,$cphl_district ,$dhis2_master_facility_list);

            

            var_dump($found_facility_record);
            $this->comment("updated.....");
            var_dump($updated_facility_record);
            if(sizeof($found_facility_record) >0){
                $this->comment("g.....");
                $updated_facility_record['dhis2_facility_uid']=$found_facility_record['dhis2_facility_uid'];

                array_push( $final_list, $updated_facility_record);
            }else{
                \Log::info($updated_facility_record['id']);
            }
            
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
         $header=['id','cphl_facility_name','cphl_district','dhis2_facility_uid',
         'dhis2_facility_name'];
        fputcsv($f, $header);
        foreach ($final_list as $final_record) {
            $row=[$final_record['id'],$final_record['cphl_facility_name'],$final_record['cphl_district'],$final_record['dhis2_facility_uid'],
                    $final_record['dhis2_facility_name']
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
            
            $cleaned_district_name = $dhis2_facility_record['district'];

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
    
    private function getClosestMatch($cphl_facility_name, $possible_dhis2_facility_names){
        $facility_to_search_for = $cphl_facility_name;

        // array of words to check against
        $possible_dhis2_facility_names  = $possible_dhis2_facility_names;

      

        // loop through words to find the closest
        foreach ($possible_dhis2_facility_names as $possible_dhis2_facility_name_instance) {
            $precentage_ = 0;
            similar_text($cphl_facility_name, $possible_dhis2_facility_name_instance,$precentage_);

        }

        return $closest_match;
    }
    private function _getClosestMatch($cphl_facility_name, $possible_dhis2_facility_names){
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

             
                $facility['district']=$array_instance[0];
                $facility['hub']=$array_instance[1];
                $facility['cphl_facility_name']=$array_instance[2];
                $facility['dhis2_facility_name']=$array_instance[3];
                $facility['dhis2_facility_uid']=$array_instance[4];
               
                
                array_push($data, $facility);
             
            
        }
        
        //remove duplicates
        $facilities = $this->unique_multidim_array($data,'dhis2_facility_uid'); 

        return $facilities;
    }
    private function getFacilitiesWithMissingDhis2Codes(){

        $sql = "SELECT f.id,f.facility,d.name as district FROM facilities f INNER JOIN districts d on f.districtID = d.id 
        where f.dhis2_uid is NULL";
        $facilities =  \DB::connection('live_db')->select($sql);
        
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
