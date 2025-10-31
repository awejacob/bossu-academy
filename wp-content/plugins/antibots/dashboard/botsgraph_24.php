<?php
namespace antibots_graph;
if (!defined('ABSPATH'))
  exit; // Exit if accessed directly
include("calcula_stats_24.php");
$totrow = count($array24);
  if($totrow <  1)
{
    _e("Please, try again later.", 'antibots');
    return;
}

if ($totrow > 12)
  $totrow = 12;
echo '<script type="text/javascript">';
echo 'jQuery(function() {';
echo 'var d1009992 = [';
for ($i = 0; $i < $totrow; $i++) {
  //graph.push([i,demand[i]]);   
  echo '[';
  echo $i;
  echo ',';
  echo $array24[$i];
  echo ']';
  if ($i < $totrow - 1)
    echo ',';
}
echo '];';
echo 'var ticks = [';
for ($i = 0; $i < $totrow; $i++) {
  //        ticks.push([i,dates[i]]);    
  echo '[';
  echo $i;
  echo ',';
  echo $array24l[$i];
  echo ']';
  if ($i < $totrow - 1)
    echo ',';
}
echo '];';
?>
var options = {
series: {
lines: { show: true },
points: { show: true },
color: "#ff0000"
},
grid: { hoverable: true,
clickable: true,
borderColor: "#CCCCCC",
color: "#333333",
backgroundColor: { colors: ["#fff", "#eee"]}
},
xaxis:{
font:{
size:8,
style:"italic",
weight:"bold",
family:"sans-serif",
color: "#616161",
variant:"small-caps"
},
ticks: ticks,
/* minTickSize: [1, "day"] */
},
<?php
echo 'yaxis: {
                                  font:{
                                  size:10,
                                  style:"italic",
                                  weight:"bold",
                                  family:"sans-serif",
                                  color: "#616161",
                                  variant:"small-caps"
                                 },';
echo 'tickFormatter: function suffixFormatter(val, axis) {return (val.toFixed(0)); }';
echo '},';
?>
};
<?php
echo 'jQuery.plot("#placeholder1009992", [ d1009992 ], options);';
echo '});';
echo '</script>';
echo '<div id="placeholder1009992" style="width:100% !important; max-width:95% !important; height:265px; margin-top: -20px;"></div>';
?>