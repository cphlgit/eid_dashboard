<?php namespace EID\Models;


class HealthFacility {


public function __construct($id, $name,$district_id,$dhis2_uid,$dhis2_name) {

        $this->id =$id ;
        $this->name = $name;
        $this->district_id = $district_id;
        $this->dhis2_name = $dhis2_name;
        $this->dhis2_uid = $dhis2_uid; 
    }


}