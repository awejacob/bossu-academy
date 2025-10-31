<?php
include("calcula_stats_pie2.php");

if(!isset($antibots_results10))
 return;
 if(count($antibots_results10) < 1)
  return;

echo '<script type="text/javascript">';
echo 'var antibots_pie2 = [';
$label = "Bots "; // . (round($antibots_results10[0]['Bots'],2)) * 100;
echo '{label: "' . $label . '", data: ' . $antibots_results10[0]['Bots'] . ', color: "#FF0000" },';
$label = "Humans "; // . (round($antibots_results10[0]['Humans'],2)) * 100;
echo '{label: "' . $label . '", data: ' . $antibots_results10[0]['Humans'] . ', color: "#00A36A" }';
echo '];';
?>
function labelFormatter(label, series) {
return "<div style='font-size:15px;'>" + label + "<br />" + Math.round(series.percent) + "%</div>";
};
var antibots_pie2_options = {
series: {
pie: {
show: true,
innerRadius: 0.3,
label: {
show: true,
formatter: labelFormatter,
}
}
},
legend: {
show: false,
}
};
jQuery(document).ready(function () {
jQuery.plot(jQuery("#antibots_flot-placeholder_pie2"), antibots_pie2, antibots_pie2_options);
});
</script>
<div id="antibots_flot-placeholder_pie2" style="width:300px;height:220px;margin:-20px 0 auto"></div>