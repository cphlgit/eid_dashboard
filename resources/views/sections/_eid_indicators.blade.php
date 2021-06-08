<table class="table table-bordered table-condensed table-striped summary-tab">
	<tr>
		<th class="ng-cloak">Months</th>
		<th class="ng-cloak" ng-repeat="dn in duration_numbers">
			<% dn._id | d_format %>
		</th>
	</tr>
	<tr>
		<td class="tb-label">Samples Received</td>
		<td ng-repeat="dn in duration_numbers" class="figure ng-cloak">
			<% dn.total_tests | number %>
		</td>
	</tr>

	<tr>
		<td class="tb-label">Samples Tested</td>
		<td class="figure ng-cloak" ng-repeat="dn in duration_numbers">
			<% dn.total_testing_completed | number %>
		</td>
	</tr>
	
	<tr>
		<td class="tb-label">Positivity Rate</td>
		<td class="figure ng-cloak" ng-repeat="dn in duration_numbers">
			<% (dn.hiv_positive_infants/dn.total_tests)*100 | number:1 %>%
		</td>
	</tr>
	
</table>  