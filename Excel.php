<?php
set_include_path(dirname(__FILE__) . '/../phpspreadsheet/vendor/');
require('autoload.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/* 
    SPIS FUNKCJONALNOŚCI

    > findTitleBar(array $headerNames, $win1250toUTF8, $returnAfterXHeadersFound)
        Szuka wiersza z nagłówkami.
        - Jako pierwszy parametr przyjmuje tablicę nazw szukanych nagłówków.
        - Jako trzeci parametr przyjmuje po jakiej ilości znalezionych nagłówków zwrócić indeks wiersza
           domyślnie szuka dopóki nie znajdzie wszystkich
           
        Zwraca indeks wiersza.
    
    > createXlsxFile(array $data, array $headers = [], $fileName = 'arkusz', $win1250toUTF8 = true)
        Generuje i wyświetla panel pobierania pliku XLSX
            - $data - tablica dwuwymiarowa zawierająca treść pliku
            - (opcjonalny) $headers - tablica jednowymiarowa zawierająca nagłówki
            - (opcjonalny) $fileName - nazwa pliku bez rozszerzenia
            - (opcjonalny)  

*/

class Excel
{
    private $workbook;
    private $spreadsheet;
    private $highestRow;
    private $highestColumn;

    public function __construct($file)
    {
        $this->workbook = IOFactory::load($file);
        $this->spreadsheet = $this->workbook->getActiveSheet();
        $this->highestRow = $this->spreadsheet->getHighestDataRow();
        $this->highestColumn = Coordinate::columnIndexFromString($this->spreadsheet->getHighestDataColumn());
    }


    # Pobierz cały arkusz w postaci tablicy
    public function getSpreadsheetValues(int $rowOffset = 0, int $columnOffset = 0, $returnInWin1250 = true, array $regex_replace_arr = null, bool $getFormattedValue = true) 
    {
        $values = array();
        $typeOfValue = ($getFormattedValue) ? "getFormattedValue" : "getValue";

        for ($currentRow = 1 + $rowOffset; $currentRow <= $this->highestRow; $currentRow++) {
            for ($currentColumn = 1 + $columnOffset; $currentColumn <= $this->highestColumn; $currentColumn++) {

                $currentCell = $this->spreadsheet->getCell(Coordinate::stringFromColumnIndex($currentColumn) . $currentRow)->$typeOfValue();

                if ($returnInWin1250) $values[$currentRow][$currentColumn] = iconv("UTF-8", "Windows-1250", trim($currentCell));
                else $values[$currentRow][$currentColumn] = trim($currentCell);

                if (!empty($regex_replace_arr)) {
                    foreach ($regex_replace_arr as $regex) {
                        $values[$currentRow][$currentColumn] = preg_replace($regex['pattern'], $regex['replace'], $values[$currentRow][$currentColumn]);
                    }
                }

            }
        }

        return (sizeof($values) > 0) ? $values : false;
    }


    # Pobierz cały arkusz w postaci tablicy
    public function getRawSpreadsheetValues(int $rowOffset = 0, int $columnOffset = 0, $returnInWin1250 = true, array $regex_replace_arr = null, bool $getFormattedValue = true) 
    {
        $values = array();
        $typeOfValue = ($getFormattedValue) ? "getFormattedValue" : "getValue";

        for ($currentRow = 1 + $rowOffset; $currentRow <= $this->highestRow; $currentRow++) {
            for ($currentColumn = 1 + $columnOffset; $currentColumn <= $this->highestColumn; $currentColumn++) {

                $currentCell = $this->spreadsheet->getCell(Coordinate::stringFromColumnIndex($currentColumn) . $currentRow)->getValue();

                if ($returnInWin1250) $values[$currentRow][$currentColumn] = iconv("UTF-8", "Windows-1250", trim($currentCell));
                else $values[$currentRow][$currentColumn] = trim($currentCell);

                if (!empty($regex_replace_arr)) {
                    foreach ($regex_replace_arr as $regex) {
                        $values[$currentRow][$currentColumn] = preg_replace($regex['pattern'], $regex['replace'], $values[$currentRow][$currentColumn]);
                    }
                }

            }
        }

        return (sizeof($values) > 0) ? $values : false;
    }

    # Pobierz wartości z kolumny podając nazwę kolumny oraz numer wiersza z tytułami z formatowaniem
    public function getColumnValues($columnName, $titleRow)
    {
        $columnName = iconv("Windows-1250", "UTF-8", $columnName);
        $columnName = mb_strtoupper($columnName);

        $values = array();

        $targetColumn = $this->isTitle($columnName, $titleRow, false);

        if ($targetColumn !== false) {
            $iterator = 0;

            for ($currentRow = $titleRow + 1; $currentRow <= $this->highestRow; $currentRow++) {
                $currentCell = $this->spreadsheet->getCell(Coordinate::stringFromColumnIndex($targetColumn) . $currentRow)->getFormattedValue();

                $values[$iterator] = iconv("UTF-8", "Windows-1250", trim($currentCell));
                $iterator++;

            }

            return (sizeof($values) > 0) ? $values : false;

        } else {
            return false;
        }

    }
        # Pobierz wartości z kolumny podając nazwę kolumny oraz numer wiersza z tytułami z formatowaniem
        public function getRawColumnValues($columnName, $titleRow)
        {
            $columnName = iconv("Windows-1250", "UTF-8", $columnName);
            $columnName = mb_strtoupper($columnName);
    
            $values = array();
    
            $targetColumn = $this->isTitle($columnName, $titleRow, false);
    
            if ($targetColumn !== false) {
                $iterator = 0;
    
                for ($currentRow = $titleRow + 1; $currentRow <= $this->highestRow; $currentRow++) {
                    $currentCell = $this->spreadsheet->getCell(Coordinate::stringFromColumnIndex($targetColumn) . $currentRow)->getValue();
    
                    $values[$iterator] = iconv("UTF-8", "Windows-1250", trim($currentCell));
                    $iterator++;
    
                }
    
                return (sizeof($values) > 0) ? $values : false;
    
            } else {
                return false;
            }
    
        }
    
    
    # Pobierz wartości z kolumny podając indeks kolumny oraz numer wiersza z tytułami z formatowaniem
    public function getColumnValuesByIndex($columnIndex, $titleRow)
    {
        $values = array();
        $iterator = 0;

        for ($currentRow = $titleRow + 1; $currentRow <= $this->highestRow; $currentRow++) {
            $currentCell = $this->spreadsheet->getCell(Coordinate::stringFromColumnIndex($columnIndex) . $currentRow)->getFormattedValue();

            //IGNORE wycina wszystkie znaki-krzaki które nie mogą być przetłumaczone na windows-1250 bez wyrzucania notice
            $values[$iterator] = iconv("UTF-8", "Windows-1250//IGNORE", trim($currentCell)); 
            $iterator++;

        }

        return (sizeof($values) > 0) ? $values : false;
    }
    
    
    # Pobierz wartości z kolumny podając indeks kolumny oraz numer wiersza z tytułami bez formatowania
    public function getRawColumnValuesByIndex($columnIndex, $titleRow)
    {
        $values = array();
        $iterator = 0;

        for ($currentRow = $titleRow + 1; $currentRow <= $this->highestRow; $currentRow++) {
            $currentCell = $this->spreadsheet->getCell(Coordinate::stringFromColumnIndex($columnIndex) . $currentRow)->getValue();

            $values[$iterator] = iconv("UTF-8", "Windows-1250", trim($currentCell));
            $iterator++;

        }

        return (sizeof($values) > 0) ? $values : false;
    }

    public function getRawColumnValuesByIndex1($columnIndex, $titleRow)
    {
        $values = array();
        $iterator = 0;

        for ($currentRow = $titleRow + 1; $currentRow <= $this->highestRow; $currentRow++) {
            $currentCell = $this->spreadsheet->getCell(Coordinate::stringFromColumnIndex($columnIndex) . $currentRow)->getValue();
            $values[$iterator] = iconv("UTF-8", "ISO-8859-2", trim($currentCell));
            $iterator++;

        }

        return (sizeof($values) > 0) ? $values : false;
    }


    # Pobierz wszystkie wartości w wierszu - zwraca tablicę dwuwymiarową (zawartość komórki, indeks kolumny)
    public function getRow($selectedRow) {
        $values = array();
        
        for ($currentColumn = 1; $currentColumn <= $this->highestColumn; $currentColumn++) {
            $currentCellValue = $this->spreadsheet->getCell(Coordinate::stringFromColumnIndex($currentColumn) . $selec)->getFormattedValue();
            array_push($values, [iconv("UTF-8", "Windows-1250", trim($currentCellValue)), $currentColumn]);
        }
        return (sizeof($values) > 0) ? $values : false;
    }

    # Pobierz wartość wskazanej komórki
    public function getCellValue(int $rowIndex, int $columnIndex, bool $getFormattedValue = true) {
        if ($getFormattedValue) return $this->spreadsheet->getCell(Coordinate::stringFromColumnIndex($columnIndex) . $rowIndex)->getFormattedValue();
        else return $this->spreadsheet->getCell(Coordinate::stringFromColumnIndex($columnIndex) . $rowIndex)->getValue();
    }

    
    # Znajdź wiersz z nagłówkami
    public function findTitleBar(array $headerNames, $win1250toUTF8, $returnAfterXHeadersFound = null) {
        $totalHeadersToFind = count($headerNames);
        $totalHeadersFound = 0;

        for ($i = 0; $i < count($headerNames); $i++) {
            if ($win1250toUTF8) $headerNames[$i] = iconv("Windows-1250", "UTF-8", $headerNames[$i]);
            $headerNames[$i] = trim(mb_strtoupper($headerNames[$i]));
        }

        for ($currentRow = 1; $currentRow <= $this->highestRow; $currentRow++) {

            for ($currentColumn = 1; $currentColumn <= $this->highestColumn; $currentColumn++) {

                foreach ($headerNames as $headerName) {
                    $currentCell = $this->spreadsheet->getCell(Coordinate::stringFromColumnIndex($currentColumn) . $currentRow)->getFormattedValue();
                    $currentCell = trim(mb_strtoupper($currentCell));

                    if ($currentCell == $headerName) $totalHeadersFound++;
                    if (!empty($returnAfterXHeadersFound) && $returnAfterXHeadersFound == $totalHeadersFound) return $currentRow;
                    elseif ($totalHeadersFound == $totalHeadersToFind) return $currentRow;
                }

            }

        }

        return false;
    }


    # Znajdź wartość ze słownika w wierszu
    public function findDictionaryValueInRow($selectedRow, $valuesArray)
    {
        foreach ($valuesArray as $value) {
            $value = iconv("Windows-1250", "UTF-8", $value);
            $value = mb_strtoupper($value);

            $found = $this->isTitle($value, $selectedRow, false);

            if($found != false) {
                return $this->getColumnValuesByIndex($found, $selectedRow);
                break;
            }
        }

        return false;
    }


    # Sprawdź czy istnieje kolumna o podanym tytule
    public function isTitle($columnName, $titleRow, $win1250toUTF8)
    {
        if ($win1250toUTF8) {
            $columnName = iconv("Windows-1250", "UTF-8", $columnName);
            $columnName = trim(mb_strtoupper($columnName));
        }

        for ($currentColumn = 1; $currentColumn <= $this->highestColumn; $currentColumn++) {
            $currentCell = $this->spreadsheet->getCell(Coordinate::stringFromColumnIndex($currentColumn) . $titleRow)->getFormattedValue();
            $currentCell = trim(mb_strtoupper($currentCell));

            if ($currentCell == $columnName) {
                return $currentColumn;
                break;
            }
        }

        return false;
    } 

    # Stwórz i wyświetl ekran pobierania pliku XLSX - należy wywołać w pustej karcie
    public static function createXlsxFile(array $data, array $headers = [], $fileName = 'arkusz', bool $win1250toUTF8 = true, $path = null)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        # Uzupełnij nagłówek
        for ($currentColumn = 0; $currentColumn < sizeof($headers); $currentColumn++) {
            if ($win1250toUTF8) $headers[$currentColumn] = iconv("Windows-1250", "UTF-8", $headers[$currentColumn]);
            $sheet->setCellValueByColumnAndRow($currentColumn + 1, 1, $headers[$currentColumn]);
            $sheet->getColumnDimension(Excel::numericIndexToAlphabeticIndex($currentColumn + 1))->setAutoSize(true);
        }

        $styleArray = [
            'font' => [
                'bold' => true
            ],
        ];
        
        $sheet->getStyle('A1:'.Excel::numericIndexToAlphabeticIndex(sizeof($headers)).'1')->applyFromArray($styleArray);
        
        # Uzupełnij dane
        $currentRow = 0;
        foreach ($data as $key => $row) {
            $currentColumn = 0;
            if (isset($row[6])){  
              if($row[6] instanceof DateTime) $row[6] = $row[6]->format('Y-m-d H:i:s');
            }
            foreach ($row as $row_key => $value) {
                  if ($win1250toUTF8) $value = iconv("Windows-1250", "UTF-8", $value);
                  $sheet->setCellValueByColumnAndRow($currentColumn + 1, ($currentRow + 1 + 1), $value);
                  $currentColumn++;
            }
            $currentRow++;
        }
        
        if (empty($path)) {
            # Wyświetl panel pobierania pliku
            ob_clean(); // Wyczyść wysłane wcześniej nagłówki aby uniknąć uszkodzenia pliku
            $writer = new Xlsx($spreadsheet);
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="'. urlencode($fileName.'.xlsx').'"');
            $writer->save('php://output');
        } else {
            # Zapisz plik w zdefiniowanej ścieżce
            // ob_clean(); // Wyczyść wysłane wcześniej nagłówki aby uniknąć uszkodzenia pliku
            $writer = new Xlsx($spreadsheet);
            // $writer->save($path.'/'.$fileName.'.xlsx'); // TODO:
            $writer->save($fileName.'.xlsx');
        }
        exit();
    }


    # Zamień liczbę na indeks alfabetyczny (np: 1=A, 2=B, 26=Z, 28=AB) 
    public static function numericIndexToAlphabeticIndex(int $numeric_index)
    {
        if ($numeric_index == 0) return false;

        $numeric_index -= 1;
        for($alphabetic_index = ""; $numeric_index >= 0; $numeric_index = intval($numeric_index / 26) - 1)
            $alphabetic_index = chr($numeric_index%26 + 0x41) . $alphabetic_index;

        return $alphabetic_index;
    }

}