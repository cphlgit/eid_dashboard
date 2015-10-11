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
    <link href="{{ asset('/css/eid.css') }}" rel="stylesheet">
    <link href="{{ asset('/css/tab.css') }}" rel="stylesheet">

    <script src="{{ asset('/js/general.js') }}" type="text/javascript"></script>
    <script src="{{ asset('/js/jquery-2.1.3.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('/js/jquery.dataTables.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('/js/jquery-ui.js')}}" type="text/javascript"></script>

    <script src="{{ asset('/js/tab.js')}}" type="text/javascript"></script>

    <script src="{{ asset('/js/Chart.js')}}" type="text/javascript"></script>

    
</head>
<body onload="init()">
<div class="navbar-custom navbar navbar-inverse navbar-fixed-top" role="navigation">
    <div class="container">
        <div class="navbar-header">
            <a class="navbar-brand" href="/db"> <span class='glyphicon glyphicon-home'></span> EID LIMS</a>
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
    <?php if(!isset($filter_val)) $filter_val="National Metrics, ".date('Y')." thus far" ?>
     <p><label class='hdr hdr-grey'> FILTERS:</label> <label class='hdr val-grey'>{!! $filter_val !!}</label> </p>
    
     <table border='1' cellpadding='0' cellspacing='0' class='filter-tb'>
        <tr>
            <td width='25%'>{!! Form::select('time',[''=>'YEAR']+MyHTML::years(2010),$time,["id"=>"time_fltr"]) !!}</td>
            <td width='25%'>{!! Form::select('region',[''=>'REGION']+$regions) !!}</td>
            <td width='25%'>{!! Form::select('district',[''=>'DISTRICT']+$districts) !!}</td>
            <td width='25%'>{!! Form::select('care_level',[''=>'CARE LEVEL']+$facility_levels) !!}</td>
        </tr>
     </table>
     <br>

     <div class="tabs">
        <ul id='tabs'>
            <li class="selected">
                <a href="#tab1">
                <label class='tab-labels'>{!! $count_positives !!}
                    <font class='tab-sm-ttl'>HIV POSITIVE INFANTS</font>
                </label>
                </a>
            </li>
            <li>
                <a href="#tab2">
                    <label class='tab-labels'>00.0%
                        <font class='tab-sm-ttl'>AVERAGE UPTAKE RATE</font>
                    </label>
                </a>
            </li>
            <li>
                <a href="#tab3">
                    <label class='tab-labels'>
                        29.1%
                        <font class='tab-sm-ttl'>AVERAGE ART INITIATION RATE</font>
                    </label>
                </a>
            </li>
            <li>
                <a href="#tab4">
                    <label class='tab-labels'>
                        {!! $av_positivity !!}%
                        <font class='tab-sm-ttl'>AVERAGE POSITIVITY RATE</font>
                    </label>
                </a>
            </li>
        </ul>

        <div>
            <div id="tab1" class="tabContent">
                <canvas id="hiv_postive_infants" class='db-charts'></canvas>              
            </div>

            <div id="tab2" class="tabContent hide">
                00.0%
            </div>
 
            <div id="tab3" class="tabContent hide">
                <p>Tab #3 content goes here!</p>
                <p>Donec pulvinar neque sed semper lacinia. Curabitur lacinia ullamcorper nibh; quis imperdiet velit eleifend ac. Donec blandit mauris eget aliquet lacinia! Donec pulvinar massa interdum ri.</p>
            </div>
 
            <div id="tab4" class="tabContent hide">
                <canvas id="av_positivity" class='db-charts'></canvas>               
            </div>
        </div>
    </div>
</div>
</body>
<?php
$chart_stuff=[
    "fillColor"=>"rgba(151,187,205,0.2)",
    "strokeColor"=>"rgba(151,187,205,1)",
    "pointColor"=>"rgba(151,187,205,1)",
    "pointStrokeColor"=>"#fff",
    "pointHighlightFill"=>"#fff",
    "pointHighlightStroke"=> "rgba(151,187,205,1)"
    ];
?>
<script type="text/javascript">

$(document).ready( function(){
    var ctx = $("#hiv_postive_infants").get(0).getContext("2d");
   // This will get the first returned node in the jQuery collection. 
   var data = {
        labels: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul","Aug","Sept","Oct","Nov","Dec"],
        datasets: [<?php echo json_encode($chart_stuff + ["data"=>$count_positives_arr]) ?>] 
    };
    var myLineChart = new Chart(ctx).Line(data);
});

function av_positivity(){
    var ctx = $("#av_positivity").get(0).getContext("2d");
   // This will get the first returned node in the jQuery collection. 
   var data = {
        labels: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul","Aug","Sept","Oct","Nov","Dec"],
        datasets: [<?php echo json_encode($chart_stuff + ["data"=>$av_positivity_arr]) ?>] 
    };
    var myLineChart = new Chart(ctx).Line(data);
    console.log("heheh there");
}    

$("#tab4").on("click",setTimeout("av_positivity()",2000));

$("#time_fltr").change(function(){
    return window.location.assign("/"+this.value);
});


</script>
</html>
