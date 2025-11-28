<?php
/**
 * Schema Helper Functions for Optimized 8-Table Structure
 * These functions help work with the new consolidated schema
 */

/**
 * ============================================================================
 * CROPS HELPERS
 * ============================================================================
 */

/**
 * Add a crop sale to the sales JSON array
 * @param mysqli $conn Database connection
 * @param int $cropId Crop ID
 * @param array $saleData Sale data array
 * @return bool Success status
 */
function addCropSale($conn, $cropId, $saleData) {
    $saleJson = json_encode([
        'date' => $saleData['sale_date'],
        'quantity' => $saleData['quantity'],
        'rate' => $saleData['rate'],
        'total' => $saleData['total'],
        'buyer' => $saleData['buyer'],
        'contact' => $saleData['contact'] ?? '',
        'payment_method' => $saleData['payment_method'] ?? 'cash'
    ]);
    
    $stmt = $conn->prepare("
        UPDATE crops 
        SET sales = JSON_ARRAY_APPEND(COALESCE(sales, '[]'), '$', CAST(? AS JSON))
        WHERE id = ?
    ");
    $stmt->bind_param("si", $saleJson, $cropId);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

/**
 * Get all sales for a crop
 * @param mysqli $conn Database connection
 * @param int $cropId Crop ID
 * @return array Array of sales
 */
function getCropSales($conn, $cropId) {
    $stmt = $conn->prepare("SELECT sales FROM crops WHERE id = ?");
    $stmt->bind_param("i", $cropId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    if ($row && $row['sales']) {
        return json_decode($row['sales'], true) ?? [];
    }
    return [];
}

/**
 * Get total sales amount for a crop
 * @param mysqli $conn Database connection
 * @param int $cropId Crop ID
 * @return float Total sales amount
 */
function getCropTotalSales($conn, $cropId) {
    $sales = getCropSales($conn, $cropId);
    $total = 0;
    foreach ($sales as $sale) {
        $total += $sale['total'] ?? 0;
    }
    return $total;
}

/**
 * ============================================================================
 * LIVESTOCK HELPERS
 * ============================================================================
 */

/**
 * Add livestock production record
 * @param mysqli $conn Database connection
 * @param int $livestockId Livestock ID
 * @param array $productionData Production data
 * @return bool Success status
 */
function addLivestockProduction($conn, $livestockId, $productionData) {
    $productionJson = json_encode([
        'date' => $productionData['date'],
        'type' => $productionData['type'], // milk, eggs, meat
        'quantity' => $productionData['quantity'],
        'unit' => $productionData['unit']
    ]);
    
    $stmt = $conn->prepare("
        UPDATE livestock 
        SET production = JSON_ARRAY_APPEND(COALESCE(production, '[]'), '$', CAST(? AS JSON))
        WHERE id = ?
    ");
    $stmt->bind_param("si", $productionJson, $livestockId);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

/**
 * Add livestock sale
 * @param mysqli $conn Database connection
 * @param int $livestockId Livestock ID
 * @param array $saleData Sale data
 * @return bool Success status
 */
function addLivestockSale($conn, $livestockId, $saleData) {
    $saleJson = json_encode([
        'date' => $saleData['sale_date'],
        'quantity' => $saleData['quantity'],
        'price' => $saleData['price'],
        'buyer' => $saleData['buyer'],
        'contact' => $saleData['contact'] ?? '',
        'payment_method' => $saleData['payment_method'] ?? 'cash'
    ]);
    
    $stmt = $conn->prepare("
        UPDATE livestock 
        SET sales = JSON_ARRAY_APPEND(COALESCE(sales, '[]'), '$', CAST(? AS JSON))
        WHERE id = ?
    ");
    $stmt->bind_param("si", $saleJson, $livestockId);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

/**
 * Get livestock production records
 * @param mysqli $conn Database connection
 * @param int $livestockId Livestock ID
 * @return array Production records
 */
function getLivestockProduction($conn, $livestockId) {
    $stmt = $conn->prepare("SELECT production FROM livestock WHERE id = ?");
    $stmt->bind_param("i", $livestockId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    if ($row && $row['production']) {
        return json_decode($row['production'], true) ?? [];
    }
    return [];
}

/**
 * Get livestock sales
 * @param mysqli $conn Database connection
 * @param int $livestockId Livestock ID
 * @return array Sales records
 */
function getLivestockSales($conn, $livestockId) {
    $stmt = $conn->prepare("SELECT sales FROM livestock WHERE id = ?");
    $stmt->bind_param("i", $livestockId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    if ($row && $row['sales']) {
        return json_decode($row['sales'], true) ?? [];
    }
    return [];
}

/**
 * ============================================================================
 * INVENTORY HELPERS
 * ============================================================================
 */

/**
 * Add inventory transaction
 * @param mysqli $conn Database connection
 * @param int $inventoryId Inventory item ID
 * @param array $transactionData Transaction data
 * @return bool Success status
 */
function addInventoryTransaction($conn, $inventoryId, $transactionData) {
    $transactionJson = json_encode([
        'date' => $transactionData['date'],
        'type' => $transactionData['type'], // in, out
        'quantity' => $transactionData['quantity'],
        'amount' => $transactionData['amount'] ?? 0,
        'notes' => $transactionData['notes'] ?? ''
    ]);
    
    // Update quantity based on transaction type
    $quantityChange = $transactionData['type'] === 'in' ? 
        $transactionData['quantity'] : -$transactionData['quantity'];
    
    $stmt = $conn->prepare("
        UPDATE inventory 
        SET transactions = JSON_ARRAY_APPEND(COALESCE(transactions, '[]'), '$', CAST(? AS JSON)),
            quantity = quantity + ?
        WHERE id = ?
    ");
    $stmt->bind_param("sdi", $transactionJson, $quantityChange, $inventoryId);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

/**
 * Get inventory transactions
 * @param mysqli $conn Database connection
 * @param int $inventoryId Inventory item ID
 * @return array Transaction records
 */
function getInventoryTransactions($conn, $inventoryId) {
    $stmt = $conn->prepare("SELECT transactions FROM inventory WHERE id = ?");
    $stmt->bind_param("i", $inventoryId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    if ($row && $row['transactions']) {
        return json_decode($row['transactions'], true) ?? [];
    }
    return [];
}

/**
 * Get equipment list (from inventory)
 * @param mysqli $conn Database connection
 * @param int $userId User ID (optional, for filtering)
 * @return mysqli_result Equipment records
 */
function getEquipment($conn, $userId = null) {
    $isolationWhere = getDataIsolationWhere();
    $query = "SELECT * FROM inventory WHERE item_type = 'equipment' AND $isolationWhere ORDER BY item_name";
    return $conn->query($query);
}

/**
 * Get employees list (from inventory)
 * @param mysqli $conn Database connection
 * @param int $userId User ID (optional, for filtering)
 * @return mysqli_result Employee records
 */
function getEmployees($conn, $userId = null) {
    $isolationWhere = getDataIsolationWhere();
    $query = "SELECT * FROM inventory WHERE item_type = 'employee' AND $isolationWhere ORDER BY item_name";
    return $conn->query($query);
}

/**
 * Get supplies list (from inventory)
 * @param mysqli $conn Database connection
 * @param int $userId User ID (optional, for filtering)
 * @return mysqli_result Supply records
 */
function getSupplies($conn, $userId = null) {
    $isolationWhere = getDataIsolationWhere();
    $query = "SELECT * FROM inventory WHERE item_type = 'supply' AND $isolationWhere ORDER BY item_name";
    return $conn->query($query);
}

/**
 * ============================================================================
 * FINANCE HELPERS
 * ============================================================================
 */

/**
 * Add income record
 * @param mysqli $conn Database connection
 * @param array $incomeData Income data
 * @return int|false Inserted ID or false on failure
 */
function addIncome($conn, $incomeData) {
    $stmt = $conn->prepare("
        INSERT INTO finance (created_by, type, category, amount, transaction_date, description, payment_method)
        VALUES (?, 'income', ?, ?, ?, ?, ?)
    ");
    $userId = getCreatedByUserId();
    $stmt->bind_param("isdsss", 
        $userId,
        $incomeData['category'],
        $incomeData['amount'],
        $incomeData['date'],
        $incomeData['description'] ?? '',
        $incomeData['payment_method'] ?? 'cash'
    );
    $result = $stmt->execute();
    $insertId = $conn->insert_id;
    $stmt->close();
    
    return $result ? $insertId : false;
}

/**
 * Add expense record
 * @param mysqli $conn Database connection
 * @param array $expenseData Expense data
 * @return int|false Inserted ID or false on failure
 */
function addExpense($conn, $expenseData) {
    $stmt = $conn->prepare("
        INSERT INTO finance (created_by, type, category, amount, transaction_date, description, payment_method)
        VALUES (?, 'expense', ?, ?, ?, ?, ?)
    ");
    $userId = getCreatedByUserId();
    $stmt->bind_param("isdsss", 
        $userId,
        $expenseData['category'],
        $expenseData['amount'],
        $expenseData['date'],
        $expenseData['description'] ?? '',
        $expenseData['payment_method'] ?? 'cash'
    );
    $result = $stmt->execute();
    $insertId = $conn->insert_id;
    $stmt->close();
    
    return $result ? $insertId : false;
}

/**
 * Get financial summary
 * @param mysqli $conn Database connection
 * @param string $dateFrom Start date
 * @param string $dateTo End date
 * @return array Summary with income and expense totals
 */
function getFinancialSummary($conn, $dateFrom, $dateTo) {
    $isolationWhere = getDataIsolationWhere();
    
    $query = "
        SELECT 
            type,
            SUM(amount) as total,
            COUNT(*) as count
        FROM finance
        WHERE $isolationWhere
            AND transaction_date BETWEEN ? AND ?
        GROUP BY type
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $dateFrom, $dateTo);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $summary = ['income' => 0, 'expense' => 0, 'income_count' => 0, 'expense_count' => 0];
    while ($row = $result->fetch_assoc()) {
        $summary[$row['type']] = $row['total'];
        $summary[$row['type'] . '_count'] = $row['count'];
    }
    $stmt->close();
    
    $summary['profit'] = $summary['income'] - $summary['expense'];
    return $summary;
}

/**
 * ============================================================================
 * REPORT HELPERS
 * ============================================================================
 */

/**
 * Save a generated report
 * @param mysqli $conn Database connection
 * @param string $reportType Report type
 * @param string $reportName Report name
 * @param string $dateFrom Start date
 * @param string $dateTo End date
 * @param array $reportData Report data
 * @return int|false Inserted ID or false on failure
 */
function saveReport($conn, $reportType, $reportName, $dateFrom, $dateTo, $reportData) {
    $stmt = $conn->prepare("
        INSERT INTO reports (created_by, report_type, report_name, date_from, date_to, report_data)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $userId = getCreatedByUserId();
    $reportJson = json_encode($reportData);
    $stmt->bind_param("isssss", $userId, $reportType, $reportName, $dateFrom, $dateTo, $reportJson);
    $result = $stmt->execute();
    $insertId = $conn->insert_id;
    $stmt->close();
    
    return $result ? $insertId : false;
}

/**
 * Get saved reports
 * @param mysqli $conn Database connection
 * @param string $reportType Optional report type filter
 * @return mysqli_result Report records
 */
function getSavedReports($conn, $reportType = null) {
    $isolationWhere = getDataIsolationWhere();
    
    if ($reportType) {
        $query = "SELECT * FROM reports WHERE $isolationWhere AND report_type = ? ORDER BY created_at DESC";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $reportType);
        $stmt->execute();
        return $stmt->get_result();
    } else {
        $query = "SELECT * FROM reports WHERE $isolationWhere ORDER BY created_at DESC";
        return $conn->query($query);
    }
}

/**
 * ============================================================================
 * SETTINGS HELPERS
 * ============================================================================
 */

/**
 * Get a setting value
 * @param mysqli $conn Database connection
 * @param string $key Setting key
 * @param int $userId User ID (null for system settings)
 * @return string|null Setting value or null if not found
 */
function getSetting($conn, $key, $userId = null) {
    $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = ? AND user_id <=> ?");
    $stmt->bind_param("si", $key, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row ? $row['setting_value'] : null;
}

/**
 * Set a setting value
 * @param mysqli $conn Database connection
 * @param string $key Setting key
 * @param string $value Setting value
 * @param int $userId User ID (null for system settings)
 * @return bool Success status
 */
function setSetting($conn, $key, $value, $userId = null) {
    $stmt = $conn->prepare("
        INSERT INTO settings (user_id, setting_key, setting_value)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE setting_value = ?
    ");
    $stmt->bind_param("isss", $userId, $key, $value, $value);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}
?>
