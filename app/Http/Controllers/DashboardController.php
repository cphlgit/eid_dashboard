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
		$regions=Region::regionsArr();
		$districts=District::districtsArr();
		$facility_levels=FacilityLevel::facilityLevelsArr();
		$count_positives=Sample::countPositives($time);
		$av_positivity=Sample::avPositivity($time);
		$count_positives_arr=Sample::countPositives2($time);
		$av_positivity_arr=Sample::avPositivity2($time);

		return view('db/show',compact(
			"time",
			"regions",
			"districts",
			"facility_levels",
			"count_positives",
			"av_positivity",
			"count_positives_arr",
			"av_positivity_arr"
			));
	}
	

}
