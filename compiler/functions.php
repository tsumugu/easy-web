<?php
//標準関数の補佐的な関数群
function disp_error($type, $mes) {
  $enc = str_replace('"', '\"', $mes);
  $enc = str_replace(array("\r\n", "\r", "\n"), "", $enc);

  $mes_encoded = "{$type}: {$enc}";
  //echo "<script>console.log(\"{$mes_encoded}\")</script>";
}

function delete_first_str($str) {
  return trim( substr($str, 1, strlen($str)-1) );
}

function explode_comma($str) {
  $str = str_replace("\,", "^%$^", $str);
  $str = explode(",", $str);
  $str = str_replace("^%$^", ",", $str);
  return $str;
}

function is_value_exists($str) {
  if (empty($str)) {
    return false;
  }
  return mb_substr_count($str,":") === 1 && mb_substr_count($str,",") === 0 && mb_substr($str, -1) === ":";
}

function is_parent_exists($str) {
  if (empty($str)) {
    return false;
  }
  return mb_substr($str, 0, 1) === ",";
}

function count_brackets_pair($str) {
  return (mb_substr_count($str,"[") === mb_substr_count($str,"]"));
}

// 翻訳する関数群
function make_nest_text($notfound_array) {
  static $static_make_nest_text_key_count = 0;
  foreach ($notfound_array as $tmp_k=>$tmp_nf) {
    if (is_value_exists($tmp_nf) && !is_value_exists($notfound_array[$tmp_k+1])) {
      $tmp_c_text = "";
      unset($notfound_array[$tmp_k]);
      $notfound_array = array_values($notfound_array);
      for ($i=$tmp_k; count($notfound_array)>=$i; $i++) {
      //for ($i=$tmp_k+1; count($notfound_array)>$i; $i++) {
        $tmp_notfound_array_text = $notfound_array[$i];
        if (!is_parent_exists($tmp_notfound_array_text) && !is_value_exists($tmp_notfound_array_text)) {
          $tmp_c_text .= "[".$tmp_notfound_array_text."]";
        } else {
          $tmp_c_text .= $tmp_notfound_array_text;
        }
        unset($notfound_array[$i]);
        if (is_parent_exists($tmp_notfound_array_text)) {
          break;
        }
      }
      $kome_text = "※".$static_make_nest_text_key_count."※";
      $notfound_array[$tmp_k] = $kome_text;
      ksort($notfound_array);
      $notfound_array = array_values($notfound_array);
      $static_make_nest_text_key_count = $static_make_nest_text_key_count+1;
      return array($notfound_array, array($kome_text, $tmp_nf.$tmp_c_text));
    }
  }
}

function generate_meta_html($type, $value) {
  # set auto value
  if ($type === "charset" && $value === "auto") {
    $replace = "UTF-8";
  } else if ($type === "viewport" && $value === "auto") {
    $replace = "width=device-width,initial-scale=1.0,minimum-scale=1.0,maximum-scale=1.0,user-scalable=yes";
  } else {
    $replace = $value;
  }
  $html_templates = array(
    "charset" => "<meta http-equiv=\"Content-Type\" content=\"text/html; charset={$replace}\">",
    "viewport" => "<meta name=\"viewport\" content=\"{$replace}\">",
    "theme-color" => "<meta name=\"theme-color\" content=\"{$replace}\">",
    "description" => "<meta name=\"description\" content=\"{$replace}\">"
  );
  return $html_templates[$type];
}
