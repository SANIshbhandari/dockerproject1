<?php
/**
 * Report Generator Core Library
 * Centralized logic for building SQL queries, applying filters, and formatting report data
 */

class ReportGenerator {
    private $conn;
    
    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }
    
    /**
     * Build WHERE clause from filters
     * @param array $filters Associative array of filter conditions
     * @return array ['clause' => string, 'params' => array, 'types' => string]
     */
    public function buildWhereClause($filters) {
        $conditions = [];
        $params = [];
        $types = '';
        
        foreach ($filters as $field => $value) {
            if (empty($value) && $value !== '0' && $value !== 0) {
                continue;
            }
            
            // Handle different filter types
            if (strpos($field, '_from') !== false) {
                // Date range start
                $actualField = str_replace('_from', '', $field);
                $conditions[] = "$actualField >= ?";
                $params[] = $value;
                $types .= 's';
            } elseif (strpos($field, '_to') !== false) {
                // Date range end
                $actualField = str_replace('_to', '', $field);
                $conditions[] = "$actualField <= ?";
                $params[] = $value;
                $types .= 's';
            } elseif (is_array($value)) {
                // IN clause for multiple values
                $placeholders = str_repeat('?,', count($value) - 1) . '?';
                $conditions[] = "$field IN ($placeholders)";
                foreach ($value as $v) {
                    $params[] = $v;
                    $types .= 's';
                }
            } else {
                // Exact match
                $conditions[] = "$field = ?";
                $params[] = $value;
                $types .= 's';
            }
        }
        
        $clause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
        
        return [
            'clause' => $clause,
            'params' => $params,
            'types' => $types
        ];
    }
    
    /**
     * Execute query with parameters
     * @param string $query SQL query
     * @param array $params Query parameters
     * @param string $types Parameter types
     * @return mysqli_result|false
     */
    public function executeQuery($query, $params = [], $types = '') {
        try {
            if (empty($params)) {
                $result = $this->conn->query($query);
                if (!$result) {
                    error_log("Query failed: " . $this->conn->error);
                    return false;
                }
                return $result;
            }
            
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                error_log("Prepare failed: " . $this->conn->error);
                return false;
            }
            
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            
            if (!$stmt->execute()) {
                error_log("Execute failed: " . $stmt->error);
                $stmt->close();
                return false;
            }
            
            $result = $stmt->get_result();
            $stmt->close();
            
            return $result;
        } catch (Exception $e) {
            error_log("Query execution error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Format report data for display
     * @param mysqli_result $result Query result
     * @param array $formatRules Formatting rules for columns
     * @return array Formatted data
     */
    public function formatReportData($result, $formatRules = []) {
        $data = [];
        
        if (!$result) {
            return $data;
        }
        
        while ($row = $result->fetch_assoc()) {
            $formattedRow = [];
            
            foreach ($row as $key => $value) {
                if (isset($formatRules[$key])) {
                    $rule = $formatRules[$key];
                    
                    switch ($rule['type']) {
                        case 'currency':
                            $formattedRow[$key] = formatCurrency($value);
                            break;
                        case 'date':
                            $formattedRow[$key] = formatDate($value);
                            break;
                        case 'number':
                            $decimals = $rule['decimals'] ?? 2;
                            $formattedRow[$key] = number_format($value, $decimals);
                            break;
                        case 'percentage':
                            $decimals = $rule['decimals'] ?? 1;
                            $formattedRow[$key] = number_format($value, $decimals) . '%';
                            break;
                        case 'badge':
                            $badgeClass = $rule['class'] ?? $value;
                            $formattedRow[$key] = '<span class="badge badge-' . $badgeClass . '">' . ucfirst(str_replace('_', ' ', $value)) . '</span>';
                            break;
                        default:
                            $formattedRow[$key] = htmlspecialchars($value);
                    }
                } else {
                    $formattedRow[$key] = htmlspecialchars($value ?? '');
                }
            }
            
            $data[] = $formattedRow;
        }
        
        return $data;
    }
    
    /**
     * Calculate aggregates (sum, avg, count, etc.)
     * @param array $data Report data
     * @param array $aggregateRules Rules for aggregation
     * @return array Calculated aggregates
     */
    public function calculateAggregates($data, $aggregateRules) {
        $aggregates = [];
        
        foreach ($aggregateRules as $field => $rule) {
            $values = array_column($data, $field);
            
            switch ($rule['function']) {
                case 'sum':
                    $aggregates[$field] = array_sum($values);
                    break;
                case 'avg':
                    $aggregates[$field] = count($values) > 0 ? array_sum($values) / count($values) : 0;
                    break;
                case 'count':
                    $aggregates[$field] = count($values);
                    break;
                case 'min':
                    $aggregates[$field] = count($values) > 0 ? min($values) : 0;
                    break;
                case 'max':
                    $aggregates[$field] = count($values) > 0 ? max($values) : 0;
                    break;
                default:
                    $aggregates[$field] = null;
            }
            
            // Apply formatting if specified
            if (isset($rule['format'])) {
                switch ($rule['format']) {
                    case 'currency':
                        $aggregates[$field] = formatCurrency($aggregates[$field]);
                        break;
                    case 'number':
                        $decimals = $rule['decimals'] ?? 2;
                        $aggregates[$field] = number_format($aggregates[$field], $decimals);
                        break;
                    case 'percentage':
                        $decimals = $rule['decimals'] ?? 1;
                        $aggregates[$field] = number_format($aggregates[$field], $decimals) . '%';
                        break;
                }
            }
        }
        
        return $aggregates;
    }
    
    /**
     * Get single value from query
     * @param string $query SQL query
     * @param array $params Query parameters
     * @param string $types Parameter types
     * @return mixed Single value or null
     */
    public function getSingleValue($query, $params = [], $types = '') {
        $result = $this->executeQuery($query, $params, $types);
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_array(MYSQLI_NUM);
            return $row[0];
        }
        
        return null;
    }
    
    /**
     * Build pagination data
     * @param int $totalRecords Total number of records
     * @param int $currentPage Current page number
     * @param int $recordsPerPage Records per page
     * @return array Pagination data
     */
    public function buildPagination($totalRecords, $currentPage = 1, $recordsPerPage = 50) {
        return getPagination($totalRecords, $currentPage, $recordsPerPage);
    }
}
?>
