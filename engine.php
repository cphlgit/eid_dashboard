<?php
echo "started at ".date("H:i:s")."\n";
$link=mysql_connect('localhost',"root","chai8910");
if(!$link){
	die('connection to server failed:' . mysql_error());
}

mysql_select_db("etl",$link) or die(mysql_error());

/*function districts(){
	$ret=[];
	$res=mysql_query("SELECT id,district from districts");
	while ($row=mysql_fetch_array($res)){
		extract($row);
		$ret[]=['id'=>$id,'name'=>$district];
	}
	return $ret;
}

function hubs(){
	$ret=[];
	$res=mysql_query("SELECT id,hub from hubs");
	while ($row=mysql_fetch_array($res)){
		extract($row);
		$ret[]=['id'=>$id,'name'=>$hub];
	}
	return $ret;
}*/

function regions(){
	$ret=[];
	$res=mysql_query("SELECT id,region FROM regions");
	while ($row=mysql_fetch_array($res)){
		extract($row);
		$ret[$id]=$region;
	}
	return $ret;
}

function districts(){
	$ret=[];
	$res=mysql_query("SELECT id,district from districts");
	while ($row=mysql_fetch_array($res)){
		extract($row);
		$ret[$id]=$district;
	}
	return $ret;
}

function hubs(){
	$ret=[];
	$res=mysql_query("SELECT id,hub from hubs");
	while ($row=mysql_fetch_array($res)){
		extract($row);
		$ret[$id]=$hub;
	}
	return $ret;
}

function levels(){
	$ret=[];
	$res=mysql_query("SELECT id,facility_level FROM facility_levels");
	while ($row=mysql_fetch_array($res)){
		extract($row);
		$ret[$id]=$facility_level;
	}
	return $ret;
}

function facilities(){
	$ret=[];
	$res=mysql_query("SELECT id,facility,districtID,hubID from facilities where facility!='' LIMIT 500");
	while ($row=mysql_fetch_array($res)){
		extract($row);
		$ret[$id]=['id'=>$id,'name'=>$facility,'district_id'=>$districtID,'hub_id'=>$hubID];
	}
	return $ret;
}

function getData($year,$cond=1){
	$sql="SELECT count(s.id) AS num,MONTH(s.date_results_entered),facility_id AS mth FROM dbs_samples AS s 
		  LEFT JOIN batches AS b ON s.batch_id=b.id
		  LEFT JOIN facilities AS f ON b.facility_id=f.id
		  LEFT JOIN districts AS d ON f.districtID=d.id
		  WHERE YEAR(s.date_data_entered)=$year AND s.PCR_test_requested='YES'  AND $cond
		  GROUP BY facility_id,mth";
	return mysql_query($sql);
}


function makeDataJSON($year){
	$data_arr=[];
	$smpls_rvd_res=getData($year,"1","");

	While($row=mysql_fetch_array($smpls_rvd_res)){
		extract($row);
		$data_arr[]=["facility_id"=>$facility_id,"year_month"=>$year."_$mth", "samples_received"=>$num];
	}

}


$data=[];
$data['districts']=districts();
$data['hubs']=hubs();
$data['facilities']=facilities();
$data['age_group']=[1=>" < 5",2=>" 5 - 9",3=>" 10 - 18",4=>"19 - 25","26+"];


$years=[2014,2015];
$results=[];
$x=1;
foreach ($years as $year) {
	$month=1;
	while ($month <= 12) {
		foreach ($data['facilities'] as $facility) {
			foreach ($data['age_group'] as $ag_k=>$ag) {
				$samples_received=rand(10,20);
				$dbs_samples=rand(1,$samples_received-5);
				$total_results=rand(10,$samples_received-1);
				$valid_results=rand(0,$total_results);
				$rejected_samples=$samples_received-$total_results;
				$suppressed=rand(0,$valid_results);

				$sqr=rand(0,$rejected_samples);
				$er=rand(0,($rejected_samples-$sqr));
				$ifr=$rejected_samples-($sqr+$er);
				$sqr=$sqr>0?$sqr:0;
				$er=$er>0?$er:0;
				$ifr=$ifr>0?$ifr:0;

				$cd4_less_than_500=rand(1,2);
				$pmtct_option_b_plus=rand(1,2);
				$children_under_15=rand(1,2);
				$other_treatment=rand(1,2);
				$treatment_blank_on_form=$samples_received-($cd4_less_than_500+$pmtct_option_b_plus+$children_under_15+$other_treatment);

				$results[]=[
					'month'=>$month,
					'year'=>$year,
					'facility_id'=>$facility['id'],
					//'age_group_id'=>$ag['id'],
					'age_group'=>$ag_k,

					'samples_received'=>$samples_received,
					'dbs_samples'=>$dbs_samples,
					'total_results'=>$total_results,
					'valid_results'=>$valid_results,
					'rejected_samples'=>$rejected_samples,
					'suppressed'=>$suppressed,

					'sample_quality_rejections'=>$sqr,
					'eligibility_rejections'=>$er,
					'incomplete_form_rejections'=>$ifr,

					'cd4_less_than_500'=>$cd4_less_than_500,
					'pmtct_option_b_plus'=>$pmtct_option_b_plus,
					'children_under_15'=>$children_under_15,
					'other_treatment'=>$other_treatment,
					'treatment_blank_on_form'=>$treatment_blank_on_form
					];
				echo "$x record in results\n";
				$x++;
			}
		}
		$month++;		
	}
}

$data['results']=$results;
echo file_put_contents("public/json/data.json", json_encode($data));

echo "\n".count($results)." rows found in results";

echo "finished at ".date("H:i:s")."\n";

//var_dump($data['facilities']);


/*

from_year=2013,from_month=2
to_year=2015,to_month=5

i=from_year;
duration=[];
while(i<=to_year){
	stat=(i==from_year)?from_month:1;
	end=(i==to_year)?to_month:12;
	j=stat;
	while(j<=end){
		duration=[i."-".j];
		j++;	
	}	
	i++;	
}

*/
?>