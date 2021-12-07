 <div class="row">
    

    <div class="col-lg-12 facilties-sect " >
    <?php 
    $currentyr = date('Y');
    $currentWeekNumber = date('W');
    $dates = new DateTime();
     ?>
        <div>
            <span style="font-size: 10px; color: #F44336;">
            </span>
            <br>
            <br>
            
        </div>
        
             <table  id="results-stats-table" datatable="ng" class="row-border hover table table-bordered table-condensed table-striped">
                            <thead>
                                <tr>
                            
                                    <th>POC site</th>
                                    <th># Peripheral facilities</th>
                                    <th>District</th>
                                    <th>Device</th>
                                    <th>#Tests</th>
                                    <!-- <th <?php $dates->setISODate($currentyr,$currentWeekNumber);
                                        $result = $dates->format('d-M-Y');?>><a href='#' title="from {{$result}}">Week {{$currentWeekNumber}}</a></th> -->
                                    <th <?php $dates->setISODate($currentyr,$currentWeekNumber-1);
                                        $result = $dates->format('d-M-Y');?>><a href='#' title="from {{$result}}">Week {{$currentWeekNumber -1}}</a></th>
                                    <th <?php $dates->setISODate($currentyr,$currentWeekNumber-2);
                                        $result = $dates->format('d-M-Y');?>><a href='#' title="from {{$result}}">Week {{$currentWeekNumber -2}}</a></th>
                                    <th <?php $dates->setISODate($currentyr,$currentWeekNumber-3);
                                        $result = $dates->format('d-M-Y');?>><a href='#' title="from {{$result}}">Week {{$currentWeekNumber -3}}</a></th>
                                    <th <?php $dates->setISODate($currentyr,$currentWeekNumber-4);
                                        $result = $dates->format('d-M-Y');?>><a href='#' title="from {{$result}}">Week {{$currentWeekNumber -4}}</a></th>
                                    <th <?php $dates->setISODate($currentyr,$currentWeekNumber-5);
                                        $result = $dates->format('d-M-Y');?>><a href='#' title="from {{$result}}">Week {{$currentWeekNumber -5}}</a></th>
                                    <th <?php $dates->setISODate($currentyr,$currentWeekNumber-6);
                                        $result = $dates->format('d-M-Y');?>><a href='#' title="from {{$result}}">Week {{$currentWeekNumber -6}}</a></th>
                                    <th <?php $dates->setISODate($currentyr,$currentWeekNumber-7);
                                        $result = $dates->format('d-M-Y');?>><a href='#' title="from {{$result}}">Week {{$currentWeekNumber -7}}</a></th>
                                    <th <?php $dates->setISODate($currentyr,$currentWeekNumber-8);
                                        $result = $dates->format('d-M-Y');?>><a href='#' title="from {{$result}}">Week {{$currentWeekNumber -8}}</a></th>
                                    <th>#Negatives</th>
                                    <th>#Positives</th>
                                    <th>Positivity Rate</th>
                                    <th>#Errors</th>
                                    <th>Error Rate</th>
                                    <th>Last Report Date</th>

                                </tr>
                            </thead>
                               <tbody>                                
                                    <tr ng-repeat="f in poc_facilities_array" ng-if="f.tests > 0">
                                        <td class="ng-cloak"><% f.facility %></td>
                                        <td class="ng-cloak"><% f.peripheral_sites %></td>
                                        <td class="ng-cloak"><% f.district %></td>
                                        <td class="ng-cloak"><% f.poc_device %></td>
                                        <td class="ng-cloak"><% f.tests %></td>
                                        <!-- <td class="ng-cloak"><% f.thiswk %></td> -->
                                        <td class="ng-cloak"><% f.wk1 %></td>
                                        <td class="ng-cloak"><% f.wk2 %></td>
                                        <td class="ng-cloak"><% f.wk3 %></td>
                                        <td class="ng-cloak"><% f.wk4 %></td>
                                        <td class="ng-cloak"><% f.wk5 %></td>
                                        <td class="ng-cloak"><% f.wk6 %></td>
                                        <td class="ng-cloak"><% f.wk7 %></td>
                                        <td class="ng-cloak"><% f.wk8 %></td>
                                        <td class="ng-cloak"><% f.negatives %></td>
                                        <td class="ng-cloak"><% f.positives %></td>
                                        <td class="ng-cloak"><% ((f.positives/(f.negatives + f.positives))*100)| number:1.0-0 %> %</td>
                                        <td class="ng-cloak"><% f.errors %></td>
                                        <td class="ng-cloak"><% ((f.errors/f.tests)*100) | number:1.0-0 %> %</td>
                                        <td class="ng-cloak"><% f.latest_date %></td>
                                    </tr>                        
                                 </tbody>
                             
            </table>         

    </div>
</div>