<?php
/**
 * PDF Report Generator using TCPDF
 * Install TCPDF via Composer: composer require tecnickcom/tcpdf
 * Or download from: https://github.com/tecnickcom/TCPDF
 */

require_once __DIR__ . '/../includes/header.php';

// Check if TCPDF is available
$tcpdfPath = __DIR__ . '/lib/tcpdf/tcpdf.php';
if (!file_exists($tcpdfPath)) {
    die('TCPDF library not found. Please install TCPDF first.<br><br>
         <strong>Installation Instructions:</strong><br>
         1. Download TCPDF from: <a href="https://github.com/tecnickcom/TCPDF/releases">https://github.com/tecnickcom/TCPDF/releases</a><br>
         2. Extract to: FarmSatthi/reports/lib/tcpdf/<br>
         3. Refresh this page<br><br>
         Or use Composer: <code>composer require tecnickcom/tcpdf</code>');
}

require_once $tcpdfPath;

$conn = getDBConnection();
$isolationWhere = getDataIsolationWhere();

// Get parameters
$reportType = sanitizeInput($_GET['type'] ?? 'dashboard');
$date_from = sanitizeInput($_GET['date_from'] ?? date('Y-01-01'));
$date_to = sanitizeInput($_GET['date_to'] ?? date('Y-m-d'));

// Create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('FarmSaathi');
$pdf->SetAuthor('FarmSaathi Farm Management System');
$pdf->SetTitle('Farm Report - ' . ucfirst($reportType));
$pdf->SetSubject('Farm Management Report');

// Set default header data
$pdf->SetHeaderData('', 0, 'FarmSaathi Farm Management', 'Report Generated: ' . date('Y-m-d H:i:s'));

// Set header and footer fonts
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// Set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// Set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// Set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// Set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', '', 10);

// Generate content based on report type
if ($reportType === 'dashboard') {
    // Financial Summary
    $financialSummary = $conn->query("
        SELECT 
            SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income,
            SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expense
        FROM finance 
        WHERE transaction_date BETWEEN '$date_from' AND '$date_to'
        AND $isolationWhere
    ")->fetch_assoc();
    
    $netProfit = ($financialSummary['total_income'] ?? 0) - ($financialSummary['total_expense'] ?? 0);
    $profitMargin = ($financialSummary['total_income'] ?? 0) > 0 ? ($netProfit / $financialSummary['total_income']) * 100 : 0;
    
    // Title
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Dashboard Summary Report', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 5, 'Period: ' . date('M d, Y', strtotime($date_from)) . ' to ' . date('M d, Y', strtotime($date_to)), 0, 1, 'C');
    $pdf->Ln(5);
    
    // Financial Summary Table
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'Financial Summary', 0, 1);
    $pdf->SetFont('helvetica', '', 10);
    
    $html = '<table border="1" cellpadding="5">
        <tr style="background-color:#2d7a3e;color:#ffffff;">
            <th width="50%"><b>Metric</b></th>
            <th width="50%"><b>Amount</b></th>
        </tr>
        <tr>
            <td>Total Income</td>
            <td style="color:#28a745;">Rs. ' . number_format($financialSummary['total_income'] ?? 0, 2) . '</td>
        </tr>
        <tr>
            <td>Total Expenses</td>
            <td style="color:#dc3545;">Rs. ' . number_format($financialSummary['total_expense'] ?? 0, 2) . '</td>
        </tr>
        <tr>
            <td><b>Net Profit/Loss</b></td>
            <td style="color:' . ($netProfit >= 0 ? '#28a745' : '#dc3545') . ';"><b>Rs. ' . number_format($netProfit, 2) . '</b></td>
        </tr>
        <tr>
            <td>Profit Margin</td>
            <td>' . number_format($profitMargin, 1) . '%</td>
        </tr>
    </table>';
    
    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Ln(5);
    
    // Crop Performance
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'Top Crop Performance', 0, 1);
    $pdf->SetFont('helvetica', '', 10);
    
    $crops = $conn->query("
        SELECT crop_name, crop_type, area_hectares, expected_yield, actual_yield, status
        FROM crops 
        WHERE $isolationWhere
        ORDER BY actual_yield DESC
        LIMIT 10
    ");
    
    $html = '<table border="1" cellpadding="4">
        <tr style="background-color:#2d7a3e;color:#ffffff;">
            <th><b>Crop Name</b></th>
            <th><b>Type</b></th>
            <th><b>Area (ha)</b></th>
            <th><b>Expected</b></th>
            <th><b>Actual</b></th>
            <th><b>Status</b></th>
        </tr>';
    
    while ($crop = $crops->fetch_assoc()) {
        $html .= '<tr>
            <td>' . htmlspecialchars($crop['crop_name']) . '</td>
            <td>' . htmlspecialchars($crop['crop_type']) . '</td>
            <td>' . number_format($crop['area_hectares'], 2) . '</td>
            <td>' . ($crop['expected_yield'] ? number_format($crop['expected_yield'], 0) : 'N/A') . '</td>
            <td>' . ($crop['actual_yield'] ? number_format($crop['actual_yield'], 0) : 'Pending') . '</td>
            <td>' . ucfirst($crop['status']) . '</td>
        </tr>';
    }
    
    $html .= '</table>';
    $pdf->writeHTML($html, true, false, true, false, '');
    
    // Top Expenses
    $pdf->AddPage();
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'Top Expense Categories', 0, 1);
    $pdf->SetFont('helvetica', '', 10);
    
    $expenses = $conn->query("
        SELECT category, SUM(amount) as total, COUNT(*) as count
        FROM finance 
        WHERE type = 'expense'
        AND transaction_date BETWEEN '$date_from' AND '$date_to'
        AND $isolationWhere
        GROUP BY category
        ORDER BY total DESC
        LIMIT 10
    ");
    
    $html = '<table border="1" cellpadding="4">
        <tr style="background-color:#2d7a3e;color:#ffffff;">
            <th><b>Category</b></th>
            <th><b>Transactions</b></th>
            <th><b>Amount</b></th>
        </tr>';
    
    while ($expense = $expenses->fetch_assoc()) {
        $html .= '<tr>
            <td>' . htmlspecialchars($expense['category']) . '</td>
            <td>' . $expense['count'] . '</td>
            <td>Rs. ' . number_format($expense['total'], 2) . '</td>
        </tr>';
    }
    
    $html .= '</table>';
    $pdf->writeHTML($html, true, false, true, false, '');
}

// Output PDF
$filename = 'FarmSaathi_' . ucfirst($reportType) . '_Report_' . date('Y-m-d') . '.pdf';
$pdf->Output($filename, 'D'); // D = download
?>
<?php
/**
 * PDF Report Generator using TCPDF
 * Install TCPDF via Composer: composer require tecnickcom/tcpdf
 * Or download from: https://github.com/tecnickcom/TCPDF
 */

require_once __DIR__ . '/../includes/header.php';

// Check if TCPDF is available
$tcpdfPath = __DIR__ . '/lib/tcpdf/tcpdf.php';
if (!file_exists($tcpdfPath)) {
    die('TCPDF library not found. Please install TCPDF first.<br><br>
         <strong>Installation Instructions:</strong><br>
         1. Download TCPDF from: <a href="https://github.com/tecnickcom/TCPDF/releases">https://github.com/tecnickcom/TCPDF/releases</a><br>
         2. Extract to: FarmSatthi/reports/lib/tcpdf/<br>
         3. Refresh this page<br><br>
         Or use Composer: <code>composer require tecnickcom/tcpdf</code>');
}

require_once $tcpdfPath;

$conn = getDBConnection();
$isolationWhere = getDataIsolationWhere();

// Get parameters
$reportType = sanitizeInput($_GET['type'] ?? 'dashboard');
$date_from = sanitizeInput($_GET['date_from'] ?? date('Y-01-01'));
$date_to = sanitizeInput($_GET['date_to'] ?? date('Y-m-d'));

// Create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('FarmSaathi');
$pdf->SetAuthor('FarmSaathi Farm Management System');
$pdf->SetTitle('Farm Report - ' . ucfirst($reportType));
$pdf->SetSubject('Farm Management Report');

// Set default header data
$pdf->SetHeaderData('', 0, 'FarmSaathi Farm Management', 'Report Generated: ' . date('Y-m-d H:i:s'));

// Set header and footer fonts
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// Set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// Set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// Set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// Set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', '', 10);

// Generate content based on report type
if ($reportType === 'dashboard') {
    // Financial Summary
    $financialSummary = $conn->query("
        SELECT 
            SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income,
            SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expense
        FROM finance 
        WHERE transaction_date BETWEEN '$date_from' AND '$date_to'
        AND $isolationWhere
    ")->fetch_assoc();
    
    $netProfit = ($financialSummary['total_income'] ?? 0) - ($financialSummary['total_expense'] ?? 0);
    $profitMargin = ($financialSummary['total_income'] ?? 0) > 0 ? ($netProfit / $financialSummary['total_income']) * 100 : 0;
    
    // Title
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Dashboard Summary Report', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 5, 'Period: ' . date('M d, Y', strtotime($date_from)) . ' to ' . date('M d, Y', strtotime($date_to)), 0, 1, 'C');
    $pdf->Ln(5);
    
    // Financial Summary Table
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'Financial Summary', 0, 1);
    $pdf->SetFont('helvetica', '', 10);
    
    $html = '<table border="1" cellpadding="5">
        <tr style="background-color:#2d7a3e;color:#ffffff;">
            <th width="50%"><b>Metric</b></th>
            <th width="50%"><b>Amount</b></th>
        </tr>
        <tr>
            <td>Total Income</td>
            <td style="color:#28a745;">Rs. ' . number_format($financialSummary['total_income'] ?? 0, 2) . '</td>
        </tr>
        <tr>
            <td>Total Expenses</td>
            <td style="color:#dc3545;">Rs. ' . number_format($financialSummary['total_expense'] ?? 0, 2) . '</td>
        </tr>
        <tr>
            <td><b>Net Profit/Loss</b></td>
            <td style="color:' . ($netProfit >= 0 ? '#28a745' : '#dc3545') . ';"><b>Rs. ' . number_format($netProfit, 2) . '</b></td>
        </tr>
        <tr>
            <td>Profit Margin</td>
            <td>' . number_format($profitMargin, 1) . '%</td>
        </tr>
    </table>';
    
    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Ln(5);
    
    // Crop Performance
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'Top Crop Performance', 0, 1);
    $pdf->SetFont('helvetica', '', 10);
    
    $crops = $conn->query("
        SELECT crop_name, crop_type, area_hectares, expected_yield, actual_yield, status
        FROM crops 
        WHERE $isolationWhere
        ORDER BY actual_yield DESC
        LIMIT 10
    ");
    
    $html = '<table border="1" cellpadding="4">
        <tr style="background-color:#2d7a3e;color:#ffffff;">
            <th><b>Crop Name</b></th>
            <th><b>Type</b></th>
            <th><b>Area (ha)</b></th>
            <th><b>Expected</b></th>
            <th><b>Actual</b></th>
            <th><b>Status</b></th>
        </tr>';
    
    while ($crop = $crops->fetch_assoc()) {
        $html .= '<tr>
            <td>' . htmlspecialchars($crop['crop_name']) . '</td>
            <td>' . htmlspecialchars($crop['crop_type']) . '</td>
            <td>' . number_format($crop['area_hectares'], 2) . '</td>
            <td>' . ($crop['expected_yield'] ? number_format($crop['expected_yield'], 0) : 'N/A') . '</td>
            <td>' . ($crop['actual_yield'] ? number_format($crop['actual_yield'], 0) : 'Pending') . '</td>
            <td>' . ucfirst($crop['status']) . '</td>
        </tr>';
    }
    
    $html .= '</table>';
    $pdf->writeHTML($html, true, false, true, false, '');
    
    // Top Expenses
    $pdf->AddPage();
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'Top Expense Categories', 0, 1);
    $pdf->SetFont('helvetica', '', 10);
    
    $expenses = $conn->query("
        SELECT category, SUM(amount) as total, COUNT(*) as count
        FROM finance 
        WHERE type = 'expense'
        AND transaction_date BETWEEN '$date_from' AND '$date_to'
        AND $isolationWhere
        GROUP BY category
        ORDER BY total DESC
        LIMIT 10
    ");
    
    $html = '<table border="1" cellpadding="4">
        <tr style="background-color:#2d7a3e;color:#ffffff;">
            <th><b>Category</b></th>
            <th><b>Transactions</b></th>
            <th><b>Amount</b></th>
        </tr>';
    
    while ($expense = $expenses->fetch_assoc()) {
        $html .= '<tr>
            <td>' . htmlspecialchars($expense['category']) . '</td>
            <td>' . $expense['count'] . '</td>
            <td>Rs. ' . number_format($expense['total'], 2) . '</td>
        </tr>';
    }
    
    $html .= '</table>';
    $pdf->writeHTML($html, true, false, true, false, '');
}

// Output PDF
$filename = 'FarmSaathi_' . ucfirst($reportType) . '_Report_' . date('Y-m-d') . '.pdf';
$pdf->Output($filename, 'D'); // D = download
?>
