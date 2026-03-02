<?php

/**
 * Bookings view page
 * Follows PSR-1 and PSR-12 standards
 */

$pdo = connectToDB(DEFAULT_DB);

if (!$pdo) {
    echo '<div class="alert alert-danger">Database connection error</div>';
    return;
}

// Fetch all bookings with details
try {
    // Try to detect the correct date column name
    $dateColumn = 'date';
    try {
        $stmt = $pdo->query("DESCRIBE sessions");
        $columns = $stmt->fetchAll();
        foreach ($columns as $column) {
            if (in_array($column['Field'], ['date', 'start_time', 'session_date'])) {
                $dateColumn = $column['Field'];
                break;
            }
        }
    } catch (Exception $e) {
        // Ignore, use default
    }

    $bookings = $pdo->query("
        SELECT 
            b.*, 
            f.title as film_title,
            h.name as hall_name,
            s.{$dateColumn} as session_date,
            s.price as session_price
        FROM bookings b
        LEFT JOIN sessions s ON b.session_id = s.id
        LEFT JOIN films f ON s.film_id = f.id
        LEFT JOIN halls h ON s.hall_id = h.id
        ORDER BY b.booking_date DESC
    ")->fetchAll();
} catch (Exception $e) {
    $bookings = [];
    error_log("Error fetching bookings: " . $e->getMessage());
}
?>

<div class="fade-in">
    <div class="d-flex justify-between" style="margin-bottom: 20px;">
        <h2><i class="fas fa-ticket-alt"></i> Bookings Management</h2>
        <a href="?action=add_booking" class="btn btn-success">
            <i class="fas fa-plus"></i> New Booking
        </a>
    </div>

    <?php if (empty($bookings)): ?>
        <div class="card text-center">
            <i class="fas fa-ticket-alt" style="font-size: 4rem; color: var(--text-muted); margin-bottom: 20px;"></i>
            <p>No bookings found</p>
            <a href="?action=add_booking" class="btn btn-success">Create First Booking</a>
        </div>
    <?php else: ?>
        <div class="card">
            <table class="bookings-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Film</th>
                        <th>Hall</th>
                        <th>Session Date</th>
                        <th>Customer</th>
                        <th>Seats</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Booking Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $booking): ?>
                        <tr>
                            <td>#<?php echo $booking['id']; ?></td>
                            <td>
                                <strong><?php echo escapeOutput($booking['film_title'] ?? 'N/A'); ?></strong>
                            </td>
                            <td><?php echo escapeOutput($booking['hall_name'] ?? 'N/A'); ?></td>
                            <td><?php echo formatDate($booking['session_date'] ?? ''); ?></td>
                            <td>
                                <?php 
                                $customerName = trim(($booking['customer_name'] ?? '') . ' ' . ($booking['customer_phone'] ?? ''));
                                echo escapeOutput($customerName ?: 'Anonymous');
                                ?>
                            </td>
                            <td class="text-center"><?php echo (int)($booking['seats'] ?? 1); ?></td>
                            <td class="text-right">
                                <?php 
                                $total = ((float)($booking['session_price'] ?? 0)) * ((int)($booking['seats'] ?? 1));
                                echo number_format($total, 2); ?> ₽
                            </td>
                            <td>
                            <?php
                            $status = $booking['status'] ?? 'pending';

                            switch($status) {
                                case 'confirmed':
                                    $statusClass = 'status-confirmed';
                                    break;
                            case 'cancelled':
                                $statusClass = 'status-cancelled';
                                break;
                            default:
                                $statusClass = 'status-pending';
                            }
                            ?>

                                <span class="booking-status <?php echo $statusClass; ?>">
                                    <?php echo ucfirst(escapeOutput($status)); ?>
                                </span>
                            </td>
                            <td><?php echo formatDate($booking['booking_date'] ?? ''); ?></td>
                            <td>
                                <div class="d-flex" style="gap: 5px;">
                                    <a href="edit.php?table=bookings&id=<?php echo $booking['id']; ?>" 
                                       class="btn btn-sm" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?action=view_booking&id=<?php echo $booking['id']; ?>" 
                                       class="btn btn-sm btn-info" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="delete.php?table=bookings&id=<?php echo $booking['id']; ?>" 
                                       class="btn btn-sm btn-danger" 
                                       onclick="return confirmDelete()" 
                                       title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Optional: Add pagination if needed -->
        <?php if (count($bookings) > ROWS_PER_PAGE): ?>
            <div class="pagination">
                <!-- Pagination links would go here -->
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php
// End of file
// EndOfFile