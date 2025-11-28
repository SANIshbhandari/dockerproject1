<?php
/**
 * Nepali Date Converter
 * Converts English dates (AD) to Nepali dates (Bikram Sambat - BS)
 */

class NepaliDateConverter {
    
    // BS month data (days in each month for different years)
    // This is a simplified version - for production, use a complete lookup table
    private static $bsMonthData = [
        2080 => [31, 31, 32, 32, 31, 30, 30, 29, 30, 29, 30, 30], // BS 2080
        2081 => [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31], // BS 2081
        2082 => [31, 31, 31, 32, 31, 31, 29, 30, 30, 29, 30, 30], // BS 2082
    ];
    
    // Nepali month names
    private static $nepaliMonths = [
        1 => 'Baisakh', 2 => 'Jestha', 3 => 'Ashadh', 4 => 'Shrawan',
        5 => 'Bhadra', 6 => 'Ashwin', 7 => 'Kartik', 8 => 'Mangsir',
        9 => 'Poush', 10 => 'Magh', 11 => 'Falgun', 12 => 'Chaitra'
    ];
    
    // Nepali month names in Devanagari
    private static $nepaliMonthsDevanagari = [
        1 => 'बैशाख', 2 => 'जेठ', 3 => 'असार', 4 => 'साउन',
        5 => 'भदौ', 6 => 'असोज', 7 => 'कार्तिक', 8 => 'मंसिर',
        9 => 'पुष', 10 => 'माघ', 11 => 'फागुन', 12 => 'चैत्र'
    ];
    
    /**
     * Convert English date to Nepali date
     * @param string $englishDate Date in Y-m-d format
     * @param bool $useDevanagari Use Devanagari month names
     * @return array ['year' => BS year, 'month' => month number, 'day' => day, 'formatted' => formatted string]
     */
    public static function convertToBS($englishDate, $useDevanagari = false) {
        if (empty($englishDate)) return null;
        
        $timestamp = strtotime($englishDate);
        $adYear = (int)date('Y', $timestamp);
        $adMonth = (int)date('m', $timestamp);
        $adDay = (int)date('d', $timestamp);
        
        // Approximate conversion (simplified algorithm)
        // For accurate conversion, you need the complete BS calendar data
        
        // Base reference: 2024-01-01 AD = 2080-09-17 BS (approximately)
        $baseAD = strtotime('2024-01-01');
        $baseBS = ['year' => 2080, 'month' => 9, 'day' => 17];
        
        if ($timestamp >= $baseAD) {
            // Date is after base date
            $daysDiff = ($timestamp - $baseAD) / 86400;
            $result = self::addDaysToBS($baseBS, (int)$daysDiff);
        } else {
            // Date is before base date
            $daysDiff = ($baseAD - $timestamp) / 86400;
            $result = self::subtractDaysFromBS($baseBS, (int)$daysDiff);
        }
        
        $months = $useDevanagari ? self::$nepaliMonthsDevanagari : self::$nepaliMonths;
        $monthName = $months[$result['month']];
        
        $result['formatted'] = $monthName . ' ' . $result['day'] . ', ' . $result['year'] . ' BS';
        $result['monthName'] = $monthName;
        
        return $result;
    }
    
    /**
     * Add days to a BS date
     */
    private static function addDaysToBS($bsDate, $days) {
        $year = $bsDate['year'];
        $month = $bsDate['month'];
        $day = $bsDate['day'];
        
        while ($days > 0) {
            $daysInMonth = self::getDaysInMonth($year, $month);
            $remainingDays = $daysInMonth - $day;
            
            if ($days <= $remainingDays) {
                $day += $days;
                $days = 0;
            } else {
                $days -= ($remainingDays + 1);
                $day = 1;
                $month++;
                
                if ($month > 12) {
                    $month = 1;
                    $year++;
                }
            }
        }
        
        return ['year' => $year, 'month' => $month, 'day' => $day];
    }
    
    /**
     * Subtract days from a BS date
     */
    private static function subtractDaysFromBS($bsDate, $days) {
        $year = $bsDate['year'];
        $month = $bsDate['month'];
        $day = $bsDate['day'];
        
        while ($days > 0) {
            if ($days < $day) {
                $day -= $days;
                $days = 0;
            } else {
                $days -= $day;
                $month--;
                
                if ($month < 1) {
                    $month = 12;
                    $year--;
                }
                
                $day = self::getDaysInMonth($year, $month);
            }
        }
        
        return ['year' => $year, 'month' => $month, 'day' => $day];
    }
    
    /**
     * Get number of days in a BS month
     */
    private static function getDaysInMonth($year, $month) {
        // If we have data for this year, use it
        if (isset(self::$bsMonthData[$year])) {
            return self::$bsMonthData[$year][$month - 1];
        }
        
        // Default pattern (approximate)
        $defaultDays = [31, 31, 32, 32, 31, 30, 30, 29, 30, 29, 30, 30];
        return $defaultDays[$month - 1];
    }
    
    /**
     * Format BS date in short format
     */
    public static function formatShort($englishDate) {
        $bs = self::convertToBS($englishDate);
        if (!$bs) return '';
        return $bs['year'] . '-' . str_pad($bs['month'], 2, '0', STR_PAD_LEFT) . '-' . str_pad($bs['day'], 2, '0', STR_PAD_LEFT);
    }
    
    /**
     * Format BS date in long format
     */
    public static function formatLong($englishDate, $useDevanagari = false) {
        $bs = self::convertToBS($englishDate, $useDevanagari);
        if (!$bs) return '';
        return $bs['formatted'];
    }
}

/**
 * Helper function to convert English date to Nepali
 */
function toNepaliDate($englishDate, $format = 'long') {
    if ($format === 'short') {
        return NepaliDateConverter::formatShort($englishDate);
    }
    return NepaliDateConverter::formatLong($englishDate);
}
?>
