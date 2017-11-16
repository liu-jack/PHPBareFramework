<?php

namespace Model\Test;

class Data
{

    /**
     * 导出excel数据表
     *
     * @author zhoujf
     */
    public function exportExcel($list)
    {
        set_time_limit(0);
        // //导出 .xls 格式
        $filename = mb_convert_encoding('测试数据-' . date("YmdHis"), "GB2312", "UTF-8");
        $filename_type = 'xls';
        header("Content-type: application/vnd.ms-excel");
        Header("Accept-Ranges:bytes");
        Header("Content-Disposition:attachment;filename=" . $filename . "." . $filename_type);  //$filename导出的文件名
        header("Pragma: no-cache");
        header("Expires: 0");
        $excel_str = '';
        if ($filename_type == 'xls') {
            $excel_str = <<<EOT
            <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
             <head>
              <meta http-equiv="expires" content="Mon, 06 Jan 1999 00:00:01 GMT">
              <meta http-equiv=Content-Type content="text/html; charset=gb2312">
              <!--[if gte mso 9]><xml>
              <x:ExcelWorkbook>
              <x:ExcelWorksheets>
                <x:ExcelWorksheet>
                <x:Name></x:Name>
                <x:WorksheetOptions>
                  <x:DisplayGridlines/>
                </x:WorksheetOptions>
                </x:ExcelWorksheet>
              </x:ExcelWorksheets>
              </x:ExcelWorkbook>
              </xml><![endif]-->
             </head>
EOT;
        }
        // style=""
        //1） 文本：vnd.ms-excel.numberformat:@
        //2） 日期：vnd.ms-excel.numberformat:yyyy/mm/dd
        //3） 数字：vnd.ms-excel.numberformat:#,##0.00
        //4） 货币：vnd.ms-excel.numberformat:￥#,##0.00
        //5） 百分比：vnd.ms-excel.numberformat: #0.00%
        $excel_str .= '<table>';
        $excel_str .= '<tr><td>ID</td><td>备 注</td></tr>';
        foreach ($list as $k => $v) {
            $excel_str .= '<tr><td>' . $v . '</td><td>&nbsp</td></tr>';
        }
        $excel_str .= '</table>';
        //echo mb_convert_encoding($excel_str,"GB2312","UTF-8");
        file_put_contents('x.xls', mb_convert_encoding($excel_str, "GB2312", "UTF-8"));
        exit();
        /*  //导出 csv 格式
               $excel_str = '"ID","来宾姓名","性 别","学 历","单 位","科 室","职 称","职 务","手 机 号 码","是否我科进修医生","地 区","邮 编","入住天数"," 身 份 证 号 ","添 加 时 间","用户来源","备 注",';
               $excel_str .= "\n";
               $sex_tran = array('1'=>'男','2'=>'女');
               $is_study_tran = array('1'=>'是','0'=>'否');
               $ufrom_tran = array('1'=>'前端录入','2'=>'后台录入','3'=>'扫码录入');
               foreach ($list as $k=>$v){
                   $excel_str .= '"' . $v["id"] . '","' . $v["username"] . '","' . $sex_tran[$v["sex"]] . '","' . $v["education"] . '","' . $v["unit"] . '","' . $v["section"] . '","' . $v["title"] . '","' . $v["duty"] . '",' . $v["mobile"] . ',"' . $is_study_tran[$v["is_study"]] . '","' . $v["area"] . '","' . $v["zipcode"] . '","' . $v["staydays"] . '","' . $v["idcode"] . '&nbsp","' . date('Y-m-d H:i:s',$v["addtime"]) . '","' . $ufrom_tran[$v["ufrom"]] . '"," "'. "\n";
               }
               //echo mb_convert_encoding($excel_str,"GB2312","UTF-8");die;
               header("Content-type:application/octet-stream");
               header("Accept-Ranges:bytes");
               header('Content-type: application/vnd.ms-excel');
               header('Content-Disposition: attachment;filename="' . mb_convert_encoding('来宾报名数据', "GB2312", "UTF-8") . '(' . date('YmdHis') . ').csv"');
               header('Cache-Control: max-age=0');
               header("Expires: 0");
               echo mb_convert_encoding($excel_str,"GB2312","UTF-8");
               exit();
         */
    }

}