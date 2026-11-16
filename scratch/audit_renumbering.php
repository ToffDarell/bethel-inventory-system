<?php

function auditDatabase($label, $dbPath) {
    echo "====================================================\n";
    echo "AUDIT FOR: $label ($dbPath)\n";
    echo "====================================================\n";

    if (!file_exists($dbPath)) {
        echo "File does not exist.\n\n";
        return;
    }

    $pdo = new PDO("sqlite:" . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // List all tables
    $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables in DB: " . implode(', ', $tables) . "\n";

    // Check if assets table exists
    if (in_array('assets', $tables)) {
        $assetCount = $pdo->query("SELECT COUNT(*) FROM assets")->fetchColumn();
        echo "Assets count: " . $assetCount . "\n";
        $assetCols = array_column($pdo->query("PRAGMA table_info(assets)")->fetchAll(PDO::FETCH_ASSOC), 'name');
        echo "Asset columns: " . implode(', ', $assetCols) . "\n";

        $sampleAssets = $pdo->query("SELECT id, asset_no, department, location, status " . (in_array('department_id', $assetCols) ? ', department_id' : '') . (in_array('needs_tag_reprint', $assetCols) ? ', needs_tag_reprint' : '') . " FROM assets")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($sampleAssets as $sa) {
            echo "   Asset #" . $sa['id'] . ": " . $sa['asset_no'] . " | Dept: " . ($sa['department'] ?? 'null') . " | Loc: " . ($sa['location'] ?? 'null') . " | DeptID: " . ($sa['department_id'] ?? 'N/A') . " | Reprint: " . ($sa['needs_tag_reprint'] ?? 'N/A') . "\n";
        }
    }

    // Check if asset_number_histories table exists
    $tableCheck = in_array('asset_number_histories', $tables);
    if (!$tableCheck) {
        echo "Table asset_number_histories does not exist in this database.\n\n";
        return;
    }

    // Inspect columns of asset_number_histories
    $cols = $pdo->query("PRAGMA table_info(asset_number_histories)")->fetchAll(PDO::FETCH_ASSOC);
    $colNames = array_column($cols, 'name');

    echo "Columns in asset_number_histories: " . implode(', ', $colNames) . "\n";

    $assetCols = array_column($pdo->query("PRAGMA table_info(assets)")->fetchAll(PDO::FETCH_ASSOC), 'name');
    echo "Columns in assets: " . implode(', ', $assetCols) . "\n";

    // Query all records in asset_number_histories
    $stmt = $pdo->query("
        SELECT h.*, a.asset_no as current_asset_no, a.department_id as current_dept_id, a.needs_tag_reprint,
               " . (in_array('name', $assetCols) ? 'a.name' : (in_array('model', $assetCols) ? 'a.model' : "a.asset_no")) . " as asset_title
        FROM asset_number_histories h
        LEFT JOIN assets a ON h.asset_id = a.id
        ORDER BY h.id ASC
    ");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Total history records: " . count($rows) . "\n";

    $anomalies = [];
    foreach ($rows as $r) {
        // Look for department transfer entries where old and new dept id are identical or unchanged,
        // or where reason mentions transfer/department change.
        // Let's dump all history records so we see everything
        echo sprintf(
            "ID: %d | Asset ID: %d (%s) | Old: %s | New: %s | Reason: %s | ChangedBy: %s | ChangedAt: %s | AssetCurrentNo: %s | CurrentDept: %s | NeedsReprint: %s\n",
            $r['id'] ?? 0,
            $r['asset_id'] ?? 0,
            $r['asset_title'] ?? 'N/A',
            $r['old_no'] ?? $r['old_asset_no'] ?? 'N/A',
            $r['new_no'] ?? $r['new_asset_no'] ?? 'N/A',
            $r['reason'] ?? $r['change_reason'] ?? 'N/A',
            $r['changed_by'] ?? 'N/A',
            $r['changed_at'] ?? $r['created_at'] ?? 'N/A',
            $r['current_asset_no'] ?? 'N/A',
            $r['current_dept_id'] ?? 'N/A',
            isset($r['needs_tag_reprint']) ? ($r['needs_tag_reprint'] ? 'YES' : 'NO') : 'N/A'
        );

        $oldDept = $r['old_department_id'] ?? null;
        $newDept = $r['new_department_id'] ?? null;

        // Check if old and new dept ids are identical or did not actually change
        if ($oldDept !== null && $newDept !== null && (string)$oldDept === (string)$newDept) {
            $anomalies[] = $r;
        }
    }

    // Also check activity_log for asset updates
    $hasActivity = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='activity_log'")->fetch();
    if ($hasActivity) {
        echo "\n--- Activity Log entries regarding renumbering or department changes ---\n";
        $stmt2 = $pdo->query("
            SELECT id, log_name, description, subject_type, subject_id, causer_id, properties, created_at
            FROM activity_log
            WHERE description LIKE '%renumber%' OR description LIKE '%department%' OR description LIKE '%asset_no%' OR description LIKE '%transfer%'
               OR properties LIKE '%asset_no%' OR properties LIKE '%department_id%'
            ORDER BY id ASC
        ");
        $actRows = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        echo "Found " . count($actRows) . " matching activity log rows.\n";
        foreach ($actRows as $act) {
            echo "ActID: " . $act['id'] . " | Subj: " . $act['subject_id'] . " | Desc: " . $act['description'] . " | Props: " . $act['properties'] . " | Time: " . $act['created_at'] . "\n";
        }
    }
}

auditDatabase("Dev Database", "C:/xampp8.2/htdocs/BettleHospital/database/database.sqlite");
auditDatabase("Installed Production AppData (Temp Copy)", "C:/xampp8.2/htdocs/BettleHospital/scratch/temp_installed_prod.sqlite");
auditDatabase("Installed Dev AppData (Temp Copy)", "C:/xampp8.2/htdocs/BettleHospital/scratch/temp_installed_dev.sqlite");
auditDatabase("Desktop Backup Before Update", "C:/Users/ToffD/Desktop/bethel-backup-before-update/database/database.sqlite");
