
//angular stuff

/*
Authors
Name                        @       Period      Role       
Logan Smith                 CHAI    2015(v1)    Interface Design, Q/A
Ina Foalea                  CHAI    2015(v1)    Req Specification, Q/A, UAT
Kitutu Paul                 CHAI    2015(v1)    System development

Credit to CHAI Uganda, CPHL and stakholders
*/
var app=angular.module('dashboard', ['datatables'], function($interpolateProvider) {
        $interpolateProvider.startSymbol('<%');
        $interpolateProvider.endSymbol('%>');
    });

app.filter('ssplit', function() {
        return function(input, splitChar,splitIndex) {
            // do some bounds checking here to ensure it has that index
            var arr=input.split(splitChar);
            return arr[splitIndex];
        }
    });

app.filter('slice', function() {
        return function(input, length) {
            return input.slice(length);
        }
    });

app.filter('d_format', function() {
        return function(y_m) {
            var month_labels={1:'Jan',2:'Feb',3:'Mar',4:'Apr',5:'May',6:'Jun',7:'Jul',8:'Aug',9:'Sept',10:'Oct',11:'Nov',12:'Dec'};
            var arr=y_m.split('-');
            var yr=arr[0]||"";
            var mth=arr[1]||"";
            return month_labels[mth]+" '"+yr.slice(-2);
        }
    });




var ctrllers={};

ctrllers.DashController=function($scope,$http){
    var regions_json={};
    var districts_json={};    
    var care_levels_json={};   
    var facilities_json={};   
    var results_json={}; //to hold a big map will all processed data to later on be used in the generalFilter


    $http.get("../json/data.json").success(function(data) {
        regions_json=data.regions;
        districts_json=data.districts;        
        care_levels_json=data.care_levels;
        facilities_json=data.facilities;

        $scope.regions_slct=pairize(regions_json);       
        $scope.districts_slct=pairize(districts_json);        
        $scope.care_levels_slct=pairize(care_levels_json);

        var res=data.results||{};
        for(var i in res){
           var that=res[i];
           var facility_details=facilities_json[that.facility_id]||{};  
           results_json[i]={}; 
           results_json[i].year_month=that.year+"-"+that.month;
           results_json[i].facility_id=that.facility_id;          
           results_json[i].facility_name=facility_details.name||"";
           results_json[i].region_id=facility_details.region_id;
           results_json[i].district_id=facility_details.district_id;
           results_json[i].care_level_id=facility_details.care_level_id;

           results_json[i].samples_received=Number(that.samples_received)||0;
           results_json[i].hiv_positive_infants=Number(that.hiv_positive_infants)||0;
           results_json[i].initiated=Number(that.initiated)||0;
           results_json[i].pcr_one=Number(that.pcr_one)||0;
           results_json[i].pcr_two=Number(that.pcr_two)||0;
           results_json[i].pcr_one_ages=Number(that.pcr_one_ages)||{};
           results_json[i].pcr_two_ages=Number(that.pcr_two_ages)||{};           
        }

        // console.log("first facility:"+JSON.stringify(results_json[0]));

        //console.log("number of data records:"+count(data));
       generalFilter(); //call the filter for the first time
    });

    $scope.dateFilter=function(mode){
        if($scope.fro_date!="all" && $scope.to_date!="all"){
            var vals={};var fro_s=$scope.fro_date.split("-");var to_s=$scope.to_date.split("-");
            vals.from_year=Number(fro_s[0]);
            vals.from_month=Number(fro_s[1]);
            vals.to_year=Number(to_s[0]);
            vals.to_month=Number(to_s[1]);

            var eval1=vals.from_year<=vals.to_year;
            var eval2=(vals.from_month>vals.to_month)&&(vals.from_year<vals.to_year);
            var eval3=(vals.from_month<=vals.to_month);

            if(eval1 && (eval2||eval3)){
                //console.log("duration expression passed");
                computeDuration(vals);
               /* if(count($scope.filter_duration)<=12){
                    
                }else{
                    alert("Please choose a duration of 12 months or less");
                }*/
                $scope.date_filtered=true;
               /* $scope.fro_date="all";
                $scope.to_date="all";*/
                $scope.filter("duration");                
            }else{
                alert("Please make sure that the fro date is earlier than the to date");
                //console.log("duration expression failing eval1="+eval1+" eval2"+eval2+" eval3"+eval3);
                //console.log("fro yr="+vals.from_year+" fro m"+vals.from_month+" to yr="+vals.to_year+" to m"+vals.to_month);
            }
        }
    }

    var computeDuration=function(vals){
        $scope.filter_duration=[];
        var i=vals.from_year;
        while(i<=vals.to_year){
            var stat=(i==vals.from_year)?vals.from_month:1;
            var end=(i==vals.to_year)?vals.to_month:12;
            var j=stat;
            while(j<=end){
                $scope.filter_duration.push(i+"-"+j);
                j++;   
            }   
            i++;  
        }
    }

    $scope.filter=function(mode){
        switch(mode){
            case "region":
            $scope.filter_regions[$scope.region]=regions_json[$scope.region];
            $scope.region='all';
            break;
            case "district":
            $scope.filter_districts[$scope.district]=districts_json[$scope.district];
            $scope.district='all';            
            break;
            case "care_level":
            $scope.filter_care_levels[$scope.care_level]=care_levels_json[$scope.care_level];
            $scope.care_level='all';
            break;
        }

        delete $scope.filter_regions["all"];
        delete $scope.filter_districts["all"];        
        delete $scope.filter_care_levels["all"];

        generalFilter(); //filter the results for each required event
    }



    var evaluator=function(that){  
        var r_num=count($scope.filter_regions);
        var d_num=count($scope.filter_districts);
        var c_num=count($scope.filter_care_levels);

        var time_eval=inArray(that.year_month,$scope.filter_duration);
        var reg_eval=$scope.filter_regions.hasOwnProperty(that.region_id);
        var dist_eval=$scope.filter_districts.hasOwnProperty(that.district_id);        
        var cl_eval=$scope.filter_care_levels.hasOwnProperty(that.care_level_id);

        var eval1=r_num==0&&d_num==0&&c_num==0;     // regions(OFF) and districts(OFF) and care_levels (OFF)
        var eval2=reg_eval&&d_num==0&&c_num==0;     // regions(ON)  and districts(OFF) and care_levels (OFF)
        var eval3=reg_eval&&dist_eval&&c_num==0;    // regions(ON)  and districts(ON)  and care_levels (OFF)
        var eval4=reg_eval&&d_num==0&&cl_eval;      // regions(ON)  and districts(OFF) and care_levels (ON)
        var eval5=reg_eval&&dist_eval&&cl_eval;     // regions(ON)  and districts(ON)  and care_levels (ON)
        var eval6=r_num==0&&dist_eval&&cl_eval;     // regions(OFF) and districts(ON)  and care_levels (ON)
        var eval7=r_num==0&&dist_eval&&c_num==0;    // regions(OFF) and districts(ON)  and care_levels (OFF)
        var eval8=r_num==0&&d_num==0&&cl_eval;      // regions(OFF) and districts(OFF) and care_levels (ON)

        if( time_eval && (eval1||eval2||eval3||eval4||eval5||eval6||eval7||eval8)){
            return true;
        }else{
            return false;
        }
    }

    var setKeyIndicators=function(that){
        $scope.samples_received+=that.samples_received;
        $scope.hiv_positive_infants+=that.hiv_positive_infants;
        $scope.initiated+=that.initiated;
    }

    var setOtherIndicators=function(that){
        $scope.pcr_one+=that.pcr_one;
        $scope.pcr_two+=that.pcr_two;

        $scope.pcr_one_ages.concat(that.pcr_one_ages);
        $scope.pcr_two_ages.concat(that.pcr_two_ages);
    }

    var setDataByDuration=function(that){
        var prev_sr=$scope.sr_by_duration[that.year_month]||0;
        var prev_hpi=$scope.hpi_by_duration[that.year_month]||0;
        var prev_i=$scope.i_by_duration[that.year_month]||0;

        $scope.sr_by_duration[that.year_month]=prev_sr+that.samples_received;
        $scope.hpi_by_duration[that.year_month]=prev_hpi+that.hiv_positive_infants;
        $scope.i_by_duration[that.year_month]=prev_i+that.initiated;
    }


    var setDataByFacility=function(that){
        $scope.facility_numbers[that.facility_id]=$scope.facility_numbers[that.facility_id]||{};
        var f_smpls_rvd=$scope.facility_numbers[that.facility_id].samples_received||0;
        var f_hpi=$scope.facility_numbers[that.facility_id].hiv_positive_infants||0;
        var f_i=$scope.facility_numbers[that.facility_id].initiated||0;

        $scope.facility_numbers[that.facility_id].samples_received=f_smpls_rvd+that.samples_received;
        $scope.facility_numbers[that.facility_id].hiv_positive_infants=f_hpi+that.hiv_positive_infants;
        $scope.facility_numbers[that.facility_id].initiated=f_i+that.initiated;       
        $scope.facility_numbers[that.facility_id].name=that.facility_name;
    }

    var generalFilter=function(){
        $scope.loading=true;
        $scope.samples_received=0;$scope.hiv_positive_infants=0;$scope.initiated=0;

        //this is data to be used in the graphs
        $scope.sr_by_duration={};$scope.hpi_by_duration={};$scope.i_by_duration={};

        $scope.facility_numbers={};//data to be used in the facility lists for each key indicator

        $scope.pcr_one=0;$scope.pcr_two=0;
        $scope.pcr_one_ages={};//create list to be used for 1st pcr median age
        $scope.pcr_two_ages={};//create list to be used for 2nd pcr median age       

        for(var i in results_json){
            var that = results_json[i];
            if(evaluator(that)){
                setKeyIndicators(that); //set the values for the key indicators
                setOtherIndicators(that); //set the values for other indicators
                setDataByDuration(that); //set data by duration to be displayed in graphs                    
                setDataByFacility(that); //set data by facility to be displayed in tables
            }         
        }
/*
        $scope.displaySamplesRecieved();
        $scope.displaySupressionRate();
        $scope.displayRejectionRate();
*/
        $scope.filtered=count($scope.filter_regions)>0||count($scope.filter_districts)>0||count($scope.filter_care_levels)>0||$scope.date_filtered;
        $scope.loading=false;    
    };


    $scope.displaySamplesRecieved=function(){       //$scope.samples_received=100000;
        var srd=$scope.samples_received_data;        
        var data=[{"key":"DBS","values":[] },{"key":"PLASMA","values":[] }];

        for(var i in srd.dbs){
            data[0].values.push({"x":dateFormat(i),"y":Math.round(srd.dbs[i])});
            data[1].values.push({"x":dateFormat(i),"y":Math.round(srd.plasma[i])});            
        }

        nv.addGraph( function(){
            var chart = nv.models.multiBarChart().color(["#F44336","#607D8B"]);
            if(count(srd.dbs)<=8) { chart.reduceXTicks(false); }

            chart.yAxis.tickFormat(d3.format(',.0d'));
            $('#samples_received svg').html(" ");
            d3.select('#samples_received svg').datum(data).transition().duration(500).call(chart);
            return chart;
        });
    };


    $scope.displaySupressionRate=function(){
        var data=[{"key":"SUPRESSION RATE","color": "#607D8B","values":[] },
                  {"key":"VALID RESULTS","bar":true,"color": "#F44336","values":[]}];

        for(var i in $scope.valid_res_by_duration){
            var sprsd=$scope.suppressed_by_duration[i]||0;
            var vld=$scope.valid_res_by_duration[i]||0;
            var s_rate=(sprsd/vld)*100;
            //s_rate.toPrecision(3);
            data[0].values.push([dateFormat(i),Math.round(s_rate)]);
            data[1].values.push([dateFormat(i),vld]);
        } 
        nv.addGraph( function() {
            var chart = nv.models.linePlusBarChart()
                        .margin({right: 60,})
                        .x(function(d,i) { return i })
                        .y(function(d,i) {return d[1] }).focusEnable(false);

            chart.xAxis.tickFormat(function(d) {
                return data[0].values[d] && data[0].values[d][0] || " ";
            });
            //chart.reduceXTicks(false);
            //chart.bars.forceY([0]);
            chart.lines.forceY([0,100]);
            chart.legendRightAxisHint(" (R)").legendLeftAxisHint(" (L)")

            $('#supression_rate svg').html(" ");
            d3.select('#supression_rate svg').datum(data).transition().duration(500).call(chart);
            return chart;
        });
    }

    $scope.displayRejectionRate=function(){
        var rbd=$scope.rejected_by_duration;
        var data=[{"key":"SAMPLE QUALITY","values":[]},
                  {"key":"INCOMPLETE FORM","values":[] },
                  {"key":"ELIGIBILITY","values":[] }];

        for(var i in rbd.sample_quality){
            var ttl=rbd.sample_quality[i]+rbd.incomplete_form[i]+rbd.eligibility[i];
            var sq_rate=(rbd.sample_quality[i]/ttl)*100;
            var inc_rate=(rbd.incomplete_form[i]/ttl)*100;
            var el_rate=(rbd.eligibility[i]/ttl)*100;
            data[0].values.push({"x":dateFormat(i),"y":Math.round(sq_rate) });
            data[1].values.push({"x":dateFormat(i),"y":Math.round(inc_rate)});
            data[2].values.push({"x":dateFormat(i),"y":Math.round(el_rate)});
        }
        nv.addGraph( function(){
            var chart = nv.models.multiBarChart().stacked(true).color(["#607D8B","#FFCDD2","#F44336"]);
            if(count(rbd.sample_quality)<=8) { chart.reduceXTicks(false); }
            chart.yAxis.tickFormat(d3.format(',.0d'));
            $('#rejection_rate svg').html(" ");
            d3.select('#rejection_rate svg').datum(data).transition().duration(500).call(chart);
            return chart;
        });
    };

     $scope.removeTag=function(mode,nr){
        switch(mode){
            case "region": delete $scope.filter_regions[nr];break;
            case "district": delete $scope.filter_districts[nr];break;            
            case "care_level": delete $scope.filter_care_levels[nr];break;
        }
        $scope.filter(mode);
    };

    $scope.clearAllFilters=function(){
        $scope.filter_regions={};
        $scope.filter_districts={};        
        $scope.filter_care_levels={};
        $scope.filter_duration=$scope.init_duration;
        $scope.filtered=false;
        $scope.date_filtered=false;
        $scope.fro_date="all";
        $scope.to_date="all";
        generalFilter();
    }

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
    };

    $scope.empty=function(prop,status){
        return function(item){
            switch(item[prop]) {
                case "":
                case 0:
                case "0":
                case null:
                case false:
                case typeof this == "undefined":
                if(status=='no'){ return false; } else { return true; };
                    default :  if(status=='no'){ return true; } else { return false; };
                }
        }
           
    };

    $scope.showF=function(i){
        var show_f=false;
        switch(i){
            case 1:
            show_f=$scope.show_fclties1;
            $scope.show_fclties1=show_f==false?true:false;        
            break;

            case 2:
            show_f=$scope.show_fclties2;
            $scope.show_fclties2=show_f==false?true:false;
            break;

            case 3:
            show_f=$scope.show_fclties3;
            $scope.show_fclties3=show_f==false?true:false;
            break;
        }
        if(show_f==true){
            $("#d_shw"+i).attr("class","active");
            $("#f_shw"+i).attr("class","");
        }else{
            $("#f_shw"+i).attr("class","active");
            $("#d_shw"+i).attr("class","");
        }
    }

    var inArray=function(val,arr){
        var ret=false;
        for(var i in arr){
            if(val==arr[i]) ret=true;
        }
        return ret;
    }

    var dateFormat=function(y_m){
        var arr=y_m.split('-');
        var yr=arr[0];
        var mth=arr[1];
        return $scope.month_labels[mth]+" '"+yr.slice(-2);
    }

    var count=function(json_obj){
        return Object.keys(json_obj).length;
    }

    var pairize=function(arr){
        var ret=[];
        for(var i in arr){
            ret.push({"id":i,"name":arr[i]});
        }
        return ret;
    }

};

app.controller(ctrllers);
