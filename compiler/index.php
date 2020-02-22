<?php
require_once("functions.php");
$project_name = basename($_GET["pn"]);
$project_dir = "../{$project_name}/";
$file_name = $_GET["fn"];
$lang = $_GET["lang"];
$proj_files_arrays = array();
if (!file_exists($project_dir)) {
  echo "Project Not Found.";
  exit;
}
foreach (glob("{$project_dir}*.ew") as $proj_file) {
  $proj_file_name = str_replace(array($project_dir,".ew"),"",$proj_file);
  $proj_file_name_exp = explode(".",$proj_file_name);
  $proj_files_arrays[$proj_file_name_exp[0]][$proj_file_name_exp[1]] = array("file_name"=>$proj_file_name, "path"=>$proj_file);
}
foreach ($proj_files_arrays as $key=>$proj_file_array) {
  if ($key === $file_name) {
    if (!file_exists($proj_file_array["strc"]["path"]) || !file_exists($proj_file_array["contents"]["path"])) {
      echo "File Not Found.";
      exit;
    }
    $html = generate_html($project_dir, $proj_file_array["strc"]["path"], $proj_file_array["contents"]["path"]);
  }
}
echo empty($html)?"File Not Found.":$html;
