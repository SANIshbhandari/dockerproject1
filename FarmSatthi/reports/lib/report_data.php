<?php
/**
 * Report Data Class
 * Handles report data structure and rendering
 */

class ReportData {
    public $title;
    public $filters;
    public $headers = [];
    public $rows = [];
    public $summary = [];
    public $metadata = [];
    
    /**
     * Constructor
     * @param string $title Report title
     */
    public function __construct($title = '') {
        $this->title = $title;
        $this->metadata['generated_at'] = date('Y-m-d H:i:s');
    }
    
    /**
     * Set report headers
     * @param array $headers Column headers
     */
    public function setHeaders($headers) {
        $this->headers = $headers;
    }
    
    /**
     * Add a row to the report
     * @param array $row Row data
     */
    public function addRow($row) {
        $this->rows[] = $row;
    }
    
    /**
     * Set all rows at once
     * @param array $rows Array of rows
     */
    public function setRows($rows) {
        $this->rows = $rows;
    }
    
    /**
     * Add summary data
     * @param string $key Summary key
     * @param mixed $value Summary value
     */
    public function addSummary($key, $value) {
        $this->summary[$key] = $value;
    }
    
    /**
     * Set all summary data at once
     * @param array $summary Summary data
     */
    public function setSummary($summary) {
        $this->summary = $summary;
    }
    
    /**
     * Add metadata
     * @param string $key Metadata key
     * @param mixed $value Metadata value
     */
    public function addMetadata($key, $value) {
        $this->metadata[$key] = $value;
    }
    
    /**
     * Generate HTML table
     * @return string HTML table markup
     */
    public function toHTML() {
        $html = '';
        
        // Summary cards
        if (!empty($this->summary)) {
            $html .= '<div class="report-summary">';
            foreach ($this->summary as $key => $value) {
                $label = ucfirst(str_replace('_', ' ', $key));
                $html .= '<div class="summary-card">';
                $html .= '<h4>' . htmlspecialchars($label) . '</h4>';
                $html .= '<p class="summary-number">' . $value . '</p>';
                $html .= '</div>';
            }
            $html .= '</div>';
        }
        
        // Data table
        if (!empty($this->rows)) {
            $html .= '<div class="table-responsive">';
            $html .= '<table class="data-table">';
            
            // Headers
            if (!empty($this->headers)) {
                $html .= '<thead><tr>';
                foreach ($this->headers as $header) {
                    $html .= '<th>' . htmlspecialchars($header) . '</th>';
                }
                $html .= '</tr></thead>';
            }
            
            // Rows
            $html .= '<tbody>';
            foreach ($this->rows as $row) {
                $html .= '<tr>';
                foreach ($row as $cell) {
                    $html .= '<td>' . $cell . '</td>';
                }
                $html .= '</tr>';
            }
            $html .= '</tbody>';
            
            $html .= '</table>';
            $html .= '</div>';
        } else {
            $html .= '<div class="no-results">';
            $html .= '<p>No data available for the selected filters.</p>';
            $html .= '</div>';
        }
        
        return $html;
    }
    
    /**
     * Convert to array for export
     * @return array Report data as array
     */
    public function toArray() {
        return [
            'title' => $this->title,
            'headers' => $this->headers,
            'rows' => $this->rows,
            'summary' => $this->summary,
            'metadata' => $this->metadata
        ];
    }
    
    /**
     * Get row count
     * @return int Number of rows
     */
    public function getRowCount() {
        return count($this->rows);
    }
    
    /**
     * Check if report has data
     * @return bool True if has data
     */
    public function hasData() {
        return !empty($this->rows);
    }
    
    /**
     * Get summary value
     * @param string $key Summary key
     * @return mixed Summary value or null
     */
    public function getSummaryValue($key) {
        return $this->summary[$key] ?? null;
    }
    
    /**
     * Generate CSV string
     * @return string CSV formatted data
     */
    public function toCSV() {
        $csv = '';
        
        // Add title
        if (!empty($this->title)) {
            $csv .= '"' . str_replace('"', '""', $this->title) . '"' . "\n\n";
        }
        
        // Add headers
        if (!empty($this->headers)) {
            $csv .= '"' . implode('","', array_map(function($h) {
                return str_replace('"', '""', $h);
            }, $this->headers)) . '"' . "\n";
        }
        
        // Add rows
        foreach ($this->rows as $row) {
            $cleanRow = array_map(function($cell) {
                // Strip HTML tags and escape quotes
                $clean = strip_tags($cell);
                return str_replace('"', '""', $clean);
            }, $row);
            $csv .= '"' . implode('","', $cleanRow) . '"' . "\n";
        }
        
        // Add summary
        if (!empty($this->summary)) {
            $csv .= "\n";
            foreach ($this->summary as $key => $value) {
                $label = ucfirst(str_replace('_', ' ', $key));
                $cleanValue = strip_tags($value);
                $csv .= '"' . str_replace('"', '""', $label) . '","' . str_replace('"', '""', $cleanValue) . '"' . "\n";
            }
        }
        
        return $csv;
    }
}
?>
