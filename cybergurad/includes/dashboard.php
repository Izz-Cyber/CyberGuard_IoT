<?php
$pageTitle = "CyberGuard IoT - Admin Dashboard";
include 'header.php';
require 'db_connect.php';

// Handling Search and Filter
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$filter_status = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : '';

// Fetch Statistics
$count_total = $conn->query("SELECT COUNT(*) as total FROM assessments")->fetch_assoc()['total'];
$count_high = $conn->query("SELECT COUNT(*) as total FROM assessments WHERE status = 'High Risk'")->fetch_assoc()['total'];
$count_med = $conn->query("SELECT COUNT(*) as total FROM assessments WHERE status = 'Medium'")->fetch_assoc()['total'];
$count_safe = $conn->query("SELECT COUNT(*) as total FROM assessments WHERE status = 'Safe'")->fetch_assoc()['total'];

// Build Query
$where = [];
if (!empty($search)) $where[] = "d.device_name LIKE '%$search%'";
if (!empty($filter_status)) $where[] = "a.status = '$filter_status'";
$where_sql = count($where) > 0 ? " WHERE " . implode(" AND ", $where) : "";

$sql_recent = "SELECT a.id, d.device_name, d.manufacturer, a.status, a.assessment_date 
               FROM assessments a JOIN devices d ON a.device_id = d.id $where_sql ORDER BY a.assessment_date DESC";
$recent_results = $conn->query($sql_recent);
?>

<style>
    /* Dashboard Specific Styles */
    .dashboard-wrapper {
        max-width: 1200px;
        margin: 40px auto;
        padding: 0 20px;
    }

    .page-header {
        margin-bottom: 40px;
    }

    .page-header h1 {
        font-size: 2.5rem;
        color: var(--white);
        margin-bottom: 10px;
    }

    /* Stats Cards */
    .stats-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 20px;
        margin-bottom: 40px;
    }

    .stat-card {
        background: var(--medium-blue);
        padding: 30px;
        border-radius: 15px;
        border-left: 6px solid var(--cyan);
        box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        transition: all 0.3s ease;
    }

    .stat-card:hover { transform: translateY(-5px); }
    .stat-card h3 { font-size: 1rem; color: var(--light-gray); margin-bottom: 15px; opacity: 0.8; }
    .stat-card .number { font-size: 2.5rem; font-weight: 700; color: var(--white); }

    /* Filter Bar */
    .filter-section {
        background: var(--medium-blue);
        padding: 25px;
        border-radius: 15px;
        margin-bottom: 30px;
        display: flex;
        gap: 20px;
        align-items: flex-end;
        flex-wrap: wrap;
    }

    .filter-group { flex: 1; min-width: 200px; }
    .filter-group label { display: block; margin-bottom: 10px; font-size: 0.9rem; color: var(--cyan); }
    
    .filter-control {
        width: 100%;
        padding: 12px 15px;
        background: var(--dark-blue);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 8px;
        color: white;
        outline: none;
    }

    .btn-apply {
        background: var(--cyan);
        color: white;
        border: none;
        padding: 12px 30px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        height: 46px;
    }

    /* Table Styles */
    .table-card {
        background: var(--medium-blue);
        border-radius: 15px;
        padding: 20px;
        box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        overflow: hidden;
    }

    .custom-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }

    .custom-table th {
        text-align: left;
        padding: 20px;
        background: rgba(255,255,255,0.03);
        color: var(--cyan);
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 1px;
    }

    .custom-table td {
        padding: 20px;
        border-bottom: 1px solid rgba(255,255,255,0.05);
        color: var(--white);
    }

    .custom-table tr:last-child td { border-bottom: none; }
    .custom-table tr:hover { background: rgba(255,255,255,0.02); }

    /* Status Badges */
    .status-badge {
        padding: 6px 15px;
        border-radius: 30px;
        font-size: 0.8rem;
        font-weight: 600;
        display: inline-block;
    }

    .status-safe { background: rgba(6, 214, 160, 0.15); color: #06D6A0; border: 1px solid #06D6A0; }
    .status-medium { background: rgba(255, 183, 3, 0.15); color: #ffb703; border: 1px solid #ffb703; }
    .status-high { background: rgba(230, 57, 70, 0.15); color: #e63946; border: 1px solid #e63946; }

    .btn-view-report {
        color: var(--cyan);
        text-decoration: none;
        font-weight: 600;
        font-size: 0.9rem;
        border: 1px solid var(--cyan);
        padding: 8px 16px;
        border-radius: 6px;
        transition: all 0.3s;
    }

    .btn-view-report:hover {
        background: var(--cyan);
        color: white;
    }
</style>

<div class="dashboard-wrapper">
    <div class="page-header">
        <h1>🛡️ Security Dashboard</h1>
        <p class="text-muted">Manage and monitor all IoT device security assessments.</p>
    </div>

    <!-- Stats Cards -->
    <div class="stats-container">
        <div class="stat-card" style="border-color: var(--cyan);">
            <h3>Total Assessments</h3>
            <div class="number"><?php echo $count_total; ?></div>
        </div>
        <div class="stat-card" style="border-color: #e63946;">
            <h3>High Risk</h3>
            <div class="number"><?php echo $count_high; ?></div>
        </div>
        <div class="stat-card" style="border-color: #ffb703;">
            <h3>Medium Risk</h3>
            <div class="number"><?php echo $count_med; ?></div>
        </div>
        <div class="stat-card" style="border-color: #06D6A0;">
            <h3>Safe Devices</h3>
            <div class="number"><?php echo $count_safe; ?></div>
        </div>
    </div>

    <!-- Filter Bar -->
    <form class="filter-section" method="GET">
        <div class="filter-group">
            <label>Search Device</label>
            <input type="text" name="search" class="filter-control" placeholder="e.g. Smart Camera" value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="filter-group">
            <label>Risk Level</label>
            <select name="status" class="filter-control">
                <option value="">All Levels</option>
                <option value="High Risk" <?php if($filter_status == 'High Risk') echo 'selected'; ?>>High Risk</option>
                <option value="Medium" <?php if($filter_status == 'Medium') echo 'selected'; ?>>Medium</option>
                <option value="Safe" <?php if($filter_status == 'Safe') echo 'selected'; ?>>Safe</option>
            </select>
        </div>
        <button type="submit" class="btn-apply">Apply Filters</button>
        <a href="dashboard.php" style="color: var(--light-gray); text-decoration: none; margin-bottom: 12px;">Reset</a>
    </form>

    <!-- Table -->
    <div class="table-card">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>Device Name</th>
                    <th>Manufacturer</th>
                    <th>Status</th>
                    <th>Scan Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($recent_results->num_rows > 0): ?>
                    <?php while($row = $recent_results->fetch_assoc()): ?>
                        <tr>
                            <td style="font-weight: 600;"><?php echo htmlspecialchars($row['device_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['manufacturer']); ?></td>
                            <td>
                                <span class="status-badge <?php 
                                    echo ($row['status'] == 'High Risk' ? 'status-high' : ($row['status'] == 'Medium' ? 'status-medium' : 'status-safe')); 
                                ?>">
                                    <?php echo $row['status']; ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($row['assessment_date'])); ?></td>
                            <td>
                                <a href="result.php?id=<?php echo $row['id']; ?>" class="btn-view-report">View Report</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 50px; opacity: 0.5;">No records found matching your search.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$conn->close();
include 'footer.php';
?>
