<?php  
    if (!function_exists('formatNumber')) {
        function formatNumber($number)
        {
            return number_format($number, 0, ',', '.');
        }
    }
?>