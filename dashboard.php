<?php
/**
 * Dashboard - Entity Visualization and Management
 * 
 * This page provides a visual interface to view and manage stored entities,
 * including financial data, memories, planning items, and general interactions.
 * 
 * @author AI Personal Assistant Team
 * @version 1.0
 * @since 2025-08-07
 */

require_once 'config.php';

// Initialize database tool
$databaseTool = new DatabaseTool();
$userId = DEFAULT_USER_ID;

// Get counts for each entity type
$entityTypes = ['person', 'event', 'task', 'financial', 'general', 'interaction'];
$entityCounts = [];
$recentEntities = [];

foreach ($entityTypes as $type) {
    $entities = $databaseTool->findEntitiesByType($userId, $type);
    $entityCounts[$type] = count($entities);
    
    // Get the 3 most recent entities of this type
    $recentEntities[$type] = array_slice($entities, -3);
}

// Get all entities for the timeline
$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$allEntitiesQuery = "SELECT * FROM entities WHERE user_id = ? ORDER BY created_at DESC LIMIT 20";
$stmt = $db->prepare($allEntitiesQuery);
$stmt->bind_param("s", $userId);
$stmt->execute();
$timelineEntities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Assistant Dashboard</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f7fa;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 2rem;
        }
        .card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .stat-card {
            text-align: center;
            padding: 1.5rem;
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #667eea;
        }
        .entity-list {
            max-height: 300px;
            overflow-y: auto;
        }
        .entity-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .entity-type {
            background: #667eea;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
        }
        .timeline {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .timeline-item {
            border-left: 3px solid #667eea;
            padding-left: 15px;
            margin-bottom: 15px;
            position: relative;
        }
        .timeline-item::before {
            content: '';
            width: 10px;
            height: 10px;
            background: #667eea;
            border-radius: 50%;
            position: absolute;
            left: -6px;
            top: 5px;
        }
        .nav-buttons {
            margin-bottom: 20px;
        }
        .btn {
            background: #667eea;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            margin-right: 10px;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover {
            background: #556cd6;
        }
    </style>
</head>
<body>
    <div class="nav-buttons">
        <a href="index.php" class="btn">← Back to Chat</a>
        <a href="dashboard.php" class="btn">🔄 Refresh Dashboard</a>
    </div>

    <div class="header">
        <h1>🤖 AI Personal Assistant Dashboard</h1>
        <p>Overview of your intelligent personal data management system</p>
    </div>

    <!-- Statistics Cards -->
    <div class="dashboard-grid">
        <?php foreach ($entityCounts as $type => $count): ?>
        <div class="card stat-card">
            <div class="stat-number"><?= $count ?></div>
            <h3><?= ucfirst($type) ?> Entities</h3>
            <p>Total <?= $type ?> records stored</p>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Recent Activity Timeline -->
    <div class="timeline">
        <h2>📅 Recent Activity Timeline</h2>
        <?php if (empty($timelineEntities)): ?>
            <p>No entities created yet. Start chatting with the assistant to see your data here!</p>
        <?php else: ?>
            <?php foreach ($timelineEntities as $entity): ?>
                <?php $data = json_decode($entity['data'], true); ?>
                <div class="timeline-item">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong><?= htmlspecialchars($entity['primary_name']) ?></strong>
                            <span class="entity-type"><?= ucfirst($entity['type']) ?></span>
                        </div>
                        <small><?= date('M j, Y H:i', strtotime($entity['created_at'])) ?></small>
                    </div>
                    <p style="margin: 5px 0; color: #666;">
                        <?= htmlspecialchars($data['triage_summary'] ?? 'No summary available') ?>
                    </p>
                    <small style="color: #999;">
                        Original query: "<?= htmlspecialchars(substr($data['original_query'] ?? '', 0, 100)) ?><?= strlen($data['original_query'] ?? '') > 100 ? '...' : '' ?>"
                    </small>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
