<?php

class export {
    public $imageIDList;
    
    public function exportDataToExcel () {
        $job = new job();
        $data = new data();
        
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->removeSheetByIndex(0);
        
        $n = 0;
        $header = $data->getExportHeader();
        
        $images = $job->getJobImagesForExport();
        
        foreach ($images as $i) {
            if (array_key_exists($i[ImageID], $_SESSION['data'])) {
                $data->imageID = $i[ImageID];
                $data->parentImageID = $data->getImageAnalyzeID();
                $data->setFilters();
                $respondentcount = $data->getRespondentCount();
                if ($respondentcount>0){
                    $myWorkSheet = new PHPExcel_Worksheet($objPHPExcel, substr($i[ImageName],0,3));
                    $objPHPExcel->addSheet($myWorkSheet, $n);
                    $objPHPExcel->getSheet($n);
                    $objPHPExcel->getSheet($n)->mergeCells('A1:F1');
                    $objPHPExcel->getSheet($n)->getStyle('A1')->getFont()->setBold(true);
                    $objPHPExcel->getSheet($n)->setCellValue('A1', 'GROUP ANALYSIS - SPEED OF NOTATION DATA TABLE');
                    $objPHPExcel->getSheet($n)->setCellValue('A3', 'Job #: '.$job->job);
                    $objPHPExcel->getSheet($n)->setCellValue('A4', $i[ImageName]." - ".$i[ImageDescription]);
                    $objPHPExcel->getSheet($n)->setCellValue('A6', $respondentcount." respondents");
                    $objPHPExcel->getSheet($n)->mergeCells('A7:C7');
                    $objPHPExcel->getSheet($n)->setCellValue('A7', "Average time on image: ".$_SESSION['times'][$i[ImageID]]." seconds");
                    $objPHPExcel->getSheet($n)->fromArray($header, '', 'A9');
                    $objPHPExcel->getSheet($n)->getStyle('A9:L9')->getFont()->setBold(true);

                    try {
                        $objPHPExcel->getSheet($n)->fromArray($_SESSION['data'][$i[ImageID]], '', 'A10');
                    } catch (\Exception $e) {
                        $objPHPExcel->getSheet($n)->fromArray(array('something went wrong!  Contact Marcus'), '', 'A10');
                    }
                    

                    for($col = 'A'; $col !== 'L'; $col++) {
                        $objPHPExcel->getSheet($n)
                            ->getColumnDimension($col)
                            ->setAutoSize(true);

                    }
                    $n++;
                }
            }
        }
        $objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
        header('Content-type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="ET_Data_'.$job->job.'.xlsx"');
        $objWriter->save('php://output');
    }
    
    public function exportMETDataToExcel(){
        $job = new job();
        $data = new data();
        
        $genHeader = array(array('GROUP ANALYSIS - SPEED OF NOTATION DATA TABLE'),array(''),array("Job #: ".$job->job));

        $objPHPExcel = new PHPExcel();
        $objPHPExcel->removeSheetByIndex(0);

        
        $images = $job->getJobImagesForExport();
        
        foreach ($images as $i) {
            if (array_key_exists($i[ImageID], $_SESSION['et']['data'])) {
                $data->imageID = $i[ImageID];
                $data->parentImageID = $data->getImageAnalyzeID();
                $data->setFilters();
                $respondentcount = $data->getRespondentCount();
                
                $key = key($_SESSION['et']['data'][$i[ImageID]]);
                $times = explode(" ", $key);
                $cData = $_SESSION['et']['data'][$i[ImageID]][$key];
                
                $imageHeader = array(array($i[ImageName]." - ".$i[ImageDescription]),array(),array("Respondent Count: ", $respondentcount),
                    array("Average time on image (Seconds): ",$_SESSION[et][data]['times'][$i[ImageID]]),
                    array("Average Number of Breaks Seen: ",$_SESSION[et][data]['breaks'][$i[ImageID]]),
                    array(),array(),array("Nets / Elements"));
 
                $headerStyle = array('alignment'=>array('horizontal'=>'center', 'vertical'=>'center', 'wrap'=>true), 'borders'=>array('outline'=>array('style'=>'medium')));
                $excel = array();
                foreach($cData as $c){
                    $eRow = array();
                    $timeText = '';
                    $eRow[] = $c[0];
                    foreach($times as $k=>$t){
                        $eRow[] = $this->getDataAt($c[1], $k);
                        $eRow[] = $this->getDataAt($c[2], $k);
                        $eRow[] = $this->getDataAt($c[3], $k);
                        $eRow[] = $this->getDataAt($c[4], $k);
                                            }
                    $eRow[] = $this->getDataAt($c[5], 0);
                    array_push($excel, $eRow);
                }

                if ($respondentcount>0){
                    //create the sheet and set name
                    $myWorkSheet = $objPHPExcel->createSheet();
                    $myWorkSheet->setTitle(substr($i[ImageName],0,32));
                    $myWorkSheet->getDefaultColumnDimension()->setWidth(13);
                    $myWorkSheet->getColumnDimension()->setWidth(25);
                    //write generic header info
                    $myWorkSheet->fromArray($genHeader, null, 'A1');
                    $myWorkSheet->getStyle('A6:A8')->applyFromArray(array('alignment'=>array('horizontal'=>'right', 'wrap'=>true)));
                    $myWorkSheet->getStyle('B6:B8')->applyFromArray(array('alignment'=>array('horizontal'=>'left', 'vertical'=>'center')));
                    
                    //write time headers and merge them
                    $col = 1;
                    foreach($times as $t){
                        $fCol = PHPExcel_Cell::stringFromColumnIndex($col);
                        $lCol = PHPExcel_Cell::stringFromColumnIndex($col+3);
                        //add time header and merge
                        $myWorkSheet->setCellValueByColumnAndRow($col, 10, ($t == 'Full' ? $t : $t.' Second'.($t == '1' ? '':'s')));
                        $myWorkSheet->mergeCellsByColumnAndRow($col, 10, $col+3, 10);
                        $myWorkSheet->fromArray(array("Noting Visit","Duration (ms)","Time to First Fixation (ms)","Brand Noted First"), null, $fCol.'11');
                        $myWorkSheet->getStyle($fCol.'10:'.$lCol.(count($excel)+11))->applyFromArray($headerStyle);
                        $col+=4;
                    }
                    $myWorkSheet->fromArray($imageHeader, null, 'A4');
                    $myWorkSheet->fromArray($excel, null, 'A12');
                    $lCol = PHPExcel_Cell::stringFromColumnIndex(count($times)*4+1);
                    $myWorkSheet->getStyle('A11')->applyFromArray($headerStyle);
                    $myWorkSheet->getStyle($lCol.'11')->applyFromArray($headerStyle);
                    $myWorkSheet->getCell($lCol.'11')->setValue("Brand AOI Coverage (%)");
                    $myWorkSheet->getStyle('A12:'.$lCol.(count($excel)+11))->applyFromArray(array('alignment'=>array('horizontal'=>'general', 'vertical'=>'general', 'wrap'=>false),
                                                                                                    'borders'=>array('outline'=>array('style'=>'medium'))));
                    if($_SESSION['et']['data'][$i[ImageID]][SL]){
                        $nextRow = $myWorkSheet->getHighestRow() + 4;
                        $myWorkSheet->fromArray(array(array('Shopper Lab Metrics'), array('Number of AOIs: ', $_SESSION['et']['data'][$i[ImageID]][ElementCount]), array(), array("Order", "AOIs", "Average", "Time to 1st Fixation", "Duration [%]", "Duration [ms]")), null, 'A'.$nextRow);
                        $myWorkSheet->fromArray($_SESSION['et']['data'][$i[ImageID]][SL], null, 'A'.($nextRow+4), true);
                        
                        $lastRow = $myWorkSheet->getHighestRow();
                        
                        $myWorkSheet->getStyle('A'.($nextRow+3).':A'.$lastRow)->applyFromArray(array('fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID,'color' => array('rgb' => 'FCD5B4'))));
                        $myWorkSheet->getStyle('B'.($nextRow+3).':B'.$lastRow)->applyFromArray(array('fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID,'color' => array('rgb' => 'B7DEE8'))));
                        $myWorkSheet->getStyle('C'.($nextRow+3).':C'.$lastRow)->applyFromArray(array('fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID,'color' => array('rgb' => 'E6B8B7'))));
                        $myWorkSheet->getStyle('C'.($nextRow+3).':C'.$lastRow)->getNumberFormat()->setFormatCode('0.0');
                        $myWorkSheet->getStyle('D'.($nextRow+3).':D'.$lastRow)->applyFromArray(array('fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID,'color' => array('rgb' => 'D8E4BC'))));
                        $myWorkSheet->getStyle('D'.($nextRow+3).':D'.$lastRow)->getNumberFormat()->setFormatCode('0.00');
                        $myWorkSheet->getStyle('E'.($nextRow+3).':E'.$lastRow)->applyFromArray(array('fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID,'color' => array('rgb' => 'CCC0DA'))));
                        $myWorkSheet->getStyle('E'.($nextRow+3).':E'.$lastRow)->getNumberFormat()->setFormatCode('0%');
                        $myWorkSheet->getStyle('F'.($nextRow+3).':F'.$lastRow)->applyFromArray(array('fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID,'color' => array('rgb' => 'D9D9D9'))));
                        $myWorkSheet->getStyle('F'.($nextRow+3).':F'.$lastRow)->getNumberFormat()->setFormatCode('0.00');
                    }
                }
            }
        }
        
        $objPHPExcel->setActiveSheetIndex(0);
        $objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
        header('Content-type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="MET_Data_'.$job->job.'.xlsx"');
        $objWriter->save('php://output');
    }

    public function exportIndividualDataToExcel() {
        $job = new job();
        $data = new data();
        $db = new mysqldb();

        $objPHPExcel = new PHPExcel();
        $objPHPExcel->removeSheetByIndex(0);

        $images = $job->getJobImagesForExport();

        $query = "select ETRespondentID, respondentNumber from et_respondents where jobNumber = '$job->job'";
        $respnum = $db->getData($query);

        $imageIds = implode(",", array_column($images, 'ImageID'));

        $query = "SELECT
            et_image_breaks.ImageID,
            et_image_breaks.BreakID,
            et_image_breaks.BreakDescription AS BreakName,
            et_image_nets.NetName,
            et_image_nets.NetID
        FROM
            et_image_nets
            INNER JOIN et_image_breaks ON ( et_image_nets.BreakID = et_image_breaks.BreakID AND et_image_nets.ImageID = et_image_breaks.ImageID ) 
        WHERE
            et_image_breaks.ImageID IN ($imageIds) 
        ORDER BY
            BreakDescription ASC";
        $elements = $db->getData($query);

        foreach ($respnum as $rn) {
            $respArray[$rn['ETRespondentID']] = $rn['respondentNumber'];
        }

        # Test with less image data
        #$images = array_slice($images, 0, 5);
    
        foreach ($images as $i) {
            $data->imageID = $i['ImageID'];
            $data->parentImageID = $data->getImageAnalyzeID();
            $data->setFilters();
            $respondentcount = $data->getRespondentCount();

            $id = "";
            $id = $data->runIndividualAnalysis();

            $ImageID = $i['ImageID'];
            $elementsFiltered = array_filter($elements, function ($var) use ($ImageID) {
                return ($var['ImageID'] == $ImageID);
            });

            $elementNetName = array_column($elementsFiltered, 'NetName');
            
            // Create nets associative array
            $nets = array();
            foreach($elementsFiltered as $k => $v)
                if (!array_key_exists($v['NetName'], $nets)) {
                    $nets[$v['NetName']][] = $v['BreakName'];
                } elseif (array_key_exists($v['NetName'], $nets)) {
                    $nets[$v['NetName']][] = $v['BreakName'];
                }
            #$this->console_log($nets);

            $tmp = array();
            foreach($elementsFiltered as $k => $v)
                $tmp[$k] = $v['BreakName'];

            // Find duplicate elements in temporary array
            $tmp = array_unique($tmp);
        
            // Remove the duplicates elements from original array
            foreach($elementsFiltered as $k => $v)  {
                if (!array_key_exists($k, $tmp))
                    unset($elementsFiltered[$k]);
            }

            if ($respondentcount>0){
                //create the sheet and set name
                $genHeader = array(array("Job #: ".$job->job),array(''),array($i['ImageName']));
                $myWorkSheet = $objPHPExcel->createSheet();
                $myWorkSheet->setTitle(substr($i['ImageName'],0,32));
                $myWorkSheet->fromArray($genHeader, null, 'A1');
                $myWorkSheet->getStyle('A3')->getFont()->setBold(true);
                $myWorkSheet->getStyle('A:XFD')->getAlignment()->setWrapText(true);

                $highestCol = $myWorkSheet->getHighestDataColumn();
                $a = 'CountNoting';

                $breakname_array = array('Resp ID');

                foreach ($elementsFiltered as $e) {
                    $breakname_array[] = $e['BreakName'];
                }

                // edit nets for columns
                $elementNetName = array_diff($elementNetName, array('No Net'));
                $prefixed_elementNetName = preg_filter('/^/', 'NET_', $elementNetName);

                // append NetNames
                $breakname_array = array_merge($breakname_array, array_unique($prefixed_elementNetName));
                $myWorkSheet->fromArray($breakname_array, null, 'A4');
                
                // create and set results
                $row = 5;
                foreach ($id['results'] as $resp => $d) {
                    $results_array[] = $respArray[$resp];

                    foreach ($elementsFiltered as $e) {
                        $results_array[] = ($id['results'][$resp][$e['BreakName']][$a] == "" ? "0" : $id['results'][$resp][$e['BreakName']][$a]);
                    }

                    // append net data to results
                    foreach (array_unique($elementNetName) as $n) {
                        foreach ($elementsFiltered as $e) {
                            if (($id['results'][$resp][$e['BreakName']][$a] == "1") and (in_array($e['BreakName'], $nets[$n]))) {
                                $results_array[] = "1";
                                continue 2;
                            }
                        }
                        $results_array[] = "0";
                    }

                    $myWorkSheet->fromArray($results_array, null, ('A' . $row));
                    $results_array = array();
                    $row++;
                }

                //set column width
                for($col = 'A'; $col !== $highestCol; $col++) {
                    $myWorkSheet->getColumnDimension($col)->setAutoSize(false);
                    $myWorkSheet->getColumnDimension($col)->setWidth(20);
                }
            }
        }
        $objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
        header('Content-type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="Indiv_ET_Data_'.$job->job.'.xlsx"');
        $objWriter->save('php://output');
    }

    public function console_log( $data ){
        echo '<script>';
        echo 'console.log('. $data .')';
        echo '</script>';
    }

    public function exportWizerDataToExcel($imageId) {
        $job = new job();
        $data = new data();
        $db = new mysqldb();

        include_once('PHPExcel.php');
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->removeSheetByIndex(0);

        $colNames = ['BreakDescription', 'Title', 'Price', 'Image_BreakID'];
    
        $query = "SELECT et_images.ImageName, et_image_breaks.BreakID, et_image_breaks.BreakDescription, wizer_json, et_image_breaks.Image_BreakID FROM et_image_breaks INNER JOIN et_images ON (et_image_breaks.ImageID = et_images.ImageID) WHERE et_image_breaks.ImageID = $imageId";
        $nets = $db->getData($query);

        $jobTest = $job->job;
    
        //create the sheet and set name
        $genHeader = ['Job #: '. $job->job, 'Image #: '. $imageId];
        $myWorkSheet = $objPHPExcel->createSheet();
        $myWorkSheet->setTitle(substr("Image - ".$nets[0]['ImageName'],0,32));
        $myWorkSheet->fromArray($genHeader, null, 'A1');
        $myWorkSheet->fromArray($colNames, null, 'A3');
        $myWorkSheet->getStyle('A3:C3')->getFont()->setBold(true);
        $myWorkSheet->getStyle('A:XFD')->getAlignment()->setWrapText(true);

        $highestCol = $myWorkSheet->getHighestDataColumn();
        $row = 4;

        foreach ($nets as $n) {
            $results_array = array($n['BreakDescription'], (json_decode($n['wizer_json'], true)['title']), (json_decode($n['wizer_json'], true)['price']), $n['Image_BreakID']);

            $myWorkSheet->fromArray($results_array, null, ('A' . $row));
            $results_array = array();
            $row++;
        }

    try {
        $myWorkSheet->getColumnDimension('A')->setWidth(20);
        $myWorkSheet->getColumnDimension('B')->setWidth(20);
        $myWorkSheet->getColumnDimension('C')->setWidth(15);
        $myWorkSheet->getColumnDimension('D')->setWidth(15);
        $myWorkSheet->getColumnDimension('D')->setVisible(false);

        $highestRow = $myWorkSheet->getHighestRow();
        $myWorkSheet->getStyle('A3:D'.$highestRow)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);

        $myWorkSheet->getProtection()->setSheet(true); 
        $myWorkSheet->getStyle('A4:C'.$highestRow)->getProtection()->setLocked(PHPExcel_Style_Protection::PROTECTION_UNPROTECTED);

        $objValidation = $myWorkSheet->getDataValidation();
        $objValidation->setType( PHPExcel_Cell_DataValidation::TYPE_DECIMAL );
        $objValidation->setErrorStyle( PHPExcel_Cell_DataValidation::STYLE_INFORMATION );
        $objValidation->setOperator( PHPExcel_Cell_DataValidation::OPERATOR_GREATERTHANOREQUAL);
        $objValidation->setAllowBlank(true);
        $objValidation->setShowInputMessage(true);
        $objValidation->setShowErrorMessage(true);
        $objValidation->setErrorTitle('Input error');
        $objValidation->setError('Only positive numeric entries are allowed.');
        $objValidation->setErrorStyle(PHPExcel_Cell_DataValidation::STYLE_STOP);
        $objValidation->setFormula1(0);
        $objValidation->setPromptTitle('Allowed input');
        $objValidation->setPrompt('Only positive entries are allowed.');
        $myWorkSheet->setDataValidation('C4:C'.$highestRow, $objValidation);
    
        $objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
        header('Content-type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="WizerExcel_'.$job->job . '_' . $nets[0]['ImageName'].'.xlsx"');
        $objWriter->save('php://output');
    } catch (Exception $e) {
        //$objPHPExcel->getSheet(0)->fromArray(array('something went wrong!  Contact Marcus'), '', 'A10');
        return array('error' => 1, 'message' => 'exportWizerDataToExcel no save');
    }

    }

    public function importWizerDataToExcel($imageId, $file) {
        if ($file['error'] != 0) {
            return array('error'=>1, 'message'=>'Error: There was an upload error: '.$file['error']);
        } else if ($file['type'] != 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') {
            return array('error'=>1, 'message'=>'Error: Not an .xlsx file: '.$file['type']);
        }

        $job = new job();
        $data = new data();
        $db = new mysqldb();

        $fileName = $file['tmp_name'];
        $check = is_uploaded_file($fileName);
        require_once 'PHPExcel/IOFactory.php';

        $excelReader = PHPExcel_IOFactory::createReaderForFile($fileName);
        $excelObj = $excelReader->load($fileName);
        $worksheet = $excelObj->getSheet(0);

        $query = "SELECT wizer_json FROM et_image_breaks INNER JOIN et_images ON (et_image_breaks.ImageID = et_images.ImageID) WHERE et_image_breaks.ImageID = $imageId";
        $nets = $db->getData($query);

        if ($fileName) {
            $objExcel = PHPExcel_IOFactory::load($fileName);
            foreach ($objExcel->getWorksheetIterator() as $worksheet) {
                $highestRow = $worksheet->getHighestRow();

                for ($row = 4; $row < ($highestRow + 1); $row++) {
                    $data = json_decode($nets[$row-4]['wizer_json'], true);
                    $data['title'] = $worksheet->getCellByColumnAndRow(1,$row)->getValue();
                    $data['price'] = $worksheet->getCellByColumnAndRow(2,$row)->getValue();
                    $data['title'] == null ? $data['title'] = "" : $data['title'] = $worksheet->getCellByColumnAndRow(1,$row)->getValue();
                    $data['price'] == null ? $data['price'] = 0 : $data['price'] = $worksheet->getCellByColumnAndRow(2,$row)->getValue();
                    $data['count'] = ($data['count'] ? array_key_exists('count', $data) : 0);
                    $data['tags'] = ($data['tags'] ? array_key_exists('tags', $data) : []);

                    $wizer_data = json_encode($data);

                    if ($worksheet->getCellByColumnAndRow(1,$row)->getValue() == null) {
                        $break_desc = $worksheet->getCellByColumnAndRow(0,$row)->getValue();   
                    $break_desc = $worksheet->getCellByColumnAndRow(0,$row)->getValue();
                        $break_desc = $worksheet->getCellByColumnAndRow(0,$row)->getValue();   
                    } else {
                        $break_desc = $worksheet->getCellByColumnAndRow(1,$row)->getValue();
                    }

                    $image_break_id = $worksheet->getCellByColumnAndRow(3,$row)->getValue();
                    

                    $db = new mysqldb();
                    $query = $db->prepare("UPDATE et_image_breaks SET BreakDescription = :BreakDescription, wizer_json = :wizer_json WHERE Image_BreakID = :Image_BreakID");
                    $query->execute(array("BreakDescription"=>$break_desc, "wizer_json"=>$wizer_data, "Image_BreakID"=>$image_break_id));
                }
            }

            return array('error'=>null, 'message'=>"Success: Breaks Updated");
        } else {
            return array('error'=>1, 'message'=>'Error: Could not update DB');
        }
    }

    private function getDataAt($string, $index, $delim = " "){
        $data = explode($delim, $string);
        return $index < count($data) ? $data[$index] : array_pop($data);
    }
    
    public function getExportInfo () {
        $job = new job();
        $db = new mysqldb();
        $query = "select u1.userFullName as analyst, u2.userFullName as analyst2, u3.userFullName as fieldSupervisor, u2.userPhone as fieldPhone, jobName, jobCountryID
            from jobdetails j 
            left join field f on j.jobnumber = f.jobnumber
            left join users u1 on j.jobAnalystID = u1.userID
            left join users u2 on j.jobAnalyst2ID = u2.userID
            left join users u3 on f.fieldManagerID = u3.userID
            where j.jobnumber = '".$job->getJobNumber(true).".00'";

        $userInfo = $db->getDataOne($query);

        return $userInfo;
    }

    public function getTestShelfList () {
        $job = new job();
        $db = new mysqldb();
        $query = "select imageName, imageDescription
            from et_images
            where jobnumber = '$job->job' and shelf=1
            order by imageName";

        $shelves = $db->getData($query);

        return $shelves;
    }
    
    public function getTestIndList () {
        $job = new job();
        $db = new mysqldb();
        $query = "select imageName, imageDescription
            from et_images
            where jobnumber = '$job->job' and shelf!=1
            order by imageName";

        $shelves = $db->getData($query);

        return $shelves;
    }
    
}
