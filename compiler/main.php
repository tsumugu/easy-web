<?php
require_once("functions.php");

function generate_html($project_dir, $strc_file_path, $contents_file_path) {
  //strcファイルを解析して構造を生成
  $strc_file_lines = file($strc_file_path);
  $analyzed_strc_file_lines = analyze_strc_lines($strc_file_lines);
  //contentsを取得, 解析
  $contents_file_lines = file($contents_file_path);
  $analyzed_contents_file_lines = analyze_contents_lines($contents_file_lines);
  //atrcファイルをcontentsで置き換え
  $strc_replaced = generate_replace_strc($project_dir, $analyzed_strc_file_lines, $analyzed_contents_file_lines);
  return $strc_replaced;
}

function analyze_strc_lines($lines) {
  $strc_array = array();
  foreach ($lines as $l) {
    if (substr($l, 0, 1) === '-'){
      $strc_array[] = $l;
    }
  }
  return $strc_array;
}

function analyze_contents_lines($lines) {
  $contents_array = array();
  $latest_key = "";
  foreach ($lines as $l) {
    $l = trim($l);
    $substr_l = mb_substr($l, 0, 1);
    if ($substr_l === '-'){
      $latest_key = delete_first_str($l);
    } else if ($substr_l === '#'){
      //comment line
    } else {
      $contents_array[$latest_key][] = $l;
    }
  }
  return $contents_array;
}

function generate_replace_strc($project_dir, $strc_array, $contents_array) {
  $ret_str = "";
  foreach ($strc_array as $strc_part_name) {
    $del_first_str = delete_first_str($strc_part_name);
    $contents = $contents_array[$del_first_str];
    if (empty($contents)) {
      //別ファイルの呼び出しだったら
      $tmp_exp = explode(".", $del_first_str);
      $other_contents_file_path = $project_dir.$tmp_exp[0].".contents.ew";
      if (file_exists($other_contents_file_path)) {
        $analyzed_other_contents_file_lines = analyze_contents_lines(file($other_contents_file_path));
        //$del_first_str_other = delete_first_str($tmp_exp[1]);
        $del_first_str_other = $tmp_exp[1];
        $other_contents = $analyzed_other_contents_file_lines[$del_first_str_other];
        if (!empty($other_contents)) {
          $other_contents = array_filter($other_contents, "strlen");
          $other_contents = array_values($other_contents);
          //-head, --scriptなどの特殊だった場合
          if (substr($del_first_str_other, 0, 1) === "-") {
            $tag = delete_first_str($del_first_str_other);
            $ret_str .= "<{$tag}>".implode("",$other_contents)."</{$tag}>";
          } else {
            $ret_str .= implode("",$other_contents);
          }
        } else {
          disp_error("warn", "未設定のcontentsがあります -> {$strc_part_name} ({$del_first_str_other})");
        }
      }
    } else {
      $contents = array_filter($contents, "strlen");
      $contents = array_values($contents);
      //-head, --scriptなどの特殊だった場合
      if (substr($del_first_str, 0, 1) === "-") {
        $tag = delete_first_str($del_first_str);
        $ret_str .= "<{$tag}>".implode("",$contents)."</{$tag}>";
      } else {
        $ret_str .= implode("",$contents);
      }
    }
  }
  return analyze_texts($ret_str, $project_dir);
}

function generate_components_array($html_components_matches) {
  $components_array = array();
  $tmp_exp_count=0;
  foreach ($html_components_matches as $tmp_html_component) {
    $tmp_exploded_components = trim($tmp_html_component, "[]");
    $tmp_exploded_components = explode_comma($tmp_exploded_components);
    $type = "";
    foreach ($tmp_exploded_components as $key=>$tmp_component) {
      //var_dump($key, $tmp_component);
      $tmp_component = trim($tmp_component);
      //引数3 で分割リミット
      $tmp_exploded_component = explode(":", $tmp_component, 2);
      if ($key === 0) {
        $type = trim($tmp_exploded_component[0]);
        if (!empty($tmp_exploded_component[1])) {
          $tmp_exp_count+=1;
          $components_array[$type."%".$tmp_exp_count]["replace_text"] = "[".trim($tmp_html_component, "[]")."]";
          $components_array[$type."%".$tmp_exp_count]["value"] = trim($tmp_exploded_component[1]);
        } else {
          disp_error("warn", "valueが空です. -> {$tmp_html_component}");
          //continue;
        }
      } else {
        $components_array[$type."%".$tmp_exp_count][trim($tmp_exploded_component[0])] = trim($tmp_exploded_component[1]);
      }
    }
  }
  return $components_array;
}

function generate_have_child_com_array($notfound_array) {
  $ret_arr = array();
  //
  foreach ($notfound_array as $nf_val_arr) {
    $replace_text_arg = $nf_val_arr[0];
    $nf_val = $nf_val_arr[1];
    $tmp_nf_explode_array = preg_split('/(\[|\])/',$nf_val);
    $tmp_nf_explode_array = array_filter($tmp_nf_explode_array, "strlen");
    $tmp_nf_explode_array = array_values($tmp_nf_explode_array);
    //var_dump($tmp_nf_explode_array);
    //適当な文字で置いて最後に置き換え
    $before_return = "";
    $nest_replaced_fin_str = "";
    $nest_replace_array = array();
    for ($i=0;$i<500;$i++) {
      if (empty($before_return)) {
        $before_return = $tmp_nf_explode_array;
      }
      $tmp_mnt_ret_arr = make_nest_text($before_return);
      $tmp_mnt_ret = $tmp_mnt_ret_arr[0];
      if (is_null($before_return) || is_null($tmp_mnt_ret)) {
        $fin_nest_replace_array_key = count($nest_replace_array)-1;
        $fin_nest_replace_array = $nest_replace_array[$fin_nest_replace_array_key];
        if (!empty($fin_nest_replace_array)) {
          $nest_replaced_fin_str = $fin_nest_replace_array[1];
          unset($nest_replace_array[$fin_nest_replace_array_key]);
        }
        break;
      }
      $nest_replace_array[] = $tmp_mnt_ret_arr[1];
      $before_return = $tmp_mnt_ret;
    }
    //
    $nest_replace_text = "[".$replace_text_arg."]";
    $nest_replace_array_2 = array();
    foreach ($nest_replace_array as $nest_content) {
      //$nest_content[1]をhtmlに置き換えする
      preg_match_all("/(\[.*?\])/", $nest_content[1], $nest_content_matches);
      $tmp_components_array = generate_components_array($nest_content_matches[0]);
      $tmp_rep_text = generate_replace_and_generate_html($nest_content[1], $tmp_components_array);
      $tmp_components_array_2 = generate_components_array(array("[".$tmp_rep_text."]"));
      $tmp_rep_text_2 = generate_replace_and_generate_html("[".$tmp_rep_text."]", $tmp_components_array_2);
      $nest_replace_array_2[] = array($nest_content[0], $tmp_rep_text_2);
    }
    $tmp_nest_replaced_fin_str_gen = generate_components_array(array("[".$nest_replaced_fin_str."]"));
    $tmp_rep_nest_replaced_fin_str = generate_replace_and_generate_html("[".$nest_replaced_fin_str."]", $tmp_nest_replaced_fin_str_gen);
    $nest_replace_array_2[] = array("fin", $tmp_rep_nest_replaced_fin_str);
    $replace_result_text = "";
    foreach ($nest_replace_array_2 as $tmp_ns_rep_key=>$ns_rep) {
      if ($tmp_ns_rep_key === 0) {
        $replace_result_text = str_replace("[".$nest_replace_array_2[$tmp_ns_rep_key][0]."]", $nest_replace_array_2[$tmp_ns_rep_key][1], $nest_replace_array_2[$tmp_ns_rep_key+1][1]);
      } else {
        $replace_result_text = str_replace("[".$nest_replace_array_2[$tmp_ns_rep_key][0]."]", $replace_result_text, $nest_replace_array_2[$tmp_ns_rep_key+1][1]);
      }
      if ($nest_replace_array_2[$tmp_ns_rep_key+1][0] === "fin") {
        break;
      }
    }
    //$replace_result_text
    $ret_arr[] = array(
      "replace_text"=>$nest_replace_text,
      "replace_html"=>$replace_result_text
    );
  }
  return $ret_arr;
}

function generate_replace_and_set_template($str, $project_dir) {
  $ret = null;
  if (mb_substr($str,0,9) !== "template.") {
    return $ret;
  }
  $body = mb_substr($str,9,mb_strlen($str));
  $exploded = explode_comma($body);
  $tmp_exploded_component = explode(":", $exploded[0], 2);
  $template_file_path = $project_dir.$tmp_exploded_component[0].".template.ew";
  unset($exploded[0]);
  if (!file_exists($template_file_path)) {
    disp_error("warn","template file not found -> {$template_file_path}");
    return $ret;
  }
  $exp_exploded = array();
  foreach ($exploded as $e) {
    $e = trim($e);
    $exploded_component = explode(":", $e, 2);
    if (!empty($exploded_component[0])) {
      $exp_exploded[$exploded_component[0]] = $exploded_component[1];
    }
  }
  //var_dump($exp_exploded);
  //1. ファイル読み込む
  $template_file_lines = file($template_file_path);
  $analyzed_template_file_lines = analyze_contents_lines($template_file_lines);
  $template_str = "";
  foreach ($analyzed_template_file_lines as $temp) {
    if (!empty($temp[0])) {
      $template_str = $temp[0];
      break;
    }
  }
  if (empty($template_str)) {
    return $ret;
  }
  //2. 値を代入
  foreach ($exp_exploded as $k=>$v) {
    $template_str = str_replace("{{$k}}", $v, $template_str);
  }
  //3. 返却
  return $template_str;
}

function generate_replace_and_generate_html($str, $components_array) {
  foreach ($components_array as $type=>$component) {
    $tmp_type_exp = explode("%",$type);
    $type = $tmp_type_exp[0];
    $value = "";
    $prms = "";
    $replace_text = $component["replace_text"];
    unset($component["replace_text"]);
    //var_dump($type);
    if ($type === "func") {
      unset($component["value"]);
      $new_html_component = "";
      //JSだったら
      foreach ($component as $tmp_k =>$tmp_c) {
        $new_html_component .= "function {$tmp_k} { {$tmp_c} }";
      }
    } else if ($type === "meta-s") {
      $meta_exp = explode("=",$component["value"]);
      $rep_html = generate_meta_html($meta_exp[0], $meta_exp[1]);
      $str = str_replace($replace_text, $rep_html, $str);
      //echo "<hr>";
    } else {
      //htmlだったら
      foreach ($component as $tmp_k =>$tmp_c) {
        if ($tmp_k === "value") {
          $value = $tmp_c;
        } else {
          $prms .= " {$tmp_k}=\"{$tmp_c}\"";
        }
      }
      // no value
      if (strlen($value)===0 || $value === "-" || $value === "-nv" || $value === "-novalue" || $value === "-no-value") {
        $new_html_component = "<{$type}{$prms}></{$type}>";
      } else if ($value === "-nc" || $value === "-noclose" || $value === "-no-close") {
        $new_html_component = "<{$type}{$prms}>";
      } else {
        $new_html_component = "<{$type}{$prms}>{$value}</{$type}>";
      }
    }
    $str = str_replace($replace_text, $new_html_component, $str);
    //var_dump($new_html_component);
    //echo "<hr>";
  }
  return $str;
}

function analyze_texts($str, $project_dir) {
  //コンポーネントを解析
  $str = str_replace(array("\r\n", "\r", "\n"), "", $str);
  preg_match_all("/(\[.*?\])/", $str, $html_components_matches);
  $str_first_pos = mb_strpos($str, "[");
  $str_deleted = mb_substr($str, ($str_first_pos+1), mb_strlen($str));
  $str_end_pos = mb_strrpos($str_deleted, "]");
  $str_deleted = mb_substr($str_deleted , 0, -(mb_strlen($str_deleted)-($str_end_pos)));
  $tmp_hcm = explode("][", $str_deleted);
  $html_components_matches = array();
  foreach ($tmp_hcm as $tmp_hcm_k=>$tmp_hcm_c) {
    //var_dump($tmp_hcm_c);
    if (count_brackets_pair($tmp_hcm_c)) {
      //[]がまだあったら
      if(preg_match('/(\[|\])/',$tmp_hcm_c)){
        $tmp_exp_hcm = preg_split('/(\[|\])/',$tmp_hcm_c);
        foreach ($tmp_exp_hcm as $tmp_tec) {
          //
          $rep_and_set_template = generate_replace_and_set_template($tmp_tec, $project_dir);
          if (is_null($rep_and_set_template)) {
            $html_components_matches[] = $tmp_tec;
          } else {
            $html_components_matches[] = $rep_and_set_template;
            //replace
            $str = str_replace($tmp_tec, $rep_and_set_template, $str);
          }
          //
        }
      } else {
        //
        $rep_and_set_template = generate_replace_and_set_template($tmp_hcm_c, $project_dir);
        if (is_null($rep_and_set_template)) {
          $html_components_matches[] = $tmp_hcm_c;
        } else {
          $html_components_matches[] = $rep_and_set_template;
          //replace
          $str = str_replace($tmp_hcm_c, $rep_and_set_template, $str);
        }
        //
      }
    } else {
      //数が合うまで足していく
      $tmp_kakko_plus = "[".$tmp_hcm[$tmp_hcm_k]."]";
      $tmp_kakko_count = 1;
      while (true) {
        $tmp_kakko_plus .= "[".$tmp_hcm[$tmp_hcm_k+$tmp_kakko_count]."]";
        unset($tmp_hcm[$tmp_hcm_k+$tmp_kakko_count]);
        $tmp_kakko_count++;
        if (count_brackets_pair($tmp_kakko_plus)) {
          $tmp_kakko_plus = str_replace("[]", "", $tmp_kakko_plus);
          $tmp_kakko_plus = trim($tmp_kakko_plus, "[]");
          if (!empty($tmp_kakko_plus)) {
            $html_components_matches[] = $tmp_kakko_plus;
          }
          break;
        }
      }
    }
  }
  //
  $notfound_array = array();
  foreach ($html_components_matches as $tmp_hcm_k_nf=>$hcm) {
    if(preg_match('/(\[|\])/',$hcm)){
      unset($html_components_matches[$tmp_hcm_k_nf]);
      $tmp_num = count($notfound_array);
      $tmp_text = "@rep-{$tmp_num}-@";
      $str = str_replace("[".$hcm."]", "[{$tmp_text}]", $str);
      $html_components_matches[$tmp_hcm_k_nf] = $tmp_text;
      $notfound_array[] = array($tmp_text,$hcm);
    }
  }
  ksort($html_components_matches);
  //var_dump($html_components_matches, $notfound_array);
  //TODO: $notfound_arrayのreplaceがちがう
  //[template]を[@rep-{$tmp_num}-@]に

  //html生成、置き換え
  $components_array = generate_components_array($html_components_matches);
  $str = generate_replace_and_generate_html($str, $components_array);
  //debug
  //echo htmlspecialchars($str);
  //$nest_replace_textを置き換える
  $replace_result_array = generate_have_child_com_array($notfound_array);
  foreach ($replace_result_array as $rep_res) {
    $str = str_replace($rep_res["replace_text"], $rep_res["replace_html"], $str);
  }
  //改行をbrタグに置き換え
  $str = str_replace("\n", "<br>", $str);
  return $str;
}
