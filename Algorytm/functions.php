<?php
function oblicz_ocs($cs_domyslna, $cena_min, $dominanta, $srednia_cena_konkurencja, $mediana, $cena_max, $ilosc_wys, &$komunikat, $czb, $ilosc_wys_min) {
    if ($ilosc_wys == 0)
    {
        $ocs = $cs_domyslna * 1.1;
        $komunikat = "OCS policzone jako 110% domyślnej ceny sprzedaży";
    } 
    elseif ($ilosc_wys >= 1 && $ilosc_wys <= 3) {
        if ($cs_domyslna > $cena_min && $cs_domyslna < $cena_max)
        {
            $ocs = $cs_domyslna;
            $komunikat = "OCS policzone jako cena domyślna. Mieści się między min a max <1,3>";
        }
        elseif($cs_domyslna <= $cena_min)
        {
            $ocs = $cena_min;
            $komunikat = "OCS policzone jako cena_min <1,3>";
        }
        elseif($cs_domyslna >= $cena_max)
        {
            if($czb <= $cena_max)
            {
                $ocs = $cena_max;
                $komunikat = "OCS policzone jako cena max <1,3>";
            }
            else
            {
                $ocs = $czb;
                $komunikat = "OCS policzone jako czb <1,3>";
            }
        }
    }
    else {
        if ($dominanta !== null)
        {
            if($cs_domyslna <= $cena_min)
            {
                $prop_minimalnej = $ilosc_wys_min / $ilosc_wys;
                if($prop_minimalnej >= 0.3)
                {
                    $ocs = $cena_min;
                    $komunikat = "OCS policzone jako cena_min <4,∞> (dominanta)";
                }
                elseif($srednia_cena_konkurencja > $dominanta){
                    $ocs = $dominanta;
                    $komunikat = "OCS policzone jako dominanta <4,∞> (dominanta)";
                }
                else{
                    $ocs = $srednia_cena_konkurencja;
                    $komunikat = "OCS policzone jako sr_cena <4,∞> (dominanta)";
                }  
            }
            elseif ($cs_domyslna <= $dominanta)
            {
                $ocs = $dominanta;
                $komunikat = "OCS policzone jako dominanta <4,∞> (dominanta)";
            }
            else
            {
                if($cs_domyslna <= $srednia_cena_konkurencja)
                {
                    if($srednia_cena_konkurencja <= $mediana)
                    {
                        $ocs = $srednia_cena_konkurencja;
                        $komunikat = "OCS policzone jako sr_cena <4,∞> (dominanta)";
                    }
                    elseif($cs_domyslna >= $mediana)
                    {
                        $ocs = $srednia_cena_konkurencja;
                        $komunikat = "OCS policzone jako sr_cena <4,∞> (dominanta)";
                    }
                    elseif($czb>=$mediana)
                    {
                        $ocs = $czb;
                        $komunikat = "OCS policzone jako CZB <4,∞> (dominanta)";
                    }
                    else
                    {
                        $ocs = $mediana;
                        $komunikat = "OCS policzone jako mediana <4,∞> (dominanta)";
                    }
                }
                else 
                {
                    //Czy o to chodziło?
                    if($czb <= $dominanta)
                    {
                        $ocs = $dominanta;
                        $komunikat = "OCS policzone jako dominanta (czb) <4,∞> (dominanta)";

                    }
                    elseif($czb <= $srednia_cena_konkurencja)
                    {
                        if($srednia_cena_konkurencja <= $mediana)
                        {
                            $ocs = $srednia_cena_konkurencja;
                            $komunikat = "OCS policzone jako sr_cena (czb) <4,∞> (dominanta)";
                        }
                        elseif($czb >= $mediana)
                        {
                            $ocs = $srednia_cena_konkurencja;
                            $komunikat = "OCS policzone jako sr_cena (czb) <4,∞> (dominanta)";
                        }
                        else
                        {
                            $ocs = $mediana;
                            $komunikat = "OCS policzone jako mediana (czb) <4,∞> (dominanta)";
                        }
                    }
                    elseif($czb <= $cena_max)
                    {
                        $ocs = $cena_max;
                        $komunikat = "OCS policzone jako cena_max (czb) <4,∞> (dominanta)";
                    }
                    else
                    {
                        $ocs = $czb;
                        $komunikat = "OCS policzone jako czb (czb) <4,∞> (dominanta)";
                    }
                }
            }
        }
        else //Nie ma dominanty
        {
            if($cs_domyslna <= $cena_min)
            {
                if($ilosc_wys_min > 1){
                    $ocs = $cena_min;
                    $komunikat = "OCS policzone jako cena_min <4,∞> (bez dominanty)";
                }
                elseif($mediana > $srednia_cena_konkurencja){
                    $ocs = $srednia_cena_konkurencja;
                    $komunikat = "OCS policzone jako sr_cena <4,∞> (bez dominanty)";
                }
                else{
                    $ocs = $mediana;
                    $komunikat = "OCS policzone jako mediana <4,∞> (bez dominanty)";
                }  
            }
            elseif($cs_domyslna <= $srednia_cena_konkurencja)
            {
                if($cs_domyslna <= $mediana)
                {
                    if($srednia_cena_konkurencja >= $mediana)
                    {
                        $ocs = $mediana;
                        $komunikat = "OCS policzone jako mediana <4,∞> (bez dominanty)";
                    }
                    else
                    {
                        $ocs = $srednia_cena_konkurencja;
                        $komunikat = "OCS policzone jako sr_cena <4,∞> (bez dominanty)";
                    }
                }
                else
                {
                    $ocs = $srednia_cena_konkurencja;
                    $komunikat = "OCS policzone jako sr_cena <4,∞> (bez dominanty)";
                }
            }
            else
            {
                //Czy o to chodziło? v2
                if($czb <= $srednia_cena_konkurencja)
                    {
                        if($srednia_cena_konkurencja <= $mediana)
                        {
                            $ocs = $srednia_cena_konkurencja;
                            $komunikat = "OCS policzone jako sr_cena (czb) <4,∞> (bez dominanty)";
                        }
                        elseif($czb >= $mediana)
                        {
                            $ocs = $srednia_cena_konkurencja;
                            $komunikat = "OCS policzone jako sr_cena (czb) <4,∞> (bez dominanty)";
                        }
                        else
                        {
                            $ocs = $mediana;
                            $komunikat = "OCS policzone jako mediana (czb) <4,∞> (bez dominanty)";
                        }
                    }
                elseif($czb <= $cena_max)
                {
                    $ocs = $cena_max;
                    $komunikat = "OCS policzone jako cena_max (czb) <4,∞> (bez dominanty)";
                }
                else
                {
                    $ocs = $czb;
                    $komunikat = "OCS policzone jako czb (czb) <4,∞> (bez dominanty)";
                }
            }
        }
    }
    
    return $ocs;
} 