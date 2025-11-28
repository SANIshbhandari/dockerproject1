-- ============================================================================
-- FINANCE TABLE - PROFIT/LOSS QUERIES
-- ============================================================================

-- 1. Get total profit/loss for all time
SELECT 
    SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income,
    SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expense,
    SUM(CASE WHEN type = 'income' THEN amount ELSE -amount END) as profit_loss
FROM finance
WHERE created_by = 1; -- Replace with actual user ID

-- 2. Get profit/loss for specific date range
SELECT 
    SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income,
    SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expense,
    SUM(CASE WHEN type = 'income' THEN amount ELSE -amount END) as profit_loss
FROM finance
WHERE created_by = 1
    AND transaction_date BETWEEN '2024-01-01' AND '2024-12-31';

-- 3. Get monthly profit/loss breakdown
SELECT 
    DATE_FORMAT(transaction_date, '%Y-%m') as month,
    DATE_FORMAT(transaction_date, '%M %Y') as month_name,
    SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income,
    SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expense,
    SUM(CASE WHEN type = 'income' THEN amount ELSE -amount END) as profit_loss
FROM finance
WHERE created_by = 1
GROUP BY DATE_FORMAT(transaction_date, '%Y-%m')
ORDER BY month DESC;

-- 4. Get yearly profit/loss breakdown
SELECT 
    YEAR(transaction_date) as year,
    SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income,
    SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expense,
    SUM(CASE WHEN type = 'income' THEN amount ELSE -amount END) as profit_loss
FROM finance
WHERE created_by = 1
GROUP BY YEAR(transaction_date)
ORDER BY year DESC;

-- 5. Get category-wise income breakdown
SELECT 
    category,
    SUM(amount) as total,
    COUNT(*) as transaction_count,
    AVG(amount) as average_amount
FROM finance
WHERE created_by = 1 AND type = 'income'
GROUP BY category
ORDER BY total DESC;

-- 6. Get category-wise expense breakdown
SELECT 
    category,
    SUM(amount) as total,
    COUNT(*) as transaction_count,
    AVG(amount) as average_amount
FROM finance
WHERE created_by = 1 AND type = 'expense'
GROUP BY category
ORDER BY total DESC;

-- 7. Get top 5 income sources
SELECT 
    category,
    SUM(amount) as total_income
FROM finance
WHERE created_by = 1 AND type = 'income'
GROUP BY category
ORDER BY total_income DESC
LIMIT 5;

-- 8. Get top 5 expense categories
SELECT 
    category,
    SUM(amount) as total_expense
FROM finance
WHERE created_by = 1 AND type = 'expense'
GROUP BY category
ORDER BY total_expense DESC
LIMIT 5;

-- 9. Get daily profit/loss for current month
SELECT 
    transaction_date,
    SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as daily_income,
    SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as daily_expense,
    SUM(CASE WHEN type = 'income' THEN amount ELSE -amount END) as daily_profit
FROM finance
WHERE created_by = 1
    AND YEAR(transaction_date) = YEAR(CURDATE())
    AND MONTH(transaction_date) = MONTH(CURDATE())
GROUP BY transaction_date
ORDER BY transaction_date;

-- 10. Get profit margin percentage
SELECT 
    SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income,
    SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expense,
    SUM(CASE WHEN type = 'income' THEN amount ELSE -amount END) as profit_loss,
    CASE 
        WHEN SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) > 0 
        THEN (SUM(CASE WHEN type = 'income' THEN amount ELSE -amount END) / 
              SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END)) * 100
        ELSE 0
    END as profit_margin_percentage
FROM finance
WHERE created_by = 1
    AND transaction_date BETWEEN '2024-01-01' AND '2024-12-31';

-- 11. Compare current month vs last month
SELECT 
    'Current Month' as period,
    SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income,
    SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expense,
    SUM(CASE WHEN type = 'income' THEN amount ELSE -amount END) as profit
FROM finance
WHERE created_by = 1
    AND YEAR(transaction_date) = YEAR(CURDATE())
    AND MONTH(transaction_date) = MONTH(CURDATE())

UNION ALL

SELECT 
    'Last Month' as period,
    SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income,
    SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expense,
    SUM(CASE WHEN type = 'income' THEN amount ELSE -amount END) as profit
FROM finance
WHERE created_by = 1
    AND transaction_date >= DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 1 MONTH)
    AND transaction_date < DATE_FORMAT(CURDATE(), '%Y-%m-01');

-- 12. Get running balance (cumulative profit/loss)
SELECT 
    transaction_date,
    type,
    category,
    amount,
    @running_total := @running_total + 
        CASE WHEN type = 'income' THEN amount ELSE -amount END as running_balance
FROM finance, (SELECT @running_total := 0) as init
WHERE created_by = 1
ORDER BY transaction_date, id;

-- 13. Get transactions with profit/loss impact
SELECT 
    id,
    transaction_date,
    type,
    category,
    amount,
    description,
    CASE 
        WHEN type = 'income' THEN CONCAT('+', amount)
        ELSE CONCAT('-', amount)
    END as impact
FROM finance
WHERE created_by = 1
ORDER BY transaction_date DESC
LIMIT 50;

-- 14. Get financial health score (0-100)
SELECT 
    CASE 
        WHEN total_income = 0 THEN 0
        WHEN profit_loss >= total_income * 0.3 THEN 100
        WHEN profit_loss >= total_income * 0.2 THEN 80
        WHEN profit_loss >= total_income * 0.1 THEN 60
        WHEN profit_loss >= 0 THEN 40
        WHEN profit_loss >= -total_income * 0.1 THEN 20
        ELSE 0
    END as health_score,
    total_income,
    total_expense,
    profit_loss
FROM (
    SELECT 
        SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income,
        SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expense,
        SUM(CASE WHEN type = 'income' THEN amount ELSE -amount END) as profit_loss
    FROM finance
    WHERE created_by = 1
        AND transaction_date >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)
) as yearly_summary;

-- 15. Get average monthly profit/loss
SELECT 
    AVG(monthly_profit) as avg_monthly_profit,
    MIN(monthly_profit) as worst_month,
    MAX(monthly_profit) as best_month
FROM (
    SELECT 
        DATE_FORMAT(transaction_date, '%Y-%m') as month,
        SUM(CASE WHEN type = 'income' THEN amount ELSE -amount END) as monthly_profit
    FROM finance
    WHERE created_by = 1
    GROUP BY DATE_FORMAT(transaction_date, '%Y-%m')
) as monthly_data;
