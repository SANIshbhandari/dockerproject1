<?php
/**
 * Report Filter Class
 * Handles validation and management of report filters
 */

class ReportFilter {
    public $dateFrom;
    public $dateTo;
    public $category;
    public $status;
    public $customFilters = [];
    private $errors = [];
    
    /**
     * Constructor
     * @param array $filterData Filter data from request
     */
    public function __construct($filterData = []) {
        $this->dateFrom = sanitizeInput($filterData['date_from'] ?? '');
        $this->dateTo = sanitizeInput($filterData['date_to'] ?? '');
        $this->category = sanitizeInput($filterData['category'] ?? '');
        $this->status = sanitizeInput($filterData['status'] ?? '');
        
        // Store any additional custom filters
        foreach ($filterData as $key => $value) {
            if (!in_array($key, ['date_from', 'date_to', 'category', 'status'])) {
                $this->customFilters[$key] = sanitizeInput($value);
            }
        }
    }
    
    /**
     * Validate filter values
     * @return bool True if valid, false otherwise
     */
    public function isValid() {
        $this->errors = [];
        
        // Validate date range
        if (!empty($this->dateFrom) && !$this->isValidDate($this->dateFrom)) {
            $this->errors[] = "Invalid 'From Date' format. Use YYYY-MM-DD.";
        }
        
        if (!empty($this->dateTo) && !$this->isValidDate($this->dateTo)) {
            $this->errors[] = "Invalid 'To Date' format. Use YYYY-MM-DD.";
        }
        
        if (!empty($this->dateFrom) && !empty($this->dateTo)) {
            if (strtotime($this->dateFrom) > strtotime($this->dateTo)) {
                $this->errors[] = "'From Date' cannot be after 'To Date'.";
            }
        }
        
        return empty($this->errors);
    }
    
    /**
     * Check if date is valid
     * @param string $date Date string
     * @return bool True if valid
     */
    private function isValidDate($date) {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
    
    /**
     * Get validation errors
     * @return array Array of error messages
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Convert to array for query building
     * @return array Filter data as associative array
     */
    public function toArray() {
        $filters = [];
        
        if (!empty($this->dateFrom)) {
            $filters['date_from'] = $this->dateFrom;
        }
        
        if (!empty($this->dateTo)) {
            $filters['date_to'] = $this->dateTo;
        }
        
        if (!empty($this->category)) {
            $filters['category'] = $this->category;
        }
        
        if (!empty($this->status)) {
            $filters['status'] = $this->status;
        }
        
        // Add custom filters
        foreach ($this->customFilters as $key => $value) {
            if (!empty($value) || $value === '0' || $value === 0) {
                $filters[$key] = $value;
            }
        }
        
        return $filters;
    }
    
    /**
     * Get filter summary for display
     * @return string HTML formatted filter summary
     */
    public function getSummary() {
        $summary = [];
        
        if (!empty($this->dateFrom)) {
            $summary[] = "From: " . formatDate($this->dateFrom);
        }
        
        if (!empty($this->dateTo)) {
            $summary[] = "To: " . formatDate($this->dateTo);
        }
        
        if (!empty($this->category)) {
            $summary[] = "Category: " . htmlspecialchars($this->category);
        }
        
        if (!empty($this->status)) {
            $summary[] = "Status: " . htmlspecialchars($this->status);
        }
        
        foreach ($this->customFilters as $key => $value) {
            if (!empty($value)) {
                $label = ucfirst(str_replace('_', ' ', $key));
                $summary[] = "$label: " . htmlspecialchars($value);
            }
        }
        
        return !empty($summary) ? implode(' | ', $summary) : 'No filters applied';
    }
    
    /**
     * Check if any filters are applied
     * @return bool True if filters are applied
     */
    public function hasFilters() {
        return !empty($this->dateFrom) || !empty($this->dateTo) || 
               !empty($this->category) || !empty($this->status) || 
               !empty($this->customFilters);
    }
    
    /**
     * Get URL query string for filters
     * @return string URL query string
     */
    public function toQueryString() {
        $params = [];
        
        if (!empty($this->dateFrom)) {
            $params[] = 'date_from=' . urlencode($this->dateFrom);
        }
        
        if (!empty($this->dateTo)) {
            $params[] = 'date_to=' . urlencode($this->dateTo);
        }
        
        if (!empty($this->category)) {
            $params[] = 'category=' . urlencode($this->category);
        }
        
        if (!empty($this->status)) {
            $params[] = 'status=' . urlencode($this->status);
        }
        
        foreach ($this->customFilters as $key => $value) {
            if (!empty($value)) {
                $params[] = urlencode($key) . '=' . urlencode($value);
            }
        }
        
        return implode('&', $params);
    }
}
?>
