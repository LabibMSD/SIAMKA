<?php
// Test script to verify dashboard queries
require_once 'config/database.php';

echo "Testing Dashboard Queries\n";
echo "========================\n\n";

// Total Aset Kampus
$totalAset = $conn->query("
  SELECT COUNT(*) AS total
  FROM assets
  WHERE deleted_at IS NULL
")->fetch_assoc()['total'] ?? 0;
echo "Total Aset: $totalAset\n";

// Nilai Total Aset (SUM harga)
$nilaiTotalAset = $conn->query("
  SELECT COALESCE(SUM(harga), 0) AS total
  FROM assets
  WHERE deleted_at IS NULL
")->fetch_assoc()['total'] ?? 0;
echo "Nilai Total Aset: Rp " . number_format($nilaiTotalAset, 2) . "\n";

// Total Maintenance Cost
$totalMaintenanceCost = $conn->query("
  SELECT COALESCE(SUM(biaya), 0) AS total
  FROM maintenance_history
")->fetch_assoc()['total'] ?? 0;
echo "Total Maintenance Cost: Rp " . number_format($totalMaintenanceCost, 2) . "\n";

// Maintenance Cost (Current Year)
$maintenanceCostThisYear = $conn->query("
  SELECT COALESCE(SUM(biaya), 0) AS total
  FROM maintenance_history
  WHERE YEAR(tanggal_perawatan) = YEAR(CURDATE())
")->fetch_assoc()['total'] ?? 0;
echo "Maintenance Cost This Year: Rp " . number_format($maintenanceCostThisYear, 2) . "\n";

echo "\nTest completed.\n";
