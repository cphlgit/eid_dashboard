<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
     <title>@yield('meta-title', 'EID LIMS')</title>
    <link href="{{ asset('/css/app.css') }}" rel="stylesheet">
    <link href="{{ asset('/css/jquery.dataTables.css') }}" rel="stylesheet">    
    <link href="{{ asset('/css/jquery-ui.css')}}" rel="stylesheet" >


    <link rel="stylesheet" type="text/css" href="{{ asset('/css/demo.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('/css/tabs.css') }} " />
    <link rel="stylesheet" type="text/css" href="{{ asset('/css/tabstyles.css') }}" />

        <link href="{{ asset('/css/eid.css') }}" rel="stylesheet">

    <script src="{{ asset('/js/modernizr.custom.js') }}"></script>


    <script src="{{ asset('/js/general.js') }}" type="text/javascript"></script>
    <script src="{{ asset('/js/jquery-2.1.3.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('/js/jquery.dataTables.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('/js/jquery-ui.js')}}" type="text/javascript"></script>


    <script src="{{ asset('/js/Chart.js')}}" type="text/javascript"></script>
    <script src="{{ asset('/js/angular.min.js')}}" type="text/javascript"></script>
    <script src="{{ asset('/js/angular-route.js')}}" type="text/javascript"></script>

    <script src="{{ asset('/js/angular-datatables.min.js') }}" type="text/javascript"></script>

    
</head>

<body ng-app="dashboard" ng-controller="DashController">
<div class="navbar-custom navbar navbar-inverse navbar-fixed-top" role="navigation">
    <div class="container">
        <div class="navbar-header">
            <a class="navbar-brand" href="/"> <span class='glyphicon glyphicon-home'></span> EID LIMS</a>
        </div>
        <div class="collapse navbar-collapse">
            <ul class="nav navbar-nav">
                    <li id='l1' class='active'>{!! link_to("/","DASH BOARD",['class'=>'hdr']) !!}</li> 
                    <li id='l2'>{!! link_to("#","REPORTS",['class'=>'hdrs']) !!}</li>             
            </ul>
        </div>
    </div>
</div> 

<div class='container'>
    <br>
    <?php //if(!isset($filter_val)) $filter_val="National Metrics, ".$time." thus far" ?>
     <label class='hdr hdr-grey'> FILTERS:</label> 
     <label class='hdr val-grey'>
        <label class='filter-val' ng-model='time' ng-init='time={!! $time !!}'>YEAR: <% time %></label> 
        <label class='filter-val' ng-model='region_label' ng-init="region_label='~'"><% region_label %></label> 
        <label class='filter-val' ng-model='district_label' ng-init="district_label='~'"><% district_label %></label> 
        <label class='filter-val' ng-model='care_level_label' ng-init="care_level_label='~'"><% care_level_label %></label> 
    </label><br>

     <table border='1' cellpadding='0' cellspacing='0' class='filter-tb'>
        <tr>
            <td width='25%'>{!! Form::select('time',[''=>'YEAR']+MyHTML::years(2010),$time,["id"=>"time_fltr"]) !!}</td>
            
            <td width='25%' id='dist_elmt'>
                <select ng-model="region" ng-init="region='all'" ng-change="filter('region')">
                    <option value="all">REGIONS</option>
                    <option ng-repeat="(reg_nr,reg_name) in regions_slct" value="<% reg_nr %>"><% reg_name %></option>
                </select>
            </td>
            <td width='25%' id='dist_elmt'>
                <select ng-model="district" ng-init="district='all'" ng-change="filter('district')">
                    <option value="all">DISTRICTS</option>
                    <option ng-repeat="(dist_nr,dist_name) in districts_slct" value="<% dist_nr %>"><% dist_name %></option>
                </select>
            </td>

             <td width='25%' id='dist_elmt'>
                <select ng-model="care_level" ng-init="care_level='all'" ng-change="filter('level')">
                    <option selected="selected" value="all">CARE LEVELS</option>
                    <option ng-repeat="(level_nr,level_name) in facility_levels_slct" value="<% level_nr %>"><% level_name %></option>
                </select>
            </td>
        </tr>
     </table>
     <br>
     <label class='hdr hdr-grey'> KEY METRICS</label>
     <br>
     <div class="tabss tabs-style-flip">
        <nav>
            <ul>
                <li id='tb_hd1'>
                    <a href="#tab1" id='tb_lnk1' ng-click="setCountPos()">
                        <span class="num" ng-model="count_positives" ng-init="count_positives={!! $count_positives !!}" >
                            <% count_positives|number %>
                        </span>
                        <span class="desc">hiv positive infants</span>
                    </a>
                </li>
                <li id='tb_hd2'>
                    <a href="#tab2" id='tb_lnk2'  ng-click="avUptakeRate()">
                        <span class="num" ng-model="total_samples" ng-init="total_samples={!! $total_samples !!}">
                            <% total_samples|number %>
                        </span>
                        <span class="desc">uptake</span>
                    </a>
                </li>
                <li id='tb_hd3'>
                    <a href="#tab3" id='tb_lnk3' ng-click="avInitRate()">
                        <span class="num" ng-model="av_initiation_rate" ng-init="av_initiation_rate={!! $av_initiation_rate !!}">
                            <% av_initiation_rate|number:1 %>%
                        </span>
                        <span class="desc">average initiation rate</span>
                    </a>
                </li>
                <li id='tb_hd4'>
                    <a href="#tab4" id='tb_lnk4' ng-click="avPositivity()">
                        <span class="num" ng-model="av_positivity" ng-init="av_positivity={!! $av_positivity !!}">
                            <% av_positivity|number:1 %>%
                        </span>
                        <span class="desc">average positivity rate</span>
                    </a>
                </li>
            </ul>
        </nav>
        <?php $key_nat="<label class='sm_box national'>&nbsp;</label>&nbsp;National"   ?>

        <div class="content-wrap">
            <section id="tab1">
                <div class="row">
                    <div class="col-lg-6">
                        {!!$key_nat !!}&nbsp;&nbsp;&nbsp;
                        <label class='sm_box hiv-positive-numbers'>&nbsp;</label>&nbsp;Selection
                        <br><br>
                        <canvas id="hiv_postive_infants" class='db-charts'></canvas> 
                    </div>
                   
                    <div class="col-lg-6 facilties-sect facilities-sect-hiv-pstv" >
                        <table datatable="ng" class="row-border hover table table-bordered table-condensed table-striped">
                            <thead>
                                <tr>
                                    <th>Facility</th>
                                    <th>Absolute Positives</th>
                                    <th>Total Results</th>

                                </tr>
                            </thead>
                            <tbody>
                                <tr ng-repeat="f in facility_numbers | filter:compare('abs_positives','ge',1)">
                                    <td width='80%'><% f.facility_name %></td>
                                    <td width='10%'><% f.abs_positives %></td>
                                    <td width='10%'><% f.total_results %></td>
                                </tr>                        
                             </tbody>
                         </table>
                    </div>

                </div>
            </section>

            <section id="tab2">
                <div class="row">
                    <div class="col-lg-6">
                        {!!$key_nat !!}&nbsp;&nbsp;&nbsp;
                        <label class='sm_box uptake'>&nbsp;</label>&nbsp;Selection<br><br>
                        <canvas id="average_uptake_rate" class='db-charts'></canvas>
                    </div>
                   
                    <div class="col-lg-6 facilties-sect facilities-sect-uptake" >
                        <table datatable="ng" class="row-border hover table table-bordered table-condensed table-striped">
                            <thead>
                                <tr>
                                    <th width='90%'>Facility</th>                                   
                                    <th width='10%'>Total </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr ng-repeat="f in facility_numbers | filter:compare('facility_name','ne',null)">
                                    <td><% f.facility_name %></td>
                                    <td><% f.total_results %></td>
                                </tr>                        
                             </tbody>
                         </table>
                    </div>
                </div> 
            </section>
            <section id="tab3">
                <div class="row">
                    <div class="col-lg-6">
                        {!!$key_nat !!}&nbsp;&nbsp;&nbsp;
                        <label class='sm_box init-rate'>&nbsp;</label>&nbsp;Selection<br><br>
                        <canvas id="average_init_rate" class='db-charts'></canvas> 
                    </div>
                   
                    <div class="col-lg-6 facilties-sect facilities-sect-init-rates" >
                        <table datatable="ng" class="row-border hover table table-bordered table-condensed table-striped">
                            <thead>
                                <tr>
                                    <th width='80%'>Facility</th>
                                    <th width='10%'>Initiation Rate (%)</th>
                                    <th width='10%'>Absolute Positives</th>                                    
                                </tr>
                            </thead>
                            <tbody>
                                <tr ng-repeat="f in facility_numbers | filter:compare('initiation_rate','gt',0)">
                                    <td><% f.facility_name %></td>
                                    <td><% f.initiation_rate %></td>
                                    <td><% f.abs_positives %></td>
                                </tr>                        
                             </tbody>
                         </table>
                    </div>
                </div>                
            </section>
            <section id="tab4">
                <div class="row">
                    <div class="col-lg-6">
                        {!!$key_nat !!}&nbsp;&nbsp;&nbsp;
                        <label class='sm_box hiv-positive-average'>&nbsp;</label>&nbsp;Selection<br><br>
                        <canvas id="av_positivity_canvas" class='db-charts'></canvas>
                    </div>
                   
                    <div class="col-lg-6 facilties-sect facilities-sect-pstv-rates" >
                        <table datatable="ng" class="row-border hover table table-bordered table-condensed table-striped">
                            <thead>
                                <tr>
                                    <th width='70%'>Facility</th>
                                    <th width='10%'>Positivity Rate (%)</th>
                                    <th width='10%'>Absolute Positives</th>                                    
                                    <th width='10%'>Total Results</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr ng-repeat="f in facility_numbers | filter:compare('facility_name','ne',null)">
                                    <td><% f.facility_name %></td>
                                    <td><% f.positivity_rate %></td>
                                    <td><% f.abs_positives %></td>
                                    <td><% f.total_results %></td>
                                </tr>                        
                             </tbody>
                         </table>
                    </div>

                </div>                
            </section>
        </div><!-- /content -->
    </div><!-- /tabs -->
    
    <br>
    <label class='hdr hdr-grey'> ADDITIONAL METRICS</label>
    <div class='addition-metrics'>
       <div class='row'>
        <div class='col-sm-2'>
            <font class='addition-metrics figure' ng-init="first_pcr_total={!! $first_pcr_total !!}" ng-model='first_pcr_total'><% first_pcr_total|number %></font><br>
            <font class='addition-metrics desc'>TOTAL 1ST PCR</font>            
        </div>
        <div class='col-sm-2'>
            <font class='addition-metrics figure' ng-init="sec_pcr_total={!! $sec_pcr_total !!}" ng-model='sec_pcr_total'><% sec_pcr_total|number %></font><br>
            <font class='addition-metrics desc'>TOTAL 2ND PCR</font>            
        </div>       
        <div class='col-sm-2'>
            <font class='addition-metrics figure' ng-init="first_pcr_median_age={!! $first_pcr_median_age !!}" ng-model="first_pcr_median_age">
                <% first_pcr_median_age|number:1 %>
            </font><br>
            <font class='addition-metrics desc'>MEDIAN MONTHS 1ST PCR</font>            
        </div>
        <div class='col-sm-2'>
            <font class='addition-metrics figure' ng-init="sec_pcr_median_age={!! $sec_pcr_median_age !!}" ng-model="sec_pcr_median_age">
                <% sec_pcr_median_age|number:1 %>
            </font><br>
            <font class='addition-metrics desc'>MEDIAN MONTHS 2ND PCR</font>            
        </div>
        <div class='col-sm-2'>
            <font class='addition-metrics figure' ng-init="total_initiated={!! $total_initiated !!}" ng-model="total_initiated">
                <% total_initiated|number %>
            </font><br>
            <font class='addition-metrics desc'>TOTAL ART INITIATED CHILDREN</font>            
        </div>
        <div class='col-sm-2'>
            <font class='addition-metrics figure' ng-init="total_samples={!! $total_samples !!}" ng-model='total_samples'>
                <% total_samples|number %>
            </font><br>
            <font class='addition-metrics desc'>TOTAL TESTS</font>            
        </div>
       </div>
    </div>
    <br>
</div>
<script src=" {{ asset('js/cbpFWTabs.js') }} "></script>
        <script>
            (function() {

                [].slice.call( document.querySelectorAll( '.tabss' ) ).forEach( function( el ) {
                    new CBPFWTabs( el );
                });

            })();
        </script>


</body>
<?php
/*
national -- #6D6D6D
blue -- #357BB8
green-- #5EA361
yellow -- #F5A623
purple -- #9F82D1

*/
$chart_stuff=[
    "fillColor"=>"rgba(0,0,0, 0)",
    "strokeColor"=>"#6D6D6D",
    "pointColor"=>"#6D6D6D",
    "pointStrokeColor"=>"#fff",
    "pointHighlightFill"=>"#fff",
    "pointHighlightStroke"=> "#6D6D6D"
    ];

$chart_stuff2=[
    "fillColor"=>"#FFFFCC",
    "strokeColor"=>"#FFCC99",
    "pointColor"=>"#FFCC99",
    "pointStrokeColor"=>"#fff",
    "pointHighlightFill"=>"#fff",
    "pointHighlightStroke"=> "#FFCC99"
    ];


$st2= ["Jan"=>2, "Feb"=>2, "Mar"=>3, "Apr"=>6, "May"=>3, "Jun"=>6, "Jul"=>6,"Aug"=>6,"Sept"=>6,"Oct"=>2,"Nov"=>6,"Dec"=>2];
?>

<?php
$count_positives_arr=array_values($count_positives_arr);
$av_positivity_arr=array_values($av_positivity_arr);
$nums_by_months=array_values($nums_by_months);
$av_initiation_rate_months=array_values($av_initiation_rate_months);



?>

<script type="text/javascript">
var months=["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul","Aug","Sept","Oct","Nov","Dec"];
var months_init={"1":0, "2":0, "3":0, "4":0, "5":0, "6":0, "7":0,"8":0,"9":0,"10":0,"11":0,"12":0};

var nice_counts=<?php echo json_encode($nice_counts) ?>;
var nice_counts_positives=<?php echo json_encode($nice_counts_positives) ?>;
var nice_counts_art_inits=<?php echo json_encode($nice_counts_art_inits) ?>;

var reg_districts=<?php echo json_encode($reg_districts) ?>;
var dist_n_reg_ids=<?php echo json_encode($dist_n_reg_ids) ?>;
var districts_json=<?php echo json_encode($districts) ?>;
var regions_json=<?php echo json_encode($regions) ?>;
var facility_levels_json=<?php echo json_encode($facility_levels) ?>;

var count_positives_json=<?php echo json_encode($chart_stuff + ["data"=>$count_positives_arr]) ?>;

//var count_positives_json2=<?php echo json_encode($chart_stuff2 + ["data"=>$st2]) ?>;
var av_positivity_json=<?php echo json_encode($chart_stuff + ["data"=>$av_positivity_arr]) ?>;

var nums_json=<?php echo json_encode($chart_stuff+["data"=>$nums_by_months]) ?>;

var first_pcr_ttl_grped=<?php echo json_encode($first_pcr_ttl_grped) ?>;
var sec_pcr_ttl_grped=<?php echo json_encode($sec_pcr_ttl_grped) ?>;
var samples_ttl_grped=<?php echo json_encode($samples_ttl_grped) ?>;
var initiated_ttl_grped=<?php echo json_encode($initiated_ttl_grped) ?>;

var first_pcr_total_init=<?php echo $first_pcr_total ?>;
var sec_pcr_total_init=<?php echo $sec_pcr_total ?>;
var first_pcr_median_age_init=<?php echo $first_pcr_median_age ?>;
var sec_pcr_median_age_init=<?php echo $sec_pcr_median_age ?>;
var total_initiated_init=<?php echo $total_initiated ?>;
var total_samples_init=<?php echo $total_samples ?>;

var av_initiation_rate_months_json=<?php echo json_encode($chart_stuff+["data"=>$av_initiation_rate_months]) ?>;


$(document).ready( function(){
    var ctx = $("#hiv_postive_infants").get(0).getContext("2d");
   // This will get the first returned node in the jQuery collection. 
   var data = {
        labels: months,
        datasets: [count_positives_json] 
    };
    var myLineChart = new Chart(ctx).Line(data);
});

/*$(document).ready(function() {
    setTimeout($('#tab_id').DataTable(),3000);
    
  });*/

$("#time_fltr").change(function(){
    return window.location.assign("/"+this.value);
});



//angular stuff
var app=angular.module('dashboard', ['datatables'], function($interpolateProvider) {
        $interpolateProvider.startSymbol('<%');
        $interpolateProvider.endSymbol('%>');
    });
var ctrllers={};

ctrllers.DashController=function($scope,$timeout){

    $scope.count_positives_init=<?php echo $count_positives ?>;
    $scope.total_samples_init=<?php echo $total_samples ?>;
    $scope.av_initiation_rate_init=<?php echo $av_initiation_rate ?>;
    $scope.av_positivity_init=<?php echo $av_positivity ?>;
    $scope.total_initiated_init=<?php echo $total_initiated ?>
    //for filtering by region

    $scope.regions_slct=<?php echo json_encode($regions) ?>;
    $scope.districts_slct=<?php echo json_encode($districts) ?>;
    $scope.facility_levels_slct=<?php echo json_encode($facility_levels) ?>;

    $scope.facility_numbers=<?php echo json_encode($facility_numbers) ?>;
    $scope.facility_numbers_init=<?php echo json_encode($facility_numbers) ?>;


    $scope.compare = function(prop,comparator, val){
        return function(item){
            if(comparator=='eq'){
                return item[prop] == val;
            }else if (comparator=='ne'){
               return item[prop] != val;
            }else if (comparator=='gt'){
               return item[prop] > val;
            }else if (comparator=='lt'){
               return item[prop] < val;
            }else if (comparator=='ge'){
               return (item[prop] > val)||(item[prop] == val);
            }else if (comparator=='le'){
               return (item[prop] < val)||(item[prop] == val);
            }else{
                return false;
            }
        }
    }

    $scope.facility_filter=function(){
        if($scope.district!="all"){
            if($scope.care_level!="all"){
                $scope.facility_numbers=$scope.filteredfcltys({"district_id":$scope.district,"level_id":$scope.care_level});
            }else{
                $scope.facility_numbers=$scope.filteredfcltys({"district_id":$scope.district});
            }
        }else if ($scope.region!="all"){
            if($scope.care_level!="all"){
                $scope.facility_numbers=$scope.filteredfcltys({"region_id":$scope.region,"level_id":$scope.care_level});
            }else{
                $scope.facility_numbers=$scope.filteredfcltys({"region_id":$scope.region});
            }
        }else if($scope.care_level!="all"){
            $scope.facility_numbers=$scope.filteredfcltys({"level_id":$scope.care_level});
        }else{
            $scope.facility_numbers=$scope.facility_numbers_init;    
        }       
    };

    $scope.filteredfcltys=function(options){
        var ret={};
        for (var i in $scope.facility_numbers_init){
            var arr=$scope.facility_numbers_init[i];
            var no_match=0;
            for(var j in options){
                if((options[j] != arr[j])){
                    no_match=1;
                }
            }            
            if(no_match==0){
                ret[i]=arr;
            }
        };
        return ret;
    };

    $scope.filter=function(filterer){
        if(filterer=='region'){
            $scope.district="all";
            if($scope.region=="all"){
                $scope.districts_slct=districts_json;
            }else{
               $scope.districts_slct=reg_districts[$scope.region]; 
           }            
        }

        $scope.setCountPos(filterer);
        $scope.avUptakeRate(filterer);
        $scope.avInitRate(filterer);
        $scope.avPositivity(filterer);
        $scope.setAdditionalMetrics(filterer);

        $scope.region_label=$scope.region!="all"?"Region: "+regions_json[$scope.region]:"~";
        $scope.district_label=$scope.district!="all"?"District: "+districts_json[$scope.district]:"~";
        $scope.care_level_label=$scope.care_level!="all"?"Care Level: "+facility_levels_json[$scope.care_level]:"~"; 
        
        $scope.facility_filter();
        
    };

    $scope.setAdditionalMetrics=function(filterer){
        var first_pcr_ttl=0;
        var sec_pcr_ttl=0;
        var ttl_smpls=0;
        var ttl_init=0;
        if($scope.district!="all"){
           var reg_id=dist_n_reg_ids[$scope.district];
            if($scope.care_level!="all"){
                first_pcr_ttl=first_pcr_ttl_grped[$scope.care_level][reg_id][$scope.district]||0; 
                sec_pcr_ttl=sec_pcr_ttl_grped[$scope.care_level][reg_id][$scope.district]||0; 
                ttl_smpls=samples_ttl_grped[$scope.care_level][reg_id][$scope.district]||0; 
                ttl_init=initiated_ttl_grped[$scope.care_level][reg_id][$scope.district]||0; 
            }else{
                for(var lvl_id in first_pcr_ttl_grped){
                    first_pcr_ttl+=Number(first_pcr_ttl_grped[lvl_id][reg_id][$scope.district])||0; 
                    sec_pcr_ttl+=Number(sec_pcr_ttl_grped[lvl_id][reg_id][$scope.district])||0; 
                    ttl_smpls+=Number(samples_ttl_grped[lvl_id][reg_id][$scope.district])||0; 
                    ttl_init+=Number(initiated_ttl_grped[lvl_id][reg_id][$scope.district])||0;                    
                }

            }
        }else if ($scope.region!="all"){
            if($scope.care_level!="all"){
                var res_data=first_pcr_ttl_grped[$scope.care_level][$scope.region];
                for(var dist_id in res_data){
                    first_pcr_ttl+=Number(res_data[dist_id])||0; 
                    sec_pcr_ttl+=Number(sec_pcr_ttl_grped[$scope.care_level][$scope.region][dist_id])||0; 
                    ttl_smpls+=Number(samples_ttl_grped[$scope.care_level][$scope.region][dist_id])||0; 
                    ttl_init+=Number(initiated_ttl_grped[$scope.care_level][$scope.region][dist_id])||0; 
                }
            }else{
                for(var lvl_id in first_pcr_ttl_grped){
                    var res_data=first_pcr_ttl_grped[lvl_id][$scope.region];
                    for(var dist_id in res_data){
                        first_pcr_ttl+=Number(res_data[dist_id])||0;
                        sec_pcr_ttl+=Number(sec_pcr_ttl_grped[lvl_id][$scope.region][dist_id])||0; 
                        ttl_smpls+=Number(samples_ttl_grped[lvl_id][$scope.region][dist_id])||0; 
                        ttl_init+=Number(initiated_ttl_grped[lvl_id][$scope.region][dist_id])||0;                        
                    }
                }

            }
        }else if($scope.care_level!="all"){
            var level_data=first_pcr_ttl_grped[$scope.care_level]; 
            for(var reg_id in level_data){
                var reg_arr=level_data[reg_id];
                for(var dist_id in reg_arr){
                    first_pcr_ttl+=Number(reg_arr[dist_id])||0; 
                    sec_pcr_ttl+=Number(sec_pcr_ttl_grped[$scope.care_level][reg_id][dist_id])||0; 
                    ttl_smpls+=Number(samples_ttl_grped[$scope.care_level][reg_id][dist_id])||0; 
                    ttl_init+=Number(initiated_ttl_grped[$scope.care_level][reg_id][dist_id])||0; 
                }
            }
        }else{
            first_pcr_ttl=first_pcr_total_init;
            sec_pcr_ttl=sec_pcr_total_init;
            ttl_smpls=total_samples_init;
            ttl_init=total_initiated_init;
        }

        $scope.first_pcr_total=first_pcr_ttl;
        $scope.sec_pcr_total=sec_pcr_ttl;
        $scope.total_samples=ttl_smpls;
        $scope.total_initiated=ttl_init;
    }

    $scope.setCountPos=function(filterer){
        var filtered_data=months_init;
        var pos_num=0;
        var filtered_data={"1":0, "2":0, "3":0, "4":0, "5":0, "6":0, "7":0,"8":0,"9":0,"10":0,"11":0,"12":0};

        if($scope.district!="all"){
            var reg_id=dist_n_reg_ids[$scope.district];
            if($scope.care_level!="all"){
               var res_data=nice_counts_positives[$scope.care_level][reg_id][$scope.district]; 
               for(var i in res_data){
                var val=Number(res_data[i]);
                pos_num+=val;
                filtered_data[i]=filtered_data[i]+val;
               }
            }else{
                console.log("right there ... reg id is:"+reg_id);
                for(var lvl_id in nice_counts_positives){
                    var res_data=nice_counts_positives[lvl_id][reg_id][$scope.district];  
                    //console.log("right there ...level is "+lvl_id+" reg id is:"+reg_id+"district is "+$scope.district);
                    var reg_data={};               
                    for(var i in res_data){
                        var val=Number(res_data[i]);
                        pos_num+=val;
                        filtered_data[i]=filtered_data[i]+val;
                    }
                }

            }

        }else if ($scope.region!="all"){
            if($scope.care_level!="all"){
                var res_data=nice_counts_positives[$scope.care_level][$scope.region];
                for(var dist_id in res_data){
                    var dist_arr=res_data[dist_id];
                    for(var i in dist_arr){
                       var val=Number(dist_arr[i]);
                       pos_num+=val;
                       filtered_data[i]=filtered_data[i]+val;
                    }
                }
            }else{
                for(var lvl_id in nice_counts_positives){
                    var res_data=nice_counts_positives[lvl_id][$scope.region];
                    for(var dist_id in res_data){
                        var dist_arr=res_data[dist_id];
                        for(var i in dist_arr){
                            var val=Number(dist_arr[i]);
                            pos_num+=val;
                            filtered_data[i]=filtered_data[i]+val;
                        }
                    }
                }

            }
        }else if($scope.care_level!="all"){
            var level_data=nice_counts_positives[$scope.care_level]; 
            for(var reg_id in level_data){
                var reg_arr=level_data[reg_id];
                for(var dist_id in reg_arr){
                    var dist_arr=reg_arr[dist_id];
                    for(var i in dist_arr){
                        var val=Number(dist_arr[i]);
                        pos_num+=val;
                        filtered_data[i]=filtered_data[i]+val;
                    }
                }
            }
        }else{
            pos_num=$scope.count_positives_init;
            filtered_data={};
        }
        $scope.count_positives=pos_num;
        
        $timeout(function(){
            if($("#tb_hd1").hasClass('tab-current')){
                var ctx = $("#hiv_postive_infants").get(0).getContext("2d");
                var data = {
                    labels: months,datasets: [
                    count_positives_json,
                    {
                        "fillColor":"rgba(0, 0, 0, 0)",
                        "strokeColor":"#357BB8",
                        "pointColor":"#357BB8",
                        "pointStrokeColor":"#fff",
                        "pointHighlightFill":"#fff",
                        "pointHighlightStroke":"#357BB8",
                        "data":filtered_data
                    }] 
                };
                var myLineChart = new Chart(ctx).Line(data);
            }

        },1);
    };

    $scope.avUptakeRate=function(filterer){
        var count_num=0;
        var filtered_data={"1":0, "2":0, "3":0, "4":0, "5":0, "6":0, "7":0,"8":0,"9":0,"10":0,"11":0,"12":0};

        if($scope.district!="all"){
            var reg_id=dist_n_reg_ids[$scope.district];
            if($scope.care_level!="all"){
               var res_data=nice_counts[$scope.care_level][reg_id][$scope.district]; 
               for(var i in res_data){
                var val=Number(res_data[i]);
                count_num+=val;
                filtered_data[i]=filtered_data[i]+val;
               }
            }else{
                console.log("right there ... reg id is:"+reg_id);
                for(var lvl_id in nice_counts){
                    var res_data=nice_counts[lvl_id][reg_id][$scope.district];  
                    //console.log("right there ...level is "+lvl_id+" reg id is:"+reg_id+"district is "+$scope.district);
                    for(var i in res_data){
                        var val=Number(res_data[i]);
                        count_num+=val;
                        filtered_data[i]=filtered_data[i]+val;
                    }
                }

            }

        }else if ($scope.region!="all"){
            if($scope.care_level!="all"){
                var res_data=nice_counts[$scope.care_level][$scope.region];
                for(var dist_id in res_data){
                    var dist_arr=res_data[dist_id];
                    for(var i in dist_arr){
                       var val=Number(dist_arr[i]);
                       count_num+=val;
                       filtered_data[i]=filtered_data[i]+val;
                    }
                }
            }else{
                for(var lvl_id in nice_counts){
                    var res_data=nice_counts[lvl_id][$scope.region];
                    for(var dist_id in res_data){
                        var dist_arr=res_data[dist_id];
                        for(var i in dist_arr){
                            var val=Number(dist_arr[i]);
                            count_num+=val;
                            filtered_data[i]=filtered_data[i]+val;
                        }
                    }
                }

            }
        }else if($scope.care_level!="all"){
            var level_data=nice_counts[$scope.care_level]; 
            for(var reg_id in level_data){
                var reg_arr=level_data[reg_id];
                for(var dist_id in reg_arr){
                    var dist_arr=reg_arr[dist_id];
                    for(var i in dist_arr){
                        var val=Number(dist_arr[i]);
                        count_num+=val;
                        filtered_data[i]=filtered_data[i]+val;
                    }
                }
            }
        }else{
            count_num=$scope.total_samples_init;
            filtered_data={};
        }
        $scope.total_samples=count_num;      
        console.log(filtered_data);
        $timeout(function(){
            if($("#tb_hd2").hasClass('tab-current')){
                var ctx = $("#average_uptake_rate").get(0).getContext("2d");
                var data = {
                    labels: months,datasets: [
                    nums_json,
                    {
                        "fillColor":"rgba(0, 0, 0, 0)",
                        "strokeColor":"#5EA361",
                        "pointColor":"#5EA361",
                        "pointStrokeColor":"#fff",
                        "pointHighlightFill":"#fff",
                        "pointHighlightStroke":"#5EA361",
                        "data":filtered_data
                    }] 
                };
                var myLineChart = new Chart(ctx).Line(data);
            }

        },1);

    };

    $scope.avInitRate=function(filterer){
        var init_num=0;
        var pos_num=0;
        var av_init=0;
        var filtered_data={"1":0, "2":0, "3":0, "4":0, "5":0, "6":0, "7":0,"8":0,"9":0,"10":0,"11":0,"12":0};
        var init_arr={"1":0, "2":0, "3":0, "4":0, "5":0, "6":0, "7":0,"8":0,"9":0,"10":0,"11":0,"12":0};
        var positives_arr={"1":0, "2":0, "3":0, "4":0, "5":0, "6":0, "7":0,"8":0,"9":0,"10":0,"11":0,"12":0};

        if($scope.district!="all"){
            var reg_id=dist_n_reg_ids[$scope.district];
            if($scope.care_level!="all"){
               var res_data=nice_counts_art_inits[$scope.care_level][reg_id][$scope.district]; 
               var res_data_p=nice_counts_positives[$scope.care_level][reg_id][$scope.district];
               for(var i in res_data){
                var val=Number(res_data[i])||0;
                var val_p=Number(res_data_p[i])||0;
                init_num+=val;
                pos_num+=val_p;
                init_arr[i]=init_arr[i]+val;
                positives_arr[i]=positives_arr[i]+val_p;
               }
            }else{
                //console.log("right there ... reg id is:"+reg_id);
                for(var lvl_id in nice_counts){
                    var res_data=nice_counts_art_inits[lvl_id][reg_id][$scope.district];  
                    var res_data_p=nice_counts_positives[lvl_id][reg_id][$scope.district];  
                    //console.log("right there ...level is "+lvl_id+" reg id is:"+reg_id+"district is "+$scope.district);
                    for(var i in res_data){
                        var val=Number(res_data[i])||0;
                        var val_p=Number(res_data_p[i])||0;
                        //console.log("value is "+val+" val_p is "+val_p);
                        init_num+=val;
                        pos_num+=val_p;
                        init_arr[i]=init_arr[i]+val;
                        positives_arr[i]=positives_arr[i]+val_p;
                    }
                }

            }

        }else if ($scope.region!="all"){
            if($scope.care_level!="all"){
                var res_data=nice_counts_art_inits[$scope.care_level][$scope.region];
                var res_data_p=nice_counts_positives[$scope.care_level][$scope.region];
                for(var dist_id in res_data){
                    var dist_arr=res_data[dist_id];
                    var dist_arr_p=res_data_p[dist_id];
                    for(var i in dist_arr){
                       var val=Number(dist_arr[i])||0;
                       var val_p=Number(dist_arr_p[i])||0;
                       init_num+=val;
                       pos_num+=val_p;
                       init_arr[i]=init_arr[i]+val;
                       positives_arr[i]=positives_arr[i]+val_p;
                    }
                }
            }else{
                for(var lvl_id in nice_counts){
                    var res_data=nice_counts_art_inits[lvl_id][$scope.region];
                    var res_data_p=nice_counts_positives[lvl_id][$scope.region];
                    for(var dist_id in res_data){
                        var dist_arr=res_data[dist_id];
                        var dist_arr_p=res_data_p[dist_id];
                        for(var i in dist_arr){
                            var val=Number(dist_arr[i])||0;
                            var val_p=Number(dist_arr_p[i])||0;
                            init_num+=val;
                            pos_num+=val_p;
                            init_arr[i]=init_arr[i]+val;
                            positives_arr[i]=positives_arr[i]+val_p;
                        }
                    }
                }

            }
        }else if($scope.care_level!="all"){
            var level_data=nice_counts_art_inits[$scope.care_level]; 
            var level_data_p=nice_counts_positives[$scope.care_level];
            for(var reg_id in level_data){
                var reg_arr=level_data[reg_id];
                var reg_arr_p=level_data_p[reg_id];
                for(var dist_id in reg_arr){
                    var dist_arr=reg_arr[dist_id];
                    var dist_arr_p=reg_arr_p[dist_id];
                    for(var i in dist_arr){
                        var val=Number(dist_arr[i])||0;
                        var val_p=Number(dist_arr_p[i])||0;
                        init_num+=val;
                        pos_num+=val_p;
                        init_arr[i]=init_arr[i]+val;
                        positives_arr[i]=positives_arr[i]+val_p;
                    }
                }
            }
        }else{
            init_num=$scope.total_initiated_init;
            pos_num=$scope.count_positives_init;
            filtered_data={};
        }
        av_init=((init_num/pos_num)*100)||0;
        $scope.av_initiation_rate=av_init;   

        for(var i in init_arr){
            var nana=((init_arr[i]/positives_arr[i])*100)||0;
            filtered_data[i]=nana.toFixed(1);
        }
       
        
        $timeout(function(){
            if($("#tb_hd3").hasClass('tab-current')){
                var ctx = $("#average_init_rate").get(0).getContext("2d");
                var data = {
                    labels: months,datasets: [
                    av_initiation_rate_months_json,
                    {
                        "fillColor":"rgba(0, 0, 0, 0)",
                        "strokeColor":"#F5A623",
                        "pointColor":"#F5A623",
                        "pointStrokeColor":"#fff",
                        "pointHighlightFill":"#fff",
                        "pointHighlightStroke":"#F5A623",
                        "data":filtered_data
                    }] 
                };
                var myLineChart = new Chart(ctx).Line(data);
            }

        },1);

    };


    $scope.avPositivity=function (filterer){
        var count_num=0;
        var pos_num=0;
        var av_pos=0;
        var filtered_data={"1":0, "2":0, "3":0, "4":0, "5":0, "6":0, "7":0,"8":0,"9":0,"10":0,"11":0,"12":0};
        var counts_arr={"1":0, "2":0, "3":0, "4":0, "5":0, "6":0, "7":0,"8":0,"9":0,"10":0,"11":0,"12":0};
        var positives_arr={"1":0, "2":0, "3":0, "4":0, "5":0, "6":0, "7":0,"8":0,"9":0,"10":0,"11":0,"12":0};

        if($scope.district!="all"){
            var reg_id=dist_n_reg_ids[$scope.district];
            if($scope.care_level!="all"){
               var res_data=nice_counts[$scope.care_level][reg_id][$scope.district]; 
               var res_data_p=nice_counts_positives[$scope.care_level][reg_id][$scope.district];
               for(var i in res_data){
                var val=Number(res_data[i])||0;
                var val_p=Number(res_data_p[i])||0;
                count_num+=val;
                pos_num+=val_p;
                counts_arr[i]=counts_arr[i]+val;
                positives_arr[i]=positives_arr[i]+val_p;
               }
            }else{
                //console.log("right there ... reg id is:"+reg_id);
                for(var lvl_id in nice_counts){
                    var res_data=nice_counts[lvl_id][reg_id][$scope.district];  
                    var res_data_p=nice_counts_positives[lvl_id][reg_id][$scope.district];  
                    //console.log("right there ...level is "+lvl_id+" reg id is:"+reg_id+"district is "+$scope.district);
                    for(var i in res_data){
                        var val=Number(res_data[i])||0;
                        var val_p=Number(res_data_p[i])||0;
                        console.log("value is "+val+" val_p is "+val_p);
                        count_num+=val;
                        pos_num+=val_p;
                        counts_arr[i]=counts_arr[i]+val;
                        positives_arr[i]=positives_arr[i]+val_p;
                    }
                }

            }

        }else if ($scope.region!="all"){
            if($scope.care_level!="all"){
                var res_data=nice_counts[$scope.care_level][$scope.region];
                var res_data_p=nice_counts_positives[$scope.care_level][$scope.region];
                for(var dist_id in res_data){
                    var dist_arr=res_data[dist_id];
                    var dist_arr_p=res_data_p[dist_id];
                    for(var i in dist_arr){
                       var val=Number(dist_arr[i])||0;
                       var val_p=Number(dist_arr_p[i])||0;
                       count_num+=val;
                       pos_num+=val_p;
                       counts_arr[i]=counts_arr[i]+val;
                       positives_arr[i]=positives_arr[i]+val_p;
                    }
                }
            }else{
                for(var lvl_id in nice_counts){
                    var res_data=nice_counts[lvl_id][$scope.region]||{};
                    var res_data_p=nice_counts_positives[lvl_id][$scope.region]||{};
                    for(var dist_id in res_data){
                        var dist_arr=res_data[dist_id]||{};
                        var dist_arr_p=res_data_p[dist_id]||{};
                        for(var i in dist_arr){
                            var val=Number(dist_arr[i])||0;
                            var val_p=Number(dist_arr_p[i])||0;
                            count_num+=val;
                            pos_num+=val_p;
                            counts_arr[i]=counts_arr[i]+val;
                            positives_arr[i]=positives_arr[i]+val_p;
                        }
                    }
                }

            }
        }else if($scope.care_level!="all"){
            var level_data=nice_counts[$scope.care_level]; 
            var level_data_p=nice_counts_positives[$scope.care_level];
            for(var reg_id in level_data){
                var reg_arr=level_data[reg_id];
                var reg_arr_p=level_data_p[reg_id];
                for(var dist_id in reg_arr){
                    var dist_arr=reg_arr[dist_id];
                    var dist_arr_p=reg_arr_p[dist_id];
                    for(var i in dist_arr){
                        var val=Number(dist_arr[i])||0;
                        var val_p=Number(dist_arr_p[i])||0;
                        count_num+=val;
                        pos_num+=val_p;
                        counts_arr[i]=counts_arr[i]+val;
                        positives_arr[i]=positives_arr[i]+val_p;
                    }
                }
            }
        }else{
            count_num=$scope.total_samples_init;
            pos_num=$scope.count_positives_init;
            filtered_data={};
        }
        av_pos=((pos_num/count_num)*100)||0;
        $scope.av_positivity=av_pos;   

        for(var i in counts_arr){
            var nana=((positives_arr[i]/counts_arr[i])*100)||0;
            filtered_data[i]=nana.toFixed(1);
        }
       
        
        $timeout(function(){
            if($("#tb_hd4").hasClass('tab-current')){
                var ctx = $("#av_positivity_canvas").get(0).getContext("2d");
                var data = {
                    labels: months,datasets: [
                    av_positivity_json,{
                        "fillColor":"rgba(0, 0, 0, 0)",
                        "strokeColor":"#9F82D1",
                        "pointColor":"#9F82D1",
                        "pointStrokeColor":"#fff",
                        "pointHighlightFill":"#fff",
                        "pointHighlightStroke":"#9F82D1",
                        "data":filtered_data
                    }] 
                };
                var myLineChart = new Chart(ctx).Line(data);
            };
        },1);
    };
};

app.controller(ctrllers);
</script>
</html>
