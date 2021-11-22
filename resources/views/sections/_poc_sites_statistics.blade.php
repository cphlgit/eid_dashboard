 <div class="row">
    

    <div class="col-lg-12 facilties-sect " >
    <?php 
    $currentWeekNumber = date('W');
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
                            
                                    <th>Facility</th>
                                    <th># Pheripheral facilities</th>
                                    <th>District</th>
                                    <th>Device</th>
                                    <th>#Tests</th>
                                    <th># $currentWeekNumber</th>
                                    <th>#Last week</th>
                                    <th>#Week2</th>
                                    <th>#Week3</th>
                                    <th>#Week4</th>
                                    <th>#Week5</th>
                                    <th>#Week6</th>
                                    <th>#Week7</th>
                                    <th>#Week8</th>
                                    <th>#Negatives</th>
                                    <th>#Positives</th>
                                    <th>Positivity Rate</th>
                                    <th>#Errors</th>
                                    <th>Error Rate</th>
                                    <th>Date last updated</th>

                                </tr>
                            </thead>
                               <tbody>                                
                                    <tr ng-repeat="f in poc_facilities_array" ng-if="f.tests > 0">
                                        <td class="ng-cloak"><% f.facility %></td>
                                        <td class="ng-cloak"><% f.peripheral_sites %></td>
                                        <td class="ng-cloak"><% f.district %></td>
                                        <td class="ng-cloak"><% f.poc_device %></td>
                                        <td class="ng-cloak"><% f.tests %></td>
                                        <td class="ng-cloak"><% f.thiswk %></td>
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
                                        <td class="ng-cloak"><% ((f.positives/(f.negatives + f.positives))*100)| number:1 %> %</td>
                                        <td class="ng-cloak"><% f.errors %></td>
                                        <td class="ng-cloak"><% ((f.errors/f.tests)*100) | number:1 %> %</td>
                                        <td class="ng-cloak"><% f.latest_date %></td>
                                    </tr>                        
                                 </tbody>
                             
            </table>

            

    </div>
</div>