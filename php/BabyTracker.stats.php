<?php

$html_space = "&nbsp;";

function FormatCellValue($value)
{
	if (!$value)
	    return $value;

	$decimal = strpos($value, ".");
	if ($decimal != false & (strlen($value) - $decimal) > 3)
		return number_format((float) $value, 2);

	switch($value)
	{
		case '0':
		case "0.0":
		case "0.00":
			return "";

		case "#N/A":
			$value = "";
			break;

		case 'Pump':
		case 'Breast':
		case 'Bottle':
		case 'Daipering':
		case 'Diapering':
			$value = "<hr><b>$value</b>";
			break;
	}

    return $value;
}

function IsSectionName($value)
{
	switch($value)
	{
		case 'Pump':
		case 'Breast':
		case 'Bottle':
		case 'Daipering':
		case 'Diapering':
			return true;
			break;
	}

    return false;
}

function FillRowHeaderArray(&$col)
{
    $col[] = 'Nurse';
	$col[] = 'Number of Nurses';
	$col[] = 'Time Nursed';
    $col[] = 'Avg Time per Nurse';
//    $col[] = 'Nurses per Day';
//    $col[] = 'Time per Day';
    $col[] = ' ';

    $col[] = 'Bottle';
    $col[] = 'Number of Bottles';
    $col[] = 'Amount Fed';
    $col[] = 'Amount per Bottle';
//    $col[] = 'Bottles per Day';
//    $col[] = 'Amount per Day';
    $col[] = ' ';

    $col[] = 'Pump';
    $col[] = 'Number of Pumps';
	$col[] = 'Amount Pumped';
    $col[] = 'Amount per Pump';
//    $col[] = 'Pumps per Day';
//    $col[] = 'Amount per Day';
    $col[] = ' ';

    $col[] = 'Daipering';
    $col[] = 'Number of Daipers';
    $col[] = 'Number of Wet';
    $col[] = 'Number of Poopy';
//    $col[] = 'Diapers per day';
//    $col[] = "Average wet / day";
//    $col[] = "Average poopy / day";
    $col[] = ' ';
}

function GetQuickStats($mysql, $table, $day_count, &$col, $daystart = 0, $dayend = 0)
{
    global $html_space;
	vprint("$table $day_count $daystart $dayend");

    $where_clause = "";
    if ($daystart && $dayend) {
        $where_clause .= "AND `datetime` >= '$daystart' AND `datetime` < '$dayend' ";
    }
    elseif ($dayend)
	{
        $where_clause .= "AND `datetime` = '$daystart' ";
	}
// Number of Nurse Today
// Number of Bottles
// Time since last breast feed
// Time since last bottle

    $return_array = 0;
    $field_list = "";

	$sql = 'SELECT ' .
    		"cast(count(id) as DECIMAL(10,2)) as count,".
			"cast(sum(amount_oz) as DECIMAL(10,2)) as sum,".
			"cast(avg(amount_oz) as DECIMAL(10,2)) as avg,".
			"cast((count(id)/$day_count) as DECIMAL(10,2)) as count_per_day,".
			"cast((sum(amount_oz)/$day_count) as DECIMAL(10,2)) as amount_per_day, ".
            "'$html_space' ".
			"FROM $table ";

    if ($where_clause) {
        $sql .= " WHERE 1=1 $where_clause";
    }

    $sql .= " GROUP BY `type`";

    //vprint($sql);
	$results = $mysql->query($sql);
    //DumpQueryResults($results);

    // get Breast, Bottle, Pump types
    for ($idx = 0; $idx < 3; $idx++)
    {
        @$col = array_merge($col, $mysql->query_results_num($results));
    }

    // Merge Wet & Diaper types
    $wet_array = $mysql->query_results($results);
    $poopy_array = $mysql->query_results($results);
    $diaper_array = array();
    $diaper_array[] = "$html_space";
    $diaper_array[] = $wet_array['count'] + $poopy_array['count'];
    $diaper_array[] = $wet_array['count'];
    $diaper_array[] = $poopy_array['count'];
    $diaper_array[] = $wet_array["count_per_day"] + $poopy_array["count_per_day"];
    $diaper_array[] = $wet_array["count_per_day"];
    $diaper_array[] = $poopy_array["count_per_day"];

    @$col = array_merge($col, $diaper_array);
}

function FillStatsArray($mysql, $table, $day_count, &$col, $daystart = 0, $dayend = 0, $group_by = 0, $dayavgs = 1)
{
    global $html_space;
	vprint("$table $day_count $daystart $dayend");

    $where_clause = "";
    if ($daystart && $dayend) {
        $where_clause .= "AND `datetime` >= '$daystart' AND `datetime` < '$dayend' ";
    }
    elseif ($dayend)
	{
        $where_clause .= "AND `datetime` = '$daystart' ";
	}

    $return_array = 0;
    $field_list = "";

	$sql = 'SELECT ' .
	        "'$html_space', " .
    		"cast(count(id) as DECIMAL(10,2)) as count,".
			"cast(sum(amount_oz) as DECIMAL(10,2)) as sum,".
			"cast(avg(amount_oz) as DECIMAL(10,2)) as avg,";

	if ($dayavgs)
	{
		$sql .=
			"cast((count(id)/$day_count) as DECIMAL(10,2)) as count_per_day,".
			"cast((sum(amount_oz)/$day_count) as DECIMAL(10,2)) as amount_per_day, ";
	}

	$sql .= "'$html_space' ".
			"FROM $table ";

    if ($where_clause) {
        $sql .= " WHERE 1=1 $where_clause";
    }

    $sql .= " GROUP BY `type`";

    //vprint($sql);
	$results = $mysql->query($sql);
    //DumpQueryResults($results);

    // get Breast, Bottle, Pump types
    for ($idx = 0; $idx < 3; $idx++)
    {
        @$col = array_merge($col, $mysql->query_results_num($results));
    }

    // Merge Wet & Diaper types
    $wet_array = $mysql->query_results($results);
    $poopy_array = $mysql->query_results($results);
    $diaper_array = array();
    $diaper_array[] = "$html_space";
    $diaper_array[] = $wet_array['count'] + $poopy_array['count'];
    $diaper_array[] = $wet_array['count'];
    $diaper_array[] = $poopy_array['count'];
    //$diaper_array[] = $wet_array["count_per_day"] + $poopy_array["count_per_day"];
    //$diaper_array[] = $wet_array["count_per_day"];
    //$diaper_array[] = $poopy_array["count_per_day"];

    @$col = array_merge($col, $diaper_array);
}

function FillStatsArrayWithCount($mysql, $table, $day_count, &$col, $daystart = 0, $dayend = 0, $group_by = 0)
{
    global $html_space;

    $where_clause = "";
    if ($daystart && $dayend) {
        $where_clause .= "AND `datetime` >= '$daystart' AND `datetime` < '$dayend' ";
    }
    elseif ($dayend)
	{
        $where_clause .= "AND `datetime` = '$daystart' ";
	}

    $return_array = 0;
    $field_list = "";

	$sql = 'SELECT ' .
    		"cast(count(id) as DECIMAL(10,2)) as count,".
			"FROM $table ";

    if ($where_clause) {
        $sql .= " WHERE 1=1 $where_clause";
    }

    $sql .= " GROUP BY `type`";

    //vprint($sql);
	$results = $mysql->query($sql);
    //DumpQueryResults($results);

    // get Breast, Bottle, Pump types
    while($array = $mysql->query_results_num($results))
    {
        @$col = array_merge($col, $array);
    }
}

function DisplayStatsArray($stats)
{
    $html = MakeTableHeader_trans($stats);
    $html .= MakeTableRow_trans($stats);
    $html .= MakeTableFooter();
    success($html);
}

function DisplayStatsCounts($stats)
{
    $html = MakeTableHeader($stats[0]);
    $html .= MakeTableRow($stats[1]);
    $html .= MakeTableFooter();
    success($html);
}

function DisplaySqlStats_Col($ids, $item='total', $day_max_delta=0, $day_min_delta=0)
{
    //set_output_flag("no_sql", 1);
    global $html_space;

	$mysql = GetMysql();
    @$table = $ids['tablename'];
	$max_date = GetQueryValue($mysql->query("select CAST(MAX(`datetime`) as DATE) from $table"));
    $dob = GetUserRegValue('dob', $ids);
	$day_count = GetQueryValue($mysql->query("select TIMESTAMPDIFF(DAY, '$dob', '$max_date')"));
	$year = GetQueryValue($mysql->query("select YEAR('$max_date')"));
	$month = GetQueryValue($mysql->query("select MONTH('$max_date')"));
    $cur_month = "$year/$month/1";
	$next_month = GetQueryValue($mysql->query("select DATE_ADD('$cur_month', INTERVAL 1 MONTH)"));
	$prev_month = GetQueryValue($mysql->query("select DATE_SUB('$cur_month', INTERVAL 1 MONTH)"));
	$cur_month_count = GetQueryValue($mysql->query("select TIMESTAMPDIFF(DAY, '$cur_month', '$next_month')"));
	$prev_month_count = GetQueryValue($mysql->query("select TIMESTAMPDIFF(DAY, '$prev_month', '$cur_month')"));

    $stats = array();

	$col = array();
    $col[] = 'Item Description';
    FillRowHeaderArray($col);
    $stats[] = $col;

    switch ($item)
    {
        case 'total':
	        $col = array();
            $col[] = 'Total';
            FillStatsArray($mysql, $table, $day_count, $col);
            $stats[] = $col;
            break;

        case 'today':
        	$col = array();
            // UNDONE: set title Today
            $col[] = GetQueryValue($mysql->query("select DATE_FORMAT('$max_date', '%m/%d')"));
            FillStatsArray($mysql, $table, $day_count, $col);
            $stats[] = $col;
            break;

        case 'now':
        // UNDONE: Use NOW!!!
	        $now = GetQueryValue($mysql->query("select MAX(`datetime`) from $table"));
	        $now24 = GetQueryValue($mysql->query("select TIMESTAMPADD(DAY, -1, '$now')"));
	        $now1 = GetQueryValue($mysql->query("select TIMESTAMPADD(SECOND, 1, '$now')"));
	        $col = array();
            $col[] = 'Last 24 Hours';
            FillStatsArray($mysql, $table, 1, $col, $now24, $now1);
            $stats[] = $col;
            break;

        case 'month':
	        $col = array();
            $col[] = GetQueryValue($mysql->query("select MONTHNAME('$cur_month')"));
            FillStatsArray($mysql, $table, $cur_month_count, $col, $cur_month, $next_month);
            $stats[] = $col;
            break;

        case "previous_month":
	        $col = array();
            $col[] = GetQueryValue($mysql->query("select MONTHNAME('$prev_month')"));
            FillStatsArray($mysql, $table, $prev_month_count, $col, $prev_month, $cur_month);
            $stats[] = $col;
            break;

        case 'week':
	        $col = array();
            $col[] = 'Week';
	        $day7 = GetQueryValue($mysql->query("select DATE_SUB('$max_date', INTERVAL 7 DAY)"));
            FillStatsArray($mysql, $table, 7, $col, $day7, $max_date);
            $stats[] = $col;
            break;

        case 'day':
	        $day_max = GetQueryValue($mysql->query("select DATE_SUB('$max_date', INTERVAL $day_max_delta day)"));
	        $day_min = GetQueryValue($mysql->query("select DATE_SUB('$max_date', INTERVAL $day_min_delta day)"));
	        $col = array();
            $col[] = GetQueryValue($mysql->query("select DATE_FORMAT('$day_max', '%m/%d')"));
            FillStatsArray($mysql, $table, 1, $col, $day_min, $day_max);
            $stats[] = $col;
            break;

        default:
            print("Unknown type of data to display.  Error occurred");
            return;
            break;
    }

    DisplayStatsArray($stats);
}

function DisplaySqlStats_Counts($ids)
{
    //set_output_flag("no_sql", 1);
	$mysql = GetMysql();
    @$table = $ids['tablename'];

    $stats = array();

	$row = array();
    $row['Nurses'] = 'Nurses';
    $row['Bottles'] = 'Bottles';
    $row['Pumps'] = 'Pumps';
    $row['Wet Diapers'] = 'Wet Diaper';
    $row['Poopy Diapers'] = 'Poopy Diaper';
    $stats[] = $row;

    $sql = 'SELECT ' .
           "count(id) " .
           "FROM $table GROUP BY `type`;";

    //vprint($sql);
	$results = $mysql->query($sql);
    //DumpQueryResults($results);

	$row = array();
    // get Breast, Bottle, Pump types
    while($array = $mysql->query_results_num($results))
    {
        @$row = array_merge($row, $array);
    }
    $stats[] = $row;

    //array_print($stats);
    DisplayStatsCounts($stats);
}

function DisplaySqlStatsMaxDate($token)
{
    //set_output_flag("no_sql", 1);
    global $html_space;

	$mysql = GetMysql();
	$child = GetChildData($token);
	varray_print($child);
	$table = $child['tablename'];
	$max_date = GetQueryValue($mysql->query("select CAST(MAX(`datetime`) as DATE) from $table"));
    $dob = $child['dob'];
	$day_count = GetQueryValue($mysql->query("select TIMESTAMPDIFF(DAY, '$dob', '$max_date')"));

// Number of Nurse Today
// Number of Bottles
// Time since last breast feed
// Time since last bottle

    // UNDONE: Move to insert path
	// update amount_oz
	$mysql->query("update $table set amount_oz=IF(ISNULL(amount), NULL, IF((type='breast' OR amount < 9), amount, amount / 29.5735296));");

    $stats = array();

	$col = array();
    $col[] = 'Item Description';
    FillRowHeaderArray($col);
    $stats[] = $col;

	$col = array();

/*
    $col[] = 'Total';
    FillStatsArray($mysql, $table, $day_count, $col);
    $stats[] = $col;
*/

	$col = array();
	$today = $max_date;
	$yesterday = GetQueryValue($mysql->query("select TIMESTAMPADD(DAY, -1, '$today')"));
	$tomorrow = GetQueryValue($mysql->query("select TIMESTAMPADD(DAY, 1, '$max_date')"));
    $col[] = 'Today';
	vprint("today=$today yesterday=$yesterday");
    FillStatsArray($mysql, $table, 1, $col, $today, $tomorrow, 0, 0);
    $stats[] = $col;

// UNDONE: Use NOW!!!
	$now = GetQueryValue($mysql->query("select MAX(`datetime`) from $table"));
	$now24 = GetQueryValue($mysql->query("select TIMESTAMPADD(DAY, -1, '$now')"));
	$now1 = GetQueryValue($mysql->query("select TIMESTAMPADD(SECOND, 1, '$now')"));
	$col = array();
    $col[] = '24 Hours';
    FillStatsArray($mysql, $table, 1, $col, $now24, $now1, 0, 0);
    $stats[] = $col;

/*
	$col = array();
    $col[] = 'Week';
	$day7 = GetQueryValue($mysql->query("select DATE_SUB('$max_date', INTERVAL 7 DAY)"));
    FillStatsArray($mysql, $table, 7, $col, $day7, $max_date);
    $stats[] = $col;

	$year = GetQueryValue($mysql->query("select YEAR('$max_date')"));
	$month = GetQueryValue($mysql->query("select MONTH('$max_date')"));
    $cur_month = "$year/$month/1";
	$next_month = GetQueryValue($mysql->query("select DATE_ADD('$cur_month', INTERVAL 1 MONTH)"));
	$prev_month = GetQueryValue($mysql->query("select DATE_SUB('$cur_month', INTERVAL 1 MONTH)"));
	$cur_month_count = GetQueryValue($mysql->query("select TIMESTAMPDIFF(DAY, '$cur_month', '$next_month')"));
	$prev_month_count = GetQueryValue($mysql->query("select TIMESTAMPDIFF(DAY, '$prev_month', '$cur_month')"));

	$col = array();
    $col[] = GetQueryValue($mysql->query("select MONTHNAME('$cur_month')"));
    FillStatsArray($mysql, $table, $cur_month_count, $col, $cur_month, $next_month);
    $stats[] = $col;

	$col = array();
    $col[] = GetQueryValue($mysql->query("select MONTHNAME('$prev_month')"));
    FillStatsArray($mysql, $table, $prev_month_count, $col, $prev_month, $cur_month);
    $stats[] = $col;

    $day_after = $max_date;
    for ($idx = 1; $idx < 20; $idx++)
    {
	    $day = GetQueryValue($mysql->query("select DATE_SUB('$day_after', INTERVAL 1 day)"));
	    $col = array();
        $col[] = GetQueryValue($mysql->query("select DATE_FORMAT('$day', '%m/%d')"));
        FillStatsArray($mysql, $table, 1, $col, $day, $day_after);
        $stats[] = $col;
        $day_after = $day;
    }
*/

    DisplayStatsArray($stats);
    //array_print($stats);
}

function DisplaySqlStats($token, $date, $dateEnd)
{
    //set_output_flag("no_sql", 1);
    global $html_space;

	$mysql = GetMysql();
	$child = GetChildData($token);
	varray_print($child);
	$table = $child['tablename'];
	$sql_date = '';
	$sql_date_end = '';

	if ($date != '')
	{
		list($month, $day, $year) = split('[/.-]', $date);
		$sql_date = "$year-$month-$day";
	}
	else
	{
		$sql_date = GetQueryValue($mysql->query("select CAST(MAX(`datetime`) as DATE) from $table"));
		list($year, $month, $day) = split('[/.-]', $sql_date);
		$date = "$month/$day/$year";
	}

	if ($dateEnd != '')
	{
		list($month, $day, $year) = split('[/.-]', $dateEnd);
		$sql_date_end = "$year-$month-$day";
	}
	else
	{
		$sql_date_end = GetQueryValue($mysql->query("select TIMESTAMPADD(DAY, 1, '$sql_date')"));
		list($year, $month, $day) = split('[/.-]', $sql_date_end);
		$dateEnd = "$month/$day/$year";
	}

    $dob = $child['dob'];
	vprint("$token $date $sql_date $dob");
	$day_count = GetQueryValue($mysql->query("select TIMESTAMPDIFF(DAY, '$dob', 'date')"));

    $stats = array();

	$col = array();
    FillRowHeaderArray($col);
    $stats[] = $col;

	$col = array();
	vprint("start date=$sql_date end date=$sql_date_end");
    FillStatsArray($mysql, $table, 1, $col, $sql_date, $sql_date_end, 0, 0);
    $stats[] = $col;


    DisplayStatsArray($stats);
    //array_print($stats);
	add_response_value('Date', $date);
	add_response_value('EndDate', $dateEnd);
}

?>
